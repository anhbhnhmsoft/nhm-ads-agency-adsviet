<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketListResource extends JsonResource
{
    public function toArray(Request $request): array
    {


        $subject = $this->subject;

        // Danh sách các subject cũ cần được map sang key mới chuẩn (nếu cần)
        $legacyMap = [
            'Account creation request' => 'create_account_request',
            'Share BM/MCC request' => 'share_request',
            'Refund request' => 'refund_request',
            'deposit_app' => 'wallet_deposit_app_request',
            'withdraw_app' => 'wallet_withdraw_app_request',
        ];

        if (isset($legacyMap[$subject])) {
            $subject = $legacyMap[$subject];
        }

        $subjectKey = "ticket.{$subject}";
        $translatedSubject = __($subjectKey);

        // Nếu vẫn không dịch được (vẫn trả về key), thì thử dịch trực tiếp cái subject (trường hợp nó là chuỗi tiếng Anh cũ)
        if ($translatedSubject === $subjectKey) {
            $directTranslation = __("ticket.{$this->subject}");
            if ($directTranslation !== "ticket.{$this->subject}") {
                $translatedSubject = $directTranslation;
            } else {
                $translatedSubject = $this->subject;
            }
        }

        return [
            'id' => $this->id,
            'subject' => $translatedSubject,
            'description' => $this->description,
            'status' => $this->status,
            'priority' => $this->priority,
            'assigned_to' => $this->assigned_to,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}