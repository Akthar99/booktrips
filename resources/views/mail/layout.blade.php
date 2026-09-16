<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width">
<title>{{ $title ?? 'BookTrips' }}</title>
</head>
<body style="margin:0;padding:0;background:#F7F4EE;font-family:Segoe UI,Arial,sans-serif;color:#1A1A1A;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#F7F4EE;padding:32px 16px;">
    <tr><td align="center">
      <table width="560" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:16px;overflow:hidden;max-width:560px;width:100%;">
        <tr>
          <td style="background:#2D6A4F;padding:22px 28px;">
            <div style="font-size:22px;font-weight:700;color:#fff;letter-spacing:-0.3px;">booktrips</div>
            <div style="color:#D8F3DC;font-size:13px;margin-top:4px;">Sri Lanka day outs, stays &amp; outdoor plans</div>
          </td>
        </tr>
        <tr>
          <td style="padding:28px;">
            <h1 style="margin:0 0 16px;font-size:22px;color:#1B4332;">{{ $title ?? '' }}</h1>
            @yield('content')
          </td>
        </tr>
        <tr>
          <td style="padding:16px 28px 24px;color:#5C6B73;font-size:12px;border-top:1px solid #EEEAE3;">
            BookTrips · Pay at destination · Made for exploring Sri Lanka
          </td>
        </tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
