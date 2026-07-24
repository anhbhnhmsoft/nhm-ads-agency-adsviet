<?php

namespace App\Service;

use App\Common\Constants\User\UserRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserPreviewService
{
    public const SESSION_KEY = 'admin_preview_user_id';
    public const RETURN_URL_SESSION_KEY = 'admin_preview_return_url';
    public const ACTOR_ATTRIBUTE = 'preview.actor';
    public const TARGET_ATTRIBUTE = 'preview.target';
    public const APPLIED_ATTRIBUTE = 'preview.applied';

    public function canManagePreview(?User $user): bool
    {
        return (int) $user?->role === UserRole::ADMIN->value;
    }

    public function start(Request $request, User $target): void
    {
        $request->session()->put(self::SESSION_KEY, (string) $target->id);

        $returnUrl = $this->resolveReturnUrl($request);
        if ($returnUrl !== null) {
            $request->session()->put(self::RETURN_URL_SESSION_KEY, $returnUrl);
        } else {
            $request->session()->forget(self::RETURN_URL_SESSION_KEY);
        }
    }

    public function stop(Request $request): void
    {
        $request->session()->forget(self::SESSION_KEY);
        $request->session()->forget(self::RETURN_URL_SESSION_KEY);
    }

    public function getReturnUrl(Request $request): ?string
    {
        $returnUrl = $request->session()->get(self::RETURN_URL_SESSION_KEY);

        return is_string($returnUrl) && $returnUrl !== '' ? $returnUrl : null;
    }

    public function pullReturnUrl(Request $request): ?string
    {
        $returnUrl = $request->session()->pull(self::RETURN_URL_SESSION_KEY);

        return is_string($returnUrl) && $returnUrl !== '' ? $returnUrl : null;
    }

    public function initializeRequest(Request $request): void
    {
        $actor = $request->attributes->get(self::ACTOR_ATTRIBUTE);
        if (!$actor instanceof User) {
            $actor = $request->user();
            if ($actor instanceof User) {
                $request->attributes->set(self::ACTOR_ATTRIBUTE, $actor);
            }
        }

        $target = $this->resolvePreviewTarget($request, $actor);
        if ($target instanceof User) {
            $request->attributes->set(self::TARGET_ATTRIBUTE, $target);
            return;
        }

        $request->attributes->remove(self::TARGET_ATTRIBUTE);
    }

    public function applyPreviewIfNeeded(Request $request): void
    {
        $this->initializeRequest($request);

        if (!$this->shouldApplyPreview($request)) {
            return;
        }

        $target = $this->getPreviewTarget($request);
        if (!$target instanceof User) {
            return;
        }

        Auth::setUser($target);
        $request->setUserResolver(static fn () => $target);
        $request->attributes->set(self::APPLIED_ATTRIBUTE, true);
    }

    public function isPreviewActive(Request $request): bool
    {
        $this->initializeRequest($request);

        return $this->getPreviewTarget($request) instanceof User;
    }

    public function getActor(Request $request): ?User
    {
        $actor = $request->attributes->get(self::ACTOR_ATTRIBUTE);

        return $actor instanceof User ? $actor : $request->user();
    }

    public function getPreviewTarget(Request $request): ?User
    {
        $target = $request->attributes->get(self::TARGET_ATTRIBUTE);

        return $target instanceof User ? $target : null;
    }

    public function isApplied(Request $request): bool
    {
        return (bool) $request->attributes->get(self::APPLIED_ATTRIBUTE, false);
    }

    public function shouldBlockMutation(Request $request): bool
    {
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return false;
        }

        if (!$this->isPreviewActive($request)) {
            return false;
        }

        return !$request->routeIs(
            'logout',
            'admin_preview_start',
            'admin_preview_stop',
        );
    }

    public function shouldApplyPreview(Request $request): bool
    {
        if (!in_array($request->method(), ['GET', 'HEAD'], true)) {
            return false;
        }

        if (!$this->isPreviewActive($request)) {
            return false;
        }

        return $request->routeIs(
            'dashboard',
            'contact_index',
            'dashboard_guide_index',
            'wallet_index',
            'wallet_me_json',
            'wallet_min_amount',
            'transactions_index',
            'service_purchase_index',
            'service_packages_list',
            'service_orders_index',
            'service_management_*',
            'spend_report_*',
            'ticket_*',
            'meta_*',
            'google_ads_*',
        );
    }

    public function findPreviewableUser(string $userId): ?User
    {
        return User::query()
            ->where('id', $userId)
            ->whereIn('role', [
                UserRole::CUSTOMER->value,
                UserRole::AGENCY->value,
                UserRole::MANAGER->value,
                UserRole::EMPLOYEE->value,
            ])
            ->where('disabled', false)
            ->first();
    }

    private function resolvePreviewTarget(Request $request, ?User $actor): ?User
    {
        if (!$this->canManagePreview($actor)) {
            $this->stop($request);

            return null;
        }

        $previewUserId = (string) $request->session()->get(self::SESSION_KEY, '');
        if ($previewUserId === '') {
            return null;
        }

        $target = $this->findPreviewableUser($previewUserId);
        if ($target instanceof User) {
            return $target;
        }

        $this->stop($request);

        return null;
    }

    private function resolveReturnUrl(Request $request): ?string
    {
        $candidate = trim((string) $request->input('return_url', ''));
        if ($candidate === '') {
            $candidate = trim((string) $request->headers->get('referer', ''));
        }

        if ($candidate === '') {
            return null;
        }

        if (str_starts_with($candidate, '/')) {
            return $candidate;
        }

        $appUrl = url('/');
        $candidateParts = parse_url($candidate);
        $appParts = parse_url($appUrl);

        if (!is_array($candidateParts) || !is_array($appParts)) {
            return null;
        }

        $candidateHost = $candidateParts['host'] ?? null;
        $appHost = $appParts['host'] ?? null;
        $candidatePort = $candidateParts['port'] ?? null;
        $appPort = $appParts['port'] ?? null;

        if ($candidateHost !== $appHost || $candidatePort !== $appPort) {
            return null;
        }

        return $candidate;
    }
}
