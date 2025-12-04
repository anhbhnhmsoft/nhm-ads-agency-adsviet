<x-mail::message>
    {{ __('mail.wallet_transaction.greeting', ['username' => $username]) }}
    <br>
    {{ __('mail.wallet_transaction.summary', ['type' => $type, 'amount' => $amount]) }}
    @if(!empty($description))
        <br>
        {{ __('mail.wallet_transaction.description', ['description' => $description]) }}
    @endif
    <br>
    {{ __('mail.wallet_transaction.thanks') }}
</x-mail::message>


