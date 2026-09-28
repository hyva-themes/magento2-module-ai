<?php
declare(strict_types=1);

namespace Hyva\Ai\Model;

use Magento\Framework\HTTP\Client\Curl;

class HttpRequest
{
    public function __construct(private readonly RequestBudget $budget)
    {
    }

    public function post(Curl $curl, string $url, string $body): void
    {
        $this->budget->releaseSession();
        $deadline = microtime(true) + $this->budget->remainingSeconds();
        for ($attempt = 0; $attempt < 2; $attempt++) {
            $remaining = max(1, (int) floor($deadline - microtime(true)));
            $curl->setTimeout($remaining);
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, min(5, $remaining));
            $curl->setOption(CURLOPT_FOLLOWLOCATION, false);
            $curl->post($url, $body);
            if ($attempt > 0 || !in_array($curl->getStatus(), [429, 502, 503, 504], true)) {
                return;
            }
            $headers = array_change_key_case($curl->getHeaders(), CASE_LOWER);
            $retryAfter = (string) ($headers['retry-after'] ?? '1');
            $delay = ctype_digit($retryAfter) ? max(1, (int) $retryAfter)
                : max(1, (strtotime($retryAfter) ?: time()) - time());
            if ($delay > 2 || $deadline - microtime(true) < $delay + 2) {
                return;
            }
            usleep($delay * 1000000);
        }
    }
}
