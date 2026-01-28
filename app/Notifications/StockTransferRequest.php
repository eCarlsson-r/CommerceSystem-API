<?php
// app/Notifications/StockTransferRequest.php
namespace App\Notifications;

use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;

class StockTransferRequest extends Notification
{
    public function __construct(public $transfer) {}

    public function via($notifiable)
    {
        return [WebPushChannel::class];
    }

    public function toWebPush($notifiable, $notification)
    {
        return (new WebPushMessage)
            ->title('Incoming Stock Transfer!')
            ->icon('/assets/icons/delivery-truck.png')
            ->body("Branch {$this->transfer->from_branch_code} has sent items. Please confirm arrival.")
            ->action('View Request', 'view_transfer')
            ->data(['id' => $this->transfer->id])
            ->badge('/assets/icons/badge.png')
            ->tag('stock-transfer-' . $this->transfer->id); // Prevents duplicate notifications
    }
}