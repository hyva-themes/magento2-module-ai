<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Hyva\Ai\Model;

use Hyva\Ai\Exception\ConcurrencyLimitExceededException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Lock\LockManagerInterface;
use Magento\Store\Model\ScopeInterface;

class ConcurrencyGuard
{
    public const XML_PATH_MAX_CONCURRENT_REQUESTS = 'hyva_ai/runtime/max_concurrent_requests';
    public const XML_PATH_REQUEST_TIMEOUT_SECONDS = 'hyva_ai/runtime/request_timeout_seconds';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly LockManagerInterface $lockManager
    ) {
    }

    /**
     * Attempt to acquire a concurrency slot.
     *
     * @return string Acquired lock name
     * @throws ConcurrencyLimitExceededException When no slots are available
     */
    public function acquire(): string
    {
        $max = (int) ($this->scopeConfig->getValue(
            self::XML_PATH_MAX_CONCURRENT_REQUESTS,
            ScopeInterface::SCOPE_STORE
        ) ?? 1);

        if ($max < 1) {
            $max = 1;
        }

        for ($i = 1; $i <= $max; $i++) {
            $lockName = 'hyva_ai_concurrent_slot_' . $i;
            if ($this->lockManager->lock($lockName, 0)) {
                return $lockName;
            }
        }

        throw new ConcurrencyLimitExceededException(
            __('Maximum concurrent AI requests reached. Please try again shortly.')
        );
    }

    /**
     * Release a previously acquired concurrency slot.
     */
    public function release(string $lockName): void
    {
        if ($lockName === '') {
            return;
        }

        $this->lockManager->unlock($lockName);
    }
}
