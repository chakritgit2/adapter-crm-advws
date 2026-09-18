{% extends 'layouts/admin.volt' %}

{% block content %}
<div class="container mx-auto px-4 lg:px-8 py-8 max-w-4xl">
    <h1 class="text-2xl font-bold text-slate-900 mb-2">Add API endpoint</h1>
    <p class="text-sm text-slate-500 mb-6">Only read-only, parameterized queries are accepted.</p>
    <form method="post" action="{{ adapterBaseUrl }}/endpoints/store" class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-5">
        <input type="hidden" name="csrf_token" value="{{ adapterCsrf }}">
        <div><label class="block text-sm font-medium text-slate-700 mb-1">API name</label><input required name="api_name" pattern="[A-Za-z][A-Za-z0-9_-]{1,99}" class="w-full rounded-lg border-slate-300" placeholder="getUserProducts"><p class="text-xs text-slate-500 mt-1">The URL will be <code>/api/v1/{name}</code>.</p></div>
        <div><label class="block text-sm font-medium text-slate-700 mb-1">Target connection</label><select required name="connection_id" class="w-full rounded-lg border-slate-300"><option value="">Select a connection</option>{% for connection in connections %}<option value="{{ connection['id'] }}">{{ connection['name'] }} ({{ connection['engine'] }})</option>{% endfor %}</select></div>
        <div><label class="block text-sm font-medium text-slate-700 mb-1">Query template</label><textarea required name="query_template" rows="8" class="w-full rounded-lg border-slate-300 font-mono text-sm" placeholder="SELECT * FROM products WHERE user_id = :user_id"></textarea><p class="text-xs text-slate-500 mt-1">Use named placeholders such as <code>:user_id</code>. Request values are bound parameters; SQL fragments and table names cannot be supplied by callers. For MongoDB, enter a JSON definition containing an <code>_collection</code> key.</p></div>
        <div class="border-t border-slate-200 pt-5 space-y-4"><label class="flex items-center gap-2 text-sm font-medium text-slate-700"><input type="checkbox" name="sync_enabled" value="1" class="rounded border-slate-300"> Enable scheduled synchronization</label><div class="grid md:grid-cols-2 gap-5"><div><label class="block text-sm font-medium text-slate-700 mb-1">Cursor column</label><input name="sync_cursor_column" class="w-full rounded-lg border-slate-300" placeholder="updated_at"></div><div><label class="block text-sm font-medium text-slate-700 mb-1">Webhook URL</label><input type="url" name="webhook_url" class="w-full rounded-lg border-slate-300" placeholder="https://crm.example/webhook"></div></div><div><label class="block text-sm font-medium text-slate-700 mb-1">Webhook API key</label><input type="password" name="webhook_api_key" class="w-full rounded-lg border-slate-300" autocomplete="new-password"><p class="text-xs text-slate-500 mt-1">Stored encrypted and only used by the CLI sync task.</p></div></div>
        <div class="flex justify-end gap-3"><a href="{{ adapterBaseUrl }}" class="px-4 py-2 text-slate-600">Cancel</a><button class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">Create endpoint</button></div>
    </form>
</div>
{% endblock %}
