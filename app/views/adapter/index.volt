{% extends 'layouts/admin.volt' %}

{% block content %}
<div class="container mx-auto px-4 lg:px-8 py-8 space-y-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Universal Data Adapter</h1>
            <p class="text-sm text-slate-500 mt-1">Manage tenant-scoped external data connections and read-only API slots.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ adapterBaseUrl }}/logs" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 text-sm font-medium"><i class="fas fa-list"></i> View logs</a>
            <a href="{{ adapterBaseUrl }}/connections/create" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-700 text-white rounded-lg hover:bg-slate-800 text-sm font-medium"><i class="fas fa-database"></i> Add connection</a>
            <a href="{{ adapterBaseUrl }}/endpoints/create" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium"><i class="fas fa-plug"></i> Add endpoint</a>
        </div>
    </div>

    <section class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
            <h2 class="font-semibold text-slate-900">External connections</h2>
            <span class="text-xs text-slate-500">{{ connections|length }} configured</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-600"><tr><th class="px-6 py-3">Name</th><th class="px-6 py-3">Engine</th><th class="px-6 py-3">Host</th><th class="px-6 py-3">Status</th><th class="px-6 py-3 text-right">Actions</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                {% for connection in connections %}
                    <tr><td class="px-6 py-4 font-medium text-slate-900">{{ connection['name'] }}</td><td class="px-6 py-4 uppercase text-xs font-mono">{{ connection['engine'] }}</td><td class="px-6 py-4 text-slate-500">{{ connection['host'] }}:{{ connection['port'] }}</td><td class="px-6 py-4"><span class="px-2 py-1 rounded-full text-xs {{ connection['status'] == 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">{{ connection['status'] }}</span></td><td class="px-6 py-4 text-right space-x-3"><button type="button" data-test-url="{{ adapterBaseUrl }}/connections/test/{{ connection['id'] }}" class="test-connection text-blue-600 hover:text-blue-800 font-medium">Test</button><a href="{{ adapterBaseUrl }}/connections/edit/{{ connection['id'] }}" class="text-slate-600 hover:text-slate-900">Edit</a><form class="inline" method="post" action="{{ adapterBaseUrl }}/connections/delete/{{ connection['id'] }}" onsubmit="return confirm('Delete this connection and its endpoints?');"><input type="hidden" name="csrf_token" value="{{ adapterCsrf }}"><button class="text-red-600 hover:text-red-800">Delete</button></form></td></tr>
                {% else %}
                    <tr><td colspan="5" class="px-6 py-10 text-center text-slate-500">No external connections configured.</td></tr>
                {% endfor %}
                </tbody>
            </table>
        </div>
    </section>

    <section class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between"><h2 class="font-semibold text-slate-900">API endpoints</h2><span class="text-xs text-slate-500">{{ endpoints|length }} configured</span></div>
        <div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead class="bg-slate-50 text-slate-600"><tr><th class="px-6 py-3">API name</th><th class="px-6 py-3">Connection</th><th class="px-6 py-3">Enabled</th><th class="px-6 py-3">Created</th><th class="px-6 py-3 text-right">Actions</th></tr></thead><tbody class="divide-y divide-slate-100">
        {% for endpoint in endpoints %}<tr><td class="px-6 py-4 font-mono text-slate-900">/api/v1/{{ endpoint['api_name'] }}</td><td class="px-6 py-4">{{ endpoint['connection_name'] }}</td><td class="px-6 py-4">{{ endpoint['enabled'] ? 'Yes' : 'No' }}</td><td class="px-6 py-4 text-slate-500">{{ endpoint['created_at'] }}</td><td class="px-6 py-4 text-right"><form method="post" action="{{ adapterBaseUrl }}/endpoints/delete/{{ endpoint['id'] }}" onsubmit="return confirm('Delete this endpoint?');"><input type="hidden" name="csrf_token" value="{{ adapterCsrf }}"><button class="text-red-600 hover:text-red-800">Delete</button></form></td></tr>{% else %}<tr><td colspan="5" class="px-6 py-10 text-center text-slate-500">No API endpoints configured.</td></tr>{% endfor %}
        </tbody></table></div>
    </section>
</div>
<script>
document.querySelectorAll('.test-connection').forEach(function (button) { button.addEventListener('click', async function () { button.disabled = true; const body = new URLSearchParams({csrf_token: '{{ adapterCsrf }}'}); try { const response = await fetch(button.dataset.testUrl, {method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: body}); const result = await response.json(); alert(result.message || 'Test completed.'); window.location.reload(); } catch (error) { alert('Connection test failed.'); button.disabled = false; } }); });
</script>
{% endblock %}
