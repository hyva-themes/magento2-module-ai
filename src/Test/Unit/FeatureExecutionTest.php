<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Hyva\Ai\Test\Unit;

use Hyva\Ai\Api\ProviderInterface;
use Hyva\Ai\Api\RequestTransportInterface;
use Hyva\Ai\Model\ExecutionConfig;
use Hyva\Ai\Plugin\RouteProviderRequests;
use Magento\Framework\App\Config\ScopeConfigInterface;
use PHPUnit\Framework\TestCase;

class FeatureExecutionTest extends TestCase
{
    public function testFeaturesInheritUntilExplicitlyOverridden(): void
    {
        $config = $this->config([
            ExecutionConfig::PATH => 'php',
            'hyva_ai/ai_worker_cms/execution_mode' => 'worker',
            'hyva_ai/hyva_cms_translations/execution_mode' => 'default',
        ]);
        self::assertSame('worker', $config->getMode('ai_worker_cms'));
        self::assertSame('php', $config->getMode('hyva_cms_translations'));
        self::assertSame('php', $config->getMode('image_generation'));
        self::assertSame('php', $config->getMode());
    }

    public function testScopedModeIsRestoredEvenWhenNestedOperationFails(): void
    {
        $config = $this->config([
            ExecutionConfig::PATH => 'worker',
            'hyva_ai/hyva_cms_translations/execution_mode' => 'php',
        ]);
        $config->withFeature('hyva_cms_translations', function () use ($config) {
            self::assertSame('php', $config->getMode());
            try {
                $config->withFeature('image_generation', function () use ($config) {
                    self::assertSame('worker', $config->getMode());
                    throw new \RuntimeException('test');
                });
            } catch (\RuntimeException) {
            }
            self::assertSame('php', $config->getMode());
        });
        self::assertSame('worker', $config->getMode());
    }

    public function testTranslationOverrideRoutesIndependentlyOfTheGlobalDefault(): void
    {
        foreach ([['php', 'worker'], ['worker', 'php']] as [$global, $override]) {
            $config = $this->config([
                ExecutionConfig::PATH => $global,
                'hyva_ai/hyva_cms_translations/execution_mode' => $override,
            ]);
            $transport = $this->createMock(RequestTransportInterface::class);
            $transport->method('supports')->willReturn(true);
            $transport->expects($override === 'worker' ? self::once() : self::never())->method('process')->willReturn(['worker']);
            $provider = $this->createStub(ProviderInterface::class);
            $provider->method('getName')->willReturn('openai');
            $router = new RouteProviderRequests($config, ['worker' => $transport]);
            self::assertSame([$override], $router->aroundProcess($provider, static fn () => ['php'], [],
                ['_execution_feature' => 'hyva_cms_translations']));
        }
    }

    private function config(array $values): ExecutionConfig
    {
        $scope = $this->createStub(ScopeConfigInterface::class);
        $scope->method('getValue')->willReturnCallback(static fn ($path) => $values[$path] ?? null);
        return new ExecutionConfig($scope);
    }
}
