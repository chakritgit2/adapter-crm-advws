{% if flashSession.has('success') %}
<div class="flash-message success mb-4">
    <div class="flex items-start">
        <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                fill="currentColor">
                <path fill-rule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                    clip-rule="evenodd" />
            </svg>
        </div>
        <div class="ml-3">
            <ul class="list-disc pl-5 mt-1 text-sm">
                {% for message in flashSession.getMessages('success') %}
                <li>{{ message }}</li>
                {% endfor %}
            </ul>
        </div>
    </div>
</div>
{% endif %}

{% if flashSession.has('error') %}
<div class="flash-message error mb-4">
    <div class="flex items-start">
        <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-red-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                fill="currentColor">
                <path fill-rule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                    clip-rule="evenodd" />
            </svg>
        </div>
        <div class="ml-3">
            <p class="text-sm font-medium">{{ t('flash.fix_errors') }}</p>
            <ul class="list-disc pl-5 mt-1 text-sm">
                {% for message in flashSession.getMessages('error') %}
                <li>{{ message }}</li>
                {% endfor %}
            </ul>
        </div>
    </div>
</div>
{% endif %}

{% if flashSession.has('warning') %}
<div class="flash-message warning mb-4 bg-yellow-100 text-yellow-700 border border-yellow-200">
    <div class="flex items-start">
        <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-yellow-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                fill="currentColor">
                <path fill-rule="evenodd"
                    d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                    clip-rule="evenodd" />
            </svg>
        </div>
        <div class="ml-3">
            <ul class="list-disc pl-5 mt-1 text-sm">
                {% for message in flashSession.getMessages('warning') %}
                <li>{{ message }}</li>
                {% endfor %}
            </ul>
        </div>
    </div>
</div>
{% endif %}

{% if flashSession.has('info') %}
<div class="flash-message info mb-4 bg-blue-100 text-blue-700 border border-blue-200">
    <div class="flex items-start">
        <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                fill="currentColor">
                <path fill-rule="evenodd"
                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h2a1 1 0 100-2h-2V9z"
                    clip-rule="evenodd" />
            </svg>
        </div>
        <div class="ml-3">
            <ul class="list-disc pl-5 mt-1 text-sm">
                {% for message in flashSession.getMessages('info') %}
                <li>{{ message }}</li>
                {% endfor %}
            </ul>
        </div>
    </div>
</div>
{% endif %}

{% if flashSession.has('validation') %}
<div class="flash-message error mb-4">
    <div class="flex items-start">
        <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-red-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                fill="currentColor">
                <path fill-rule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                    clip-rule="evenodd" />
            </svg>
        </div>
        <div class="ml-3">
            <p class="text-sm font-medium">{{ t('flash.fix_errors') }}</p>
            <ul class="list-disc pl-5 mt-1 text-sm">
                {% for message in flashSession.getMessages('validation') %}
                <li>{{ message }}</li>
                {% endfor %}
            </ul>
        </div>
    </div>
</div>
{% endif %}