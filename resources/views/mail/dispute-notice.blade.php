@extends('mail.layout', ['title' => $headline])

@section('content')
  <p style="line-height:1.6;">Hi {{ $name }},</p>
  <p style="line-height:1.6;">{{ $body }}</p>
  @if ($deadline)
    <p style="line-height:1.6;color:#5C6B73;">Please share your side before <strong>{{ $deadline }}</strong> so BookTrips can make a fair decision.</p>
  @endif
  <p style="margin:22px 0;">
    <a href="{{ $url }}" style="display:inline-block;background:#2D6A4F;color:#ffffff;text-decoration:none;padding:12px 20px;border-radius:999px;font-weight:700;">Open booking {{ $code }}</a>
  </p>
  <p style="line-height:1.6;color:#5C6B73;font-size:13px;">BookTrips never acts on a report before both sides have had a chance to explain. Penalties only follow a review by our team.</p>
@endsection
