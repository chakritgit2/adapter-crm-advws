{% extends 'layouts/admin.volt' %}

{% block content %}
<div class="container mx-auto px-4 lg:px-8 py-8 max-w-4xl">
    <h1 class="text-2xl font-bold text-slate-900 mb-2">Add API endpoint</h1>
    <p class="text-sm text-slate-500 mb-6">Only read-only, parameterized queries are accepted.</p>
    <form method="post" action="{{ adapterBaseUrl }}/endpoints/store" class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-5">
        <input type="hidden" name="csrf_token" value="{{ adapterCsrf }}">
        <div><label class="block text-sm font-medium text-slate-700 mb-1">API name</label><input required name="api_name" pattern="[A-Za-z][A-Za-z0-9_-]{1,99}" class="w-full rounded-lg border-slate-300" placeholder="getUserProducts"><p class="text-xs text-slate-500 mt-1">The URL will be <code>/api/v1/{name}</code>.</p></div>
        <div><label class="block text-sm font-medium text-slate-700 mb-1">Target connection</label><select required name="connection_id" id="connection-select" class="w-full rounded-lg border-slate-300"><option value="">Select a connection</option>{% for connection in connections %}<option value="{{ connection['id'] }}" data-engine="{{ connection['engine'] }}">{{ connection['name'] }} ({{ connection['engine'] }})</option>{% endfor %}</select></div>
        <div><div class="flex items-center justify-between mb-1"><label class="block text-sm font-medium text-slate-700">Query template</label><button type="button" id="insert-example" class="text-xs text-blue-600 hover:text-blue-800 hidden">Insert example</button></div><textarea required name="query_template" id="query-template" rows="8" class="w-full rounded-lg border-slate-300 font-mono text-sm" placeholder="Select a connection to load a matching query example"></textarea><p id="query-help" class="text-xs text-slate-500 mt-1">SQL connections accept a single <code>SELECT</code>/<code>WITH</code> statement with <code>:named</code> placeholders; MongoDB connections accept a JSON filter with an <code>_collection</code> key.</p></div>
        <div class="border-t border-slate-200 pt-5 space-y-4"><label class="flex items-center gap-2 text-sm font-medium text-slate-700"><input type="checkbox" name="sync_enabled" value="1" class="rounded border-slate-300"> Enable scheduled synchronization</label><div class="grid md:grid-cols-2 gap-5"><div><label class="block text-sm font-medium text-slate-700 mb-1">Cursor column</label><input name="sync_cursor_column" class="w-full rounded-lg border-slate-300" placeholder="updated_at"></div><div><label class="block text-sm font-medium text-slate-700 mb-1">Webhook URL</label><input type="url" name="webhook_url" class="w-full rounded-lg border-slate-300" placeholder="https://crm.example/webhook"></div></div><div><label class="block text-sm font-medium text-slate-700 mb-1">Webhook API key</label><input type="password" name="webhook_api_key" class="w-full rounded-lg border-slate-300" autocomplete="new-password"><p class="text-xs text-slate-500 mt-1">Stored encrypted and only used by the CLI sync task.</p></div></div>
        <div class="flex justify-end gap-3"><a href="{{ adapterBaseUrl }}" class="px-4 py-2 text-slate-600">Cancel</a><button class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">Create endpoint</button></div>
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
            textarea.placeholder = examples[engine];
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
