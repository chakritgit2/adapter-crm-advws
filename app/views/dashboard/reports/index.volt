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
</div>
{% endblock %}
