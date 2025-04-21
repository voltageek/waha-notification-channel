<?php

namespace Cactus\Notifications\Channels\WAHA;

use GuzzleHttp\Psr7\Response;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Notification;
use Cactus\Notifications\Channels\WAHA\Exceptions\CouldNotSendNotification;


class WhatsappChannel
{
    public function __construct(
        private readonly Dispatcher $dispatcher
    ) {}

    /**
     * Send the given notification.
     *
     *
     * @throws CouldNotSendNotification|\JsonException
     */
    public function send(mixed $notifiable, Notification $notification): ?array
    {
        // @phpstan-ignore-next-line
        $message = $notification->toWhatsapp($notifiable);

        if (is_string($message)) {
            $message = WhatsappMessage::create($message);
        }

        if (! $message->canSend()) {
            return null;
        }

        $to = $message->getPayloadValue('chatId') ?:
              ($notifiable->routeNotificationFor('whatsapp', $notification) ?:
              $notifiable->routeNotificationFor(self::class, $notification));

        if (! $to) {
            return null;
        }

        $message->to($to);

        try {
            $response = $message->send();
        } catch (CouldNotSendNotification $exception) {
            $data = [
                'to' => $message->getPayloadValue('chatId'),
                'request' => $message->toArray(),
                'exception' => $exception,
            ];

            if ($message->exceptionHandler) {
                ($message->exceptionHandler)($data);
            }

            $this->dispatcher->dispatch(new NotificationFailed($notifiable, $notification, 'whatsapp', $data));

            throw $exception;
        }

        return $response instanceof Response
                ? json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)
                : $response;
    }
}
