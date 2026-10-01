<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Hyva\Ai\Test\Unit;

use Hyva\Ai\Model\HttpRequest;
use Hyva\Ai\Model\RequestBudget;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\HTTP\Client\Curl;
use PHPUnit\Framework\TestCase;

class HttpRequestTest extends TestCase
{
    public function testZeroTimeoutUsesBoundedDefault(): void
    {
        $config = $this->createStub(ScopeConfigInterface::class);
        $config->method('getValue')->willReturn('0');
        self::assertSame(25, (new RequestBudget($config))->remainingSeconds());
    }

    public function testTransientFailureRetriesWithinBudget(): void
    {
        $budget = $this->createMock(RequestBudget::class);
        $budget->method('remainingSeconds')->willReturn(10);
        $budget->expects(self::once())->method('releaseSession');
        $curl = $this->createMock(Curl::class);
        $curl->expects(self::exactly(2))->method('post');
        $curl->method('getStatus')->willReturn(503, 200);
        $curl->method('getHeaders')->willReturn(['Retry-After' => '1']);
        $curl->expects(self::exactly(2))->method('setTimeout')->with(self::callback(
            static fn (int $seconds): bool => $seconds > 0 && $seconds <= 10
        ));
        (new HttpRequest($budget))->post($curl, 'https://provider.test', '{}');
    }

    public function testLongRetryAfterDoesNotBlockPhp(): void
    {
        $budget = $this->createStub(RequestBudget::class);
        $budget->method('remainingSeconds')->willReturn(25);
        $curl = $this->createMock(Curl::class);
        $curl->expects(self::once())->method('post');
        $curl->method('getStatus')->willReturn(429);
        $curl->method('getHeaders')->willReturn(['retry-after' => '60']);
        (new HttpRequest($budget))->post($curl, 'https://provider.test', '{}');
    }

    public function testAmbiguousNetworkFailureIsNotRetried(): void
    {
        $budget = $this->createStub(RequestBudget::class);
        $budget->method('remainingSeconds')->willReturn(25);
        $curl = $this->createMock(Curl::class);
        $curl->expects(self::once())->method('post')->willThrowException(new \RuntimeException('timeout'));
        $this->expectException(\RuntimeException::class);
        (new HttpRequest($budget))->post($curl, 'https://provider.test', '{}');
    }
}
