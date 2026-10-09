<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Hyva\Ai\Plugin;

use Hyva\Ai\Api\HandlerInterface;
use Hyva\Ai\Controller\Adminhtml\Ai\Process;
use Hyva\Ai\Model\ExecutionConfig;

class UseHandlerExecution
{
    public function __construct(private readonly ExecutionConfig $config)
    {
    }

    public function aroundExecute(Process $subject, callable $proceed)
    {
        return $this->config->withFeature((string) $subject->getRequest()->getParam('handler'), $proceed);
    }

    public function afterGetOptions(HandlerInterface $subject, array $options): array
    {
        $options['_execution_feature'] = $subject->getName();
        return $options;
    }
}
