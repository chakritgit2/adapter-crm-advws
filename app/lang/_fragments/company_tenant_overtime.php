<?php

// Fragment: Company, Tenant, Profile, and Overtime view strings.
// Covers dashboard/companies.volt, companies-action.volt, companies-dashboard.volt,
// select-company.volt, tenant-dashboard.volt, tenant-companies.volt, tenant-billing.volt,
// profile.volt, overtime-request.volt, overtime-approvals.volt, overtime-ledger.volt.
// Also includes related flash messages and view titles.
// Thai is the default UI language; English is the fallback.

return [
    // ---- company list (companies.volt) ----
    'company.list.subtitle'        => ['en' => 'View and manage your organizations.', 'th' => 'ดูและจัดการองค์กรของคุณ'],
    'company.list.new'             => ['en' => 'New Company', 'th' => 'เพิ่มบริษัท'],
    'company.list.search_placeholder' => ['en' => 'Search companies...', 'th' => 'ค้นหาบริษัท...'],
    'company.list.slug'            => ['en' => 'Slug', 'th' => 'Slug'],
    'company.list.no_companies'    => ['en' => 'No companies yet', 'th' => 'ยังไม่มีบริษัท'],
    'company.list.no_companies_hint' => ['en' => 'Get started by adding your first company.', 'th' => 'เริ่มต้นโดยเพิ่มบริษัทแรกของคุณ'],
    'company.list.add'             => ['en' => 'Add Company', 'th' => 'เพิ่มบริษัท'],
    'company.list.confirm_delete'  => ['en' => 'Are you sure you want to delete this company?', 'th' => 'คุณแน่ใจหรือไม่ว่าต้องการลบบริษัทนี้?'],

    // ---- company status ----
    'company.status.suspended'     => ['en' => 'Suspended', 'th' => 'ถูกระงับ'],

    // ---- company action (companies-action.volt) ----
    'company.action.edit_title'    => ['en' => 'Edit Company', 'th' => 'แก้ไขบริษัท'],
    'company.action.new_title'     => ['en' => 'New Company', 'th' => 'เพิ่มบริษัท'],
    'company.action.edit_subtitle' => ['en' => 'Update company details.', 'th' => 'อัปเดตรายละเอียดบริษัท'],
    'company.action.new_subtitle'  => ['en' => 'Create a new organization.', 'th' => 'สร้างองค์กรใหม่'],
    'company.action.back'          => ['en' => 'Back to Companies', 'th' => 'กลับไปยังบริษัท'],
    'company.action.name'          => ['en' => 'Company Name', 'th' => 'ชื่อบริษัท'],
    'company.action.name_placeholder' => ['en' => 'Acme Corporation', 'th' => 'Acme Corporation'],
    'company.action.slug_placeholder' => ['en' => 'acme-corporation', 'th' => 'acme-corporation'],
    'company.action.slug_hint'     => ['en' => 'Auto-generated from name. Used in URLs.', 'th' => 'สร้างอัตโนมัติจากชื่อ ใช้ใน URL'],
    'company.action.custom_fields' => ['en' => 'Custom Fields', 'th' => 'ฟิลด์เพิ่มเติม'],
    'company.action.select_option' => ['en' => '-- Select --', 'th' => '-- เลือก --'],
    'company.action.enter_value'   => ['en' => 'Enter value', 'th' => 'กรอกค่า'],
    'company.action.update'        => ['en' => 'Update Company', 'th' => 'อัปเดตบริษัท'],
    'company.action.create'        => ['en' => 'Create Company', 'th' => 'สร้างบริษัท'],

    // ---- company dashboard (companies-dashboard.volt) ----
    'company.dashboard.subtitle'   => ['en' => 'Company overview and metrics.', 'th' => 'ภาพรวมและสถิติของบริษัท'],
    'company.dashboard.age_distribution' => ['en' => 'Age Bracket Distribution', 'th' => 'การกระจายตามช่วงอายุ'],
    'company.dashboard.upcoming_birthdays' => ['en' => 'Upcoming Birthdays', 'th' => 'วันเกิดที่กำลังจะมาถึง'],
    'company.dashboard.no_birthdays' => ['en' => 'No upcoming birthdays in the next 30 days.', 'th' => 'ไม่มีวันเกิดที่กำลังจะมาถึงใน 30 วันข้างหน้า'],
    'company.dashboard.custom_breakdown' => ['en' => 'Custom Attribute Breakdown', 'th' => 'การกระจายตามคุณสมบัติเพิ่มเติม'],
    'company.dashboard.employee_roster' => ['en' => 'Employee Roster', 'th' => 'รายชื่อพนักงาน'],
    'company.dashboard.reset_filters' => ['en' => 'Reset Filters', 'th' => 'รีเซ็ตตัวกรอง'],
    'company.dashboard.email'      => ['en' => 'Email', 'th' => 'อีเมล'],
    'company.dashboard.department' => ['en' => 'Department', 'th' => 'แผนก'],
    'company.dashboard.position'   => ['en' => 'Position', 'th' => 'ตำแหน่ง'],
    'company.dashboard.dob'        => ['en' => 'Date of Birth', 'th' => 'วันเกิด'],
    'company.dashboard.age'        => ['en' => 'Age', 'th' => 'อายุ'],

    // ---- company select (select-company.volt) ----
    'company.select.title'         => ['en' => 'Select Company', 'th' => 'เลือกบริษัท'],
    'company.select.subtitle_prefix' => ['en' => 'Choose a company under', 'th' => 'เลือกบริษัทภายใต้'],
    'company.select.subtitle_suffix' => ['en' => 'to continue.', 'th' => 'เพื่อดำเนินการต่อ'],
    'company.select.redirect_prefix' => ['en' => "You'll be taken to", 'th' => 'คุณจะถูกนำไปยัง'],
    'company.select.open'          => ['en' => 'Open', 'th' => 'เปิด'],
    'company.select.no_companies'  => ['en' => 'No companies available', 'th' => 'ไม่มีบริษัทให้เลือก'],
    'company.select.no_access_prefix' => ['en' => "You don't have access to any companies under", 'th' => 'คุณยังไม่มีสิทธิ์เข้าถึงบริษัทใดๆ ภายใต้'],
    'company.select.no_access_suffix' => ['en' => 'yet.', 'th' => ''],
    'company.select.contact_admin' => ['en' => 'Please contact a tenant administrator to be assigned to a company.', 'th' => 'กรุณาติดต่อผู้ดูแลเทนนันต์เพื่อให้มอบหมายบริษัทให้คุณ'],

    // ---- tenant dashboard (tenant-dashboard.volt) ----
    'tenant.dashboard.subtitle'    => ['en' => 'Tenant dashboard — overview of all companies under this tenant.', 'th' => 'แดชบอร์ดเทนนันต์ — ภาพรวมบริษัททั้งหมดภายใต้เทนนันต์นี้'],
    'tenant.dashboard.total_companies' => ['en' => 'Total Companies', 'th' => 'บริษัททั้งหมด'],
    'tenant.dashboard.plan_billing' => ['en' => 'Plan & Billing', 'th' => 'แผนและการเรียกเก็บเงิน'],
    'tenant.dashboard.manage'      => ['en' => 'Manage', 'th' => 'จัดการ'],
    'tenant.dashboard.current_plan' => ['en' => 'Current Plan', 'th' => 'แผนปัจจุบัน'],
    'tenant.dashboard.billing_email' => ['en' => 'Billing Email', 'th' => 'อีเมลเรียกเก็บเงิน'],
    'tenant.dashboard.view_all'    => ['en' => 'View all', 'th' => 'ดูทั้งหมด'],
    'tenant.dashboard.view_dashboard' => ['en' => 'View Dashboard', 'th' => 'ดูแดชบอร์ด'],
    'tenant.dashboard.no_companies_hint' => ['en' => 'Get started by adding your first company under this tenant.', 'th' => 'เริ่มต้นโดยเพิ่มบริษัทแรกของคุณภายใต้เทนนันต์นี้'],
    'tenant.not_set'               => ['en' => 'Not set', 'th' => 'ไม่ได้ตั้งค่า'],

    // ---- tenant companies (tenant-companies.volt) ----
    'tenant.companies.subtitle_prefix' => ['en' => 'Companies under', 'th' => 'บริษัทภายใต้'],

    // ---- tenant billing (tenant-billing.volt) ----
    'tenant.billing.title'         => ['en' => 'Billing & Plan', 'th' => 'การเรียกเก็บเงินและแผน'],
    'tenant.billing.subtitle_prefix' => ['en' => 'Manage the subscription plan for', 'th' => 'จัดการแผนสมัครสมาชิกสำหรับ'],
    'tenant.billing.plan'          => ['en' => 'Plan', 'th' => 'แผน'],
    'tenant.billing.tenant_created' => ['en' => 'Tenant created', 'th' => 'สร้างเทนนันต์เมื่อ'],
    'tenant.billing.usage'         => ['en' => 'Usage', 'th' => 'การใช้งาน'],
    'tenant.billing.avg_employees' => ['en' => 'Average Employees/Company', 'th' => 'พนักงานเฉลี่ย/บริษัท'],
    'tenant.billing.coming_soon'   => ['en' => 'Billing management coming soon', 'th' => 'การจัดการการเรียกเก็บเงินจะมาเร็วๆ นี้'],
    'tenant.billing.placeholder_text' => ['en' => 'This is a placeholder for the billing/plan management interface. Integration with a billing provider (e.g. Stripe) will be added here.', 'th' => 'นี่คือพื้นที่สำหรับส่วนติดต่อจัดการการเรียกเก็บเงิน/แผน การเชื่อมต่อกับผู้ให้บริการเรียกเก็บเงิน (เช่น Stripe) จะถูกเพิ่มที่นี่'],

    // ---- profile (profile.volt) ----
    'profile.subtitle'             => ['en' => 'Manage your account settings and security', 'th' => 'จัดการการตั้งค่าบัญชีและความปลอดภัยของคุณ'],
    'profile.back_to_dashboard'    => ['en' => 'Back to Dashboard', 'th' => 'กลับไปยังแดชบอร์ด'],
    'profile.current_password'     => ['en' => 'Current Password', 'th' => 'รหัสผ่านปัจจุบัน'],
    'profile.new_password'         => ['en' => 'New Password', 'th' => 'รหัสผ่านใหม่'],
    'profile.password_strength'    => ['en' => 'Password strength: ', 'th' => 'ความแข็งแกร่งของรหัสผ่าน: '],
    'profile.strength.very_weak'   => ['en' => 'Very Weak', 'th' => 'อ่อนมาก'],
    'profile.strength.weak'        => ['en' => 'Weak', 'th' => 'อ่อน'],
    'profile.strength.moderate'    => ['en' => 'Moderate', 'th' => 'ปานกลาง'],
    'profile.strength.strong'      => ['en' => 'Strong', 'th' => 'แข็งแกร่ง'],
    'profile.strength.very_strong' => ['en' => 'Very Strong', 'th' => 'แข็งแกร่งมาก'],
    'profile.confirm_password'     => ['en' => 'Confirm New Password', 'th' => 'ยืนยันรหัสผ่านใหม่'],
    'profile.update_password'      => ['en' => 'Update Password', 'th' => 'อัปเดตรหัสผ่าน'],
    'profile.passwords_match'      => ['en' => 'Passwords match!', 'th' => 'รหัสผ่านตรงกัน!'],
    'profile.password_min_length'  => ['en' => 'Password must be at least 8 characters long', 'th' => 'รหัสผ่านต้องมีความยาวอย่างน้อย 8 ตัวอักษร'],

    // ---- overtime request (overtime-request.volt) ----
    'overtime.request.back'        => ['en' => 'Back to Overtime Approvals', 'th' => 'กลับไปยังการอนุมัติล่วงเวลา'],
    'overtime.request.subtitle'    => ['en' => 'Submit an overtime request for an employee.', 'th' => 'ส่งคำขอล่วงเวลาสำหรับพนักงาน'],
    'overtime.request.employee'    => ['en' => 'Employee', 'th' => 'พนักงาน'],
    'overtime.request.select_employee' => ['en' => 'Select an employee', 'th' => 'เลือกพนักงาน'],
    'overtime.request.policy'      => ['en' => 'Overtime Policy', 'th' => 'นโยบายล่วงเวลา'],
    'overtime.request.select_policy' => ['en' => 'Select a policy', 'th' => 'เลือกนโยบาย'],
    'overtime.request.no_policies' => ['en' => 'No active overtime policies found.', 'th' => 'ไม่พบนโยบายล่วงเวลาที่ใช้งานอยู่'],
    'overtime.request.create_one_prefix' => ['en' => 'Please', 'th' => 'กรุณา'],
    'overtime.request.create_one'  => ['en' => 'create one', 'th' => 'สร้างนโยบาย'],
    'overtime.request.create_one_suffix' => ['en' => 'first.', 'th' => 'ก่อน'],
    'overtime.request.start_time'  => ['en' => 'Start Time', 'th' => 'เวลาเริ่มต้น'],
    'overtime.request.end_time'    => ['en' => 'End Time', 'th' => 'เวลาสิ้นสุด'],
    'overtime.request.compensation_type' => ['en' => 'Compensation Type', 'th' => 'ประเภทการชดเชย'],
    'overtime.request.cash_payout' => ['en' => 'Cash Payout', 'th' => 'จ่ายเป็นเงินสด'],
    'overtime.request.toil'        => ['en' => 'Time Off In Lieu (TOIL)', 'th' => 'เวลาชดเชย (TOIL)'],
    'overtime.request.worked_preview' => ['en' => 'Worked minutes will be calculated from start and end time.', 'th' => 'นาทีที่ทำงานจะถูกคำนวณจากเวลาเริ่มต้นและเวลาสิ้นสุด'],
    'overtime.request.reason'      => ['en' => 'Reason', 'th' => 'เหตุผล'],
    'overtime.request.reason_placeholder' => ['en' => 'Describe why overtime was worked...', 'th' => 'อธิบายเหตุผลที่ทำงานล่วงเวลา...'],
    'overtime.request.submit'      => ['en' => 'Submit Request', 'th' => 'ส่งคำขอ'],
    'overtime.request.end_after_start' => ['en' => 'End time must be after start time.', 'th' => 'เวลาสิ้นสุดต้องอยู่หลังเวลาเริ่มต้น'],
    'overtime.request.worked_time_prefix' => ['en' => 'Worked time: ', 'th' => 'เวลาที่ทำงาน: '],
    'overtime.request.minutes'     => ['en' => 'minutes', 'th' => 'นาที'],

    // ---- overtime approvals (overtime-approvals.volt) ----
    'overtime.approvals.subtitle'  => ['en' => 'Review and approve employee overtime requests.', 'th' => 'ตรวจสอบและอนุมัติคำขอล่วงเวลาของพนักงาน'],
    'overtime.approvals.new_request' => ['en' => 'New Overtime Request', 'th' => 'คำขอล่วงเวลาใหม่'],
    'overtime.approvals.pending_requests' => ['en' => 'Pending Requests', 'th' => 'คำขอที่รอดำเนินการ'],
    'overtime.approvals.pending_toil' => ['en' => 'Pending TOIL Hours', 'th' => 'ชั่วโมง TOIL ที่รอดำเนินการ'],
    'overtime.approvals.pending_payout' => ['en' => 'Pending Payout Hours', 'th' => 'ชั่วโมงจ่ายเงินที่รอดำเนินการ'],
    'overtime.approvals.pending_approval' => ['en' => 'Pending Approval', 'th' => 'รอการอนุมัติ'],
    'overtime.approvals.shift_date' => ['en' => 'Shift Date', 'th' => 'วันที่ทำงาน'],
    'overtime.approvals.duration'  => ['en' => 'Duration', 'th' => 'ระยะเวลา'],
    'overtime.approvals.compensation' => ['en' => 'Compensation', 'th' => 'การชดเชย'],
    'overtime.approvals.approve'   => ['en' => 'Approve', 'th' => 'อนุมัติ'],
    'overtime.approvals.reject'    => ['en' => 'Reject', 'th' => 'ปฏิเสธ'],
    'overtime.approvals.no_pending' => ['en' => 'No pending overtime requests.', 'th' => 'ไม่มีคำขอล่วงเวลาที่รออนุมัติ'],
    'overtime.approvals.history'   => ['en' => 'History', 'th' => 'ประวัติ'],
    'overtime.approvals.processed_by' => ['en' => 'Processed By', 'th' => 'ดำเนินการโดย'],
    'overtime.approvals.no_history' => ['en' => 'No processed overtime requests yet.', 'th' => 'ยังไม่มีคำขอล่วงเวลาที่ดำเนินการแล้ว'],

    // ---- overtime compensation labels ----
    'overtime.compensation.toil'   => ['en' => 'TOIL', 'th' => 'TOIL'],
    'overtime.compensation.payout' => ['en' => 'Payout', 'th' => 'จ่ายเงิน'],

    // ---- overtime ledger (overtime-ledger.volt) ----
    'overtime.ledger.subtitle'     => ['en' => 'Company-wide audit trail of overtime requests and TOIL balances.', 'th' => 'บันทึกการตรวจสอบล่วงเวลาทั้งบริษัทและยอดคงเหลือ TOIL'],
    'overtime.ledger.new_request'  => ['en' => 'New Request', 'th' => 'คำขอใหม่'],
    'overtime.ledger.policies'     => ['en' => 'Policies', 'th' => 'นโยบาย'],
    'overtime.ledger.toil_only'    => ['en' => 'TOIL Only', 'th' => 'TOIL เท่านั้น'],
    'overtime.ledger.payout_only'  => ['en' => 'Payout Only', 'th' => 'จ่ายเงินเท่านั้น'],
    'overtime.ledger.search_placeholder' => ['en' => 'Search ledger...', 'th' => 'ค้นหาบันทึก...'],
    'overtime.ledger.worked'       => ['en' => 'Worked', 'th' => 'ที่ทำงาน'],
    'overtime.ledger.compensated'  => ['en' => 'Compensated', 'th' => 'ที่ได้รับชดเชย'],
    'overtime.ledger.toil_balance' => ['en' => 'TOIL Balance', 'th' => 'ยอดคงเหลือ TOIL'],
    'overtime.ledger.approver'     => ['en' => 'Approver', 'th' => 'ผู้อนุมัติ'],
    'overtime.ledger.edit_balance' => ['en' => 'Edit Balance', 'th' => 'แก้ไขยอดคงเหลือ'],
    'overtime.ledger.no_records'   => ['en' => 'No overtime records found', 'th' => 'ไม่พบบันทึกล่วงเวลา'],
    'overtime.ledger.no_records_hint' => ['en' => 'Submit overtime requests to populate the ledger.', 'th' => 'ส่งคำขอล่วงเวลาเพื่อเพิ่มข้อมูลในบันทึก'],
    'overtime.ledger.submit_request' => ['en' => 'Submit Overtime Request', 'th' => 'ส่งคำขอล่วงเวลา'],
    'overtime.ledger.edit_toil_balance' => ['en' => 'Edit TOIL Balance', 'th' => 'แก้ไขยอดคงเหลือ TOIL'],
    'overtime.ledger.employee_label' => ['en' => 'Employee:', 'th' => 'พนักงาน:'],
    'overtime.ledger.allowance'    => ['en' => 'Allowance', 'th' => 'สิทธิ์'],
    'overtime.ledger.days'         => ['en' => 'Days', 'th' => 'วัน'],
    'overtime.ledger.hours'        => ['en' => 'Hours', 'th' => 'ชั่วโมง'],
    'overtime.ledger.minutes'      => ['en' => 'Minutes', 'th' => 'นาที'],
    'overtime.ledger.minutes_lower' => ['en' => 'minutes', 'th' => 'นาที'],
    'overtime.ledger.total'        => ['en' => 'Total:', 'th' => 'รวม:'],
    'overtime.ledger.used'         => ['en' => 'Used', 'th' => 'ใช้ไป'],
    'overtime.ledger.remaining'    => ['en' => 'Remaining', 'th' => 'คงเหลือ'],
    'overtime.ledger.save_changes' => ['en' => 'Save Changes', 'th' => 'บันทึกการเปลี่ยนแปลง'],
    'overtime.ledger.zero_minutes' => ['en' => '0 minutes', 'th' => '0 นาที'],
    'overtime.ledger.unit_day'     => ['en' => 'day', 'th' => 'วัน'],
    'overtime.ledger.unit_day_plural' => ['en' => 'days', 'th' => 'วัน'],
    'overtime.ledger.unit_hour'    => ['en' => 'hour', 'th' => 'ชั่วโมง'],
    'overtime.ledger.unit_hour_plural' => ['en' => 'hours', 'th' => 'ชั่วโมง'],
    'overtime.ledger.unit_minute'  => ['en' => 'minute', 'th' => 'นาที'],
    'overtime.ledger.unit_minute_plural' => ['en' => 'minutes', 'th' => 'นาที'],

    // ---- flash messages: employee / milestone ----
    'flash.employee_not_found'     => ['en' => 'Employee not found.', 'th' => 'ไม่พบพนักงาน'],
    'flash.name_required'          => ['en' => 'Name is required.', 'th' => 'กรุณาระบุชื่อ'],
    'flash.milestone_event_type_created' => ['en' => 'Employee milestone event type created successfully.', 'th' => 'สร้างประเภทเหตุการณ์หลักชีวิตพนักงานเรียบร้อยแล้ว'],
    'flash.event_type_not_found'   => ['en' => 'Event type not found.', 'th' => 'ไม่พบประเภทเหตุการณ์'],

    // ---- flash messages: companies ----
    'flash.company_not_found'      => ['en' => 'Company not found.', 'th' => 'ไม่พบบริษัท'],
    'flash.company_name_required'  => ['en' => 'Company name is required.', 'th' => 'กรุณาระบุชื่อบริษัท'],
    'flash.company_slug_duplicate' => ['en' => 'A company with this slug already exists.', 'th' => 'บริษัทที่มี slug นี้มีอยู่แล้ว'],
    'flash.company_created'        => ['en' => 'Company created successfully.', 'th' => 'สร้างบริษัทเรียบร้อยแล้ว'],
    'flash.company_updated'        => ['en' => 'Company updated successfully.', 'th' => 'อัปเดตบริษัทเรียบร้อยแล้ว'],
    'flash.company_deleted'        => ['en' => 'Company deleted successfully.', 'th' => 'ลบบริษัทเรียบร้อยแล้ว'],

    // ---- flash messages: password (profile) ----
    'flash.password_current_incorrect' => ['en' => 'Current password is incorrect', 'th' => 'รหัสผ่านปัจจุบันไม่ถูกต้อง'],
    'flash.password_too_short'     => ['en' => 'New password must be at least 8 characters long', 'th' => 'รหัสผ่านใหม่ต้องมีความยาวอย่างน้อย 8 ตัวอักษร'],
    'flash.password_mismatch'      => ['en' => 'New password and confirmation do not match', 'th' => 'รหัสผ่านใหม่และการยืนยันไม่ตรงกัน'],
    'flash.password_updated'       => ['en' => 'Password updated successfully', 'th' => 'อัปเดตรหัสผ่านเรียบร้อยแล้ว'],
    'flash.password_update_failed' => ['en' => 'Failed to update password. Please try again.', 'th' => 'ไม่สามารถอัปเดตรหัสผ่านได้ กรุณาลองอีกครั้ง'],

    // ---- flash messages: tenant admin permissions ----
    'flash.tenant_admin_only_create' => ['en' => 'Only tenant admins can create companies.', 'th' => 'ผู้ดูแลเทนนันต์เท่านั้นที่สร้างบริษัทได้'],
    'flash.tenant_admin_only_edit' => ['en' => 'Only tenant admins can edit companies.', 'th' => 'ผู้ดูแลเทนนันต์เท่านั้นที่แก้ไขบริษัทได้'],
    'flash.tenant_admin_only_update' => ['en' => 'Only tenant admins can update companies.', 'th' => 'ผู้ดูแลเทนนันต์เท่านั้นที่อัปเดตบริษัทได้'],
    'flash.tenant_admin_only_delete' => ['en' => 'Only tenant admins can delete companies.', 'th' => 'ผู้ดูแลเทนนันต์เท่านั้นที่ลบบริษัทได้'],

    // ---- flash messages: tenant-specific company ----
    'flash.company_slug_reserved'  => ['en' => 'This slug is reserved and cannot be used. Please choose a different slug.', 'th' => 'slug นี้ถูกสงวนไว้และไม่สามารถใช้ได้ กรุณาเลือก slug อื่น'],
    'flash.company_slug_duplicate_tenant' => ['en' => 'A company with this slug already exists under this tenant.', 'th' => 'บริษัทที่มี slug นี้มีอยู่แล้วในเทนนันต์นี้'],
    'flash.company_create_failed'  => ['en' => 'Failed to create company: %message%', 'th' => 'ไม่สามารถสร้างบริษัทได้: %message%'],
    'flash.company_not_found_tenant' => ['en' => 'Company not found under this tenant.', 'th' => 'ไม่พบบริษัทในเทนนันต์นี้'],

    // ---- flash messages: overtime ----
    'flash.overtime_request_not_found' => ['en' => 'Overtime request not found.', 'th' => 'ไม่พบคำขอล่วงเวลา'],
    'flash.overtime_request_only_pending_approve' => ['en' => 'Only pending requests can be approved.', 'th' => 'อนุมัติได้เฉพาะคำขอที่รอดำเนินการเท่านั้น'],
    'flash.overtime_request_approved' => ['en' => 'Overtime request approved.', 'th' => 'อนุมัติคำขอล่วงเวลาเรียบร้อยแล้ว'],
    'flash.overtime_request_approve_failed' => ['en' => 'Failed to approve overtime request: %message%', 'th' => 'ไม่สามารถอนุมัติคำขอล่วงเวลาได้: %message%'],
    'flash.overtime_request_only_pending_reject' => ['en' => 'Only pending requests can be rejected.', 'th' => 'ปฏิเสธได้เฉพาะคำขอที่รอดำเนินการเท่านั้น'],
    'flash.overtime_request_rejected' => ['en' => 'Overtime request rejected.', 'th' => 'ปฏิเสธคำขอล่วงเวลาเรียบร้อยแล้ว'],
    'flash.toil_balance_not_found' => ['en' => 'TOIL leave balance not found.', 'th' => 'ไม่พบยอดคงเหลือการลา TOIL'],
    'flash.overtime_fields_required' => ['en' => 'Employee, policy, start time, and end time are required.', 'th' => 'กรุณาระบุพนักงาน นโยบาย เวลาเริ่มต้น และเวลาสิ้นสุด'],
    'flash.overtime_invalid_compensation' => ['en' => 'Invalid compensation type.', 'th' => 'ประเภทการชดเชยไม่ถูกต้อง'],
    'flash.overtime_policy_not_found_selected' => ['en' => 'Selected overtime policy not found.', 'th' => 'ไม่พบนโยบายล่วงเวลาที่เลือก'],
    'flash.overtime_request_submitted' => ['en' => 'Overtime request submitted successfully.', 'th' => 'ส่งคำขอล่วงเวลาเรียบร้อยแล้ว'],
    'flash.overtime_policy_name_required' => ['en' => 'Policy name is required.', 'th' => 'กรุณาระบุชื่อนโยบาย'],
    'flash.overtime_multiplier_required' => ['en' => 'Multiplier must be greater than zero.', 'th' => 'ตัวคูณต้องมากกว่าศูนย์'],
    'flash.overtime_policy_created' => ['en' => 'Overtime policy created successfully.', 'th' => 'สร้างนโยบายล่วงเวลาเรียบร้อยแล้ว'],
    'flash.overtime_policy_not_found' => ['en' => 'Overtime policy not found.', 'th' => 'ไม่พบนโยบายล่วงเวลา'],
    'flash.overtime_policy_updated' => ['en' => 'Overtime policy updated successfully.', 'th' => 'อัปเดตนโยบายล่วงเวลาเรียบร้อยแล้ว'],
    'flash.overtime_policy_deactivated' => ['en' => 'Overtime policy deactivated. Historical records are preserved.', 'th' => 'ปิดใช้งานนโยบายล่วงเวลาแล้ว ข้อมูลประวัติยังคงถูกเก็บรักษาไว้'],

    // ---- view titles: company ----
    'company.list.title'           => ['en' => 'Companies', 'th' => 'บริษัท'],
    'company.new.title'            => ['en' => 'New Company', 'th' => 'เพิ่มบริษัท'],
    'company.edit.title'           => ['en' => 'Edit Company', 'th' => 'แก้ไขบริษัท'],

    // ---- view titles: profile ----
    'profile.title'                => ['en' => 'Profile', 'th' => 'โปรไฟล์'],

    // ---- view titles: tenant ----
    'tenant.dashboard.title'       => ['en' => 'Tenant Dashboard', 'th' => 'แดชบอร์ดเทนนันต์'],
    'tenant.select_company.title'  => ['en' => 'Select Company', 'th' => 'เลือกบริษัท'],

    // ---- view titles: overtime ----
    'overtime.approvals.title'     => ['en' => 'Overtime Approvals', 'th' => 'การอนุมัติล่วงเวลา'],
    'overtime.ledger.title'        => ['en' => 'Overtime Ledger', 'th' => 'บัญชีล่วงเวลา'],
    'overtime.request.title'       => ['en' => 'Request Overtime', 'th' => 'ขอล่วงเวลา'],
];
