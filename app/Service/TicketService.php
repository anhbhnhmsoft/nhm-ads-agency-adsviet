<?php

namespace App\Service;

use App\Common\Constants\Platform\PlatformType;
use App\Common\Constants\ServiceUser\ServiceUserStatus;
use App\Common\Constants\Ticket\TicketPriority;
use App\Common\Constants\Ticket\TicketReplySide;
use App\Common\Constants\Ticket\TicketStatus;
use App\Common\Constants\User\UserRole;
use App\Core\Logging;
use App\Core\QueryListDTO;
use App\Core\ServiceReturn;
use App\Models\Ticket;
use App\Repositories\TicketConversationRepository;
use App\Repositories\TicketRepository;
use App\Repositories\UserReferralRepository;
use App\Repositories\UserRepository;
use App\Repositories\MetaAccountRepository;
use App\Repositories\GoogleAccountRepository;
use App\Repositories\ServiceUserRepository;
use App\Service\TelegramService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class TicketService
{
    public function __construct(
        protected TicketRepository $ticketRepository,
        protected TicketConversationRepository $ticketConversationRepository,
        protected UserReferralRepository $userReferralRepository,
        protected UserRepository $userRepository,
        protected MetaAccountRepository $metaAccountRepository,
        protected GoogleAccountRepository $googleAccountRepository,
        protected ServiceUserRepository $serviceUserRepository,
        protected TelegramService $telegramService,
    ) {
    }

    /**
     * Lấy danh sách user IDs mà user hiện tại có thể hỗ trợ
     * - Manager: user trực tiếp + user của employee thuộc mình
     * - Employee: chỉ user trực tiếp
     */
    protected function getSupportableUserIds(int $userId, int $role): array
    {
        if ($role === UserRole::MANAGER->value) {
            // Manager: lấy user trực tiếp + user của employee
            $directUserIds = $this->userReferralRepository->query()
                ->where('referrer_id', $userId)
                ->whereNull('deleted_at')
                ->pluck('referred_id')
                ->toArray();

            // Lấy employee IDs thuộc manager
            $employeeIds = $this->userReferralRepository->getAssignedEmployeeIds($userId);

            // Lấy user của các employee
            $employeeUserIds = [];
            if (!empty($employeeIds)) {
                $employeeUserIds = $this->userReferralRepository->query()
                    ->whereIn('referrer_id', $employeeIds)
                    ->whereNull('deleted_at')
                    ->pluck('referred_id')
                    ->toArray();
            }

            return array_unique(array_merge($directUserIds, $employeeUserIds));
        } elseif ($role === UserRole::EMPLOYEE->value) {
            // Employee: chỉ lấy user trực tiếp
            return $this->userReferralRepository->query()
                ->where('referrer_id', $userId)
                ->whereNull('deleted_at')
                ->pluck('referred_id')
                ->toArray();
        }

        return [];
    }

    /**
     * Lấy danh sách tickets có phân trang (với logic phân quyền)
     */
    public function getTicketsPaginated(QueryListDTO $queryListDTO): ServiceReturn
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return ServiceReturn::error(message: __('common_error.permission_denied'));
            }

            $query = $this->ticketRepository->query()
                ->with(['user', 'assignedUser', 'conversations' => function ($q) {
                    $q->orderBy('created_at', 'desc')->limit(1);
                }]);

            if (in_array($user->role, [UserRole::MANAGER->value, UserRole::EMPLOYEE->value])) {
                // Manager/Employee: chỉ thấy tickets từ user mà họ có thể hỗ trợ
                $supportableUserIds = $this->getSupportableUserIds((int) $user->id, $user->role);
                if (empty($supportableUserIds)) {
                    // Không có user nào để hỗ trợ -> trả về rỗng
                    return ServiceReturn::success(
                        data: new LengthAwarePaginator(
                            items: [],
                            total: 0,
                            perPage: $queryListDTO->perPage,
                            currentPage: $queryListDTO->page
                        )
                    );
                }
                $query->whereIn('user_id', $supportableUserIds);
            } elseif ($user->role === UserRole::CUSTOMER->value || $user->role === UserRole::AGENCY->value) {
                // Customer/Agency: chỉ thấy tickets của chính mình
                $query->where('user_id', $user->id);
            } elseif ($user->role === UserRole::ADMIN->value) {
                // Admin: thấy tất cả ko xử lý lọc
            } else {
                return ServiceReturn::error(message: __('common_error.permission_denied'));
            }

            // Apply filters
            if ($queryListDTO->filter) {
                $query = $this->ticketRepository->filterQuery($query, $queryListDTO->filter);
            }

            // Apply sorting
            $query = $this->ticketRepository->sortQuery(
                $query,
                $queryListDTO->sortBy ?? 'created_at',
                $queryListDTO->sortDirection ?? 'desc'
            );

            $paginator = $query->paginate(
                $queryListDTO->perPage,
                ['*'],
                'page',
                $queryListDTO->page
            );

            return ServiceReturn::success(data: $paginator);
        } catch (\Throwable $exception) {
            Logging::error(
                message: 'TicketService@getTicketsPaginated error: ' . $exception->getMessage(),
                exception: $exception
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    /**
     * Tạo ticket mới (chỉ Customer/Agency)
     */
    public function createTicket(array $data): ServiceReturn
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return ServiceReturn::error(message: __('common_error.permission_denied'));
            }

            // Chỉ Customer/Agency mới được tạo ticket
            if (!in_array($user->role, [UserRole::CUSTOMER->value, UserRole::AGENCY->value])) {
                return ServiceReturn::error(message: __('common_error.permission_denied'));
            }

            $ticket = $this->ticketRepository->create([
                'user_id' => $user->id,
                'subject' => $data['subject'],
                'description' => $data['description'],
                'status' => TicketStatus::PENDING->value,
                'priority' => $data['priority'] ?? TicketPriority::MEDIUM->value,
                'assigned_to' => null,
                'metadata' => $data['metadata'] ?? null,
            ]);

            // Tạo conversation đầu tiên từ customer
            $this->ticketConversationRepository->create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'message' => $data['description'],
                'reply_side' => TicketReplySide::CUSTOMER->value,
            ]);

            // Gửi thông báo Telegram
            $this->sendTicketCreatedNotification($ticket);

            return ServiceReturn::success(data: $ticket->load(['user', 'conversations']));
        } catch (\Throwable $exception) {
            Logging::error(
                message: 'TicketService@createTicket error: ' . $exception->getMessage(),
                exception: $exception
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    /**
     * Tạo yêu cầu chuyển tiền (chỉ Customer/Agency)
     */
    public function createTransferRequest(array $data): ServiceReturn
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return ServiceReturn::error(message: __('common_error.permission_denied'));
            }

            // Chỉ Customer/Agency mới được tạo yêu cầu chuyển tiền
            if (!in_array($user->role, [UserRole::CUSTOMER->value, UserRole::AGENCY->value])) {
                return ServiceReturn::error(message: __('common_error.permission_denied'));
            }

            // Tạo description từ notes (user mô tả vấn đề)
            $description = $data['notes'] ?? '';

            // Lưu metadata đầy đủ
            $metadata = [
                'type' => 'transfer',
                'platform' => $data['platform'] ?? null,
                'from_account_id' => $data['from_account_id'],
                'from_account_name' => $data['from_account_name'] ?? null,
                'to_account_id' => $data['to_account_id'],
                'to_account_name' => $data['to_account_name'] ?? null,
                'amount' => $data['amount'],
                'currency' => $data['currency'] ?? 'USD',
                'notes' => $data['notes'] ?? null,
            ];

            $ticket = $this->ticketRepository->create([
                'user_id' => $user->id,
                'subject' => 'transfer_request',
                'description' => $description,
                'status' => TicketStatus::PENDING->value,
                'priority' => TicketPriority::HIGH->value, // Chuyển tiền là priority cao
                'assigned_to' => null,
                'metadata' => $metadata,
            ]);

            // Tạo conversation đầu tiên với message là notes
            $this->ticketConversationRepository->create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'message' => $description,
                'reply_side' => TicketReplySide::CUSTOMER->value,
            ]);

            // Gửi thông báo Telegram
            $this->sendTicketCreatedNotification($ticket);

            return ServiceReturn::success(data: $ticket->load(['user', 'conversations']));
        } catch (\Throwable $exception) {
            Logging::error(
                message: 'TicketService@createTransferRequest error: ' . $exception->getMessage(),
                exception: $exception
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    /**
     * Tạo yêu cầu thanh lý tài khoản
     */
    public function createRefundRequest(array $data): ServiceReturn
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return ServiceReturn::error(message: __('common_error.permission_denied'));
            }

            // Chỉ Customer/Agency mới được tạo yêu cầu thanh lý
            if (!in_array($user->role, [UserRole::CUSTOMER->value, UserRole::AGENCY->value])) {
                return ServiceReturn::error(message: __('common_error.permission_denied'));
            }

            $description = $data['notes'] ?? '';

            // Lưu metadata
            $metadata = [
                'type' => 'refund',
                'platform' => $data['platform'],
                'account_ids' => $data['account_ids'],
                'account_names' => $data['account_names'] ?? [],
                'liquidation_type' => $data['liquidation_type'],
                'notes' => $data['notes'] ?? null,
            ];

            $ticket = $this->ticketRepository->create([
                'user_id' => $user->id,
                'subject' => 'refund_request',
                'description' => $description,
                'status' => TicketStatus::PENDING->value,
                'priority' => TicketPriority::HIGH->value,
                'assigned_to' => null,
                'metadata' => $metadata,
            ]);

            // Tạo conversation
            $this->ticketConversationRepository->create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'message' => $description,
                'reply_side' => TicketReplySide::CUSTOMER->value,
            ]);

            // Gửi thông báo Telegram
            $this->sendTicketCreatedNotification($ticket);

            return ServiceReturn::success(data: $ticket->load(['user', 'conversations']));
        } catch (\Throwable $exception) {
            Logging::error(
                message: 'TicketService@createRefundRequest error: ' . $exception->getMessage(),
                exception: $exception
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    /**
     * Tạo yêu cầu kháng tài khoản
     */
    public function createAppealRequest(array $data): ServiceReturn
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return ServiceReturn::error(message: __('common_error.permission_denied'));
            }

            // Chỉ Customer/Agency mới được tạo yêu cầu kháng
            if (!in_array($user->role, [UserRole::CUSTOMER->value, UserRole::AGENCY->value])) {
                return ServiceReturn::error(message: __('common_error.permission_denied'));
            }

            // Tìm account để lấy name
            $account = $this->findAccountById($data['account_id']);
            $accountName = $account['name'] ?? null;

            // Tạo description từ notes (user mô tả vấn đề)
            $description = $data['notes'] ?? '';

            // Lưu metadata đầy đủ
            $metadata = [
                'type' => 'appeal',
                'platform' => $data['platform'],
                'account_id' => $data['account_id'],
                'account_name' => $accountName,
                'notes' => $data['notes'] ?? null,
            ];

            $ticket = $this->ticketRepository->create([
                'user_id' => $user->id,
                'subject' => 'appeal_request',
                'description' => $description,
                'status' => TicketStatus::PENDING->value,
                'priority' => TicketPriority::HIGH->value,
                'assigned_to' => null,
                'metadata' => $metadata,
            ]);

            // Tạo conversation
            $this->ticketConversationRepository->create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'message' => $description,
                'reply_side' => TicketReplySide::CUSTOMER->value,
            ]);

            // Gửi thông báo Telegram
            $this->sendTicketCreatedNotification($ticket);

            return ServiceReturn::success(data: $ticket->load(['user', 'conversations']));
        } catch (\Throwable $exception) {
            Logging::error(
                message: 'TicketService@createAppealRequest error: ' . $exception->getMessage(),
                exception: $exception
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    /**
     * Tạo yêu cầu share BM/BC/MCC
     */
    public function createShareRequest(array $data): ServiceReturn
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return ServiceReturn::error(message: __('common_error.permission_denied'));
            }

            // Chỉ Customer/Agency mới được tạo yêu cầu share
            if (!in_array($user->role, [UserRole::CUSTOMER->value, UserRole::AGENCY->value])) {
                return ServiceReturn::error(message: __('common_error.permission_denied'));
            }

            $account = $this->findAccountById($data['account_id']);
            $accountName = $account['name'] ?? null;

            $description = $data['notes'] ?? '';

            // Lưu metadata
            $metadata = [
                'type' => 'share',
                'platform' => $data['platform'],
                'account_id' => $data['account_id'],
                'account_name' => $accountName,
                'bm_bc_mcc_id' => $data['bm_bc_mcc_id'],
                'notes' => $data['notes'] ?? null,
            ];

            $ticket = $this->ticketRepository->create([
                'user_id' => $user->id,
                'subject' => 'share_request',
                'description' => $description,
                'status' => TicketStatus::PENDING->value,
                'priority' => TicketPriority::HIGH->value,
                'assigned_to' => null,
                'metadata' => $metadata,
            ]);

            // Tạo conversation
            $this->ticketConversationRepository->create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'message' => $description,
                'reply_side' => TicketReplySide::CUSTOMER->value,
            ]);

            // Gửi thông báo Telegram
            $this->sendTicketCreatedNotification($ticket);

            return ServiceReturn::success(data: $ticket->load(['user', 'conversations']));
        } catch (\Throwable $exception) {
            Logging::error(
                message: 'TicketService@createShareRequest error: ' . $exception->getMessage(),
                exception: $exception
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    /**
     * Tìm account theo account_id (từ Meta hoặc Google)
     * Public method để Controller có thể gọi
     */
    public function findAccountById(string $accountId): array
    {
        // Tìm trong Meta accounts qua Repository
        $metaAccount = $this->metaAccountRepository->query()
            ->where('account_id', $accountId)
            ->select('account_id', 'account_name')
            ->first();
        
        if ($metaAccount) {
            return [
                'id' => $metaAccount->account_id,
                'name' => $metaAccount->account_name,
            ];
        }
        
        // Tìm trong Google accounts qua Repository
        $googleAccount = $this->googleAccountRepository->query()
            ->where('account_id', $accountId)
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

    /**
     * Lấy chi tiết ticket
     */
    public function getTicketDetail(string $ticketId): ServiceReturn
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return ServiceReturn::error(message: __('common_error.permission_denied'));
            }

            $ticket = $this->ticketRepository->find($ticketId);
            if (!$ticket) {
                return ServiceReturn::error(message: __('ticket.not_found'));
            }

            // Kiểm tra quyền truy cập
            if (!$this->canAccessTicket($user, $ticket)) {
                return ServiceReturn::error(message: __('common_error.permission_denied'));
            }

            $ticket->load(['user', 'assignedUser', 'conversations.user']);

            return ServiceReturn::success(data: $ticket);
        } catch (\Throwable $exception) {
            Logging::error(
                message: 'TicketService@getTicketDetail error: ' . $exception->getMessage(),
                exception: $exception
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    /**
     * Kiểm tra user có quyền truy cập ticket không
     */
    protected function canAccessTicket($user, Ticket $ticket): bool
    {
        // Admin: truy cập tất cả
        if ($user->role === UserRole::ADMIN->value) {
            return true;
        }

        // Customer/Agency: chỉ ticket của chính mình
        if (in_array($user->role, [UserRole::CUSTOMER->value, UserRole::AGENCY->value])) {
            return $ticket->user_id == $user->id;
        }

        // Manager/Employee: ticket từ user mà họ có thể hỗ trợ
        if (in_array($user->role, [UserRole::MANAGER->value, UserRole::EMPLOYEE->value])) {
            $supportableUserIds = $this->getSupportableUserIds((int) $user->id, $user->role);
            return in_array($ticket->user_id, $supportableUserIds);
        }

        return false;
    }

    /**
     * Thêm message vào ticket
     */
    public function addMessage(string $ticketId, string $message): ServiceReturn
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return ServiceReturn::error(message: __('common_error.permission_denied'));
            }

            $ticket = $this->ticketRepository->find($ticketId);
            if (!$ticket) {
                return ServiceReturn::error(message: __('ticket.not_found'));
            }

            // Kiểm tra quyền truy cập
            if (!$this->canAccessTicket($user, $ticket)) {
                return ServiceReturn::error(message: __('common_error.permission_denied'));
            }

            // Xác định reply_side
            $replySide = in_array($user->role, [UserRole::CUSTOMER->value, UserRole::AGENCY->value])
                ? TicketReplySide::CUSTOMER->value
                : TicketReplySide::AGENT->value;

            // Cập nhật status nếu cần
            if ($ticket->status === TicketStatus::PENDING->value) {
                $ticket->status = TicketStatus::OPEN->value;
                $ticket->save();
            }

            // Nếu staff reply và chưa assign, tự động assign
            if ($replySide === TicketReplySide::AGENT->value && !$ticket->assigned_to) {
                $ticket->assigned_to = $user->id;
                $ticket->status = TicketStatus::IN_PROGRESS->value;
                $ticket->save();
            }

            $conversation = $this->ticketConversationRepository->create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'message' => $message,
                'reply_side' => $replySide,
            ]);

            return ServiceReturn::success(data: $conversation->load('user'));
        } catch (\Throwable $exception) {
            Logging::error(
                message: 'TicketService@addMessage error: ' . $exception->getMessage(),
                exception: $exception
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    /**
     * Cập nhật status ticket (chỉ Manager/Employee/Admin)
     */
    public function updateTicketStatus(string $ticketId, int $status): ServiceReturn
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return ServiceReturn::error(message: __('common_error.permission_denied'));
            }

            // Chỉ Manager/Employee/Admin mới được update status
            if (!in_array($user->role, [UserRole::ADMIN->value, UserRole::MANAGER->value, UserRole::EMPLOYEE->value])) {
                return ServiceReturn::error(message: __('common_error.permission_denied'));
            }

            $ticket = $this->ticketRepository->find($ticketId);
            if (!$ticket) {
                return ServiceReturn::error(message: __('ticket.not_found'));
            }

            // Kiểm tra quyền truy cập
            if (!$this->canAccessTicket($user, $ticket)) {
                return ServiceReturn::error(message: __('common_error.permission_denied'));
            }

            $ticket->status = $status;
            $ticket->save();

            return ServiceReturn::success(data: $ticket->load(['user', 'assignedUser']));
        } catch (\Throwable $exception) {
            Logging::error(
                message: 'TicketService@updateTicketStatus error: ' . $exception->getMessage(),
                exception: $exception
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }

    /**
     * Gửi thông báo Telegram khi ticket được tạo
     */
    protected function sendTicketCreatedNotification(Ticket $ticket): void
    {
        try {
            $user = $ticket->user;
            if (!$user) {
                return;
            }

            // Lấy danh sách chat IDs cần gửi thông báo
            $chatIds = $this->getNotificationChatIds($user->id);

            if (empty($chatIds)) {
                return;
            }

            // Tạo message
            $priorityLabel = match ($ticket->priority) {
                TicketPriority::LOW->value => __('ticket.priority.low'),
                TicketPriority::MEDIUM->value => __('ticket.priority.medium'),
                TicketPriority::HIGH->value => __('ticket.priority.high'),
                TicketPriority::URGENT->value => __('ticket.priority.urgent'),
                default => __('ticket.priority.medium'),
            };

            $ticketUrl = url(route('ticket_show', ['id' => $ticket->id], false));
            
            $message = sprintf(
                "🔔 <b>Yêu cầu hỗ trợ mới</b>\n\n" .
                "👤 <b>Khách hàng:</b> %s\n" .
                "📋 <b>Chủ đề:</b> %s\n" .
                "📝 <b>Mô tả:</b> %s\n" .
                "⚡ <b>Mức độ:</b> %s\n" .
                "🔗 <b>Link:</b> <a href=\"%s\">Xem chi tiết</a>",
                htmlspecialchars($user->name ?? $user->username, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($ticket->subject, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars(mb_substr($ticket->description, 0, 200) . (mb_strlen($ticket->description) > 200 ? '...' : ''), ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($priorityLabel, ENT_QUOTES, 'UTF-8'),
                $ticketUrl
            );

            // Gửi thông báo
            $this->telegramService->sendTicketNotification($chatIds, $message);
        } catch (\Throwable $exception) {
            // Log lỗi nhưng không throw để không ảnh hưởng đến việc tạo ticket
            Logging::error(
                message: 'TicketService@sendTicketCreatedNotification error: ' . $exception->getMessage(),
                exception: $exception
            );
        }
    }

    /**
     * Lấy danh sách chat IDs cần gửi thông báo
     * - Manager/Employee quản lý user (dựa vào referral) - gửi đến group hoặc telegram_id
     * - Tất cả Admin - gửi đến group hoặc telegram_id
     */
    protected function getNotificationChatIds(int $userId): array
    {
        $chatIds = [];

        // 1. Lấy Manager/Employee quản lý user này
        $referrers = $this->userReferralRepository->getReferrerChain($userId);
        $managerEmployeeIds = [];
        
        foreach ($referrers as $referrer) {
            // Chỉ lấy Manager và Employee
            if (in_array($referrer->role, [UserRole::MANAGER->value, UserRole::EMPLOYEE->value])) {
                $managerEmployeeIds[] = $referrer->id;
                // Nếu có telegram_id cá nhân, thêm vào
                if (!empty($referrer->telegram_id)) {
                    $chatIds[] = $referrer->telegram_id;
                }
            }
        }

        // 2. Lấy tất cả Admin
        $admins = $this->userRepository->query()
            ->where('role', UserRole::ADMIN->value)
            ->where('disabled', false)
            ->get();

        foreach ($admins as $admin) {
            if (!empty($admin->telegram_id)) {
                $chatIds[] = $admin->telegram_id;
            }
        }

        // 3. Nếu có config Telegram support group, thêm vào
        $supportGroupId = config('services.telegram.support_group_id');
        if (!empty($supportGroupId)) {
            $chatIds[] = $supportGroupId;
        }

        // Loại bỏ duplicate và null
        return array_filter(array_unique($chatIds));
    }

    /**
     * Lấy danh sách accounts của user (Meta và Google)
     */
    public function getUserAccounts(int $userId): array
    {
        $accounts = [];
        
        // Lấy service_users của user
        $serviceUsers = $this->serviceUserRepository->query()
            ->where('user_id', $userId)
            ->where('status', ServiceUserStatus::ACTIVE->value)
            ->with(['package:id,platform'])
            ->get();
        
        foreach ($serviceUsers as $serviceUser) {
            $platform = $serviceUser->package->platform ?? null;
            
            // Lấy Meta accounts
            if ($platform === PlatformType::META->value) {
                $metaAccounts = $this->metaAccountRepository->query()
                    ->where('service_user_id', $serviceUser->id)
                    ->select('id', 'account_id', 'account_name')
                    ->get();
                
                foreach ($metaAccounts as $account) {
                    $accounts[] = [
                        'id' => (string) $account->id,
                        'account_id' => $account->account_id,
                        'account_name' => $account->account_name,
                        'platform' => PlatformType::META->value,
                    ];
                }
            }
            
            // Lấy Google accounts
            if ($platform === PlatformType::GOOGLE->value) {
                $googleAccounts = $this->googleAccountRepository->query()
                    ->where('service_user_id', $serviceUser->id)
                    ->select('id', 'account_id', 'account_name')
                    ->get();
                
                foreach ($googleAccounts as $account) {
                    $accounts[] = [
                        'id' => (string) $account->id,
                        'account_id' => $account->account_id,
                        'account_name' => $account->account_name,
                        'platform' => PlatformType::GOOGLE->value,
                    ];
                }
            }
        }
        
        return $accounts;
    }

    /**
     * Lấy email của admin đầu tiên (đã verify email)
     */
    public function getAdminEmail(): ?string
    {
        $admin = $this->userRepository->query()
            ->where('role', UserRole::ADMIN->value)
            ->where('disabled', false)
            ->whereNotNull('email')
            ->whereNotNull('email_verified_at')
            ->first();

        return $admin?->email;
    }

    /**
     * Tạo yêu cầu tạo tài khoản mới (không tạo đơn dịch vụ/transaction)
     */
    public function createAccountRequest(array $data): ServiceReturn
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return ServiceReturn::error(message: __('common_error.permission_denied'));
            }

            // Chỉ Customer/Agency mới được tạo yêu cầu
            if (!in_array($user->role, [UserRole::CUSTOMER->value, UserRole::AGENCY->value])) {
                return ServiceReturn::error(message: __('common_error.permission_denied'));
            }

            // Tạo description từ notes (user mô tả vấn đề)
            $description = $data['notes'] ?? '';

            // Chuẩn bị config account từ accounts array hoặc single fields
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
                    'timezone_bm',
                    'payment_type',
                    'top_up_amount',
                ];

                foreach ($allowedKeys as $key) {
                    if (isset($data[$key])) {
                        $configAccount[$key] = $data[$key];
                    }
                }
            }

            // Lưu metadata đầy đủ
            $metadata = [
                'type' => 'create_account',
                'package_id' => $data['package_id'],
                'payment_type' => $data['payment_type'] ?? 'prepay',
                'top_up_amount' => isset($data['top_up_amount']) ? (float) $data['top_up_amount'] : 0,
                'budget' => isset($data['budget']) ? (float) $data['budget'] : 0,
                'config_account' => $configAccount,
                'notes' => $data['notes'] ?? null,
            ];

            $ticket = $this->ticketRepository->create([
                'user_id' => $user->id,
                'subject' => 'create_account_request',
                'description' => $description,
                'status' => TicketStatus::PENDING->value,
                'priority' => TicketPriority::HIGH->value,
                'assigned_to' => null,
                'metadata' => $metadata,
            ]);

            // Tạo conversation
            $this->ticketConversationRepository->create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'message' => $description,
                'reply_side' => TicketReplySide::CUSTOMER->value,
            ]);

            // Gửi thông báo Telegram
            $this->sendTicketCreatedNotification($ticket);

            return ServiceReturn::success(data: $ticket->load(['user', 'conversations']));
        } catch (\Throwable $exception) {
            Logging::error(
                message: 'TicketService@createAccountRequest error: ' . $exception->getMessage(),
                exception: $exception
            );
            return ServiceReturn::error(message: __('common_error.server_error'));
        }
    }
}

