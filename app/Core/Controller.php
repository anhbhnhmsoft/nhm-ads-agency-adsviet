<?php

namespace App\Core;

use Illuminate\Http\Request;

abstract class Controller
{
    protected function extractQueryPagination(Request $request): \Illuminate\Support\Collection
    {
        $perPage = $request->integer('per_page', 10);
        $page = $request->integer('page', 1);
        $filter = $request->array('filter', []);
        $sortBy = $request->string('sort_by', '');
        $direction = $request->string('direction', 'desc');
        return collect([
            'per_page' => $perPage,
            'page' => $page,
            'filter' => $filter,
            'sort_by' => $sortBy,
            'direction' => $direction,
        ]);
    }
}
