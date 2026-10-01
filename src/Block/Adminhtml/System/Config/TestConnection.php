<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Hyva\Ai\Block\Adminhtml\System\Config;

use Hyva\Ai\Model\ModelCatalog;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class TestConnection extends Field
{
    protected $_template = 'Hyva_Ai::system/config/test-connection.phtml';

    public function __construct(Context $context, private readonly ModelCatalog $catalog, array $data = [])
    {
        parent::__construct($context, $data);
    }

    protected function _getElementHtml(AbstractElement $element)
    {
        $this->setData('provider_id', preg_replace('/^hyva_ai_|_test_connection$/', '', $element->getHtmlId()));
        return $this->toHtml();
    }

    public function getProviderId(): string
    {
        return (string) $this->getData('provider_id');
    }

    public function getModels(): array
    {
        return array_filter($this->catalog->getProviders()[$this->getProviderId()]['models'] ?? [],
            static fn (array $model): bool => $model['current']);
    }

    public function getFixedModel(): string
    {
        return (string) $this->getData('fixed_model');
    }

    public function getTestDescription(): string
    {
        return (string) __($this->getData('test_description')
            ?: 'Save your API key first. This makes a small direct request to the provider and may incur a small usage charge. It does not change your feature models or test the worker.');
    }
}
