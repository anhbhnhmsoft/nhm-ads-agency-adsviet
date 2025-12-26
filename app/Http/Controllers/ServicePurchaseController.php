<?php

namespace App\Http\Controllers;

use App\Common\Helpers\TimezoneHelper;
use App\Core\Controller;
use App\Core\FlashMessage;
use App\Core\QueryListDTO;
use App\Http\Requests\Service\ServicePurchaseRequest;
use App\Http\Resources\ServicePackageListResource;
use App\Service\ConfigService;
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
        protected ConfigService $configService,
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
        $postpayMinBalanceRaw = $this->configService->getValue(\App\Common\Constants\Config\ConfigName::POSTPAY_MIN_BALANCE->value, 200);
        $postpayMinBalance = is_numeric($postpayMinBalanceRaw) ? (float) $postpayMinBalanceRaw : 200;

        // Nếu package có danh sách users được phép trả sau và user hiện tại có trong danh sách => true
        $postpayPermissions = [];
        foreach ($packages as $package) {
            $postpayUserIds = $this->servicePackageService->getPostpayUserIds($package->id);
            if ($postpayUserIds->isError()) {
                $postpayPermissions[$package->id] = false;
                continue;
            }
            $allowedUserIds = $postpayUserIds->getData();
            // Nếu danh sách rỗng => không cho phép (ẩn nút)
            if (empty($allowedUserIds)) {
                $postpayPermissions[$package->id] = false;
            } else {
                // Nếu có danh sách => chỉ những user trong danh sách mới được phép
                $postpayPermissions[$package->id] = in_array((string) $user->id, $allowedUserIds);
            }
        }

        return $this->rendering(
            view: 'service-purchase/index',
            data: [
                'packages' => fn() => ServicePackageListResource::collection($packages),
                'wallet_balance' => $walletBalance,
                'postpay_min_balance' => $postpayMinBalance,
                'meta_timezones' => TimezoneHelper::getMetaTimezoneOptions(),
                'google_timezones' => TimezoneHelper::getGoogleTimezoneOptions(),
                'postpay_permissions' => $postpayPermissions,
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
        if (isset($data['bm_id'])) {
            $configAccount['bm_id'] = $data['bm_id'];
        }
        if (isset($data['info_fanpage'])) {
            $configAccount['info_fanpage'] = $data['info_fanpage'];
        }
        if (isset($data['info_website'])) {
            $configAccount['info_website'] = $data['info_website'];
        }
        if (isset($data['payment_type'])) {
            $configAccount['payment_type'] = $data['payment_type'];
        }
        if (isset($data['asset_access'])) {
            $configAccount['asset_access'] = $data['asset_access'];
        }
        if (isset($data['timezone_bm'])) {
            $configAccount['timezone_bm'] = $data['timezone_bm'];
        }

        $result = $this->servicePurchaseService->createPurchaseOrder(
            userId: $user->id,
            packageId: $data['package_id'],
            topUpAmount: isset($data['top_up_amount']) ? (float) $data['top_up_amount'] : 0,
            budget: isset($data['budget']) ? (float) $data['budget'] : 0,
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
