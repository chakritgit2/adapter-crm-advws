{% extends 'layouts/admin.volt' %}

{% block content %}
<main class="container mx-auto px-4 lg:px-8 py-12">
    <div class="max-w-4xl mx-auto">
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-slate-900 mb-2">{{ t('header.change_password') }}</h1>
            <p class="text-slate-600">{{ t('profile.subtitle') }}</p>
            <a href="/dashboard" class="text-blue-600 hover:text-blue-800 text-sm font-medium flex items-center mt-2">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                {{ t('profile.back_to_dashboard') }}
            </a>
        </div>

        <!-- Success/Error Messages -->
        {% if success is not empty %}
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6" role="alert">
            <span class="block sm:inline">{{ success }}</span>
        </div>
        {% endif %}

        {% if error is not empty %}
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
            <span class="block sm:inline">{{ error }}</span>
        </div>
        {% endif %}

        <!-- Password Change Card -->
        <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm mb-8">
            <h2 class="text-xl font-semibold text-slate-900 mb-6">{{ t('header.change_password') }}</h2>

            <form id="changePasswordForm" action="/dashboard/profile/save" method="POST"
                class="space-y-6">
                <div class="space-y-4">
                    <!-- Current Password -->
                    <div>
                        <label for="current_password" class="block text-sm font-medium text-slate-700 mb-1">
                            {{ t('profile.current_password') }}
                        </label>
                        <input type="password" name="current_password" id="current_password" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <!-- New Password -->
                    <div>
                        <label for="new_password" class="block text-sm font-medium text-slate-700 mb-1">
                            {{ t('profile.new_password') }}
                        </label>
                        <input type="password" name="new_password" id="new_password" required minlength="8"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            onkeyup="checkPasswordStrength()">

                        <!-- Password Strength Meter -->
                        <div class="mt-2">
                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                <div id="password-strength-bar" class="h-2.5 rounded-full" style="width: 0%;"></div>
                            </div>
                            <p id="password-strength-text" class="text-xs mt-1 text-gray-500">{{ t('profile.password_strength') }}<span
                                    id="strength-text">{{ t('profile.strength.very_weak') }}</span></p>
                        </div>

                        <p class="mt-1 text-xs text-gray-500">
                            {{ t('auth.password_weak') }}
                        </p>
                    </div>

                    <!-- Confirm New Password -->
                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-slate-700 mb-1">
                            {{ t('profile.confirm_password') }}
                        </label>
                        <input type="password" name="confirm_password" id="confirm_password" required minlength="8"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            onkeyup="checkPasswordMatch()">
                        <p id="password-match-message" class="text-xs mt-1"></p>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit"
                        class="text-sm cursor-pointer px-6 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        {{ t('profile.update_password') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
    function checkPasswordStrength() {
        const password = document.getElementById('new_password').value;
        const strengthBar = document.getElementById('password-strength-bar');
        const strengthText = document.getElementById('strength-text');

        // Reset
        let strength = 0;
        let text = '<?= $this->getDI()->get('locale')->t('profile.strength.very_weak') ?>';
        let color = 'bg-red-500';

        // Check password strength
        if (password.length >= 8) strength++;
        if (password.match(/[a-z]+/)) strength++;
        if (password.match(/[A-Z]+/)) strength++;
        if (password.match(/[0-9]+/)) strength++;
        if (password.match(/[!@#$%^&*(),.?":{}|<>]+/)) strength++;

        // Update UI based on strength
        switch (strength) {
            case 0:
            case 1:
                text = '<?= $this->getDI()->get('locale')->t('profile.strength.very_weak') ?>';
                color = 'bg-red-500';
                break;
            case 2:
                text = '<?= $this->getDI()->get('locale')->t('profile.strength.weak') ?>';
                color = 'bg-orange-500';
                break;
            case 3:
                text = '<?= $this->getDI()->get('locale')->t('profile.strength.moderate') ?>';
                color = 'bg-yellow-500';
                break;
            case 4:
                text = '<?= $this->getDI()->get('locale')->t('profile.strength.strong') ?>';
                color = 'bg-green-500';
                break;
            case 5:
                text = '<?= $this->getDI()->get('locale')->t('profile.strength.very_strong') ?>';
                color = 'bg-green-500';
                break;
        }

        // Update the strength bar
        const width = (strength / 5) * 100;
        strengthBar.style.width = width + '%';
        strengthBar.className = 'h-2.5 rounded-full ' + color;
        strengthText.textContent = text;
    }

    function checkPasswordMatch() {
        const password = document.getElementById('new_password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        const message = document.getElementById('password-match-message');

        if (password === '' || confirmPassword === '') {
            message.textContent = '';
            message.className = 'text-xs mt-1';
            return;
        }

        if (password === confirmPassword) {
            message.textContent = '<?= $this->getDI()->get('locale')->t('profile.passwords_match') ?>';
            message.className = 'text-xs mt-1 text-green-600';
        } else {
            message.textContent = '<?= $this->getDI()->get('locale')->t('auth.password_mismatch') ?>';
            message.className = 'text-xs mt-1 text-red-600';
        }
    }

    document.getElementById('changePasswordForm').addEventListener('submit', function (e) {
        const password = document.getElementById('new_password').value;
        const confirmPassword = document.getElementById('confirm_password').value;

        if (password !== confirmPassword) {
            e.preventDefault();
            alert('<?= $this->getDI()->get('locale')->t('auth.password_mismatch') ?>');
            return false;
        }

        if (password.length < 8) {
            e.preventDefault();
            alert('<?= $this->getDI()->get('locale')->t('profile.password_min_length') ?>');
            return false;
        }

        return true;
    });
</script>
{% endblock %}