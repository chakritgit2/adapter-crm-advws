{% extends 'layouts/admin.volt' %}

{% block content %}
<div class="container mx-auto px-4 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-slate-900 notosan">{{ t('settings.index.title') }}</h1>
        <p class="text-sm text-slate-500 mt-1 notosan">{{ t('settings.index.subtitle') }}</p>
    </div>

    {# ---------- Organization ---------- #}
    <section>
        <div class="mb-4">
            <h2 class="text-lg font-semibold text-slate-900 notosan">{{ t('settings.index.category.organization') }}</h2>
            <p class="text-sm text-slate-500 notosan">{{ t('settings.index.category.organization_desc') }}</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">

            <a href="/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/settings/company"
                class="block bg-white rounded-xl border border-slate-200 p-4 shadow-sm hover:shadow-md hover:border-slate-300 transition notosan">
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center">
                        <i class="fas fa-building text-blue-600 text-base"></i>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-sm font-semibold text-slate-900">{{ t('settings.index.company') }}</h3>
                        <p class="text-xs text-slate-500 line-clamp-2">{{ t('settings.index.company_desc') }}</p>
                    </div>
                </div>
            </a>

            <a href="/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/settings/custom-attributes"
                class="block bg-white rounded-xl border border-slate-200 p-4 shadow-sm hover:shadow-md hover:border-slate-300 transition notosan">
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-emerald-50 flex items-center justify-center">
                        <i class="fas fa-sitemap text-emerald-600 text-base"></i>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-sm font-semibold text-slate-900">{{ t('settings.index.custom_attributes') }}</h3>
                        <p class="text-xs text-slate-500 line-clamp-2">{{ t('settings.index.custom_attributes_desc') }}</p>
                    </div>
                </div>
            </a>

        </div>
    </section>

    {# ---------- Time & Attendance ---------- #}
    <section class="mt-10">
        <div class="mb-4">
            <h2 class="text-lg font-semibold text-slate-900 notosan">{{ t('settings.index.category.time_attendance') }}</h2>
            <p class="text-sm text-slate-500 notosan">{{ t('settings.index.category.time_attendance_desc') }}</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">

        </div>
    </section>

    {# ---------- System ---------- #}
    <section class="mt-10">
        <div class="mb-4">
            <h2 class="text-lg font-semibold text-slate-900 notosan">{{ t('settings.index.category.system') }}</h2>
            <p class="text-sm text-slate-500 notosan">{{ t('settings.index.category.system_desc') }}</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">

            <a href="/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/settings/admin-users"
                class="block bg-white rounded-xl border border-slate-200 p-4 shadow-sm hover:shadow-md hover:border-slate-300 transition notosan">
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-purple-50 flex items-center justify-center">
                        <i class="fas fa-shield-alt text-purple-600 text-base"></i>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-sm font-semibold text-slate-900">{{ t('settings.index.iam') }}</h3>
                        <p class="text-xs text-slate-500 line-clamp-2">{{ t('settings.index.iam_desc') }}</p>
                    </div>
                </div>
            </a>

            <a href="/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/settings/audit"
                class="block bg-white rounded-xl border border-slate-200 p-4 shadow-sm hover:shadow-md hover:border-slate-300 transition notosan">
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-amber-50 flex items-center justify-center">
                        <i class="fas fa-clipboard-list text-amber-600 text-base"></i>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-sm font-semibold text-slate-900">{{ t('settings.index.audit') }}</h3>
                        <p class="text-xs text-slate-500 line-clamp-2">{{ t('settings.index.audit_desc') }}</p>
                    </div>
                </div>
            </a>

        </div>
    </section>
</div>
{% endblock %}
