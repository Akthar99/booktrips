@extends('mail.layout', ['title' => 'Verify your email'])

@section('content')
  <p style="line-height:1.6;margin:0 0 16px;">Welcome, {{ $name }}. Confirm your email to book day outs, camping nights, villas and activity packages across Sri Lanka.</p>
  <p style="margin:0 0 24px;">
    <a href="{{ $url }}" style="display:inline-block;background:#2D6A4F;color:#fff;text-decoration:none;padding:12px 22px;border-radius:10px;font-weight:600;">Verify email</a>
  </p>
  <p style="font-size:13px;color:#5C6B73;line-height:1.5;">If the button does not work, copy this link:<br>{{ $url }}</p>
  <p style="font-size:13px;color:#5C6B73;">This link expires in {{ $expiresIn ?? '60 minutes' }}.</p>
@endsection
