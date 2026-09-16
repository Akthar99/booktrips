@extends('mail.layout', ['title' => 'You are approved'])

@section('content')
  <p style="line-height:1.6;">Hi {{ $name }}, great news — <strong>{{ $businessName }}</strong> has been approved as a BookTrips partner.</p>
  <p style="line-height:1.6;">Your partner panel is open. Publish your first package so travellers can start booking it, and you will get an email and an in-app alert whenever a request arrives.</p>
  <p style="margin:22px 0;">
    <a href="{{ $newPackageUrl }}" style="display:inline-block;background:#2D6A4F;color:#ffffff;text-decoration:none;padding:12px 20px;border-radius:999px;font-weight:700;">Create your first package</a>
  </p>
  <p style="line-height:1.6;color:#5C6B73;font-size:13px;">You can always find your dashboard at <a href="{{ $dashboardUrl }}" style="color:#2D6A4F;">{{ $dashboardUrl }}</a>. Commission is 10% of completed bookings, invoiced monthly — pay by bank transfer and upload the receipt from the Finance tab.</p>
@endsection
