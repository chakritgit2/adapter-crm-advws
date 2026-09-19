{% extends 'layouts/admin.volt' %}

{% block content %}
<div class="container mx-auto px-4 lg:px-8 py-8 space-y-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 notosan">Universal Data Adapter</h1>
            <p class="text-sm text-slate-500 mt-1 notosan">Manage tenant-scoped external data connections and their read-only API endpoints.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ adapterBaseUrl }}/logs" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 text-sm font-medium focus:outline-hidden focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"><i class="fas fa-list"></i> View logs</a>
            <a href="{{ adapterBaseUrl }}/connections/create" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium focus:outline-hidden focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"><i class="fas fa-database"></i> Add connection</a>
        </div>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-medium text-slate-500">Connections</p>
            <p id="stat-connections" class="mt-2 text-2xl font-bold text-slate-900">{{ adapterStats['connections'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-medium text-slate-500">Active</p>
            <p id="stat-active" class="mt-2 text-2xl font-bold text-emerald-600">{{ adapterStats['activeConnections'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-medium text-slate-500">Needs attention</p>
            <p id="stat-failed" class="mt-2 text-2xl font-bold {{ adapterStats['failedConnections'] > 0 ? 'text-red-600' : 'text-slate-900' }}">{{ adapterStats['failedConnections'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-medium text-slate-500">API endpoints</p>
            <p class="mt-2 text-2xl font-bold text-slate-900">{{ adapterStats['enabledEndpoints'] }}<span class="text-sm font-medium text-slate-400"> / {{ adapterStats['endpoints'] }} enabled</span></p>
        </div>
    </div>

    {% if connections|length > 0 %}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
        <div class="grid gap-3 lg:grid-cols-4">
            <label class="relative block">
                <span class="sr-only">Search connections and endpoints</span>
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                <input id="adapter-search" type="search" placeholder="Search connections, hosts, databases, or endpoints" class="w-full pl-10 pr-3 py-2 rounded-lg border border-slate-300 text-sm text-slate-900 placeholder-slate-400 shadow-xs focus:outline-hidden focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
            </label>
            <label>
                <span class="sr-only">Filter by status</span>
                <select id="status-filter" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm text-slate-900 shadow-xs focus:outline-hidden focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                    <option value="">All statuses</option>
                    <option value="active">Active</option>
                    <option value="failed">Failed</option>
                    <option value="disabled">Disabled</option>
                </select>
            </label>
            <label>
                <span class="sr-only">Filter by engine</span>
                <select id="engine-filter" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm text-slate-900 shadow-xs focus:outline-hidden focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                    <option value="">All engines</option>
                    <option value="mysql">MySQL</option>
                    <option value="mariadb">MariaDB</option>
                    <option value="pgsql">PostgreSQL</option>
                    <option value="mongodb">MongoDB</option>
                </select>
            </label>
            <label>
                <span class="sr-only">Filter by endpoint state</span>
                <select id="endpoint-filter" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm text-slate-900 shadow-xs focus:outline-hidden focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                    <option value="">Any endpoint state</option>
                    <option value="enabled">Has enabled endpoints</option>
                    <option value="disabled">Has disabled endpoints</option>
                    <option value="none">No endpoints</option>
                </select>
            </label>
        </div>
        <div class="mt-3 flex items-center justify-between gap-3">
            <p id="connection-result-count" class="text-xs text-slate-500" aria-live="polite">{{ connections|length }} connections shown</p>
            <button type="button" id="reset-filters" class="text-xs font-medium text-blue-600 hover:text-blue-800 focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-blue-500 rounded px-1 transition-colors">Clear filters</button>
        </div>
    </div>

    <div class="space-y-4" id="connection-list">
        {% for connection in connections %}
        {% set endpointCount = 0 %}
        {% set enabledEndpointCount = 0 %}
        {% if endpointsByConnection[connection['id']] is defined %}
            {% set connectionEndpoints = endpointsByConnection[connection['id']] %}
            {% set endpointCount = connectionEndpoints|length %}
            {% for endpoint in connectionEndpoints %}
                {% if endpoint['enabled'] %}
                    {% set enabledEndpointCount = enabledEndpointCount + 1 %}
                {% endif %}
            {% endfor %}
        {% endif %}
        {% set connectionSearch = connection['name'] ~ ' ' ~ connection['engine'] ~ ' ' ~ connection['host'] ~ ' ' ~ connection['db_name'] %}
        {% if endpointCount > 0 %}
            {% for endpoint in connectionEndpoints %}
                {% set connectionSearch = connectionSearch ~ ' /api/v1/' ~ endpoint['api_name'] %}
            {% endfor %}
        {% endif %}
        {% set statusClasses = 'bg-slate-100 text-slate-700' %}
        {% if connection['status'] == 'active' %}
            {% set statusClasses = 'bg-emerald-100 text-emerald-700' %}
        {% elseif connection['status'] == 'failed' %}
            {% set statusClasses = 'bg-red-100 text-red-700' %}
        {% endif %}
        <article class="connection-card bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden transition" data-connection-id="{{ connection['id'] }}" data-engine="{{ connection['engine'] }}" data-status="{{ connection['status'] }}" data-endpoint-count="{{ endpointCount }}" data-enabled-count="{{ enabledEndpointCount }}" data-search="{{ connectionSearch }}">
            <div class="p-5 lg:p-6">
                <div class="flex flex-col md:flex-row md:items-center gap-5">
                    <div class="flex min-w-0 flex-1 items-start gap-4">
                        <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-database text-blue-600"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-lg font-semibold text-slate-900 notosan truncate" title="{{ connection['name'] }}">{{ connection['name'] }}</h2>
                                <span class="connection-status-badge px-2 py-1 rounded-full text-xs font-medium {{ statusClasses }}">{{ connection['status'] }}</span>
                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700">{{ endpointCount }} endpoint{{ endpointCount == 1 ? '' : 's' }}</span>
                            </div>
                            <dl class="mt-3 grid gap-x-6 gap-y-2 text-sm text-slate-500 md:grid-cols-3">
                                <div><dt class="sr-only">Engine</dt><dd class="uppercase font-mono text-xs text-slate-700">{{ connection['engine'] }}</dd></div>
                                <div><dt class="sr-only">Target</dt><dd class="truncate" title="{{ connection['host'] }}:{{ connection['port'] }}/{{ connection['db_name'] }}">{{ connection['host'] }}:{{ connection['port'] }}/{{ connection['db_name'] }}</dd></div>
                                <div><dt class="sr-only">Last tested</dt><dd class="connection-last-tested">{{ connection['last_tested_at']|default('Not tested yet') }}</dd></div>
                            </dl>
                            {% if connection['last_error'] %}
                            <p class="connection-last-error mt-3 rounded-lg bg-red-50 px-3 py-2 text-xs text-red-700">{{ connection['last_error'] }}</p>
                            {% else %}
                            <p class="connection-last-error mt-3 hidden rounded-lg bg-red-50 px-3 py-2 text-xs text-red-700"></p>
                            {% endif %}
                            <p class="connection-test-message mt-3 text-xs text-slate-500" aria-live="polite"></p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" data-test-url="{{ adapterBaseUrl }}/connections/test/{{ connection['id'] }}" class="test-connection inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm font-medium focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed transition-colors"><i class="fas fa-bolt"></i> <span>Test</span></button>
                        <a href="{{ adapterBaseUrl }}/connections/edit/{{ connection['id'] }}" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm font-medium focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 transition-colors"><i class="fas fa-pen"></i> Edit</a>
                        <form method="post" action="{{ adapterBaseUrl }}/connections/delete/{{ connection['id'] }}" onsubmit="return confirm('Delete this connection and its {{ endpointCount }} endpoint(s)?');">
                            <input type="hidden" name="csrf_token" value="{{ adapterCsrf }}">
                            <button class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-white border border-red-200 text-red-600 hover:bg-red-50 text-sm font-medium focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-2 transition-colors"><i class="fas fa-trash"></i> Delete</button>
                        </form>
                        <button type="button" class="connection-toggle inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 text-sm font-medium focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-blue-500 transition-colors" aria-expanded="false" aria-controls="connection-endpoints-{{ connection['id'] }}" data-target="connection-endpoints-{{ connection['id'] }}">
                            <span>{{ endpointCount }} endpoint{{ endpointCount == 1 ? '' : 's' }}</span>
                            <i class="connection-chevron fas fa-chevron-down"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div id="connection-endpoints-{{ connection['id'] }}" class="connection-endpoints hidden border-t border-slate-200 bg-slate-50 px-5 py-5">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900 notosan">API endpoints for {{ connection['name'] }}</h3>
                        <p class="text-xs text-slate-500 mt-1">Read-only routes exposed by this external connection.</p>
                    </div>
                    <a href="{{ adapterBaseUrl }}/endpoints/create?connection_id={{ connection['id'] }}" class="inline-flex items-center justify-center gap-2 px-3 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 text-sm font-medium focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 transition-colors"><i class="fas fa-plus"></i> Add endpoint</a>
                </div>

                {% if endpointCount > 0 %}
                <div class="divide-y divide-slate-100 rounded-lg border border-slate-200 bg-white">
                    {% for endpoint in connectionEndpoints %}
                    <div class="endpoint-row flex flex-col md:flex-row md:items-center gap-4 px-4 py-4">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <code class="inline-block min-w-0 font-mono text-sm text-slate-900 truncate" title="/api/v1/{{ endpoint['api_name'] }}">/api/v1/{{ endpoint['api_name'] }}</code>
                                {% if endpoint['enabled'] %}
                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">Enabled</span>
                                {% else %}
                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-700">Disabled</span>
                                {% endif %}
                                {% if endpoint['sync_enabled'] %}
                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700">Sync enabled</span>
                                {% endif %}
                            </div>
                            <p class="mt-1 text-xs text-slate-500">Created {{ endpoint['created_at'] }}</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <button type="button" data-copy-url="/api/v1/{{ endpoint['api_name'] }}" class="copy-endpoint inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm font-medium focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-blue-500 transition-colors"><i class="fas fa-copy"></i> <span class="copy-label">Copy URL</span></button>
                            <a href="{{ adapterBaseUrl }}/endpoints/edit/{{ endpoint['id'] }}" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm font-medium focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-blue-500 transition-colors"><i class="fas fa-pen"></i> Edit</a>
                            <form method="post" action="{{ adapterBaseUrl }}/endpoints/toggle/{{ endpoint['id'] }}">
                                <input type="hidden" name="csrf_token" value="{{ adapterCsrf }}">
                                <button class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm font-medium focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-blue-500 transition-colors"><i class="fas fa-power-off"></i> {{ endpoint['enabled'] ? 'Disable' : 'Enable' }}</button>
                            </form>
                            <form method="post" action="{{ adapterBaseUrl }}/endpoints/delete/{{ endpoint['id'] }}" onsubmit="return confirm('Delete endpoint /api/v1/{{ endpoint['api_name'] }}?');">
                                <input type="hidden" name="csrf_token" value="{{ adapterCsrf }}">
                                <button class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-white border border-red-200 text-red-600 hover:bg-red-50 text-sm font-medium focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-red-500 transition-colors"><i class="fas fa-trash"></i> Delete</button>
                            </form>
                        </div>
                    </div>
                    {% endfor %}
                </div>
                {% else %}
                <div class="rounded-lg border border-slate-300 bg-white px-4 py-8 text-center">
                    <i class="fas fa-plug text-slate-300 text-xl"></i>
                    <p class="mt-2 text-sm font-medium text-slate-700">No API endpoints yet</p>
                    <p class="mt-1 text-xs text-slate-500">Create a read-only endpoint for this connection when the target query is ready.</p>
                </div>
                {% endif %}
            </div>
        </article>
        {% endfor %}
    </div>

    <div id="no-filter-results" class="hidden rounded-xl border border-slate-300 bg-white px-6 py-10 text-center">
        <i class="fas fa-search text-slate-300 text-2xl"></i>
        <p class="mt-3 text-sm font-medium text-slate-700">No matching connections</p>
        <p class="mt-1 text-xs text-slate-500">Try a different search term or clear the filters.</p>
    </div>
    {% else %}
    <section class="rounded-xl border border-slate-300 bg-white px-6 py-14 text-center">
        <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50">
            <i class="fas fa-database text-blue-600 text-xl"></i>
        </div>
        <h2 class="mt-4 text-lg font-semibold text-slate-900 notosan">No external connections configured</h2>
        <p class="mx-auto mt-2 max-w-md text-sm text-slate-500">Create a connection before exposing external data through read-only API endpoints.</p>
        <a href="{{ adapterBaseUrl }}/connections/create" class="mt-5 inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 text-sm font-medium focus:outline-hidden focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"><i class="fas fa-plus"></i> Add connection</a>
    </section>
    {% endif %}
</div>
<script>
(function () {
    var cards = Array.prototype.slice.call(document.querySelectorAll('.connection-card'));
    var searchInput = document.getElementById('adapter-search');
    var statusFilter = document.getElementById('status-filter');
    var engineFilter = document.getElementById('engine-filter');
    var endpointFilter = document.getElementById('endpoint-filter');
    var resetFilters = document.getElementById('reset-filters');
    var resultCount = document.getElementById('connection-result-count');
    var noResults = document.getElementById('no-filter-results');

    document.querySelectorAll('.connection-toggle').forEach(function (button) {
        button.addEventListener('click', function () {
            var panel = document.getElementById(button.dataset.target);
            if (!panel) return;
            var expanded = button.getAttribute('aria-expanded') === 'true';
            button.setAttribute('aria-expanded', expanded ? 'false' : 'true');
            panel.classList.toggle('hidden', expanded);
            var icon = button.querySelector('.connection-chevron');
            if (icon) {
                icon.classList.toggle('fa-chevron-down', expanded);
                icon.classList.toggle('fa-chevron-up', !expanded);
            }
        });
    });

    function applyFilters() {
        var query = searchInput ? searchInput.value.trim().toLowerCase() : '';
        var status = statusFilter ? statusFilter.value : '';
        var engine = engineFilter ? engineFilter.value : '';
        var endpointState = endpointFilter ? endpointFilter.value : '';
        var visible = 0;

        cards.forEach(function (card) {
            var endpointCount = parseInt(card.dataset.endpointCount || '0', 10);
            var enabledCount = parseInt(card.dataset.enabledCount || '0', 10);
            var matchesQuery = !query || (card.dataset.search || '').toLowerCase().indexOf(query) !== -1;
            var matchesStatus = !status || card.dataset.status === status;
            var matchesEngine = !engine || card.dataset.engine === engine;
            var matchesEndpointState = !endpointState
                || (endpointState === 'enabled' && enabledCount > 0)
                || (endpointState === 'disabled' && endpointCount > enabledCount)
                || (endpointState === 'none' && endpointCount === 0);
            var show = matchesQuery && matchesStatus && matchesEngine && matchesEndpointState;
            card.classList.toggle('hidden', !show);
            if (show) {
                visible++;
                if (query || endpointState) {
                    var toggle = card.querySelector('.connection-toggle');
                    var panel = toggle ? document.getElementById(toggle.dataset.target) : null;
                    if (toggle && panel) {
                        toggle.setAttribute('aria-expanded', 'true');
                        panel.classList.remove('hidden');
                        var icon = toggle.querySelector('.connection-chevron');
                        if (icon) {
                            icon.classList.remove('fa-chevron-down');
                            icon.classList.add('fa-chevron-up');
                        }
                    }
                }
            }
        });

        if (resultCount) {
            resultCount.textContent = visible + ' of ' + cards.length + ' connections shown';
        }
        if (noResults) {
            noResults.classList.toggle('hidden', visible !== 0);
        }
    }

    [searchInput, statusFilter, engineFilter, endpointFilter].forEach(function (control) {
        if (!control) return;
        control.addEventListener(control.tagName === 'INPUT' ? 'input' : 'change', applyFilters);
    });
    if (resetFilters) {
        resetFilters.addEventListener('click', function () {
            if (searchInput) searchInput.value = '';
            if (statusFilter) statusFilter.value = '';
            if (engineFilter) engineFilter.value = '';
            if (endpointFilter) endpointFilter.value = '';
            applyFilters();
        });
    }

    function updateConnectionStats() {
        var active = 0;
        var failed = 0;
        cards.forEach(function (card) {
            if (card.dataset.status === 'active') active++;
            if (card.dataset.status === 'failed') failed++;
        });
        var activeStat = document.getElementById('stat-active');
        var failedStat = document.getElementById('stat-failed');
        if (activeStat) activeStat.textContent = active;
        if (failedStat) {
            failedStat.textContent = failed;
            failedStat.className = 'mt-2 text-2xl font-bold ' + (failed > 0 ? 'text-red-600' : 'text-slate-900');
        }
    }

    function setConnectionStatus(card, status) {
        var badge = card.querySelector('.connection-status-badge');
        if (!badge) return;
        card.dataset.status = status;
        badge.textContent = status;
        if (status === 'active') {
            badge.className = 'connection-status-badge px-2 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700';
        } else if (status === 'failed') {
            badge.className = 'connection-status-badge px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700';
        } else {
            badge.className = 'connection-status-badge px-2 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-700';
        }
        updateConnectionStats();
        applyFilters();
    }

    document.querySelectorAll('.test-connection').forEach(function (button) {
        button.addEventListener('click', async function () {
            var card = button.closest('.connection-card');
            var message = card ? card.querySelector('.connection-test-message') : null;
            var label = button.querySelector('span');
            var originalLabel = label ? label.textContent : 'Test';
            button.disabled = true;
            if (label) label.textContent = 'Testing…';
            if (message) {
                message.textContent = 'Testing connection…';
                message.className = 'connection-test-message mt-3 text-xs text-slate-500';
            }

            try {
                var response = await fetch(button.dataset.testUrl, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams({csrf_token: '{{ adapterCsrf }}'})
                });
                var result = await response.json().catch(function () { return {}; });
                var succeeded = response.ok && result.status === 'success';
                if (card) {
                    setConnectionStatus(card, result.connectionStatus || (succeeded ? 'active' : 'failed'));
                    var lastTested = card.querySelector('.connection-last-tested');
                    if (lastTested) lastTested.textContent = result.lastTestedAt || 'Just now';
                    var lastError = card.querySelector('.connection-last-error');
                    if (lastError) {
                        lastError.textContent = succeeded ? '' : 'External connection test failed.';
                        lastError.classList.toggle('hidden', succeeded);
                    }
                }
                if (message) {
                    message.textContent = result.message || (succeeded ? 'Connection test succeeded.' : 'Connection test failed.');
                    message.className = 'connection-test-message mt-3 text-xs ' + (succeeded ? 'text-emerald-600' : 'text-red-600');
                }
            } catch (error) {
                if (card) setConnectionStatus(card, 'failed');
                if (message) {
                    message.textContent = 'Connection test request failed.';
                    message.className = 'connection-test-message mt-3 text-xs text-red-600';
                }
            } finally {
                button.disabled = false;
                if (label) label.textContent = originalLabel;
            }
        });
    });

    function showCopied(button) {
        var label = button.querySelector('.copy-label');
        if (!label) return;
        var original = label.textContent;
        label.textContent = 'Copied';
        window.setTimeout(function () { label.textContent = original; }, 1600);
    }

    function copyFallback(text, button) {
        var input = document.createElement('textarea');
        input.value = text;
        document.body.appendChild(input);
        input.select();
        document.execCommand('copy');
        input.remove();
        showCopied(button);
    }

    document.querySelectorAll('.copy-endpoint').forEach(function (button) {
        button.addEventListener('click', function () {
            var url = new URL(button.dataset.copyUrl, window.location.origin).toString();
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(url).then(function () {
                    showCopied(button);
                }).catch(function () {
                    copyFallback(url, button);
                });
                return;
            }
            copyFallback(url, button);
        });
    });
})();
</script>
{% endblock %}
