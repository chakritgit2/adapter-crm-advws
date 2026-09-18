{% extends 'layouts/admin.volt' %}

{% block content %}
<div class="p-6 bg-slate-50 min-h-screen">
    <div class="max-w-2xl mx-auto">
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-slate-800 notosan">
                    {% if mode == 'edit' %}{{ t('company.action.edit_title') }}{% else %}{{ t('company.action.new_title') }}{% endif %}
                </h1>
                <p class="text-sm text-slate-500 mt-1">
                    {% if mode == 'edit' %}{{ t('company.action.edit_subtitle') }}{% else %}{{ t('company.action.new_subtitle') }}{% endif %}
                </p>
            </div>
            <a href="{{ companiesBase }}" class="px-4 py-2 text-slate-600 bg-slate-100 rounded-lg hover:bg-slate-200 transition-colors font-medium">
                {{ t('company.action.back') }}
            </a>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <form method="POST"
                action="{% if mode == 'edit' %}{{ companiesBase }}/update/{{ companies['id'] }}{% else %}{{ companiesBase }}/store{% endif %}"
                class="px-6 py-6 space-y-5">

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1 notosan">{{ t('company.action.name') }}</label>
                    <input type="text" id="name" name="name" required
                        value="{% if mode == 'edit' %}{{ companies['name'] }}{% endif %}"
                        class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                        placeholder="{{ t('company.action.name_placeholder') }}">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1 notosan">{{ t('company.list.slug') }}</label>
                    <input type="text" id="slug" name="slug"
                        value="{% if mode == 'edit' %}{{ companies['slug'] }}{% endif %}"
                        class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none font-mono text-sm"
                        placeholder="{{ t('company.action.slug_placeholder') }}">
                    <p class="text-xs text-slate-500 mt-1">{{ t('company.action.slug_hint') }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1 notosan">{{ t('common.status') }}</label>
                    <select id="status" name="status"
                        class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        <option value="active" {% if mode == 'edit' and companies['status'] == 'active' %}selected{% endif %}>{{ t('dashboard.active') }}</option>
                        <option value="suspended" {% if mode == 'edit' and companies['status'] == 'suspended' %}selected{% endif %}>{{ t('company.status.suspended') }}</option>
                    </select>
                </div>

                {% if mode == 'edit' and customAttributes|length > 0 %}
                <div class="pt-4 border-t border-slate-100 mt-6">
                    <h3 class="text-sm font-semibold text-slate-800 mb-4 notosan">{{ t('company.action.custom_fields') }}</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        {% for attr in customAttributes %}
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1 notosan">
                                {{ attr['name'] }}
                                {% if attr['is_required'] %}<span class="text-red-500">*</span>{% endif %}
                            </label>
                            {% if attr['field_type'] == 'boolean' %}
                                <div class="flex items-center gap-3 h-[42px]">
                                    <input type="checkbox" name="custom_attributes[{{ attr['id'] }}]"
                                        value="1"
                                        {% if companyAttributes[attr['id']] %}checked{% endif %}
                                        {% if attr['is_required'] %}required{% endif %}
                                        class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500">
                                    <span class="text-sm text-slate-600">{{ t('common.yes') }}</span>
                                </div>
                            {% elseif attr['field_type'] == 'dropdown' %}
                                <select name="custom_attributes[{{ attr['id'] }}]"
                                    {% if attr['is_required'] %}required{% endif %}
                                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm bg-white">
                                    <option value="">{{ t('company.action.select_option') }}</option>
                                    {% for choice in attr['dropdown_choice'] %}
                                    <option value="{{ choice }}" {% if companyAttributes[attr['id']] == choice %}selected{% endif %}>{{ choice }}</option>
                                    {% endfor %}
                                </select>
                            {% elseif attr['field_type'] == 'date' %}
                                <input type="date" name="custom_attributes[{{ attr['id'] }}]"
                                    value="{% if companyAttributes[attr['id']] is defined %}{{ companyAttributes[attr['id']] }}{% endif %}"
                                    {% if attr['is_required'] %}required{% endif %}
                                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm">
                            {% elseif attr['field_type'] == 'number' %}
                                <input type="number" name="custom_attributes[{{ attr['id'] }}]"
                                    value="{% if companyAttributes[attr['id']] is defined %}{{ companyAttributes[attr['id']] }}{% endif %}"
                                    {% if attr['is_required'] %}required{% endif %}
                                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm"
                                    placeholder="0">
                            {% elseif attr['field_type'] == 'email' %}
                                <input type="email" name="custom_attributes[{{ attr['id'] }}]"
                                    value="{% if companyAttributes[attr['id']] is defined %}{{ companyAttributes[attr['id']] }}{% endif %}"
                                    {% if attr['is_required'] %}required{% endif %}
                                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm"
                                    placeholder="value@example.com">
                            {% else %}
                                <input type="text" name="custom_attributes[{{ attr['id'] }}]"
                                    value="{% if companyAttributes[attr['id']] is defined %}{{ companyAttributes[attr['id']] }}{% endif %}"
                                    {% if attr['is_required'] %}required{% endif %}
                                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm"
                                    placeholder="{{ t('company.action.enter_value') }}">
                            {% endif %}
                        </div>
                        {% endfor %}
                    </div>
                </div>
                {% endif %}

                <div class="pt-4 flex justify-end border-t border-slate-100 mt-6">
                    <button type="submit" class="px-6 py-2 text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors font-medium shadow-sm">
                        {% if mode == 'edit' %}{{ t('company.action.update') }}{% else %}{{ t('company.action.create') }}{% endif %}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<input type="hidden" id="edit-mode-flag" value="{% if mode == 'edit' %}1{% else %}0{% endif %}">

<script>
    function slugify(text) {
        return text
            .toString()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .trim()
            .replace(/[^\w\s-]/g, '')
            .replace(/[\s_-]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }

    document.addEventListener('DOMContentLoaded', function () {
        const nameInput = document.getElementById('name');
        const slugInput = document.getElementById('slug');
        const isEdit = document.getElementById('edit-mode-flag').value === '1';

        if (nameInput && slugInput && !isEdit) {
            nameInput.addEventListener('input', function () {
                if (!slugInput.dataset.manuallyEdited) {
                    slugInput.value = slugify(this.value);
                }
            });

            slugInput.addEventListener('input', function () {
                if (this.value !== slugify(nameInput.value)) {
                    this.dataset.manuallyEdited = 'true';
                }
                if (this.value === '') {
                    delete this.dataset.manuallyEdited;
                }
            });
        }
    });
</script>
{% endblock %}
