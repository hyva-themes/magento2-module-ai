<?php
declare(strict_types=1);

namespace Hyva\Ai\Plugin;

use Hyva\Ai\Api\ProviderInterface;
use Hyva\Ai\Api\RequestTransportInterface;
use Hyva\Ai\Model\ExecutionConfig;
use Magento\Framework\Exception\LocalizedException;

class RouteProviderRequests
{
    /** @param RequestTransportInterface[] $transports */
    public function __construct(
        private readonly ExecutionConfig $config,
        private readonly array $transports = [],
        private readonly array $providerConfigs = []
    ) {
    }

    public function aroundChatWithTools(
        \Hyva\Ai\Api\ToolCapableProviderInterface $subject,
        callable $proceed,
        array $messages,
        array $tools,
        array $options = []
    ): array {
        $mode = $this->config->getMode($options['_execution_feature'] ?? null);
        if ($mode === 'legacy' || $mode === 'php' || !$subject instanceof ProviderInterface || $subject->getName() === 'worker') {
            return $proceed($messages, $tools, $options);
        }
        $transport = $this->transports[$mode] ?? null;
        if (!$transport) {
            throw new LocalizedException(__('The selected AI execution method is unavailable.'));
        }
        if (!$transport->supports($subject->getName())) {
            return $proceed($messages, $tools, $options);
        }
        if (!$transport instanceof \Hyva\Ai\Api\ToolTransportInterface) {
            throw new LocalizedException(__('The selected execution method does not support tool requests.'));
        }
        return $transport->chatWithTools($subject->getName(), $messages, $tools, $this->withDefaults($subject->getName(), $options));
    }

    public function aroundProcess(ProviderInterface $subject, callable $proceed, array $data, array $options = []): array
    {
        $mode = $this->config->getMode($options['_execution_feature'] ?? null);
        if ($mode === 'legacy' || $mode === 'php' || $subject->getName() === 'worker') {
            return $proceed($data, $options);
        }
        $transport = $this->transports[$mode] ?? null;
        if (!$transport) {
            throw new LocalizedException(__('The selected AI execution method is unavailable. Check Hyvä AI settings.'));
        }
        if (!$transport->supports($subject->getName())) {
            return $proceed($data, $options);
        }
        return $transport->process($subject->getName(), $data, $this->withDefaults($subject->getName(), $options));
    }

    private function withDefaults(string $provider, array $options): array
    {
        $config = $this->providerConfigs[$provider] ?? null;
        if ($config instanceof \Hyva\Ai\Api\ProviderConfigInterface) {
            $options += [
                'model' => $config->getDefaultModel(),
                'max_tokens' => $config->getDefaultMaxTokens(),
                'temperature' => $config->getDefaultTemperature(),
            ];
        }
        return $options;
    }
}
