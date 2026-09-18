{% extends 'layouts/admin.volt' %}

{% block content %}
<div class="container mx-auto px-4 lg:px-8 py-8">
    <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 notosan">{{ t('settings.overtime_policies.title') }}</h1>
            <p class="text-sm text-slate-500 mt-1 notosan">{{ t('settings.overtime_policies.subtitle') }}</p>
        </div>
        <a href="/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/settings/overtime-policies/create"
            class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium text-sm">
            <i class="fas fa-plus"></i>
            {{ t('settings.overtime_policies.new') }}
        </a>
    </div>

    {% if policies|length > 0 %}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-slate-200 flex items-center gap-3">
            <i class="fas fa-search text-slate-400"></i>
            <input type="text" id="simpleSearch" placeholder="{{ t('settings.overtime_policies.search_placeholder') }}"
                class="flex-1 text-sm outline-none bg-transparent text-slate-700 placeholder-slate-400">
        </div>
        <div class="overflow-x-auto">
            <table id="overtimePoliciesTable" class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-700 font-semibold border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-3">{{ t('settings.overtime_policies.name') }}</th>
                        <th class="px-6 py-3">{{ t('settings.overtime_policies.multiplier') }}</th>
                        <th class="px-6 py-3 text-center">{{ t('settings.overtime_policies.active') }}</th>
                        <th class="px-6 py-3 text-right">{{ t('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    {% for policy in policies %}
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4 font-medium text-slate-900">{{ policy['name'] }}</td>
                        <td class="px-6 py-4 text-slate-600">{{ policy['multiplier'] }}x</td>
                        <td class="px-6 py-4 text-center">
                            {% if policy['is_active'] %}
                            <span class="inline-flex items-center text-emerald-600"><i class="fas fa-check-circle mr-1"></i> {{ t('common.yes') }}</span>
                            {% else %}
                            <span class="inline-flex items-center text-slate-400"><i class="fas fa-minus-circle mr-1"></i> {{ t('common.no') }}</span>
                            {% endif %}
                        </td>
                        <td class="px-6 py-4 text-end">
                            <div class="flex items-center justify-end gap-2">
                                <a href="/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/settings/overtime-policies/edit/{{ policy['public_id'] }}"
                                    class="text-blue-600 hover:text-blue-800 text-sm font-medium px-2 py-1 rounded hover:bg-blue-50 transition">
                                    <i class="fas fa-edit mr-1"></i>{{ t('common.edit') }}
                                </a>
                                <form action="/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/settings/overtime-policies/delete/{{ policy['public_id'] }}" method="POST"
                                    class="inline" onsubmit="return confirm('{{ t('settings.overtime_policies.confirm_delete') }}');">
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
            <i class="fas fa-clock text-slate-400 text-2xl"></i>
        </div>
        <h3 class="text-lg font-semibold text-slate-900 mb-1 notosan">{{ t('settings.overtime_policies.no_policies') }}</h3>
        <p class="text-sm text-slate-500 mb-4 notosan">{{ t('settings.overtime_policies.no_policies_desc') }}</p>
        <a href="/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/settings/overtime-policies/create"
            class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium text-sm">
            <i class="fas fa-plus"></i>
            {{ t('settings.overtime_policies.create_first') }}
        </a>
    </div>
    {% endif %}
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('simpleSearch');
        const table = document.getElementById('overtimePoliciesTable');
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
