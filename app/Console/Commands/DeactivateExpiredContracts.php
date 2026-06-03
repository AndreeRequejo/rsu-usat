<?php

namespace App\Console\Commands;

use App\Models\Contract;
use Illuminate\Console\Command;

class DeactivateExpiredContracts extends Command
{
    protected $signature = 'contracts:deactivate-expired';

    protected $description = 'Deactivate temporary contracts that have passed their end date';

    public function handle(): int
    {
        $count = Contract::query()
            ->where('is_active', true)
            ->where('contract_type', 'Temporal')
            ->whereNotNull('end_date')
            ->whereDate('end_date', '<', now()->toDateString())
            ->update(['is_active' => false]);

        $this->info("Deactivated {$count} expired temporary contract(s).");

        return Command::SUCCESS;
    }
}
