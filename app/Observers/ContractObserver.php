<?php

namespace App\Observers;

use App\Models\Contract;
use Carbon\Carbon;

class ContractObserver
{
    public function retrieved(Contract $contract): void
    {
        if ($this->shouldDeactivate($contract)) {
            $contract->is_active = false;
            $contract->saveQuietly();
        }
    }

    private function shouldDeactivate(Contract $contract): bool
    {
        if (! $contract->is_active) {
            return false;
        }

        if ($contract->contract_type !== 'Temporal') {
            return false;
        }

        if (! $contract->end_date) {
            return false;
        }

        return Carbon::today()->gt($contract->end_date);
    }
}
