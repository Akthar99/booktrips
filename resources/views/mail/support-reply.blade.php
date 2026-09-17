@extends('mail.layout', ['title' => 'We replied to your request'])

@section('content')
  <p style="line-height:1.6;">Hi {{ $name }}, our team answered your request <strong>{{ $subject }}</strong>.</p>
  <div style="margin:16px 0;padding:14px 16px;background:#F7F4EE;border-radius:12px;line-height:1.6;white-space:pre-line;">{{ $body }}</div>
  <p style="margin:22px 0;">
    <a href="{{ $url }}" style="display:inline-block;background:#2D6A4F;color:#ffffff;text-decoration:none;padding:12px 20px;border-radius:999px;font-weight:700;">Open the conversation</a>
  </p>
  <p style="line-height:1.6;color:#5C6B73;font-size:13px;">Reply from that page and it comes straight back to us.</p>
@endsection
