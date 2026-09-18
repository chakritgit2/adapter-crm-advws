<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8" />
    <meta name="description"
        content="งานสอนดนตรี แพลตฟอร์มอันดับ 1 สมัครฟรี! เหมาะสำหรับคุณครูที่ต้องการอิสระในการรับงาน หรือ ต้องการขยายฐานลูกค้าแบบมืออาชีพ พร้อมระบบสนับสนุนมากมาย">
    <link rel="canonical" href="https://mysamt.com/master">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ title }} - ห้องซ้อม.com</title>
    <link rel="stylesheet" href="/css/main.css" />
    <link rel="apple-touch-icon" sizes="57x57" href="/icons/apple-icon-57x57.png">
    <link rel="apple-touch-icon" sizes="60x60" href="/icons/apple-icon-60x60.png">
    <link rel="apple-touch-icon" sizes="72x72" href="/icons/apple-icon-72x72.png">
    <link rel="apple-touch-icon" sizes="76x76" href="/icons/apple-icon-76x76.png">
    <link rel="apple-touch-icon" sizes="114x114" href="/icons/apple-icon-114x114.png">
    <link rel="apple-touch-icon" sizes="120x120" href="/icons/apple-icon-120x120.png">
    <link rel="apple-touch-icon" sizes="144x144" href="/icons/apple-icon-144x144.png">
    <link rel="apple-touch-icon" sizes="152x152" href="/icons/apple-icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/icons/apple-icon-180x180.png">
    <link rel="icon" type="image/png" sizes="192x192" href="/android-icon-192x192.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/icons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="96x96" href="/icons/favicon-96x96.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/icons/favicon-16x16.png">
    <link rel="manifest" href="/icons/manifest.json">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-TileImage" content="/icons/ms-icon-144x144.png">
    <meta name="theme-color" content="#ffffff">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@400..800&display=swap" rel="stylesheet">
    <link rel="shortcut icon" type="image/image/x-icon" href="favicon.ico?ver=1.1">

    {% if clarityOn == true %}
    <script type="text/javascript">
        (function(c,l,a,r,i,t,y){
            c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
            t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
            y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
        })(window, document, "clarity", "script", "i92tgasbcj");
    </script>
    {% endif %}

    <style>
        .notosan {
            font-family: 'Noto Sans Thai', sans-serif;
        }

        html {
            scroll-behavior: smooth;
        }
    </style>

    <!-- Add this CSS in the head section, after the existing styles -->
    <style>
        /* Toast notification styles */
        .toast-notification {
            position: fixed;
            top: 1rem;
            left: 50%;
            transform: translateX(-50%);
            z-index: 9999;
            min-width: 300px;
            max-width: 90%;
            padding: 1rem 1.5rem;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            display: flex;
            align-items: center;
            justify-content: space-between;
            animation: slideIn 0.3s ease-out forwards;
            opacity: 0;
            margin-bottom: 0.5rem;
        }

        .toast-notification.success {
            background-color: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        .toast-notification.error {
            background-color: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }

        .toast-notification.warning {
            background-color: #fef3c7;
            color: #92400e;
            border-left: 4px solid #f59e0b;
        }

        .toast-notification.info {
            background-color: #dbeafe;
            color: #1e40af;
            border-left: 4px solid #3b82f6;
        }

        .toast-close {
            background: none;
            border: none;
            color: inherit;
            cursor: pointer;
            padding: 0.25rem;
            margin-left: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background-color 0.2s;
        }

        .toast-close:hover {
            background-color: rgba(0, 0, 0, 0.1);
        }

        .toast-content {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex: 1;
        }

        html {
            scroll-behavior: smooth;
        }


        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translate(-50%, -20px);
            }

            to {
                opacity: 1;
                transform: translate(-50%, 0);
            }
        }

        @keyframes slideOut {
            to {
                opacity: 0;
                transform: translate(-50%, -20px);
            }
        }
    </style>
</head>

