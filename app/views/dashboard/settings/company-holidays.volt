{% extends 'layouts/admin.volt' %}

{% block content %}
<div class="container mx-auto px-4 lg:px-8 py-8">
    <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 notosan">{{ t('settings.company_holidays.title') }}</h1>
            <p class="text-sm text-slate-500 mt-1 notosan">{{ t('settings.company_holidays.subtitle') }}</p>
        </div>

        <!-- Year navigator -->
        <div class="flex items-center gap-2 bg-white border border-slate-200 rounded-lg px-2 py-1 shadow-sm">
            <a href="/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/settings/company-holidays?year={{ selectedYear - 1 }}"
                class="w-8 h-8 flex items-center justify-center rounded text-slate-600 hover:bg-slate-100 transition" title="{{ t('settings.company_holidays.prev_year') }}">
                <i class="fas fa-chevron-left"></i>
            </a>
            <form method="GET" action="/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/settings/company-holidays" class="flex items-center gap-1">
                <input type="number" name="year" value="{{ selectedYear }}" min="{{ currentYear - 5 }}" max="{{ currentYear + 10 }}"
                    class="w-20 text-center text-sm font-semibold text-slate-900 border border-slate-200 rounded px-1 py-1 outline-none focus:border-blue-400" />
            </form>
            <a href="/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/settings/company-holidays?year={{ selectedYear + 1 }}"
                class="w-8 h-8 flex items-center justify-center rounded text-slate-600 hover:bg-slate-100 transition" title="{{ t('settings.company_holidays.next_year') }}">
                <i class="fas fa-chevron-right"></i>
            </a>
            {% if selectedYear != currentYear %}
            <a href="/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/settings/company-holidays?year={{ currentYear }}"
                class="ml-1 px-2 py-1 text-xs font-medium text-blue-600 hover:bg-blue-50 rounded transition">{{ t('settings.company_holidays.this_year') }}</a>
            {% endif %}
        </div>
    </div>

    <!-- Legend / instructions -->
    <div class="mb-4 flex flex-wrap items-center gap-4 text-xs text-slate-600 notosan">
        <span class="inline-flex items-center gap-1.5">
            <span class="inline-block w-3.5 h-3.5 rounded bg-rose-500"></span>
            {{ t('settings.company_holidays.legend_holiday') }}
        </span>
        <span class="inline-flex items-center gap-1.5">
            <span class="inline-block w-3.5 h-3.5 rounded bg-white border border-slate-200"></span>
            {{ t('settings.company_holidays.legend_normal') }}
        </span>
        <span class="inline-flex items-center gap-1.5 text-slate-400">
            <i class="fas fa-info-circle"></i>
            {{ t('settings.company_holidays.hint_right_click') }}
        </span>
    </div>

    <!-- 12-month calendar grid -->
    <div id="holidaysCalendar" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4"></div>
</div>

<!-- Right-click context menu -->
<div id="holidayContextMenu" class="hidden fixed z-50 w-64 bg-white border border-slate-200 rounded-lg shadow-xl p-3 notosan">
    <div class="flex items-center justify-between mb-2">
        <span id="ctxDateLabel" class="text-sm font-semibold text-slate-900"></span>
        <button type="button" id="ctxCloseBtn" class="text-slate-400 hover:text-slate-600">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <!-- Mark form (shown when day is NOT a holiday) -->
    <div id="ctxMarkForm">
        <label class="block text-xs text-slate-500 mb-1">{{ t('settings.company_holidays.name_optional') }}</label>
        <input type="text" id="ctxHolidayName" maxlength="120"
            class="w-full text-sm border border-slate-200 rounded px-2 py-1.5 mb-2 outline-none focus:border-blue-400"
            placeholder="{{ t('settings.company_holidays.name_placeholder') }}" />
        <button type="button" id="ctxMarkBtn"
            class="w-full inline-flex items-center justify-center gap-2 px-3 py-2 bg-rose-600 text-white rounded-lg hover:bg-rose-700 transition text-sm font-medium">
            <i class="fas fa-calendar-check"></i>
            {{ t('settings.company_holidays.mark') }}
        </button>
    </div>

    <!-- Unmark form (shown when day IS a holiday) -->
    <div id="ctxUnmarkForm" class="hidden">
        <div id="ctxCurrentName" class="text-xs text-slate-500 mb-2 break-words"></div>
        <button type="button" id="ctxUnmarkBtn"
            class="w-full inline-flex items-center justify-center gap-2 px-3 py-2 bg-slate-100 text-slate-700 rounded-lg hover:bg-slate-200 transition text-sm font-medium">
            <i class="fas fa-calendar-times"></i>
            {{ t('settings.company_holidays.unmark') }}
        </button>
    </div>

    <div id="ctxError" class="hidden mt-2 text-xs text-red-600"></div>
