<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Hyva\Ai\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

class LocaleResolver
{
    private ResourceConnection $resourceConnection;
    private LoggerInterface $logger;
    private array $localeCache = [];

    public function __construct(
        ResourceConnection $resourceConnection,
        LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
    }

    /**
     * Get locale code for a specific store ID
     *
     * @param int $storeId
     * @return string
     */
    public function getLocaleByStoreId(int $storeId): string
    {
        if (isset($this->localeCache[$storeId])) {
            return $this->localeCache[$storeId];
        }

        $connection = $this->resourceConnection->getConnection();
        $configTable = $this->resourceConnection->getTableName('core_config_data');

        try {
            $select = $connection->select()
                ->from($configTable, ['value'])
                ->where('path = ?', 'general/locale/code')
                ->where('scope = ?', ScopeInterface::SCOPE_STORES)
                ->where('scope_id = ?', $storeId);

            $locale = $connection->fetchOne($select);

            if (!$locale) {
                $select = $connection->select()
                    ->from($configTable, ['value'])
                    ->where('path = ?', 'general/locale/code')
                    ->where('scope = ?', 'default')
                    ->where('scope_id = ?', 0);

                $locale = $connection->fetchOne($select);
            }

            $locale = $locale ?: 'en_US';

            $this->localeCache[$storeId] = $locale;

            return $locale;

        } catch (\Exception $e) {
            $this->logger->error('Error retrieving locale for store ID ' . $storeId . ': ' . $e->getMessage());

            $this->localeCache[$storeId] = 'en_US';
            return 'en_US';
        }
    }

    /**
     * Get all store locales
     *
     * @param array $storeIds
     * @return array Array with store_id => locale_code mapping
     */
    public function getMultipleStoreLocales(array $storeIds): array
    {
        $locales = [];

        foreach ($storeIds as $storeId) {
            $locales[$storeId] = $this->getLocaleByStoreId($storeId);
        }

        return $locales;
    }

    /**
     * Clear locale cache
     */
    public function clearCache(): void
    {
        $this->localeCache = [];
    }
}
