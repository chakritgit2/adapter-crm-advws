<?php

use Phalcon\Mvc\Router\Group as RouterGroup;

$router = $di->getRouter();
$config = $di->get('config');

// Remove trailing slashes automatically
$router->removeExtraSlashes(true);

// Set default controller/action
$router->setDefaults([
    'controller' => 'index',
    'action' => 'index',
]);

$tenantSlugPattern = '([a-zA-Z0-9\-]+)';

// =========================================================
// Public / legacy / auth routes (no tenant slug required)
// NOTE: These are registered AFTER the tenant groups near the
// bottom of this file. Phalcon matches routes in reverse order
// (last added wins), so registering them here would let the
// generic /{tenant_slug} pattern swallow /dashboard, /api,
// /reporting, /loginhrm, etc. They must be added last to take
// precedence.
// =========================================================

// $router->addGet('/select-tenant', [
//     'controller' => 'login',
//     'action' => 'selectTenant'
// ]);

// $router->addPost('/select-tenant/choose', [
//     'controller' => 'login',
//     'action' => 'chooseTenant'
// ]);

// =========================================================
// Tenant-level routes (only tenant_slug, no company_slug)
// NOTE: This group is DEFINED here but MOUNTED after the
// company-scoped group below. Phalcon matches routes in
// reverse order (last added wins), so mounting the
// tenant-level group AFTER the company group ensures that
// specific paths like /{tenant_slug}/companies/set-default
// take precedence over the generic
// /{tenant_slug}/{company_slug} pattern.
// =========================================================
$tenantLevelGroup = new RouterGroup([
    'controller' => 'tenant',
]);
$tenantLevelGroup->setPrefix('/{tenant_slug:' . $tenantSlugPattern . '}');

// Tenant root -> redirect to the user's default company
$tenantLevelGroup->addGet('', [
    'action' => 'dashboardRedirect'
]);

$tenantLevelGroup->addPost('/companies/set-default', [
    'action' => 'setDefaultCompany'
]);

// Catch company-scoped paths accessed without a company_slug (e.g.
// /{tenant_slug}/dashboard/settings). These would otherwise fall through
// to Phalcon's default routing and treat the tenant slug as a controller
// name, causing "handler class cannot be loaded" errors. The action
// redirects to the user's default company in this tenant, or to the
// global companies page when none is available.
$tenantLevelGroup->addGet('/dashboard{params:.*}', [
    'action' => 'dashboardRedirect'
]);
$tenantLevelGroup->addGet('/api{params:.*}', [
    'action' => 'dashboardRedirect'
]);
$tenantLevelGroup->addGet('/reporting{params:.*}', [
    'action' => 'dashboardRedirect'
]);

// NOTE: $tenantLevelGroup is mounted AFTER $tenantGroup below.
// See the comment at the top of the tenant-level section for why.

// =========================================================
// Company-scoped routes (tenant_slug + company_slug)
// All existing HR routes move under this nested prefix.
// Mounted FIRST so it has LOWER relevance than the
// tenant-level group (which is mounted after).
// =========================================================
$tenantGroup = new RouterGroup([
    'controller' => 'dashboard',
]);
$tenantGroup->setPrefix('/{tenant_slug:' . $tenantSlugPattern . '}/{company_slug:' . $tenantSlugPattern . '}');

// Company root -> adapter dashboard. The HR dashboard is retired because
// the employee/position tables are no longer part of the adapter database.
$tenantGroup->addGet('', [
    'controller' => 'adapter',
    'action' => 'index'
]);

// Dashboard routes
$tenantGroup->addGet('/dashboard', [
    'controller' => 'adapter',
    'action' => 'index'
]);
$tenantGroup->addGet('/dashboard/filter-employees', [
    'action' => 'filterEmployees'
]);
$tenantGroup->addGet('/dashboard/filter-employees-by-age', [
    'action' => 'filterEmployeesByAge'
]);
$tenantGroup->addGet('/dashboard/settings', [
    'action' => 'settings'
]);
// Help Center (Super Admin only)
$tenantGroup->addGet('/dashboard/help-center', [
    'action' => 'helpCenter'
]);

// Employee Milestone Event Types routes
$tenantGroup->addGet('/dashboard/settings/employee-milestone-event-types', [
    'action' => 'employeeMilestoneEventTypes'
]);
$tenantGroup->addGet('/dashboard/settings/employee-milestone-event-types/create', [
    'action' => 'employeeMilestoneEventTypesCreate'
]);
$tenantGroup->addPost('/dashboard/settings/employee-milestone-event-types/store', [
    'action' => 'employeeMilestoneEventTypesStore'
]);
$tenantGroup->addGet('/dashboard/settings/employee-milestone-event-types/edit/{id:[0-9]+}', [
    'action' => 'employeeMilestoneEventTypesEdit'
]);
$tenantGroup->addPost('/dashboard/settings/employee-milestone-event-types/update/{id:[0-9]+}', [
    'action' => 'employeeMilestoneEventTypesUpdate'
]);
$tenantGroup->addPost('/dashboard/settings/employee-milestone-event-types/delete/{id:[0-9]+}', [
    'action' => 'employeeMilestoneEventTypesDelete'
]);

