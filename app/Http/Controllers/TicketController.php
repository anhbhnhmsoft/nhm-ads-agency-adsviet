<?php

namespace App\Http\Controllers;

use App\Common\Constants\Ticket\TicketMetadataType;
use App\Core\Controller;
use App\Core\QueryListDTO;
use App\Http\Requests\Ticket\AddMessageRequest;
use App\Http\Requests\Ticket\CreateTicketRequest;
use App\Http\Requests\Ticket\CreateTransferRequest;
use App\Http\Requests\Ticket\CreateRefundRequest;
use App\Http\Requests\Ticket\CreateAppealRequest;
use App\Http\Requests\Ticket\CreateShareRequest;
use App\Http\Requests\Ticket\UpdateTicketStatusRequest;
use App\Service\TicketService;
use App\Service\MetaService;
use App\Service\GoogleAdsService;
use App\Service\WalletTransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Response;

class TicketController extends Controller
{
    public function __construct(
        protected TicketService $ticketService,
        protected MetaService $metaService,
        protected GoogleAdsService $googleAdsService,
        protected WalletTransactionService $walletTransactionService,
    ) {
    }

    /**
     * Danh sách tickets
     */
    public function index(Request $request): Response
    {
        $params = $this->extractQueryPagination($request);
        $result = $this->ticketService->getTicketsPaginated(
            new QueryListDTO(
                perPage: $params->get('per_page', 15),
                page: $params->get('page', 1),
                filter: $params->get('filter'),
                sortBy: $params->get('sort_by'),
                sortDirection: $params->get('direction'),
            )
        );

        if ($result->isError()) {
            return $this->rendering('ticket/index', [
                'error' => $result->getMessage(),
                'tickets' => null,
            ]);
        }

        $pagination = $result->getData();

        return $this->rendering('ticket/index', [
            'tickets' => $pagination,
            'error' => null,
        ]);
    }

    /**
     * Chi tiết ticket
     */
    public function show(string $id): Response|RedirectResponse
    {
        $result = $this->ticketService->getTicketDetail($id);

        if ($result->isError()) {
            return redirect()->route('ticket_index')->with('error', $result->getMessage());
        }

        return $this->rendering('ticket/show', [
            'ticket' => $result->getData(),
        ]);
    }

    /**
     * Tạo ticket mới
     */
    public function store(CreateTicketRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = Auth::user();
        if (!$user) {
            return back()->withErrors(['error' => __('common_error.permission_denied')]);
        }

        // Nếu type = wallet_withdraw_app thì tạo lệnh rút tiền giống trang ví
        if (($data['metadata']['type'] ?? null) === TicketMetadataType::WALLET_WITHDRAW_APP->value) {
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
                return back()->withErrors(['error' => $withdrawResult->getMessage()]);
            }

            $transaction = $withdrawResult->getData();
            $metadata['withdraw_transaction_id'] = $transaction?->id ?? null;
            $data['metadata'] = $metadata;
        }

        // Nếu type = wallet_deposit_app thì tạo lệnh nạp tiền chờ duyệt
        if (($data['metadata']['type'] ?? null) === TicketMetadataType::WALLET_DEPOSIT_APP->value) {
            $metadata = $data['metadata'] ?? [];
            $depositResult = $this->walletTransactionService->createDepositOrder(
                userId: (int) $user->id,
                amount: (float) ($metadata['amount'] ?? 0),
                network: (string) ($metadata['network'] ?? 'USDT'),
                depositAddress: 'SUPPORT_TICKET'
            );

            if ($depositResult->isError()) {
                return back()->withErrors(['error' => $depositResult->getMessage()]);
            }

            $transaction = $depositResult->getData();
            $metadata['deposit_transaction_id'] = $transaction?->id ?? null;
            $data['metadata'] = $metadata;
        }

        unset($data['wallet_password']);

        $result = $this->ticketService->createTicket($data);

        if ($result->isError()) {
            return back()->withErrors(['error' => $result->getMessage()]);
        }

