<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketListResource extends JsonResource
{
    public function toArray(Request $request): array
    {


        $subjectKey = "ticket.{$this->subject}";
        $translatedSubject = __($subjectKey);

        if ($translatedSubject === $subjectKey) {
            $translatedSubject = $this->subject;
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