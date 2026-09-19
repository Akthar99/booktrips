# File storage on AWS S3

Partner photos and payment receipts live on S3 once `AWS_BUCKET` is set:

| What             | Disk | Key prefix                  | Served via                                                                         |
| ---------------- | ---- | --------------------------- | ---------------------------------------------------------------------------------- |
| Package photos   | `s3` | `packages/{businessId}/...` | public URL — `https://{bucket}.s3.{region}.amazonaws.com/...` (or `AWS_URL` / CDN) |
| Payment receipts | `s3` | `receipts/{businessId}/...` | 5-minute **presigned URL** via `ReceiptDownloadController` (never public)          |

When `AWS_BUCKET` is empty (local dev, CI, tests) the app falls back to the local `public` and
`local` disks, so nothing here is required to run the project on your machine. `phpunit.xml` pins
the local disks explicitly.

## How it works in code

- `config/booktrips.php` → `storage.images_disk` / `storage.receipts_disk` (overridable with
  `BOOKTRIPS_IMAGES_DISK` / `BOOKTRIPS_RECEIPTS_DISK`) choose the disks; both default to `s3`
  when `AWS_BUCKET` is set.
- `PartnerImageController` watermarks the **local upload** and then stores it — a single
  watermarked object lands in S3, with no queue worker and no re-download/re-upload round trip.
- `App\Services\MediaUrl` resolves every stored image value to a browsable URL. It accepts
  absolute URLs (new uploads), legacy `/storage/...` paths and bare keys — so **existing database
  rows keep working after the move** — and it only ever deletes objects under our own bucket/CDN.
- Removing a photo in the package editor deletes the S3 object (`MediaUrl::deleteRemoved`).
- `ReceiptDownloadController` redirects to a signed URL on remote disks and streams local files.

## 1. Environment

```dotenv
AWS_ACCESS_KEY_ID=...
AWS_SECRET_ACCESS_KEY=...
AWS_DEFAULT_REGION=ap-southeast-1
AWS_BUCKET=booktips-bucket
# AWS_URL=                       # optional: CloudFront / custom domain in front of the bucket
AWS_USE_PATH_STYLE_ENDPOINT=false

# Optional explicit overrides (both default to s3 as soon as AWS_BUCKET is set):
# BOOKTRIPS_IMAGES_DISK=s3
# BOOKTRIPS_RECEIPTS_DISK=s3
```

`AWS_URL` is not required — Laravel generates `https://{bucket}.s3.{region}.amazonaws.com/...`
URLs. Set it only when you front the bucket with CloudFront or a custom domain.

## 2. Bucket setup

1. Create the bucket (e.g. `booktips-bucket`) in `ap-southeast-1`.
2. Scope an IAM user (no root keys) to that bucket:

```json
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Effect": "Allow",
            "Action": ["s3:GetObject", "s3:PutObject", "s3:DeleteObject"],
            "Resource": "arn:aws:s3:::booktips-bucket/*"
        },
        {
            "Effect": "Allow",
            "Action": ["s3:ListBucket"],
            "Resource": "arn:aws:s3:::booktips-bucket"
        }
    ]
}
```

3. Make **only the photos** public — either turn off "Block Public Access" and add a bucket
   policy, or front the bucket with CloudFront:

```json
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Sid": "PublicPackages",
            "Effect": "Allow",
            "Principal": "*",
            "Action": "s3:GetObject",
            "Resource": "arn:aws:s3:::booktips-bucket/packages/*"
        }
    ]
}
```

Receipts (`receipts/*`) are never covered by a public policy — the partner/admin download route
signs a 5-minute URL with the IAM credentials above.

## 3. One-time copy of existing files

Run this **on the server** (SSH / Ploi terminal) after deploying, with the AWS CLI configured for
the scoped IAM user. Bucket keys must match what the code writes:

```bash
cd /home/ploi/booktrips.lk
aws s3 sync storage/app/public/packages s3://booktips-bucket/packages
aws s3 sync storage/app/private/receipts s3://booktips-bucket/receipts
```

Legacy `/storage/...` values already in the database keep working — `MediaUrl` rewrites them to
bucket URLs at render time, so no database update is needed.

## 4. Watermarks

Watermarking happens **before** the file reaches the bucket: `ImageWatermarker` (GD) stamps the
local upload, then the controller stores the stamped file on the configured disk. That means S3
never sees an unwatermarked version, there is no background job to keep alive, and partners wait
only for GD + the transfer. `php -m` must list `gd` on the server (Ploi → PHP → Extensions).

## 5. Verify

1. Upload a photo as a partner → `aws s3 ls s3://booktips-bucket/packages/` shows the new object
   immediately; the returned URL loads and is watermarked.
2. Upload a receipt → the object appears under `receipts/...`; download it from the admin console
   (redirects to a signed URL that expires in 5 minutes).
3. Remove a photo in the package editor → the object disappears from the bucket.
4. `storage/logs/laravel.log` has no S3 auth errors and no "Watermark skipped" for the new files.

## 6. Rollback

Set `BOOKTRIPS_IMAGES_DISK=public` and `BOOKTRIPS_RECEIPTS_DISK=local` (or clear `AWS_BUCKET`) and
the app is back on local disks; keep `php artisan storage:link` in place and the S3 files remain
as a backup.
