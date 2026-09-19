{% extends 'layouts/admin.volt' %}

{% block content %}
<div class="container mx-auto px-4 lg:px-8 py-8 max-w-2xl">
    <div class="bg-white rounded-xl border border-emerald-200 shadow-sm p-8">
        <div class="flex items-center gap-3 mb-4"><i class="fas fa-check-circle text-emerald-600 text-2xl"></i><h1 class="text-2xl font-bold text-slate-900 notosan">Endpoint created</h1></div>
        <p class="text-sm text-slate-600 mb-5">Copy this API key now. It is stored as a one-way verifier and cannot be displayed again.</p>
        <div class="flex gap-2"><input id="new-api-key" readonly value="{{ newApiKey }}" class="flex-1 px-3 py-2 rounded-lg border border-slate-300 font-mono text-sm bg-slate-50 text-slate-900"><button type="button" id="copy-api-key" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-slate-700 text-white hover:bg-slate-800 text-sm font-medium focus:outline-hidden focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">Copy</button></div>
        <a href="{{ adapterBaseUrl }}" class="inline-flex items-center px-4 py-2 mt-6 rounded-lg text-blue-600 hover:text-blue-800 focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-blue-500 transition-colors">Return to adapter</a>
    </div>
</div>
<script>
(function () {
    var input = document.getElementById('new-api-key');
    var button = document.getElementById('copy-api-key');
    button.addEventListener('click', async function () {
        var copied = false;
        if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
            try {
                await navigator.clipboard.writeText(input.value);
                copied = true;
            } catch (error) {
                copied = false;
            }
        }
        if (!copied) {
            input.focus();
            input.select();
            try {
                copied = document.execCommand('copy');
            } catch (error) {
                copied = false;
            }
        }
        button.textContent = copied ? 'Copied' : 'Select and copy';
    });
}());
</script>
{% endblock %}
