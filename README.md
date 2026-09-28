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

## Settings and upgrades

Stores > Configuration > Hyvä AI > Settings contains the default execution choice and shared provider connections. CMS feature settings are under Hyvä AI > Hyvä CMS. Feature pickers show a provider first, then its
recent models. Search and **Show older models** keep the list manageable. Saved older
models remain visible; hiding a model from the shortlist does not migrate or delete it.
The shortlist is curated metadata, not a rolling expiry that changes saved behaviour.

The default execution setting `hyva_ai/execution/mode` is additive:

| Value | Behaviour |
| --- | --- |
| `legacy` (also the default when absent) | Preserve each feature's existing direct or worker selection. |
| `php` | Use direct provider adapters. Worker-only CMS generation is unavailable. |
| `worker` | Route supported provider requests and tool calls through the configured worker. Providers without an adapter, including DeepL, remain direct. |

Each feature can override Settings using `hyva_ai/<feature>/execution_mode`. An absent value
or `default` inherits Settings. Translations offer PHP and worker overrides; CMS content and
image generation offer a worker override because those flows require a worker. Image and
content overrides are independent.

`ExecutionConfig::getMode($feature)` resolves this precedence. Handler requests use the
registered handler name as their feature ID; the controller scopes the configured check and
the provider call together. Handler options also carry `_execution_feature` for server-side
callers. Scoped context is restored after each operation, including failures.

Existing configuration paths and `model_options` JSON are unchanged. Existing `model=worker`
selections with `underlying_provider` / `underlying_model` continue working. New selections
can store the actual model ID. No data patch is required. Install the AI packages together;
the provider and worker packages use the 1.1 base API. Refresh Magento configuration,
generated dependency injection code and admin static assets as part of the normal deployment.

Provider connection tests use the saved key and make a small direct request to the chosen
model, even when shared worker execution is selected. They may incur provider usage charges.
DeepL instead checks the saved Free/Pro key and allowance using its [usage endpoint](https://developers.deepl.com/api-reference/usage-and-quota/check-usage-and-limits), without sending text or consuming translation characters.
Worker connection testing is separate and does not call an AI provider.

## Extension points

- Keep registering providers, model options and option schemas through `ProviderPool`,
  `ProviderResolver` and `OptionsSchemaPool`. Their contracts are unchanged.
- `ModelCatalog` reads those registrations. Its optional `metadata` DI argument is keyed by
  model ID, with `current`, `capabilities` (`text`, `translation`) and `hidden_options` entries.
  Unknown third-party models are visible by default. `display_label` on a provider's existing
  resolver configuration sets its picker label.
- `RequestTransportInterface` adds an execution method without adding a pseudo-provider.
  Register it in `RouteProviderRequests.transports` and label it in `ExecutionMode.transports`.
  `ToolTransportInterface` is the optional tool-calling capability. Register provider defaults
  in `RouteProviderRequests.providerConfigs` so callers that omit options retain their defaults.
- `ConnectionTesterInterface` implementations register in `ConnectionTesterPool.testers`.
  The shared admin endpoint validates registered model IDs, form keys and configuration ACLs.
- `ProviderOptions` still accepts the original constructor arguments and virtual types;
  optional `capability` and `workerOnly` arguments narrow a feature's picker.

## License

This package is licensed under the **Open Software License (OSL 3.0)**.

* **Copyright:** Copyright © 2020-present Hyvä Themes. All rights reserved.
* **License Text:** The full text of the OSL 3.0 license can be found in the `LICENSE.txt` file within this package, and is also available online at [http://opensource.org/licenses/osl-3.0.php](http://opensource.org/licenses/osl-3.0.php).

### Provider package boundaries

The base module owns shared contracts, execution routing, credential handling and model
registries. Provider packages register their own settings, connection tester, credentials,
text models and image models. Installing the base plus one provider requires no other
provider, AI Worker or CMS package.

Register credentials in `Hyva\Ai\Model\ApiKeyResolverPool` and image choices in
`Hyva\Ai\Model\ImageModelRegistry`. The optional worker and CMS packages consume these
registries. Providers with a supported worker adapter declare `worker_adapter: true` in
their `ProviderResolver` configuration. Removing a provider removes its choices. Connection tests belong to the provider
package; DeepL uses its usage endpoint and distinguishes an unreadable saved key from a
missing key. An unreadable key must be re-entered and saved on the current installation.
