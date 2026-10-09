/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes 2020-present. All rights reserved.
 * This product is licensed per Magento install
 * See https://hyva.io/license
 */
define(['jquery', 'mage/translate'], function ($, $t) {
    'use strict';

    const WORKER_MODEL = 'worker';
    const SAVED_PROVIDER = 'saved';
    const UNDERLYING_PREFIX = 'underlying_';
    const GLOBAL_EXECUTION_FIELD_ID = 'hyva_ai_execution_mode';
    const EXECUTION = { DEFAULT: 'default', PHP: 'php', WORKER: 'worker' };
    const TRUE_VALUES = new Set([true, 1, '1', 'true']);
    const FORM_CONTROLS = 'input,select';

    /**
     * Parse the stored options JSON, falling back to an empty object for anything that is not a plain object.
     */
    const parseOptions = (json) => {
        try {
            const parsed = JSON.parse(json || '{}');
            return parsed && typeof parsed === 'object' && !Array.isArray(parsed) ? parsed : {};
        } catch (error) {
            return {};
        }
    };

    const note = (text) => $('<p>', { class: 'note', text });

    /**
     * Replace a plain model <select> with a provider/model picker and render the per-model option fields.
     *
     * @param {{elementId: string, modelFieldId: string, picker: Object}} config
     */
    return function (config) {
        const source = document.getElementById(config.modelFieldId);
        const storage = document.getElementById(config.elementId);
        const fields = document.getElementById(`${config.elementId}_container`);
        if (!source || !storage || !fields) {
            return;
        }

        const { providers = {}, capability, workerOnly, execution: defaultExecution, executionFieldId } = config.picker;
        const $fields = $(fields);
        const allowed = new Set(Array.from(source.options, (option) => option.value));
        const legacy = source.value === WORKER_MODEL;
        const saved = parseOptions(storage.value);
        const overrideInput = executionFieldId ? document.getElementById(executionFieldId) : null;
        const globalInput = document.getElementById(GLOBAL_EXECUTION_FIELD_ID);

        const findProviderFor = (model) => Object.keys(providers).find((id) => providers[id].models[model]) || '';
        const initialModel = legacy ? String(saved.underlying_model || '') : source.value;
        const initialProvider = legacy ? String(saved.underlying_provider || '') : findProviderFor(initialModel);

        const state = {
            provider: initialProvider || (initialModel ? SAVED_PROVIDER : ''),
            model: initialModel,
            options: { ...saved },
        };
        const drafts = new Map([[state.provider, { ...saved }]]);
        const choices = new Map([[state.provider, state.model]]);

        const ids = {
            provider: `${source.id}_provider`,
            model: `${source.id}_choice`,
            search: `${source.id}_search`,
            older: `${source.id}_older`,
        };
        const providerInput = $('<select>', { id: ids.provider, class: 'admin__control-select' });
        const modelInput = $('<select>', { id: ids.model, class: 'admin__control-select' });
        const searchInput = $('<input>', {
            type: 'search',
            id: ids.search,
            class: 'admin__control-text',
            placeholder: $t('Search this provider’s models'),
        });
        const olderToggle = $('<input>', { type: 'checkbox', id: ids.older });
        const status = $('<p>', { class: 'note', role: 'status', 'aria-live': 'polite' });
        const picker = $('<div>', { class: 'hyva-ai-picker' }).append(
            $('<label>', { for: ids.provider, text: $t('Provider') }),
            providerInput,
            $('<label>', { for: ids.model, text: $t('Model') }),
            modelInput,
            $('<label>', { for: ids.search, class: 'hyva-ai-search-label', text: $t('Find a model') }),
            searchInput,
            $('<label>', { class: 'hyva-ai-older' })
                .append(olderToggle, document.createTextNode(` ${$t('Show older models')}`)),
            status
        );

        const isDisabled = () => source.disabled || storage.disabled;
        const currentProvider = () => providers[state.provider];
        const currentModel = () => currentProvider()?.models[state.model];

        const isSelectable = (id, model) => {
            const capable = !capability || (model.capabilities || []).includes(capability);
            const workerReady = !workerOnly || Boolean(model.worker);
            const permitted = allowed.has(id) || (allowed.has(WORKER_MODEL) && Boolean(model.worker));
            return capable && workerReady && permitted;
        };

        const usesWorker = () => {
            if (!state.model || (!legacy && allowed.has(state.model))) {
                return false;
            }
            const model = currentModel();
            return Boolean(model?.worker) || (legacy && !model);
        };

        const executionMode = () => {
            const override = overrideInput?.value;
            if (override && override !== EXECUTION.DEFAULT) {
                return override;
            }
            return globalInput?.value || defaultExecution;
        };

        const describeExecution = (model, mode) => {
            if (workerOnly && mode === EXECUTION.PHP) {
                return $t('This feature needs an AI Worker. Choose AI Worker in Settings or in the override below.');
            }
            if (mode === EXECUTION.PHP && currentProvider()?.direct === false) {
                return $t('This provider needs an AI Worker. Choose worker execution or a provider with PHP support.');
            }
            if (workerOnly) {
                return $t('Runs through AI Worker.');
            }
            if ((usesWorker() && mode !== EXECUTION.PHP) || (mode === EXECUTION.WORKER && model?.worker)) {
                return $t('Runs through AI Worker. This feature still waits for its response within the PHP request limit.');
            }
            const direct = $t('Runs directly on your PHP server.');
            return mode === EXECUTION.WORKER
                ? `${direct} ${$t('This provider does not have a worker adapter yet.')}`
                : direct;
        };

        const statusMessage = () => {
            if (!state.model) {
                return $t('Choose a provider and model. Recent models are shown first; saved older choices are kept.');
            }
            const model = currentModel();
            const parts = [describeExecution(model, executionMode())];
            if (model && !model.current) {
                parts.push($t('Your saved older model remains available.'));
            }
            return parts.join(' ');
        };

        const updateStatus = () => status.text(statusMessage());

        const syncDisabled = () => {
            const disabled = isDisabled();
            picker.find(FORM_CONTROLS).prop('disabled', disabled);
            modelInput.prop('disabled', disabled || !state.provider);
            $fields.find(FORM_CONTROLS).prop('disabled', disabled);
        };

        const renderProviders = () => {
            providerInput.append(new Option($t('Choose a provider'), ''));
            Object.entries(providers)
                .filter(([id, entry]) => id === state.provider
                    || Object.entries(entry.models).some(([model, info]) => isSelectable(model, info)))
                .forEach(([id, entry]) => providerInput.append(new Option(entry.label, id)));
            if (state.provider === SAVED_PROVIDER) {
                providerInput.append(new Option($t('Saved selection'), SAVED_PROVIDER));
            } else if (state.provider && !currentProvider()) {
                providerInput.append(new Option(`${state.provider} (${$t('saved provider')})`, state.provider));
            }
            providerInput.val(state.provider);
        };

        const renderModels = () => {
            const query = searchInput.val().trim().toLowerCase();
            const showOlder = olderToggle.prop('checked');
            const models = currentProvider()?.models || {};
            const matchesQuery = (id, model) => !query || `${model.label} ${id}`.toLowerCase().includes(query);
            const isVisible = (id, model) => id === state.model
                || (isSelectable(id, model) && (showOlder || model.current) && matchesQuery(id, model));

            modelInput.empty().append(new Option($t('Choose a model'), ''));
            Object.entries(models)
                .filter(([id, model]) => isVisible(id, model))
                .forEach(([id, model]) => {
                    const label = model.current ? model.label : `${model.label} · ${$t('older model')}`;
                    modelInput.append(new Option(label, id));
                });
            if (state.model && !models[state.model]) {
                modelInput.append(new Option(`${state.model} · ${$t('saved model')}`, state.model));
            }
            modelInput.val(state.model);
            updateStatus();
            syncDisabled();
        };

        const createInput = (field, value) => {
            if (field.type === 'select') {
                const options = field.options || [];
                const input = $('<select>');
                options.forEach((option) => input.append(new Option(option.label, option.value)));
                if (value !== '' && !options.some((option) => String(option.value) === String(value))) {
                    input.append(new Option(String(value), String(value)));
                }
                return input.val(value);
            }
            if (field.type === 'boolean') {
                return $('<input type="checkbox">').prop('checked', TRUE_VALUES.has(value));
            }
            return $('<input type="text">').val(value);
        };

        const renderField = (field) => {
            const id = `${storage.id}_${field.id}`;
            const value = state.options[field.id] ?? field.default ?? '';
            const input = createInput(field, value)
                .attr({ id, 'data-option': field.id })
                .addClass(field.validate || '')
                .toggleClass('required-entry', Boolean(field.required))
                .on('input change', persist);
            const row = $('<div>', { class: 'hyva-ai-option' })
                .append($('<label>', { for: id, text: field.label }), input);
            if (field.comment) {
                row.append(note(field.comment));
            }
            return row;
        };

        const renderFields = () => {
            $fields.empty();
            const entry = currentProvider();
            if (!entry || !state.model) {
                $fields.append(note($t('Choose a model to see its options.')));
                return;
            }
            const hidden = entry.models[state.model]?.hidden_options || [];
            const definitions = (entry.fields || [])
                .filter((field) => !field.id.startsWith(UNDERLYING_PREFIX) && !hidden.includes(field.id));
            if (!definitions.length) {
                $fields.append(note($t('This model needs no additional settings.')));
            }
            $fields.append(definitions.map(renderField));
            syncDisabled();
        };

        const collectOptions = () => {
            $fields.find('[data-option]').each((index, input) => {
                state.options[input.dataset.option] = input.type === 'checkbox' ? input.checked : input.value;
            });
        };

        const ensureSourceOption = (value) => {
            if (value && !Array.from(source.options).some((option) => option.value === value)) {
                source.add(new Option(value, value));
            }
        };

        const persist = () => {
            if (isDisabled()) {
                return;
            }
            collectOptions();
            const output = { ...state.options };
            if (usesWorker()) {
                source.value = WORKER_MODEL;
                output.underlying_provider = state.provider === SAVED_PROVIDER
                    ? saved.underlying_provider
                    : state.provider;
                output.underlying_model = state.model;
            } else {
                delete output.underlying_provider;
                delete output.underlying_model;
                ensureSourceOption(state.model);
                source.value = state.model;
            }
            storage.value = JSON.stringify(output);
            $(storage).trigger('change');
            $(source).trigger('change');
        };

        const onProviderChange = () => {
            drafts.set(state.provider, { ...state.options });
            choices.set(state.provider, state.model);
            state.provider = providerInput.val();
            state.model = choices.get(state.provider) || '';
            state.options = { ...(drafts.get(state.provider) || {}) };
            searchInput.val('');
            olderToggle.prop('checked', false);
            renderModels();
            renderFields();
            persist();
        };

        const onModelChange = () => {
            state.model = modelInput.val();
            (currentModel()?.hidden_options || []).forEach((key) => delete state.options[key]);
            renderFields();
            persist();
            updateStatus();
        };

        source.hidden = true;
        source.classList.add('hyva-ai-model-source');
        $(source).after(picker);
        renderProviders();

        providerInput.on('change', onProviderChange);
        modelInput.on('change', onModelChange);
        searchInput.on('input', renderModels);
        olderToggle.on('change', renderModels);
        $([overrideInput, globalInput].filter(Boolean)).on('change', updateStatus);

        const disabledObserver = new MutationObserver(syncDisabled);
        [source, storage].forEach((node) => {
            disabledObserver.observe(node, { attributes: true, attributeFilter: ['disabled'] });
        });

        renderModels();
        renderFields();
    };
});
