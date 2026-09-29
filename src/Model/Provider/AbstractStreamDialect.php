<?php
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
