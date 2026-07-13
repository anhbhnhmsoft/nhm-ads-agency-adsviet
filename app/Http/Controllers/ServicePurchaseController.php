<?php

namespace App\Http\Controllers;

use App\Common\Constants\Config\ConfigName;
use App\Common\Constants\User\UserRole;
use App\Common\Helpers\TimezoneHelper;
use App\Core\Controller;
use App\Core\FlashMessage;
use App\Core\QueryListDTO;
use App\Http\Requests\Service\ServicePurchaseRequest;
use App\Http\Resources\ServicePackageListResource;
use App\Service\ConfigService;
use App\Service\ServicePackageService;
use App\Service\ServicePurchaseService;
use App\Service\UserService;
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
        protected UserService $userService,
    ) {}

    public function index()
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $isStaff = in_array($user->role, [
            UserRole::ADMIN->value,
            UserRole::MANAGER->value,
            UserRole::EMPLOYEE->value,
        ], true);

        $selectedCustomerId = null;
        $customers = [];
        $packages = collect();
        $walletBalance = 0.0;

        if ($isStaff) {
            $customersResult = $this->userService->getPurchasableCustomersForActor($user);
            $customers = $customersResult->isSuccess() ? $customersResult->getData() : [];
            $selectedCustomerId = trim(request()->string('customer_id')->toString());
            $selectedCustomerId = $selectedCustomerId !== '' ? $selectedCustomerId : null;

            if ($selectedCustomerId !== null) {
                if (! $this->userService->canActOnCustomer($user, $selectedCustomerId)) {
                    FlashMessage::error(__('services.validation.customer_scope_denied'));
                    return redirect()->route('service_purchase_index');
                }

                $packages = $this->loadPackagesForUser($selectedCustomerId);
                $walletBalance = $this->getWalletBalanceForUser($selectedCustomerId);
            }
        } else {
            $packages = $this->loadPackagesForUser((string) $user->id);
            $walletBalance = $this->getWalletBalanceForUser((string) $user->id);
        }
        $postpayMinBalanceRaw = $this->configService->getValue(ConfigName::POSTPAY_MIN_BALANCE, 100);
        $postpayMinBalance = is_numeric($postpayMinBalanceRaw) ? (float) $postpayMinBalanceRaw : 100;

        return $this->rendering(
            view: 'service-purchase/index',
            data: [
                'packages' => fn() => ServicePackageListResource::collection($packages),
                'wallet_balance' => $walletBalance,
                'postpay_min_balance' => $postpayMinBalance,
                'meta_timezones' => TimezoneHelper::getMetaTimezoneOptions(),
                'google_timezones' => TimezoneHelper::getGoogleTimezoneOptions(),
                'customers' => $customers,
                'selected_customer_id' => $selectedCustomerId ? (string) $selectedCustomerId : null,
                'is_staff_purchase' => $isStaff,
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

        $isStaff = in_array($user->role, [
            UserRole::ADMIN->value,
            UserRole::MANAGER->value,
            UserRole::EMPLOYEE->value,
        ], true);

        // Xác định service owner (khách hàng)
        $actorUserId = (string) $user->id;
        $serviceOwnerUserId = $actorUserId;

        if ($isStaff) {
            $customerId = $data['customer_id'] ?? null;
            if (!$customerId || !$this->userService->canActOnCustomer($user, $customerId)) {
                FlashMessage::error(__('services.validation.customer_scope_denied'));
                return redirect()->back()->withInput();
            }
            $serviceOwnerUserId = (string) $customerId;
        }

        $configAccount = [];

        if (isset($data['accounts']) && is_array($data['accounts']) && !empty($data['accounts'])) {
            $configAccount['accounts'] = $data['accounts'];
        } else {
            $allowedKeys = [
                'meta_email',
                'display_name',
                'bm_id',
                'info_fanpage',
                'info_website',
                'asset_access',
                'timezone_bm'
            ];

            foreach ($allowedKeys as $key) {
                if(isset($data[$key])){
                    $configAccount[$key] = $data[$key];
                }
            }
        }

        if (isset($data['payment_type'])) {
            $configAccount['payment_type'] = $data['payment_type'];
        }

        $result = $this->servicePurchaseService->createPurchaseOrder(
            actorUserId: $actorUserId,
            serviceOwnerUserId: $serviceOwnerUserId,
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

    private function loadPackagesForUser(int|string $userId)
    {
        $result = $this->servicePackageService->getListServicePackage(new QueryListDTO(
            perPage: 100,
            page: 1,
            filter: [],
            sortBy: 'created_at',
            sortDirection: 'desc',
        ));

        if (! $result->isSuccess()) {
            return collect();
        }

        $paginator = $result->getData();
        $items = method_exists($paginator, 'items') ? $paginator->items() : (array) $paginator;

        return $this->servicePackageService->filterPackagesForUser(
            collect($items)->filter(fn ($pkg) => ! $pkg->disabled),
            $userId
        );
    }

    private function getWalletBalanceForUser(int|string $userId): float
    {
        $walletResult = $this->walletService->getWalletForUser($userId);
        $wallet = $walletResult->isSuccess() ? $walletResult->getData() : null;

        return $wallet ? (float) $wallet['balance'] : 0;
    }
}
