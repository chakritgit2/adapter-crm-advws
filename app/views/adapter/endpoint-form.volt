{% extends 'layouts/admin.volt' %}

{% block content %}
{% set isEdit = endpoint ? true : false %}
{% set selectedId = endpoint ? endpoint['connection_id'] : selectedConnectionId %}
{% set endpointEnabled = endpoint ? endpoint['enabled'] : 1 %}
{% set syncEnabled = endpoint ? endpoint['sync_enabled'] : 0 %}
<div class="container mx-auto px-4 lg:px-8 py-8 max-w-4xl">
    <div class="mb-8">
        <a href="{{ adapterBaseUrl }}" class="inline-flex items-center gap-2 text-sm text-blue-600 hover:text-blue-800 mb-3 focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-blue-500 rounded transition-colors">
            <i class="fas fa-arrow-left"></i> Back to adapter
        </a>
        <h1 class="text-2xl font-bold text-slate-900 notosan">{{ isEdit ? 'Edit' : 'Add' }} API endpoint</h1>
        <p class="text-sm text-slate-500 mt-1 notosan">Only read-only, parameterized queries are accepted.</p>
    </div>

    <form method="post" action="{{ adapterBaseUrl }}/endpoints/{{ isEdit ? 'update/' ~ endpoint['id'] : 'store' }}" class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-5">
        <input type="hidden" name="csrf_token" value="{{ adapterCsrf }}">

        <div class="grid md:grid-cols-2 gap-5">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">API name</label>
                <input required name="api_name" value="{{ endpoint['api_name']|default('') }}" pattern="[A-Za-z][A-Za-z0-9_-]{1,99}" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm text-slate-900 placeholder-slate-400 shadow-xs focus:outline-hidden focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:bg-slate-50 disabled:text-slate-500 disabled:cursor-not-allowed transition" placeholder="getUserProducts">
                <p class="text-xs text-slate-500 mt-1">The URL will be <code>/api/v1/{name}</code>.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Target connection</label>
                <select required name="connection_id" id="connection-select" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm text-slate-900 placeholder-slate-400 shadow-xs focus:outline-hidden focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:bg-slate-50 disabled:text-slate-500 disabled:cursor-not-allowed transition">
                    <option value="">Select a connection</option>
                    {% for connection in connections %}
                    <option value="{{ connection['id'] }}" data-engine="{{ connection['engine'] }}" {{ selectedId == connection['id'] ? 'selected' : '' }}>{{ connection['name'] }} ({{ connection['engine'] }})</option>
                    {% endfor %}
                </select>
            </div>
        </div>

        <label class="flex items-center gap-2 text-sm font-medium text-slate-700">
            <input type="checkbox" name="enabled" value="1" {{ endpointEnabled ? 'checked' : '' }} class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-2 focus:ring-blue-500">
            Endpoint enabled
        </label>

        <div>
            <div class="flex items-center justify-between mb-1">
                <label class="block text-sm font-medium text-slate-700">Query template</label>
                <button type="button" id="insert-example" class="text-xs text-blue-600 hover:text-blue-800 focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 rounded px-1 hidden">Insert Example</button>
            </div>
            <textarea required name="query_template" id="query-template" rows="8" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm text-slate-900 placeholder-slate-400 font-mono shadow-xs focus:outline-hidden focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition" placeholder="Select a connection to load a matching query example">{{ endpoint['query_template']|default('') }}</textarea>
            <p id="query-help" class="text-xs text-slate-500 mt-1">SQL connections accept a single <code>SELECT</code>/<code>WITH</code> statement with <code>:named</code> placeholders; MongoDB connections accept a JSON filter with an <code>_collection</code> key.</p>
        </div>

        <div class="border-t border-slate-200 pt-5 space-y-4">
            <label class="flex items-center gap-2 text-sm font-medium text-slate-700">
                <input type="checkbox" name="sync_enabled" value="1" {{ syncEnabled ? 'checked' : '' }} class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-2 focus:ring-blue-500">
                Enable scheduled synchronization
            </label>
            <div class="grid md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Cursor column</label>
                    <input name="sync_cursor_column" value="{{ endpoint['sync_cursor_column']|default('') }}" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm text-slate-900 placeholder-slate-400 shadow-xs focus:outline-hidden focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:bg-slate-50 disabled:text-slate-500 disabled:cursor-not-allowed transition" placeholder="updated_at">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Webhook URL</label>
                    <input type="url" name="webhook_url" value="{{ endpoint['webhook_url']|default('') }}" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm text-slate-900 placeholder-slate-400 shadow-xs focus:outline-hidden focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:bg-slate-50 disabled:text-slate-500 disabled:cursor-not-allowed transition" placeholder="https://crm.example/webhook">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Webhook API key</label>
                <input type="password" name="webhook_api_key" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm text-slate-900 placeholder-slate-400 shadow-xs focus:outline-hidden focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:bg-slate-50 disabled:text-slate-500 disabled:cursor-not-allowed transition" autocomplete="new-password">
                <p class="text-xs text-slate-500 mt-1">{{ isEdit ? 'Leave blank to retain the existing webhook key.' : 'Stored encrypted and only used by the CLI sync task.' }}</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm">
                <h2 class="font-semibold text-slate-900">Synchronization settings explained</h2>
                <dl class="mt-3 space-y-3">
                    <div><dt class="font-medium text-slate-900">Cursor column</dt><dd class="text-slate-600 mt-0.5">A timestamp or incrementing column used to fetch only changed records.</dd></div>
                    <div><dt class="font-medium text-slate-900">Webhook URL</dt><dd class="text-slate-600 mt-0.5">The HTTPS endpoint that receives transformed records after each sync run.</dd></div>
                    <div><dt class="font-medium text-slate-900">Webhook API key</dt><dd class="text-slate-600 mt-0.5">A secret sent with sync deliveries. It is encrypted at rest and never displayed.</dd></div>
                </dl>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ adapterBaseUrl }}" class="px-4 py-2 text-slate-600 rounded-lg focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-blue-500 transition-colors">Cancel</a>
            <button class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 text-sm font-medium focus:outline-hidden focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">{{ isEdit ? 'Save endpoint' : 'Create endpoint' }}</button>
        </div>
    </form>
