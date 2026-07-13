<?php

namespace App\Http\Middleware;

use App\Service\UserPreviewService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventPreviewMutations
{
    public function __construct(
        protected UserPreviewService $userPreviewService,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (!$this->userPreviewService->shouldBlockMutation($request)) {
            return $next($request);
        }

        $message = __('user.preview.read_only_warning');

        if ($request->expectsJson() || $request->ajax()) {
            return new JsonResponse([
                'message' => $message,
            ], 423);
        }

        /** @var RedirectResponse $response */
        $response = redirect()->back();
        $response->with('warning', $message);

        return $response;
    }
}
