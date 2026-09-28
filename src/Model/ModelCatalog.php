<?php
declare(strict_types=1);

namespace Hyva\Ai\Model;

use Hyva\Ai\Api\ProviderResolverInterface;
use Hyva\Ai\Api\Provider\OptionsSchemaPoolInterface;

class ModelCatalog
{
    public function __construct(
        private readonly ProviderResolverInterface $resolver,
        private readonly OptionsSchemaPoolInterface $schemas,
        private readonly array $metadata = []
    ) {
    }

    public function getProviders(): array
    {
        $providers = [];
        foreach ($this->resolver->getProviderConfigurations() as $id => $config) {
            if ($id === 'worker') {
                continue;
            }
            $providers[$id] = [
                'label' => $config['display_label'] ?? ucfirst($id),
                'direct' => (bool) ($config['direct'] ?? true),
                'fields' => $this->schemas->getSchema($id)?->getFields() ?? [],
                'models' => [],
            ];
            foreach ($config['options'] ?? [] as $option) {
                $value = (string) ($option['value'] ?? '');
                if ($value !== '') {
                    $providers[$id]['models'][$value] = array_replace([
                        'label' => (string) $option['label'], 'current' => true,
                        'capabilities' => ['text', 'translation'], 'hidden_options' => [],
                    ], $this->metadata[$value] ?? []);
                    foreach (['capabilities', 'hidden_options'] as $key) {
                        $providers[$id]['models'][$value][$key] = array_values($providers[$id]['models'][$value][$key]);
                    }
                }
            }
        }
        return $providers;
    }
}
