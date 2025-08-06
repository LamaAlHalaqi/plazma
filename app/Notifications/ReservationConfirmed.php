<?php

namespace App\Notifications;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Reservation;
use Illuminate\Support\Facades\Log;
class ReservationConfirmed extends Notification implements ShouldQueue
{
    use Queueable;

    protected $reservation;

    /**
     * Create a new notification instance.
     */
    public function __construct(Reservation $reservation)
    {
        $this->reservation = $reservation;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable)
    {
        return ['database', 'mail']; // يمكنك إضافة قنوات أخرى مثل 'mail' أو 'broadcast'
    }

    /**
     * Get the database representation of the notification.
     */

// ...
    public function toDatabase($notifiable)
    {
        return [
            'reservation_id' => $this->reservation->id,
            'service_name' => $this->reservation->service->name ?? 'غير محدد',
            'start_time' => Carbon::parse($this->reservation->start_time)->format('Y-m-d H:i:s'),
            'message' => 'تم تأكيد حجزك للخدمة: ' . ($this->reservation->service->name ?? 'غير محدد'),
            'deep_link' => url('/reservations/' . $this->reservation->id)
        ];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('تأكيد الحجز')
            ->line('تم تأكيد حجزك بنجاح!')
            ->line('الخدمة: ' . ($this->reservation->service->name ?? 'غير محدد'))
            ->line('وقت الحجز: ' . Carbon::parse($this->reservation->start_time)->toDateTimeString())
            ->action('عرض الحجز', url('/reservations/' . $this->reservation->id))
            ->line('شكرًا لاستخدامك خدماتنا!');
    }

// في دالة toMail
    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
