{% extends 'layouts/admin.volt' %}

{% block content %}
<div class="container mx-auto px-4 lg:px-8 py-8">
    <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 notosan">{{ t('nav.companies') }}</h1>
            <p class="text-sm text-slate-500 mt-1 notosan">{{ t('company.list.subtitle') }}</p>
        </div>
        {% if isSuperAdmin %}
        <a href="/dashboard/companies/create"
            class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium text-sm">
            <i class="fas fa-plus"></i>
            {{ t('company.list.new') }}
        </a>
        {% endif %}
    </div>

    {% if companies|length > 0 %}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-slate-200 flex items-center gap-3">
            <i class="fas fa-search text-slate-400"></i>
            <input type="text" id="companySearch" placeholder="{{ t('company.list.search_placeholder') }}"
                class="flex-1 text-sm outline-none bg-transparent text-slate-700 placeholder-slate-400">
        </div>
        <div class="overflow-x-auto">
            <table id="companiesTable" class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-700 font-semibold border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-3">{{ t('common.name') }}</th>
                        <th class="px-6 py-3">{{ t('company.list.slug') }}</th>
                        <th class="px-6 py-3 text-center">{{ t('common.status') }}</th>
                        <th class="px-6 py-3 text-center">{{ t('dashboard.employees') }}</th>
                        {% for attr in customAttributes %}
                        <th class="px-6 py-3">{{ attr['name'] }}</th>
                        {% endfor %}
                        {% if isSuperAdmin %}
                        <th class="px-6 py-3 text-right">{{ t('common.actions') }}</th>
                        {% endif %}
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    {% for company in companies %}
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4 font-medium text-slate-900">
                            {{ company['name'] }}
                        </td>
                        <td class="px-6 py-4 text-slate-600 font-mono text-xs">
                            {{ company['slug'] }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            {% if company['status'] == 'active' %}
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                {{ t('dashboard.active') }}
                            </span>
                            {% else %}
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                {{ t('company.status.suspended') }}
                            </span>
                            {% endif %}
                        </td>
                        <td class="px-6 py-4 text-center">
                            <a href="/{{ company['tenant_slug'] | default('default') }}/{{ company['slug'] }}/dashboard" class="text-blue-600 hover:text-blue-800 font-medium">
                                {{ employeeCounts[company['id']] | default(0) }}
                            </a>
                        </td>
                        {% for attr in customAttributes %}
                        <td class="px-6 py-4 text-slate-600">
                            {% if companyAttributes[company['id']][attr['id']] is defined %}
                                {{ companyAttributes[company['id']][attr['id']] }}
                            {% else %}
                                <span class="text-slate-400 italic">—</span>
                            {% endif %}
                        </td>
                        {% endfor %}
                        {% if isSuperAdmin %}
                        <td class="px-6 py-4 text-right">
                            <a href="/dashboard/companies/edit/{{ company['id'] }}"
                                class="text-blue-600 hover:text-blue-800 text-sm font-medium px-2 py-1 rounded hover:bg-blue-50 transition mr-2">
                                <i class="fas fa-edit mr-1"></i>{{ t('common.edit') }}
                            </a>
                            <form action="/dashboard/companies/delete/{{ company['id'] }}" method="POST" class="inline"
                                onsubmit="return confirm('<?= $this->getDI()->get('locale')->t('company.list.confirm_delete') ?>');">
                                <button type="submit"
                                    class="text-red-600 hover:text-red-800 text-sm font-medium px-2 py-1 rounded hover:bg-red-50 transition">
                                    <i class="fas fa-trash-alt mr-1"></i>{{ t('common.delete') }}
                                </button>
                            </form>
                        </td>
                        {% endif %}
                    </tr>
                    {% endfor %}
                </tbody>
            </table>
        </div>
    </div>
    {% else %}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-12 text-center">
        <div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-building text-slate-400 text-2xl"></i>
        </div>
        <h3 class="text-lg font-semibold text-slate-900 mb-1 notosan">{{ t('company.list.no_companies') }}</h3>
        <p class="text-sm text-slate-500 mb-4 notosan">{{ t('company.list.no_companies_hint') }}</p>
        {% if isSuperAdmin %}
        <a href="/dashboard/companies/create"
            class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium text-sm">
            <i class="fas fa-plus"></i>
            {{ t('company.list.add') }}
        </a>
        {% endif %}
    </div>
    {% endif %}
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('companySearch');
        const table = document.getElementById('companiesTable');
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
