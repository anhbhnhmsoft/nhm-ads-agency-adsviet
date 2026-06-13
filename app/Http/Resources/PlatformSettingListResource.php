<?php

namespace App\Http\Resources;

use App\Common\Constants\Platform\PlatformType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\PlatformSetting */
class PlatformSettingListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $tokenStatus = (array) (($this->config ?? [])['token_status'] ?? []);

        if (
            empty($tokenStatus['expires_at'])
            && (int) $this->platform === PlatformType::GOOGLE->value
            && ($tokenStatus['status'] ?? null) === 'valid'
            && !empty($tokenStatus['checked_at'])
        ) {
            $tokenStatus['expires_at'] = Carbon::parse($tokenStatus['checked_at'])
                ->addDays(30)
                ->toIso8601String();
            $tokenStatus['message'] = 'Google token còn hiệu lực.';
        }

        if (!empty($tokenStatus['expires_at'])) {
            $expiresAt = Carbon::parse($tokenStatus['expires_at']);
            $tokenStatus['expires_label'] = $this->humanTimeLeft($expiresAt);
            $tokenStatus['expires_in_seconds'] = max(
                0,
                now()->diffInSeconds($expiresAt, false),
            );
        }

        return [
            'id' => (string) $this->id,
            'name' => (string) ($this->name ?? ''),
            'platform' => (int) $this->platform,
            'config' => (array) $this->config,
            'token_status' => $tokenStatus,
            'disabled' => (bool) $this->disabled,
        ];
    }

    private function humanTimeLeft(Carbon $expiresAt): string
    {
        if ($expiresAt->isPast()) {
            return 'đã hết hạn';
        }

        $seconds = (int) now()->diffInSeconds($expiresAt);
        $days = intdiv($seconds, 86400);
        if ($days > 0) {
            return $days . ' ngày';
        }

        $hours = intdiv($seconds, 3600);
        if ($hours > 0) {
            return $hours . ' giờ';
        }

        $minutes = max(1, intdiv($seconds, 60));
        return $minutes . ' phút';
    }
}
