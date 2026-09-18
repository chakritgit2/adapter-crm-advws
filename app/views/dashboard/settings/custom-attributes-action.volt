{% extends 'layouts/admin.volt' %}

{% block content %}
<div class="container mx-auto px-4 lg:px-8 py-8">
    <div class="max-w-2xl mx-auto">
        <div class="mb-6">
            <a href="/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/settings/custom-attributes"
                class="text-sm text-slate-500 hover:text-slate-700 inline-flex items-center gap-1 mb-4">
                <i class="fas fa-arrow-left"></i>
                {{ t('settings.custom_attributes.back') }}
            </a>
            <h1 class="text-2xl font-bold text-slate-900 notosan">
                {% if mode == 'edit' %}{{ t('common.edit') }}{% else %}{{ t('settings.custom_attributes.new_label') }}{% endif %} {{ t('settings.custom_attributes.attribute_label') }}
            </h1>
            <p class="text-sm text-slate-500 mt-1 notosan">
                {% if mode == 'edit' %}{{ t('settings.custom_attributes.edit_subtitle') }}{% else %}{{ t('settings.custom_attributes.create_subtitle') }}{% endif %}
            </p>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <form method="POST"
                action="{% if mode == 'edit' %}/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/settings/custom-attributes/update/{{ attribute['id'] }}{% else %}/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/settings/custom-attributes/store{% endif %}"
                class="space-y-6">

                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700 mb-1 notosan">
                        {{ t('settings.custom_attributes.field_name') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="name" name="name" required maxlength="100"
                        value="{% if mode == 'edit' %}{{ attribute['name'] }}{% endif %}"
                        class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm"
                        placeholder="{{ t('settings.custom_attributes.name_placeholder') }}">
                </div>

                <div>
                    <label for="attribute_entity" class="block text-sm font-medium text-slate-700 mb-1 notosan">
                        {{ t('settings.custom_attributes.applies_to') }} <span class="text-red-500">*</span>
                    </label>
                    <select id="attribute_entity" name="attribute_entity" required
                        class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm bg-white">
                        <option value="">{{ t('settings.custom_attributes.select_entity') }}</option>
                        <option value="employees" {% if mode == 'edit' and attribute['attribute_entity'] == 'employees' %}selected{% endif %}>{{ t('nav.employees') }}</option>
                        <option value="positions" {% if mode == 'edit' and attribute['attribute_entity'] == 'positions' %}selected{% endif %}>{{ t('nav.positions') }}</option>
                        <option value="companies" {% if mode == 'edit' and attribute['attribute_entity'] == 'companies' %}selected{% endif %}>{{ t('nav.companies') }}</option>
                    </select>
                    <p class="text-xs text-slate-500 mt-1">{{ t('settings.custom_attributes.applies_to_hint') }}</p>
                </div>

                <div>
                    <label for="field_type" class="block text-sm font-medium text-slate-700 mb-1 notosan">
                        {{ t('settings.custom_attributes.type') }} <span class="text-red-500">*</span>
                    </label>
                    <select id="field_type" name="field_type" required
                        class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm bg-white">
                        <option value="text" {% if mode == 'edit' and attribute['field_type'] == 'text' %}selected{% endif %}>{{ t('settings.custom_attributes.type_text') }}</option>
                        <option value="number" {% if mode == 'edit' and attribute['field_type'] == 'number' %}selected{% endif %}>{{ t('settings.custom_attributes.type_number') }}</option>
                        <option value="date" {% if mode == 'edit' and attribute['field_type'] == 'date' %}selected{% endif %}>{{ t('settings.custom_attributes.type_date') }}</option>
                        <option value="boolean" {% if mode == 'edit' and attribute['field_type'] == 'boolean' %}selected{% endif %}>{{ t('settings.custom_attributes.type_boolean') }}</option>
                        <option value="dropdown" {% if mode == 'edit' and attribute['field_type'] == 'dropdown' %}selected{% endif %}>{{ t('settings.custom_attributes.type_dropdown') }}</option>
                    </select>
                    <p class="text-xs text-slate-500 mt-1">{{ t('settings.custom_attributes.type_hint') }}</p>
                </div>

                <div id="dropdownOptionsSection" class="hidden"
                    data-initial="{% if mode == 'edit' %}{{ attribute['dropdown_choice']|e }}{% else %}[]{% endif %}">
                    <label class="block text-sm font-medium text-slate-700 mb-1 notosan">
                        {{ t('settings.custom_attributes.dropdown_options') }}
                    </label>
                    <div class="flex items-center gap-2 mb-2">
                        <input type="text" id="newOptionInput"
                            class="flex-1 px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm"
                            placeholder="{{ t('settings.custom_attributes.option_placeholder') }}">
                        <button type="button" id="addOptionBtn"
                            class="px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium">
                            <i class="fas fa-plus mr-1"></i>{{ t('settings.custom_attributes.add') }}
                        </button>
                    </div>
                    <ul id="optionsList" class="space-y-1"></ul>
                    <input type="hidden" name="dropdown_options" id="dropdown_options_input" value="">
                    <p class="text-xs text-slate-500 mt-1">{{ t('settings.custom_attributes.dropdown_hint') }}</p>
                </div>

                <div class="flex items-center gap-3">
                    <input type="checkbox" id="is_required" name="is_required" value="1"
                        {% if mode == 'edit' and attribute['is_required'] %}checked{% endif %}
                        class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500">
                    <label for="is_required" class="text-sm font-medium text-slate-700 notosan">
                        {{ t('settings.custom_attributes.required_field') }}
                    </label>
                </div>
                <p class="text-xs text-slate-500 -mt-4 ml-7">{{ t('settings.custom_attributes.required_hint') }}</p>

                <div class="flex items-center gap-3">
                    <input type="checkbox" id="show_in_list" name="show_in_list" value="1"
                        {% if mode == 'edit' and attribute['show_in_list'] %}checked{% endif %}
                        class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500">
                    <label for="show_in_list" class="text-sm font-medium text-slate-700 notosan">
                        {{ t('settings.custom_attributes.show_in_list') }}
                    </label>
                </div>
                <p class="text-xs text-slate-500 -mt-4 ml-7">{{ t('settings.custom_attributes.show_in_list_hint') }}</p>

                <div class="flex items-center gap-3">
                    <input type="checkbox" id="show_in_dashboard" name="show_in_dashboard" value="1"
                        {% if mode == 'edit' and attribute['show_in_dashboard'] %}checked{% endif %}
                        class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500">
                    <label for="show_in_dashboard" class="text-sm font-medium text-slate-700 notosan">
                        {{ t('settings.custom_attributes.show_in_dashboard') }}
                    </label>
                </div>
                <p class="text-xs text-slate-500 -mt-4 ml-7">{{ t('settings.custom_attributes.show_in_dashboard_hint') }}</p>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                    <a href="/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/settings/custom-attributes"
                        class="px-4 py-2 text-sm font-medium text-slate-600 bg-slate-100 rounded-lg hover:bg-slate-200 transition-colors">
                        {{ t('common.cancel') }}
                    </a>
                    <button type="submit"
                        class="px-6 py-2 cursor-pointer text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors shadow-sm">
                        {% if mode == 'edit' %}{{ t('settings.custom_attributes.update') }}{% else %}{{ t('settings.custom_attributes.create') }}{% endif %}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    const fieldType = document.getElementById('field_type');
    const section = document.getElementById('dropdownOptionsSection');
    const newInput = document.getElementById('newOptionInput');
    const addBtn = document.getElementById('addOptionBtn');
    const list = document.getElementById('optionsList');
    const hiddenInput = document.getElementById('dropdown_options_input');

    let options = [];
    try {
        options = JSON.parse(section.dataset.initial || '[]');
    } catch (e) {
        options = [];
    }

    function render() {
        list.innerHTML = '';
        options.forEach(function (opt, idx) {
            const li = document.createElement('li');
            li.className = 'flex items-center justify-between px-3 py-2 bg-slate-50 rounded border border-slate-200 text-sm text-slate-700';

            const span = document.createElement('span');
            span.textContent = opt;

            const btn = document.createElement('button');
            btn.type = 'button';
            btn.dataset.idx = String(idx);
            btn.className = 'text-red-500 hover:text-red-700 text-xs font-medium';
            btn.innerHTML = '<i class="fas fa-trash-alt mr-1"></i><?= $this->getDI()->get('locale')->t('settings.custom_attributes.remove') ?>';

            li.appendChild(span);
            li.appendChild(btn);
            list.appendChild(li);
        });
        hiddenInput.value = JSON.stringify(options);
    }

    function toggle() {
        if (fieldType.value === 'dropdown') {
            section.classList.remove('hidden');
        } else {
            section.classList.add('hidden');
        }
    }

    addBtn.addEventListener('click', function () {
        const val = newInput.value.trim();
        if (!val) return;
        if (options.includes(val)) {
            alert('<?= $this->getDI()->get('locale')->t('settings.custom_attributes.option_exists') ?>');
            return;
        }
        options.push(val);
        newInput.value = '';
        render();
    });

    list.addEventListener('click', function (e) {
        const btn = e.target.closest('button[data-idx]');
        if (!btn) return;
        const idx = parseInt(btn.dataset.idx, 10);
        options.splice(idx, 1);
        render();
    });

    newInput.addEventListener('keypress', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            addBtn.click();
        }
    });

    fieldType.addEventListener('change', toggle);
    toggle();
    render();
})();
</script>
{% endblock %}