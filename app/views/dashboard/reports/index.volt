{% extends 'layouts/admin.volt' %}

{% block content %}
<div class="container mx-auto px-4 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-slate-900 notosan">{{ t('reports.title') }}</h1>
        <p class="text-sm text-slate-500 mt-1 notosan">{{ t('reports.subtitle') }}</p>
    </div>

    {# ---------- Summary KPIs ---------- #}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-10">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center">
                    <i class="fas fa-users text-blue-600"></i>
                </div>
                <div>
                    <p class="text-xs text-slate-500 font-medium notosan">{{ t('reports.kpi.total_employees') }}</p>
                    <p class="text-xl font-bold text-slate-900">{{ totalEmployees }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-purple-50 flex items-center justify-center">
                    <i class="fas fa-briefcase text-purple-600"></i>
                </div>
                <div>
                    <p class="text-xs text-slate-500 font-medium notosan">{{ t('reports.kpi.total_positions') }}</p>
                    <p class="text-xl font-bold text-slate-900">{{ totalPositions }}</p>
                    <p class="text-xs text-slate-400 notosan">{{ vacantPositions }} {{ t('reports.kpi.vacant') }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-amber-50 flex items-center justify-center">
                    <i class="fas fa-clipboard-list text-amber-600"></i>
                </div>
                <div>
                    <p class="text-xs text-slate-500 font-medium notosan">{{ t('reports.kpi.pending_approvals') }}</p>
                    <p class="text-xl font-bold text-slate-900">{{ pendingLeave + pendingOvertime }}</p>
                    <p class="text-xs text-slate-400 notosan">{{ pendingLeave }} {{ t('reports.kpi.leave') }} &middot; {{ pendingOvertime }} {{ t('reports.kpi.overtime') }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-emerald-50 flex items-center justify-center">
                    <i class="fas fa-arrows-rotate text-emerald-600"></i>
                </div>
                <div>
                    <p class="text-xs text-slate-500 font-medium notosan">{{ t('reports.kpi.hires_exits') }}</p>
                    <p class="text-xl font-bold text-slate-900">
                        <span class="text-emerald-600">+{{ hires }}</span>
                        <span class="text-slate-300 mx-1">/</span>
                        <span class="text-rose-600">-{{ exits }}</span>
                    </p>
                    <p class="text-xs text-slate-400 notosan">{{ from }} &rarr; {{ to }}</p>
                </div>
            </div>
        </div>
    </div>

    {# ---------- Report Categories ---------- #}
    <section class="mb-10">
        <div class="mb-4">
            <h2 class="text-lg font-semibold text-slate-900 notosan">{{ t('reports.category.workforce') }}</h2>
            <p class="text-sm text-slate-500 notosan">{{ t('reports.category.workforce_desc') }}</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <a href="/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/reports/workforce"
                class="block bg-white rounded-xl border border-slate-200 p-6 shadow-sm hover:shadow-md transition notosan">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0 w-12 h-12 rounded-lg bg-blue-50 flex items-center justify-center">
                        <i class="fas fa-users text-blue-600 text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">{{ t('reports.workforce.title') }}</h3>
                        <p class="text-sm text-slate-500">{{ t('reports.workforce.desc') }}</p>
                    </div>
                </div>
            </a>
        </div>
    </section>

    <section class="mb-10">
        <div class="mb-4">
            <h2 class="text-lg font-semibold text-slate-900 notosan">{{ t('reports.category.attendance') }}</h2>
            <p class="text-sm text-slate-500 notosan">{{ t('reports.category.attendance_desc') }}</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <a href="/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/reports/leave"
                class="block bg-white rounded-xl border border-slate-200 p-6 shadow-sm hover:shadow-md transition notosan">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0 w-12 h-12 rounded-lg bg-teal-50 flex items-center justify-center">
                        <i class="fas fa-umbrella-beach text-teal-600 text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">{{ t('reports.leave.title') }}</h3>
                        <p class="text-sm text-slate-500">{{ t('reports.leave.desc') }}</p>
                    </div>
                </div>
            </a>
            <a href="/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/reports/overtime"
                class="block bg-white rounded-xl border border-slate-200 p-6 shadow-sm hover:shadow-md transition notosan">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0 w-12 h-12 rounded-lg bg-orange-50 flex items-center justify-center">
                        <i class="fas fa-clock text-orange-600 text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">{{ t('reports.overtime.title') }}</h3>
                        <p class="text-sm text-slate-500">{{ t('reports.overtime.desc') }}</p>
                    </div>
                </div>
            </a>
        </div>
    </section>

    <section class="mb-10">
        <div class="mb-4">
            <h2 class="text-lg font-semibold text-slate-900 notosan">{{ t('reports.category.people') }}</h2>
            <p class="text-sm text-slate-500 notosan">{{ t('reports.category.people_desc') }}</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <a href="/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/reports/turnover"
                class="block bg-white rounded-xl border border-slate-200 p-6 shadow-sm hover:shadow-md transition notosan">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0 w-12 h-12 rounded-lg bg-rose-50 flex items-center justify-center">
                        <i class="fas fa-people-arrows text-rose-600 text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">{{ t('reports.turnover.title') }}</h3>
                        <p class="text-sm text-slate-500">{{ t('reports.turnover.desc') }}</p>
                    </div>
                </div>
            </a>
        </div>
    </section>
</div>
{% endblock %}
