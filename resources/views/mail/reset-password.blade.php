@extends('mail.layout', ['title' => 'Reset password'])

@section('content')
  <p style="line-height:1.6;">Hi {{ $name }}, use this link to choose a new password. It expires in 60 minutes.</p>
  <p style="margin:16px 0 24px;">
    <a href="{{ $url }}" style="display:inline-block;background:#3E9B6C;color:#fff;text-decoration:none;padding:12px 22px;border-radius:10px;font-weight:600;">Choose new password</a>
  </p>
  <p style="font-size:13px;color:#5C6B73;">If you did not ask for this, you can ignore this email.</p>
  <p style="font-size:13px;color:#5C6B73;">{{ $url }}</p>
@endsection
