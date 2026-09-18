{% extends 'layouts/admin.volt' %}

{% block content %}
<main class="flex-1">
    <div class="container mx-auto px-4 lg:px-8 py-8">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-900 notosan">{{ currentCompany.name }} {{ t('dashboard.title') }}</h1>
            <p class="text-sm text-slate-500 mt-1 notosan">{{ t('company.dashboard.subtitle') }}</p>
        </div>

        <!-- KPI Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center">
                        <i class="fas fa-users text-blue-600"></i>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 font-medium">{{ t('dashboard.total_employees') }}</p>
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
                        <p class="text-xs text-slate-500 font-medium">{{ t('dashboard.total_positions') }}</p>
                        <p class="text-xl font-bold text-slate-900">{{ totalPositions }}</p>
                    </div>
                </div>
            </div>
            {% for card in dashboardCards %}
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-emerald-50 flex items-center justify-center">
                        <i class="fas fa-chart-line text-emerald-600"></i>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 font-medium">{{ card['name'] }}</p>
                        <p class="text-xl font-bold text-slate-900">{{ card['value'] }}</p>
                    </div>
                </div>
            </div>
            {% endfor %}
        </div>

        <!-- Demographics -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                <h2 class="text-lg font-semibold text-slate-900 mb-4 notosan">{{ t('company.dashboard.age_distribution') }}</h2>
                <div class="relative h-72">
                    {% autoescape false %}
                    <canvas id="ageChart" data-chart="{{ ageChartPayloadJson }}"></canvas>
                    {% endautoescape %}
                </div>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                <h2 class="text-lg font-semibold text-slate-900 mb-4 notosan">{{ t('company.dashboard.upcoming_birthdays') }}</h2>
                {% if upcomingBirthdays|length > 0 %}
                <ul class="space-y-3">
                    {% for birthday in upcomingBirthdays %}
                    <li class="flex items-center justify-between p-3 bg-slate-50 rounded-lg">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-amber-50 flex items-center justify-center">
                                <i class="fas fa-birthday-cake text-amber-600 text-xs"></i>
                            </div>
                            <span class="text-sm font-medium text-slate-900">{{ birthday['first_name'] }} {{ birthday['last_name'] }}</span>
                        </div>
                        <span class="text-sm text-slate-500">{{ birthday['formatted_birthday'] }}</span>
                    </li>
                    {% endfor %}
                </ul>
                {% else %}
                <p class="text-sm text-slate-500">{{ t('company.dashboard.no_birthdays') }}</p>
                {% endif %}
            </div>
        </div>

        <!-- Custom Attribute Charts -->
        {% if dashboardCharts|length > 0 %}
        <div class="mb-8">
            <h2 class="text-lg font-semibold text-slate-900 mb-4 notosan">{{ t('company.dashboard.custom_breakdown') }}</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                {% for chart in dashboardCharts %}
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                    <h3 class="text-sm font-semibold text-slate-700 mb-4 notosan">{{ chart['name'] }}</h3>
                    <div class="relative h-56">
                        {% autoescape false %}
                        <canvas class="dashboard-pie-chart"
                            data-chart="{{ chart['json'] }}"></canvas>
                        {% endautoescape %}
                    </div>
                </div>
                {% endfor %}
            </div>
        </div>
        {% endif %}

        <!-- Dynamic Roster -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-slate-900 notosan">{{ t('company.dashboard.employee_roster') }}</h2>
                <button id="resetFilters" class="hidden px-4 py-2 text-sm font-medium text-slate-600 bg-slate-100 rounded-lg hover:bg-slate-200 transition">
                    {{ t('company.dashboard.reset_filters') }}
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left">
                    <thead class="text-xs text-slate-500 uppercase bg-slate-50">
                        <tr>
                            <th class="px-3 py-2 rounded-l-lg">{{ t('common.name') }}</th>
                            <th class="px-3 py-2">{{ t('company.dashboard.email') }}</th>
                            <th class="px-3 py-2">{{ t('company.dashboard.department') }}</th>
                            <th class="px-3 py-2">{{ t('company.dashboard.position') }}</th>
                            <th class="px-3 py-2">{{ t('company.dashboard.dob') }}</th>
                            <th class="px-3 py-2{% if dashboardRosterAttributes|length == 0 %} rounded-r-lg{% endif %}">{{ t('company.dashboard.age') }}</th>
                            {% for attr in dashboardRosterAttributes %}
                            <th class="px-3 py-2 text-center{% if loop.last %} rounded-r-lg{% endif %}">{{ attr['name'] }}</th>
                            {% endfor %}
                        </tr>
                    </thead>
                    <tbody id="rosterBody">
                        {% for employee in roster %}
                        <tr class="border-b border-slate-100 hover:bg-slate-50">
                            <td class="px-3 py-2 font-medium text-slate-900">{{ employee['first_name'] }} {{ employee['last_name'] }}</td>
                            <td class="px-3 py-2 text-slate-500">{{ employee['email'] }}</td>
                            <td class="px-3 py-2 text-slate-500">{{ employee['department'] }}</td>
                            <td class="px-3 py-2 text-slate-500">{{ employee['job_title'] }}</td>
                            <td class="px-3 py-2 text-slate-500">{{ employee['date_of_birth'] }}</td>
                            <td class="px-3 py-2 text-slate-500">{{ employee['age'] }}</td>
                            {% for attr in dashboardRosterAttributes %}
                            <td class="px-3 py-2 text-slate-500 text-center">
                                {% if attr['field_type'] == 'boolean' %}
                                    {% set rawValue = employee['attributes'][attr['name']]['value'] ? employee['attributes'][attr['name']]['value'] : '' %}
                                    {% if rawValue|lower in ['1', 'true', 'yes'] %}
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">{{ t('common.yes') }}</span>
                                    {% else %}
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">{{ t('common.no') }}</span>
                                    {% endif %}
                                {% elseif employee['attributes'][attr['name']]['value'] is defined %}
                                    {{ employee['attributes'][attr['name']]['value'] }}
                                {% else %}
                                    <span class="text-slate-400 italic">—</span>
                                {% endif %}
                            </td>
                            {% endfor %}
                        </tr>
                        {% endfor %}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    function decodeHtmlEntities(str) {
        const txt = document.createElement('textarea');
        txt.innerHTML = str;
        return txt.value;
    }

    function renderBarChart(canvasId, labelKey, valueKey, onClick) {
        const ctx = document.getElementById(canvasId);
        if (!ctx) return;
        const chartData = JSON.parse(decodeHtmlEntities(ctx.dataset.chart || '[]'));
        const labels = chartData.map(function (item) { return item[labelKey]; });
        const data = chartData.map(function (item) { return item[valueKey]; });

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: '<?= $this->getDI()->get('locale')->t('chart.employees') ?>',
                    data: data,
                    backgroundColor: 'rgba(37, 99, 235, 0.8)',
                    borderColor: 'rgba(37, 99, 235, 1)',
                    borderWidth: 1,
                    borderRadius: 6,
                    barPercentage: 0.5,
                    maxBarThickness: 32
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } }
                },
                onClick: function (evt, elements) {
                    if (!elements.length || !onClick) return;
                    const index = elements[0].index;
                    const value = labels[index];
                    onClick(value);
                }
            }
        });
    }

    function renderPieChart(canvas, onClick) {
        if (!canvas) return;
        const chartData = JSON.parse(decodeHtmlEntities(canvas.dataset.chart || '{}'));
        const labels = chartData.labels || [];
        const data = chartData.data || [];
        const values = chartData.values || labels;
        const attributeId = chartData.id;

        const colors = [
            'rgba(37, 99, 235, 0.8)',
            'rgba(16, 185, 129, 0.8)',
            'rgba(245, 158, 11, 0.8)',
            'rgba(139, 92, 246, 0.8)',
            'rgba(236, 72, 153, 0.8)',
            'rgba(14, 165, 233, 0.8)'
        ];

        new Chart(canvas, {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: colors,
                    borderColor: 'rgba(255, 255, 255, 1)',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                },
                onClick: function (evt, elements) {
                    if (!elements.length || !onClick) return;
                    const index = elements[0].index;
                    const value = values[index];
                    onClick(attributeId, value);
                }
            }
        });
    }

    function renderRoster(employees, attributes) {
        const tbody = document.getElementById('rosterBody');
        if (!tbody) return;
        tbody.innerHTML = '';
        employees.forEach(function (emp) {
            let html = '<td class="px-3 py-2 font-medium text-slate-900">' + (emp.first_name || '') + ' ' + (emp.last_name || '') + '</td>' +
                '<td class="px-3 py-2 text-slate-500">' + (emp.email || '') + '</td>' +
                '<td class="px-3 py-2 text-slate-500">' + (emp.department || '') + '</td>' +
                '<td class="px-3 py-2 text-slate-500">' + (emp.job_title || '') + '</td>' +
                '<td class="px-3 py-2 text-slate-500">' + (emp.date_of_birth || '') + '</td>' +
                '<td class="px-3 py-2 text-slate-500">' + (emp.age || '') + '</td>';

            (attributes || []).forEach(function (attr) {
                const attrData = (emp.attributes || {})[attr.name] || {};
                const rawValue = attrData.value || '';
                let cell = '';
                if (attr.field_type === 'boolean') {
                    const isYes = ['1', 'true', 'yes'].indexOf(String(rawValue).toLowerCase()) !== -1;
                    cell = isYes
                        ? '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700"><?= $this->getDI()->get('locale')->t('common.yes') ?></span>'
                        : '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700"><?= $this->getDI()->get('locale')->t('common.no') ?></span>';
                } else if (rawValue) {
                    cell = rawValue;
                } else {
                    cell = '<span class="text-slate-400 italic">—</span>';
                }
                html += '<td class="px-3 py-2 text-slate-500 text-center">' + cell + '</td>';
            });

            const tr = document.createElement('tr');
            tr.className = 'border-b border-slate-100 hover:bg-slate-50';
            tr.innerHTML = html;
            tbody.appendChild(tr);
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        const resetBtn = document.getElementById('resetFilters');
        const tenantSlugEl = document.getElementById('tenantSlug');
        const tenantSlug = tenantSlugEl ? JSON.parse(decodeHtmlEntities(tenantSlugEl.textContent || '""')) : '';
        const initialRosterEl = document.getElementById('initialRoster');
        const initialRoster = initialRosterEl ? JSON.parse(decodeHtmlEntities(initialRosterEl.textContent || '[]')) : [];
        const dashboardRosterAttributesEl = document.getElementById('dashboardRosterAttributes');
        const dashboardRosterAttributes = dashboardRosterAttributesEl ? JSON.parse(decodeHtmlEntities(dashboardRosterAttributesEl.textContent || '[]')) : [];

        function applyFilter(attributeId, value) {
            fetch('/' + tenantSlug + '/dashboard/filter-employees?attribute_id=' + encodeURIComponent(attributeId) + '&value=' + encodeURIComponent(value))
                .then(function (res) { return res.json(); })
                .then(function (payload) {
                    if (payload.status === 'success') {
                        renderRoster(payload.data, dashboardRosterAttributes);
                        if (resetBtn) resetBtn.classList.remove('hidden');
                    }
                })
                .catch(function (err) { console.error('Filter failed', err); });
        }

        function applyAgeFilter(ageBracket) {
            fetch('/' + tenantSlug + '/dashboard/filter-employees-by-age?age_bracket=' + encodeURIComponent(ageBracket))
                .then(function (res) { return res.json(); })
                .then(function (payload) {
                    if (payload.status === 'success') {
                        renderRoster(payload.data, dashboardRosterAttributes);
                        if (resetBtn) resetBtn.classList.remove('hidden');
                    }
                })
                .catch(function (err) { console.error('Age filter failed', err); });
        }

        renderBarChart('ageChart', 'label', 'count', applyAgeFilter);

        document.querySelectorAll('.dashboard-pie-chart').forEach(function (canvas) {
            renderPieChart(canvas, applyFilter);
        });

        if (resetBtn) {
            resetBtn.addEventListener('click', function () {
                renderRoster(initialRoster, dashboardRosterAttributes);
                resetBtn.classList.add('hidden');
            });
        }
    });
</script>

{% autoescape false %}
<script type="application/json" id="initialRoster">{{ rosterJson }}</script>
<script type="application/json" id="dashboardRosterAttributes">{{ dashboardRosterAttributesJson }}</script>
<script type="application/json" id="tenantSlug">{{ tenantSlugJson }}</script>
{% endautoescape %}
{% endblock %}