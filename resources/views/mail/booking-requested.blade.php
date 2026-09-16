@extends('mail.layout', ['title' => 'Request sent'])

@section('content')
  <p style="line-height:1.6;">Hi {{ $name }}, your host has your request. They will confirm or decline it. Pay at the destination if they accept.</p>
  <table style="width:100%;font-size:14px;margin:16px 0;" cellpadding="0" cellspacing="0">
    <tr><td style="padding:6px 0;color:#5C6B73;">Booking code</td><td style="padding:6px 0;font-weight:700;">{{ $bookingCode }}</td></tr>
    <tr><td style="padding:6px 0;color:#5C6B73;">Package</td><td style="padding:6px 0;">{{ $packageTitle }}</td></tr>
    <tr><td style="padding:6px 0;color:#5C6B73;">Dates</td><td style="padding:6px 0;">{{ $checkIn }} → {{ $checkOut }}</td></tr>
    <tr><td style="padding:6px 0;color:#5C6B73;">Guests</td><td style="padding:6px 0;">{{ $guests }}</td></tr>
    <tr><td style="padding:6px 0;color:#5C6B73;">Pay at destination</td><td style="padding:6px 0;font-weight:700;">Rs. {{ $total }}</td></tr>
  </table>
@endsection
