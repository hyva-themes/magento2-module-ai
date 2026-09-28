<?php
declare(strict_types=1);

namespace Hyva\Ai\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Overview extends Field
{
    protected $_template = 'Hyva_Ai::system/config/overview.phtml';

    public function render(AbstractElement $element)
    {
        return '<tr><td colspan="4">' . $this->toHtml() . '</td></tr>';
    }
}
