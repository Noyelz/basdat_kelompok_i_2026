<?php

namespace App\Console\Commands;

use App\Models\TransaksiZakat;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:expire-pending-orders')]
#[Description('Kedaluwarsakan pesanan uji dan lepaskan reservasi berasnya')]
class ExpirePendingOrders extends Command
{
    public function handle(): int
    {
        $total = 0;
        do {
            $expired = TransaksiZakat::expirePending();
            $total += $expired;
        } while ($expired === 100);

        $this->info("{$total} pesanan uji kedaluwarsa diproses.");

        return self::SUCCESS;
    }
}
