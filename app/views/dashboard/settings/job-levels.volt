{% extends 'layouts/admin.volt' %}

{% block content %}
<div class="container mx-auto px-4 lg:px-8 py-8">
    <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 notosan">{{ t('settings.job_levels.title') }}</h1>
            <p class="text-sm text-slate-500 mt-1 notosan">{{ t('settings.job_levels.subtitle') }}</p>
        </div>
        <a href="/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/settings/job-levels/create"
            class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium text-sm">
            <i class="fas fa-plus"></i>
            {{ t('settings.job_levels.new') }}
        </a>
    </div>

    {% if jobLevels|length > 0 %}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-slate-200 flex items-center gap-3">
            <i class="fas fa-search text-slate-400"></i>
            <input type="text" id="simpleSearch" placeholder="{{ t('settings.job_levels.search_placeholder') }}"
                class="flex-1 text-sm outline-none bg-transparent text-slate-700 placeholder-slate-400">
        </div>
        <div class="overflow-x-auto">
            <table id="jobLevelsTable" class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-600 font-semibold border-b border-slate-200 text-xs uppercase tracking-wide">
                    <tr>
                        <th class="px-4 py-2">{{ t('settings.job_levels.code') }}</th>
                        <th class="px-4 py-2">{{ t('settings.job_levels.category') }}</th>
                        <th class="px-4 py-2">{{ t('common.name') }}</th>
                        <th class="px-4 py-2 text-center">{{ t('settings.job_levels.sort_order') }}</th>
                        <th class="px-4 py-2 text-center">{{ t('settings.job_levels.active') }}</th>
                        <th class="px-4 py-2 text-center">{{ t('settings.job_levels.approval_permissions') }}</th>
                        <th class="px-4 py-2 text-center">{{ t('settings.job_levels.scope') }}</th>
                        <th class="px-4 py-2 text-right">{{ t('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    {% for level in jobLevels %}
                    {% set isGlobal = level['company_id'] is null %}
                    <tr class="hover:bg-slate-50 transition-colors {% if isGlobal %}bg-slate-50/50{% endif %}">
                        <td class="px-4 py-2 font-medium text-slate-900 whitespace-nowrap">
                            {{ level['code'] }}
                            {% if isGlobal %}
                            <i class="fas fa-lock text-slate-400 ml-1.5 text-xs" title="{{ t('settings.job_levels.system_default') }}"></i>
                            {% endif %}
                        </td>
                        <td class="px-4 py-2 text-slate-600">{{ level['category'] }}</td>
                        <td class="px-4 py-2 text-slate-600">{{ level['name'] }}</td>
                        <td class="px-4 py-2 text-center text-slate-500 tabular-nums">{{ level['sort_order'] }}</td>
                        <td class="px-4 py-2 text-center">
                            {% if level['is_active'] %}
                            <span class="inline-flex items-center text-emerald-600"><i class="fas fa-check-circle mr-1"></i> {{ t('common.yes') }}</span>
                            {% else %}
                            <span class="inline-flex items-center text-slate-400"><i class="fas fa-minus-circle mr-1"></i> {{ t('common.no') }}</span>
                            {% endif %}
                        </td>
                        <td class="px-4 py-2 text-center">
                            {% if level['can_approve_leave'] %}
                            <span class="inline-flex items-center text-emerald-600" title="{{ t('settings.job_levels.can_approve_leave') }}">
                                <i class="fas fa-check-circle mr-1"></i> {{ t('common.yes') }}
                            </span>
                            {% else %}
                            <span class="inline-flex items-center text-slate-400" title="{{ t('settings.job_levels.can_approve_leave') }}">
                                <i class="fas fa-minus-circle mr-1"></i> {{ t('common.no') }}
                            </span>
                            {% endif %}
                        </td>
                        <td class="px-4 py-2 text-center">
                            {% if isGlobal %}
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-600 border border-slate-200">
                                <i class="fas fa-globe mr-1"></i>{{ t('settings.job_levels.system_default') }}
                            </span>
                            {% else %}
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">
                                <i class="fas fa-building mr-1"></i>{{ t('settings.job_levels.custom') }}
                            </span>
                            {% endif %}
                        </td>
                        <td class="px-4 py-2 text-end">
                            {% if isGlobal %}
                            <span class="inline-flex items-center text-slate-400 text-xs">
                                <i class="fas fa-lock mr-1"></i>{{ t('settings.job_levels.unmodifiable') }}
                            </span>
                            {% else %}
                            <div class="flex items-center justify-end gap-1.5">
                                <a href="/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/settings/job-levels/edit/{{ level['id'] }}"
                                    class="text-blue-600 hover:text-blue-800 text-xs font-medium px-1.5 py-0.5 rounded hover:bg-blue-50 transition">
                                    <i class="fas fa-edit mr-1"></i>{{ t('common.edit') }}
                                </a>
                                <form action="/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/settings/job-levels/delete/{{ level['id'] }}" method="POST"
                                    class="inline" onsubmit="return confirm('{{ t('settings.job_levels.confirm_delete') }}');">
                                    <button type="submit"
                                        class="text-red-600 cursor-pointer hover:text-red-800 text-xs font-medium px-1.5 py-0.5 rounded hover:bg-red-50 transition">
                                        <i class="fas fa-trash-alt mr-1"></i>{{ t('common.delete') }}
                                    </button>
                                </form>
                            </div>
                            {% endif %}
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
            <i class="fas fa-layer-group text-slate-400 text-2xl"></i>
        </div>
        <h3 class="text-lg font-semibold text-slate-900 mb-1 notosan">{{ t('settings.job_levels.no_levels') }}</h3>
        <p class="text-sm text-slate-500 mb-4 notosan">{{ t('settings.job_levels.no_levels_desc') }}</p>
        <a href="/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/settings/job-levels/create"
            class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium text-sm">
            <i class="fas fa-plus"></i>
            {{ t('settings.job_levels.create_first') }}
        </a>
    </div>
    {% endif %}
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('simpleSearch');
        const table = document.getElementById('jobLevelsTable');
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
