{% extends 'layouts/admin.volt' %}

{% block content %}
<main class="flex-1">
    <div class="container mx-auto px-4 lg:px-8 py-8">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-900 notosan">{{ t('dashboard.title') }}</h1>
            <p class="text-sm text-slate-500 mt-1 notosan">{{ t('dashboard.subtitle') }}</p>
        </div>

        {% if companies|length > 0 %}
        <!-- KPI Summary -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center">
                        <i class="fas fa-building text-blue-600"></i>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 font-medium">{{ t('dashboard.active_companies') }}</p>
                        <p class="text-xl font-bold text-slate-900">{{ totalCompanies }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-emerald-50 flex items-center justify-center">
                        <i class="fas fa-users text-emerald-600"></i>
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
                        <p class="text-xl font-bold text-slate-900">
                            {% set totalPositions = 0 %}
                            {% for count in positionCounts %}
                                {% set totalPositions = totalPositions + count %}
                            {% endfor %}
                            {{ totalPositions }}
                        </p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-amber-50 flex items-center justify-center">
                        <i class="fas fa-user-plus text-amber-600"></i>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 font-medium">{{ t('dashboard.avg_employees') }}</p>
                        <p class="text-xl font-bold text-slate-900">{{ averageEmployees }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chart -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 mb-8">
            <h2 class="text-lg font-semibold text-slate-900 mb-4 notosan">{{ t('dashboard.employees_per_company') }}</h2>
            <div class="relative h-72">
                {% autoescape false %}
                <canvas id="employeesChart" data-chart="{{ chartPayloadJson }}"></canvas>
                {% endautoescape %}
            </div>
        </div>

        <!-- Company Cards -->
        <h2 class="text-lg font-semibold text-slate-900 mb-4 notosan">{{ t('dashboard.your_active_companies') }}</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            {% for company in companies %}
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 hover:shadow-md transition">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center">
                            <i class="fas fa-building text-blue-600"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-slate-900 notosan">{{ company['name'] }}</h3>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">{{ t('dashboard.active') }}</span>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4 mb-5">
                    <div class="bg-slate-50 rounded-lg p-3 text-center">
                        <p class="text-xs text-slate-500">{{ t('dashboard.employees') }}</p>
                        <p class="text-lg font-bold text-slate-900">{{ employeeCounts[company['id']] | default(0) }}</p>
                    </div>
                    <div class="bg-slate-50 rounded-lg p-3 text-center">
                        <p class="text-xs text-slate-500">{{ t('dashboard.positions') }}</p>
                        <p class="text-lg font-bold text-slate-900">{{ positionCounts[company['id']] | default(0) }}</p>
                    </div>
                </div>
                <a href="/{{ company['tenant_slug'] }}/{{ company['slug'] }}/dashboard"
                    class="block w-full text-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium text-sm">
                    {{ t('dashboard.enter') }}
                </a>
            </div>
            {% endfor %}
        </div>
        {% else %}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-12 text-center">
            <div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-building text-slate-400 text-2xl"></i>
            </div>
            <h3 class="text-lg font-semibold text-slate-900 mb-1 notosan">{{ t('dashboard.no_active_companies') }}</h3>
            <p class="text-sm text-slate-500 notosan">{{ t('dashboard.no_access') }}</p>
        </div>
        {% endif %}
    </div>
</main>

{% if companies|length > 0 %}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const ctx = document.getElementById('employeesChart');
        if (!ctx) return;

        function decodeHtmlEntities(str) {
            const txt = document.createElement('textarea');
            txt.innerHTML = str;
            return txt.value;
        }

        const chartData = JSON.parse(decodeHtmlEntities(ctx.dataset.chart || '[]'));
        const labels = chartData.map(function (company) { return company.name; });
        const data = chartData.map(function (company) { return company.employees || 0; });

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
                    barPercentage: 0.3,
                    maxBarThickness: 32
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0 }
                    }
                }
            }
        });
    });
</script>
{% endif %}
{% endblock %}