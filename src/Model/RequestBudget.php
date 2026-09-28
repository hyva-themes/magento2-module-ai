<?php
declare(strict_types=1);

namespace Hyva\Ai\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;

class RequestBudget
{
    public function __construct(private readonly ScopeConfigInterface $scopeConfig)
    {
    }

    public function remainingSeconds(): int
    {
        $configured = (int) $this->scopeConfig->getValue(ConcurrencyGuard::XML_PATH_REQUEST_TIMEOUT_SECONDS);
        $limit = $configured > 0 ? $configured : 25;
        if (PHP_SAPI === 'cli') {
            return $limit;
        }

        $phpLimit = (int) ini_get('max_execution_time');
        if ($phpLimit > 0) {
            $limit = min($limit, max(1, $phpLimit - 5));
        }
        $remaining = (int) floor($limit - (microtime(true) - ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true))));
        if ($remaining < 1) {
            throw new LocalizedException(__('The AI request time budget is exhausted. Use an AI Worker job for longer tasks.'));
        }
        return $remaining;
    }

    public function releaseSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
    }
}
