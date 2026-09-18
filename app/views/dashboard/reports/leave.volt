{% extends 'layouts/admin.volt' %}

{% block content %}
<div class="container mx-auto px-4 lg:px-8 py-8">
    <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <a href="/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/reports" class="text-sm text-slate-500 hover:text-slate-700 notosan mb-2 inline-block">
                <i class="fas fa-arrow-left mr-1"></i> {{ t('reports.back_to_hub') }}
            </a>
            <h1 class="text-2xl font-bold text-slate-900 notosan">{{ t('reports.leave.title') }}</h1>
            <p class="text-sm text-slate-500 mt-1 notosan">{{ t('reports.leave.desc') }}</p>
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
        <p class="text-xs text-slate-400 notosan md:ml-2 md:self-center">{{ t('reports.leave.balance_year_note') }}: {{ year }}</p>
    </form>

    {# Leave utilization by type #}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 mb-8">
        <h2 class="text-lg font-semibold text-slate-900 mb-4 notosan">{{ t('reports.leave.utilization') }}</h2>
        {% if utilization|length > 0 %}
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-slate-500 uppercase bg-slate-50">
                    <tr>
                        <th class="px-3 py-2 rounded-l-lg notosan">{{ t('reports.col.leave_type') }}</th>
                        <th class="px-3 py-2 text-right notosan">{{ t('reports.col.allowance') }}</th>
                        <th class="px-3 py-2 text-right notosan">{{ t('reports.col.used') }}</th>
                        <th class="px-3 py-2 rounded-r-lg text-right notosan">{{ t('reports.col.pct_used') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    {% for row in utilization %}
                    {% set wh = row['workday_hours'] is empty ? 8 : row['workday_hours'] %}
                    {% set mpd = wh * 60 %}
                    {# Allowance breakdown #}
                    {% set aMin = row['allowance_minutes'] is empty ? 0 : row['allowance_minutes'] %}
                    {% set aDays = floor(aMin / mpd) %}
                    {% set aRem = aMin - (aDays * mpd) %}
                    {% set aHours = floor(aRem / 60) %}
                    {% set aMins = aRem - (aHours * 60) %}
                    {# Used breakdown #}
                    {% set uMin = row['used_minutes'] is empty ? 0 : row['used_minutes'] %}
                    {% set uDays = floor(uMin / mpd) %}
                    {% set uRem = uMin - (uDays * mpd) %}
                    {% set uHours = floor(uRem / 60) %}
                    {% set uMins = uRem - (uHours * 60) %}
                    <tr class="hover:bg-slate-50">
                        <td class="px-3 py-2 font-medium text-slate-900">{{ row['leave_type'] }}</td>
                        <td class="px-3 py-2 text-right text-slate-600 whitespace-nowrap">{{ aDays }}d {{ aHours }}h {{ aMins }}m</td>
                        <td class="px-3 py-2 text-right text-slate-600 whitespace-nowrap">{{ uDays }}d {{ uHours }}h {{ uMins }}m</td>
                        <td class="px-3 py-2 text-right">
                            {% set pct = row['pct_used'] is empty ? 0 : row['pct_used'] %}
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                {% if pct >= 80 %}bg-rose-100 text-rose-700{% elseif pct >= 50 %}bg-amber-100 text-amber-700{% else %}bg-emerald-100 text-emerald-700{% endif %}">
                                {{ pct }}%
                            </span>
                        </td>
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
        {# Pending approvals aging #}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <h2 class="text-lg font-semibold text-slate-900 mb-4 notosan">{{ t('reports.leave.pending_aging') }}</h2>
            {% if pendingAging|length > 0 %}
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-slate-500 uppercase bg-slate-50">
                        <tr>
                            <th class="px-3 py-2 rounded-l-lg notosan">{{ t('reports.col.employee') }}</th>
                            <th class="px-3 py-2 notosan">{{ t('reports.col.leave_type') }}</th>
                            <th class="px-3 py-2 notosan">{{ t('reports.col.period') }}</th>
                            <th class="px-3 py-2 rounded-r-lg text-right notosan">{{ t('reports.col.age_days') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        {% for row in pendingAging %}
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-2 font-medium text-slate-900">{{ row['first_name'] }} {{ row['last_name'] }}</td>
                            <td class="px-3 py-2 text-slate-600">{{ row['leave_type'] }}</td>
                            <td class="px-3 py-2 text-slate-600">{{ row['start_date'] }} &rarr; {{ row['end_date'] }}</td>
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
            <p class="text-sm text-slate-400 notosan">{{ t('reports.leave.no_pending') }}</p>
            {% endif %}
        </div>

        {# Approval stats #}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <h2 class="text-lg font-semibold text-slate-900 mb-4 notosan">{{ t('reports.leave.approval_stats') }}</h2>
            {% if approvalStats|length > 0 %}
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-slate-500 uppercase bg-slate-50">
                        <tr>
                            <th class="px-3 py-2 rounded-l-lg notosan">{{ t('reports.col.status') }}</th>
                            <th class="px-3 py-2 rounded-r-lg text-right notosan">{{ t('reports.col.count') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        {% for row in approvalStats %}
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-2 font-medium text-slate-900 capitalize">{{ row['status'] }}</td>
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

        {# Leave by department #}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <h2 class="text-lg font-semibold text-slate-900 mb-4 notosan">{{ t('reports.leave.by_department') }}</h2>
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

        {# Negative balances #}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <h2 class="text-lg font-semibold text-slate-900 mb-4 notosan">{{ t('reports.leave.negative_balances') }}</h2>
            {% if negativeBalances|length > 0 %}
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-slate-500 uppercase bg-slate-50">
                        <tr>
                            <th class="px-3 py-2 rounded-l-lg notosan">{{ t('reports.col.employee') }}</th>
                            <th class="px-3 py-2 notosan">{{ t('reports.col.leave_type') }}</th>
                            <th class="px-3 py-2 text-right notosan">{{ t('reports.col.allowance_min') }}</th>
                            <th class="px-3 py-2 text-right notosan">{{ t('reports.col.used_min') }}</th>
                            <th class="px-3 py-2 rounded-r-lg text-right notosan">{{ t('reports.col.over_min') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        {% for row in negativeBalances %}
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-2 font-medium text-slate-900">{{ row['first_name'] }} {{ row['last_name'] }}</td>
                            <td class="px-3 py-2 text-slate-600">{{ row['leave_type'] }}</td>
                            <td class="px-3 py-2 text-right text-slate-600">{{ row['allowance_minutes'] }}</td>
                            <td class="px-3 py-2 text-right text-slate-600">{{ row['used_minutes'] }}</td>
                            <td class="px-3 py-2 text-right text-rose-600 font-medium">+{{ row['over_minutes'] }}</td>
                        </tr>
                        {% endfor %}
                    </tbody>
                </table>
            </div>
            {% else %}
            <p class="text-sm text-slate-400 notosan">{{ t('reports.leave.no_negative') }}</p>
            {% endif %}
        </div>
    </div>

    {# Employees near leave limit #}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 mb-8">
        <h2 class="text-lg font-semibold text-slate-900 mb-4 notosan">{{ t('reports.leave.near_limit') }}</h2>
        {% if nearLimit|length > 0 %}
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-slate-500 uppercase bg-slate-50">
                    <tr>
                        <th class="px-3 py-2 rounded-l-lg notosan">{{ t('reports.col.employee') }}</th>
                        <th class="px-3 py-2 notosan">{{ t('reports.col.leave_type') }}</th>
                        <th class="px-3 py-2 text-right notosan">{{ t('reports.col.allowance_min') }}</th>
                        <th class="px-3 py-2 text-right notosan">{{ t('reports.col.used_min') }}</th>
                        <th class="px-3 py-2 rounded-r-lg text-right notosan">{{ t('reports.col.pct_used') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    {% for row in nearLimit %}
                    <tr class="hover:bg-slate-50">
                        <td class="px-3 py-2 font-medium text-slate-900">{{ row['first_name'] }} {{ row['last_name'] }}</td>
                        <td class="px-3 py-2 text-slate-600">{{ row['leave_type'] }}</td>
                        <td class="px-3 py-2 text-right text-slate-600">{{ row['allowance_minutes'] }}</td>
                        <td class="px-3 py-2 text-right text-slate-600">{{ row['used_minutes'] }}</td>
                        <td class="px-3 py-2 text-right text-amber-600 font-medium">{{ row['pct_used'] }}%</td>
                    </tr>
                    {% endfor %}
                </tbody>
            </table>
        </div>
        {% else %}
        <p class="text-sm text-slate-400 notosan">{{ t('reports.leave.no_near_limit') }}</p>
        {% endif %}
    </div>
</div>
{% endblock %}
