<div>
    <h1>Hello {{ $otp_code->user->name }}, thank you for using our service</h1>
    @if ($type == "email")
        <h1>Your OTP Code: {{ $otp_code->email_code }}</h1>
    @else
        <h1>Your OTP Code: {{ $otp_code->sms_code }}</h1>
    @endif
</div>