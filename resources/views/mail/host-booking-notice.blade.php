@extends('mail.layout', ['title' => $isReminder ? 'Still waiting on a request' : 'New request'])

@section('content')
  <p style="line-height:1.6;">
    {{ $guestName }} requested <strong>{{ $packageTitle }}</strong>
    ({{ $checkIn }} → {{ $checkOut }}, {{ $guests }} guests) — booking {{ $bookingCode }}.
  </p>
  <p style="line-height:1.6;">Open your partner dashboard to confirm or reject it.</p>
  @if ($isReminder)
    <p style="line-height:1.6;">This request has been waiting for more than 24 hours.</p>
  @endif
@endsection
