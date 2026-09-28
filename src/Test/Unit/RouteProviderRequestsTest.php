<?php
declare(strict_types=1);

namespace Hyva\Ai\Test\Unit;

use Hyva\Ai\Api\ProviderInterface;
use Hyva\Ai\Api\ProviderConfigInterface;
use Hyva\Ai\Api\RequestTransportInterface;
use Hyva\Ai\Api\ToolCapableProviderInterface;
use Hyva\Ai\Api\ToolTransportInterface;
use Hyva\Ai\Model\ExecutionConfig;
use Hyva\Ai\Plugin\RouteProviderRequests;
use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\TestCase;

class RouteProviderRequestsTest extends TestCase
{
    public function testLegacyPhpAndWorkerProviderKeepTheirOriginalPath(): void
    {
        foreach ([['legacy', 'openai'], ['php', 'openai'], ['worker', 'worker']] as [$mode, $name]) {
            $transport = $this->createMock(RequestTransportInterface::class);
            $transport->expects(self::never())->method('process');
            $router = new RouteProviderRequests($this->config($mode), ['worker' => $transport]);
            self::assertSame(['original'], $router->aroundProcess($this->provider($name),
                static fn ($data, $options) => $data, ['original']));
        }
    }

    public function testWorkerRoutingPreservesProviderDefaultsAndExplicitOptions(): void
    {
        $defaults = $this->createStub(ProviderConfigInterface::class);
        $defaults->method('getDefaultModel')->willReturn('default-model');
        $defaults->method('getDefaultMaxTokens')->willReturn(2048);
        $defaults->method('getDefaultTemperature')->willReturn(0.3);
        $transport = $this->createMock(RequestTransportInterface::class);
        $transport->method('supports')->with('openai')->willReturn(true);
        $transport->expects(self::once())->method('process')->with('openai', ['messages'], [
            'max_tokens' => 4000, 'model' => 'default-model', 'temperature' => 0.3,
        ])->willReturn(['worker']);
        $router = new RouteProviderRequests($this->config('worker'), ['worker' => $transport], ['openai' => $defaults]);
        self::assertSame(['worker'], $router->aroundProcess($this->provider('openai'),
            static function () { self::fail('Unexpected direct request'); }, ['messages'], ['max_tokens' => 4000]));
    }

    public function testProviderWithoutWorkerAdapterStaysDirect(): void
    {
        $transport = $this->createMock(RequestTransportInterface::class);
        $transport->method('supports')->with('deepl')->willReturn(false);
        $transport->expects(self::never())->method('process');
        $router = new RouteProviderRequests($this->config('worker'), ['worker' => $transport]);
        self::assertSame(['translated'], $router->aroundProcess($this->provider('deepl'),
            static fn () => ['translated'], []));
    }

    public function testWorkerFailureNeverSilentlyRetriesOnPhp(): void
    {
        $transport = $this->createMock(RequestTransportInterface::class);
        $transport->method('supports')->willReturn(true);
        $transport->expects(self::once())->method('process')->willThrowException(new LocalizedException(__('Worker unavailable')));
        $router = new RouteProviderRequests($this->config('worker'), ['worker' => $transport]);
        $this->expectException(LocalizedException::class);
        $router->aroundProcess($this->provider('openai'), static function () { self::fail('Unexpected fallback'); }, []);
    }

    public function testMissingSelectedTransportIsAnError(): void
    {
        $router = new RouteProviderRequests($this->config('missing'));
        $this->expectException(LocalizedException::class);
        $router->aroundProcess($this->provider('openai'), static function () { self::fail('Unexpected fallback'); }, []);
    }

    public function testToolRequestsUseTheSameSharedTransport(): void
    {
        $provider = $this->createMockForIntersectionOfInterfaces([ProviderInterface::class, ToolCapableProviderInterface::class]);
        $provider->expects(self::atLeastOnce())->method('getName')->willReturn('openai');
        $transport = $this->createMock(ToolTransportInterface::class);
        $transport->method('supports')->willReturn(true);
        $transport->expects(self::once())->method('chatWithTools')->with('openai', ['messages'], ['tools'], ['model' => 'chosen'])
            ->willReturn(['tool_calls' => []]);
        $router = new RouteProviderRequests($this->config('worker'), ['worker' => $transport]);
        self::assertSame(['tool_calls' => []], $router->aroundChatWithTools($provider,
            static function () { self::fail('Unexpected direct request'); }, ['messages'], ['tools'], ['model' => 'chosen']));
    }

    private function config(string $mode): ExecutionConfig
    {
        $config = $this->createStub(ExecutionConfig::class);
        $config->method('getMode')->willReturn($mode);
        return $config;
    }

    private function provider(string $name): ProviderInterface
    {
        $provider = $this->createStub(ProviderInterface::class);
        $provider->method('getName')->willReturn($name);
        return $provider;
    }
}
