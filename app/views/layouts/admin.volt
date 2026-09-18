<!DOCTYPE html>
<html lang="{{ htmlLang|default('th') }}" dir="{{ htmlDir|default('ltr') }}">

<head>
    <meta charset="UTF-8" />
    <meta name="description"
        content="{{config.appDescription}}">
    <link rel="canonical" href="https://hr.advws.com">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ title ~ " - " ~ config.appName }}</title>
    <link rel="stylesheet" href="/css/main.css" />
    <script>
        window.TENANT_BASE_URL = '/{{ currentTenantSlug }}/{{ navCompanySlug }}';
    </script>
    <link rel="apple-touch-icon" sizes="57x57" href="/icons/apple-icon-57x57.png">
    <link rel="apple-touch-icon" sizes="60x60" href="/icons/apple-icon-60x60.png">
    <link rel="apple-touch-icon" sizes="72x72" href="/icons/apple-icon-72x72.png">
    <link rel="apple-touch-icon" sizes="76x76" href="/icons/apple-icon-76x76.png">
    <link rel="apple-touch-icon" sizes="114x114" href="/icons/apple-icon-114x114.png">
    <link rel="apple-touch-icon" sizes="120x120" href="/icons/apple-icon-120x120.png">
    <link rel="apple-touch-icon" sizes="144x144" href="/icons/apple-icon-144x144.png">
    <link rel="apple-touch-icon" sizes="152x152" href="/icons/apple-icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/icons/apple-icon-180x180.png">
    <link rel="icon" type="image/png" sizes="192x192" href="/android-icon-192x192.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/icons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="96x96" href="/icons/favicon-96x96.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/icons/favicon-16x16.png">
    <link rel="manifest" href="/icons/manifest.json">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-TileImage" content="/icons/ms-icon-144x144.png">
    <meta name="theme-color" content="#ffffff">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@400..800&display=swap" rel="stylesheet">
    <link rel="shortcut icon" type="image/image/x-icon" href="favicon.ico?ver=1.1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css" integrity="sha512-DxV+EoADOkOygM4IR9yXP8Sb2qwgidEmeqAEmDKIOfPRQZOWbXCzLC6vjbZyy0vPisbH2SyW27+ddLVCN+OMzQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.dataTables.min.css">

    {% set clarityOn = false %}
    {% if clarityOn == true %}
    <script type="text/javascript">
        (function (c, l, a, r, i, t, y) {
            c[a] = c[a] || function () { (c[a].q = c[a].q || []).push(arguments) };
            t = l.createElement(r); t.async = 1; t.src = "https://www.clarity.ms/tag/" + i;
            y = l.getElementsByTagName(r)[0]; y.parentNode.insertBefore(t, y);
        })(window, document, "clarity", "script", "{{ config.clarityKey }}");
    </script>
    {% endif %}

    <style>
        .notosan {
            font-family: 'Noto Sans Thai', sans-serif;
        }
        
        /* Toast notification styles */
        .toast-notification {
            position: fixed;
            top: 1rem;
            left: 50%;
            transform: translateX(-50%);
            z-index: 9999;
            min-width: 300px;
            max-width: 90%;
            padding: 1rem 1.5rem;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            display: flex;
            align-items: center;
            justify-content: space-between;
            animation: slideIn 0.3s ease-out forwards;
            opacity: 0;
            margin-bottom: 0.5rem;
        }

        .toast-notification.success {
            background-color: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        .toast-notification.error {
            background-color: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }

        .toast-notification.warning {
            background-color: #fef3c7;
            color: #92400e;
            border-left: 4px solid #f59e0b;
        }

        .toast-notification.info {
            background-color: #dbeafe;
            color: #1e40af;
            border-left: 4px solid #3b82f6;
        }

        .toast-close {
            background: none;
            border: none;
            color: inherit;
            cursor: pointer;
            padding: 0.25rem;
            margin-left: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background-color 0.2s;
        }

        .toast-close:hover {
            background-color: rgba(0, 0, 0, 0.1);
        }

        .toast-content {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex: 1;
        }

        html {
            scroll-behavior: smooth;
        }


        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translate(-50%, -20px);
            }
            to {
                opacity: 1;
                transform: translate(-50%, 0);
            }
        }

        @keyframes slideOut {
            to {
                opacity: 0;
                transform: translate(-50%, -20px);
            }
        }

    </style>
</head>

