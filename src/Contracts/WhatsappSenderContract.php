<?php

namespace Cactus\Notifications\Channels\WAHA\Contracts;

use Cactus\Notifications\Channels\WAHA\Exceptions\CouldNotSendNotification;
use Psr\Http\Message\ResponseInterface;

interface WhatsappSenderContract
{
    /**
     * Send the message.
     *
     *
     * @throws CouldNotSendNotification
     */
    public function send(): ResponseInterface|array|null;
}
