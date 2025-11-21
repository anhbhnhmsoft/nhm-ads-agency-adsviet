<?php

namespace App\Http\Controllers;

use App\Core\Controller;
use App\Core\FlashMessage;
use App\Core\QueryListDTO;
use App\Http\Requests\Service\ServicePurchaseRequest;
use App\Http\Resources\ServicePackageListResource;
use App\Service\ServicePackageService;
use App\Service\ServicePurchaseService;
use App\Service\WalletService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class ServicePurchaseController extends Controller
{
    public function __construct(
        protected ServicePurchaseService $servicePurchaseService,
        protected ServicePackageService $servicePackageService,
        protected WalletService $walletService,
    ) {}

    public function index()
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $result = $this->servicePackageService->getListServicePackage(new QueryListDTO(
            perPage: 100,
            page: 1,
            filter: [],
            sortBy: 'created_at',
            sortDirection: 'desc',
        ));

        $packages = collect();
        if ($result->isSuccess()) {
            $paginator = $result->getData();
            $items = method_exists($paginator, 'items') ? $paginator->items() : (array) $paginator;
            $packages = collect($items)
                ->filter(fn ($pkg) => !$pkg->disabled)
                ->values();
        }

        $walletResult = $this->walletService->getWalletForUser((int) $user->id);
        $wallet = $walletResult->isSuccess() ? $walletResult->getData() : null;
        $walletBalance = $wallet ? (float) $wallet['balance'] : 0;

        return $this->rendering(
            view: 'service-purchase/index',
            data: [
                'packages' => fn() => ServicePackageListResource::collection($packages),
                'wallet_balance' => $walletBalance,
            ]
        );
    }

    public function purchase(ServicePurchaseRequest $request): RedirectResponse
    {
        $user = $request->user();

        if (!$user) {
            FlashMessage::error(__('common_error.service_purchase_login_required'));
            return redirect()->route('login');
        }

        $data = $request->validated();

        $configAccount = [];
        if (isset($data['meta_email'])) {
            $configAccount['meta_email'] = $data['meta_email'];
        }
        if (isset($data['display_name'])) {
            $configAccount['display_name'] = $data['display_name'];
        }

        $result = $this->servicePurchaseService->createPurchaseOrder(
            userId: $user->id,
            packageId: $data['package_id'],
            topUpAmount: isset($data['top_up_amount']) ? (float) $data['top_up_amount'] : 0,
            configAccount: $configAccount,
        );

        if ($result->isError()) {
            FlashMessage::error($result->getMessage());
            return redirect()->back()->withInput();
        }

        FlashMessage::success(__('services.flash.purchase_success'));
        return redirect()->route('service_orders_index');
    }
}
