{% extends 'layouts/admin.volt' %}

{% block content %}
<div class="container mx-auto px-4 lg:px-8 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="mb-6">
            <a href="/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/settings/leave-policies"
                class="text-sm text-slate-500 hover:text-slate-700 inline-flex items-center gap-1 mb-4">
                <i class="fas fa-arrow-left"></i>
                {{ t('settings.leave_policies.back') }}
            </a>
            <h1 class="text-2xl font-bold text-slate-900 notosan">
                {% if mode == 'edit' %}{{ t('common.edit') }}{% else %}{{ t('settings.leave_policies.new_label') }}{% endif %} {{ t('settings.leave_policies.policy_label') }}
            </h1>
            <p class="text-sm text-slate-500 mt-1 notosan">
                {% if mode == 'edit' %}{{ t('settings.leave_policies.edit_subtitle') }}{% else %}{{ t('settings.leave_policies.create_subtitle') }}{% endif %}
            </p>
        </div>

        <form method="POST"
            action="{% if mode == 'edit' %}/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/settings/leave-policies/update/{{ leaveType['public_id'] }}{% else %}/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/settings/leave-policies/store{% endif %}"
            class="space-y-6">

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-slate-700 mb-1 notosan">
                            {{ t('settings.leave_policies.policy_name') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="name" name="name" required maxlength="100"
                            value="{% if mode == 'edit' %}{{ leaveType['name'] }}{% endif %}"
                            class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm"
                            placeholder="{{ t('settings.leave_policies.name_placeholder') }}">
                    </div>

                    <div>
                        <label for="default_allowance_minutes" class="block text-sm font-medium text-slate-700 mb-1 notosan">
                            {{ t('settings.leave_policies.default_allowance_minutes') }}
                        </label>
                        <input type="number" id="default_allowance_minutes" name="default_allowance_minutes" min="0" step="1"
                            value="{% if mode == 'edit' %}{{ leaveType['default_allowance_minutes'] }}{% else %}4800{% endif %}"
                            class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm"
                            placeholder="{{ t('settings.leave_policies.allowance_placeholder') }}">
                        <p class="text-xs text-slate-500 mt-1">{{ t('settings.leave_policies.allowance_hint') }}</p>
                    </div>

                    <div>
                        <label for="workday_hours" class="block text-sm font-medium text-slate-700 mb-1 notosan">
                            {{ t('settings.leave_policies.workday_hours') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="number" id="workday_hours" name="workday_hours" required min="0.5" step="0.5"
                            value="{% if mode == 'edit' %}{{ leaveType['workday_hours'] }}{% else %}8{% endif %}"
                            class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm"
                            placeholder="{{ t('settings.leave_policies.workday_hours_placeholder') }}">
                        <p class="text-xs text-slate-500 mt-1">{{ t('settings.leave_policies.workday_hours_hint') }}</p>
                    </div>

                    <div>
                        <div class="flex items-center gap-3">
                            <input type="checkbox" id="is_toil" name="is_toil" value="1"
                                {% if mode == 'edit' and leaveType['is_toil'] %}checked{% endif %}
                                class="w-4 h-4 text-purple-600 border-slate-300 rounded focus:ring-purple-500">
                            <label for="is_toil" class="text-sm font-medium text-slate-700 notosan">
                                {{ t('settings.leave_policies.toil_label') }}
                            </label>
                        </div>
                        <p class="text-xs text-slate-500 ml-7">{{ t('settings.leave_policies.toil_hint') }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2 notosan">
                            {{ t('settings.leave_policies.leave_modes_label') }}
                        </label>
                        <div class="flex flex-wrap items-center gap-6">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" id="allow_hourly" name="allow_hourly" value="1"
                                    {% if mode != 'edit' or leaveType['allow_hourly'] %}checked{% endif %}
                                    class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500 leave-mode-cb">
                                <span class="text-sm text-slate-700 notosan">{{ t('settings.leave_policies.allow_hourly') }}</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" id="allow_whole_day" name="allow_whole_day" value="1"
                                    {% if mode != 'edit' or leaveType['allow_whole_day'] %}checked{% endif %}
                                    class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500 leave-mode-cb">
                                <span class="text-sm text-slate-700 notosan">{{ t('settings.leave_policies.allow_whole_day') }}</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" id="allow_multi_day" name="allow_multi_day" value="1"
                                    {% if mode != 'edit' or leaveType['allow_multi_day'] %}checked{% endif %}
                                    class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500 leave-mode-cb">
                                <span class="text-sm text-slate-700 notosan">{{ t('settings.leave_policies.allow_multi_day') }}</span>
                            </label>
                        </div>
                        <p class="text-xs text-slate-500 mt-1">{{ t('settings.leave_policies.leave_modes_hint') }}</p>
                    </div>

                    {% if isSuperAdmin %}
                    <div>
                        <label for="year_end_mode" class="block text-sm font-medium text-slate-700 mb-1 notosan">
                            {{ t('settings.leave_policies.year_end_mode') }}
                        </label>
                        <select id="year_end_mode" name="year_end_mode"
                            class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm">
                            <option value="reset" {% if mode == 'edit' and leaveType['year_end_mode'] == 'reset' %}selected{% endif %}>{{ t('settings.leave_policies.reset_option') }}</option>
                            <option value="carry_forward" {% if mode == 'edit' and leaveType['year_end_mode'] == 'carry_forward' %}selected{% endif %}>{{ t('settings.leave_policies.carry_forward_option') }}</option>
                        </select>
                        <p class="text-xs text-slate-500 mt-1">{{ t('settings.leave_policies.year_end_hint') }}</p>
                    </div>
                    {% else %}
                    <input type="hidden" name="year_end_mode" value="{% if mode == 'edit' %}{{ leaveType['year_end_mode'] }}{% else %}reset{% endif %}">
                    {% endif %}

                    <div>
                        <div class="flex items-center gap-3">
                            <input type="checkbox" id="is_active" name="is_active" value="1"
                                {% if mode != 'edit' or leaveType['is_active'] %}checked{% endif %}
                                class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500">
                            <label for="is_active" class="text-sm font-medium text-slate-700 notosan">
                                {{ t('settings.leave_policies.active') }}
                            </label>
                        </div>
                        <p class="text-xs text-slate-500 ml-7">{{ t('settings.leave_policies.active_hint') }}</p>
                    </div>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                <h2 class="text-lg font-semibold text-slate-900 mb-1 notosan">{{ t('settings.leave_policies.calculator_title') }}</h2>
                <p class="text-xs text-slate-500 mb-4 notosan">{{ t('settings.leave_policies.calculator_desc') }}</p>

                <div class="space-y-4">
                    <div>
                        <label for="calc_days" class="block text-sm font-medium text-slate-700 mb-1 notosan">{{ t('settings.leave_policies.days') }}</label>
                        <input type="number" id="calc_days" min="0" step="0.5"
                            class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm"
                            placeholder="{{ t('settings.leave_policies.days_placeholder') }}">
                    </div>

                    <div>
                        <label for="calc_hours_per_day" class="block text-sm font-medium text-slate-700 mb-1 notosan">{{ t('settings.leave_policies.workday_hours') }}</label>
                        <input type="number" id="calc_hours_per_day" value="{% if mode == 'edit' %}{{ leaveType['workday_hours'] }}{% else %}8{% endif %}" min="0.5" step="0.5"
                            class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm"
                            placeholder="{{ t('settings.leave_policies.workday_hours_placeholder') }}">
                    </div>

                    <div class="text-sm text-slate-600">
                        <span class="font-medium">{{ t('settings.leave_policies.result') }}</span>
                        <span id="calc_result">0</span> {{ t('settings.leave_policies.minutes') }}
                    </div>

                    <button type="button" id="calc_insert_btn"
                        class="w-full px-4 py-2 cursor-pointer text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors shadow-sm">
                        &lt; {{ t('settings.leave_policies.insert_allowance') }}
                    </button>
                </div>
            </div>
        </div>

        {% if installedLanguages is defined and installedLanguages|length > 0 %}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-4 border-b border-slate-200 bg-indigo-50">
                <div class="flex items-center gap-2">
                    <i class="fas fa-language text-indigo-600"></i>
                    <h2 class="text-lg font-semibold text-slate-900 notosan">{{ t('translations.title') }}</h2>
                </div>
                <p class="text-sm text-slate-500 mt-1 notosan">{% if mode == 'edit' %}{{ t('settings.leave_policies.translations_desc_edit') }}{% else %}{{ t('settings.leave_policies.translations_desc_create') }}{% endif %}</p>
            </div>
            <div class="p-6 space-y-5">
                {% for lang in installedLanguages %}
                <div class="border-b border-slate-100 pb-4 last:border-0 last:pb-0">
                    <p class="text-sm font-semibold text-slate-700 mb-3 notosan">{{ lang['language_name'] }} ({{ lang['language_code'] }})</p>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1 notosan">{{ t('settings.leave_policies.policy_name') }}</label>
                        <div class="flex items-center gap-2">
                            {% if mode == 'edit' %}
                            <input type="text" id="trans_{{ lang['language_code'] }}_name"
                                name="translations[{{ lang['language_code'] }}][name]"
                                class="flex-1 px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"
                                placeholder="{{ leaveType['name'] }}"
                                value="{{ existingTranslations[lang['language_code']]['name'] is defined ? existingTranslations[lang['language_code']]['name']['value'] : '' }}">
                            {% if existingTranslations[lang['language_code']]['name'] is defined %}
                            <button type="button" onclick="deleteLeavePolicyTranslation({{ existingTranslations[lang['language_code']]['name']['id'] }}, '{{ lang['language_code'] }}', 'name')"
                                class="px-3 py-2 text-xs font-medium text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition cursor-pointer">
                                <i class="fas fa-times"></i>
                            </button>
                            {% endif %}
                            {% else %}
                            <input type="text" name="translations[{{ lang['language_code'] }}][name]"
                                class="flex-1 px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"
                                placeholder="{{ t('settings.leave_policies.translate_name_placeholder') }}">
                            {% endif %}
                        </div>
                        {% if mode == 'edit' %}
                        <div id="trans_feedback_{{ lang['language_code'] }}_name" class="text-xs mt-1 hidden"></div>
                        {% endif %}
                    </div>
                </div>
                {% endfor %}
            </div>
        </div>
        {% endif %}

        {% if mode == 'edit' %}
        <!-- Allowance Rules Section (inside main form; nested forms replaced with JS dynamic submission) -->
        <div id="allowance-rules" class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-4 border-b border-slate-200 bg-amber-50">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-sliders-h text-amber-600"></i>
                        <h2 class="text-lg font-semibold text-slate-900 notosan">{{ t('settings.leave_policies.allowance_rules') }}</h2>
                    </div>
                    <button type="button" onclick="recalculateBalances()"
                        class="px-3 py-1.5 text-xs font-medium text-white bg-amber-600 rounded-lg hover:bg-amber-700 transition cursor-pointer">
                        <i class="fas fa-sync-alt mr-1"></i> {{ t('settings.leave_policies.recalculate') }}
                    </button>
                </div>
                <p class="text-sm text-slate-500 mt-1 notosan">
                    {{ t('settings.leave_policies.allowance_rules_desc') }}
                </p>
            </div>

            <!-- Existing Rules Table -->
            <div class="overflow-x-auto">
                {% if allowanceRules is defined and allowanceRules|length > 0 %}
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-2 text-left font-medium text-slate-600">{{ t('settings.leave_policies.position') }}</th>
                            <th class="px-4 py-2 text-left font-medium text-slate-600">{{ t('settings.leave_policies.job_level') }}</th>
                            <th class="px-4 py-2 text-left font-medium text-slate-600">{{ t('settings.leave_policies.min_tenure') }}</th>
                            <th class="px-4 py-2 text-left font-medium text-slate-600">{{ t('settings.leave_policies.max_tenure') }}</th>
                            <th class="px-4 py-2 text-left font-medium text-slate-600">{{ t('settings.leave_policies.allowance') }}</th>
                            <th class="px-4 py-2 text-left font-medium text-slate-600">{{ t('settings.leave_policies.priority') }}</th>
                            <th class="px-4 py-2 text-left font-medium text-slate-600">{{ t('settings.leave_policies.active') }}</th>
                            <th class="px-4 py-2 text-left font-medium text-slate-600">{{ t('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {% for rule in allowanceRules %}
                        <tr class="border-t border-slate-100 {% if not rule['is_active'] %}opacity-50{% endif %}">
                            <td class="px-4 py-2 text-slate-700">{{ rule['position_title'] is empty ? '<span class="text-slate-400">' ~ t('settings.leave_policies.any') ~ '</span>' : rule['position_title'] }}</td>
                            <td class="px-4 py-2 text-slate-700">{{ rule['job_level'] is empty ? '<span class="text-slate-400">' ~ t('settings.leave_policies.any') ~ '</span>' : rule['job_level'] }}</td>
                            <td class="px-4 py-2 text-slate-700">{{ rule['min_tenure_months'] is empty ? '<span class="text-slate-400">—</span>' : rule['min_tenure_months'] ~ ' ' ~ t('settings.leave_policies.months_short') }}</td>
                            <td class="px-4 py-2 text-slate-700">{{ rule['max_tenure_months'] is empty ? '<span class="text-slate-400">—</span>' : rule['max_tenure_months'] ~ ' ' ~ t('settings.leave_policies.months_short') }}</td>
                            <td class="px-4 py-2 text-slate-700 font-medium">
                                {{ rule['allowance_minutes'] }} {{ t('settings.leave_policies.min') }}
                                <span class="text-xs text-slate-400">({{ round(rule['allowance_minutes'] / (workdayHours * 60), 2) }} {{ t('settings.leave_policies.days') }})</span>
                            </td>
                            <td class="px-4 py-2 text-slate-700">{{ rule['priority'] }}</td>
                            <td class="px-4 py-2">
                                {% if rule['is_active'] %}
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">{{ t('common.yes') }}</span>
                                {% else %}
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-500">{{ t('common.no') }}</span>
                                {% endif %}
                            </td>
                            <td class="px-4 py-2">
                                <button type="button" onclick="editRule('{{ rule['public_id'] }}')"
                                    class="text-xs text-blue-600 hover:text-blue-800 mr-2 cursor-pointer">{{ t('common.edit') }}</button>
                                <button type="button" onclick="deleteRule('{{ rule['public_id'] }}')"
                                    class="text-xs text-red-600 hover:text-red-800 cursor-pointer">{{ t('common.delete') }}</button>
                            </td>
                        </tr>
                        {% endfor %}
                    </tbody>
                </table>
                {% else %}
                <div class="p-8 text-center text-slate-400 text-sm">
                    {{ t('settings.leave_policies.no_rules') }} {{ leaveType['default_allowance_minutes'] }} {{ t('settings.leave_policies.minutes') }}.
                </div>
                {% endif %}
            </div>

            <!-- Add / Edit Rule (no nested form; submitted via JS dynamic form) -->
            <div class="p-6 border-t border-slate-200 bg-slate-50">
                <h3 class="text-sm font-semibold text-slate-700 mb-4 notosan" id="rule_form_title">{{ t('settings.leave_policies.add_rule') }}</h3>
                <div id="rule_form_container">
                    <input type="hidden" id="rule_form_mode" value="create">
                    <input type="hidden" id="rule_form_action" value="">

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1 notosan">{{ t('settings.leave_policies.position') }}</label>
                            <select id="rule_position_id"
                                class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                                <option value="0">{{ t('settings.leave_policies.any_position') }}</option>
                                {% for pos in positions %}
                                <option value="{{ pos['id'] }}">{{ pos['job_title'] }}{% if pos['department'] %} ({{ pos['department'] }}){% endif %}</option>
                                {% endfor %}
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1 notosan">{{ t('settings.leave_policies.job_level') }}</label>
                            <select id="rule_job_level"
                                class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                                <option value="">{{ t('settings.leave_policies.any_level') }}</option>
                                {% for jl in jobLevels %}
                                <option value="{{ jl['code'] }}">{{ jl['code'] }} — {{ jl['name'] }}</option>
                                {% endfor %}
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1 notosan">{{ t('settings.leave_policies.priority') }}</label>
                            <input type="number" id="rule_priority" value="0" min="0" step="1"
                                class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none"
                                placeholder="0">
                            <p class="text-xs text-slate-400 mt-0.5">{{ t('settings.leave_policies.priority_hint') }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1 notosan">{{ t('settings.leave_policies.min_tenure_months') }}</label>
                            <input type="number" id="rule_min_tenure" value="" min="0" step="1"
                                class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none"
                                placeholder="{{ t('settings.leave_policies.min_tenure_placeholder') }}">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1 notosan">{{ t('settings.leave_policies.max_tenure_months') }}</label>
                            <input type="number" id="rule_max_tenure" value="" min="0" step="1"
                                class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none"
                                placeholder="{{ t('settings.leave_policies.max_tenure_placeholder') }}">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1 notosan">{{ t('settings.leave_policies.allowance_days') }}</label>
                            <input type="number" id="rule_allowance_days" value="0" min="0" step="0.5"
                                class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none"
                                placeholder="{{ t('settings.leave_policies.days_placeholder') }}">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1 notosan">{{ t('settings.leave_policies.allowance_hours') }}</label>
                            <input type="number" id="rule_allowance_hours" value="0" min="0" step="0.5"
                                class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none"
                                placeholder="{{ t('settings.leave_policies.hours_placeholder') }}">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1 notosan">{{ t('settings.leave_policies.allowance_minutes') }}</label>
                            <input type="number" id="rule_allowance_minutes" value="0" min="0" step="1"
                                class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none"
                                placeholder="{{ t('settings.leave_policies.minutes_placeholder') }}">
                            <p class="text-xs text-slate-400 mt-0.5">{{ t('settings.leave_policies.total') }} <span id="rule_total_minutes">0</span> {{ t('settings.leave_policies.min') }} (<span id="rule_total_days">0.00</span> {{ t('settings.leave_policies.days') }})</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 mb-4">
                        <input type="checkbox" id="rule_is_active" value="1" checked
                            class="w-4 h-4 text-amber-600 border-slate-300 rounded focus:ring-amber-500">
                        <label for="rule_is_active" class="text-sm font-medium text-slate-700 notosan">{{ t('settings.leave_policies.active') }}</label>
                    </div>

                    <div class="flex items-center gap-3">
                        <button type="button" id="rule_submit_btn" onclick="submitRuleForm()"
                            class="px-4 py-2 text-sm font-medium text-white bg-amber-600 rounded-lg hover:bg-amber-700 transition cursor-pointer">
                            <i class="fas fa-plus mr-1"></i> <span id="rule_submit_label">{{ t('settings.leave_policies.add_rule_btn') }}</span>
                        </button>
                        <button type="button" id="rule_cancel_btn" onclick="cancelEditRule()"
                            class="px-4 py-2 text-sm font-medium text-slate-600 bg-slate-100 rounded-lg hover:bg-slate-200 transition cursor-pointer hidden">
                            {{ t('settings.leave_policies.cancel_edit') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
        {% endif %}

        <div class="flex items-center justify-end gap-3">
            <a href="/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/settings/leave-policies"
                class="px-4 py-2 text-sm font-medium text-slate-600 bg-slate-100 rounded-lg hover:bg-slate-200 transition-colors">
                {{ t('common.cancel') }}
            </a>
            <button type="submit"
                class="px-6 py-2 cursor-pointer text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors shadow-sm">
                {% if mode == 'edit' %}{{ t('settings.leave_policies.update') }}{% else %}{{ t('settings.leave_policies.create') }}{% endif %}
            </button>
        </div>
        </form>

    </div>
</div>

<script>
function tenantUrl(path) {
    return window.TENANT_BASE_URL + path;
}

function deleteLeavePolicyTranslation(id, lang, column) {
    if (!confirm('<?= $this->getDI()->get('locale')->t('translations.confirm_delete') ?>')) return;

    const feedback = document.getElementById('trans_feedback_' + lang + '_' + column);

    fetch(tenantUrl('/dashboard/translations/delete/' + id), {
        method: 'POST'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('trans_' + lang + '_' + column).value = '';
            feedback.className = 'text-xs mt-1 text-emerald-600';
            feedback.textContent = '<?= $this->getDI()->get('locale')->t('translations.deleted') ?>';
        } else {
            feedback.className = 'text-xs mt-1 text-red-600';
            feedback.textContent = data.message || '<?= $this->getDI()->get('locale')->t('translations.delete_failed') ?>';
        }
        feedback.classList.remove('hidden');
        setTimeout(() => feedback.classList.add('hidden'), 3000);
    })
    .catch(() => {
        feedback.className = 'text-xs mt-1 text-red-600';
        feedback.textContent = '<?= $this->getDI()->get('locale')->t('translations.network_error') ?>';
        feedback.classList.remove('hidden');
    });
}

    document.addEventListener('DOMContentLoaded', function () {
        const daysInput = document.getElementById('calc_days');
        const hoursInput = document.getElementById('calc_hours_per_day');
        const resultDisplay = document.getElementById('calc_result');
        const insertBtn = document.getElementById('calc_insert_btn');
        const allowanceInput = document.getElementById('default_allowance_minutes');
        const workdayHoursInput = document.getElementById('workday_hours');

        function calculateMinutes() {
            const days = parseFloat(daysInput.value) || 0;
            const hours = parseFloat(hoursInput.value) || 0;
            return Math.round(days * hours * 60);
        }

        function updateResult() {
            resultDisplay.textContent = calculateMinutes().toLocaleString();
        }

        daysInput.addEventListener('input', updateResult);
        hoursInput.addEventListener('input', updateResult);

        workdayHoursInput.addEventListener('input', function () {
            hoursInput.value = workdayHoursInput.value;
            updateResult();
        });
        hoursInput.addEventListener('input', function () {
            workdayHoursInput.value = hoursInput.value;
        });

        insertBtn.addEventListener('click', function () {
            const minutes = calculateMinutes();
            if (minutes <= 0) return;
            allowanceInput.value = minutes;
            allowanceInput.focus();
        });

        updateResult();
    });

    // ─── Allowance Rules ───
    var workdayHours = {{ workdayHours is defined ? workdayHours : 8.0 }};
    var ruleBaseUrl = '/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/settings/leave-policies/{{ leaveType['public_id'] is defined ? leaveType['public_id'] : '' }}/rules';
    var ruleData = {};
    {% if allowanceRules is defined %}
    {% for rule in allowanceRules %}
    ruleData['{{ rule['public_id'] }}'] = {
        position_id: {{ rule['position_id'] is empty ? 'null' : rule['position_id'] }},
        job_level: '{{ rule['job_level'] is empty ? "" : rule['job_level'] }}',
        min_tenure_months: {{ rule['min_tenure_months'] is empty ? 'null' : rule['min_tenure_months'] }},
        max_tenure_months: {{ rule['max_tenure_months'] is empty ? 'null' : rule['max_tenure_months'] }},
        allowance_minutes: {{ rule['allowance_minutes'] }},
        priority: {{ rule['priority'] }},
        is_active: {{ rule['is_active'] }}
    };
    {% endfor %}
    {% endif %}

    // Create a hidden form appended to <body> and submit it (avoids nested <form> tags)
    function submitHiddenForm(action, fields) {
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = action;
        for (var key in fields) {
            if (!fields.hasOwnProperty(key)) continue;
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = fields[key];
            form.appendChild(input);
        }
        document.body.appendChild(form);
        form.submit();
    }

    function submitRuleForm() {
        var mode = document.getElementById('rule_form_mode').value; // 'create' or 'edit'
        var editId = document.getElementById('rule_form_action').value;
        var action = mode === 'edit' && editId
            ? ruleBaseUrl + '/update/' + editId
            : ruleBaseUrl + '/store';

        submitHiddenForm(action, {
            position_id: document.getElementById('rule_position_id').value,
            job_level: document.getElementById('rule_job_level').value,
            min_tenure_months: document.getElementById('rule_min_tenure').value,
            max_tenure_months: document.getElementById('rule_max_tenure').value,
            priority: document.getElementById('rule_priority').value,
            allowance_days: document.getElementById('rule_allowance_days').value,
            allowance_hours: document.getElementById('rule_allowance_hours').value,
            allowance_minutes: document.getElementById('rule_allowance_minutes').value,
            is_active: document.getElementById('rule_is_active').checked ? '1' : '0'
        });
    }

    function deleteRule(publicId) {
        if (!confirm('<?= $this->getDI()->get('locale')->t('settings.leave_policies.confirm_delete_rule') ?>')) return;
        submitHiddenForm(ruleBaseUrl + '/delete/' + publicId, {});
    }

    function recalculateBalances() {
        if (!confirm('<?= $this->getDI()->get('locale')->t('settings.leave_policies.confirm_recalculate') ?>')) return;
        submitHiddenForm(ruleBaseUrl + '/recalculate', {});
    }

    function updateRuleTotal() {
        var days = parseFloat(document.getElementById('rule_allowance_days').value) || 0;
        var hours = parseInt(document.getElementById('rule_allowance_hours').value) || 0;
        var mins = parseInt(document.getElementById('rule_allowance_minutes').value) || 0;
        var total = Math.round(days * workdayHours * 60) + (hours * 60) + mins;
        document.getElementById('rule_total_minutes').textContent = total.toLocaleString();
        document.getElementById('rule_total_days').textContent = (total / (workdayHours * 60)).toFixed(2);
    }

    ['rule_allowance_days', 'rule_allowance_hours', 'rule_allowance_minutes'].forEach(function(id) {
        document.getElementById(id).addEventListener('input', updateRuleTotal);
    });

    function editRule(publicId) {
        var data = ruleData[publicId];
        if (!data) return;

        document.getElementById('rule_position_id').value = data.position_id || 0;
        document.getElementById('rule_job_level').value = data.job_level || '';
        document.getElementById('rule_min_tenure').value = data.min_tenure_months !== null ? data.min_tenure_months : '';
        document.getElementById('rule_max_tenure').value = data.max_tenure_months !== null ? data.max_tenure_months : '';
        document.getElementById('rule_priority').value = data.priority;

        // Convert minutes back to days
        var totalMinutes = data.allowance_minutes;
        var days = (totalMinutes / (workdayHours * 60));
        document.getElementById('rule_allowance_days').value = days.toFixed(2);
        document.getElementById('rule_allowance_hours').value = 0;
        document.getElementById('rule_allowance_minutes').value = 0;

        document.getElementById('rule_is_active').checked = data.is_active == 1;

        // Set edit mode
        document.getElementById('rule_form_mode').value = 'edit';
        document.getElementById('rule_form_action').value = publicId;

        document.getElementById('rule_form_title').textContent = '<?= $this->getDI()->get('locale')->t('settings.leave_policies.edit_rule') ?>';
        document.getElementById('rule_submit_label').textContent = '<?= $this->getDI()->get('locale')->t('settings.leave_policies.update_rule') ?>';
        document.getElementById('rule_cancel_btn').classList.remove('hidden');

        updateRuleTotal();
        document.getElementById('allowance-rules').scrollIntoView({ behavior: 'smooth' });
    }

    function cancelEditRule() {
        // Reset to create mode
        document.getElementById('rule_form_mode').value = 'create';
        document.getElementById('rule_form_action').value = '';

        document.getElementById('rule_form_title').textContent = '<?= $this->getDI()->get('locale')->t('settings.leave_policies.add_rule') ?>';
        document.getElementById('rule_submit_label').textContent = '<?= $this->getDI()->get('locale')->t('settings.leave_policies.add_rule_btn') ?>';
        document.getElementById('rule_cancel_btn').classList.add('hidden');

        // Reset form
        document.getElementById('rule_position_id').value = 0;
        document.getElementById('rule_job_level').value = '';
        document.getElementById('rule_min_tenure').value = '';
        document.getElementById('rule_max_tenure').value = '';
        document.getElementById('rule_priority').value = 0;
        document.getElementById('rule_allowance_days').value = 0;
        document.getElementById('rule_allowance_hours').value = 0;
        document.getElementById('rule_allowance_minutes').value = 0;
        document.getElementById('rule_is_active').checked = true;

        updateRuleTotal();
    }

    updateRuleTotal();

    // ─── Leave Mode checkboxes: keep at least one enabled ───
    (function () {
        var cbs = Array.prototype.slice.call(document.querySelectorAll('.leave-mode-cb'));
        cbs.forEach(function (cb) {
            cb.addEventListener('change', function () {
                var anyChecked = cbs.some(function (c) { return c.checked; });
                if (!anyChecked) {
                    // re-check the one the user just tried to uncheck
                    cb.checked = true;
                    alert('<?= $this->getDI()->get('locale')->t('settings.leave_policies.leave_modes_hint') ?>');
                }
            });
        });
    })();
</script>
{% endblock %}
