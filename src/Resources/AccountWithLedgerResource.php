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
            // Subunits (pence), or null if the account has never been synced with Xero
            'outstanding' => $this->xero_outstanding,
            'overdue' => $this->xero_overdue,
            'balances_synced_at' => $this->xero_balances_synced_at,
        ];
    }
}
