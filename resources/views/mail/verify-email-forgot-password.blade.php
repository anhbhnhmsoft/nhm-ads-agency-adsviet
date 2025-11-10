<x-mail::message>
    {{ __('mail.verify_email_forgot_password.greeting', ['username' => $username]) }}
    <br>
    {{ __('mail.verify_email_forgot_password.otp', ['otp' => $otp]) }}
    <br>
    {{ __('mail.verify_email_forgot_password.expire', ['expire_time' => $expireTime]) }}
    <br>
    {{ __('mail.verify_email_forgot_password.footer') }}
    <br>
    {{ __('mail.verify_email_forgot_password.thanks') }}
    <br>
</x-mail::message>
