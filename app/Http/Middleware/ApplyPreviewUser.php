<?php

namespace App\Http\Middleware;

use App\Service\UserPreviewService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplyPreviewUser
{
    public function __construct(
        protected UserPreviewService $userPreviewService,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $this->userPreviewService->applyPreviewIfNeeded($request);

        return $next($request);
    }
}
