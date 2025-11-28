<?php

namespace App\Http\Controllers;

use App\Core\Controller;
use App\Core\QueryListDTO;
use App\Http\Requests\Ticket\AddMessageRequest;
use App\Http\Requests\Ticket\CreateTicketRequest;
use App\Http\Requests\Ticket\UpdateTicketStatusRequest;
use App\Service\TicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TicketController extends Controller
{
    public function __construct(
        protected TicketService $ticketService,
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
}

