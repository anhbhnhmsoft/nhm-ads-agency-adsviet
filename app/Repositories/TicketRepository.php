<?php

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Builder;

class TicketRepository extends BaseRepository
{
    protected function model(): Ticket
    {
        return new Ticket();
    }

    public function filterQuery(Builder $query, array $params): Builder
    {
        if (isset($params['user_id'])) {
            $query->where('user_id', $params['user_id']);
        }
        if (isset($params['assigned_to'])) {
            $query->where('assigned_to', $params['assigned_to']);
        }
        if (isset($params['status'])) {
            $query->where('status', $params['status']);
        }
        if (isset($params['priority'])) {
            $query->where('priority', $params['priority']);
        }
        return $query;
    }

    public function sortQuery(Builder $query, string $column, string $direction = 'desc'): Builder
    {
        if (!in_array($direction, ['asc', 'desc'])) {
            $direction = 'desc';
        }
        if (empty($column)) {
            $column = 'created_at';
        }
        $query->orderBy($column, $direction);
        return $query;
    }

}

