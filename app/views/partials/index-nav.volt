<!-- Responsive Nav -->
<nav class="sticky top-0 bg-[#ec0607] z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <!-- Left side: Logo and Desktop Links -->
            <div class="flex items-center">
                <!-- Logo -->
                <a href="https://www.mysamt.com" class="flex-shrink-0">
                    <img height="32" width="70" src="https://www.samtstore.com/assets/frontend/samt-logo-small.webp"
                        alt="SAMT Music Logo">
                </a>
                <!-- Desktop Menu -->
                <div class="hidden md:block">
                    <div class="ml-10 flex space-x-4">
                        <a href="https://www.mysamt.com"
                            class="px-3 py-2 rounded-md text-sm font-medium text-white hover:bg-gray-700 hover:text-white notosan">{{ t('index.nav.find_teacher') }}</a>
                        <a href="https://blog.mysamt.com"
                            class="px-3 py-2 rounded-md text-sm font-medium text-white hover:bg-gray-700 hover:text-white notosan">{{ t('index.nav.blog') }}</a>
                        <a href="https://www.mysamt.com/about-us"
                            class="px-3 py-2 rounded-md text-sm font-medium text-white hover:bg-gray-700 hover:text-white notosan">{{ t('index.nav.about_us') }}</a>
                        <a href="https://www.mysamt.com/contact-us"
                            class="px-3 py-2 rounded-md text-sm font-medium text-white hover:bg-gray-700 hover:text-white notosan">{{ t('index.nav.contact_us') }}</a>
                    </div>
                </div>
            </div>

            <!-- Right side: Cart, Login & Mobile Menu -->
            <div class="inline-flex items-center space-x-4">
                <!-- Cart Dropdown -->
                <div class="relative">
                    <button type="button" onclick="toggleCart()"
                        class="p-2 rounded-md text-white hover:text-gray-200 hover:bg-gray-800 focus:outline-none">
                        <!-- Cart Icon (Heroicons) -->
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                            class="bi bi-cart3" viewBox="0 0 16 16">
                            <path
                                d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .49.598l-1 5a.5.5 0 0 1-.465.401l-9.397.472L4.415 11H13a.5.5 0 0 1 0 1H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5M3.102 4l.84 4.479 9.144-.459L13.89 4zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4m7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4m-7 1a1 1 0 1 1 0 2 1 1 0 0 1 0-2m7 0a1 1 0 1 1 0 2 1 1 0 0 1 0-2" />
                        </svg>
                        <!-- Badge for item count -->
                        <span id="cart-count"
                            class="hidden top-0 right-0 items-center justify-center px-1.5 py-0.5 text-xs font-bold leading-none text-white bg-green-600 rounded-full"></span>
                    </button>
                    <!-- Dropdown panel -->
                    <div id="cart-dropdown"
                        class="origin-top-right absolute right-0 mt-2 w-64 rounded-md ring-3 ring-white shadow-lg bg-white hidden z-20">
                        <div class="pt-1" role="menu" aria-orientation="vertical" aria-labelledby="cart-button">
                            <!-- Example items -->
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                role="menuitem">Item 1</a>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                role="menuitem">Item 2</a>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                role="menuitem">Item 3</a>
                            <div class="border-t my-1"></div>
                        </div>
                        <a href="/checkout" rel="nofollow"
                            class="block px-4 py-2 text-lg font-medium text-end bg-red-500 text-white ring-3 ring-white rounded-b-md hover:bg-red-600 notosan"
                            role="menuitem">
                            {{ t('index.nav.checkout') }}
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                class="inline bi bi-chevron-double-right" viewBox="0 0 16 16">
                                <path fill-rule="evenodd"
                                    d="M3.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L9.293 8 3.646 2.354a.5.5 0 0 1 0-.708" />
                                <path fill-rule="evenodd"
                                    d="M7.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L13.293 8 7.646 2.354a.5.5 0 0 1 0-.708" />
                            </svg>
                            <div class="text-xs font-normal">{{ t('index.nav.checkout_hint') }}</div>
                        </a>
                    </div>
                </div>

                <!-- Login Button -->
                <a href="login"
                    class="btn inline-flex shadow-xl px-3 py-2 rounded-md bg-gray-800 hover:bg-gray-700 notosan">{{ t('header.login') }}</a>

                <!-- Mobile menu button -->
                <button type="button"
                    class="md:hidden inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-white hover:bg-gray-800 focus:outline-none"
                    aria-controls="mobile-menu" aria-expanded="false" onclick="toggleMenu()">
                    <span class="sr-only">{{ t('index.nav.open_menu') }}</span>
                    <!-- Hamburger icon -->
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Menu (hidden by default) -->
    <div class="md:hidden hidden" id="mobile-menu">
        <div class="px-2 pt-2 pb-3 space-y-1">
            <a href="https://www.mysamt.com"
                class="block px-3 py-2 rounded-md text-base font-medium text-gray-300 hover:bg-gray-700 hover:text-white">{{ t('index.nav.find_teacher') }}</a>
            <a href="https://blog.mysamt.com"
                class="block px-3 py-2 rounded-md text-base font-medium text-gray-300 hover:bg-gray-700 hover:text-white">{{ t('index.nav.blog') }}</a>
            <a href="https://www.mysamt.com/about-us"
                class="block px-3 py-2 rounded-md text-base font-medium text-gray-300 hover:bg-gray-700 hover:text-white">{{ t('index.nav.about_us') }}</a>
            <a href="https://www.mysamt.com/contact-us"
                class="block px-3 py-2 rounded-md text-base font-medium text-gray-300 hover:bg-gray-700 hover:text-white">{{ t('index.nav.contact_us') }}</a>
        </div>
    </div>
