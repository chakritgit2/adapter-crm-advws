{% extends 'layouts/admin.volt' %}

{% block content %}
<div class="container mx-auto px-4 lg:px-8 py-8">
    <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 notosan">{{ t('settings.languages.title') }}</h1>
            <p class="text-sm text-slate-500 mt-1 notosan">{{ t('settings.languages.subtitle') }}</p>
        </div>
        <a href="/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/settings/languages/create"
            class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium text-sm">
            <i class="fas fa-plus"></i>
            {{ t('settings.languages.add') }}
        </a>
    </div>

    {% if languages|length > 0 %}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-slate-200 flex items-center gap-3">
            <i class="fas fa-search text-slate-400"></i>
            <input type="text" id="simpleSearch" placeholder="{{ t('settings.languages.search_placeholder') }}"
                class="flex-1 text-sm outline-none bg-transparent text-slate-700 placeholder-slate-400">
        </div>
        <div class="overflow-x-auto">
            <table id="languagesTable" class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-700 font-semibold border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-3">{{ t('settings.languages.code') }}</th>
                        <th class="px-6 py-3">{{ t('settings.languages.name') }}</th>
                        <th class="px-6 py-3 text-center">{{ t('settings.languages.active') }}</th>
                        <th class="px-6 py-3">{{ t('settings.languages.installed_column') }}</th>
                        <th class="px-6 py-3 text-right">{{ t('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    {% for lang in languages %}
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4 font-mono font-medium text-slate-900">{{ lang['language_code'] }}</td>
                        <td class="px-6 py-4 font-medium text-slate-900 notosan">{{ lang['language_name'] }}</td>
                        <td class="px-6 py-4 text-center">
                            {% if lang['is_active'] %}
                            <span class="inline-flex items-center text-emerald-600"><i class="fas fa-check-circle mr-1"></i> {{ t('common.yes') }}</span>
                            {% else %}
                            <span class="inline-flex items-center text-slate-400"><i class="fas fa-minus-circle mr-1"></i> {{ t('common.no') }}</span>
                            {% endif %}
                        </td>
                        <td class="px-6 py-4 text-slate-500 text-xs">{{ lang['created_at'] }}</td>
                        <td class="px-6 py-4 text-end">
                            <form action="/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/settings/languages/delete/{{ lang['id'] }}" method="POST"
                                class="inline" onsubmit="return confirm('{{ t('settings.languages.confirm_remove') }}');">
                                <button type="submit"
                                    class="text-red-600 cursor-pointer hover:text-red-800 text-sm font-medium px-2 py-1 rounded hover:bg-red-50 transition">
                                    <i class="fas fa-trash-alt mr-1"></i>{{ t('settings.languages.remove') }}
                                </button>
                            </form>
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
            <i class="fas fa-language text-slate-400 text-2xl"></i>
        </div>
        <h3 class="text-lg font-semibold text-slate-900 mb-1 notosan">{{ t('settings.languages.no_languages') }}</h3>
        <p class="text-sm text-slate-500 mb-4 notosan">{{ t('settings.languages.no_languages_desc') }}</p>
        <a href="/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/settings/languages/create"
            class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium text-sm">
            <i class="fas fa-plus"></i>
            {{ t('settings.languages.install_first') }}
        </a>
    </div>
    {% endif %}
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('simpleSearch');
        const table = document.getElementById('languagesTable');
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
