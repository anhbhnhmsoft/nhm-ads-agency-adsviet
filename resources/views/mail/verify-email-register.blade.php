<x-mail::message>
    {{ __('mail.verify_email_register.greeting', ['username' => $username]) }}
    <br>
    {{ __('mail.verify_email_register.thank_for_register', ['otp' => $otp]) }}
    <br>
    {{ __('mail.verify_email_register.expire', ['expire_time' => $expireTime]) }}
    <br>
    {{ __('mail.verify_email_register.footer') }}
    <br>
    {{ __('mail.verify_email_register.thanks') }}
    <br>
</x-mail::message>
