<?php
declare(strict_types=1);

namespace Hyva\Ai\Api;

interface ConnectionTesterInterface
{
    public function test(string $model): void;
}
