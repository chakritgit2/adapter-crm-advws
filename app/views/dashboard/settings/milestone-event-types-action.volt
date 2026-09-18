{% extends 'layouts/admin.volt' %}

{% block content %}
<div class="container mx-auto px-4 lg:px-8 py-8">
    <div class="max-w-2xl mx-auto">
        <div class="mb-6">
            <a href="/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/settings/employee-milestone-event-types"
                class="text-sm text-slate-500 hover:text-slate-700 inline-flex items-center gap-1 mb-4">
                <i class="fas fa-arrow-left"></i>
                {{ t('settings.milestone_event_types.back') }}
            </a>
            <h1 class="text-2xl font-bold text-slate-900 notosan">
                {% if mode == 'view' %}{{ t('settings.milestone_event_types.type_label') }}{% elseif mode == 'edit' %}{{ t('common.edit') }}{% else %}{{ t('settings.milestone_event_types.new_label') }}{% endif %} {{ t('settings.milestone_event_types.type_label') }}
            </h1>
            <p class="text-sm text-slate-500 mt-1 notosan">
                {% if mode == 'view' %}{{ t('settings.milestone_event_types.view_subtitle') }}{% elseif mode == 'edit' %}{{ t('settings.milestone_event_types.edit_subtitle') }}{% else %}{{ t('settings.milestone_event_types.create_subtitle') }}{% endif %}
            </p>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            {% if mode == 'view' %}
            <div class="mb-4">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-600 border border-slate-200">
                    <i class="fas fa-globe mr-1"></i>{{ t('settings.milestone_event_types.system_default') }}
                </span>
            </div>
            {% endif %}
            <form method="POST"
                action="{% if mode == 'edit' %}/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/settings/employee-milestone-event-types/update/{{ eventType['id'] }}{% else %}/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/settings/employee-milestone-event-types/store{% endif %}"
                class="space-y-6">

                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700 mb-1 notosan">
                        {{ t('common.name') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="name" name="name" required maxlength="100"
                        value="{% if mode == 'edit' or mode == 'view' %}{{ eventType['name'] }}{% endif %}"
                        {% if mode == 'view' %}disabled{% endif %}
                        class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm disabled:bg-slate-50 disabled:text-slate-500"
                        placeholder="{{ t('settings.milestone_event_types.name_placeholder') }}">
                </div>

                <div>
                    <label for="name_th" class="block text-sm font-medium text-slate-700 mb-1 notosan">
                        {{ t('settings.milestone_event_types.name_th') }}
                    </label>
                    <input type="text" id="name_th" name="name_th" maxlength="100"
                        value="{% if mode == 'edit' or mode == 'view' %}{{ eventType['name_th'] }}{% endif %}"
                        {% if mode == 'view' %}disabled{% endif %}
                        class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm disabled:bg-slate-50 disabled:text-slate-500"
                        placeholder="{{ t('settings.milestone_event_types.name_th_placeholder') }}">
                </div>

                <div>
                    <label for="color_tag" class="block text-sm font-medium text-slate-700 mb-1 notosan">
                        {{ t('settings.milestone_event_types.color_tag') }}
                    </label>
                    <div class="flex items-center gap-2">
                        <input type="color" id="color_tag_picker" name="color_tag_picker"
                            value="{% if (mode == 'edit' or mode == 'view') and eventType['color_tag'] %}{{ eventType['color_tag'] }}{% else %}#3b82f6{% endif %}"
                            {% if mode == 'view' %}disabled{% endif %}
                            class="w-10 h-10 p-0 border border-slate-300 rounded-lg cursor-pointer overflow-hidden disabled:cursor-not-allowed">
                        <input type="text" id="color_tag" name="color_tag" maxlength="7"
                            value="{% if mode == 'edit' or mode == 'view' %}{{ eventType['color_tag'] }}{% endif %}"
                            {% if mode == 'view' %}disabled{% endif %}
                            class="flex-1 px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm font-mono disabled:bg-slate-50 disabled:text-slate-500"
                            placeholder="#3b82f6">
                    </div>
                    <p class="text-xs text-slate-500 mt-1">{{ t('settings.milestone_event_types.color_hint') }}</p>
                </div>

                <div class="flex items-center gap-3">
                    <input type="checkbox" id="is_active" name="is_active" value="1"
                        {% if mode != 'edit' or eventType['is_active'] %}checked{% endif %}
                        {% if mode == 'view' %}disabled{% endif %}
                        class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500 disabled:bg-slate-100">
                    <label for="is_active" class="text-sm font-medium text-slate-700 notosan">
                        {{ t('settings.milestone_event_types.active') }}
                    </label>
                </div>
                <p class="text-xs text-slate-500 -mt-4 ml-7">{{ t('settings.milestone_event_types.active_hint') }}</p>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                    <a href="/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/settings/employee-milestone-event-types"
                        class="px-4 py-2 text-sm font-medium text-slate-600 bg-slate-100 rounded-lg hover:bg-slate-200 transition-colors">
                        {% if mode == 'view' %}{{ t('common.back') }}{% else %}{{ t('common.cancel') }}{% endif %}
                    </a>
                    {% if mode != 'view' %}
                    <button type="submit"
                        class="px-6 py-2 cursor-pointer text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors shadow-sm">
                        {% if mode == 'edit' %}{{ t('settings.milestone_event_types.update') }}{% else %}{{ t('settings.milestone_event_types.create') }}{% endif %}
                    </button>
                    {% endif %}
                </div>
            </form>
        </div>

        {% if mode == 'edit' and installedLanguages is defined and installedLanguages|length > 0 %}
        <div class="mt-6 bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-4 border-b border-slate-200 bg-indigo-50">
                <div class="flex items-center gap-2">
                    <i class="fas fa-language text-indigo-600"></i>
                    <h2 class="text-lg font-semibold text-slate-900 notosan">{{ t('translations.title') }}</h2>
                </div>
                <p class="text-sm text-slate-500 mt-1 notosan">{{ t('settings.milestone_event_types.translations_desc') }}</p>
            </div>
            <div class="p-6 space-y-5">
                {% for lang in installedLanguages %}
                <div class="border-b border-slate-100 pb-4 last:border-0 last:pb-0">
                    <p class="text-sm font-semibold text-slate-700 mb-3 notosan">{{ lang['language_name'] }} ({{ lang['language_code'] }})</p>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1 notosan">{{ t('common.name') }}</label>
                        <div class="flex items-center gap-2">
                            <input type="text" id="trans_{{ lang['language_code'] }}_name"
                                class="flex-1 px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"
                                placeholder="{{ eventType['name'] }}"
                                value="{{ existingTranslations[lang['language_code']]['name'] is defined ? existingTranslations[lang['language_code']]['name']['value'] : '' }}">
                            <button type="button" onclick="saveEventTypeTranslation('{{ lang['language_code'] }}', 'name')"
                                class="px-3 py-2 text-xs font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition cursor-pointer">
                                <i class="fas fa-save"></i>
                            </button>
                            {% if existingTranslations[lang['language_code']]['name'] is defined %}
                            <button type="button" onclick="deleteEventTypeTranslation({{ existingTranslations[lang['language_code']]['name']['id'] }}, '{{ lang['language_code'] }}', 'name')"
                                class="px-3 py-2 text-xs font-medium text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition cursor-pointer">
                                <i class="fas fa-times"></i>
                            </button>
                            {% endif %}
                        </div>
                        <div id="trans_feedback_{{ lang['language_code'] }}_name" class="text-xs mt-1 hidden"></div>
                    </div>
                </div>
                {% endfor %}
            </div>
        </div>
        {% endif %}

    </div>
</div>

<script>
function tenantUrl(path) {
    return window.TENANT_BASE_URL + path;
}

function saveEventTypeTranslation(lang, column) {
    const input = document.getElementById('trans_' + lang + '_' + column);
    const feedback = document.getElementById('trans_feedback_' + lang + '_' + column);
    const value = input.value.trim();

    if (!value) {
        feedback.className = 'text-xs mt-1 text-red-600';
        feedback.textContent = '<?= $this->getDI()->get('locale')->t('translations.value_empty') ?>';
        feedback.classList.remove('hidden');
        return;
    }

    fetch(tenantUrl('/dashboard/translations/store'), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            language_code: lang,
            target_table: 'milestone_event_types',
            target_column: column,
            target_id: {{ eventTypeInternalId }},
            translation_value: value
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            feedback.className = 'text-xs mt-1 text-emerald-600';
            feedback.textContent = '<?= $this->getDI()->get('locale')->t('translations.saved_short') ?>';
        } else {
            feedback.className = 'text-xs mt-1 text-red-600';
            feedback.textContent = data.message || '<?= $this->getDI()->get('locale')->t('translations.save_failed_short') ?>';
        }
        feedback.classList.remove('hidden');
        setTimeout(() => feedback.classList.add('hidden'), 3000);
    })
    .catch(() => {
        feedback.className = 'text-xs mt-1 text-red-600';
        feedback.textContent = '<?= $this->getDI()->get('locale')->t('translations.network_error') ?>';
        feedback.classList.remove('hidden');
    });
}

