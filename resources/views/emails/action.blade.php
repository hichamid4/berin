@extends('emails.layout')

@section('content')
    <p style="margin:0 0 6px;font-size:12px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:#0f7b6c;">{{ $eyebrow }}</p>
    <h1 style="margin:0 0 16px;font-size:24px;line-height:30px;color:#12232e;">{{ $heading }}</h1>
    <p style="margin:0 0 16px;font-size:15px;line-height:24px;">Hello {{ $name }},</p>
    @foreach ($paragraphs as $paragraph)
        <p style="margin:0 0 16px;font-size:15px;line-height:24px;">{{ $paragraph }}</p>
    @endforeach
    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:24px 0;"><tr>
        <td style="border-radius:10px;background:#0f7b6c;">
            <a href="{{ $url }}" style="display:inline-block;padding:14px 28px;font-size:15px;font-weight:700;color:#ffffff;text-decoration:none;border-radius:10px;">{{ $button }}</a>
        </td>
    </tr></table>
    <p style="margin:0 0 16px;font-size:13px;line-height:20px;color:#64727d;">{{ $expiry }}</p>
    <p style="margin:0 0 6px;font-size:13px;line-height:20px;color:#64727d;">If the button does not work, copy and paste this address into your browser:</p>
    <p style="margin:0 0 20px;font-size:12px;line-height:18px;word-break:break-all;"><a href="{{ $url }}" style="color:#0f7b6c;">{{ $url }}</a></p>
    <p style="margin:0;padding-top:16px;border-top:1px solid #e3e8ee;font-size:13px;line-height:20px;color:#64727d;">{{ $ignore }}</p>
@endsection
