<?php

namespace App\Service;

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
use App\Service\TelegramService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TicketService
{
    public function __construct(
        protected TicketRepository $ticketRepository,
        protected TicketConversationRepository $ticketConversationRepository,
        protected UserReferralRepository $userReferralRepository,
        protected UserRepository $userRepository,
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
     * Lấy chi tiết ticket
     */
    public function getTicketDetail(string $ticketId): ServiceReturn
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return ServiceReturn::error(message: __('common_error.permission_denied'));
            }

            $ticket = $this->ticketRepository->find((int) $ticketId);
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

            $ticket = $this->ticketRepository->find((int) $ticketId);
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

            $ticket = $this->ticketRepository->find((int) $ticketId);
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
}

