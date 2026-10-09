<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Hyva\Ai\Model\Provider;

use Hyva\Ai\Api\StreamDialectInterface;

abstract class AbstractStreamDialect implements StreamDialectInterface
{
    public function validateRequest(array $request): ?array
    {
        return null;
    }
}
