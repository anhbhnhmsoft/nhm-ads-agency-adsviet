<?php

namespace App\Core;

final readonly class QueryListDTO
{
    public function __construct(
        public int $perPage,
        public int $page,
        public array $filter,
        public string $sortBy,
        public string $sortDirection,
    ){
    }
}
