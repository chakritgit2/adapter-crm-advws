<h4 class="d-flex justify-content-between align-items-center mb-3">
    <span class="text-white">{{ t('profile.menu_title') }}</span>
</h4>
<ul class="list-group mb-3 sticky-md-top notosan" style="top:1.25rem">
    <a href="/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/profile-getting-started"
        class="list-group-item d-flex align-items-center justify-content-between lh-sm">
        <div>
            <h6 class="my-0 mt-1">{{ t('profile.getting_started') }}</h6>
            <small class="text-muted">{{ t('profile.getting_started') }}</small>
        </div>
        <span class="text-muted"><i class="bi bi-chevron-right"></i></span>
    </a>
    <a href="/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/profile-customize" 
        class="list-group-item d-flex align-items-center justify-content-between lh-sm">
        <div>
            <h6 class="my-0 mt-1">{{ t('layout.profile') }}</h6>
            <small class="text-muted">{{ t('layout.profile') }}</small>
        </div>
        <span class="text-muted" id="menu_customize">
            {% if TEACHER['in_profile_review'] %}
                <i class="bi bi-hourglass-split"></i>
                <small>{{ t('profile.in_review') }}</small>
            {% else %}
                <i class="bi bi-chevron-right"></i>
            {% endif %}
        </span>
    </a>
    <a href="/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/profile-basic"
        class="list-group-item d-flex align-items-center justify-content-between lh-sm">
        <div>
            <h6 class="my-0 mt-1">{{ t('profile.personal_info') }}</h6>
            <small class="text-muted">{{ t('profile.personal_info') }}</small>
        </div>
        <span class="text-muted" id="menu_basic">
            {% if TEACHER['in_review'] %}
                <i class="bi bi-hourglass-split"></i>
                <small>{{ t('profile.in_review') }}</small>
            {% else %}
                <i class="bi bi-chevron-right"></i>
            {% endif %}
        </span>
    </a>
    <a href="/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/profile-education"
        class="list-group-item d-flex align-items-center justify-content-between lh-sm">
        <div>
            <h6 class="my-0 mt-1">{{ t('profile.education') }}</h6>
            <small class="text-muted">{{ t('profile.education') }}</small>
        </div>
        <span class="text-muted" id="menu_education">
            {% if TEACHER['in_profile_education_review'] %}
                <i class="bi bi-hourglass-split"></i>
                <small>{{ t('profile.in_review') }}</small>
            {% else %}
                <i class="bi bi-chevron-right"></i>
            {% endif %}
        </span>
    </a>
    <a href="/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/receiving-bank"
        class="list-group-item d-flex align-items-center justify-content-between lh-sm">
        <div>
            <h6 class="my-0 mt-1">{{ t('profile.receiving_bank') }}</h6>
            <small class="text-muted">{{ t('profile.receiving_bank') }}</small>
        </div>
        <span class="text-muted" id="menu_receiving_bank">
            {% if TEACHER['in_receive_bank_account_review'] %}
                <i class="bi bi-hourglass-split"></i>
                <small>{{ t('profile.in_review') }}</small>
            {% else %}
                <i class="bi bi-chevron-right"></i>
            {% endif %}
        </span>
    </a>
    <a href="/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/profile-opening-hour"
        class="list-group-item d-flex align-items-center justify-content-between lh-sm">
        <div>
            <h6 class="my-0 mt-1">{{ t('profile.opening_hour') }}</h6>
            <small class="text-muted">{{ t('profile.opening_hour') }}</small>
        </div>
        <span class="text-muted">
            <i class="bi bi-chevron-right"></i>
        </span>
    </a>
    
    <a href="/{{ currentTenantSlug }}/{{ currentCompanySlug }}/dashboard/password-change"
        class="list-group-item d-flex align-items-center justify-content-between lh-sm">
        <div>
            <h6 class="my-0 mt-1">{{ t('header.change_password') }}</h6>
            <small class="text-muted">{{ t('header.change_password') }}</small>
        </div>
        <span class="text-muted"><i class="bi bi-chevron-right"></i></span>
    </a>
</ul>