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

        $message->to(self::formatPhoneNumber($to));

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

    public static function formatPhoneNumber($phoneNumber )
    {
        // Check if number already starts with a plus sign (already E.164 format)
        $hasPlus = (strpos($phoneNumber, '+') === 0);
        if ($hasPlus) {
            // Remove the plus sign temporarily
            $phoneNumber = substr($phoneNumber, 1);
        }
    
        // Remove any non-digit characters
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
    
        // Ghanaian country code
        $countryCode = '233';
    
        // Check if the number already includes the country code
        if (strpos($phoneNumber, $countryCode) === 0) {
            // Number already has country code
            $formattedNumber = $phoneNumber;
    
            // Validate the length of the number with country code
            // Country code (3) + 9 digits = 12 digits total
            $localPart = substr($phoneNumber, strlen($countryCode));
            if (strlen($localPart) != 9) {
                return false; // Invalid phone number length
            }
        } else {
            // Handle numbers starting with 0
            if (strlen($phoneNumber) > 0 && $phoneNumber[0] === '0') {
                $phoneNumber = substr($phoneNumber, 1);
            }
    
            // Validate length after potentially removing leading zero
            // Ghanaian mobile numbers are typically 9 digits after removing the leading zero
            if (strlen($phoneNumber) != 9) {
                return false; // Invalid phone number length
            }
    
            // Add country code
            $formattedNumber = $countryCode . $phoneNumber;
        }
    
        return "{$formattedNumber}@c.us";
    }
}
