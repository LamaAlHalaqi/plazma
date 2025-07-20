<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Offer;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class DeleteExpiredOffers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:delete-expired-offers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'حذف العروض التي انتهت مدتها تلقائيًا';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now();

        // جلب العروض التي انتهى تاريخ انتهائها
        $expiredOffers = Offer::where('end_datetime', '<', $now)->get();

        $count = $expiredOffers->count();

        foreach ($expiredOffers as $offer) {
            // حذف الصورة إذا موجودة
            if ($offer->image) {
                Storage::delete('public/offers/' . $offer->image);
            }

            $offer->delete();
        }

        $this->info("تم حذف $count عرض منتهٍ.");
    }
}
