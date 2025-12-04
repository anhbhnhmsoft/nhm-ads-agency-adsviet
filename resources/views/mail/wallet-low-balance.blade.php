<x-mail::message>
    {{ __('mail.wallet_low_balance.greeting', ['username' => $username]) }}
    <br>
    {{ __('mail.wallet_low_balance.description', ['balance' => $balance, 'threshold' => $threshold]) }}
    <br>
    {{ __('mail.wallet_low_balance.action') }}
    <br>
    {{ __('mail.wallet_low_balance.thanks') }}
</x-mail::message>

