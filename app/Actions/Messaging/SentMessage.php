<?php

namespace App\Actions\Messaging;

use App\Models\Message;

/**
 * Result of a send attempt: the stored message and whether this attempt created it.
 */
final readonly class SentMessage
{
    public function __construct(public Message $message, public bool $created) {}
}
