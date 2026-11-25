<?php

namespace App\Console\Commands;

use App\Models\Stylist;
use App\Modules\Appointment\Support\OpenStylistSlots;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('salon:open-slots {--salon= : Optional salon slug}')]
#[Description('Open stylist blocks up to the booking window, preserving existing blocks and appointments')]
class OpenSlotsCommand extends Command
{
    public function handle(OpenStylistSlots $open): int
    {
        $slug = $this->option('salon');
        $count = 0;

        Stylist::query()->where('is_active', true)
            ->whereHas('salon', fn ($query) => $query->when($slug, fn ($query) => $query->where('slug', $slug)))
            ->with('salon')
            ->chunkById(50, function ($stylists) use ($open, &$count) {
                foreach ($stylists as $stylist) {
                    $count += $open->handle($stylist);
                }
            });

        $this->info('Opened '.$count.' new stylist blocks. Existing blocks and appointments were preserved.');

        return self::SUCCESS;
    }
}
