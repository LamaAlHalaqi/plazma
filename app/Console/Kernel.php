<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * جدولة أوامر التطبيق.
     */
    protected function schedule(Schedule $schedule): void
    {
        // جدولة حذف العروض المنتهية مرة يومياً عند منتصف الليل
       // $schedule->command('app:delete-expired-offers')->dailyAt('00:00');
        $schedule->command('app:delete-expired-offers')->everyMinute();

    }

    /**
     * تسجيل أوامر التطبيق.
     */
    protected function commands(): void
    {
        // يحمل أوامر موجودة في مجلد Commands تلقائياً
        $this->load(__DIR__ . '/Commands');

        // تحميل أوامر من routes/console.php (إذا كنت تستخدمها)
        require base_path('routes/console.php');
    }
}
