{% extends 'layouts/admin.volt' %}

{% block content %}
<div class="container mx-auto px-4 lg:px-8 py-8 max-w-5xl">
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-slate-900 notosan">{{ t('help_center.title') }}</h1>
        <p class="text-sm text-slate-500 mt-1 notosan">{{ t('help_center.subtitle') }}</p>
    </div>

    <!-- Getting Started -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 mb-8">
        <h2 class="text-lg font-semibold text-slate-900 mb-3 notosan">{{ t('help_center.getting_started') }}</h2>
        <p class="text-sm text-slate-600 notosan">{{ t('help_center.getting_started_body') }}</p>
    </div>

    <!-- Admin Guide — Detailed guides for Features & Settings -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 mb-8">
        <h2 class="text-lg font-semibold text-slate-900 mb-1 notosan">{{ t('help_center.admin_guide') }}</h2>
        <p class="text-sm text-slate-500 mb-5 notosan">{{ t('help_center.admin_guide_subtitle') }}</p>

        <!-- Features section -->
        <h3 class="text-sm font-semibold text-slate-700 uppercase tracking-wide mb-3 notosan">{{ t('help_center.guide_features') }}</h3>
        <div class="space-y-2 mb-6">
            {% set features = [
                {'icon': 'fa-gauge-high', 'title': 'help_center.guide.dashboard_title', 'desc': 'help_center.guide.dashboard_desc', 'options': [
                    {'label': 'help_center.guide.dashboard_opt_kpis_label', 'desc': 'help_center.guide.dashboard_opt_kpis_desc'},
                    {'label': 'help_center.guide.dashboard_opt_chart_label', 'desc': 'help_center.guide.dashboard_opt_chart_desc'},
                    {'label': 'help_center.guide.dashboard_opt_company_cards_label', 'desc': 'help_center.guide.dashboard_opt_company_cards_desc'},
                    {'label': 'help_center.guide.dashboard_opt_age_brackets_label', 'desc': 'help_center.guide.dashboard_opt_age_brackets_desc'},
                    {'label': 'help_center.guide.dashboard_opt_birthdays_label', 'desc': 'help_center.guide.dashboard_opt_birthdays_desc'},
                    {'label': 'help_center.guide.dashboard_opt_custom_charts_label', 'desc': 'help_center.guide.dashboard_opt_custom_charts_desc'},
                    {'label': 'help_center.guide.dashboard_opt_cross_filter_label', 'desc': 'help_center.guide.dashboard_opt_cross_filter_desc'}
                ], 'info': 'help_center.guide.dashboard_info', 'warning': '', 'steps': [
                    'help_center.guide.dashboard_step1',
                    'help_center.guide.dashboard_step2',
                    'help_center.guide.dashboard_step3',
                    'help_center.guide.dashboard_step4',
                    'help_center.guide.dashboard_step5'
                ]},
                {'icon': 'fa-building', 'title': 'help_center.guide.companies_title', 'desc': 'help_center.guide.companies_desc', 'options': [
                    {'label': 'help_center.guide.companies_opt_name_label', 'desc': 'help_center.guide.companies_opt_name_desc'},
                    {'label': 'help_center.guide.companies_opt_slug_label', 'desc': 'help_center.guide.companies_opt_slug_desc'},
                    {'label': 'help_center.guide.companies_opt_status_label', 'desc': 'help_center.guide.companies_opt_status_desc'},
                    {'label': 'help_center.guide.companies_opt_custom_label', 'desc': 'help_center.guide.companies_opt_custom_desc'}
                ], 'info': 'help_center.guide.companies_info', 'warning': 'help_center.guide.companies_warning', 'steps': [
                    'help_center.guide.companies_step1',
                    'help_center.guide.companies_step2',
                    'help_center.guide.companies_step3',
                    'help_center.guide.companies_step4'
                ]},
                {'icon': 'fa-id-badge', 'title': 'help_center.guide.positions_title', 'desc': 'help_center.guide.positions_desc', 'options': [
                    {'label': 'help_center.guide.positions_opt_jobtitle_label', 'desc': 'help_center.guide.positions_opt_jobtitle_desc'},
                    {'label': 'help_center.guide.positions_opt_department_label', 'desc': 'help_center.guide.positions_opt_department_desc'},
                    {'label': 'help_center.guide.positions_opt_parent_label', 'desc': 'help_center.guide.positions_opt_parent_desc'},
                    {'label': 'help_center.guide.positions_opt_joblevel_label', 'desc': 'help_center.guide.positions_opt_joblevel_desc'},
                    {'label': 'help_center.guide.positions_opt_employment_label', 'desc': 'help_center.guide.positions_opt_employment_desc'}
                ], 'info': 'help_center.guide.positions_info', 'warning': 'help_center.guide.positions_warning', 'steps': [
                    'help_center.guide.positions_step1',
                    'help_center.guide.positions_step2',
                    'help_center.guide.positions_step3'
                ]},
                {'icon': 'fa-sitemap', 'title': 'help_center.guide.org_hierarchy_title', 'desc': 'help_center.guide.org_hierarchy_desc', 'options': [
                    {'label': 'help_center.guide.org_hierarchy_opt_chart_label', 'desc': 'help_center.guide.org_hierarchy_opt_chart_desc'},
                    {'label': 'help_center.guide.org_hierarchy_opt_pending_label', 'desc': 'help_center.guide.org_hierarchy_opt_pending_desc'},
                    {'label': 'help_center.guide.org_hierarchy_opt_expand_label', 'desc': 'help_center.guide.org_hierarchy_opt_expand_desc'}
                ], 'info': '', 'warning': 'help_center.guide.org_hierarchy_warning', 'steps': [
                    'help_center.guide.org_hierarchy_step1',
                    'help_center.guide.org_hierarchy_step2'
                ]},
                {'icon': 'fa-users-line', 'title': 'help_center.guide.emp_hierarchy_title', 'desc': 'help_center.guide.emp_hierarchy_desc', 'options': [
                    {'label': 'help_center.guide.emp_hierarchy_opt_chart_label', 'desc': 'help_center.guide.emp_hierarchy_opt_chart_desc'},
                    {'label': 'help_center.guide.emp_hierarchy_opt_dept_filter_label', 'desc': 'help_center.guide.emp_hierarchy_opt_dept_filter_desc'},
                    {'label': 'help_center.guide.emp_hierarchy_opt_view_toggle_label', 'desc': 'help_center.guide.emp_hierarchy_opt_view_toggle_desc'},
                    {'label': 'help_center.guide.emp_hierarchy_opt_import_label', 'desc': 'help_center.guide.emp_hierarchy_opt_import_desc'},
                    {'label': 'help_center.guide.emp_hierarchy_opt_fullscreen_label', 'desc': 'help_center.guide.emp_hierarchy_opt_fullscreen_desc'}
                ], 'info': 'help_center.guide.emp_hierarchy_info', 'warning': 'help_center.guide.emp_hierarchy_warning', 'steps': [
                    'help_center.guide.emp_hierarchy_step1',
                    'help_center.guide.emp_hierarchy_step2',
                    'help_center.guide.emp_hierarchy_step3'
                ]},
                {'icon': 'fa-users', 'title': 'help_center.guide.employees_title', 'desc': 'help_center.guide.employees_desc', 'options': [
                    {'label': 'help_center.guide.employees_opt_code_label', 'desc': 'help_center.guide.employees_opt_code_desc'},
                    {'label': 'help_center.guide.employees_opt_name_label', 'desc': 'help_center.guide.employees_opt_name_desc'},
                    {'label': 'help_center.guide.employees_opt_contact_label', 'desc': 'help_center.guide.employees_opt_contact_desc'},
                    {'label': 'help_center.guide.employees_opt_dob_label', 'desc': 'help_center.guide.employees_opt_dob_desc'},
                    {'label': 'help_center.guide.employees_opt_salary_label', 'desc': 'help_center.guide.employees_opt_salary_desc'},
                    {'label': 'help_center.guide.employees_opt_onboarded_label', 'desc': 'help_center.guide.employees_opt_onboarded_desc'},
                    {'label': 'help_center.guide.employees_opt_custom_label', 'desc': 'help_center.guide.employees_opt_custom_desc'}
                ], 'info': 'help_center.guide.employees_info', 'warning': 'help_center.guide.employees_warning', 'steps': [
                    'help_center.guide.employees_step1',
                    'help_center.guide.employees_step2',
                    'help_center.guide.employees_step3',
                    'help_center.guide.employees_step4',
                    'help_center.guide.employees_step5',
                    'help_center.guide.employees_step6'
                ]},
                {'icon': 'fa-umbrella-beach', 'title': 'help_center.guide.leave_title', 'desc': 'help_center.guide.leave_desc', 'options': [
                    {'label': 'help_center.guide.leave_opt_type_label', 'desc': 'help_center.guide.leave_opt_type_desc'},
                    {'label': 'help_center.guide.leave_opt_mode_label', 'desc': 'help_center.guide.leave_opt_mode_desc'},
                    {'label': 'help_center.guide.leave_opt_dates_label', 'desc': 'help_center.guide.leave_opt_dates_desc'},
                    {'label': 'help_center.guide.leave_opt_reason_label', 'desc': 'help_center.guide.leave_opt_reason_desc'},
                    {'label': 'help_center.guide.leave_opt_attachment_label', 'desc': 'help_center.guide.leave_opt_attachment_desc'}
                ], 'info': 'help_center.guide.leave_info', 'warning': 'help_center.guide.leave_warning', 'steps': [
                    'help_center.guide.leave_step1',
                    'help_center.guide.leave_step2',
                    'help_center.guide.leave_step3'
                ]},
                {'icon': 'fa-clock', 'title': 'help_center.guide.overtime_title', 'desc': 'help_center.guide.overtime_desc', 'options': [
                    {'label': 'help_center.guide.overtime_opt_type_label', 'desc': 'help_center.guide.overtime_opt_type_desc'},
                    {'label': 'help_center.guide.overtime_opt_dates_label', 'desc': 'help_center.guide.overtime_opt_dates_desc'},
                    {'label': 'help_center.guide.overtime_opt_minutes_label', 'desc': 'help_center.guide.overtime_opt_minutes_desc'},
                    {'label': 'help_center.guide.overtime_opt_reason_label', 'desc': 'help_center.guide.overtime_opt_reason_desc'}
                ], 'info': '', 'warning': 'help_center.guide.overtime_warning', 'steps': [
                    'help_center.guide.overtime_step1',
                    'help_center.guide.overtime_step2',
                    'help_center.guide.overtime_step3'
                ]},
                {'icon': 'fa-chart-bar', 'title': 'help_center.guide.reports_title', 'desc': 'help_center.guide.reports_desc', 'options': [
                    {'label': 'help_center.guide.reports_opt_workforce_label', 'desc': 'help_center.guide.reports_opt_workforce_desc'},
                    {'label': 'help_center.guide.reports_opt_leave_label', 'desc': 'help_center.guide.reports_opt_leave_desc'},
                    {'label': 'help_center.guide.reports_opt_overtime_label', 'desc': 'help_center.guide.reports_opt_overtime_desc'},
                    {'label': 'help_center.guide.reports_opt_turnover_label', 'desc': 'help_center.guide.reports_opt_turnover_desc'},
                    {'label': 'help_center.guide.reports_opt_date_range_label', 'desc': 'help_center.guide.reports_opt_date_range_desc'}
                ], 'info': 'help_center.guide.reports_info', 'warning': '', 'steps': [
                    'help_center.guide.reports_step1',
                    'help_center.guide.reports_step2',
                    'help_center.guide.reports_step3'
                ]}
            ] %}
            {% for guide in features %}
            <details class="group border border-slate-200 rounded-lg overflow-hidden notosan">
                <summary class="flex items-center gap-3 px-4 py-3 cursor-pointer hover:bg-slate-50 transition-colors list-none">
                    <i class="fas {{ guide['icon'] }} w-5 text-center text-slate-500"></i>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-slate-900">{{ t(guide['title']) }}</p>
                        <p class="text-xs text-slate-500">{{ t(guide['desc']) }}</p>
                    </div>
                    <i class="fas fa-chevron-down text-xs text-slate-400 transition-transform group-open:rotate-180"></i>
                </summary>
                <div class="px-4 pb-4 pt-3 border-t border-slate-100 space-y-4">
                    <!-- 1. Brief Description -->
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">{{ t('help_center.guide_description') }}</p>
                        <p class="text-sm text-slate-600">{{ t(guide['desc']) }}</p>
                    </div>
                    <!-- 2. Options -->
                    {% if guide['options'] is defined and guide['options']|length > 0 %}
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">{{ t('help_center.guide_options') }}</p>
                        <div class="space-y-2">
                            {% for opt in guide['options'] %}
                            <div class="flex gap-3 text-sm">
                                <i class="fas fa-circle-dot text-blue-400 text-xs mt-1.5 flex-shrink-0"></i>
                                <div>
                                    <span class="font-medium text-slate-700">{{ t(opt['label']) }}</span>
                                    <span class="text-slate-500"> — {{ t(opt['desc']) }}</span>
                                </div>
                            </div>
                            {% endfor %}
                        </div>
                    </div>
                    {% endif %}
                    <!-- 3. Info Card (examples) -->
                    {% if guide['info'] is defined and guide['info'] != '' %}
                    <div class="flex gap-3 bg-blue-50 border border-blue-200 rounded-lg p-3">
                        <i class="fas fa-lightbulb text-blue-500 flex-shrink-0 mt-0.5"></i>
                        <div>
                            <p class="text-xs font-semibold text-blue-700 uppercase tracking-wide mb-1">{{ t('help_center.guide_info_title') }}</p>
                            <p class="text-sm text-blue-800">{{ t(guide['info']) }}</p>
                        </div>
                    </div>
                    {% endif %}
                    <!-- 4. Warning Card (warnings & best practices) -->
                    {% if guide['warning'] is defined and guide['warning'] != '' %}
                    <div class="flex gap-3 bg-amber-50 border border-amber-200 rounded-lg p-3">
                        <i class="fas fa-triangle-exclamation text-amber-500 flex-shrink-0 mt-0.5"></i>
                        <div>
                            <p class="text-xs font-semibold text-amber-700 uppercase tracking-wide mb-1">{{ t('help_center.guide_warning_title') }}</p>
                            <p class="text-sm text-amber-800">{{ t(guide['warning']) }}</p>
                        </div>
                    </div>
                    {% endif %}
                    <!-- 5. How-to Steps -->
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">{{ t('help_center.guide_steps') }}</p>
                        <ol class="space-y-2">
                            {% for step in guide['steps'] %}
                            <li class="flex gap-3 text-sm text-slate-600">
                                <span class="flex-shrink-0 w-5 h-5 rounded-full bg-blue-100 text-blue-700 text-xs font-bold flex items-center justify-center">{{ loop.index }}</span>
                                <span class="pt-0.5">{{ t(step) }}</span>
                            </li>
                            {% endfor %}
                        </ol>
                    </div>
                </div>
            </details>
            {% endfor %}
        </div>

        <!-- Settings section -->
        <h3 class="text-sm font-semibold text-slate-700 uppercase tracking-wide mb-3 notosan">{{ t('help_center.guide_settings') }}</h3>
        <div class="space-y-2 mb-6">
            {% set settingsGuides = [
                {'icon': 'fa-building', 'title': 'help_center.guide.company_settings_title', 'desc': 'help_center.guide.company_settings_desc', 'options': [
                    {'label': 'help_center.guide.company_settings_opt_name_label', 'desc': 'help_center.guide.company_settings_opt_name_desc'},
                    {'label': 'help_center.guide.company_settings_opt_slug_label', 'desc': 'help_center.guide.company_settings_opt_slug_desc'},
                    {'label': 'help_center.guide.company_settings_opt_status_label', 'desc': 'help_center.guide.company_settings_opt_status_desc'}
                ], 'info': '', 'warning': 'help_center.guide.company_settings_warning', 'steps': [
                    'help_center.guide.company_settings_step1',
                    'help_center.guide.company_settings_step2'
                ]},
                {'icon': 'fa-shield-halved', 'title': 'help_center.guide.iam_title', 'desc': 'help_center.guide.iam_desc', 'options': [
                    {'label': 'help_center.guide.iam_opt_name_label', 'desc': 'help_center.guide.iam_opt_name_desc'},
                    {'label': 'help_center.guide.iam_opt_email_label', 'desc': 'help_center.guide.iam_opt_email_desc'},
                    {'label': 'help_center.guide.iam_opt_role_label', 'desc': 'help_center.guide.iam_opt_role_desc'},
                    {'label': 'help_center.guide.iam_opt_companies_label', 'desc': 'help_center.guide.iam_opt_companies_desc'}
                ], 'info': 'help_center.guide.iam_info', 'warning': 'help_center.guide.iam_warning', 'steps': [
                    'help_center.guide.iam_step1',
                    'help_center.guide.iam_step2',
                    'help_center.guide.iam_step3',
                    'help_center.guide.iam_step4'
                ]},
                {'icon': 'fa-sitemap', 'title': 'help_center.guide.custom_attributes_title', 'desc': 'help_center.guide.custom_attributes_desc', 'options': [
                    {'label': 'help_center.guide.custom_attributes_opt_name_label', 'desc': 'help_center.guide.custom_attributes_opt_name_desc'},
                    {'label': 'help_center.guide.custom_attributes_opt_entity_label', 'desc': 'help_center.guide.custom_attributes_opt_entity_desc'},
                    {'label': 'help_center.guide.custom_attributes_opt_type_label', 'desc': 'help_center.guide.custom_attributes_opt_type_desc'},
                    {'label': 'help_center.guide.custom_attributes_opt_required_label', 'desc': 'help_center.guide.custom_attributes_opt_required_desc'},
                    {'label': 'help_center.guide.custom_attributes_opt_list_label', 'desc': 'help_center.guide.custom_attributes_opt_list_desc'},
                    {'label': 'help_center.guide.custom_attributes_opt_dashboard_label', 'desc': 'help_center.guide.custom_attributes_opt_dashboard_desc'}
                ], 'info': 'help_center.guide.custom_attributes_info', 'warning': 'help_center.guide.custom_attributes_warning', 'steps': [
                    'help_center.guide.custom_attributes_step1',
                    'help_center.guide.custom_attributes_step2',
                    'help_center.guide.custom_attributes_step3'
                ]},
                {'icon': 'fa-layer-group', 'title': 'help_center.guide.job_levels_title', 'desc': 'help_center.guide.job_levels_desc', 'options': [
                    {'label': 'help_center.guide.job_levels_opt_code_label', 'desc': 'help_center.guide.job_levels_opt_code_desc'},
                    {'label': 'help_center.guide.job_levels_opt_name_label', 'desc': 'help_center.guide.job_levels_opt_name_desc'},
                    {'label': 'help_center.guide.job_levels_opt_category_label', 'desc': 'help_center.guide.job_levels_opt_category_desc'}
                ], 'info': 'help_center.guide.job_levels_info', 'warning': '', 'steps': [
                    'help_center.guide.job_levels_step1',
                    'help_center.guide.job_levels_step2'
                ]},
                {'icon': 'fa-flag', 'title': 'help_center.guide.milestone_types_title', 'desc': 'help_center.guide.milestone_types_desc', 'options': [
                    {'label': 'help_center.guide.milestone_types_opt_name_label', 'desc': 'help_center.guide.milestone_types_opt_name_desc'},
                    {'label': 'help_center.guide.milestone_types_opt_category_label', 'desc': 'help_center.guide.milestone_types_opt_category_desc'}
                ], 'info': 'help_center.guide.milestone_types_info', 'warning': '', 'steps': [
                    'help_center.guide.milestone_types_step1',
                    'help_center.guide.milestone_types_step2'
                ]},
                {'icon': 'fa-umbrella-beach', 'title': 'help_center.guide.leave_management_title', 'desc': 'help_center.guide.leave_management_desc', 'options': [
                    {'label': 'help_center.guide.leave_management_opt_type_label', 'desc': 'help_center.guide.leave_management_opt_type_desc'},
                    {'label': 'help_center.guide.leave_management_opt_allowance_label', 'desc': 'help_center.guide.leave_management_opt_allowance_desc'},
                    {'label': 'help_center.guide.leave_management_opt_yearend_label', 'desc': 'help_center.guide.leave_management_opt_yearend_desc'},
                    {'label': 'help_center.guide.leave_management_opt_workday_label', 'desc': 'help_center.guide.leave_management_opt_workday_desc'}
                ], 'info': 'help_center.guide.leave_management_info', 'warning': 'help_center.guide.leave_management_warning', 'steps': [
                    'help_center.guide.leave_management_step1',
                    'help_center.guide.leave_management_step2',
                    'help_center.guide.leave_management_step3'
                ]},
                {'icon': 'fa-clock', 'title': 'help_center.guide.overtime_management_title', 'desc': 'help_center.guide.overtime_management_desc', 'options': [
                    {'label': 'help_center.guide.overtime_management_opt_type_label', 'desc': 'help_center.guide.overtime_management_opt_type_desc'},
                    {'label': 'help_center.guide.overtime_management_opt_multiplier_label', 'desc': 'help_center.guide.overtime_management_opt_multiplier_desc'},
                    {'label': 'help_center.guide.overtime_management_opt_compensation_label', 'desc': 'help_center.guide.overtime_management_opt_compensation_desc'}
                ], 'info': 'help_center.guide.overtime_management_info', 'warning': '', 'steps': [
                    'help_center.guide.overtime_management_step1',
                    'help_center.guide.overtime_management_step2'
                ]},
                {'icon': 'fa-language', 'title': 'help_center.guide.localization_title', 'desc': 'help_center.guide.localization_desc', 'options': [
                    {'label': 'help_center.guide.localization_opt_languages_label', 'desc': 'help_center.guide.localization_opt_languages_desc'},
                    {'label': 'help_center.guide.localization_opt_switching_label', 'desc': 'help_center.guide.localization_opt_switching_desc'},
                    {'label': 'help_center.guide.localization_opt_translations_label', 'desc': 'help_center.guide.localization_opt_translations_desc'}
                ], 'info': '', 'warning': 'help_center.guide.localization_warning', 'steps': [
                    'help_center.guide.localization_step1',
                    'help_center.guide.localization_step2',
                    'help_center.guide.localization_step3'
                ]},
                {'icon': 'fa-calendar-days', 'title': 'help_center.guide.company_holidays_title', 'desc': 'help_center.guide.company_holidays_desc', 'options': [
                    {'label': 'help_center.guide.company_holidays_opt_year_label', 'desc': 'help_center.guide.company_holidays_opt_year_desc'},
                    {'label': 'help_center.guide.company_holidays_opt_calendar_label', 'desc': 'help_center.guide.company_holidays_opt_calendar_desc'},
                    {'label': 'help_center.guide.company_holidays_opt_mark_label', 'desc': 'help_center.guide.company_holidays_opt_mark_desc'},
                    {'label': 'help_center.guide.company_holidays_opt_unmark_label', 'desc': 'help_center.guide.company_holidays_opt_unmark_desc'},
                    {'label': 'help_center.guide.company_holidays_opt_name_label', 'desc': 'help_center.guide.company_holidays_opt_name_desc'}
                ], 'info': 'help_center.guide.company_holidays_info', 'warning': 'help_center.guide.company_holidays_warning', 'steps': [
                    'help_center.guide.company_holidays_step1',
                    'help_center.guide.company_holidays_step2',
                    'help_center.guide.company_holidays_step3'
                ]},
                {'icon': 'fa-clipboard-list', 'title': 'help_center.guide.audit_log_title', 'desc': 'help_center.guide.audit_log_desc', 'options': [
                    {'label': 'help_center.guide.audit_log_opt_activity_label', 'desc': 'help_center.guide.audit_log_opt_activity_desc'},
                    {'label': 'help_center.guide.audit_log_opt_actor_label', 'desc': 'help_center.guide.audit_log_opt_actor_desc'},
                    {'label': 'help_center.guide.audit_log_opt_timestamp_label', 'desc': 'help_center.guide.audit_log_opt_timestamp_desc'}
                ], 'info': '', 'warning': 'help_center.guide.audit_log_warning', 'steps': [
                    'help_center.guide.audit_log_step1',
                    'help_center.guide.audit_log_step2'
                ]}
            ] %}
            {% for guide in settingsGuides %}
            <details class="group border border-slate-200 rounded-lg overflow-hidden notosan">
                <summary class="flex items-center gap-3 px-4 py-3 cursor-pointer hover:bg-slate-50 transition-colors list-none">
                    <i class="fas {{ guide['icon'] }} w-5 text-center text-slate-500"></i>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-slate-900">{{ t(guide['title']) }}</p>
                        <p class="text-xs text-slate-500">{{ t(guide['desc']) }}</p>
                    </div>
                    <i class="fas fa-chevron-down text-xs text-slate-400 transition-transform group-open:rotate-180"></i>
                </summary>
                <div class="px-4 pb-4 pt-3 border-t border-slate-100 space-y-4">
                    <!-- 1. Brief Description -->
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">{{ t('help_center.guide_description') }}</p>
                        <p class="text-sm text-slate-600">{{ t(guide['desc']) }}</p>
                    </div>
                    <!-- 2. Options -->
                    {% if guide['options'] is defined and guide['options']|length > 0 %}
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">{{ t('help_center.guide_options') }}</p>
                        <div class="space-y-2">
                            {% for opt in guide['options'] %}
                            <div class="flex gap-3 text-sm">
                                <i class="fas fa-circle-dot text-blue-400 text-xs mt-1.5 flex-shrink-0"></i>
                                <div>
                                    <span class="font-medium text-slate-700">{{ t(opt['label']) }}</span>
                                    <span class="text-slate-500"> — {{ t(opt['desc']) }}</span>
                                </div>
                            </div>
                            {% endfor %}
                        </div>
                    </div>
                    {% endif %}
                    <!-- 3. Info Card (examples) -->
                    {% if guide['info'] is defined and guide['info'] != '' %}
                    <div class="flex gap-3 bg-blue-50 border border-blue-200 rounded-lg p-3">
                        <i class="fas fa-lightbulb text-blue-500 flex-shrink-0 mt-0.5"></i>
                        <div>
                            <p class="text-xs font-semibold text-blue-700 uppercase tracking-wide mb-1">{{ t('help_center.guide_info_title') }}</p>
                            <p class="text-sm text-blue-800">{{ t(guide['info']) }}</p>
                        </div>
                    </div>
                    {% endif %}
                    <!-- 4. Warning Card (warnings & best practices) -->
                    {% if guide['warning'] is defined and guide['warning'] != '' %}
                    <div class="flex gap-3 bg-amber-50 border border-amber-200 rounded-lg p-3">
                        <i class="fas fa-triangle-exclamation text-amber-500 flex-shrink-0 mt-0.5"></i>
                        <div>
                            <p class="text-xs font-semibold text-amber-700 uppercase tracking-wide mb-1">{{ t('help_center.guide_warning_title') }}</p>
                            <p class="text-sm text-amber-800">{{ t(guide['warning']) }}</p>
                        </div>
                    </div>
                    {% endif %}
                    <!-- 5. How-to Steps -->
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">{{ t('help_center.guide_steps') }}</p>
                        <ol class="space-y-2">
                            {% for step in guide['steps'] %}
                            <li class="flex gap-3 text-sm text-slate-600">
                                <span class="flex-shrink-0 w-5 h-5 rounded-full bg-blue-100 text-blue-700 text-xs font-bold flex items-center justify-center">{{ loop.index }}</span>
                                <span class="pt-0.5">{{ t(step) }}</span>
                            </li>
                            {% endfor %}
                        </ol>
                    </div>
                </div>
            </details>
            {% endfor %}
        </div>

        <!-- Super Admin Privileges -->
        <h3 class="text-sm font-semibold text-slate-700 uppercase tracking-wide mb-3 notosan">{{ t('help_center.guide_super_admin') }}</h3>
        <div class="space-y-2">
            {% set saGuides = [
                {'icon': 'fa-check-double', 'title': 'help_center.guide.position_approval_title', 'desc': 'help_center.guide.position_approval_desc', 'options': [
                    {'label': 'help_center.guide.position_approval_opt_pending_label', 'desc': 'help_center.guide.position_approval_opt_pending_desc'},
                    {'label': 'help_center.guide.position_approval_opt_sign_label', 'desc': 'help_center.guide.position_approval_opt_sign_desc'},
                    {'label': 'help_center.guide.position_approval_opt_threshold_label', 'desc': 'help_center.guide.position_approval_opt_threshold_desc'}
                ], 'info': 'help_center.guide.position_approval_info', 'warning': 'help_center.guide.position_approval_warning', 'steps': [
                    'help_center.guide.position_approval_step1',
                    'help_center.guide.position_approval_step2',
                    'help_center.guide.position_approval_step3'
                ]}
            ] %}
            {% for guide in saGuides %}
            <details class="group border border-slate-200 rounded-lg overflow-hidden notosan">
                <summary class="flex items-center gap-3 px-4 py-3 cursor-pointer hover:bg-slate-50 transition-colors list-none">
                    <i class="fas {{ guide['icon'] }} w-5 text-center text-slate-500"></i>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-slate-900">{{ t(guide['title']) }}</p>
                        <p class="text-xs text-slate-500">{{ t(guide['desc']) }}</p>
                    </div>
                    <i class="fas fa-chevron-down text-xs text-slate-400 transition-transform group-open:rotate-180"></i>
                </summary>
                <div class="px-4 pb-4 pt-3 border-t border-slate-100 space-y-4">
                    <!-- 1. Brief Description -->
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">{{ t('help_center.guide_description') }}</p>
                        <p class="text-sm text-slate-600">{{ t(guide['desc']) }}</p>
                    </div>
                    <!-- 2. Options -->
                    {% if guide['options'] is defined and guide['options']|length > 0 %}
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">{{ t('help_center.guide_options') }}</p>
                        <div class="space-y-2">
                            {% for opt in guide['options'] %}
                            <div class="flex gap-3 text-sm">
                                <i class="fas fa-circle-dot text-amber-400 text-xs mt-1.5 flex-shrink-0"></i>
                                <div>
                                    <span class="font-medium text-slate-700">{{ t(opt['label']) }}</span>
                                    <span class="text-slate-500"> — {{ t(opt['desc']) }}</span>
                                </div>
                            </div>
                            {% endfor %}
                        </div>
                    </div>
                    {% endif %}
                    <!-- 3. Info Card (examples) -->
                    {% if guide['info'] is defined and guide['info'] != '' %}
                    <div class="flex gap-3 bg-blue-50 border border-blue-200 rounded-lg p-3">
                        <i class="fas fa-lightbulb text-blue-500 flex-shrink-0 mt-0.5"></i>
                        <div>
                            <p class="text-xs font-semibold text-blue-700 uppercase tracking-wide mb-1">{{ t('help_center.guide_info_title') }}</p>
                            <p class="text-sm text-blue-800">{{ t(guide['info']) }}</p>
                        </div>
                    </div>
                    {% endif %}
                    <!-- 4. Warning Card (warnings & best practices) -->
                    {% if guide['warning'] is defined and guide['warning'] != '' %}
                    <div class="flex gap-3 bg-amber-50 border border-amber-200 rounded-lg p-3">
                        <i class="fas fa-triangle-exclamation text-amber-500 flex-shrink-0 mt-0.5"></i>
                        <div>
                            <p class="text-xs font-semibold text-amber-700 uppercase tracking-wide mb-1">{{ t('help_center.guide_warning_title') }}</p>
                            <p class="text-sm text-amber-800">{{ t(guide['warning']) }}</p>
                        </div>
                    </div>
                    {% endif %}
                    <!-- 5. How-to Steps -->
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">{{ t('help_center.guide_steps') }}</p>
                        <ol class="space-y-2">
                            {% for step in guide['steps'] %}
                            <li class="flex gap-3 text-sm text-slate-600">
                                <span class="flex-shrink-0 w-5 h-5 rounded-full bg-amber-100 text-amber-700 text-xs font-bold flex items-center justify-center">{{ loop.index }}</span>
                                <span class="pt-0.5">{{ t(step) }}</span>
                            </li>
                            {% endfor %}
                        </ol>
                    </div>
                </div>
            </details>
            {% endfor %}
        </div>
    </div>

    <!-- Role & Permissions Reference -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 mb-8">
        <h2 class="text-lg font-semibold text-slate-900 mb-4 notosan">{{ t('help_center.roles_title') }}</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-700 font-semibold border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3 notosan">{{ t('help_center.role') }}</th>
                        <th class="px-4 py-3 notosan">{{ t('help_center.capabilities') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr>
                        <td class="px-4 py-3 font-medium text-slate-900 notosan">{{ t('help_center.role_super_admin') }}</td>
                        <td class="px-4 py-3 text-slate-600 notosan">{{ t('help_center.role_super_admin_desc') }}</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium text-slate-900 notosan">{{ t('help_center.role_admin') }}</td>
                        <td class="px-4 py-3 text-slate-600 notosan">{{ t('help_center.role_admin_desc') }}</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-medium text-slate-900 notosan">{{ t('help_center.role_member') }}</td>
                        <td class="px-4 py-3 text-slate-600 notosan">{{ t('help_center.role_member_desc') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Support -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
        <h2 class="text-lg font-semibold text-slate-900 mb-3 notosan">{{ t('help_center.support_title') }}</h2>
        <p class="text-sm text-slate-600 notosan">{{ t('help_center.support_body') }}</p>
    </div>
</div>
{% endblock %}
