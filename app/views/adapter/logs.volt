{% extends 'layouts/admin.volt' %}

{% block content %}
<div class="container mx-auto px-4 lg:px-8 py-8 space-y-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 notosan">Adapter Logs</h1>
            <p class="text-sm text-slate-500 mt-1 notosan">Monitor external connection tests and API endpoint requests with diagnostic context.</p>
        </div>
        <div class="flex items-center gap-3">
            <label for="auto-refresh" class="text-sm text-slate-600">Auto refresh</label>
            <select id="auto-refresh" class="px-3 py-2 rounded-lg border border-slate-300 text-sm text-slate-900 shadow-xs focus:outline-hidden focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                <option value="0">Off</option>
                <option value="5">Every 5 seconds</option>
                <option value="15">Every 15 seconds</option>
                <option value="30">Every 30 seconds</option>
                <option value="60">Every minute</option>
            </select>
            <button type="button" id="refresh-logs" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 text-sm font-medium focus:outline-hidden focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"><i class="fas fa-sync-alt"></i> Refresh</button>
        </div>
    </div>

    <section class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between"><div><h2 class="text-lg font-semibold text-slate-900 notosan">External connection log</h2><p class="text-xs text-slate-500 mt-1">Connection target and outcome for tests and adapter queries.</p></div><span class="text-xs text-slate-500">Latest {{ connectionLogs|length }} entries</span></div>
        <div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead class="bg-slate-50 text-slate-600"><tr><th class="px-6 py-3">Time</th><th class="px-6 py-3">Connection</th><th class="px-6 py-3">Event</th><th class="px-6 py-3">Status</th><th class="px-6 py-3">Duration</th><th class="px-6 py-3">Message</th><th class="px-6 py-3">Details</th></tr></thead><tbody class="divide-y divide-slate-100">
        {% for log in connectionLogs %}<tr><td class="px-6 py-3 whitespace-nowrap text-slate-500">{{ log['created_at'] }}</td><td class="px-6 py-3">{{ log['connection_name']|default('Deleted connection') }}</td><td class="px-6 py-3 font-mono text-xs">{{ log['event_type'] }}</td><td class="px-6 py-3"><span class="px-2 py-1 rounded-full text-xs {{ log['status'] == 'success' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">{{ log['status'] }}</span></td><td class="px-6 py-3 whitespace-nowrap">{{ log['duration_ms'] }} ms</td><td class="px-6 py-3 text-slate-500 max-w-xs">{{ log['message']|default('—') }}</td><td class="px-6 py-3"><details><summary class="cursor-pointer text-blue-600 hover:text-blue-800 text-xs font-medium focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-blue-500 rounded px-1 transition-colors">View details</summary><dl class="mt-3 grid grid-cols-2 gap-x-4 gap-y-2 min-w-72 text-xs"><dt class="text-slate-500">Log ID</dt><dd class="font-mono">{{ log['id'] }}</dd><dt class="text-slate-500">Connection ID</dt><dd class="font-mono">{{ log['connection_id']|default('—') }}</dd><dt class="text-slate-500">Engine</dt><dd>{{ log['engine']|default('—') }}</dd><dt class="text-slate-500">Target</dt><dd class="break-all">{{ log['host']|default('—') }}:{{ log['port']|default('—') }}/{{ log['db_name']|default('—') }}</dd><dt class="text-slate-500">Message</dt><dd class="col-span-1 break-words">{{ log['message']|default('—') }}</dd></dl></details></td></tr>{% else %}<tr><td colspan="7" class="px-6 py-10 text-center text-slate-500">No external connection activity recorded.</td></tr>{% endfor %}
        </tbody></table></div>
    </section>

    <section class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between"><div><h2 class="text-lg font-semibold text-slate-900 notosan">API endpoint log</h2><p class="text-xs text-slate-500 mt-1">Request identity, authentication, source target, and failure context.</p></div><span class="text-xs text-slate-500">Latest {{ endpointLogs|length }} entries</span></div>
        <div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead class="bg-slate-50 text-slate-600"><tr><th class="px-6 py-3">Time</th><th class="px-6 py-3">Endpoint</th><th class="px-6 py-3">HTTP</th><th class="px-6 py-3">Auth</th><th class="px-6 py-3">Rows</th><th class="px-6 py-3">Duration</th><th class="px-6 py-3">Request ID</th><th class="px-6 py-3">Details</th></tr></thead><tbody class="divide-y divide-slate-100">
        {% for log in endpointLogs %}<tr><td class="px-6 py-3 whitespace-nowrap text-slate-500">{{ log['created_at'] }}</td><td class="px-6 py-3 font-mono text-xs">{{ log['api_name'] }}</td><td class="px-6 py-3"><span class="px-2 py-1 rounded-full text-xs {{ log['status_code'] >= 200 and log['status_code'] < 300 ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">{{ log['status_code'] }}</span></td><td class="px-6 py-3">{{ log['auth_result'] }}</td><td class="px-6 py-3">{{ log['row_count'] }}</td><td class="px-6 py-3 whitespace-nowrap">{{ log['duration_ms'] }} ms</td><td class="px-6 py-3 font-mono text-xs text-slate-500">{{ log['request_id']|default('—') }}</td><td class="px-6 py-3"><details><summary class="cursor-pointer text-blue-600 hover:text-blue-800 text-xs font-medium focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-blue-500 rounded px-1 transition-colors">View details</summary><dl class="mt-3 grid grid-cols-2 gap-x-4 gap-y-2 min-w-80 text-xs"><dt class="text-slate-500">Log ID</dt><dd class="font-mono">{{ log['id'] }}</dd><dt class="text-slate-500">Endpoint ID</dt><dd class="font-mono">{{ log['endpoint_id']|default('—') }}</dd><dt class="text-slate-500">Connection</dt><dd>{{ log['connection_name']|default('Deleted connection') }}</dd><dt class="text-slate-500">Connection ID</dt><dd class="font-mono">{{ log['connection_id']|default('—') }}</dd><dt class="text-slate-500">Engine</dt><dd>{{ log['engine']|default('—') }}</dd><dt class="text-slate-500">Target</dt><dd class="break-all">{{ log['host']|default('—') }}:{{ log['port']|default('—') }}/{{ log['db_name']|default('—') }}</dd><dt class="text-slate-500">Client IP</dt><dd class="font-mono">{{ log['client_ip']|default('—') }}</dd><dt class="text-slate-500">Error code</dt><dd class="font-mono">{{ log['error_code']|default('—') }}</dd><dt class="text-slate-500">Auth result</dt><dd>{{ log['auth_result'] }}</dd><dt class="text-slate-500">Rows returned</dt><dd>{{ log['row_count'] }}</dd></dl></details></td></tr>{% else %}<tr><td colspan="8" class="px-6 py-10 text-center text-slate-500">No API endpoint activity recorded.</td></tr>{% endfor %}
        </tbody></table></div>
    </section>
    <p class="text-xs text-slate-400">Page loaded at <span id="last-refreshed">now</span>. Logs are limited to the latest 100 entries per category. Credentials and API keys are never displayed.</p>
</div>
<script>
(function () {
    const select = document.getElementById('auto-refresh');
    const button = document.getElementById('refresh-logs');
    const key = 'adapter-log-refresh-seconds';
    let timer;
    select.value = localStorage.getItem(key) || '0';
    function schedule() { if (timer) window.clearInterval(timer); const seconds = Number(select.value); localStorage.setItem(key, select.value); if (seconds > 0) timer = window.setInterval(function () { window.location.reload(); }, seconds * 1000); }
    button.addEventListener('click', function () { window.location.reload(); });
    select.addEventListener('change', schedule);
    document.getElementById('last-refreshed').textContent = new Date().toLocaleString();
    schedule();
}());
</script>
{% endblock %}
