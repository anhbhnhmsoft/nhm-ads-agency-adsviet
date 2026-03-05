<?php

namespace App\Http\Controllers\API;

use App\Common\Constants\Ticket\TicketMetadataType;
use App\Common\Helpers\TimezoneHelper;
use App\Core\Controller;
use App\Core\QueryListDTO;
use App\Core\RestResponse;
use App\Http\Requests\Ticket\AddMessageRequest;
use App\Http\Requests\Ticket\CreateAccountRequest;
use App\Http\Requests\Ticket\CreateAppealRequest;
use App\Http\Requests\Ticket\CreateRefundRequest;
use App\Http\Requests\Ticket\CreateShareRequest;
use App\Http\Requests\Ticket\CreateTicketRequest;
use App\Http\Requests\Ticket\CreateTransferRequest;
use App\Http\Requests\Ticket\UpdateTicketStatusRequest;
use App\Http\Resources\ServicePackageResource;
use App\Http\Resources\TicketListResource;
use App\Service\ServicePackageService;
use App\Service\TicketService;
use App\Service\WalletTransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TicketController extends Controller
{
    public function __construct(
        protected TicketService $ticketService,
        protected WalletTransactionService $walletTransactionService,
        protected ServicePackageService $servicePackageService,
    ) {
    }

    /**
     * Danh sách tickets của user hiện tại
     */
    public function index(Request $request): JsonResponse
    {
        $params = $this->extractQueryPagination($request);
        $result = $this->ticketService->getTicketsPaginated(
            new QueryListDTO(
                perPage: $params->get('per_page', 15),
                page: $params->get('page', 1),
                filter: $params->get('filter'),
                sortBy: $params->get('sort_by', 'created_at'),
                sortDirection: $params->get('direction', 'desc'),
            )
        );

        if ($result->isError()) {
            return RestResponse::error(message: $result->getMessage(), status: 400);
        }

        $pagination = $result->getData();
        return RestResponse::success(data: TicketListResource::collection($pagination)->response()->getData());
    }

    /**
     * Chi tiết ticket
     */
    public function show(string $id): JsonResponse
    {
        $result = $this->ticketService->getTicketDetail($id);

        if ($result->isError()) {
            return RestResponse::error(message: $result->getMessage(), status: 404);
        }

        return RestResponse::success(data: $result->getData());
    }

    /**
     * Tạo ticket hỗ trợ chung
     */
    public function store(CreateTicketRequest $request): JsonResponse
    {
        $result = $this->ticketService->createTicket($request->validated());

        if ($result->isError()) {
            return RestResponse::error(message: $result->getMessage(), status: 400);
        }

        return RestResponse::success(
            data: $result->getData(),
            message: __('ticket.create_success')
        );
    }

    /**
     * Gửi tin nhắn mới vào ticket
     */
    public function addMessage(string $id, AddMessageRequest $request): JsonResponse
    {
        $result = $this->ticketService->addMessage($id, $request->validated()['message']);

        if ($result->isError()) {
            return RestResponse::error(message: $result->getMessage(), status: 400);
        }

        return RestResponse::success(
            data: $result->getData(),
            message: __('ticket.message_sent')
        );
    }

    /**
     * Cập nhật trạng thái ticket (chỉ staff/admin)
     */
    public function updateStatus(string $id, UpdateTicketStatusRequest $request): JsonResponse
    {
        $result = $this->ticketService->updateTicketStatus($id, $request->validated()['status']);

        if ($result->isError()) {
            return RestResponse::error(message: $result->getMessage(), status: 400);
        }

        return RestResponse::success(
            data: $result->getData(),
            message: __('ticket.status_updated')
        );
    }

    /**
     * API Chuyển tiền (Transfer)
     */
    public function transfer(Request $request): JsonResponse
    {
        $params = $this->extractQueryPagination($request);
        $result = $this->ticketService->getTicketsPaginated(
            new QueryListDTO(
                perPage: $params->get('per_page', 15),
                page: $params->get('page', 1),
                filter: array_merge($params->get('filter', []), ['type' => 'transfer']),
                sortBy: $params->get('sort_by', 'created_at'),
                sortDirection: $params->get('direction', 'desc'),
            )
        );

        if ($result->isError()) {
            return RestResponse::error(message: $result->getMessage(), status: 400);
        }

        $pagination = $result->getData();
        return RestResponse::success(data: TicketListResource::collection($pagination)->response()->getData());
    }

    public function storeTransfer(CreateTransferRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $fromAccount = $this->ticketService->findAccountById($validated['from_account_id']);
        $toAccount = $this->ticketService->findAccountById($validated['to_account_id']);

        $result = $this->ticketService->createTransferRequest([
            'platform' => $validated['platform'],
            'from_account_id' => $validated['from_account_id'],
            'from_account_name' => $fromAccount['name'] ?? null,
            'to_account_id' => $validated['to_account_id'],
            'to_account_name' => $toAccount['name'] ?? null,
            'amount' => $validated['amount'],
            'currency' => 'USD',
            'notes' => $validated['notes'] ?? null,
        ]);

        if ($result->isError()) {
            return RestResponse::error(message: $result->getMessage());
        }

        return RestResponse::success(data: $result->getData(), message: __('ticket.transfer.create_success'));
    }

    /**
     * API Thanh lý tài khoản (Refund)
     */
    public function storeRefund(CreateRefundRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $accountNames = [];
        foreach ($validated['account_ids'] as $accountId) {
            $account = $this->ticketService->findAccountById($accountId);
            if ($account['name']) {
                $accountNames[] = $account['name'];
            }
        }

        $result = $this->ticketService->createRefundRequest([
            'platform' => $validated['platform'],
            'account_ids' => $validated['account_ids'],
            'account_names' => $accountNames,
            'liquidation_type' => $validated['liquidation_type'],
            'notes' => $validated['notes'] ?? null,
        ]);

        if ($result->isError()) {
            return RestResponse::error(message: $result->getMessage());
        }

        return RestResponse::success(data: $result->getData(), message: __('ticket.refund.create_success'));
    }

    /**
     * API Kháng tài khoản (Appeal)
     */
    public function storeAppeal(CreateAppealRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $result = $this->ticketService->createAppealRequest([
            'platform' => $validated['platform'],
            'account_id' => $validated['account_id'],
            'notes' => $validated['notes'] ?? null,
        ]);

        if ($result->isError()) {
            return RestResponse::error(message: $result->getMessage());
        }

        return RestResponse::success(data: $result->getData(), message: __('ticket.appeal.create_success'));
    }

    /**
     * API Chia sẻ BM/MCC (Share)
     */
    public function storeShare(CreateShareRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $result = $this->ticketService->createShareRequest([
            'platform' => $validated['platform'],
            'account_id' => $validated['account_id'],
            'bm_bc_mcc_id' => $validated['bm_bc_mcc_id'],
            'notes' => $validated['notes'],
        ]);

        if ($result->isError()) {
            return RestResponse::error(message: $result->getMessage());
        }

        return RestResponse::success(data: $result->getData(), message: __('ticket.share.create_success'));
    }

    /**
     * API Tạo tài khoản (Create Account)
     */
    public function storeCreateAccount(CreateAccountRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();

        if (!$user) {
            return RestResponse::error(message: __('common_error.permission_denied'), status: 401);
        }

        $paymentType = strtolower((string) ($data['payment_type'] ?? 'prepay'));
        $data['payment_type'] = $paymentType;

        if ($paymentType === 'postpay') {
            $postpayUserIdsResult = $this->servicePackageService->getPostpayUserIds($data['package_id']);
            if ($postpayUserIdsResult->isError()) {
                return RestResponse::error(message: $postpayUserIdsResult->getMessage());
            }

            $allowedUserIds = array_map('strval', (array) $postpayUserIdsResult->getData());
            if (empty($allowedUserIds) || !in_array((string) $user->id, $allowedUserIds, true)) {
                return RestResponse::error(message: __('services.validation.postpay_not_allowed'), status: 422);
            }

            $data['top_up_amount'] = 0;
        }

        $result = $this->ticketService->createAccountRequest($data);

        if ($result->isError()) {
            return RestResponse::error(message: $result->getMessage());
        }

        return RestResponse::success(data: $result->getData(), message: __('ticket.create_account.create_success'));
    }

    /**
     * API Rút ví App (Withdraw App)
     */
    public function storeWithdrawApp(CreateTicketRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = Auth::user();

        if (($data['metadata']['type'] ?? null) !== TicketMetadataType::WALLET_WITHDRAW_APP->value) {
            return RestResponse::error(message: __('ticket.validation.invalid_type_withdraw'));
        }

        $metadata = $data['metadata'] ?? [];
        $withdrawInfo = $metadata['withdraw_info'] ?? [];
        $withdrawType = $metadata['withdraw_type'] ?? 'bank';

        $withdrawResult = $this->walletTransactionService->createWithdrawOrder(
            userId: (int) $user->id,
            amount: (float) ($metadata['amount'] ?? 0),
            withdrawInfo: array_merge($withdrawInfo, ['withdraw_type' => $withdrawType]),
            walletPassword: $data['wallet_password'] ?? null,
        );

        if ($withdrawResult->isError()) {
            return RestResponse::error(message: $withdrawResult->getMessage());
        }

        $transaction = $withdrawResult->getData();
        $metadata['withdraw_transaction_id'] = $transaction?->id ?? null;
        $data['metadata'] = $metadata;
        unset($data['wallet_password']);

        $result = $this->ticketService->createTicket($data);

        if ($result->isError()) {
            return RestResponse::error(message: $result->getMessage());
        }

        return RestResponse::success(data: $result->getData(), message: __('ticket.create_success'));
    }

    /**
     * API Nạp tiền (Deposit App)
     */
    public function storeDepositApp(CreateTicketRequest $request): JsonResponse
    {
        $data = $request->validated();
        if (($data['metadata']['type'] ?? null) !== TicketMetadataType::WALLET_DEPOSIT_APP->value) {
            return RestResponse::error(message: __('ticket.validation.invalid_type_deposit'));
        }

        $metadata = $data['metadata'] ?? [];
        if (isset($metadata['account_id']) && !isset($metadata['account_name'])) {
            $account = $this->ticketService->findAccountById($metadata['account_id']);
            $metadata['account_name'] = $account['name'] ?? null;
        }
        $data['metadata'] = $metadata;

        $result = $this->ticketService->createTicket($data);

        if ($result->isError()) {
            return RestResponse::error(message: $result->getMessage());
        }

        return RestResponse::success(data: $result->getData(), message: __('ticket.create_success'));
    }

    /**
     * Lấy dữ liệu init cho các form (Danh sách tài khoản, Packages, Timezones)
     */
    public function getFormData(): JsonResponse
    {
        $user = Auth::user();
        $accounts = $this->ticketService->getUserAccounts((int) $user->id);

        $packageResult = $this->servicePackageService->getListServicePackage(new QueryListDTO(
            perPage: 100,
            page: 1,
            filter: [],
            sortBy: 'created_at',
            sortDirection: 'desc'
        ));
        $packages = $packageResult->isSuccess() ? $packageResult->getData()->items() : [];

        return RestResponse::success(data: [
            'accounts' => $accounts,
            'packages' => ServicePackageResource::collection($packages),
            'meta_timezones' => TimezoneHelper::getMetaTimezoneOptions(),
            'google_timezones' => TimezoneHelper::getGoogleTimezoneOptions(),
            'admin_email' => $this->ticketService->getAdminEmail(),
        ]);
    }
}