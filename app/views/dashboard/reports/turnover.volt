{% extends 'layouts/admin.volt' %}

{% block content %}
<div class="container mx-auto px-4 lg:px-8 py-8">
    <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <a href="/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/reports" class="text-sm text-slate-500 hover:text-slate-700 notosan mb-2 inline-block">
                <i class="fas fa-arrow-left mr-1"></i> {{ t('reports.back_to_hub') }}
            </a>
            <h1 class="text-2xl font-bold text-slate-900 notosan">{{ t('reports.turnover.title') }}</h1>
            <p class="text-sm text-slate-500 mt-1 notosan">{{ t('reports.turnover.desc') }}</p>
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

    {# Turnover KPIs #}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <p class="text-xs text-slate-500 font-medium notosan">{{ t('reports.turnover.current_headcount') }}</p>
            <p class="text-2xl font-bold text-slate-900 mt-1">{{ totalEmployees }}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <p class="text-xs text-slate-500 font-medium notosan">{{ t('reports.turnover.exits_in_range') }}</p>
            <p class="text-2xl font-bold text-rose-600 mt-1">{{ exits }}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <p class="text-xs text-slate-500 font-medium notosan">{{ t('reports.turnover.turnover_rate') }}</p>
            <p class="text-2xl font-bold text-amber-600 mt-1">{{ turnoverRate }}%</p>
        </div>
    </div>

    {# Average tenure #}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 mb-8">
        <p class="text-xs text-slate-500 font-medium notosan">{{ t('reports.turnover.avg_tenure') }}</p>
        <p class="text-2xl font-bold text-slate-900 mt-1">{{ avgTenure }} <span class="text-sm font-normal text-slate-400 notosan">{{ t('reports.turnover.years') }}</span></p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        {# Turnover by department #}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <h2 class="text-lg font-semibold text-slate-900 mb-4 notosan">{{ t('reports.turnover.by_department') }}</h2>
            {% if turnoverByDept|length > 0 %}
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-slate-500 uppercase bg-slate-50">
                        <tr>
                            <th class="px-3 py-2 rounded-l-lg notosan">{{ t('reports.col.department') }}</th>
                            <th class="px-3 py-2 rounded-r-lg text-right notosan">{{ t('reports.col.exits') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        {% for row in turnoverByDept %}
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-2 font-medium text-slate-900">{{ row['department'] }}</td>
                            <td class="px-3 py-2 text-right text-rose-600">{{ row['exits'] }}</td>
                        </tr>
                        {% endfor %}
                    </tbody>
                </table>
            </div>
            {% else %}
            <p class="text-sm text-slate-400 notosan">{{ t('reports.no_data') }}</p>
            {% endif %}
        </div>

        {# Internal mobility #}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <h2 class="text-lg font-semibold text-slate-900 mb-4 notosan">{{ t('reports.turnover.mobility') }}</h2>
            {% if mobility|length > 0 %}
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-slate-500 uppercase bg-slate-50">
                        <tr>
                            <th class="px-3 py-2 rounded-l-lg notosan">{{ t('reports.col.event_type') }}</th>
                            <th class="px-3 py-2 rounded-r-lg text-right notosan">{{ t('reports.col.count') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        {% for row in mobility %}
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-2 font-medium text-slate-900">{{ row['event_type'] }}</td>
                            <td class="px-3 py-2 text-right text-slate-600">{{ row['cnt'] }}</td>
                        </tr>
                        {% endfor %}
                    </tbody>
                </table>
            </div>
            {% else %}
            <p class="text-sm text-slate-400 notosan">{{ t('reports.no_data') }}</p>
            {% endif %}
        </div>

        {# Upcoming anniversaries #}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <h2 class="text-lg font-semibold text-slate-900 mb-4 notosan">{{ t('reports.turnover.anniversaries') }}</h2>
            {% if anniversaries|length > 0 %}
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-slate-500 uppercase bg-slate-50">
                        <tr>
                            <th class="px-3 py-2 rounded-l-lg notosan">{{ t('reports.col.employee') }}</th>
                            <th class="px-3 py-2 notosan">{{ t('reports.col.hire_date') }}</th>
                            <th class="px-3 py-2 rounded-r-lg text-right notosan">{{ t('reports.col.years') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        {% for row in anniversaries %}
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-2 font-medium text-slate-900">{{ row['first_name'] }} {{ row['last_name'] }}</td>
                            <td class="px-3 py-2 text-slate-600">{{ row['hire_date'] }}</td>
                            <td class="px-3 py-2 text-right text-slate-600">{{ row['years'] }}</td>
                        </tr>
                        {% endfor %}
                    </tbody>
                </table>
            </div>
            {% else %}
            <p class="text-sm text-slate-400 notosan">{{ t('reports.turnover.no_anniversaries') }}</p>
            {% endif %}
        </div>

        {# Early-tenure attrition #}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <h2 class="text-lg font-semibold text-slate-900 mb-4 notosan">{{ t('reports.turnover.early_attrition') }}</h2>
            {% if earlyAttrition|length > 0 %}
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-slate-500 uppercase bg-slate-50">
                        <tr>
                            <th class="px-3 py-2 rounded-l-lg notosan">{{ t('reports.col.employee') }}</th>
                            <th class="px-3 py-2 notosan">{{ t('reports.col.exit_type') }}</th>
                            <th class="px-3 py-2 notosan">{{ t('reports.col.exit_date') }}</th>
                            <th class="px-3 py-2 rounded-r-lg text-right notosan">{{ t('reports.col.tenure_months') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        {% for row in earlyAttrition %}
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-2 font-medium text-slate-900">{{ row['first_name'] }} {{ row['last_name'] }}</td>
                            <td class="px-3 py-2 text-slate-600">{{ row['exit_type'] }}</td>
                            <td class="px-3 py-2 text-slate-600">{{ row['exit_date'] }}</td>
                            <td class="px-3 py-2 text-right text-rose-600">{{ row['tenure_months'] }}</td>
                        </tr>
                        {% endfor %}
                    </tbody>
                </table>
            </div>
            {% else %}
            <p class="text-sm text-slate-400 notosan">{{ t('reports.turnover.no_early_attrition') }}</p>
            {% endif %}
        </div>
    </div>
</div>
{% endblock %}
