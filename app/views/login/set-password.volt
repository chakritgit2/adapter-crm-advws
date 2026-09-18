{% extends 'layouts/loginwrap.volt' %}

{% block content %}
<div class="grid grid-cols-1 min-h-100 justify-center -mt-3 pb-16 items-center gradient-background flex-1">
    <div class="w-full max-w-md mx-auto grid grid-cols-1 mt-12 gap-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900 notosan">
                {{ t('auth.set_password') }}
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600 notosan">
                {{ t('auth.set_password_subtitle') }}
            </p>
        </div>
        <div class="bg-white p-6 mx-5 md:mx-0 rounded-lg shadow-xl">
            <form action="/set-password/{{ token }}/save" method="POST" id="setPasswordForm" class="flex flex-col gap-y-5">

                <!-- Password Input -->
                <div>
                    <label for="password" class="block text-md font-medium text-gray-700 notosan">
                        {{ t('auth.password') }} <span class="text-red-500">*</span>
                    </label>
                    <div class="relative mt-1">
                        <input type="password" name="password" id="password" required
                            class="block text-black w-full px-3 py-2 pr-10 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500"
                            placeholder="{{ t('auth.enter_password') }}">
                        <button type="button" class="eye-toggle absolute inset-y-0 right-0 px-3 flex items-center text-gray-500 hover:text-gray-700 focus:outline-none"
                            data-target="password" aria-label="<?= $this->getDI()->get('locale')->t('auth.show_password') ?>">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <!-- Confirm Password Input -->
                <div>
                    <label for="confirm_password" class="block text-md font-medium text-gray-700 notosan">
                        {{ t('auth.confirm_password') }} <span class="text-red-500">*</span>
                    </label>
                    <div class="relative mt-1">
                        <input type="password" name="confirm_password" id="confirm_password" required
                            class="block text-black w-full px-3 py-2 pr-10 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500"
                            placeholder="{{ t('auth.confirm_password_placeholder') }}">
                        <button type="button" class="eye-toggle absolute inset-y-0 right-0 px-3 flex items-center text-gray-500 hover:text-gray-700 focus:outline-none"
                            data-target="confirm_password" aria-label="<?= $this->getDI()->get('locale')->t('auth.show_password') ?>">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <p id="matchError" class="hidden mt-1 text-sm text-red-600 notosan">
                        {{ t('auth.password_mismatch') }}
                    </p>
                </div>

                <!-- Strength Indicator -->
                <div>
                    <div class="flex justify-between items-center mb-1">
                        <span class="text-sm font-medium text-gray-700 notosan">{{ t('auth.strength') }}</span>
                        <span id="strengthText" class="text-sm font-medium text-gray-500 notosan">-</span>
                    </div>
                    <div class="w-full h-2 bg-gray-200 rounded-full overflow-hidden">
                        <div id="strengthBar" class="h-full w-0 bg-red-500 transition-all duration-300"></div>
                    </div>
                </div>

                <!-- Validation Rules -->
                <ul class="space-y-1 text-sm text-gray-600 notosan">
                    <li id="ruleLength" class="flex items-center gap-2">
                        <i class="fas fa-circle text-xs text-gray-300"></i>
                        <span>{{ t('auth.rule_length') }}</span>
                    </li>
                    <li id="ruleUpper" class="flex items-center gap-2">
                        <i class="fas fa-circle text-xs text-gray-300"></i>
                        <span>{{ t('auth.rule_upper') }}</span>
                    </li>
                    <li id="ruleLower" class="flex items-center gap-2">
                        <i class="fas fa-circle text-xs text-gray-300"></i>
                        <span>{{ t('auth.rule_lower') }}</span>
                    </li>
                    <li id="ruleNumber" class="flex items-center gap-2">
                        <i class="fas fa-circle text-xs text-gray-300"></i>
                        <span>{{ t('auth.rule_number') }}</span>
                    </li>
                    <li id="ruleSpecial" class="flex items-center gap-2">
                        <i class="fas fa-circle text-xs text-gray-300"></i>
                        <span>{{ t('auth.rule_special') }}</span>
                    </li>
                </ul>

                <!-- Submit Button -->
                <div>
                    <button type="submit" id="submitBtn" disabled
                        class="w-full notosan cursor-pointer flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 disabled:opacity-50 disabled:cursor-not-allowed">
                        {{ t('auth.set_password') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('setPasswordForm');
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');
        const submitBtn = document.getElementById('submitBtn');
        const strengthBar = document.getElementById('strengthBar');
        const strengthText = document.getElementById('strengthText');
        const matchError = document.getElementById('matchError');

        const rules = {
            length: { el: document.getElementById('ruleLength'), regex: /.{8,}/ },
            upper: { el: document.getElementById('ruleUpper'), regex: /[A-Z]/ },
            lower: { el: document.getElementById('ruleLower'), regex: /[a-z]/ },
            number: { el: document.getElementById('ruleNumber'), regex: /[0-9]/ },
            special: { el: document.getElementById('ruleSpecial'), regex: /[^A-Za-z0-9]/ }
        };

        function updateRule(rule, valid) {
            const icon = rule.el.querySelector('i');
            const span = rule.el.querySelector('span');
            if (valid) {
                rule.el.classList.remove('text-gray-600');
                rule.el.classList.add('text-green-600');
                icon.classList.remove('fa-circle', 'text-gray-300');
                icon.classList.add('fa-check-circle', 'text-green-600');
            } else {
                rule.el.classList.add('text-gray-600');
                rule.el.classList.remove('text-green-600');
                icon.classList.add('fa-circle', 'text-gray-300');
                icon.classList.remove('fa-check-circle', 'text-green-600');
            }
        }

        function calculateStrength(value) {
            let score = 0;
            if (value.length >= 8) score++;
            if (value.length >= 12) score++;
            if (/[A-Z]/.test(value)) score++;
            if (/[a-z]/.test(value)) score++;
            if (/[0-9]/.test(value)) score++;
            if (/[^A-Za-z0-9]/.test(value)) score++;
            return Math.min(score, 5);
        }

        function validate() {
            const val = password.value;
            const confirmVal = confirmPassword.value;
            let allValid = true;

            for (const key in rules) {
                const valid = rules[key].regex.test(val);
                updateRule(rules[key], valid);
                if (!valid) allValid = false;
            }

            const strength = calculateStrength(val);
            const strengthLabels = [
                '<?= $this->getDI()->get('locale')->t('auth.strength_weak') ?>',
                '<?= $this->getDI()->get('locale')->t('auth.strength_fair') ?>',
                '<?= $this->getDI()->get('locale')->t('auth.strength_good') ?>',
                '<?= $this->getDI()->get('locale')->t('auth.strength_strong') ?>',
                '<?= $this->getDI()->get('locale')->t('auth.strength_very_strong') ?>'
            ];
            const strengthColors = ['bg-red-500', 'bg-yellow-500', 'bg-blue-500', 'bg-green-500', 'bg-emerald-600'];
            const widths = ['20%', '40%', '60%', '80%', '100%'];

            strengthBar.className = 'h-full transition-all duration-300 ' + (strengthColors[strength - 1] || 'bg-red-500');
            strengthBar.style.width = widths[strength - 1] || '0';
            strengthText.textContent = strength > 0 ? strengthLabels[strength - 1] : '-';
            strengthText.className = 'text-sm font-medium notosan ' + (strength >= 4 ? 'text-green-600' : strength >= 3 ? 'text-blue-600' : strength >= 2 ? 'text-yellow-600' : 'text-red-600');

            if (confirmVal && val !== confirmVal) {
                matchError.classList.remove('hidden');
                allValid = false;
            } else {
                matchError.classList.add('hidden');
            }

            if (confirmVal && val === confirmVal && allValid) {
                submitBtn.disabled = false;
            } else {
                submitBtn.disabled = true;
            }
        }

        password.addEventListener('input', validate);
        confirmPassword.addEventListener('input', validate);

        // Eye toggle behavior
        document.querySelectorAll('.eye-toggle').forEach(function (btn) {
            const targetId = btn.getAttribute('data-target');
            const targetInput = document.getElementById(targetId);
            const icon = btn.querySelector('i');

            function show() {
                targetInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            }

            function hide() {
                targetInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }

            btn.addEventListener('mousedown', show);
            btn.addEventListener('mouseup', hide);
            btn.addEventListener('mouseleave', hide);
            btn.addEventListener('touchstart', show, { passive: true });
            btn.addEventListener('touchend', hide);
        });
    });
</script>
{% endblock %}
