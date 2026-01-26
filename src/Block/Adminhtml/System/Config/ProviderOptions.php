<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes 2020-present. All rights reserved.
 * This product is licensed per Magento install
 * See https://hyva.io/license
 */
declare(strict_types=1);

namespace Hyva\Ai\Block\Adminhtml\System\Config;

use Hyva\Ai\Api\Provider\OptionsSchemaPoolInterface;
use Hyva\Ai\Api\ProviderResolverInterface;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * Reusable frontend model for provider-specific options in system config
 *
 * This block renders dynamic form fields based on the selected AI model's
 * provider schema. Use via virtual type to configure for your implementation:
 *
 * <virtualType name="YourModule\Block\ProviderOptions" type="Hyva\Ai\Block\Adminhtml\System\Config\ProviderOptions">
 *     <arguments>
 *         <argument name="modelFieldId" xsi:type="string">your_config_group_model</argument>
 *     </arguments>
 * </virtualType>
 */
class ProviderOptions extends Field
{
    protected $_template = 'Hyva_Ai::system/config/provider_options.phtml';

    public function __construct(
        Context $context,
        private readonly OptionsSchemaPoolInterface $optionsSchemaPool,
        private readonly ProviderResolverInterface $providerResolver,
        private readonly SerializerInterface $serializer,
        private readonly string $modelFieldId = '',
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Get the HTML ID of the model select field this options field depends on
     *
     * Can be set via:
     * 1. Constructor argument (DI/virtual type)
     * 2. Block data attribute 'model_field_id'
     * 3. Derived from element HTML ID (replaces '_model_options' with '_model')
     */
    public function getModelFieldId(): string
    {
        // First check constructor argument
        if ($this->modelFieldId) {
            return $this->modelFieldId;
        }

        // Then check block data
        if ($this->hasData('model_field_id')) {
            return $this->getData('model_field_id');
        }

        // Finally, try to derive from element ID
        // e.g., 'hyva_ai_translations_model_options' -> 'hyva_ai_translations_model'
        $elementId = $this->getHtmlId();
        if (str_ends_with($elementId, '_model_options')) {
            return str_replace('_model_options', '_model', $elementId);
        }

        return '';
    }

    /**
     * Get the element HTML ID
     */
    public function getHtmlId(): string
    {
        return $this->getElement()->getHtmlId();
    }

    /**
     * Get all provider schemas indexed by model ID
     */
    public function getProviderSchemasByModel(): array
    {
        $schemasByModel = [];
        $allProviders = $this->providerResolver->getProviderConfigurations();

        foreach ($allProviders as $providerId => $providerData) {
            $schema = $this->optionsSchemaPool->getSchema($providerId);
            if (!$schema) {
                continue;
            }

            $models = $providerData['options'] ?? [];
            foreach ($models as $model) {
                $modelId = $model['value'] ?? null;
                if ($modelId) {
                    $schemasByModel[$modelId] = $schema;
                }
            }
        }

        return $schemasByModel;
    }

    /**
     * Get schemas data as JSON for JavaScript
     */
    public function getSchemasJson(): string
    {
        $schemasByModel = $this->getProviderSchemasByModel();
        $schemasData = [];

        foreach ($schemasByModel as $modelId => $schema) {
            $schemasData[$modelId] = [
                'provider_id' => $schema->getProviderId(),
                'fields' => $schema->getFields()
            ];
        }

        return $this->serializer->serialize($schemasData);
    }

    /**
     * Get current saved values
     */
    public function getCurrentValues(): array
    {
        $value = $this->getElement()->getValue();

        if (empty($value)) {
            return [];
        }

        if (is_string($value)) {
            try {
                return $this->serializer->unserialize($value);
            } catch (\Exception $e) {
                return [];
            }
        }

        return is_array($value) ? $value : [];
    }

    /**
     * Get current values as JSON for JavaScript
     */
    public function getCurrentValuesJson(): string
    {
        return $this->serializer->serialize($this->getCurrentValues());
    }

    /**
     * Get value as JSON string for textarea
     */
    public function getValueAsJson(): string
    {
        $value = $this->getElement()->getValue();

        if (is_array($value)) {
            return $this->serializer->serialize($value);
        }

        if (is_string($value) && !empty($value)) {
            return $value;
        }

        return '[]';
    }

    /**
     * Render element HTML
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        $this->setElement($element);
        return $this->_toHtml();
    }
}