<body class="bg-slate-50 text-slate-800">

    <div class="min-h-screen flex">
        {% if not mustChange %}
        <!-- Sidebar -->
        <aside id="sidebar" class="hidden lg:flex flex-col w-64 bg-white border-r border-slate-200 h-screen sticky top-0 print:hidden transition-all duration-300">
            <div class="flex items-center justify-left h-20 px-4 border-b border-slate-200">
                <img src="/img/logo.png" alt="HR Logo" class="h-10">
                <div class="w-px h-6 bg-slate-200"></div>
                <a href="/{{ currentTenantSlug }}/{{ navCompanySlug }}/dashboard" class="text-xl ml-2 font-bold text-slate-900 notosan">
                    <p>{{config.appName}}</p>
                </a>
                <div class="flex items-center gap-2">
                    <button id="sidebar-desktop-toggle" type="button" class="hidden lg:block p-2 text-slate-500 hover:text-slate-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path></svg>
                    </button>
                    <button id="sidebar-close" type="button" class="lg:hidden p-2 text-slate-500 hover:text-slate-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
            </div>
            <nav class="flex-1 overflow-y-auto py-4">
                {% set authUser = session.get('auth') %}
                {% set isSuperAdminNav = authUser is defined and authUser is iterable and authUser['role'] is defined and authUser['role'] == 'Super Admin' %}
                <ul class="space-y-1 px-3">
                    {# ---- General ---- #}
                    <li class="px-3 pt-2 pb-1 text-xs font-semibold uppercase tracking-wide text-slate-400 notosan">{{ t('nav.category.general') }}</li>
                    <li>
                        <a href="/{{ currentTenantSlug }}" class="flex items-center gap-3 px-3 py-2 text-sm font-medium text-slate-700 rounded-lg hover:bg-slate-100 notosan">
                            <i class="fas fa-home w-5 text-center text-slate-500"></i>
                            {{ t('nav.dashboard') }}
                        </a>
                    </li>
                    <li>
                        <a href="/dashboard/companies" class="flex items-center gap-3 px-3 py-2 text-sm font-medium text-slate-700 rounded-lg hover:bg-slate-100 notosan">
                            <i class="fas fa-building w-5 text-center text-slate-500"></i>
                            {{ t('nav.companies') }}
                        </a>
                    </li>

                    {# ---- System ---- #}
                    <li class="px-3 pt-4 pb-1 text-xs font-semibold uppercase tracking-wide text-slate-400 notosan">{{ t('nav.category.system') }}</li>
                    <li>
                        <a href="/{{ currentTenantSlug ? currentTenantSlug ~ '/' : '' }}{{ navCompanySlug ? navCompanySlug ~ '/' : '' }}dashboard/reports" class="flex items-center gap-3 px-3 py-2 text-sm font-medium text-slate-700 rounded-lg hover:bg-slate-100 notosan">
                            <i class="fas fa-chart-bar w-5 text-center text-slate-500"></i>
                            {{ t('nav.reports') }}
                        </a>
                    </li>
                    <li>
                        <a href="/{{ currentTenantSlug ? currentTenantSlug ~ '/' : '' }}{{ navCompanySlug ? navCompanySlug ~ '/' : '' }}dashboard/settings" class="flex items-center gap-3 px-3 py-2 text-sm font-medium text-slate-700 rounded-lg hover:bg-slate-100 notosan">
                            <i class="fas fa-cog w-5 text-center text-slate-500"></i>
                            {{ t('nav.settings') }}
                        </a>
                    </li>
                    {% if isSuperAdminNav %}
                    <li>
                        <a href="/{{ currentTenantSlug ? currentTenantSlug ~ '/' : '' }}{{ navCompanySlug ? navCompanySlug ~ '/' : '' }}dashboard/help-center" class="flex items-center gap-3 px-3 py-2 text-sm font-medium text-slate-700 rounded-lg hover:bg-slate-100 notosan">
                            <i class="fas fa-circle-question w-5 text-center text-slate-500"></i>
                            {{ t('nav.help_center') }}
                        </a>
                    </li>
                    <li>
                        <a href="/{{ currentTenantSlug ? currentTenantSlug ~ '/' : '' }}{{ navCompanySlug ? navCompanySlug ~ '/' : '' }}adapter" class="flex items-center gap-3 px-3 py-2 text-sm font-medium text-slate-700 rounded-lg hover:bg-slate-100 notosan">
                            <i class="fas fa-plug w-5 text-center text-slate-500"></i>
                            Data Adapter
                        </a>
                    </li>
                    {% endif %}
                </ul>
            </nav>
        </aside>
        {% endif %}

        <div class="flex flex-col flex-1 min-w-0">
            <header class="bg-white border-b border-slate-200 sticky top-0 z-50 print:hidden">
            <nav class="px-4 flex justify-between items-center h-20">
                <div class="flex items-center gap-3">
                    {% if not mustChange %}
                    <button id="sidebar-toggle" type="button" class="p-2 -ml-2 text-slate-500 hover:text-slate-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    </button>
                    {% endif %}
                </div>

                <div class="flex items-center gap-5">
                    <div class="flex items-center gap-3">

                        <ul class="hidden md:flex items-center gap-2">
                            <li>
                                <div class="relative group">
                                    <div class="flex items-center gap-2 focus:outline-none">
                                        <div class="text-sm text-end cursor-pointer">
                                                <p class="text-sm font-medium text-slate-900 notosan">
                                                    <span class="ml-auto inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700">0</span>
                                                    {{ t('header.alerts') }}
                                                </p>
                                        </div>
                                        <svg width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M4.29289 8.29289C4.68342 7.90237 5.31658 7.90237 5.70711 8.29289L12 14.5858L18.2929 8.29289C18.6834 7.90237 19.3166 7.90237 19.7071 8.29289C20.0976 8.68342 20.0976 9.31658 19.7071 9.70711L12.7071 16.7071C12.3166 17.0976 11.6834 17.0976 11.2929 16.7071L4.29289 9.70711C3.90237 9.31658 3.90237 8.68342 4.29289 8.29289Z" fill="#000000"/>
                                        </svg>
                                    </div>
                                    <div class="absolute right-0 w-[200px] bg-white rounded-md shadow-lg py-1 z-10 hidden group-hover:block">

                                    </div>
                                </div>
                            </li>
                            <li><div class="w-px h-6 bg-slate-200"></div></li>
                            <li>
                                <form id="languageSwitcherForm" action="/dashboard/set-language" method="POST">
                                    <input type="hidden" name="language_code" id="languageCodeInput" value="{{ activeLanguage|default('th') }}">
                                    {# Active language code for toggle highlighting #}
                                    {% set activeLangCode = activeLanguage|default('th') %}
                                    <div class="inline-flex items-center gap-1 px-1.5 py-1 rounded-lg bg-slate-100 border border-slate-200">
                                        {# Thai (default UI locale) #}
                                        <button type="button" aria-label="{{ t('layout.language.thai_default') }}"
                                            onclick="document.getElementById('languageCodeInput').value='th';document.getElementById('languageSwitcherForm').submit()"
                                            class="px-2 py-0.5 rounded-md text-xs font-medium transition-colors notosan {{ activeLangCode == 'th' ? 'bg-blue-600 text-white' : 'text-slate-500 hover:bg-slate-200' }}">
                                            ไทย
                                        </button>
                                        {# English UI locale #}
                                        <button type="button" aria-label="{{ t('layout.language.english') }}"
                                            onclick="document.getElementById('languageCodeInput').value='en';document.getElementById('languageSwitcherForm').submit()"
                                            class="px-2 py-0.5 rounded-md text-xs font-medium transition-colors {{ activeLangCode == 'en' ? 'bg-blue-600 text-white' : 'text-slate-500 hover:bg-slate-200' }}">
                                            EN
                                        </button>
                                        {# Content-translation locales (Layer B) #}
                                        {% if tenantLanguages is defined %}
                                            {% for lang in tenantLanguages %}
                                                {% if lang['is_active'] and lang['language_code'] not in ['th', 'en'] %}
                                                <button type="button" aria-label="{{ lang['language_name'] }}"
                                                    onclick="document.getElementById('languageCodeInput').value='{{ lang['language_code'] }}';document.getElementById('languageSwitcherForm').submit()"
                                                    class="px-2 py-0.5 rounded-md text-xs font-medium transition-colors notosan {{ activeLangCode == lang['language_code'] ? 'bg-blue-600 text-white' : 'text-slate-500 hover:bg-slate-200' }}">
                                                    {{ lang['language_name'] }}
                                                </button>
                                                {% endif %}
                                            {% endfor %}
                                        {% endif %}
                                    </div>
                                </form>
                            </li>
                        </ul>

                        {% if isCompaniesPage or isProfilePage %}
                        <div class="relative group" title="Organization switching is disabled on this page">
                            <div class="flex items-center gap-2 focus:outline-none cursor-not-allowed opacity-60">
                                <div class="text-sm text-end">
                                    <p class="font-semibold text-slate-900 notosan">{% if isProfilePage %}{{ t('header.all_companies') }}{% elseif currentCompany is not empty %}{{ currentCompany.name }}{% else %}{{ t('header.all_companies') }}{% endif %}</p>
                                    <div class="font-light text-slate-500 text-xs">{{ t('header.switch_organization') }}</div>
                                </div>
                                <div class="w-px h-6 bg-slate-300"></div>
                            </div>
                        </div>
                        {% else %}
                        <div class="relative group">
                            <div class="flex items-center gap-2 focus:outline-none cursor-pointer">
                                <div class="text-sm text-end">
                                    <p class="font-semibold text-slate-900 notosan">{% if currentCompany is not empty %}{{ currentCompany.name }}{% else %}{{ t('header.all_companies') }}{% endif %}</p>
                                    <div class="font-light text-slate-500 text-xs">{{ t('header.switch_organization') }}</div>
                                </div>
                                <svg width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M4.29289 8.29289C4.68342 7.90237 5.31658 7.90237 5.70711 8.29289L12 14.5858L18.2929 8.29289C18.6834 7.90237 19.3166 7.90237 19.7071 8.29289C20.0976 8.68342 20.0976 9.31658 19.7071 9.70711L12.7071 16.7071C12.3166 17.0976 11.6834 17.0976 11.2929 16.7071L4.29289 9.70711C3.90237 9.31658 3.90237 8.68342 4.29289 8.29289Z" fill="#000000"/>
                                </svg>
                                <div class="w-px h-6 bg-slate-200"></div>
                            </div>
                            <div class="absolute right-0 w-72 bg-white rounded-md shadow-lg py-1 z-10 hidden group-hover:block">
                                {% if currentCompany is empty %}
                                <a href="/dashboard/companies" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-100 border-b border-slate-100">
                                    <i class="fas fa-th-list mr-2 text-slate-400"></i> {{ t('header.all_companies') }}
                                </a>
                                {% endif %}
                                {% for company in userCompanies %}
                                {% set isDefault = defaultCompanyId is defined and defaultCompanyId is not null and defaultCompanyId == company['id'] %}
                                <div class="flex items-center justify-between hover:bg-slate-100">
                                    <a href="/{{ currentTenantSlug }}/{{ company['slug'] }}{{ currentPathSuffix }}" class="flex-1 block px-4 py-2 text-sm {{ isDefault ? 'font-medium' : '' }} text-slate-700 truncate">
                                        <i class="fas fa-building mr-2 text-slate-400"></i> {{ company['name'] }}
                                    </a>
                                    <div class="set-default-company-action shrink-0 pr-4" data-company-id="{{ company['id'] }}" data-is-default="{{ isDefault ? '1' : '0' }}">
                                        <span class="default-indicator inline-flex items-center gap-1" style="{{ isDefault ? '' : 'display:none;' }}">
                                            <svg class="w-3.5 h-3.5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-blue-50 text-blue-700">{{ t('header.default_badge') }}</span>
                                        </span>
                                        <button type="button" class="set-default-company-btn text-[10px] text-slate-400 hover:text-blue-600 transition-colors" style="{{ isDefault ? 'display:none;' : '' }}">{{ t('header.set_default') }}</button>
                                    </div>
                                </div>
                                {% endfor %}
                            </div>
                        </div>
                        {% endif %}
                        
                        {% if user %}
                            {% if user.id > 0 %}
                            <div class="relative group">
                                <div class="flex items-center gap-2 focus:outline-none">
                                    <div class="text-sm text-end cursor-pointer">
                                            <p class="font-semibold text-slate-900 notosan">{{ t('header.welcome', ['name': user.name]) }}</p>
                                            <div class="font-light text-slate-500">{{ user.email }}</div>
                                    </div>
                                    <svg width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M4.29289 8.29289C4.68342 7.90237 5.31658 7.90237 5.70711 8.29289L12 14.5858L18.2929 8.29289C18.6834 7.90237 19.3166 7.90237 19.7071 8.29289C20.0976 8.68342 20.0976 9.31658 19.7071 9.70711L12.7071 16.7071C12.3166 17.0976 11.6834 17.0976 11.2929 16.7071L4.29289 9.70711C3.90237 9.31658 3.90237 8.68342 4.29289 8.29289Z" fill="#000000"/>
                                    </svg>
                                </div>
                                <div class="absolute right-0 w-48 bg-white rounded-md shadow-lg py-1 z-10 hidden group-hover:block">
                                    <!-- <a href="/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/api-credentials" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-100">
                                        <i class="fas fa-cog mr-2"></i> API Credentials
                                    </a>
                                    <a href="/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/webhooks" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-100">
                                        <i class="fas fa-cog mr-2"></i> Webhooks
                                    </a> -->
                                    <a href="/dashboard/profile" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-100">
                                        <i class="fas fa-cog mr-2"></i> {{ t('header.change_password') }}
                                    </a>
                                    <a href="{{ config.loginPath }}/logout" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-100">
                                        <i class="fas fa-sign-out-alt mr-2"></i> {{ t('header.logout') }}
                                    </a>
                                </div>
                            </div>
                            {% else %}
                            <a href="{{ config.loginPath }}?returnUrl={{ base64_encode(request.getScheme() ~ '://' ~ request.getServerName() ~ request.getURI()) }}"
                                class="text-base font-medium text-slate-600 hover:text-blue-600 transition">{{ t('header.login') }}</a>
                            {% endif %}
                        {% else %}
                        <a href="{{ config.loginPath }}?returnUrl={{ base64_encode(request.getScheme() ~ '://' ~ request.getServerName() ~ request.getURI()) }}"
                            class="text-base font-medium text-slate-600 hover:text-blue-600 transition">{{ t('header.login') }}</a>
                        {% endif %}
                    </div>
                </div>
            </nav>
        </header>

            <main class="flex-1">
                {% block content %}{% endblock %}
            </main>

            <footer class="bg-white border-t border-slate-200 mt-12 print:hidden">
            <div class="container mx-auto px-4 lg:px-8 py-6">
                <div class="flex flex-col md:flex-row justify-center items-center gap-6">
                    <div class="text-sm text-slate-500 text-center">
                        &copy;
                        <?= date('Y') ?> {{config.appName}}. {{ t('footer.rights_reserved') }}
                    </div>
                </div>
            </div>
            </footer>
        </div>
    </div>

    <div id="toast-container"
        class="fixed top-0 left-0 right-0 z-50 pointer-events-none flex flex-col items-center pt-4"></div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
    
    <script>
        // Initialize DataTable
        $(document).ready(function() {
            if ($('#portfolioTable').length) {
                $('#portfolioTable').DataTable({
                    responsive: true,
                    pageLength: 25,
                    lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, '<?= $this->getDI()->get('locale')->t('dt.all') ?>']],
                    language: {
                        search: '_INPUT_',
                        searchPlaceholder: '<?= $this->getDI()->get('locale')->t('dt.search_placeholder') ?>',
                        lengthMenu: '<?= $this->getDI()->get('locale')->t('dt.length_menu') ?>',
                        info: '<?= $this->getDI()->get('locale')->t('dt.info') ?>',
                        infoEmpty: '<?= $this->getDI()->get('locale')->t('dt.info_empty') ?>',
                        infoFiltered: '<?= $this->getDI()->get('locale')->t('dt.info_filtered') ?>',
                        paginate: {
                            first: '<?= $this->getDI()->get('locale')->t('dt.first') ?>',
                            last: '<?= $this->getDI()->get('locale')->t('dt.last') ?>',
                            next: '<?= $this->getDI()->get('locale')->t('dt.next') ?>',
                            previous: '<?= $this->getDI()->get('locale')->t('dt.previous') ?>'
                        },
                    },
                    dom: "<'flex flex-col md:flex-row md:items-center md:justify-between'<'mb-4 md:mb-0'f><'mb-4 md:mb-0'p>>" +
                         "<'w-full overflow-x-auto'tr>" +
                         "<'flex flex-col md:flex-row items-center justify-between mt-4'<'mb-4 md:mb-0'i><'mb-4 md:mb-0'p>>",
                    initComplete: function() {
                        // Add custom class to search input
                        $('.dataTables_filter input').addClass('border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent');
                        // Add custom class to length select
                        $('.dataTables_length select').addClass('border border-gray-300 rounded-lg px-3 py-1 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent');
                    }
                });
            }
        });

        document.addEventListener('DOMContentLoaded', function () {
            const toastContainer = document.getElementById('toast-container');

            function createToast(message, type = 'info') {
                const toast = document.createElement('div');
                toast.className = `toast-notification ${type} pointer-events-auto`;

                let icon = '';
                switch (type) {
                    case 'success':
                        icon = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
                        break;
                    case 'error':
                        icon = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>';
                        break;
                    case 'warning':
                        icon = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>';
                        break;
                    default:
                        icon = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
                }

                toast.innerHTML = `
            <div class="toast-content">
                ${icon}
                <span class="text-sm font-medium">${message}</span>
            </div>
            <button type="button" class="toast-close" aria-label="Close">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        `;

                // Auto remove after 5 seconds
                const timer = setTimeout(() => {
                    toast.style.animation = 'slideOut 0.3s ease-out forwards';
                    setTimeout(() => toast.remove(), 300);
                }, 5000);

                // Close button functionality
                const closeButton = toast.querySelector('.toast-close');
                closeButton.addEventListener('click', () => {
                    clearTimeout(timer);
                    toast.style.animation = 'slideOut 0.3s ease-out forwards';
                    setTimeout(() => toast.remove(), 300);
                });

                return toast;
            }

        // Display flash messages
        <?php 
        $flashMessages = $this->flashSession->getMessages();
                if (!empty($flashMessages)):
                    foreach($flashMessages as $type => $messages):
                $jsType = 'info';
                if (in_array($type, ['success', 'error', 'warning', 'info'])) {
                    $jsType = $type;
                }
                foreach($messages as $message): 
        ?>
            const toast = createToast('<?= addslashes($message) ?>', '<?= $jsType ?>');
                toastContainer.appendChild(toast);
        <?php 
                endforeach;
                endforeach;
                endif; 
        ?>
        // Set Default Company (company selector)
        document.querySelectorAll('.set-default-company-btn').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();

                var container = this.closest('.set-default-company-action');
                if (!container || container.dataset.isDefault === '1') return;

                var companyId = container.dataset.companyId;
                var tenantSlug = '{{ currentTenantSlug }}';

                fetch('/' + tenantSlug + '/companies/set-default', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: 'company_id=' + encodeURIComponent(companyId) + '&ajax=1'
                })
                .then(function (resp) { return resp.json(); })
                .then(function (data) {
                    if (data.success) {
                        // Reset all rows to non-default, then mark the selected one
                        document.querySelectorAll('.set-default-company-action').forEach(function (c) {
                            c.dataset.isDefault = '0';
                            var ind = c.querySelector('.default-indicator');
                            var b = c.querySelector('.set-default-company-btn');
                            if (c === container) {
                                c.dataset.isDefault = '1';
                                if (ind) ind.style.display = '';
                                if (b) b.style.display = 'none';
                            } else {
                                if (ind) ind.style.display = 'none';
                                if (b) b.style.display = '';
                            }
                        });

                        var toast = createToast(data.message, 'success');
                        toastContainer.appendChild(toast);
                    } else {
                        var toast = createToast(data.message, 'error');
                        toastContainer.appendChild(toast);
                    }
                })
                .catch(function () {
                    var toast = createToast("{{ t('flash.default_company_error') }}", 'error');
                    toastContainer.appendChild(toast);
                });
            });
        });
    });
        // Sidebar toggle
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebar-toggle');
        const sidebarClose = document.getElementById('sidebar-close');
        const sidebarDesktopToggle = document.getElementById('sidebar-desktop-toggle');

        function isMobile() {
            return window.innerWidth < 1024;
        }

        function isSidebarOpen() {
            if (!sidebar) return false;
            return isMobile() ? !sidebar.classList.contains('hidden') : !sidebar.classList.contains('lg:hidden');
        }

        function toggleSidebar() {
            if (!sidebar) return;
            if (isSidebarOpen()) {
                if (isMobile()) {
                    sidebar.classList.add('hidden');
                    sidebar.classList.remove('fixed', 'inset-y-0', 'left-0', 'z-40');
                } else {
                    sidebar.classList.add('lg:hidden');
                }
            } else {
                if (isMobile()) {
                    sidebar.classList.remove('hidden');
                    sidebar.classList.add('fixed', 'inset-y-0', 'left-0', 'z-40');
                } else {
                    sidebar.classList.remove('lg:hidden');
                }
            }
        }

        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', toggleSidebar);
        }
        if (sidebarClose) {
            sidebarClose.addEventListener('click', toggleSidebar);
        }
        if (sidebarDesktopToggle) {
            sidebarDesktopToggle.addEventListener('click', toggleSidebar);
        }

    </script>

</body>

</html>