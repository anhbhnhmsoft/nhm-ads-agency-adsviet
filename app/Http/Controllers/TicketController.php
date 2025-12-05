<?php

namespace App\Http\Controllers;

use App\Core\Controller;
use App\Core\QueryListDTO;
use App\Http\Requests\Ticket\AddMessageRequest;
use App\Http\Requests\Ticket\CreateTicketRequest;
use App\Http\Requests\Ticket\CreateTransferRequest;
use App\Http\Requests\Ticket\UpdateTicketStatusRequest;
use App\Service\TicketService;
use App\Service\MetaService;
use App\Service\GoogleAdsService;
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
        $result = $this->ticketService->createTicket($request->validated());

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

        // Lấy danh sách accounts của user để hiển thị trong select
        $user = Auth::user();
        $accounts = [];
        
        if ($user) {
            // Lấy service_users của user
            $serviceUsers = \App\Models\ServiceUser::where('user_id', $user->id)
                ->where('status', \App\Common\Constants\ServiceUser\ServiceUserStatus::ACTIVE->value)
                ->with(['package:id,platform'])
                ->get();
            
            foreach ($serviceUsers as $serviceUser) {
                $platform = $serviceUser->package->platform ?? null;
                
                // Lấy Meta accounts - tất cả accounts thuộc service_user
                if ($platform === \App\Common\Constants\Platform\PlatformType::META->value) {
                    $metaAccounts = \App\Models\MetaAccount::where('service_user_id', $serviceUser->id)
                        ->select('id', 'account_id', 'account_name')
                        ->get();
                    
                    foreach ($metaAccounts as $account) {
                        $accounts[] = [
                            'id' => (string) $account->id,
                            'account_id' => $account->account_id,
                            'account_name' => $account->account_name,
                            'platform' => 1, // META
                        ];
                    }
                }
                
                // Lấy Google accounts - tất cả accounts thuộc service_user
                if ($platform === \App\Common\Constants\Platform\PlatformType::GOOGLE->value) {
                    $googleAccounts = \App\Models\GoogleAccount::where('service_user_id', $serviceUser->id)
                        ->select('id', 'account_id', 'account_name')
                        ->get();
                    
                    foreach ($googleAccounts as $account) {
                        $accounts[] = [
                            'id' => (string) $account->id,
                            'account_id' => $account->account_id,
                            'account_name' => $account->account_name,
                            'platform' => 2, // GOOGLE
                        ];
                    }
                }
            }
        }

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
        $fromAccount = $this->findAccountById($validated['from_account_id']);
        $toAccount = $this->findAccountById($validated['to_account_id']);
        
        $result = $this->ticketService->createTransferRequest([
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
     * Tìm account theo account_id (từ Meta hoặc Google)
     */
    protected function findAccountById(string $accountId): array
    {
        // Tìm trong Meta accounts
        $metaAccount = \App\Models\MetaAccount::where('account_id', $accountId)
            ->select('account_id', 'account_name')
            ->first();
        
        if ($metaAccount) {
            return [
                'id' => $metaAccount->account_id,
                'name' => $metaAccount->account_name,
            ];
        }
        
        // Tìm trong Google accounts
        $googleAccount = \App\Models\GoogleAccount::where('account_id', $accountId)
            ->select('account_id', 'account_name')
            ->first();
        
        if ($googleAccount) {
            return [
                'id' => $googleAccount->account_id,
                'name' => $googleAccount->account_name,
            ];
        }
        
        return ['id' => $accountId, 'name' => null];
    }
}

