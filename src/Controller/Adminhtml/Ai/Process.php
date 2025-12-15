<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes 2020-present. All rights reserved.
 * This product is licensed per Magento install
 * See https://hyva.io/license
 */
declare(strict_types=1);

namespace Hyva\Ai\Controller\Adminhtml\Ai;

use Hyva\Ai\Api\ProviderInterface;
use Hyva\Ai\Api\HandlerInterface;
use Hyva\Ai\Api\ProviderResolverInterface;
use Hyva\Ai\Model\ErrorHandler;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
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
        private readonly ProviderResolverInterface $providerResolver,
        private readonly array $providers = [],
        private readonly array $handlers = []
    ) {
        parent::__construct($context);
    }

    public function execute(): Json
    {
        $result = $this->resultJsonFactory->create();

        try {
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

    private function getProvider(string $providerName): ProviderInterface
    {
        if (!isset($this->providers[$providerName])) {
            throw new LocalizedException(__('AI provider "%1" is not available.', $providerName));
        }

        return $this->providers[$providerName];
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

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
