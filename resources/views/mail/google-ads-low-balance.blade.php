<x-mail::message>
    {{ __('mail.google_ads_low_balance.greeting', ['username' => $username]) }}
    <br>
    {{ __('mail.google_ads_low_balance.description', ['accountName' => $accountName, 'balance' => $balance, 'currency' => $currency, 'threshold' => $threshold]) }}
    <br>
    {{ __('mail.google_ads_low_balance.action') }}
    <br>
    {{ __('mail.google_ads_low_balance.thanks') }}
</x-mail::message>

