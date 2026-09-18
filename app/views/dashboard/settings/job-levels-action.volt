{% extends 'layouts/admin.volt' %}

{% block content %}
<div class="container mx-auto px-4 lg:px-8 py-8">
    <div class="max-w-2xl mx-auto">
        <div class="mb-6">
            <a href="/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/settings/job-levels"
                class="text-sm text-slate-500 hover:text-slate-700 inline-flex items-center gap-1 mb-4">
                <i class="fas fa-arrow-left"></i>
                {{ t('settings.job_levels.back') }}
            </a>
            <h1 class="text-2xl font-bold text-slate-900 notosan">
                {% if mode == 'view' %}{{ t('settings.job_levels.level_label') }}{% elseif mode == 'edit' %}{{ t('common.edit') }}{% else %}{{ t('settings.job_levels.new_label') }}{% endif %} {{ t('settings.job_levels.level_label') }}
            </h1>
            <p class="text-sm text-slate-500 mt-1 notosan">
                {% if mode == 'view' %}{{ t('settings.job_levels.view_subtitle') }}{% elseif mode == 'edit' %}{{ t('settings.job_levels.edit_subtitle') }}{% else %}{{ t('settings.job_levels.create_subtitle') }}{% endif %}
            </p>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            {% if mode == 'view' %}
            <div class="mb-4">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-600 border border-slate-200">
                    <i class="fas fa-globe mr-1"></i>{{ t('settings.job_levels.system_default') }}
                </span>
            </div>
            {% endif %}
            <form method="POST"
                action="{% if mode == 'edit' %}/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/settings/job-levels/update/{{ jobLevel['id'] }}{% else %}/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/settings/job-levels/store{% endif %}"
                class="space-y-6">

                <div>
                    <label for="code" class="block text-sm font-medium text-slate-700 mb-1 notosan">
                        {{ t('settings.job_levels.code') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="code" name="code" required maxlength="10"
                        value="{% if mode == 'edit' or mode == 'view' %}{{ jobLevel['code'] }}{% endif %}"
                        {% if mode == 'view' %}disabled{% endif %}
                        class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm font-mono uppercase disabled:bg-slate-50 disabled:text-slate-500"
                        placeholder="{{ t('settings.job_levels.code_placeholder') }}">
                    <p class="text-xs text-slate-500 mt-1">{{ t('settings.job_levels.code_hint') }}</p>
                </div>

                <div>
                    <label for="category" class="block text-sm font-medium text-slate-700 mb-1 notosan">
                        {{ t('settings.job_levels.category') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="category" name="category" required maxlength="50"
                        value="{% if mode == 'edit' or mode == 'view' %}{{ jobLevel['category'] }}{% endif %}"
                        {% if mode == 'view' %}disabled{% endif %}
                        class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm disabled:bg-slate-50 disabled:text-slate-500"
                        placeholder="{{ t('settings.job_levels.category_placeholder') }}">
                    <p class="text-xs text-slate-500 mt-1">{{ t('settings.job_levels.category_hint') }}</p>
                </div>

                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700 mb-1 notosan">
                        {{ t('common.name') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="name" name="name" required maxlength="100"
                        value="{% if mode == 'edit' or mode == 'view' %}{{ jobLevel['name'] }}{% endif %}"
                        {% if mode == 'view' %}disabled{% endif %}
                        class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm disabled:bg-slate-50 disabled:text-slate-500"
                        placeholder="{{ t('settings.job_levels.name_placeholder') }}">
                </div>

                <div>
                    <label for="sort_order" class="block text-sm font-medium text-slate-700 mb-1 notosan">
                        {{ t('settings.job_levels.sort_order') }}
                    </label>
                    <input type="number" id="sort_order" name="sort_order" min="0" max="9999"
                        value="{% if mode == 'edit' or mode == 'view' %}{{ jobLevel['sort_order'] }}{% else %}0{% endif %}"
                        {% if mode == 'view' %}disabled{% endif %}
                        class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm disabled:bg-slate-50 disabled:text-slate-500">
                    <p class="text-xs text-slate-500 mt-1">{{ t('settings.job_levels.sort_order_hint') }}</p>
                </div>

                <div class="flex items-center gap-3">
                    <input type="checkbox" id="is_active" name="is_active" value="1"
                        {% if mode != 'edit' or jobLevel['is_active'] %}checked{% endif %}
                        {% if mode == 'view' %}disabled{% endif %}
                        class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500 disabled:bg-slate-100">
                    <label for="is_active" class="text-sm font-medium text-slate-700 notosan">
                        {{ t('settings.job_levels.active') }}
                    </label>
                </div>
                <p class="text-xs text-slate-500 -mt-4 ml-7">{{ t('settings.job_levels.active_hint') }}</p>

                {# Can Approve Leave Request flag — defines which job levels are
                   permitted to approve leave requests for employees they manage. #}
                <div class="pt-2">
                    <label class="block text-sm font-medium text-slate-700 mb-2 notosan">
                        {{ t('settings.job_levels.approval_permissions') }}
                    </label>
                    <div class="flex flex-wrap gap-2">
                        {% set leaveChecked = (mode == 'edit' or mode == 'view') ? jobLevel['can_approve_leave'] : false %}
                        <label for="can_approve_leave" id="canApproveLeaveBtn"
                            class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border transition-colors text-sm notosan {% if leaveChecked %}bg-emerald-50 border-emerald-300 text-emerald-700{% else %}bg-white border-slate-300 text-slate-600 hover:bg-slate-50{% endif %} {% if mode == 'view' %}cursor-default opacity-80{% else %}cursor-pointer{% endif %}">
                            <input type="checkbox" id="can_approve_leave" name="can_approve_leave" value="1"
                                {% if leaveChecked %}checked{% endif %}
                                {% if mode == 'view' %}disabled{% endif %}
                                class="sr-only">
                            <span id="canApproveLeaveBox"
                                class="w-4 h-4 rounded border flex items-center justify-center transition-colors {% if leaveChecked %}bg-emerald-500 border-emerald-500 text-white{% else %}bg-white border-slate-300 text-transparent{% endif %}">
                                <i id="canApproveLeaveCheck" class="fas fa-check text-[10px] {% if not leaveChecked %}hidden{% endif %}"></i>
                            </span>
                            <i id="canApproveLeaveIcon" class="fas fa-calendar-check {% if leaveChecked %}text-emerald-600{% else %}text-slate-400{% endif %}"></i>
                            <span>{{ t('settings.job_levels.can_approve_leave') }}</span>
                        </label>
                    </div>
                    <p class="text-xs text-slate-500 mt-2">{{ t('settings.job_levels.can_approve_leave_hint') }}</p>
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                    <a href="/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/settings/job-levels"
                        class="px-4 py-2 text-sm font-medium text-slate-600 bg-slate-100 rounded-lg hover:bg-slate-200 transition-colors">
                        {% if mode == 'view' %}{{ t('common.back') }}{% else %}{{ t('common.cancel') }}{% endif %}
                    </a>
                    {% if mode != 'view' %}
                    <button type="submit"
                        class="px-6 py-2 cursor-pointer text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors shadow-sm">
                        {% if mode == 'edit' %}{{ t('settings.job_levels.update') }}{% else %}{{ t('settings.job_levels.create') }}{% endif %}
                    </button>
                    {% endif %}
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    (function () {
        const cb = document.getElementById('can_approve_leave');
        if (!cb) return;
        const btn = document.getElementById('canApproveLeaveBtn');
        const box = document.getElementById('canApproveLeaveBox');
        const icon = document.getElementById('canApproveLeaveIcon');
        const check = document.getElementById('canApproveLeaveCheck');

        function render() {
            const on = cb.checked;
            if (on) {
                btn.classList.remove('bg-white', 'border-slate-300', 'text-slate-600', 'hover:bg-slate-50');
                btn.classList.add('bg-emerald-50', 'border-emerald-300', 'text-emerald-700');
                box.classList.remove('bg-white', 'border-slate-300', 'text-transparent');
                box.classList.add('bg-emerald-500', 'border-emerald-500', 'text-white');
                icon.classList.remove('text-slate-400');
                icon.classList.add('text-emerald-600');
                if (check) check.classList.remove('hidden');
            } else {
                btn.classList.add('bg-white', 'border-slate-300', 'text-slate-600', 'hover:bg-slate-50');
                btn.classList.remove('bg-emerald-50', 'border-emerald-300', 'text-emerald-700');
                box.classList.add('bg-white', 'border-slate-300', 'text-transparent');
                box.classList.remove('bg-emerald-500', 'border-emerald-500', 'text-white');
                icon.classList.add('text-slate-400');
                icon.classList.remove('text-emerald-600');
                if (check) check.classList.add('hidden');
            }
        }

        cb.addEventListener('change', render);
    })();
</script>
{% endblock %}
