<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 * Supports Saga pattern and Event Sourcing
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Infrastructure\Storage;

/**
 * Unescape binary data
 */
interface BinaryDataDecoder
{
    /**
     * Unescape binary string
     *
     * @param string|resource $payload
     *
     * @return string
     */
    public function unescapeBinary($payload): string;
}
