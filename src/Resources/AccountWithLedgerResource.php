<?php

namespace AdminUI\AdminUIXero\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountWithLedgerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);
        return [
            ...$data,
            'outstanding' => $this->outstanding,
            'overdue' => $this->overdue
        ];
    }
}
