<?php

namespace Cactus\Notifications\Channels\WAHA;

use JsonSerializable;
use Cactus\Notifications\Channels\WAHA\Traits\HasSharedLogic;

/**
 * Class WhatsappBase.
 */
class WhatsappBase implements JsonSerializable
{
    use HasSharedLogic;

    public Whatsapp $whatsapp;

    public function __construct()
    {
        $this->whatsapp = app(Whatsapp::class);
    }
}
