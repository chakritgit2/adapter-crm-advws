{% extends 'layouts/admin.volt' %}

{% block content %}
<div class="container mx-auto px-4 lg:px-8 py-8 max-w-2xl">
    <div class="bg-white rounded-xl border border-emerald-200 shadow-sm p-8">
        <div class="flex items-center gap-3 mb-4"><i class="fas fa-check-circle text-emerald-600 text-2xl"></i><h1 class="text-2xl font-bold text-slate-900">Endpoint created</h1></div>
        <p class="text-sm text-slate-600 mb-5">Copy this API key now. It is stored as a one-way verifier and cannot be displayed again.</p>
        <div class="flex gap-2"><input id="new-api-key" readonly value="{{ newApiKey }}" class="flex-1 rounded-lg border-slate-300 font-mono text-sm"><button type="button" id="copy-api-key" class="px-4 py-2 rounded-lg bg-slate-700 text-white">Copy</button></div>
        <a href="{{ adapterBaseUrl }}" class="inline-block mt-6 text-blue-600 hover:text-blue-800">Return to adapter</a>
    </div>
</div>
<script>document.getElementById('copy-api-key').addEventListener('click', function () { navigator.clipboard.writeText(document.getElementById('new-api-key').value); this.textContent = 'Copied'; });</script>
{% endblock %}
