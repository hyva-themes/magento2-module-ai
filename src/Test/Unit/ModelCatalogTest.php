<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Hyva\Ai\Test\Unit;

use Hyva\Ai\Api\ProviderResolverInterface;
use Hyva\Ai\Api\Provider\OptionsSchemaPoolInterface;
use Hyva\Ai\Model\ModelCatalog;
use Hyva\Ai\Model\ExecutionConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use PHPUnit\Framework\TestCase;

class ModelCatalogTest extends TestCase
{
    public function testOlderModelsRemainRegisteredAndNewProvidersWorkWithoutMetadata(): void
    {
        $resolver = $this->createStub(ProviderResolverInterface::class);
        $resolver->method('getProviderConfigurations')->willReturn([
            'example' => ['display_label' => 'Example AI', 'options' => [
                ['value' => 'old', 'label' => 'Old model'], ['value' => 'new', 'label' => 'New model'],
            ]],
            'worker' => ['options' => [['value' => 'worker', 'label' => 'AI Worker']]],
        ]);
        $schemas = $this->createStub(OptionsSchemaPoolInterface::class);
        $catalog = new ModelCatalog($resolver, $schemas, ['old' => [
            'current' => false, 'capabilities' => ['translation' => 'translation'],
            'hidden_options' => ['temperature' => 'temperature'],
        ]]);
        $result = $catalog->getProviders();
        self::assertArrayNotHasKey('worker', $result);
        self::assertSame('Example AI', $result['example']['label']);
        self::assertFalse($result['example']['models']['old']['current']);
        self::assertTrue($result['example']['models']['new']['current']);
        self::assertSame(['translation'], $result['example']['models']['old']['capabilities']);
        self::assertSame(['temperature'], $result['example']['models']['old']['hidden_options']);
    }

    public function testUpgradesDoNotOptIntoSharedWorkerExecution(): void
    {
        $scope = $this->createStub(ScopeConfigInterface::class);
        $scope->method('getValue')->willReturn(null);
        self::assertSame('legacy', (new ExecutionConfig($scope))->getMode());
    }
}
