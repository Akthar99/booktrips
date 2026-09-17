# Migrating storage to AWS S3

This app stores two kinds of files on the server's local disk:

| What | Disk | Path | Served via |
| --- | --- | --- | --- |
| Package photos | `public` | `storage/app/public/packages/{businessId}/...` | `/storage/...` symlink (`php artisan storage:link`) |
| Payment receipts | `local` | `storage/app/private/receipts/{businessId}/...` | `ReceiptDownloadController` (auth-gated download) |

Moving to S3 removes the local disk as state, which is a prerequisite for running
multiple app servers or Laravel Cloud/Octane on ephemeral hosts.

---

## 1. Install the S3 driver

```bash
composer require league/flysystem-aws-s3-v3 "^3.0"
```

Then `composer dump-autoload` (composer runs it for you).

## 2. Add an S3 disk for receipts

`config/filesystems.php` already ships a `s3` disk driven by `AWS_*` env vars. It is
intended for **public** photos. Add a second, **private** disk for receipts:

```php
's3_private' => [
    'driver' => 's3',
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    'bucket' => env('AWS_PRIVATE_BUCKET'),
    'url' => env('AWS_PRIVATE_URL'),
    'endpoint' => env('AWS_ENDPOINT'),
    'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
    'throw' => false,
    'report' => false,
],
```

## 3. Environment

```dotenv
# Public photos bucket
AWS_ACCESS_KEY_ID=...
AWS_SECRET_ACCESS_KEY=...
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=booktrips-public
AWS_URL=https://cdn.yourdomain.com        # optional CloudFront domain
# AWS_ENDPOINT=                            # leave empty for AWS proper (set for MinIO/R2)

# Private receipts bucket
AWS_PRIVATE_BUCKET=booktrips-private
AWS_PRIVATE_URL=                          # optional; leave empty to keep it fully private
AWS_USE_PATH_STYLE_ENDPOINT=false
```

Create an IAM user scoped to just these two buckets (no root keys): `s3:PutObject`,
`s3:GetObject`, `s3:DeleteObject` on each bucket's ARN.

## 4. Code touch points

### Package photos — `app/Http/Controllers/Partner/PartnerImageController.php`

```php
$path = $file->store("packages/{$business->id}", 's3');

WatermarkPackageImage::dispatchSync('s3', $path);

return Storage::disk('s3')->url($path);
```

> **Watermark caveat.** `WatermarkPackageImage` works on a local file path. On S3 the
> job has no local path, so it currently skips silently (`Watermark skipped …` in the
> log). If you need watermarks on S3, watermark the photo *before* uploading: write it
> to `storage/app/tmp`, run `ImageWatermarker::apply()`, then `Storage::disk('s3')->putFileAs(...)`
> and delete the temp file. This is a small refactor of the upload controller.

### Receipts — `app/Http/Controllers/Partner/PartnerPaymentController.php`

```php
$path = $file->store("receipts/{$business->id}", 's3_private');
```

### Receipt download — `app/Http/Controllers/ReceiptDownloadController.php`

`Storage::disk('local')->download()` streams through the app server, which works but
tunnels every receipt through PHP. For a private bucket, redirect to a short-lived
signed URL instead:

```php
return redirect()->away(
    Storage::disk('s3_private')->temporaryUrl($receipt->file_path, now()->addMinutes(5))
);
```

`temporaryUrl` needs the bucket to allow the server's IAM credentials to sign URLs
(nothing extra beyond the `s3:GetObject` permission already granted).

## 5. Migrating existing files

Install the AWS CLI and copy each tree into its new home. Bucket keys must match what
the code writes (`packages/...` and `receipts/...`).

```bash
# Public photos → public bucket (no key prefix, matching the code above)
aws s3 sync storage/app/public s3://booktrips-public --acl public-read

# Private receipts → private bucket
aws s3 sync storage/app/private s3://booktrips-private
```

On Windows PowerShell use the same commands with the AWS CLI installed and
`aws configure` completed for the scoped IAM user.

If `storage/app/public` was linked via `php artisan storage:link`, `aws s3 sync` follows
the symlink; if not, sync `storage/app/public` directly.

## 6. Public access

- **Public bucket:** either leave `--acl public-read` on every object (simplest), or
  restrict the bucket and front it with a CloudFront distribution whose origin access
  control can read the bucket. Set `AWS_URL` to the CloudFront domain and add
  `'bucket_endpoint' => false` (the default) so Laravel generates `https://cdn.../key`
  URLs.
- **Private bucket:** block all public access; downloads go through the signed-URL
  redirect above.

## 7. Remove the local-disk assumptions

- You can delete `php artisan storage:link` (`public/storage`) once photos come from S3.
- Keep `FILESYSTEM_DISK=local` — the app always addresses explicit disks (`public`,
  `local`, `s3`, `s3_private`), it never relies on the default.
- `Storage::fake('public')` / `Storage::fake('local')` in the tests keep working
  unchanged; the watermark test writes to the fake local disk.

## 8. Verify

1. Upload a photo as a partner → image loads from the S3 URL (and is watermarked if you
   kept the pre-upload watermark step).
2. Upload a receipt for an open invoice → admin can download it from `/receipts/{id}`.
3. Check `storage/logs/laravel.log` has no `Watermark skipped` surprises and no S3 auth errors.
4. Confirm `aws s3 ls s3://booktrips-public/packages/` shows the new object immediately.

## 9. Rollback

Point the two controllers back to `'public'` / `'local'` and re-run
`php artisan storage:link`; the `s3`/`s3_private` disks simply become unused.
