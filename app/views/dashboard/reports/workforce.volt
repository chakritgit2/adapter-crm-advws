{% extends 'layouts/admin.volt' %}

{% block content %}
<div class="container mx-auto px-4 lg:px-8 py-8">
    <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <a href="/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/reports" class="text-sm text-slate-500 hover:text-slate-700 notosan mb-2 inline-block">
                <i class="fas fa-arrow-left mr-1"></i> {{ t('reports.back_to_hub') }}
            </a>
            <h1 class="text-2xl font-bold text-slate-900 notosan">{{ t('reports.workforce.title') }}</h1>
            <p class="text-sm text-slate-500 mt-1 notosan">{{ t('reports.workforce.desc') }}</p>
        </div>
    </div>

    {# Date filter bar #}
    <form method="get" class="mb-6 bg-white rounded-xl border border-slate-200 shadow-sm p-4 flex flex-col md:flex-row md:items-end gap-3">
        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1 notosan">{{ t('reports.filter.from') }}</label>
            <input type="date" name="from" value="{{ from }}" class="text-sm border border-slate-300 rounded-lg px-3 py-2 bg-white outline-none">
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1 notosan">{{ t('reports.filter.to') }}</label>
            <input type="date" name="to" value="{{ to }}" class="text-sm border border-slate-300 rounded-lg px-3 py-2 bg-white outline-none">
        </div>
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-medium notosan">
            <i class="fas fa-filter mr-1"></i> {{ t('reports.filter.apply') }}
        </button>
    </form>

    {# Position fill KPIs #}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <p class="text-xs text-slate-500 font-medium notosan">{{ t('reports.workforce.total_positions') }}</p>
            <p class="text-2xl font-bold text-slate-900 mt-1">{{ totalPositions }}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <p class="text-xs text-slate-500 font-medium notosan">{{ t('reports.workforce.filled_positions') }}</p>
            <p class="text-2xl font-bold text-emerald-600 mt-1">{{ filledPositions }}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <p class="text-xs text-slate-500 font-medium notosan">{{ t('reports.workforce.vacant_positions') }}</p>
            <p class="text-2xl font-bold text-rose-600 mt-1">{{ vacantPositions }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        {# Headcount by department #}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <h2 class="text-lg font-semibold text-slate-900 mb-4 notosan">{{ t('reports.workforce.by_department') }}</h2>
            {% if byDepartment|length > 0 %}
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-slate-500 uppercase bg-slate-50">
                        <tr>
                            <th class="px-3 py-2 rounded-l-lg notosan">{{ t('reports.col.department') }}</th>
                            <th class="px-3 py-2 rounded-r-lg text-right notosan">{{ t('reports.col.headcount') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        {% for row in byDepartment %}
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-2 font-medium text-slate-900">{{ row['department'] }}</td>
                            <td class="px-3 py-2 text-right text-slate-600">{{ row['headcount'] }}</td>
                        </tr>
                        {% endfor %}
                    </tbody>
                </table>
            </div>
            {% else %}
            <p class="text-sm text-slate-400 notosan">{{ t('reports.no_data') }}</p>
            {% endif %}
        </div>

        {# Headcount by employment type #}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <h2 class="text-lg font-semibold text-slate-900 mb-4 notosan">{{ t('reports.workforce.by_employment_type') }}</h2>
            {% if byEmploymentType|length > 0 %}
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-slate-500 uppercase bg-slate-50">
                        <tr>
                            <th class="px-3 py-2 rounded-l-lg notosan">{{ t('reports.col.employment_type') }}</th>
                            <th class="px-3 py-2 rounded-r-lg text-right notosan">{{ t('reports.col.headcount') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        {% for row in byEmploymentType %}
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-2 font-medium text-slate-900">{{ row['employment_type'] }}</td>
                            <td class="px-3 py-2 text-right text-slate-600">{{ row['headcount'] }}</td>
                        </tr>
                        {% endfor %}
                    </tbody>
                </table>
            </div>
            {% else %}
            <p class="text-sm text-slate-400 notosan">{{ t('reports.no_data') }}</p>
            {% endif %}
        </div>

        {# Headcount by job level #}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <h2 class="text-lg font-semibold text-slate-900 mb-4 notosan">{{ t('reports.workforce.by_job_level') }}</h2>
            {% if byJobLevel|length > 0 %}
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-slate-500 uppercase bg-slate-50">
                        <tr>
                            <th class="px-3 py-2 rounded-l-lg notosan">{{ t('reports.col.job_level') }}</th>
                            <th class="px-3 py-2 rounded-r-lg text-right notosan">{{ t('reports.col.headcount') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        {% for row in byJobLevel %}
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-2 font-medium text-slate-900">{{ row['job_level'] }}</td>
                            <td class="px-3 py-2 text-right text-slate-600">{{ row['headcount'] }}</td>
                        </tr>
                        {% endfor %}
                    </tbody>
                </table>
            </div>
            {% else %}
            <p class="text-sm text-slate-400 notosan">{{ t('reports.no_data') }}</p>
            {% endif %}
        </div>

        {# Hires vs exits per month #}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <h2 class="text-lg font-semibold text-slate-900 mb-4 notosan">{{ t('reports.workforce.hires_exits') }}</h2>
            {% if hiresExits|length > 0 %}
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-slate-500 uppercase bg-slate-50">
                        <tr>
                            <th class="px-3 py-2 rounded-l-lg notosan">{{ t('reports.col.month') }}</th>
                            <th class="px-3 py-2 text-right notosan">{{ t('reports.col.hires') }}</th>
                            <th class="px-3 py-2 rounded-r-lg text-right notosan">{{ t('reports.col.exits') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        {% for row in hiresExits %}
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-2 font-medium text-slate-900">{{ row['month'] }}</td>
                            <td class="px-3 py-2 text-right text-emerald-600">+{{ row['hires'] }}</td>
                            <td class="px-3 py-2 text-right text-rose-600">-{{ row['exits'] }}</td>
                        </tr>
                        {% endfor %}
                    </tbody>
                </table>
            </div>
            {% else %}
            <p class="text-sm text-slate-400 notosan">{{ t('reports.no_data') }}</p>
            {% endif %}
        </div>
    </div>
</div>
{% endblock %}
