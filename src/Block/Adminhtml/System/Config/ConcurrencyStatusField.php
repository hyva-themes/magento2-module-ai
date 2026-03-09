<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Hyva\Ai\Block\Adminhtml\System\Config;

use Hyva\Ai\Model\ConcurrencyGuard;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Renders the Max Concurrent AI Requests input with a live status note below it
 * showing how many slots are currently in use and which lock backend is active.
 */
class ConcurrencyStatusField extends Field
{
    protected $_template = 'Hyva_Ai::system/config/concurrency_status.phtml';

    /** @see https://experienceleague.adobe.com/en/docs/commerce-operations/installation-guide/tutorials/lock-provider */
    public const LOCK_PROVIDER_DOCS_URL = 'https://experienceleague.adobe.com/en/docs/commerce-operations/installation-guide/tutorials/lock-provider';

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        private readonly ConcurrencyGuard $concurrencyGuard,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Render the standard text input followed by the status note.
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        return $element->getElementHtml() . $this->toHtml();
    }

    public function getActiveLockCount(): int
    {
        return $this->concurrencyGuard->getActiveLockCount();
    }

    public function getMaxSlots(): int
    {
        return count($this->concurrencyGuard->getSlotNames());
    }

    public function getLockProviderDocsUrl(): string
    {
        return self::LOCK_PROVIDER_DOCS_URL;
    }
}
