<x-mail::message>
    {{ __('mail.admin_wallet_transaction.greeting', ['admin' => $adminName]) }}
    <br>
    {{ __('mail.admin_wallet_transaction.summary', [
        'customer' => $customerName,
        'type' => $transactionType,
        'amount' => $amount,
    ]) }}
    @if(!empty($stage))
        <br>
        {{ __('mail.admin_wallet_transaction.stage_'.$stage) }}
    @endif
    @if(!empty($description))
        <br>
        {{ __('mail.admin_wallet_transaction.description', ['description' => $description]) }}
    @endif
    <br>
    {{ __('mail.admin_wallet_transaction.thanks') }}
</x-mail::message>