</div>
<script>
(function () {
    var examples = {
        mysql: "SELECT id, name, email FROM users WHERE status = :status ORDER BY id LIMIT :limit",
        mariadb: "SELECT id, name, email FROM users WHERE status = :status ORDER BY id LIMIT :limit",
        pgsql: 'SELECT id, name, email FROM "users" WHERE status = :status ORDER BY id LIMIT :limit',
        mongodb: '{\n    "_collection": "users",\n    "status": "active"\n}'
    };
    var hints = {
        mysql: 'MySQL example uses <code>:named</code> placeholders bound from request parameters. Only a single read-only SELECT/WITH statement is allowed.',
        mariadb: 'MariaDB example uses <code>:named</code> placeholders bound from request parameters. Only a single read-only SELECT/WITH statement is allowed.',
        pgsql: 'PostgreSQL example uses <code>:named</code> placeholders bound from request parameters. Only a single read-only SELECT/WITH statement is allowed.',
        mongodb: 'MongoDB endpoints require a JSON filter containing an <code>_collection</code> key; all other keys form the query filter. Dynamic request parameters are not supported.'
    };
    var select = document.getElementById('connection-select');
    var textarea = document.getElementById('query-template');
    var help = document.getElementById('query-help');
    var insertBtn = document.getElementById('insert-example');
    function selectedEngine() {
        var option = select.options[select.selectedIndex];
        return option ? (option.getAttribute('data-engine') || '') : '';
    }
    function applyEngine() {
        var engine = selectedEngine();
        if (examples[engine]) {
            if (!textarea.value) {
                textarea.placeholder = examples[engine];
            }
            help.innerHTML = hints[engine];
            insertBtn.classList.remove('hidden');
        } else {
            textarea.placeholder = 'Select a connection to load a matching query example';
            help.innerHTML = 'SQL connections accept a single <code>SELECT</code>/<code>WITH</code> statement with <code>:named</code> placeholders; MongoDB connections accept a JSON filter with an <code>_collection</code> key.';
            insertBtn.classList.add('hidden');
        }
    }
    insertBtn.addEventListener('click', function () {
        var example = examples[selectedEngine()];
        if (example) {
            textarea.value = example;
            textarea.focus();
        }
    });
    select.addEventListener('change', applyEngine);
    applyEngine();
})();
</script>
{% endblock %}
