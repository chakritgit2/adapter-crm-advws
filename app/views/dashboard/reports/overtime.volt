{% extends 'layouts/admin.volt' %}

{% block content %}
<div class="container mx-auto px-4 lg:px-8 py-8">
    <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <a href="/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/reports" class="text-sm text-slate-500 hover:text-slate-700 notosan mb-2 inline-block">
                <i class="fas fa-arrow-left mr-1"></i> {{ t('reports.back_to_hub') }}
            </a>
            <h1 class="text-2xl font-bold text-slate-900 notosan">{{ t('reports.overtime.title') }}</h1>
            <p class="text-sm text-slate-500 mt-1 notosan">{{ t('reports.overtime.desc') }}</p>
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

    {# Compensation split #}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 mb-8">
        <h2 class="text-lg font-semibold text-slate-900 mb-4 notosan">{{ t('reports.overtime.compensation_split') }}</h2>
        {% if compensationSplit|length > 0 %}
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-slate-500 uppercase bg-slate-50">
                    <tr>
                        <th class="px-3 py-2 rounded-l-lg notosan">{{ t('reports.col.compensation') }}</th>
                        <th class="px-3 py-2 notosan">{{ t('reports.col.status') }}</th>
                        <th class="px-3 py-2 text-right notosan">{{ t('reports.col.count') }}</th>
                        <th class="px-3 py-2 rounded-r-lg text-right notosan">{{ t('reports.col.total_min') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    {% for row in compensationSplit %}
                    <tr class="hover:bg-slate-50">
                        <td class="px-3 py-2 font-medium text-slate-900 capitalize">{{ row['compensation_type'] }}</td>
                        <td class="px-3 py-2 text-slate-600 capitalize">{{ row['status'] }}</td>
                        <td class="px-3 py-2 text-right text-slate-600">{{ row['cnt'] }}</td>
                        <td class="px-3 py-2 text-right text-slate-600">{{ row['total_minutes'] }}</td>
                    </tr>
                    {% endfor %}
                </tbody>
            </table>
        </div>
        {% else %}
        <p class="text-sm text-slate-400 notosan">{{ t('reports.no_data') }}</p>
        {% endif %}
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        {# Overtime by employee #}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <h2 class="text-lg font-semibold text-slate-900 mb-4 notosan">{{ t('reports.overtime.by_employee') }}</h2>
            {% if byEmployee|length > 0 %}
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-slate-500 uppercase bg-slate-50">
                        <tr>
                            <th class="px-3 py-2 rounded-l-lg notosan">{{ t('reports.col.employee') }}</th>
                            <th class="px-3 py-2 text-right notosan">{{ t('reports.col.requests') }}</th>
                            <th class="px-3 py-2 rounded-r-lg text-right notosan">{{ t('reports.col.total_hours') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        {% for row in byEmployee %}
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-2 font-medium text-slate-900">{{ row['first_name'] }} {{ row['last_name'] }}</td>
                            <td class="px-3 py-2 text-right text-slate-600">{{ row['requests'] }}</td>
                            <td class="px-3 py-2 text-right text-slate-600">{{ row['total_hours'] }}</td>
                        </tr>
                        {% endfor %}
                    </tbody>
                </table>
            </div>
            {% else %}
            <p class="text-sm text-slate-400 notosan">{{ t('reports.no_data') }}</p>
            {% endif %}
        </div>

        {# Overtime by department #}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <h2 class="text-lg font-semibold text-slate-900 mb-4 notosan">{{ t('reports.overtime.by_department') }}</h2>
            {% if byDepartment|length > 0 %}
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-slate-500 uppercase bg-slate-50">
                        <tr>
                            <th class="px-3 py-2 rounded-l-lg notosan">{{ t('reports.col.department') }}</th>
                            <th class="px-3 py-2 text-right notosan">{{ t('reports.col.requests') }}</th>
                            <th class="px-3 py-2 rounded-r-lg text-right notosan">{{ t('reports.col.total_min') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        {% for row in byDepartment %}
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-2 font-medium text-slate-900">{{ row['department'] }}</td>
                            <td class="px-3 py-2 text-right text-slate-600">{{ row['requests'] }}</td>
                            <td class="px-3 py-2 text-right text-slate-600">{{ row['total_minutes'] }}</td>
                        </tr>
                        {% endfor %}
                    </tbody>
                </table>
            </div>
            {% else %}
            <p class="text-sm text-slate-400 notosan">{{ t('reports.no_data') }}</p>
            {% endif %}
        </div>

        {# Overtime trend by month #}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <h2 class="text-lg font-semibold text-slate-900 mb-4 notosan">{{ t('reports.overtime.by_month') }}</h2>
            {% if byMonth|length > 0 %}
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-slate-500 uppercase bg-slate-50">
                        <tr>
                            <th class="px-3 py-2 rounded-l-lg notosan">{{ t('reports.col.month') }}</th>
                            <th class="px-3 py-2 text-right notosan">{{ t('reports.col.requests') }}</th>
                            <th class="px-3 py-2 rounded-r-lg text-right notosan">{{ t('reports.col.total_min') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        {% for row in byMonth %}
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-2 font-medium text-slate-900">{{ row['month'] }}</td>
                            <td class="px-3 py-2 text-right text-slate-600">{{ row['requests'] }}</td>
                            <td class="px-3 py-2 text-right text-slate-600">{{ row['total_minutes'] }}</td>
                        </tr>
                        {% endfor %}
                    </tbody>
                </table>
            </div>
            {% else %}
            <p class="text-sm text-slate-400 notosan">{{ t('reports.no_data') }}</p>
            {% endif %}
        </div>

        {# Pending overtime approvals aging #}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <h2 class="text-lg font-semibold text-slate-900 mb-4 notosan">{{ t('reports.overtime.pending_aging') }}</h2>
            {% if pendingAging|length > 0 %}
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-slate-500 uppercase bg-slate-50">
                        <tr>
                            <th class="px-3 py-2 rounded-l-lg notosan">{{ t('reports.col.employee') }}</th>
                            <th class="px-3 py-2 notosan">{{ t('reports.col.period') }}</th>
                            <th class="px-3 py-2 text-right notosan">{{ t('reports.col.minutes') }}</th>
                            <th class="px-3 py-2 rounded-r-lg text-right notosan">{{ t('reports.col.age_days') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        {% for row in pendingAging %}
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-2 font-medium text-slate-900">{{ row['first_name'] }} {{ row['last_name'] }}</td>
                            <td class="px-3 py-2 text-slate-600">{{ row['start_time'] }} &rarr; {{ row['end_time'] }}</td>
                            <td class="px-3 py-2 text-right text-slate-600">{{ row['worked_minutes'] }}</td>
                            <td class="px-3 py-2 text-right">
                                {% set age = row['age_days'] %}
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                    {% if age >= 7 %}bg-rose-100 text-rose-700{% elseif age >= 3 %}bg-amber-100 text-amber-700{% else %}bg-slate-100 text-slate-600{% endif %}">
                                    {{ age }}
                                </span>
                            </td>
                        </tr>
                        {% endfor %}
                    </tbody>
                </table>
            </div>
            {% else %}
            <p class="text-sm text-slate-400 notosan">{{ t('reports.overtime.no_pending') }}</p>
            {% endif %}
        </div>
    </div>
</div>
{% endblock %}