        return redirect()->route('ticket_show', ['id' => $result->getData()->id])
            ->with('success', __('ticket.create_success'));
    }

    /**
     * Thêm message vào ticket
     */
    public function addMessage(string $id, AddMessageRequest $request): RedirectResponse
    {
        $result = $this->ticketService->addMessage($id, $request->validated()['message']);

        if ($result->isError()) {
            return back()->withErrors(['error' => $result->getMessage()]);
        }

        return back()->with('success', __('ticket.message_sent'));
    }

    /**
     * Cập nhật status ticket
     */
    public function updateStatus(string $id, UpdateTicketStatusRequest $request): RedirectResponse
    {
        $result = $this->ticketService->updateTicketStatus($id, $request->validated()['status']);

        if ($result->isError()) {
            return back()->withErrors(['error' => $result->getMessage()]);
        }

        return back()->with('success', __('ticket.status_updated'));
    }

    /**
     * Trang chuyển tiền - hiển thị form và danh sách yêu cầu
     */
    public function transfer(Request $request): Response
    {
        $params = $this->extractQueryPagination($request);
        
        // Lấy danh sách tickets loại transfer
        $result = $this->ticketService->getTicketsPaginated(
            new QueryListDTO(
                perPage: $params->get('per_page', 15),
                page: $params->get('page', 1),
                filter: array_merge($params->get('filter', []), ['type' => 'transfer']),
                sortBy: $params->get('sort_by'),
                sortDirection: $params->get('direction'),
            )
        );

        $ticketsPaginator = $result->isError() ? null : $result->getData();

        // Convert paginator to array format for frontend
        $tickets = null;
        if ($ticketsPaginator) {
            $laravelArray = $ticketsPaginator->toArray();
            $tickets = [
                'data' => $laravelArray['data'] ?? [],
                'links' => [
                    'first' => $laravelArray['first_page_url'] ?? null,
                    'last' => $laravelArray['last_page_url'] ?? null,
                    'prev' => $laravelArray['prev_page_url'] ?? null,
                    'next' => $laravelArray['next_page_url'] ?? null,
                ],
                'meta' => [
                    'links' => array_map(function ($link) {
                        return [
                            'url' => $link['url'] ?? null,
                            'label' => $link['label'] ?? '',
                            'active' => $link['active'] ?? false,
                            'page' => $link['page'] ?? null,
                        ];
                    }, $laravelArray['links'] ?? []),
                    'current_page' => $laravelArray['current_page'] ?? 1,
                    'from' => $laravelArray['from'] ?? null,
                    'last_page' => $laravelArray['last_page'] ?? 1,
                    'per_page' => $laravelArray['per_page'] ?? 15,
                    'to' => $laravelArray['to'] ?? null,
                    'total' => $laravelArray['total'] ?? 0,
                ],
            ];
        }

        // Lấy danh sách accounts của user
        $user = Auth::user();
        $accounts = $user ? $this->ticketService->getUserAccounts((int) $user->id) : [];

        return $this->rendering('ticket/transfer', [
            'tickets' => $tickets,
            'accounts' => $accounts,
            'error' => $result->isError() ? $result->getMessage() : null,
        ]);
    }

    /**
     * Tạo yêu cầu chuyển tiền
     */
    public function storeTransfer(CreateTransferRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        
        // Lấy thông tin account names
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
            return back()->withErrors(['error' => $result->getMessage()]);
        }

        return redirect()->route('ticket_transfer')
            ->with('success', __('ticket.transfer.create_success'));
    }


    /**
     * Trang thanh lý tài khoản - hiển thị form và danh sách yêu cầu
     */
    public function refund(Request $request): Response
    {
        $params = $this->extractQueryPagination($request);
        
        // Lấy danh sách tickets loại refund
        $result = $this->ticketService->getTicketsPaginated(
            new QueryListDTO(
                perPage: $params->get('per_page', 15),
                page: $params->get('page', 1),
                filter: array_merge($params->get('filter', []), ['type' => 'refund']),
                sortBy: $params->get('sort_by'),
                sortDirection: $params->get('direction'),
            )
        );

        $ticketsPaginator = $result->isError() ? null : $result->getData();

        // Convert paginator to array format for frontend
        $tickets = null;
        if ($ticketsPaginator) {
            $laravelArray = $ticketsPaginator->toArray();
            $tickets = [
                'data' => $laravelArray['data'] ?? [],
                'links' => [
                    'first' => $laravelArray['first_page_url'] ?? null,
                    'last' => $laravelArray['last_page_url'] ?? null,
                    'prev' => $laravelArray['prev_page_url'] ?? null,
                    'next' => $laravelArray['next_page_url'] ?? null,
                ],
                'meta' => [
                    'links' => array_map(function ($link) {
                        return [
                            'url' => $link['url'] ?? null,
                            'label' => $link['label'] ?? '',
                            'active' => $link['active'] ?? false,
                            'page' => $link['page'] ?? null,
                        ];
                    }, $laravelArray['links'] ?? []),
                    'current_page' => $laravelArray['current_page'] ?? 1,
                    'from' => $laravelArray['from'] ?? null,
                    'last_page' => $laravelArray['last_page'] ?? 1,
                    'per_page' => $laravelArray['per_page'] ?? 15,
                    'to' => $laravelArray['to'] ?? null,
                    'total' => $laravelArray['total'] ?? 0,
                ],
            ];
        }

        // Lấy danh sách accounts của user
        $user = Auth::user();
        $accounts = $user ? $this->ticketService->getUserAccounts((int) $user->id) : [];

        return $this->rendering('ticket/refund', [
            'tickets' => $tickets,
            'accounts' => $accounts,
            'error' => $result->isError() ? $result->getMessage() : null,
        ]);
    }

    /**
     * Tạo yêu cầu thanh lý tài khoản
     */
    public function storeRefund(CreateRefundRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        
        // Lấy thông tin account
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
            return back()->withErrors(['error' => $result->getMessage()]);
        }

        return redirect()->route('ticket_refund')
            ->with('success', __('ticket.refund.create_success'));
    }

    /**
     * Trang kháng tài khoản - hiển thị form và danh sách yêu cầu
     */
    public function appeal(Request $request): Response
    {
        $params = $this->extractQueryPagination($request);
        
        $result = $this->ticketService->getTicketsPaginated(
            new QueryListDTO(
                perPage: $params->get('per_page', 15),
                page: $params->get('page', 1),
                filter: array_merge($params->get('filter', []), ['type' => 'appeal']),
                sortBy: $params->get('sort_by'),
                sortDirection: $params->get('direction'),
            )
        );

        $ticketsPaginator = $result->isError() ? null : $result->getData();

        $tickets = null;
        if ($ticketsPaginator) {
            $laravelArray = $ticketsPaginator->toArray();
            $tickets = [
                'data' => $laravelArray['data'] ?? [],
                'links' => [
                    'first' => $laravelArray['first_page_url'] ?? null,
                    'last' => $laravelArray['last_page_url'] ?? null,
                    'prev' => $laravelArray['prev_page_url'] ?? null,
                    'next' => $laravelArray['next_page_url'] ?? null,
                ],
                'meta' => [
                    'links' => array_map(function ($link) {
                        return [
                            'url' => $link['url'] ?? null,
                            'label' => $link['label'] ?? '',
                            'active' => $link['active'] ?? false,
                            'page' => $link['page'] ?? null,
                        ];
                    }, $laravelArray['links'] ?? []),
                    'current_page' => $laravelArray['current_page'] ?? 1,
                    'from' => $laravelArray['from'] ?? null,
                    'last_page' => $laravelArray['last_page'] ?? 1,
                    'per_page' => $laravelArray['per_page'] ?? 15,
                    'to' => $laravelArray['to'] ?? null,
                    'total' => $laravelArray['total'] ?? 0,
                ],
            ];
        }

        // Lấy danh sách accounts của user để hiển thị trong select qua Service
        $user = Auth::user();
        $accounts = $user ? $this->ticketService->getUserAccounts((int) $user->id) : [];

        // Lấy email admin đầu tiên qua Service
        $adminEmail = $this->ticketService->getAdminEmail();

        return $this->rendering('ticket/appeal', [
            'tickets' => $tickets,
            'accounts' => $accounts,
            'adminEmail' => $adminEmail,
            'error' => $result->isError() ? $result->getMessage() : null,
        ]);
    }

    /**
     * Tạo yêu cầu kháng tài khoản
     */
    public function storeAppeal(CreateAppealRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        
        $result = $this->ticketService->createAppealRequest([
            'platform' => $validated['platform'],
            'account_id' => $validated['account_id'],
            'notes' => $validated['notes'] ?? null,
        ]);

        if ($result->isError()) {
            return back()->withErrors(['error' => $result->getMessage()]);
        }

        return redirect()->route('ticket_appeal')
            ->with('success', __('ticket.appeal.create_success'));
    }

    /**
     * Trang share BM/BC/MCC - hiển thị form và danh sách yêu cầu
     */
    public function share(Request $request): Response
    {
        $params = $this->extractQueryPagination($request);
        
        $result = $this->ticketService->getTicketsPaginated(
            new QueryListDTO(
                perPage: $params->get('per_page', 15),
                page: $params->get('page', 1),
                filter: array_merge($params->get('filter', []), ['type' => 'share']),
                sortBy: $params->get('sort_by'),
                sortDirection: $params->get('direction'),
            )
        );

        $ticketsPaginator = $result->isError() ? null : $result->getData();

        $tickets = null;
        if ($ticketsPaginator) {
            $laravelArray = $ticketsPaginator->toArray();
            $tickets = [
                'data' => $laravelArray['data'] ?? [],
                'links' => [
                    'first' => $laravelArray['first_page_url'] ?? null,
                    'last' => $laravelArray['last_page_url'] ?? null,
                    'prev' => $laravelArray['prev_page_url'] ?? null,
                    'next' => $laravelArray['next_page_url'] ?? null,
                ],
                'meta' => [
                    'links' => array_map(function ($link) {
                        return [
                            'url' => $link['url'] ?? null,
                            'label' => $link['label'] ?? '',
                            'active' => $link['active'] ?? false,
                            'page' => $link['page'] ?? null,
                        ];
                    }, $laravelArray['links'] ?? []),
                    'current_page' => $laravelArray['current_page'] ?? 1,
                    'from' => $laravelArray['from'] ?? null,
                    'last_page' => $laravelArray['last_page'] ?? 1,
                    'per_page' => $laravelArray['per_page'] ?? 15,
                    'to' => $laravelArray['to'] ?? null,
                    'total' => $laravelArray['total'] ?? 0,
                ],
            ];
        }

        // Lấy danh sách accounts của user để hiển thị trong select qua Service
        $user = Auth::user();
        $accounts = $user ? $this->ticketService->getUserAccounts((int) $user->id) : [];

        return $this->rendering('ticket/share', [
            'tickets' => $tickets,
            'accounts' => $accounts,
            'error' => $result->isError() ? $result->getMessage() : null,
        ]);
    }

    /**
     * Trang rút ví app về ví khách - hiển thị form tạo ticket + lệnh rút
     */
    public function withdrawApp(): Response|RedirectResponse
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        return $this->rendering('ticket/withdraw-app', [
            'auth' => $user,
        ]);
    }

    /**
     * Trang nạp tiền vào ví khách (tạo ticket + lệnh nạp chờ duyệt)
     */
    public function depositApp(): Response|RedirectResponse
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        return $this->rendering('ticket/deposit-app', [
            'auth' => $user,
        ]);
    }

    /**
     * Tạo yêu cầu share BM/BC/MCC
     */
    public function storeShare(CreateShareRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        
        $result = $this->ticketService->createShareRequest([
            'platform' => $validated['platform'],
            'account_id' => $validated['account_id'],
            'bm_bc_mcc_id' => $validated['bm_bc_mcc_id'],
            'notes' => $validated['notes'],
        ]);

        if ($result->isError()) {
            return back()->withErrors(['error' => $result->getMessage()]);
        }

        return redirect()->route('ticket_share')
            ->with('success', __('ticket.share.create_success'));
    }
}

