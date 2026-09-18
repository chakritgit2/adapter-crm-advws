{% extends 'layouts/admin.volt' %}

{% block content %}
<div class="container mx-auto px-4 lg:px-8 py-8">
    <div class="max-w-2xl mx-auto">
        <div class="mb-6">
            <a href="/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/settings/admin-users"
                class="text-sm text-slate-500 hover:text-slate-700 inline-flex items-center gap-1 mb-4">
                <i class="fas fa-arrow-left"></i>
                {{ t('settings.admin_users.back') }}
            </a>
            <h1 class="text-2xl font-bold text-slate-900 notosan">
                {% if mode == 'edit' %}{{ t('common.edit') }}{% else %}{{ t('settings.admin_users.new_label') }}{% endif %} {{ t('settings.admin_users.admin_user') }}
            </h1>
            <p class="text-sm text-slate-500 mt-1 notosan">
                {% if mode == 'edit' %}{{ t('settings.admin_users.edit_subtitle') }}{% else %}{{ t('settings.admin_users.create_subtitle') }}{% endif %}
            </p>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <form method="POST"
                action="{% if mode == 'edit' %}/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/settings/admin-users/update/{{ adminUser['id'] }}{% else %}/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/settings/admin-users/store{% endif %}"
                class="space-y-6">

                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700 mb-1 notosan">
                        {{ t('settings.admin_users.full_name') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="name" name="name" required maxlength="255"
                        value="{% if mode == 'edit' %}{{ adminUser['name'] }}{% endif %}"
                        class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm"
                        placeholder="{{ t('settings.admin_users.name_placeholder') }}">
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700 mb-1 notosan">
                        {{ t('settings.admin_users.email_address') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="email" id="email" name="email" required maxlength="255"
                        value="{% if mode == 'edit' %}{{ adminUser['email'] }}{% endif %}"
                        class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm"
                        placeholder="{{ t('settings.admin_users.email_placeholder') }}">
                    <p class="text-xs text-slate-500 mt-1">{{ t('settings.admin_users.email_hint') }}</p>
                </div>

                <div>
                    <label for="role" class="block text-sm font-medium text-slate-700 mb-1 notosan">
                        {{ t('settings.admin_users.role') }} <span class="text-red-500">*</span>
                    </label>
                    <select id="role" name="role" required
                        class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm bg-white">
                        <option value="">{{ t('settings.admin_users.select_role') }}</option>
                        <option value="Super Admin" {% if mode == 'edit' and adminUser['role'] == 'Super Admin' %}selected{% endif %}>Super Admin</option>
                        <option value="Admin" {% if mode == 'edit' and adminUser['role'] == 'Admin' %}selected{% endif %}>Admin</option>
                        <option value="Member" {% if mode == 'edit' and adminUser['role'] == 'Member' %}selected{% endif %}>Member</option>
                    </select>
                    <p class="text-xs text-slate-500 mt-1">{{ t('settings.admin_users.role_hint') }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2 notosan">
                        {{ t('settings.admin_users.company_access') }}
                    </label>
                    <div class="border border-slate-300 rounded-lg p-3 max-h-60 overflow-y-auto bg-white">
                        {% if companies|length > 0 %}
                        <div class="space-y-2">
                            {% for company in companies %}
                            <label class="flex items-center gap-3 p-2 rounded hover:bg-slate-50 cursor-pointer">
                                <input type="checkbox" name="company_ids[]" value="{{ company['id'] }}"
                                    {% if mode == 'edit' and company['id'] in selectedCompanyIds %}checked{% endif %}
                                    class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500">
                                <span class="text-sm text-slate-700">{{ company['name'] }}</span>
                            </label>
                            {% endfor %}
                        </div>
                        {% else %}
                        <p class="text-sm text-slate-500 text-center py-4">{{ t('settings.admin_users.no_companies') }}</p>
                        {% endif %}
                    </div>
                    <p class="text-xs text-slate-500 mt-1">{{ t('settings.admin_users.company_access_hint') }}</p>
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                    <a href="/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/settings/admin-users"
                        class="px-4 py-2 text-sm font-medium text-slate-600 bg-slate-100 rounded-lg hover:bg-slate-200 transition-colors">
                        {{ t('common.cancel') }}
                    </a>
                    <button type="submit"
                        class="px-6 py-2 cursor-pointer text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors shadow-sm">
                        {% if mode == 'edit' %}{{ t('settings.admin_users.update') }}{% else %}{{ t('settings.admin_users.create') }}{% endif %}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
{% endblock %}