</div>

<script>
(function () {
    var holidays = {{ holidaysJson }};
    var toggleUrl = "{{ toggleUrl }}";
    var selectedYear = {{ selectedYear }};

    var monthLabels = [
        "{{ t('settings.company_holidays.month_jan') }}", "{{ t('settings.company_holidays.month_feb') }}",
        "{{ t('settings.company_holidays.month_mar') }}", "{{ t('settings.company_holidays.month_apr') }}",
        "{{ t('settings.company_holidays.month_may') }}", "{{ t('settings.company_holidays.month_jun') }}",
        "{{ t('settings.company_holidays.month_jul') }}", "{{ t('settings.company_holidays.month_aug') }}",
        "{{ t('settings.company_holidays.month_sep') }}", "{{ t('settings.company_holidays.month_oct') }}",
        "{{ t('settings.company_holidays.month_nov') }}", "{{ t('settings.company_holidays.month_dec') }}"
    ];
    // Weekday header labels, Sunday-first.
    var weekdayLabels = [
        "{{ t('settings.company_holidays.dow_sun') }}", "{{ t('settings.company_holidays.dow_mon') }}",
        "{{ t('settings.company_holidays.dow_tue') }}", "{{ t('settings.company_holidays.dow_wed') }}",
        "{{ t('settings.company_holidays.dow_thu') }}", "{{ t('settings.company_holidays.dow_fri') }}",
        "{{ t('settings.company_holidays.dow_sat') }}"
    ];

    var container = document.getElementById('holidaysCalendar');
    var menu = document.getElementById('holidayContextMenu');
    var ctxDateLabel = document.getElementById('ctxDateLabel');
    var ctxMarkForm = document.getElementById('ctxMarkForm');
    var ctxUnmarkForm = document.getElementById('ctxUnmarkForm');
    var ctxCurrentName = document.getElementById('ctxCurrentName');
    var ctxNameInput = document.getElementById('ctxHolidayName');
    var ctxMarkBtn = document.getElementById('ctxMarkBtn');
    var ctxUnmarkBtn = document.getElementById('ctxUnmarkBtn');
    var ctxCloseBtn = document.getElementById('ctxCloseBtn');
    var ctxError = document.getElementById('ctxError');
    var ctxDate = null;

    function pad(n) { return n < 10 ? '0' + n : '' + n; }
    function dateKey(y, m, d) { return y + '-' + pad(m + 1) + '-' + pad(d); }

    function buildMonth(year, month) {
        var firstDay = new Date(year, month, 1);
        var startDow = firstDay.getDay(); // 0=Sun
        var daysInMonth = new Date(year, month + 1, 0).getDate();

        var card = document.createElement('div');
        card.className = 'bg-white rounded-xl border border-slate-200 shadow-sm p-3';

        var header = document.createElement('div');
        header.className = 'text-sm font-semibold text-slate-800 mb-2 notosan';
        header.textContent = monthLabels[month] + ' ' + year;
        card.appendChild(header);

        var grid = document.createElement('div');
        grid.className = 'grid grid-cols-7 gap-1 text-center';

        // Weekday header row
        weekdayLabels.forEach(function (w) {
            var cell = document.createElement('div');
            cell.className = 'text-[10px] font-semibold text-slate-400 py-0.5';
            cell.textContent = w;
            grid.appendChild(cell);
        });

        // Leading blanks
        for (var i = 0; i < startDow; i++) {
            var blank = document.createElement('div');
            grid.appendChild(blank);
        }

        // Day cells
        for (var d = 1; d <= daysInMonth; d++) {
            var key = dateKey(year, month, d);
            var isHoliday = holidays.hasOwnProperty(key);
            var dow = new Date(year, month, d).getDay();
            var isWeekend = dow === 0 || dow === 6;

            var day = document.createElement('div');
            day.className = 'day-cell text-xs rounded py-1.5 cursor-pointer select-none transition';
            day.setAttribute('data-date', key);

            if (isHoliday) {
                day.classList.add('bg-rose-500', 'text-white', 'font-semibold', 'hover:bg-rose-600');
                day.title = holidays[key] || '';
            } else if (isWeekend) {
                day.classList.add('text-slate-400', 'hover:bg-slate-100');
            } else {
                day.classList.add('text-slate-700', 'hover:bg-blue-50');
            }

            day.textContent = d;
            grid.appendChild(day);
        }

        card.appendChild(grid);
        return card;
    }

    function renderCalendar() {
        container.innerHTML = '';
        for (var m = 0; m < 12; m++) {
            container.appendChild(buildMonth(selectedYear, m));
        }
    }

    function closeMenu() {
        menu.classList.add('hidden');
        ctxError.classList.add('hidden');
        ctxError.textContent = '';
        ctxDate = null;
    }

    function openMenu(dateStr, x, y) {
        ctxDate = dateStr;
        ctxDateLabel.textContent = dateStr;

        var isHoliday = holidays.hasOwnProperty(dateStr);
        if (isHoliday) {
            ctxMarkForm.classList.add('hidden');
            ctxUnmarkForm.classList.remove('hidden');
            ctxCurrentName.textContent = holidays[dateStr]
                ? "{{ t('settings.company_holidays.current_holiday') }}: " + holidays[dateStr]
                : "{{ t('settings.company_holidays.current_holiday_unnamed') }}";
        } else {
            ctxUnmarkForm.classList.add('hidden');
            ctxMarkForm.classList.remove('hidden');
            ctxNameInput.value = '';
            setTimeout(function () { ctxNameInput.focus(); }, 10);
        }

        ctxError.classList.add('hidden');
        ctxError.textContent = '';

        // Position the menu, keeping it on-screen.
        menu.classList.remove('hidden');
        var rect = menu.getBoundingClientRect();
        var left = x, top = y;
        if (left + rect.width > window.innerWidth - 8) left = window.innerWidth - rect.width - 8;
        if (top + rect.height > window.innerHeight - 8) top = window.innerHeight - rect.height - 8;
        menu.style.left = Math.max(8, left) + 'px';
        menu.style.top = Math.max(8, top) + 'px';
    }

    function setBusy(busy) {
        ctxMarkBtn.disabled = busy;
        ctxUnmarkBtn.disabled = busy;
        ctxMarkBtn.classList.toggle('opacity-60', busy);
        ctxUnmarkBtn.classList.toggle('opacity-60', busy);
    }

    function showError(msg) {
        ctxError.textContent = msg;
        ctxError.classList.remove('hidden');
    }

    function toggleHoliday(dateStr, name, callback) {
        setBusy(true);
        var body = new URLSearchParams();
        body.append('date', dateStr);
        if (name) body.append('name', name);

        fetch(toggleUrl, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: body
        })
            .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, json: j }; }); })
            .then(function (res) {
                setBusy(false);
                if (!res.ok || !res.json || res.json.success === false) {
                    var msg = (res.json && res.json.message) ? res.json.message : "{{ t('settings.company_holidays.error_generic') }}";
                    showError(msg);
                    return;
                }
                if (res.json.is_holiday) {
                    holidays[res.json.date] = res.json.name || '';
                } else {
                    delete holidays[res.json.date];
                }
                renderCalendar();
                if (callback) callback();
            })
            .catch(function () {
                setBusy(false);
                showError("{{ t('settings.company_holidays.error_generic') }}");
            });
    }

    // Right-click on a day cell → open context menu.
    container.addEventListener('contextmenu', function (e) {
        var cell = e.target.closest('.day-cell');
        if (!cell) return;
        e.preventDefault();
        openMenu(cell.getAttribute('data-date'), e.clientX, e.clientY);
    });

    // Left-click also opens the menu (handy on touch / no right-click).
    container.addEventListener('click', function (e) {
        var cell = e.target.closest('.day-cell');
        if (!cell) return;
        var rect = cell.getBoundingClientRect();
        openMenu(cell.getAttribute('data-date'), rect.left, rect.bottom + 4);
    });

    ctxMarkBtn.addEventListener('click', function () {
        if (!ctxDate) return;
        var name = ctxNameInput.value.trim();
        toggleHoliday(ctxDate, name, closeMenu);
    });

    ctxUnmarkBtn.addEventListener('click', function () {
        if (!ctxDate) return;
        toggleHoliday(ctxDate, '', closeMenu);
    });

    ctxCloseBtn.addEventListener('click', closeMenu);

    ctxNameInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); ctxMarkBtn.click(); }
    });

    // Close menu on outside click / Escape.
    document.addEventListener('click', function (e) {
        if (menu.classList.contains('hidden')) return;
        if (!menu.contains(e.target) && !e.target.closest('.day-cell')) closeMenu();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeMenu();
    });

    renderCalendar();
})();
</script>
{% endblock %}
