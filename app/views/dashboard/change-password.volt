{% extends 'layouts/admin.volt' %}

{% block content %}
<div class="container mx-auto px-4 lg:px-8 py-8">
    <div class="max-w-md mx-auto">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-900 notosan">{{ t('auth.change_password') }}</h1>
            <p class="text-sm text-slate-500 mt-1 notosan">{{ t('auth.change_password_subtitle') }}</p>
        </div>

        {% if mustChange %}
        <div class="mb-6 bg-amber-50 border border-amber-200 rounded-lg p-4">
            <div class="flex items-start gap-3">
                <i class="fas fa-exclamation-triangle text-amber-500 text-lg mt-0.5"></i>
                <p class="text-sm text-amber-700 notosan">{{ t('auth.must_change_password_notice') }}</p>
            </div>
        </div>
        {% endif %}

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <form method="POST" action="/dashboard/change-password/store" class="space-y-5">

                <div>
                    <label for="new_password" class="block text-sm font-medium text-slate-700 mb-1 notosan">
                        {{ t('profile.new_password') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="password" name="new_password" id="new_password" required autocomplete="new-password"
                        class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm"
                        oninput="updateStrengthMeter(this.value)">

                    <!-- Strength meter (progress bar) -->
                    <div class="mt-2">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs text-slate-500 notosan">{{ t('emp.auth.password_strength') }}</span>
                            <span id="strength-label" class="text-xs font-medium text-slate-400 notosan">{{ t('emp.auth.strength_none') }}</span>
                        </div>
                        <!-- Track -->
                        <div class="h-2 w-full rounded-full bg-slate-200 overflow-hidden">
                            <!-- Fill -->
                            <div id="strength-bar" class="h-full rounded-full bg-slate-200 transition-all duration-300 ease-out" style="width: 0%"></div>
                        </div>
                        <div class="flex items-center justify-between mt-1">
                            <span id="strength-percent" class="text-[10px] text-slate-400 notosan">0%</span>
                            <span id="strength-score" class="text-[10px] text-slate-400 notosan">0/5</span>
                        </div>
                        <ul class="mt-2 space-y-1" id="criteria-list">
                            <li id="crit-length" class="flex items-center gap-1.5 text-xs text-slate-400 notosan transition-colors">
                                <i class="fas fa-circle text-[6px]"></i> {{ t('emp.auth.crit_length') }}
                            </li>
                            <li id="crit-upper" class="flex items-center gap-1.5 text-xs text-slate-400 notosan transition-colors">
                                <i class="fas fa-circle text-[6px]"></i> {{ t('emp.auth.crit_upper') }}
                            </li>
                            <li id="crit-lower" class="flex items-center gap-1.5 text-xs text-slate-400 notosan transition-colors">
                                <i class="fas fa-circle text-[6px]"></i> {{ t('emp.auth.crit_lower') }}
                            </li>
                            <li id="crit-digit" class="flex items-center gap-1.5 text-xs text-slate-400 notosan transition-colors">
                                <i class="fas fa-circle text-[6px]"></i> {{ t('emp.auth.crit_digit') }}
                            </li>
                            <li id="crit-special" class="flex items-center gap-1.5 text-xs text-slate-400 notosan transition-colors">
                                <i class="fas fa-circle text-[6px]"></i> {{ t('emp.auth.crit_special') }}
                            </li>
                        </ul>
                    </div>
                </div>

                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-slate-700 mb-1 notosan">
                        {{ t('profile.confirm_password') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="password" name="confirm_password" id="confirm_password" required autocomplete="new-password"
                        class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm"
                        oninput="updateMatchIndicator()">
                    <p id="match-indicator" class="mt-1 text-xs notosan hidden"></p>
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                    {% if not mustChange %}
                    <a href="/dashboard/profile"
                        class="px-4 py-2 text-sm font-medium text-slate-600 bg-slate-100 rounded-lg hover:bg-slate-200 transition-colors">
                        {{ t('common.cancel') }}
                    </a>
                    {% endif %}
                    <button type="submit"
                        class="px-6 py-2 cursor-pointer text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors shadow-sm">
                        {{ t('auth.change_password') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    var bar = document.getElementById('strength-bar');
    var label = document.getElementById('strength-label');
    var percentEl = document.getElementById('strength-percent');
    var scoreEl = document.getElementById('strength-score');
    var newPasswordInput = document.getElementById('new_password');
    var confirmPasswordInput = document.getElementById('confirm_password');
    var matchIndicator = document.getElementById('match-indicator');

    // Colour + label per strength level (0-5).
    var levels = [
        { bar: 'bg-slate-200',  text: 'text-slate-400',  label: '{{ t('emp.auth.strength_none') }}' },       // 0
        { bar: 'bg-red-500',    text: 'text-red-600',    label: '{{ t('emp.auth.strength_very_weak') }}' },  // 1
        { bar: 'bg-orange-500', text: 'text-orange-600', label: '{{ t('emp.auth.strength_weak') }}' },       // 2
        { bar: 'bg-amber-500',  text: 'text-amber-600',  label: '{{ t('emp.auth.strength_fair') }}' },       // 3
        { bar: 'bg-lime-500',   text: 'text-lime-600',   label: '{{ t('emp.auth.strength_good') }}' },       // 4
        { bar: 'bg-green-500',  text: 'text-green-600',  label: '{{ t('emp.auth.strength_strong') }}' },     // 5
    ];

    function setCriterion(id, ok) {
        var el = document.getElementById(id);
        if (!el) return;
        var icon = el.querySelector('i');
        if (ok) {
            el.classList.remove('text-slate-400');
            el.classList.add('text-green-600');
            icon.className = 'fas fa-check text-[10px]';
        } else {
            el.classList.remove('text-green-600');
            el.classList.add('text-slate-400');
            icon.className = 'fas fa-circle text-[6px]';
        }
    }

    window.updateStrengthMeter = function (value) {
        var checks = {
            length:  value.length >= 8,
            upper:   /[A-Z]/.test(value),
            lower:   /[a-z]/.test(value),
            digit:   /[0-9]/.test(value),
            special: /[^A-Za-z0-9]/.test(value),
        };

        setCriterion('crit-length',  checks.length);
        setCriterion('crit-upper',   checks.upper);
        setCriterion('crit-lower',   checks.lower);
        setCriterion('crit-digit',   checks.digit);
        setCriterion('crit-special', checks.special);

        // Score: 1 point per met criterion. Bonus: all 5 + length >= 12 => 5.
        var score = 0;
        for (var k in checks) { if (checks[k]) score++; }
        if (score === 5 && value.length >= 12) score = 5; else if (score === 5) score = 4;

        // Map score to percentage (0-100).
        var pct = (score / 5) * 100;
        var lvl = levels[score];

        // Update progress bar fill width + colour.
        bar.style.width = pct + '%';
        bar.className = 'h-full rounded-full transition-all duration-300 ease-out ' + lvl.bar;

        // Update label + colour.
        label.textContent = lvl.label;
        label.className = 'text-xs font-medium notosan ' + lvl.text;

        // Update percentage + score readout.
        percentEl.textContent = Math.round(pct) + '%';
        percentEl.className = 'text-[10px] notosan ' + lvl.text;
        scoreEl.textContent = score + '/5';
        scoreEl.className = 'text-[10px] notosan ' + lvl.text;

        // Re-check match indicator when password changes.
        if (confirmPasswordInput.value.length > 0) {
            updateMatchIndicator();
        }
    };

    window.updateMatchIndicator = function () {
        var np = newPasswordInput.value;
        var cp = confirmPasswordInput.value;

        if (cp.length === 0) {
            matchIndicator.classList.add('hidden');
            return;
        }

        matchIndicator.classList.remove('hidden');
        if (np === cp) {
            matchIndicator.textContent = '{{ t('emp.auth.passwords_match') }}';
            matchIndicator.className = 'mt-1 text-xs notosan text-green-600';
        } else {
            matchIndicator.textContent = '{{ t('emp.auth.passwords_dont_match') }}';
            matchIndicator.className = 'mt-1 text-xs notosan text-red-600';
        }
    };

    // Initialise meter state.
    updateStrengthMeter('');
})();
</script>
{% endblock %}
