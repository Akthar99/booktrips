@extends('mail.layout', ['title' => 'Confirm this email'])

@section('content')
  <p style="line-height:1.6;">Hi {{ $name }}, confirm this address to finish changing your BookTrips email.</p>
  <p style="margin:16px 0 24px;">
    <a href="{{ $url }}" style="display:inline-block;background:#3E9B6C;color:#fff;text-decoration:none;padding:12px 22px;border-radius:10px;font-weight:600;">Confirm email</a>
  </p>
  <p style="font-size:13px;color:#5C6B73;">This link expires in 24 hours.</p>
  <p style="font-size:13px;color:#5C6B73;">{{ $url }}</p>
@endsection