function deleteEventTypeTranslation(id, lang, column) {
    if (!confirm('<?= $this->getDI()->get('locale')->t('translations.confirm_delete') ?>')) return;

    const feedback = document.getElementById('trans_feedback_' + lang + '_' + column);

    fetch(tenantUrl('/dashboard/translations/delete/' + id), {
        method: 'POST'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('trans_' + lang + '_' + column).value = '';
            feedback.className = 'text-xs mt-1 text-emerald-600';
            feedback.textContent = '<?= $this->getDI()->get('locale')->t('translations.deleted_short') ?>';
        } else {
            feedback.className = 'text-xs mt-1 text-red-600';
            feedback.textContent = data.message || '<?= $this->getDI()->get('locale')->t('translations.delete_failed') ?>';
        }
        feedback.classList.remove('hidden');
        setTimeout(() => feedback.classList.add('hidden'), 3000);
    })
    .catch(() => {
        feedback.className = 'text-xs mt-1 text-red-600';
        feedback.textContent = '<?= $this->getDI()->get('locale')->t('translations.network_error') ?>';
        feedback.classList.remove('hidden');
    });
}
</script>

<script>
(function () {
    const picker = document.getElementById('color_tag_picker');
    const textInput = document.getElementById('color_tag');

    if (picker && textInput) {
        picker.addEventListener('input', function () {
            textInput.value = this.value;
        });

        textInput.addEventListener('input', function () {
            const value = this.value;
            if (/^#[0-9A-Fa-f]{6}$/.test(value)) {
                picker.value = value;
            }
        });
    }
})();
</script>
{% endblock %}
