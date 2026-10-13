# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

## [1.3.1] - 2026-10-13

[1.3.1]: https://gitlab.hyva.io/hyva-themes/ai/module-ai/-/compare/1.3.0...1.3.1

### Changed
- The Hyvä AI settings section (Stores > Configuration > Hyvä AI > Settings), which holds the provider API keys, needs its own resource, Hyvä AI Section (`Hyva_Ai::config`) under Stores > Settings > Configuration, instead of Content. The provider connection test needs it too. Existing roles are not migrated; grant it to roles that manage AI settings.

## [1.3.0] - 2026-10-09

[1.3.0]: https://gitlab.hyva.io/hyva-themes/ai/module-ai/-/compare/1.2.0...1.3.0

### Added
- A **Run requests using** setting that chooses how features run AI requests by default, for example direct PHP or a worker such as Hyvä AI Worker. Each feature can override it. When it isn't set, existing feature choices are kept.
- Shared provider contracts for API keys, connection tests, image models and worker streaming, used by the provider modules and Hyvä AI Worker. OpenAI, Gemini and Anthropic keys can also come from `HYVA_AI_<PROVIDER>_API_KEY` environment variables.
- A **Test saved connection** button for each provider.

### Changed
- The Settings page opens with a Getting started overview, and the model picker has search and **Show older models**.
- Direct PHP requests are limited by the time left in the PHP request.

### Fixed
- The Hyvä AI configuration tab logo no longer returns a 404 after `setup:static-content:deploy -s compact`.
- Removed a link to a missing `model-picker.css`, which logged a console error for each provider field.

## [1.2.0] - 2026-07-03

### Added
- New ToolCapableProviderInterface so Providers can be marked as "Tools are available", see the [Anthropic provider](https://gitlab.hyva.io/hyva-themes/ai/module-ai-anthropic/)

## [1.1.0] - 2026-06-30

### Added
- Configuration for Runtime Safeguards to limit the number of concurrent AI requests and the timeout for each request.
As AI API requests can take several seconds to complete, this makes sure that the rest of the site stays responsive.
- New system config tab and logo

## [1.0.0] - 2026-02-03

### Added

- Initial implementation of the AI framework to allow providers and their configuration to be created in a standardized way
