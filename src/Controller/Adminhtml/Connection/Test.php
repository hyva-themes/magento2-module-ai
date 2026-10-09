<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Hyva\Ai\Controller\Adminhtml\Connection;

use Hyva\Ai\Model\ConnectionTesterPool;
use Hyva\Ai\Model\ModelCatalog;
use Hyva\Ai\Model\ConcurrencyGuard;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;

class Test extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Magento_Config::config';

    public function __construct(
        Context $context,
        private readonly JsonFactory $jsonFactory,
        private readonly ConnectionTesterPool $testers,
        private readonly ModelCatalog $catalog,
        private readonly ConcurrencyGuard $concurrency
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();
        $lock = '';
        try {
            $provider = (string) $this->getRequest()->getParam('provider');
            $model = (string) $this->getRequest()->getParam('model');
            $models = $this->catalog->getProviders()[$provider]['models'] ?? [];
            if (!isset($models[$model])) {
                throw new LocalizedException(__('Choose a registered model for this provider.'));
            }
            $this->_session->writeClose();
            $lock = $this->concurrency->acquire();
            $this->testers->get($provider)->test($model);
            return $result->setData(['success' => true,
                'message' => (string) __('Connected. The saved API key can access %1.', $models[$model]['label'] ?? $model)]);
        } catch (LocalizedException $e) {
            return $result->setData(['success' => false, 'message' => $e->getMessage()]);
        } catch (\Throwable $e) {
            return $result->setData(['success' => false,
                'message' => (string) __('The connection test failed. Check the saved API key and try again.')]);
        } finally {
            if ($lock !== '') {
                $this->concurrency->release($lock);
            }
        }
    }
}
