{% extends 'layouts/admin.volt' %}

{% block content %}
<div class="container mx-auto px-4 lg:px-8 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="mb-6">
            <a href="/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/settings/languages"
                class="text-sm text-slate-500 hover:text-slate-700 inline-flex items-center gap-1 mb-4">
                <i class="fas fa-arrow-left"></i>
                {{ t('settings.languages.back') }}
            </a>
            <h1 class="text-2xl font-bold text-slate-900 notosan">{{ t('settings.languages.add') }}</h1>
            <p class="text-sm text-slate-500 mt-1 notosan">{{ t('settings.languages.add_subtitle') }}</p>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <form method="POST"
                action="/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/settings/languages/store"
                class="space-y-6">

                <div>
                    <label for="language_code" class="block text-sm font-medium text-slate-700 mb-1 notosan">
                        {{ t('settings.languages.code') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="language_code" name="language_code" required maxlength="10"
                        class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm font-mono"
                        placeholder="{{ t('settings.languages.code_placeholder') }}">
                    <p class="text-xs text-slate-500 mt-1">{{ t('settings.languages.code_hint') }}</p>
                </div>

                <div>
                    <label for="language_name" class="block text-sm font-medium text-slate-700 mb-1 notosan">
                        {{ t('settings.languages.name') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="language_name" name="language_name" required maxlength="50"
                        class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm"
                        placeholder="{{ t('settings.languages.name_placeholder') }}">
                    <p class="text-xs text-slate-500 mt-1">{{ t('settings.languages.name_hint') }}</p>
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                    <a href="/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/settings/languages"
                        class="px-4 py-2 text-sm font-medium text-slate-600 bg-slate-100 rounded-lg hover:bg-slate-200 transition-colors">
                        {{ t('common.cancel') }}
                    </a>
                    <button type="submit"
                        class="px-6 py-2 cursor-pointer text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors shadow-sm">
                        {{ t('settings.languages.install') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
{% endblock %}
