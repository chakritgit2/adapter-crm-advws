<section class="bg-gray-900 text-gray-200 py-12">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <!-- Site Logo / Name -->
                <div class="mb-4 md:mb-0 w-full place-items-center md:w-fit md:place-items-start">
                    <div class="place-items-center md:place-items-start">
                        <img width="50" height="50"
                            src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcT1_7dT3rah3cA77OAhPWJ1f-ieXM9JwlnZrg&s"
                            class="rounded-4xl" alt="SAMT Music Logo Small" loading="lazy" />
                        <div class="text-xl font-bold mt-2">SAMT Music</div>
                    </div>
                    <div class="text-lg">
                        <div>{{ t('index.footer.contact_hours') }}</div>
                        <a href="tel:0649474241" class="mt-2 inline-block text-4xl">092-599-4514</a>
                    </div>
                </div>
                <!-- Navigation Links -->
                <div class="w-full place-items-center md:w-fit md:place-items-start">
                    <h2 class="text-2xl md:text-2xl font-bold notosan">{{ t('index.footer.download_app') }}</h2>
                    <span class="hidden md:block">
                        {{ t('index.footer.download_hint') }}
                    </span>
                    <div class="flex space-x-4 mt-4 gap-x-2">
                        <a target="_blank" href="https://itunes.apple.com/th/app/samt-master/id1424840222?mt=8"
                            class="flex items-center bg-white text-gray-800 rounded-lg px-4 py-2 shadow hover:shadow-md transition gap-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" fill="currentColor"
                                class="bi bi-apple text-black" viewBox="0 0 16 16">
                                <path
                                    d="M11.182.008C11.148-.03 9.923.023 8.857 1.18c-1.066 1.156-.902 2.482-.878 2.516s1.52.087 2.475-1.258.762-2.391.728-2.43m3.314 11.733c-.048-.096-2.325-1.234-2.113-3.422s1.675-2.789 1.698-2.854-.597-.79-1.254-1.157a3.7 3.7 0 0 0-1.563-.434c-.108-.003-.483-.095-1.254.116-.508.139-1.653.589-1.968.607-.316.018-1.256-.522-2.267-.665-.647-.125-1.333.131-1.824.328-.49.196-1.422.754-2.074 2.237-.652 1.482-.311 3.83-.067 4.56s.625 1.924 1.273 2.796c.576.984 1.34 1.667 1.659 1.899s1.219.386 1.843.067c.502-.308 1.408-.485 1.766-.472.357.013 1.061.154 1.782.539.571.197 1.111.115 1.652-.105.541-.221 1.324-1.059 2.238-2.758q.52-1.185.473-1.282" />
                            </svg>
                            <div class="text-left leading-tight">
                                <div class="text-xs text-gray-500">{{ t('index.download_on') }}</div>
                                <div class="text-base font-semibold mt-[-4px]">Apple App Store</div>
                            </div>
                        </a>

                        <a target="_new" href="https://play.google.com/store/apps/details?id=com.mysamt.teacher"
                            class="flex items-center bg-green-400 text-gray-800 rounded-lg px-4 py-2 shadow hover:shadow-md transition gap-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="currentColor"
                                class="bi bi-google-play" viewBox="0 0 16 16">
                                <path
                                    d="M14.222 9.374c1.037-.61 1.037-2.137 0-2.748L11.528 5.04 8.32 8l3.207 2.96zm-3.595 2.116L7.583 8.68 1.03 14.73c.201 1.029 1.36 1.61 2.303 1.055zM1 13.396V2.603L6.846 8zM1.03 1.27l6.553 6.05 3.044-2.81L3.333.215C2.39-.341 1.231.24 1.03 1.27" />
                            </svg>
                            <div class="text-left leading-tight">
                                <div class="text-xs text-gray-800">{{ t('index.download_on') }}</div>
                                <div class="text-base font-semibold mt-[-4px]" style="word-break: keep-all;">Google Play
                                    Store
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
            <!-- Divider and Copyright -->
            <div class="border-t border-gray-700 mt-8 pt-4 text-center text-sm text-gray-400">
                © 2025 SAMT Music. {{ t('footer.rights_reserved') }}
            </div>
        </div>
    </section>


    <!-- Toast Container -->
    <div id="toast-container" class="fixed top-4 left-1/2 transform -translate-x-1/2 space-y-2 z-50"></div>


    <!-- JavaScript to toggle the mobile menu -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            function showToast(message, type = 'info', duration = 10000) {
                const colors = {
                    success: 'bg-green-500',
                    error: 'bg-red-500',
                    info: 'bg-blue-500',
                    warning: 'bg-yellow-500',
                };

                // Create toast element
                const toast = document.createElement('div');
                toast.className = `
        flex items-center justify-between max-w-md mx-auto px-4 py-2 rounded shadow-lg text-white
        ${colors[type] || colors.info}
        transform transition-transform duration-300
        translate-y-[-20px] opacity-0
      `;

                // Message
                const msg = document.createElement('span');
                msg.innerHTML = message;
                toast.appendChild(msg);

                // Close button
                const btn = document.createElement('button');
                btn.innerHTML = '&times;';
                btn.className = 'ml-4 text-xl leading-none focus:outline-none';
                btn.onclick = () => {
                    hideToast(toast);
                };
                toast.appendChild(btn);

                // Append to container
                const container = document.getElementById('toast-container');
                container.appendChild(toast);

                // Animate in
                requestAnimationFrame(() => {
                    toast.classList.remove('translate-y-[-20px]', 'opacity-0');
                });

                // Auto-hide
                if (duration > 0) {
                    setTimeout(() => hideToast(toast), duration);
                }
            }

            function hideToast(toast) {
                toast.classList.add('translate-y-[-20px]', 'opacity-0');
                toast.addEventListener('transitionend', () => toast.remove());
            }

            {% for type, messages in flashSession.getMessages() %}
            {% for message in messages %}
            showToast("{{ message }}", '{{ type }}');
            {% endfor %}
            {% endfor %}
        });
    </script>