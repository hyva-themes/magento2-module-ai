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
     * Slot lock name prefix. Slot names are hyva_ai_concurrent_slot_1, hyva_ai_concurrent_slot_2, ...
     */
    private const LOCK_NAME_PREFIX = 'hyva_ai_concurrent_slot_';

    /**
     * Get the list of concurrency slot lock names (based on max_concurrent_requests config).
     *
     * @return list<string>
     */
    public function getSlotNames(): array
    {
        $max = (int) ($this->scopeConfig->getValue(
            self::XML_PATH_MAX_CONCURRENT_REQUESTS,
            ScopeInterface::SCOPE_STORE
        ) ?? 0);

        if ($max <= 0) {
            return [];
        }

        $names = [];
        for ($i = 1; $i <= $max; $i++) {
            $names[] = self::LOCK_NAME_PREFIX . $i;
        }

        return $names;
    }

    /**
     * Attempt to acquire a concurrency slot.
     *
     * @return string Acquired lock name
     * @throws ConcurrencyLimitExceededException When no slots are available
     */
    public function acquire(): string
    {
        $slots = $this->getSlotNames();

        if (empty($slots)) {
            return '';
        }

        foreach ($slots as $lockName) {
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

    /**
     * Get the number of concurrency slots that are currently locked.
     *
     * @return int Number of slots currently held (by this or other processes)
     */
    public function getActiveLockCount(): int
    {
        $count = 0;
        foreach ($this->getSlotNames() as $lockName) {
            if ($this->lockManager->isLocked($lockName)) {
                $count++;
            }
        }

        return $count;
    }
}
