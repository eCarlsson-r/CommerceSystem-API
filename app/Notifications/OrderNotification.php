<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;

class OrderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $order;

    /**
     * Create a new notification instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
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
        $customer = $this->order->customer;
        $branch = $this->order->branch;

        $type = $this->order->type === 'pickup' ? 'In-Store Pickup' : 'Shipping';
        $branchInfo = $branch ? " at {$branch->name}" : '';

        return (new WebPushMessage)
            ->title("New Order: {$this->order->order_number}")
            ->body("{$customer->name} placed a {$type} order for Rp " . number_format($this->order->total_amount) . $branchInfo)
            ->action('Process Order', route('orders.show', $this->order->id))
            ->tag('order-' . $this->order->id);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'customer_name' => $this->order->customer->name,
            'total_amount' => $this->order->total_amount,
            'type' => $this->order->type,
            'branch_name' => $this->order->branch?->name,
            'item_count' => $this->order->items->count()
        ];
    }
}
