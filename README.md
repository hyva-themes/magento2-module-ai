# Module - Hyva_Ai
`Hyva_Ai` provides the base framework to allow making requests to, and getting responses from AI providers, in a standardized manner when working with Hyvä.

Note: this modules contains the base framework only, integrations with specific AI providers are implemented in separate provider-specific modules.

## Installation

### For Hyvä license holders

1. Install via composer
    ```
    composer require hyva-themes/magento2-module-ai
    ```
1. Run `bin/magento setup:upgrade`

### For contributions

1. Install via composer
    ```
    composer config repositories.hyva-themes/magento2-module-ai git git@gitlab.hyva.io:hyva-themes/ai/module-ai.git
    composer require hyva-themes/magento2-module-ai:dev-main --prefer-source
    ```
1. Run `bin/magento setup:upgrade`

## Documentation

[https://docs.hyva.io/hyva-themes/ai/features-providers/index.html](https://docs.hyva.io/hyva-themes/ai/features-providers/index.html)
