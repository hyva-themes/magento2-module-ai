<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Hyva\Ai\Model;

use Hyva\Ai\Api\ConnectionTesterInterface;
use Magento\Framework\Exception\LocalizedException;

class ConnectionTesterPool
{
    /** @param ConnectionTesterInterface[] $testers */
    public function __construct(private readonly array $testers = [])
    {
    }

    public function get(string $provider): ConnectionTesterInterface
    {
        if (!isset($this->testers[$provider])) {
            throw new LocalizedException(__('This provider does not offer a connection test.'));
        }
        return $this->testers[$provider];
    }
}