// Job Levels routes
$tenantGroup->addGet('/dashboard/settings/job-levels', [
    'action' => 'jobLevels'
]);
$tenantGroup->addGet('/dashboard/settings/job-levels/create', [
    'action' => 'jobLevelsCreate'
]);
$tenantGroup->addPost('/dashboard/settings/job-levels/store', [
    'action' => 'jobLevelsStore'
]);
$tenantGroup->addGet('/dashboard/settings/job-levels/edit/{id:[0-9]+}', [
    'action' => 'jobLevelsEdit'
]);
$tenantGroup->addPost('/dashboard/settings/job-levels/update/{id:[0-9]+}', [
    'action' => 'jobLevelsUpdate'
]);
$tenantGroup->addPost('/dashboard/settings/job-levels/delete/{id:[0-9]+}', [
    'action' => 'jobLevelsDelete'
]);

// Custom Attributes routes
$tenantGroup->addGet('/dashboard/settings/custom-attributes', [
    'action' => 'customAttributes'
]);
$tenantGroup->addGet('/dashboard/settings/custom-attributes/create', [
    'action' => 'customAttributesCreate'
]);
$tenantGroup->addPost('/dashboard/settings/custom-attributes/store', [
    'action' => 'customAttributesStore'
]);
$tenantGroup->addGet('/dashboard/settings/custom-attributes/edit/{id:[0-9]+}', [
    'action' => 'customAttributesEdit'
]);
$tenantGroup->addPost('/dashboard/settings/custom-attributes/update/{id:[0-9]+}', [
    'action' => 'customAttributesUpdate'
]);
$tenantGroup->addPost('/dashboard/settings/custom-attributes/delete/{id:[0-9]+}', [
    'action' => 'customAttributesDelete'
]);

// Admin Users routes
$tenantGroup->addGet('/dashboard/settings/admin-users', [
    'action' => 'adminUsers'
]);
$tenantGroup->addGet('/dashboard/settings/admin-users/create', [
    'action' => 'adminUsersCreate'
]);
$tenantGroup->addPost('/dashboard/settings/admin-users/store', [
    'action' => 'adminUsersStore'
]);
$tenantGroup->addGet('/dashboard/settings/admin-users/edit/{id:[0-9]+}', [
    'action' => 'adminUsersEdit'
]);
$tenantGroup->addPost('/dashboard/settings/admin-users/update/{id:[0-9]+}', [
    'action' => 'adminUsersUpdate'
]);
$tenantGroup->addPost('/dashboard/settings/admin-users/delete/{id:[0-9]+}', [
    'action' => 'adminUsersDelete'
]);

// Leave Management routes
$tenantGroup->addGet('/dashboard/settings/leave-policies', [
    'action' => 'leavePolicies'
]);
$tenantGroup->addGet('/dashboard/settings/leave-policies/create', [
    'action' => 'leavePoliciesCreate'
]);
$tenantGroup->addPost('/dashboard/settings/leave-policies/store', [
    'action' => 'leavePoliciesStore'
]);
$tenantGroup->addGet('/dashboard/settings/leave-policies/edit/{id:[0-9a-fA-F-]{36}}', [
    'action' => 'leavePoliciesEdit'
]);
$tenantGroup->addPost('/dashboard/settings/leave-policies/update/{id:[0-9a-fA-F-]{36}}', [
    'action' => 'leavePoliciesUpdate'
]);
$tenantGroup->addPost('/dashboard/settings/leave-policies/delete/{id:[0-9a-fA-F-]{36}}', [
    'action' => 'leavePoliciesDelete'
]);
$tenantGroup->addPost('/dashboard/settings/leave-policies/{id:[0-9a-fA-F-]{36}}/year-end-mode', [
    'action' => 'leavePoliciesYearEndModeUpdate'
]);

// Company Holidays routes (Super Admin only)
$tenantGroup->addGet('/dashboard/settings/company-holidays', [
    'action' => 'companyHolidays'
]);
$tenantGroup->addPost('/dashboard/settings/company-holidays/toggle', [
    'action' => 'companyHolidaysToggle'
]);

