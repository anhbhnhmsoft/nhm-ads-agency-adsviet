<?php

namespace App\Http\Controllers\API;

use App\Core\Controller;
use App\Core\QueryListDTO;
use App\Core\RestResponse;
use App\Http\Requests\Ticket\AddMessageRequest;
use App\Http\Requests\Ticket\CreateTicketRequest;
use App\Http\Requests\Ticket\UpdateTicketStatusRequest;
use App\Service\TicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function __construct(
        protected TicketService $ticketService,
    ) {
    }

    public function index(Request $request): JsonResponse
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
            return RestResponse::error(message: $result->getMessage(), status: 400);
        }

        $pagination = $result->getData();
        return RestResponse::success(data: [
            'data' => $pagination->items(),
            'current_page' => $pagination->currentPage(),
            'last_page' => $pagination->lastPage(),
            'per_page' => $pagination->perPage(),
            'total' => $pagination->total(),
            'from' => $pagination->firstItem(),
            'to' => $pagination->lastItem(),
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $result = $this->ticketService->getTicketDetail($id);

        if ($result->isError()) {
            return RestResponse::error(message: $result->getMessage(), status: 404);
        }

        return RestResponse::success(data: $result->getData());
    }

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

    // Thêm message vào ticket
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

    // Cập nhật status ticket
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
}

