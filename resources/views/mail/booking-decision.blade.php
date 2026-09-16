@extends('mail.layout', ['title' => $confirmed ? 'You’re confirmed' : 'Booking '.$status])

@section('content')
  <p style="line-height:1.6;">Hi {{ $name }}, your booking {{ $bookingCode }} is now <strong>{{ $status }}</strong>.</p>
  @if ($confirmed)
    <p style="line-height:1.6;">See you at {{ $packageTitle }}. Pay at the destination as agreed.</p>
  @endif
@endsection