<body class="text-slate-800">

    <header class="bg-white border-b border-slate-200 sticky top-0 z-50 print:hidden">
        <nav class="container mx-auto px-4 lg:px-8 flex justify-between items-center h-20">
            <div class="flex items-center gap-8">
                <a href="/" class="text-2xl font-bold text-slate-900 notosan">ห้องซ้อม.com</a>
            </div>

            <div class="flex items-center gap-5">
                <button class="relative text-slate-500 hover:text-slate-900">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                    </svg>
                    <span class="absolute top-0 right-0 size-2 bg-red-500 rounded-full"></span>
                </button>
                <div class="w-px h-8 bg-slate-200"></div>
                <div class="flex items-center gap-3">
                    {% if user.id > 0 %}
                    {% if user.line_profile_image_url %}
                    <img class="size-10 rounded-full object-cover" src="{{ user.line_profile_image_url }}"
                        alt="User avatar">
                    {% endif %}
                    {% if user.google_profile_image_url %}
                    <img class="size-10 rounded-full object-cover" src="{{ user.google_profile_image_url }}"
                        alt="User avatar">
                    {% endif %}
                    <div class="text-sm">
                        <p class="text-slate-500">Good Morning</p>
                        <p class="font-semibold text-slate-900">{{ user.full_name }}</p>
                    </div>
                    {% else %}
                    <a href="/login"
                        class="text-base font-medium text-slate-600 hover:text-blue-600 transition">Login</a>
                    {% endif %}
                </div>
            </div>
        </nav>
    </header>

    <main class="bg-slate-50">
    {{ content() }}{% block content %}{% endblock %}
    </main>

    <footer class="bg-white border-t border-slate-200 print:hidden">
        <div class="container mx-auto px-4 lg:px-8 py-6">
            <div class="flex flex-col md:flex-row justify-between items-center gap-6 notosan">
                <div class="text-sm text-slate-500">
                    &copy; <?php echo date('Y'); ?> สงวนลิขสิทธิ์ ห้องซ้อม.com
                </div>
                <div class="flex items-center gap-6">
                    <a href="#" class="text-slate-500 hover:text-slate-800 transition text-sm">นโยบายความเป็นส่วนตัว</a>
                    <a href="#" class="text-slate-500 hover:text-slate-800 transition text-sm">เงื่อนไขการใช้บริการ</a>
                </div>
            </div>
        </div>
    </footer>

    <div id="toast-container"
        class="fixed top-0 left-0 right-0 z-50 pointer-events-none flex flex-col items-center pt-4"></div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const toastContainer = document.getElementById('toast-container');

            function createToast(message, type = 'info') {
                const toast = document.createElement('div');
                toast.className = `toast-notification ${type} pointer-events-auto`;

                let icon = '';
                switch (type) {
                    case 'success':
                        icon = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
                        break;
                    case 'error':
                        icon = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>';
                        break;
                    case 'warning':
                        icon = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>';
                        break;
                    default:
                        icon = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
                }

                toast.innerHTML = `
            <div class="toast-content">
                ${icon}
                <span class="text-sm font-medium">${message}</span>
            </div>
            <button type="button" class="toast-close" aria-label="Close">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        `;

                // Auto remove after 5 seconds
                const timer = setTimeout(() => {
                    toast.style.animation = 'slideOut 0.3s ease-out forwards';
                    setTimeout(() => toast.remove(), 300);
                }, 5000);

                // Close button functionality
                const closeButton = toast.querySelector('.toast-close');
                closeButton.addEventListener('click', () => {
                    clearTimeout(timer);
                    toast.style.animation = 'slideOut 0.3s ease-out forwards';
                    setTimeout(() => toast.remove(), 300);
                });

                return toast;
            }

    // Display flash messages
    <?php 
    $flashMessages = $this->flashSession->getMessages();
            if (!empty($flashMessages)):
                foreach($flashMessages as $type => $messages):
            $jsType = 'info';
            if (in_array($type, ['success', 'error', 'warning', 'info'])) {
                $jsType = $type;
            }
            foreach($messages as $message): 
    ?>
        const toast = createToast('<?= addslashes($message) ?>', '<?= $jsType ?>');
            toastContainer.appendChild(toast);
    <?php 
            endforeach;
            endforeach;
            endif; 
    ?>
});
    </script>

</body>
</html>