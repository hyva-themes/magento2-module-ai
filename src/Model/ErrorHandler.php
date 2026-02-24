<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Hyva\Ai\Model;

use Psr\Log\LoggerInterface;

class ErrorHandler
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    public function logError(\Throwable $exception): void
    {
        $this->logger->error('Hyva AI Error: ' . $exception->getMessage(), [
            'exception' => $exception,
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);
    }

    public function logWarning(string $message, array $context = []): void
    {
        $this->logger->warning('Hyva AI Warning: ' . $message, $context);
    }

    public function logInfo(string $message, array $context = []): void
    {
        $this->logger->info('Hyva AI Info: ' . $message, $context);
    }
}
