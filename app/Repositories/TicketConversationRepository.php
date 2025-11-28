<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Models\TicketConversation;
use Illuminate\Database\Eloquent\Builder;

class TicketConversationRepository extends BaseRepository
{
    protected function model(): TicketConversation
    {
        return new TicketConversation();
    }

    public function filterQuery(Builder $query, array $params): Builder
    {
        if (isset($params['ticket_id'])) {
            $query->where('ticket_id', $params['ticket_id']);
        }
        if (isset($params['user_id'])) {
            $query->where('user_id', $params['user_id']);
        }
        return $query;
    }

    public function sortQuery(Builder $query, string $column, string $direction = 'asc'): Builder
    {
        if (!in_array($direction, ['asc', 'desc'])) {
            $direction = 'asc';
        }
        if (empty($column)) {
            $column = 'created_at';
        }
        $query->orderBy($column, $direction);
        return $query;
    }
}