// Leave Allowance Rules routes
$tenantGroup->addPost('/dashboard/settings/leave-policies/{id:[0-9a-fA-F-]{36}}/rules/store', [
    'action' => 'leaveAllowanceRulesStore'
]);
$tenantGroup->addPost('/dashboard/settings/leave-policies/{id:[0-9a-fA-F-]{36}}/rules/update/{ruleId:[0-9a-fA-F-]{36}}', [
    'action' => 'leaveAllowanceRulesUpdate'
]);
$tenantGroup->addPost('/dashboard/settings/leave-policies/{id:[0-9a-fA-F-]{36}}/rules/delete/{ruleId:[0-9a-fA-F-]{36}}', [
    'action' => 'leaveAllowanceRulesDelete'
]);
$tenantGroup->addPost('/dashboard/settings/leave-policies/{id:[0-9a-fA-F-]{36}}/rules/recalculate', [
    'action' => 'leaveAllowanceRulesRecalculate'
]);

$tenantGroup->addGet('/dashboard/settings/overtime-policies', [
    'controller' => 'overtime',
    'action' => 'policies'
]);
$tenantGroup->addGet('/dashboard/settings/overtime-policies/create', [
    'controller' => 'overtime',
    'action' => 'policiesCreate'
]);
$tenantGroup->addPost('/dashboard/settings/overtime-policies/store', [
    'controller' => 'overtime',
    'action' => 'policiesStore'
]);
$tenantGroup->addGet('/dashboard/settings/overtime-policies/edit/{id:[0-9a-fA-F-]{36}}', [
    'controller' => 'overtime',
    'action' => 'policiesEdit'
]);
$tenantGroup->addPost('/dashboard/settings/overtime-policies/update/{id:[0-9a-fA-F-]{36}}', [
    'controller' => 'overtime',
    'action' => 'policiesUpdate'
]);
$tenantGroup->addPost('/dashboard/settings/overtime-policies/delete/{id:[0-9a-fA-F-]{36}}', [
    'controller' => 'overtime',
    'action' => 'policiesDelete'
]);

// Language Management routes
$tenantGroup->addGet('/dashboard/settings/languages', [
    'controller' => 'translation',
    'action' => 'languages'
]);
$tenantGroup->addGet('/dashboard/settings/languages/create', [
    'controller' => 'translation',
    'action' => 'languagesCreate'
]);
$tenantGroup->addPost('/dashboard/settings/languages/store', [
    'controller' => 'translation',
    'action' => 'languagesStore'
]);
$tenantGroup->addPost('/dashboard/settings/languages/delete/{id:[0-9]+}', [
    'controller' => 'translation',
    'action' => 'languagesDelete'
]);

// Translation save/delete endpoints (used by settings forms for
// translated entity names; the translations browse/manage UI was removed)
$tenantGroup->addPost('/dashboard/translations/store', [
    'controller' => 'translation',
    'action' => 'store'
]);
$tenantGroup->addPost('/dashboard/translations/delete/{id:[0-9]+}', [
    'controller' => 'translation',
    'action' => 'delete'
]);

// Reporting routes
$tenantGroup->addGet('/reporting/campaign-performance', [
    'controller' => 'reporting',
    'action' => 'campaignPerformance'
]);
$tenantGroup->addGet('/reporting/active-alerts', [
    'controller' => 'reporting',
    'action' => 'activeAlerts'
]);
$tenantGroup->addGet('/reporting/test-results', [
    'controller' => 'reporting',
    'action' => 'testResults'
]);
$tenantGroup->addGet('/reporting/broken-urls', [
    'controller' => 'reporting',
    'action' => 'brokenUrls'
]);
$tenantGroup->addGet('/reporting/budget-pacing', [
    'controller' => 'reporting',
    'action' => 'budgetPacing'
]);
$tenantGroup->addGet('/reporting/zero-conversion-waste', [
    'controller' => 'reporting',
    'action' => 'zeroConversionWaste'
]);
$tenantGroup->addGet('/reporting/ab-test-losers', [
    'controller' => 'reporting',
    'action' => 'abTestLosers'
]);
$tenantGroup->addGet('/reporting/health-alerts', [
    'controller' => 'reporting',
    'action' => 'healthAlerts'
]);

// HR Reports routes (company-scoped, date-filtered analytics)
$tenantGroup->addGet('/dashboard/reports', [
    'controller' => 'hr-reports',
    'action' => 'index'
]);

$router->mount($tenantGroup);

// Mount the tenant-level group AFTER the company-scoped group.
// Phalcon matches routes in reverse order (last added wins),
// so the tenant-level group's specific routes (e.g.
// /{tenant_slug}/companies/set-default) take precedence over
// the generic /{tenant_slug}/{company_slug} pattern from the
// company-scoped group above.
$router->mount($tenantLevelGroup);

// =========================================================
// Public / legacy routes (no tenant slug required)
// These MUST be registered AFTER the tenant groups above,
// because Phalcon matches routes in reverse order (last
// added wins). If registered earlier, the generic
// /{tenant_slug} pattern would swallow /dashboard, /api,
// /reporting, /loginhrm, etc. and route them to
// TenantController/DashboardController with null context.
// =========================================================

