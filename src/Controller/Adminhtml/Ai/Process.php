<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Hyva\Ai\Controller\Adminhtml\Ai;

use Hyva\Ai\Api\HandlerInterface;
use Hyva\Ai\Api\ProviderPoolInterface;
use Hyva\Ai\Api\ProviderResolverInterface;
use Hyva\Ai\Exception\ConcurrencyLimitExceededException;
use Hyva\Ai\Model\ErrorHandler;
use Hyva\Ai\Model\ConcurrencyGuard;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;

class Process extends Action implements HttpPostActionInterface, HttpGetActionInterface, CsrfAwareActionInterface
{
    public const ADMIN_RESOURCE = 'Magento_Backend::content';

    public function __construct(
        Context $context,
        private readonly JsonFactory $resultJsonFactory,
        private readonly JsonSerializer $jsonSerializer,
        private readonly ErrorHandler $errorHandler,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly ProviderResolverInterface $providerResolver,
        private readonly ProviderPoolInterface $providerPool,
        private readonly ConcurrencyGuard $concurrencyGuard,
        private readonly array $handlers = []
    ) {
        parent::__construct($context);
    }

    public function execute(): Json
    {
        $result = $this->resultJsonFactory->create();

        try {
            $lockName = '';
            $handler = $this->getRequest()->getParam('handler');

            if (!$handler) {
                throw new LocalizedException(__('Handler parameter is required.'));
            }

            $handlerInstance = $this->getHandler($handler);
            $provider = $this->determineProviderFromHandler($handlerInstance);
            $providerInstance = $this->getProvider($provider);

            if (!$providerInstance->isConfigured()) {
                throw new LocalizedException(__('AI provider "%1" is not properly configured.', $provider));
            }

            $requestData = match (true) {
                $this->getRequest()->isGet() => $this->parseGetRequest(),
                $this->getRequest()->isPost() => $this->parsePostRequest(),
                default => throw new LocalizedException(__('Invalid request method.'))
            };

            try {
                $lockName = $this->concurrencyGuard->acquire();
                $this->applyRequestTimeout();

                $handlerData = $handlerInstance->getData($requestData);
                $providerResponse = $providerInstance->process($handlerData, $handlerInstance->getOptions());

                $processedData = method_exists($handlerInstance, 'processResponse')
                    ? $handlerInstance->processResponse($providerResponse, $handlerData)
                    : $providerResponse;

                return $result->setData([
                    'success' => true,
                    'provider' => $provider,
                    'handler' => $handler,
                    'data' => $processedData,
                    'count' => is_countable($processedData) ? count($processedData) : 1,
                    'message' => __('AI processing completed successfully.')
                ]);
            } finally {
                if ($lockName !== '') {
                    $this->concurrencyGuard->release($lockName);
                }
            }

        } catch (ConcurrencyLimitExceededException $e) {
            $this->errorHandler->logError($e);
            return $result->setData([
                'success' => false,
                'code' => 429,
                'message' => $e->getMessage()
            ]);
        } catch (LocalizedException $e) {
            $this->errorHandler->logError($e);
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        } catch (\Throwable $e) {
            $this->errorHandler->logError($e);
            return $result->setData([
                'success' => false,
                'message' => __('An error occurred while processing the AI request: %1', $e->getMessage())
            ]);
        }
    }

    private function getProvider(string $providerName): \Hyva\Ai\Api\ProviderInterface
    {
        return $this->providerPool->get($providerName);
    }

    private function getHandler(string $handlerName): HandlerInterface
    {
        if (!isset($this->handlers[$handlerName])) {
            throw new LocalizedException(__('AI handler "%1" is not available.', $handlerName));
        }

        return $this->handlers[$handlerName];
    }

    private function determineProviderFromHandler(HandlerInterface $handler): string
    {
        $options = $handler->getOptions();
        $model = $options['model'] ?? null;

        if (!$model) {
            throw new LocalizedException(__('Handler does not specify a model.'));
        }

        return $this->providerResolver->resolveProviderFromModel($model);
    }

    private function parseGetRequest(): array
    {
        $data = $this->getRequest()->getParam('data');

        if (!$data) {
            throw new LocalizedException(__('Missing data parameter.'));
        }

        return $this->jsonSerializer->unserialize($data);
    }

    private function parsePostRequest(): array
    {
        $data = $this->getRequest()->getParam('data');

        if (!$data) {
            throw new LocalizedException(__('Missing data parameter.'));
        }

        return $this->jsonSerializer->unserialize($data);
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    private function applyRequestTimeout(): void
    {
        $timeout = (int) ($this->scopeConfig->getValue(
            ConcurrencyGuard::XML_PATH_REQUEST_TIMEOUT_SECONDS
        ) ?? 0);

        if ($timeout <= 0) {
            return;
        }

        if (!function_exists('set_time_limit')) {
            return;
        }

        try {
            set_time_limit($timeout);
        } catch (\Throwable $e) {
            // Ignore failures; PHP configuration may disallow set_time_limit()
        }
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
