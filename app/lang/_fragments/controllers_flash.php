<?php

// New translation keys introduced by controllers_flash i18n conversion.
// Covers DashboardController, IndexController, TenantController, OvertimeController.
// Keys with placeholders use %name% syntax (NativeArray adapter).

return [
    // ---- flash messages: employee / milestone ----
    'flash.employee_not_found'           => ['en' => 'Employee not found.', 'th' => 'ไม่พบพนักงาน'],
    'flash.name_required'                => ['en' => 'Name is required.', 'th' => 'กรุณาระบุชื่อ'],
    'flash.milestone_event_type_created' => ['en' => 'Employee milestone event type created successfully.', 'th' => 'สร้างประเภทเหตุการณ์หลักชีวิตพนักงานเรียบร้อยแล้ว'],
    'flash.event_type_not_found'         => ['en' => 'Event type not found.', 'th' => 'ไม่พบประเภทเหตุการณ์'],
    'flash.event_type_system_default'    => ['en' => 'System default event types cannot be modified.', 'th' => 'ไม่สามารถแก้ไขประเภทเหตุการณ์เริ่มต้นของระบบได้'],
    'flash.milestone_event_type_updated' => ['en' => 'Employee milestone event type updated successfully.', 'th' => 'อัปเดตประเภทเหตุการณ์หลักชีวิตพนักงานเรียบร้อยแล้ว'],
    'flash.milestone_event_type_deleted' => ['en' => 'Employee milestone event type deleted successfully.', 'th' => 'ลบประเภทเหตุการณ์หลักชีวิตพนักงานเรียบร้อยแล้ว'],

    // ---- flash messages: job levels ----
    'flash.job_level_required'           => ['en' => 'Code, Category, and Name are required.', 'th' => 'กรุณาระบุรหัส หมวดหมู่ และชื่อ'],
    'flash.job_level_duplicate'          => ['en' => 'A job level with code "%code%" already exists for this organization.', 'th' => 'ระดับตำแหน่งที่มีรหัส "%code%" มีอยู่แล้วในองค์กรนี้'],
    'flash.job_level_created'            => ['en' => 'Job level created successfully.', 'th' => 'สร้างระดับตำแหน่งเรียบร้อยแล้ว'],
    'flash.job_level_not_found'          => ['en' => 'Job level not found.', 'th' => 'ไม่พบระดับตำแหน่ง'],
    'flash.job_level_system_default'     => ['en' => 'System default job levels cannot be modified.', 'th' => 'ไม่สามารถแก้ไขระดับตำแหน่งเริ่มต้นของระบบได้'],
    'flash.job_level_updated'            => ['en' => 'Job level updated successfully.', 'th' => 'อัปเดตระดับตำแหน่งเรียบร้อยแล้ว'],
    'flash.job_level_deleted'            => ['en' => 'Job level deleted successfully.', 'th' => 'ลบระดับตำแหน่งเรียบร้อยแล้ว'],

    // ---- flash messages: custom attributes ----
    'flash.attribute_required'           => ['en' => 'Name and Applies To are required.', 'th' => 'กรุณาระบุชื่อและประเภทเอนทิตี้'],
    'flash.attribute_created'            => ['en' => 'Custom attribute created successfully.', 'th' => 'สร้างแอตทริบิวต์เรียบร้อยแล้ว'],
    'flash.attribute_not_found'          => ['en' => 'Attribute not found.', 'th' => 'ไม่พบแอตทริบิวต์'],
    'flash.attribute_updated'            => ['en' => 'Custom attribute updated successfully.', 'th' => 'อัปเดตแอตทริบิวต์เรียบร้อยแล้ว'],
    'flash.attribute_deleted'            => ['en' => 'Custom attribute deleted successfully.', 'th' => 'ลบแอตทริบิวต์เรียบร้อยแล้ว'],

    // ---- flash messages: super admin permissions (companies) ----
    'flash.super_admin_only_create'      => ['en' => 'Only Super Admins can create companies.', 'th' => 'ผู้ดูแลระบบสูงสุดเท่านั้นที่สร้างบริษัทได้'],
    'flash.super_admin_only_edit'        => ['en' => 'Only Super Admins can edit companies.', 'th' => 'ผู้ดูแลระบบสูงสุดเท่านั้นที่แก้ไขบริษัทได้'],
    'flash.super_admin_only_update'      => ['en' => 'Only Super Admins can update companies.', 'th' => 'ผู้ดูแลระบบสูงสุดเท่านั้นที่อัปเดตบริษัทได้'],
    'flash.super_admin_only_delete'      => ['en' => 'Only Super Admins can delete companies.', 'th' => 'ผู้ดูแลระบบสูงสุดเท่านั้นที่ลบบริษัทได้'],

    // ---- flash messages: super admin permissions (admin users) ----
    'flash.super_admin_only_create_admin'  => ['en' => 'Only Super Admins can create admin users.', 'th' => 'ผู้ดูแลระบบสูงสุดเท่านั้นที่สร้างผู้ใช้ผู้ดูแลได้'],
    'flash.super_admin_only_edit_admin'    => ['en' => 'Only Super Admins can edit admin users.', 'th' => 'ผู้ดูแลระบบสูงสุดเท่านั้นที่แก้ไขผู้ใช้ผู้ดูแลได้'],
    'flash.super_admin_only_update_admin'  => ['en' => 'Only Super Admins can update admin users.', 'th' => 'ผู้ดูแลระบบสูงสุดเท่านั้นที่อัปเดตผู้ใช้ผู้ดูแลได้'],
    'flash.super_admin_only_delete_admin'  => ['en' => 'Only Super Admins can delete admin users.', 'th' => 'ผู้ดูแลระบบสูงสุดเท่านั้นที่ลบผู้ใช้ผู้ดูแลได้'],

    // ---- flash messages: admin users ----
    'flash.admin_user_required'              => ['en' => 'Name, email, and role are required.', 'th' => 'กรุณาระบุชื่อ อีเมล และบทบาท'],
    'flash.admin_user_invalid_role'          => ['en' => 'Invalid role selected.', 'th' => 'บทบาทที่เลือกไม่ถูกต้อง'],
    'flash.admin_user_duplicate'             => ['en' => 'An admin user with this email already exists.', 'th' => 'ผู้ใช้ผู้ดูแลที่มีอีเมลนี้มีอยู่แล้ว'],
    'flash.admin_user_email_failed'          => ['en' => 'Admin user created, but the invitation email could not be queued.', 'th' => 'สร้างผู้ใช้ผู้ดูแลแล้ว แต่ไม่สามารถส่งอีเมลคำเชิญได้'],
    'flash.admin_user_created'               => ['en' => 'Admin user created successfully and invitation email queued.', 'th' => 'สร้างผู้ใช้ผู้ดูแลเรียบร้อยแล้วและส่งอีเมลคำเชิญเข้าคิวแล้ว'],
    'flash.admin_user_email_template_missing'=> ['en' => 'Admin user created, but the invitation email template was not found.', 'th' => 'สร้างผู้ใช้ผู้ดูแลแล้ว แต่ไม่พบเทมเพลตอีเมลคำเชิญ'],
    'flash.admin_user_not_found'             => ['en' => 'Admin user not found.', 'th' => 'ไม่พบผู้ใช้ผู้ดูแล'],
    'flash.admin_user_updated'               => ['en' => 'Admin user updated successfully.', 'th' => 'อัปเดตผู้ใช้ผู้ดูแลเรียบร้อยแล้ว'],
    'flash.admin_user_self_delete'           => ['en' => 'You cannot delete your own account.', 'th' => 'คุณไม่สามารถลบบัญชีของตัวเองได้'],
    'flash.admin_user_deleted'               => ['en' => 'Admin user deleted successfully.', 'th' => 'ลบผู้ใช้ผู้ดูแลเรียบร้อยแล้ว'],

    // ---- flash messages: leave policies ----
    'flash.leave_policy_name_required'  => ['en' => 'Policy name is required.', 'th' => 'กรุณาระบุชื่อนโยบาย'],
    'flash.leave_policy_created'        => ['en' => 'Leave policy created successfully.', 'th' => 'สร้างนโยบายการลาเรียบร้อยแล้ว'],
    'flash.leave_policy_not_found'      => ['en' => 'Leave policy not found.', 'th' => 'ไม่พบนโยบายการลา'],
    'flash.leave_policy_updated'        => ['en' => 'Leave policy updated successfully.', 'th' => 'อัปเดตนโยบายการลาเรียบร้อยแล้ว'],
    'flash.leave_policy_deleted'        => ['en' => 'Leave policy deleted successfully.', 'th' => 'ลบนโยบายการลาเรียบร้อยแล้ว'],

    // ---- flash messages: allowance rules ----
    'flash.allowance_rule_created'      => ['en' => 'Allowance rule created successfully.', 'th' => 'สร้างกฎสิทธิ์การลาเรียบร้อยแล้ว'],
    'flash.allowance_rule_not_found'    => ['en' => 'Allowance rule not found.', 'th' => 'ไม่พบกฎสิทธิ์การลา'],
    'flash.allowance_rule_updated'      => ['en' => 'Allowance rule updated successfully.', 'th' => 'อัปเดตกฎสิทธิ์การลาเรียบร้อยแล้ว'],
    'flash.allowance_rule_deleted'      => ['en' => 'Allowance rule deleted successfully.', 'th' => 'ลบกฎสิทธิ์การลาเรียบร้อยแล้ว'],
    'flash.balances_recalculated'       => ['en' => 'Balances recalculated: %created% created, %updated% updated. (Employees with existing usage were not changed.)', 'th' => 'คำนวณยอดคงเหลือใหม่: สร้าง %created% รายการ อัปเดต %updated% รายการ (พนักงานที่มีการใช้งานแล้วจะไม่ถูกเปลี่ยนแปลง)'],

    // ---- flash messages: leave balances & requests ----
    'flash.leave_balance_updated'             => ['en' => 'Leave balance updated successfully.', 'th' => 'อัปเดตยอดคงเหลือการลาเรียบร้อยแล้ว'],
    'flash.employee_and_leave_type_required'  => ['en' => 'Employee and leave type are required.', 'th' => 'กรุณาระบุพนักงานและประเภทการลา'],
    'flash.employee_not_found_selected'       => ['en' => 'Selected employee not found.', 'th' => 'ไม่พบพนักงานที่เลือก'],
    'flash.leave_type_not_found_selected'     => ['en' => 'Selected leave type not found.', 'th' => 'ไม่พบประเภทการลาที่เลือก'],
    'flash.dates_required'                    => ['en' => 'Start date and end date are required.', 'th' => 'กรุณาระบุวันที่เริ่มต้นและวันที่สิ้นสุด'],
    'flash.datetime_required'                 => ['en' => 'Start date/time and end date/time are required.', 'th' => 'กรุณาระบุวันที่/เวลาเริ่มต้นและวันที่/เวลาสิ้นสุด'],
    'flash.invalid_date_range'                => ['en' => 'Invalid date range.', 'th' => 'ช่วงวันที่ไม่ถูกต้อง'],
    'flash.invalid_date_time_range'           => ['en' => 'Invalid date/time range.', 'th' => 'ช่วงวันที่/เวลาไม่ถูกต้อง'],
    'flash.leave_request_submitted'           => ['en' => 'Leave request submitted successfully.', 'th' => 'ส่งคำขอลาเรียบร้อยแล้ว'],
    'flash.leave_request_not_found'           => ['en' => 'Leave request not found.', 'th' => 'ไม่พบคำขอลา'],
    'flash.leave_request_only_pending_approve'=> ['en' => 'Only pending requests can be approved.', 'th' => 'อนุมัติได้เฉพาะคำขอที่รอดำเนินการเท่านั้น'],
    'flash.leave_request_approved'            => ['en' => 'Leave request approved.', 'th' => 'อนุมัติคำขอลาเรียบร้อยแล้ว'],
    'flash.leave_request_approve_failed'      => ['en' => 'Failed to approve leave request: %message%', 'th' => 'ไม่สามารถอนุมัติคำขอลาได้: %message%'],
    'flash.leave_request_only_pending_reject' => ['en' => 'Only pending requests can be rejected.', 'th' => 'ปฏิเสธได้เฉพาะคำขอที่รอดำเนินการเท่านั้น'],
    'flash.leave_request_rejected'            => ['en' => 'Leave request rejected.', 'th' => 'ปฏิเสธคำขอลาเรียบร้อยแล้ว'],
    'flash.leave_request_no_chargeable_days'  => ['en' => 'The selected date range contains no chargeable working days (all days are weekends or Company Holidays).', 'th' => 'ช่วงวันที่ที่เลือกไม่มีวันทำการที่นับเป็นวันลา (ทุกวันเป็นวันหยุดสุดสัปดาห์หรือวันหยุดบริษัท)'],

    // ---- flash messages: companies (IndexController) ----
    'flash.company_not_found'            => ['en' => 'Company not found.', 'th' => 'ไม่พบบริษัท'],
    'flash.company_name_required'        => ['en' => 'Company name is required.', 'th' => 'กรุณาระบุชื่อบริษัท'],
    'flash.company_slug_duplicate'       => ['en' => 'A company with this slug already exists.', 'th' => 'บริษัทที่มี slug นี้มีอยู่แล้ว'],
    'flash.company_created'              => ['en' => 'Company created successfully.', 'th' => 'สร้างบริษัทเรียบร้อยแล้ว'],
    'flash.company_updated'              => ['en' => 'Company updated successfully.', 'th' => 'อัปเดตบริษัทเรียบร้อยแล้ว'],
    'flash.company_deleted'              => ['en' => 'Company deleted successfully.', 'th' => 'ลบบริษัทเรียบร้อยแล้ว'],

    // ---- flash messages: password (IndexController) ----
    'flash.password_current_incorrect'   => ['en' => 'Current password is incorrect', 'th' => 'รหัสผ่านปัจจุบันไม่ถูกต้อง'],
    'flash.password_too_short'           => ['en' => 'New password must be at least 8 characters long', 'th' => 'รหัสผ่านใหม่ต้องมีความยาวอย่างน้อย 8 ตัวอักษร'],
    'flash.password_mismatch'            => ['en' => 'New password and confirmation do not match', 'th' => 'รหัสผ่านใหม่และการยืนยันไม่ตรงกัน'],
    'flash.password_updated'             => ['en' => 'Password updated successfully', 'th' => 'อัปเดตรหัสผ่านเรียบร้อยแล้ว'],
    'flash.password_update_failed'       => ['en' => 'Failed to update password. Please try again.', 'th' => 'ไม่สามารถอัปเดตรหัสผ่านได้ กรุณาลองอีกครั้ง'],
    'flash.admin_user_created_default_password' => ['en' => 'Admin user created. The default password is "password" — the user will be asked to change it on first login.', 'th' => 'สร้างผู้ใช้ผู้ดูแลเรียบร้อยแล้ว รหัสผ่านเริ่มต้นคือ "password" — ผู้ใช้จะถูกขอให้เปลี่ยนรหัสผ่านเมื่อเข้าสู่ระบบครั้งแรก'],

    // ---- flash messages: tenant admin permissions (TenantController) ----
    'flash.tenant_admin_only_create'     => ['en' => 'Only tenant admins can create companies.', 'th' => 'ผู้ดูแลเทนนันต์เท่านั้นที่สร้างบริษัทได้'],
    'flash.tenant_admin_only_edit'       => ['en' => 'Only tenant admins can edit companies.', 'th' => 'ผู้ดูแลเทนนันต์เท่านั้นที่แก้ไขบริษัทได้'],
    'flash.tenant_admin_only_update'     => ['en' => 'Only tenant admins can update companies.', 'th' => 'ผู้ดูแลเทนนันต์เท่านั้นที่อัปเดตบริษัทได้'],
    'flash.tenant_admin_only_delete'     => ['en' => 'Only tenant admins can delete companies.', 'th' => 'ผู้ดูแลเทนนันต์เท่านั้นที่ลบบริษัทได้'],

    // ---- flash messages: tenant-specific company (TenantController) ----
    'flash.company_slug_reserved'          => ['en' => 'This slug is reserved and cannot be used. Please choose a different slug.', 'th' => 'slug นี้ถูกสงวนไว้และไม่สามารถใช้ได้ กรุณาเลือก slug อื่น'],
    'flash.company_slug_duplicate_tenant'  => ['en' => 'A company with this slug already exists under this tenant.', 'th' => 'บริษัทที่มี slug นี้มีอยู่แล้วในเทนนันต์นี้'],
    'flash.company_create_failed'          => ['en' => 'Failed to create company: %message%', 'th' => 'ไม่สามารถสร้างบริษัทได้: %message%'],
    'flash.company_not_found_tenant'       => ['en' => 'Company not found under this tenant.', 'th' => 'ไม่พบบริษัทในเทนนันต์นี้'],

    // ---- flash messages: overtime (OvertimeController) ----
    'flash.overtime_request_not_found'           => ['en' => 'Overtime request not found.', 'th' => 'ไม่พบคำขอล่วงเวลา'],
    'flash.overtime_request_only_pending_approve'=> ['en' => 'Only pending requests can be approved.', 'th' => 'อนุมัติได้เฉพาะคำขอที่รอดำเนินการเท่านั้น'],
    'flash.overtime_request_approved'            => ['en' => 'Overtime request approved.', 'th' => 'อนุมัติคำขอล่วงเวลาเรียบร้อยแล้ว'],
    'flash.overtime_request_approve_failed'      => ['en' => 'Failed to approve overtime request: %message%', 'th' => 'ไม่สามารถอนุมัติคำขอล่วงเวลาได้: %message%'],
    'flash.overtime_request_only_pending_reject' => ['en' => 'Only pending requests can be rejected.', 'th' => 'ปฏิเสธได้เฉพาะคำขอที่รอดำเนินการเท่านั้น'],
    'flash.overtime_request_rejected'            => ['en' => 'Overtime request rejected.', 'th' => 'ปฏิเสธคำขอล่วงเวลาเรียบร้อยแล้ว'],
    'flash.toil_balance_not_found'               => ['en' => 'TOIL leave balance not found.', 'th' => 'ไม่พบยอดคงเหลือการลา TOIL'],
    'flash.overtime_fields_required'             => ['en' => 'Employee, policy, start time, and end time are required.', 'th' => 'กรุณาระบุพนักงาน นโยบาย เวลาเริ่มต้น และเวลาสิ้นสุด'],
    'flash.overtime_invalid_compensation'        => ['en' => 'Invalid compensation type.', 'th' => 'ประเภทการชดเชยไม่ถูกต้อง'],
    'flash.overtime_policy_not_found_selected'   => ['en' => 'Selected overtime policy not found.', 'th' => 'ไม่พบนโยบายล่วงเวลาที่เลือก'],
    'flash.overtime_request_submitted'           => ['en' => 'Overtime request submitted successfully.', 'th' => 'ส่งคำขอล่วงเวลาเรียบร้อยแล้ว'],
    'flash.overtime_policy_name_required'        => ['en' => 'Policy name is required.', 'th' => 'กรุณาระบุชื่อนโยบาย'],
    'flash.overtime_multiplier_required'         => ['en' => 'Multiplier must be greater than zero.', 'th' => 'ตัวคูณต้องมากกว่าศูนย์'],
    'flash.overtime_policy_created'              => ['en' => 'Overtime policy created successfully.', 'th' => 'สร้างนโยบายล่วงเวลาเรียบร้อยแล้ว'],
    'flash.overtime_policy_not_found'            => ['en' => 'Overtime policy not found.', 'th' => 'ไม่พบนโยบายล่วงเวลา'],
    'flash.overtime_policy_updated'              => ['en' => 'Overtime policy updated successfully.', 'th' => 'อัปเดตนโยบายล่วงเวลาเรียบร้อยแล้ว'],
    'flash.overtime_policy_deactivated'          => ['en' => 'Overtime policy deactivated. Historical records are preserved.', 'th' => 'ปิดใช้งานนโยบายล่วงเวลาแล้ว ข้อมูลประวัติยังคงถูกเก็บรักษาไว้'],

    // ---- view titles: dashboard ----
    'dashboard.company_dashboard.title'  => ['en' => 'Company Dashboard', 'th' => 'แดชบอร์ดบริษัท'],
    'dashboard.org_hierarchy.title'      => ['en' => 'Organization Hierarchy', 'th' => 'ลำดับชั้นองค์กร'],
    'dashboard.emp_hierarchy.title'      => ['en' => 'Employee Hierarchy', 'th' => 'ผังองค์กร'],
    'dashboard.positions.title'          => ['en' => 'Positions', 'th' => 'ตำแหน่ง'],
    'dashboard.employees.title'          => ['en' => 'Employees', 'th' => 'พนักงาน'],

    // ---- view titles: employee ----
    'employee.edit.title'                => ['en' => 'Edit Employee', 'th' => 'แก้ไขพนักงาน'],
    'employee.new.title'                 => ['en' => 'New Hire', 'th' => 'พนักงานใหม่'],
    'employee.milestones.title'          => ['en' => 'Employee Milestones', 'th' => 'หลักชีวิตพนักงาน'],
    'employee.milestone.add.title'       => ['en' => 'Add Milestone', 'th' => 'เพิ่มหลักชีวิต'],
    'employee.status_change.title'       => ['en' => 'Employee Status Change', 'th' => 'เปลี่ยนสถานะพนักงาน'],

    // ---- view titles: settings ----
    'settings.system.title'                            => ['en' => 'System Settings', 'th' => 'ตั้งค่าระบบ'],
    'settings.milestone_event_types.title'             => ['en' => 'Employee Milestone Event Types', 'th' => 'ประเภทเหตุการณ์หลักชีวิตพนักงาน'],
    'settings.milestone_event_types.new.title'         => ['en' => 'New Employee Milestone Event Type', 'th' => 'เพิ่มประเภทเหตุการณ์หลักชีวิตพนักงาน'],
    'settings.milestone_event_types.view.title'        => ['en' => 'Employee Milestone Event Type', 'th' => 'ประเภทเหตุการณ์หลักชีวิตพนักงาน'],
    'settings.milestone_event_types.edit.title'        => ['en' => 'Edit Employee Milestone Event Type', 'th' => 'แก้ไขประเภทเหตุการณ์หลักชีวิตพนักงาน'],
    'settings.job_levels.title'                        => ['en' => 'Job Levels', 'th' => 'ระดับตำแหน่ง'],
    'settings.job_levels.new.title'                    => ['en' => 'New Job Level', 'th' => 'เพิ่มระดับตำแหน่ง'],
    'settings.job_levels.view.title'                   => ['en' => 'Job Level', 'th' => 'ระดับตำแหน่ง'],
    'settings.job_levels.edit.title'                   => ['en' => 'Edit Job Level', 'th' => 'แก้ไขระดับตำแหน่ง'],
    'settings.custom_attributes.title'                 => ['en' => 'Custom Attributes', 'th' => 'แอตทริบิวต์เพิ่มเติม'],
    'settings.custom_attributes.new.title'             => ['en' => 'New Custom Attribute', 'th' => 'เพิ่มแอตทริบิวต์'],
    'settings.custom_attributes.edit.title'            => ['en' => 'Edit Custom Attribute', 'th' => 'แก้ไขแอตทริบิวต์'],
    'settings.admin_users.title'                       => ['en' => 'Admin Users', 'th' => 'ผู้ใช้ผู้ดูแล'],
    'settings.admin_users.new.title'                   => ['en' => 'New Admin User', 'th' => 'เพิ่มผู้ใช้ผู้ดูแล'],
    'settings.admin_users.edit.title'                  => ['en' => 'Edit Admin User', 'th' => 'แก้ไขผู้ใช้ผู้ดูแล'],
    'settings.leave_policies.title'                    => ['en' => 'Leave Policies', 'th' => 'นโยบายการลา'],
    'settings.leave_policies.new.title'                => ['en' => 'New Leave Policy', 'th' => 'เพิ่มนโยบายการลา'],
    'settings.leave_policies.edit.title'               => ['en' => 'Edit Leave Policy', 'th' => 'แก้ไขนโยบายการลา'],
    'settings.overtime_policies.title'                 => ['en' => 'Overtime Policies', 'th' => 'นโยบายล่วงเวลา'],
    'settings.overtime_policies.new.title'             => ['en' => 'New Overtime Policy', 'th' => 'เพิ่มนโยบายล่วงเวลา'],
    'settings.overtime_policies.edit.title'            => ['en' => 'Edit Overtime Policy', 'th' => 'แก้ไขนโยบายล่วงเวลา'],

    // ---- view titles: leave ----
    'leave.balances.title'              => ['en' => 'Leave Balances', 'th' => 'ยอดคงเหลือการลา'],
    'leave.requests.title'              => ['en' => 'Leave Requests', 'th' => 'คำขอลา'],
    'leave.requests.new.title'          => ['en' => 'New Leave Request', 'th' => 'สร้างคำขอลา'],
    'leave.balances.import.title'       => ['en' => 'Import Leave Balances', 'th' => 'นำเข้ายอดคงเหลือการลา'],

    // ---- view titles: company ----
    'company.list.title'                => ['en' => 'Companies', 'th' => 'บริษัท'],
    'company.new.title'                 => ['en' => 'New Company', 'th' => 'เพิ่มบริษัท'],
    'company.edit.title'                => ['en' => 'Edit Company', 'th' => 'แก้ไขบริษัท'],

    // ---- view titles: profile ----
    'profile.title'                     => ['en' => 'Profile', 'th' => 'โปรไฟล์'],

    // ---- view titles: tenant ----
    'tenant.dashboard.title'            => ['en' => 'Tenant Dashboard', 'th' => 'แดชบอร์ดเทนนันต์'],
    'tenant.billing.title'              => ['en' => 'Billing & Plan', 'th' => 'การเรียกเก็บเงินและแผน'],
    'tenant.select_company.title'       => ['en' => 'Select Company', 'th' => 'เลือกบริษัท'],

    // ---- view titles: overtime ----
    'overtime.approvals.title'          => ['en' => 'Overtime Approvals', 'th' => 'การอนุมัติล่วงเวลา'],
    'overtime.ledger.title'             => ['en' => 'Overtime Ledger', 'th' => 'บัญชีล่วงเวลา'],
    'overtime.request.title'            => ['en' => 'Request Overtime', 'th' => 'ขอล่วงเวลา'],
];