// Root
$router->addGet('/', [
    'controller' => 'index',
    'action' => 'index'
]);

// Companies routes (global, no tenant slug required)
// /dashboard lands on the global companies page (post-login target)
$router->addGet('/dashboard', [
    'controller' => 'index',
    'action' => 'companies'
]);
$router->addGet('/dashboard/companies', [
    'controller' => 'index',
    'action' => 'companies'
]);
$router->addGet('/dashboard/companies/create', [
    'controller' => 'index',
    'action' => 'companiesCreate'
]);
$router->addGet('/dashboard/companies/edit/{id:[0-9]+}', [
    'controller' => 'index',
    'action' => 'companiesEdit'
]);
$router->addPost('/dashboard/companies/store', [
    'controller' => 'index',
    'action' => 'companiesStore'
]);
$router->addPost('/dashboard/companies/update/{id:[0-9]+}', [
    'controller' => 'index',
    'action' => 'companiesUpdate'
]);
$router->addPost('/dashboard/companies/delete/{id:[0-9]+}', [
    'controller' => 'index',
    'action' => 'companiesDelete'
]);

// Profile routes (global, no tenant slug required)
$router->addGet('/dashboard/profile', [
    'controller' => 'index',
    'action' => 'profile'
]);
$router->addPost('/dashboard/profile/save', [
    'controller' => 'index',
    'action' => 'profileSave'
]);

// Global language switcher (works on every page, no tenant/company context)
$router->addPost('/dashboard/set-language', [
    'controller' => 'index',
    'action' => 'setLanguage'
]);

// Forced password change (used when admin_users.must_change_password = 1)
$router->addGet('/dashboard/change-password', [
    'controller' => 'index',
    'action' => 'changePassword'
]);
$router->addPost('/dashboard/change-password/store', [
    'controller' => 'index',
    'action' => 'changePasswordSave'
]);

// Legacy URL redirects (GET only) - resolve default tenant
$router->addGet('/dashboard{params:.*}', [
    'controller' => 'index',
    'action' => 'legacyRedirect'
]);

$router->addGet('/api{params:.*}', [
    'controller' => 'index',
    'action' => 'legacyRedirect'
]);

$router->addGet('/reporting{params:.*}', [
    'controller' => 'index',
    'action' => 'legacyRedirect'
]);

// UDA passive API. This route is registered after the generic /api legacy
// redirect so Phalcon's reverse matching gives the specific route priority.
$router->addGet('/api/v1/{api_name:[a-zA-Z0-9_-]+}', [
    'controller' => 'adapter-api',
    'action' => 'fetch'
]);

// Adapter administration (tenant/company scoped and Super Admin only).
$adapterGroup = new RouterGroup([
    'controller' => 'adapter',
]);
$adapterGroup->setPrefix('/{tenant_slug:' . $tenantSlugPattern . '}/{company_slug:' . $tenantSlugPattern . '}/adapter');
$adapterGroup->addGet('', ['action' => 'index']);
$adapterGroup->addGet('/connections/create', ['action' => 'connectionCreate']);
$adapterGroup->addPost('/connections/store', ['action' => 'connectionStore']);
$adapterGroup->addGet('/connections/edit/{id:[0-9]+}', ['action' => 'connectionEdit']);
$adapterGroup->addPost('/connections/update/{id:[0-9]+}', ['action' => 'connectionUpdate']);
$adapterGroup->addPost('/connections/test/{id:[0-9]+}', ['action' => 'connectionTest']);
$adapterGroup->addPost('/connections/delete/{id:[0-9]+}', ['action' => 'connectionDelete']);
$adapterGroup->addGet('/endpoints/create', ['action' => 'endpointCreate']);
$adapterGroup->addPost('/endpoints/store', ['action' => 'endpointStore']);
$adapterGroup->addPost('/endpoints/delete/{id:[0-9]+}', ['action' => 'endpointDelete']);
$adapterGroup->addGet('/logs', ['action' => 'logs']);
$router->mount($adapterGroup);

// Auth routes (login / set-password / logout)
$router->add($config['loginPath'], [
    'controller' => 'login',
    'action' => 'index'
]);

$router->addPost($config['loginPath'] . '/authenticate', [
    'controller' => 'login',
    'action' => 'authenticate'
]);

$router->add($config['loginPath'].'/logout', [
    'controller' => 'login',
    'action' => 'logout'
]);

$router->add('/set-password/{token:[a-zA-Z0-9\-]+}', [
    'controller' => 'login',
    'action' => 'setPassword'
]);

$router->addPost('/set-password/{token:[a-zA-Z0-9\-]+}/save', [
    'controller' => 'login',
    'action' => 'setPasswordSave'
]);

// Handle the request
$router->handle($_SERVER['REQUEST_URI']);



// Return router
return $router;
