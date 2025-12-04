<x-mail::message>
    {{ __('service_user.mail.greeting', ['user' => $username]) }}
    <br>
    {{ __('service_user.mail.content.' . $statusKey, ['package' => $package]) }}
    <br>
    {{ __('service_user.mail.footer') }}
</x-mail::message>