</nav>

<script>
    function toggleMenu() {
        document.getElementById('mobile-menu').classList.toggle('hidden');
    }
    function toggleCart() {
        document.getElementById('cart-dropdown').classList.toggle('hidden');
    }
    // Optional: close cart dropdown when clicking outside
    document.addEventListener('click', function (event) {
        const cartBtn = event.target.closest('button[onclick="toggleCart()"]');
        const dropdown = document.getElementById('cart-dropdown');
        if (!cartBtn && !dropdown.contains(event.target)) {
            dropdown.classList.add('hidden');
        }
    });

    document.addEventListener("DOMContentLoaded", function () {
        fetch(`/api/CartController/get?apikey=018a5dd459134f1da7936cc8b691d73cc7576fe9129c6448fe0dd12ade370b83`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        })
            .then(async res => {
                const payload = await res.json();
                if (!res.ok) throw payload;
                return payload;
            })
            .then(data => {
                // 1) Update badge
                const countEl = document.getElementById('cart-count');
                countEl.textContent = data.cart_items.length;

                if (data.cart_items.length > 0){
                    countEl.classList.remove("hidden");
                    countEl.classList.add("absolute","inline-flex");
                }

                // 2) Rebuild dropdown
                const menu = document.querySelector('#cart-dropdown [role="menu"]');
                menu.innerHTML = ''; // clear existing

                data.cart_items.forEach(item => {
                    const a = document.createElement('a');
                    a.href = `/detail/ecourse/${item.course_permalink}`;
                    a.className = 'flex items-center justify-between px-4 py-2 text-sm text-gray-700 hover:bg-gray-100';

                    // Left side: poster + course name
                    const left = document.createElement('div');
                    left.className = 'flex items-center space-x-2';

                    const img = document.createElement('img');
                    img.src = item.poster;
                    img.alt = item.course_name;
                    img.className = 'h-8 w-8 rounded-full object-cover';

                    const name = document.createElement('span');
                    name.textContent = item.course_name;

                    left.appendChild(img);
                    left.appendChild(name);

                    // Right side: amount
                    const amount = document.createElement('span');
                    amount.textContent = `${item.amount.toFixed(2)}`; // adjust currency symbol as needed
                    amount.className = 'text-sm font-medium text-gray-900';

                    a.appendChild(left);
                    a.appendChild(amount);
                    menu.appendChild(a);
                });

                // divider + checkout link
                const divider = document.createElement('div');
                divider.className = 'border-t my-1';
                menu.appendChild(divider);
            })
            .catch(err => {
                const msg = err.error || err.message || <?= $this->getDI()->get('locale')->t('index.failed_to_add_to_cart') ?>;
                showToast(msg, 'error');
            });
    });
</script>