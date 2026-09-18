{% extends 'layouts/admin.volt' %}

{% block content %}
<div class="container mx-auto px-4 lg:px-8 py-8">
    <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 notosan">{{ t('settings.leave_policies.title') }}</h1>
            <p class="text-sm text-slate-500 mt-1 notosan">{{ t('settings.leave_policies.subtitle') }}</p>
        </div>
        <a href="/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/settings/leave-policies/create"
            class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium text-sm">
            <i class="fas fa-plus"></i>
            {{ t('settings.leave_policies.new') }}
        </a>
    </div>

    {% if leaveTypes|length > 0 %}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-3 border-b border-slate-200 flex items-center gap-3">
            <i class="fas fa-search text-slate-400 text-sm"></i>
            <input type="text" id="simpleSearch" placeholder="{{ t('settings.leave_policies.search_placeholder') }}"
                class="flex-1 text-sm outline-none bg-transparent text-slate-700 placeholder-slate-400">
        </div>
        <div class="overflow-x-auto">
            <table id="leavePoliciesTable" class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-600 font-semibold border-b border-slate-200 uppercase tracking-wide">
                    <tr>
                        <th class="px-4 py-2" rowspan="2">{{ t('common.name') }}</th>
                        <th class="px-4 py-2" rowspan="2">{{ t('settings.leave_policies.default_allowance') }}</th>
                        <th class="px-4 py-2 text-center" rowspan="2">{{ t('settings.leave_policies.workday') }}</th>
                        <th class="px-4 py-2 text-center" rowspan="2">{{ t('settings.leave_policies.toil') }}</th>
                        <th class="px-4 py-2 text-center" rowspan="2">{{ t('settings.leave_policies.year_end') }}</th>
                        <th class="px-4 py-2 text-center" colspan="3">{{ t('settings.leave_policies.leave_modes') }}</th>
                        <th class="px-4 py-2 text-center" rowspan="2">{{ t('settings.leave_policies.active') }}</th>
                        <th class="px-4 py-2 text-right" rowspan="2">{{ t('common.actions') }}</th>
                    </tr>
                    <tr>
                        <th class="px-4 py-2 text-center text-xs">{{ t('settings.leave_policies.mode_hourly_short') }}</th>
                        <th class="px-4 py-2 text-center text-xs">{{ t('settings.leave_policies.mode_whole_day_short') }}</th>
                        <th class="px-4 py-2 text-center text-xs">{{ t('settings.leave_policies.mode_multi_day_short') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    {% for leaveType in leaveTypes %}
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-4 py-2 font-medium text-slate-900 whitespace-nowrap">
                            {{ leaveType['name'] }}
                            {% if leaveType['is_toil'] %}
                            <span class="ml-1.5 inline-flex items-center px-1.5 py-0 rounded text-[10px] font-medium bg-purple-100 text-purple-700 border border-purple-200">TOIL</span>
                            {% endif %}
                        </td>
                        <td class="px-4 py-2 text-slate-600 whitespace-nowrap">
                            {% if leaveType['default_allowance_minutes'] >= 480 %}
                                {{ round(leaveType['default_allowance_minutes'] / 480, 2) }} {{ t('settings.leave_policies.days') }}
                            {% elseif leaveType['default_allowance_minutes'] >= 60 %}
                                {{ round(leaveType['default_allowance_minutes'] / 60, 0) }} {{ t('settings.leave_policies.hours') }}
                            {% elseif leaveType['default_allowance_minutes'] > 0 %}
                                {{ leaveType['default_allowance_minutes'] }} {{ t('settings.leave_policies.min') }}
                            {% else %}
                                <span class="text-slate-400">-</span>
                            {% endif %}
                        </td>
                        <td class="px-4 py-2 text-center text-slate-600 whitespace-nowrap">
                            {{ leaveType['workday_hours'] }}h
                        </td>
                        <td class="px-4 py-2 text-center">
                            {% if leaveType['is_toil'] %}
                            <span class="inline-flex items-center text-purple-600"><i class="fas fa-check-circle mr-1"></i>{{ t('common.yes') }}</span>
                            {% else %}
                            <span class="text-slate-400">-</span>
                            {% endif %}
                        </td>
                        <td class="px-4 py-2 text-center whitespace-nowrap">
                            {% if isSuperAdmin %}
                            <select class="year-end-mode-select text-xs px-2 py-1 border border-slate-300 rounded-lg bg-white text-slate-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none cursor-pointer"
                                data-public-id="{{ leaveType['public_id'] }}"
                                data-leave-name="{{ leaveType['name'] }}">
                                <option value="reset" {% if leaveType['year_end_mode'] == 'reset' %}selected{% endif %}>{{ t('settings.leave_policies.reset_normal') }}</option>
                                <option value="carry_forward" {% if leaveType['year_end_mode'] == 'carry_forward' %}selected{% endif %}>{{ t('settings.leave_policies.carry_forward') }}</option>
                            </select>
                            {% else %}
                                {% if leaveType['year_end_mode'] == 'carry_forward' %}
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-blue-100 text-blue-700 border border-blue-200">{{ t('settings.leave_policies.carry_forward') }}</span>
                                {% else %}
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 text-slate-600 border border-slate-200">{{ t('settings.leave_policies.reset') }}</span>
                                {% endif %}
                            {% endif %}
                        </td>
                        <td class="px-4 py-2 text-center" title="{{ t('settings.leave_policies.allow_hourly') }}">
                            {% if leaveType['allow_hourly'] %}<i class="fas fa-check-circle text-emerald-500"></i>{% else %}<span class="text-slate-300">-</span>{% endif %}
                        </td>
                        <td class="px-4 py-2 text-center" title="{{ t('settings.leave_policies.allow_whole_day') }}">
                            {% if leaveType['allow_whole_day'] %}<i class="fas fa-check-circle text-emerald-500"></i>{% else %}<span class="text-slate-300">-</span>{% endif %}
                        </td>
                        <td class="px-4 py-2 text-center" title="{{ t('settings.leave_policies.allow_multi_day') }}">
                            {% if leaveType['allow_multi_day'] %}<i class="fas fa-check-circle text-emerald-500"></i>{% else %}<span class="text-slate-300">-</span>{% endif %}
                        </td>
                        <td class="px-4 py-2 text-center">
                            {% if leaveType['is_active'] %}
                            <span class="inline-flex items-center text-emerald-600"><i class="fas fa-check-circle mr-1"></i>{{ t('common.yes') }}</span>
                            {% else %}
                            <span class="inline-flex items-center text-slate-400"><i class="fas fa-minus-circle mr-1"></i>{{ t('common.no') }}</span>
                            {% endif %}
                        </td>
                        <td class="px-4 py-2 text-end">
                            <div class="flex items-center justify-end gap-1">
                                <a href="/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/settings/leave-policies/edit/{{ leaveType['public_id'] }}"
                                    class="text-blue-600 hover:text-blue-800 text-xs font-medium px-1.5 py-0.5 rounded hover:bg-blue-50 transition">
                                    <i class="fas fa-edit mr-0.5"></i>{{ t('common.edit') }}
                                </a>
                                <form action="/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/settings/leave-policies/delete/{{ leaveType['public_id'] }}" method="POST"
                                    class="inline" onsubmit="return confirm('{{ t('settings.leave_policies.confirm_delete') }}');">
                                    <button type="submit"
                                        class="text-red-600 cursor-pointer hover:text-red-800 text-xs font-medium px-1.5 py-0.5 rounded hover:bg-red-50 transition">
                                        <i class="fas fa-trash-alt mr-0.5"></i>{{ t('common.delete') }}
                                    </button>
                                </form>
                            </div>
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
            <i class="fas fa-umbrella-beach text-slate-400 text-2xl"></i>
        </div>
        <h3 class="text-lg font-semibold text-slate-900 mb-1 notosan">{{ t('settings.leave_policies.no_policies') }}</h3>
        <p class="text-sm text-slate-500 mb-4 notosan">{{ t('settings.leave_policies.no_policies_desc') }}</p>
        <a href="/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/settings/leave-policies/create"
            class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium text-sm">
            <i class="fas fa-plus"></i>
            {{ t('settings.leave_policies.create_first') }}
        </a>
    </div>
    {% endif %}
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('simpleSearch');
        const table = document.getElementById('leavePoliciesTable');
        if (!searchInput || !table) return;

        const rows = table.querySelectorAll('tbody tr');
        searchInput.addEventListener('keyup', function () {
            const term = this.value.toLowerCase();
            rows.forEach(function (row) {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(term) ? '' : 'none';
            });
        });

        // Inline year-end mode toggle (Super Admin only)
        document.querySelectorAll('.year-end-mode-select').forEach(function (select) {
            const originalValue = select.value;
            select.addEventListener('change', function () {
                const publicId = this.getAttribute('data-public-id');
                const leaveName = this.getAttribute('data-leave-name');
                const newMode = this.value;
                const label = newMode === 'carry_forward' ? '<?= $this->getDI()->get('locale')->t('settings.leave_policies.carry_forward') ?>' : '<?= $this->getDI()->get('locale')->t('settings.leave_policies.reset_normal') ?>';

                if (!confirm('<?= $this->getDI()->get('locale')->t('settings.leave_policies.confirm_year_end_change') ?>' + leaveName + '<?= $this->getDI()->get('locale')->t('settings.leave_policies.confirm_year_end_change_mid') ?>' + label + '"?\n\n' +
                    (newMode === 'carry_forward'
                        ? '<?= $this->getDI()->get('locale')->t('settings.leave_policies.carry_forward_desc') ?>'
                        : '<?= $this->getDI()->get('locale')->t('settings.leave_policies.reset_desc') ?>')) {
                    this.value = originalValue;
                    return;
                }

                const self = this;
                self.disabled = true;
                fetch(window.TENANT_BASE_URL + '/dashboard/settings/leave-policies/' + publicId + '/year-end-mode', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'year_end_mode=' + encodeURIComponent(newMode)
                })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.success) {
                        self.value = data.year_end_mode;
                        // brief visual confirmation
                        self.classList.add('ring-2', 'ring-emerald-400');
                        setTimeout(function () { self.classList.remove('ring-2', 'ring-emerald-400'); }, 1200);
                    } else {
                        alert(data.message || '<?= $this->getDI()->get('locale')->t('settings.leave_policies.update_failed') ?>');
                        self.value = originalValue;
                    }
                })
                .catch(function () {
                    alert('<?= $this->getDI()->get('locale')->t('settings.leave_policies.network_error') ?>');
                    self.value = originalValue;
                })
                .finally(function () { self.disabled = false; });
            });
        });
    });
</script>
{% endblock %}
