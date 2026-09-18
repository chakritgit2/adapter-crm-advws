{% extends 'layouts/admin.volt' %}

{% block content %}
<div class="container mx-auto px-4 lg:px-8 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="mb-6">
            <a href="/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/settings/overtime-policies"
                class="text-sm text-slate-500 hover:text-slate-700 inline-flex items-center gap-1 mb-4">
                <i class="fas fa-arrow-left"></i>
                {{ t('settings.overtime_policies.back') }}
            </a>
            <h1 class="text-2xl font-bold text-slate-900 notosan">
                {% if mode == 'edit' %}{{ t('common.edit') }}{% else %}{{ t('settings.overtime_policies.new_label') }}{% endif %} {{ t('settings.overtime_policies.policy_label') }}
            </h1>
            <p class="text-sm text-slate-500 mt-1 notosan">
                {% if mode == 'edit' %}{{ t('settings.overtime_policies.edit_subtitle') }}{% else %}{{ t('settings.overtime_policies.create_subtitle') }}{% endif %}
            </p>
        </div>

        <form method="POST"
            action="{% if mode == 'edit' %}/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/settings/overtime-policies/update/{{ policy['public_id'] }}{% else %}/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/settings/overtime-policies/store{% endif %}"
            class="space-y-6">

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700 mb-1 notosan">
                        {{ t('settings.overtime_policies.policy_name') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="name" name="name" required maxlength="100"
                        value="{% if mode == 'edit' %}{{ policy['name'] }}{% endif %}"
                        class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm"
                        placeholder="{{ t('settings.overtime_policies.name_placeholder') }}">
                </div>

                <div>
                    <label for="multiplier" class="block text-sm font-medium text-slate-700 mb-1 notosan">
                        {{ t('settings.overtime_policies.multiplier') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="number" id="multiplier" name="multiplier" required min="0.01" step="0.01"
                        value="{% if mode == 'edit' %}{{ policy['multiplier'] }}{% else %}1.50{% endif %}"
                        class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm"
                        placeholder="{{ t('settings.overtime_policies.multiplier_placeholder') }}">
                    <p class="text-xs text-slate-500 mt-1">{{ t('settings.overtime_policies.multiplier_hint') }}</p>
                </div>

                <div>
                    <div class="flex items-center gap-3">
                        <input type="checkbox" id="is_active" name="is_active" value="1"
                            {% if mode != 'edit' or policy['is_active'] %}checked{% endif %}
                            class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500">
                        <label for="is_active" class="text-sm font-medium text-slate-700 notosan">
                            {{ t('settings.overtime_policies.active') }}
                        </label>
                    </div>
                    <p class="text-xs text-slate-500 -mt-4 ml-7">{{ t('settings.overtime_policies.active_hint') }}</p>
                </div>
        </div>

        {% if installedLanguages is defined and installedLanguages|length > 0 %}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-4 border-b border-slate-200 bg-indigo-50">
                <div class="flex items-center gap-2">
                    <i class="fas fa-language text-indigo-600"></i>
                    <h2 class="text-lg font-semibold text-slate-900 notosan">{{ t('translations.title') }}</h2>
                </div>
                <p class="text-sm text-slate-500 mt-1 notosan">{% if mode == 'edit' %}{{ t('settings.overtime_policies.translations_desc_edit') }}{% else %}{{ t('settings.overtime_policies.translations_desc_create') }}{% endif %}</p>
            </div>
            <div class="p-6 space-y-5">
                {% for lang in installedLanguages %}
                <div class="border-b border-slate-100 pb-4 last:border-0 last:pb-0">
                    <p class="text-sm font-semibold text-slate-700 mb-3 notosan">{{ lang['language_name'] }} ({{ lang['language_code'] }})</p>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1 notosan">{{ t('settings.overtime_policies.policy_name') }}</label>
                        <div class="flex items-center gap-2">
                            {% if mode == 'edit' %}
                            <input type="text" id="trans_{{ lang['language_code'] }}_name"
                                class="flex-1 px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"
                                placeholder="{{ policy['name'] }}"
                                value="{{ existingTranslations[lang['language_code']]['name'] is defined ? existingTranslations[lang['language_code']]['name']['value'] : '' }}">
                            <button type="button" onclick="saveOvertimePolicyTranslation('{{ lang['language_code'] }}', 'name')"
                                class="px-3 py-2 text-xs font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition cursor-pointer">
                                <i class="fas fa-save"></i>
                            </button>
                            {% if existingTranslations[lang['language_code']]['name'] is defined %}
                            <button type="button" onclick="deleteOvertimePolicyTranslation({{ existingTranslations[lang['language_code']]['name']['id'] }}, '{{ lang['language_code'] }}', 'name')"
                                class="px-3 py-2 text-xs font-medium text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition cursor-pointer">
                                <i class="fas fa-times"></i>
                            </button>
                            {% endif %}
                            {% else %}
                            <input type="text" name="translations[{{ lang['language_code'] }}][name]"
                                class="flex-1 px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"
                                placeholder="{{ t('settings.overtime_policies.translate_name_placeholder') }}">
                            {% endif %}
                        </div>
                        {% if mode == 'edit' %}
                        <div id="trans_feedback_{{ lang['language_code'] }}_name" class="text-xs mt-1 hidden"></div>
                        {% endif %}
                    </div>
                </div>
                {% endfor %}
            </div>
        </div>
        {% endif %}

        <div class="flex items-center justify-end gap-3">
            <a href="/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/settings/overtime-policies"
                class="px-4 py-2 text-sm font-medium text-slate-600 bg-slate-100 rounded-lg hover:bg-slate-200 transition-colors">
                {{ t('common.cancel') }}
            </a>
            <button type="submit"
                class="px-6 py-2 cursor-pointer text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors shadow-sm">
                {% if mode == 'edit' %}{{ t('settings.overtime_policies.update') }}{% else %}{{ t('settings.overtime_policies.create') }}{% endif %}
            </button>
        </div>
        </form>

    </div>
</div>

<script>
function tenantUrl(path) {
    return window.TENANT_BASE_URL + path;
}

function saveOvertimePolicyTranslation(lang, column) {
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
            target_table: 'overtime_policies',
            target_column: column,
            target_id: {{ overtimePolicyInternalId }},
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

function deleteOvertimePolicyTranslation(id, lang, column) {
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
{% endblock %}
