<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Hyva\Ai\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;

class Introduction extends Template implements RendererInterface
{
    protected $_template = 'Hyva_Ai::system/config/overview.phtml';

    public function render(AbstractElement $element)
    {
        return $this->toHtml();
    }
}
