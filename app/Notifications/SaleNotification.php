<?php

namespace App\Notifications;

use App\Models\Sale;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;

class SaleNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $sale;

    /**
     * Create a new notification instance.
     */
    public function __construct(Sale $sale)
    {
        $this->sale = $sale;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', WebPushChannel::class];
    }

    /**
     * Get the WebPush representation of the notification.
     */
    public function toWebPush($notifiable, $notification)
    {
        $branch = $this->sale->branch;
        $employee = $this->sale->employee;

        return (new WebPushMessage)
            ->title("New Sale: {$this->sale->invoice_number}")
            ->body("{$employee->name} completed a sale of Rp " . number_format($this->sale->grand_total) . " at {$branch->name}")
            ->action('View Sale', route('sales.show', $this->sale->id))
            ->tag('sale-' . $this->sale->id);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'sale_id' => $this->sale->id,
            'invoice_number' => $this->sale->invoice_number,
            'branch_name' => $this->sale->branch->name,
            'employee_name' => $this->sale->employee->name,
            'grand_total' => $this->sale->grand_total,
            'item_count' => $this->sale->items->count(),
        ];
    }
}
