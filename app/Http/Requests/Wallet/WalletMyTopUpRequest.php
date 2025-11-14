<?php

namespace App\Http\Requests\Wallet;

use App\Service\NowPaymentsService;
use Illuminate\Foundation\Http\FormRequest;

class WalletMyTopUpRequest extends FormRequest
{
    public function __construct(
        protected NowPaymentsService $nowPaymentsService,
    ) {
        parent::__construct();
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $selectedNetwork = $this->input('network', 'BEP20');
        $minAmount = $this->nowPaymentsService->getMinimalAmountForNetwork($selectedNetwork);

        return [
            'amount' => ['required', 'numeric', 'min:' . $minAmount],
            'network' => ['required', 'string', 'in:BEP20,TRC20'],
        ];
    }

    public function messages(): array
    {
        $selectedNetwork = $this->input('network', 'BEP20');
        $minAmount = $this->nowPaymentsService->getMinimalAmountForNetwork($selectedNetwork);

        return [
            'amount.required' => __('common_validation.amount.required'),
            'amount.numeric' => __('common_validation.amount.numeric'),
            'amount.min' => __('Số tiền tối thiểu cho :network là :amount USD', [
                'network' => $selectedNetwork,
                'amount' => number_format($minAmount, 2),
            ]),
            'network.required' => __('common_validation.network.required'),
            'network.string' => __('common_validation.network.string'),
            'network.in' => __('common_validation.network.in'),
        ];
    }
}
