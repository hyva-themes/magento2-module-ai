<?php
declare(strict_types=1);

namespace Hyva\Ai\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

class ExecutionConfig
{
    public const PATH = 'hyva_ai/execution/mode';
    private string $feature = '';

    public function __construct(private readonly ScopeConfigInterface $scopeConfig)
    {
    }

    public function getMode(?string $feature = null): string
    {
        $feature ??= $this->feature;
        if ($feature !== '' && preg_match('/^[a-z0-9_]+$/', $feature)) {
            $override = (string) $this->scopeConfig->getValue('hyva_ai/' . $feature . '/execution_mode');
            if ($override !== '' && $override !== 'default') {
                return $override;
            }
        }
        return (string) ($this->scopeConfig->getValue(self::PATH) ?: 'legacy');
    }

    public function withFeature(string $feature, callable $operation): mixed
    {
        $previous = $this->feature;
        $this->feature = $feature;
        try {
            return $operation();
        } finally {
            $this->feature = $previous;
        }
    }
}
