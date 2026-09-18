{% extends 'layouts/admin.volt' %}

{% block content %}
<div class="container mx-auto px-4 lg:px-8 py-8">
    <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 notosan">{{ t('settings.custom_attributes.title') }}</h1>
            <p class="text-sm text-slate-500 mt-1 notosan">{{ t('settings.custom_attributes.subtitle') }}</p>
        </div>
        <a href="/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/settings/custom-attributes/create"
            class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium text-sm">
            <i class="fas fa-plus"></i>
            {{ t('settings.custom_attributes.new') }}
        </a>
    </div>
    
    <div class="mb-4 flex flex-wrap gap-2">
        <a href="/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/settings/custom-attributes"
            class="filter-pill px-3 py-1 text-sm font-medium rounded-full border transition
            {% if entityFilter == '' %}bg-blue-100 text-blue-700 border-blue-200 hover:bg-blue-200{% else %}bg-slate-100 text-slate-600 border-slate-200 hover:bg-slate-200{% endif %}">
            {{ t('common.all') }}
        </a>
        <a href="/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/settings/custom-attributes?entity=companies"
            class="filter-pill px-3 py-1 text-sm font-medium rounded-full border transition
            {% if entityFilter == 'companies' %}bg-blue-100 text-blue-700 border-blue-200 hover:bg-blue-200{% else %}bg-slate-100 text-slate-600 border-slate-200 hover:bg-slate-200{% endif %}">
            {{ t('nav.companies') }}
        </a>
        <a href="/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/settings/custom-attributes?entity=positions"
            class="filter-pill px-3 py-1 text-sm font-medium rounded-full border transition
            {% if entityFilter == 'positions' %}bg-blue-100 text-blue-700 border-blue-200 hover:bg-blue-200{% else %}bg-slate-100 text-slate-600 border-slate-200 hover:bg-slate-200{% endif %}">
            {{ t('nav.positions') }}
        </a>
        <a href="/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/settings/custom-attributes?entity=employees"
            class="filter-pill px-3 py-1 text-sm font-medium rounded-full border transition
            {% if entityFilter == 'employees' %}bg-blue-100 text-blue-700 border-blue-200 hover:bg-blue-200{% else %}bg-slate-100 text-slate-600 border-slate-200 hover:bg-slate-200{% endif %}">
            {{ t('nav.employees') }}
        </a>
    </div>

    {% if attributes|length > 0 %}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-slate-200 flex items-center gap-3">
            <i class="fas fa-search text-slate-400"></i>
            <input type="text" id="simpleSearch" placeholder="{{ t('settings.custom_attributes.search_placeholder') }}"
                class="flex-1 text-sm outline-none bg-transparent text-slate-700 placeholder-slate-400">
        </div>
        <div class="overflow-x-auto">
            <table id="customAttributesTable" class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-700 font-semibold border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-3">{{ t('common.name') }}</th>
                        <th class="px-6 py-3 text-center">{{ t('settings.custom_attributes.applies_to') }}</th>
                        <th class="px-6 py-3 text-center">{{ t('settings.custom_attributes.type') }}</th>
                        <th class="px-6 py-3 text-center">{{ t('common.required') }}</th>
                        <th class="px-6 py-3 text-center">{{ t('settings.custom_attributes.show_in_list') }}</th>
                        <th class="px-6 py-3 text-center">{{ t('settings.custom_attributes.show_in_dashboard') }}</th>
                        <th class="px-6 py-3 text-right">{{ t('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    {% for attr in attributes %}
                    <tr data-entity="{{ attr['attribute_entity'] }}" class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4 font-medium text-slate-900">{{ attr['name'] }}</td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-medium
                                {% if attr['attribute_entity'] == 'employees' %}bg-emerald-100 text-emerald-700{% elseif attr['attribute_entity'] == 'positions' %}bg-blue-100 text-blue-700{% elseif attr['attribute_entity'] == 'companies' %}bg-purple-100 text-purple-700{% else %}bg-slate-100 text-slate-600{% endif %}">
                                {{ attr['attribute_entity']|capitalize }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-slate-600 text-center">{{ attr['field_type']|capitalize }}</td>
                        <td class="px-6 py-4 text-center">
                            {% if attr['is_required'] %}
                            <span class="inline-flex items-center text-amber-600"><i class="fas fa-check-circle mr-1"></i> {{ t('common.yes') }}</span>
                            {% else %}
                            <span class="inline-flex items-center text-slate-400"><i class="fas fa-minus-circle mr-1"></i> {{ t('common.no') }}</span>
                            {% endif %}
                        </td>
                        <td class="px-6 py-4 text-center">
                            {% if attr['show_in_list'] %}
                            <span class="inline-flex items-center text-emerald-600"><i class="fas fa-eye mr-1"></i> {{ t('common.yes') }}</span>
                            {% else %}
                            <span class="inline-flex items-center text-slate-400"><i class="fas fa-eye-slash mr-1"></i> {{ t('common.no') }}</span>
                            {% endif %}
                        </td>
                        <td class="px-6 py-4 text-center">
                            {% if attr['show_in_dashboard'] %}
                            <span class="inline-flex items-center text-emerald-600"><i class="fas fa-eye mr-1"></i> {{ t('common.yes') }}</span>
                            {% else %}
                            <span class="inline-flex items-center text-slate-400"><i class="fas fa-eye-slash mr-1"></i> {{ t('common.no') }}</span>
                            {% endif %}
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-end gap-2">
                                <a href="/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/settings/custom-attributes/edit/{{ attr['id'] }}"
                                    class="text-blue-600 hover:text-blue-800 text-sm font-medium px-2 py-1 rounded hover:bg-blue-50 transition">
                                    <i class="fas fa-edit mr-1"></i>{{ t('common.edit') }}
                                </a>
                                <form action="/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/settings/custom-attributes/delete/{{ attr['id'] }}" method="POST"
                                    class="inline" onsubmit="return confirm('{{ t('settings.custom_attributes.confirm_delete') }}');">
                                    <button type="submit"
                                        class="text-red-600 cursor-pointer hover:text-red-800 text-sm font-medium px-2 py-1 rounded hover:bg-red-50 transition">
                                        <i class="fas fa-trash-alt mr-1"></i>{{ t('common.delete') }}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    {% endfor %}
                </tbody>
            </table>
        </div>
    </div>
    {% else %}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-12 text-center">
        <div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-sliders-h text-slate-400 text-2xl"></i>
        </div>
        <h3 class="text-lg font-semibold text-slate-900 mb-1 notosan">{{ t('settings.custom_attributes.no_attributes') }}</h3>
        <p class="text-sm text-slate-500 mb-4 notosan">{{ t('settings.custom_attributes.no_attributes_desc') }}</p>
        <a href="/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/settings/custom-attributes/create"
            class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium text-sm">
            <i class="fas fa-plus"></i>
            {{ t('settings.custom_attributes.create_first') }}
        </a>
    </div>
    {% endif %}
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('simpleSearch');
        const table = document.getElementById('customAttributesTable');
        if (!searchInput || !table) return;

        const rows = table.querySelectorAll('tbody tr');
        searchInput.addEventListener('keyup', function () {
            const term = this.value.toLowerCase();
            rows.forEach(function (row) {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(term) ? '' : 'none';
            });
        });
    });
</script>
{% endblock %}