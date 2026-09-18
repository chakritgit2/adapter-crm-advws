<?php

// NEW translation keys introduced by the auth/error/index i18n pass.
// Existing keys reused from app/lang/en.php (NOT duplicated here):
//   auth.sign_in, auth.email, auth.password, auth.set_password,
//   auth.password_mismatch, header.login, header.change_password,
//   layout.profile, footer.rights_reserved
//
// Thai is the default UI language; English is the fallback.

return [
    // ---- flash messages ----
    'flash.fix_errors' => ['en' => 'Please fix the following errors:', 'th' => 'กรุณาแก้ไขข้อผิดพลาดต่อไปนี้:'],

    // ---- index / landing footer & nav ----
    'index.footer.contact_hours'  => ['en' => 'Contact inquiries 09:00 - 18:00', 'th' => 'ติดต่อสอบถาม 09:00 - 18:00'],
    'index.footer.download_app'    => ['en' => 'Download App', 'th' => 'ดาวน์โหลด APP'],
    'index.footer.download_hint'   => ['en' => 'Click your OS logo to download', 'th' => 'คลิกที่โลโก้ระบบปฏิบัติการของคุณเพื่อดาวน์โหลด'],
    'index.download_on'            => ['en' => 'Download on', 'th' => 'ดาวน์โหลดจาก'],
    'index.failed_to_add_to_cart'  => ['en' => 'Failed to add to cart', 'th' => 'เพิ่มลงตะกร้าไม่สำเร็จ'],
    'index.nav.find_teacher'       => ['en' => 'Find a Music Teacher', 'th' => 'หาครูสอนดนตรี'],
    'index.nav.blog'               => ['en' => 'Blog', 'th' => 'บล็อก'],
    'index.nav.about_us'           => ['en' => 'About Us', 'th' => 'เกี่ยวกับเรา'],
    'index.nav.contact_us'         => ['en' => 'Contact Us', 'th' => 'ติดต่อเรา'],
    'index.nav.checkout'           => ['en' => 'Checkout', 'th' => 'ชำระเงิน'],
    'index.nav.checkout_hint'      => ['en' => 'You will review your items on the next page', 'th' => 'คุณจะได้ตรวจสอบรายการในหน้าถัดไป'],
    'index.nav.open_menu'          => ['en' => 'Open main menu', 'th' => 'เปิดเมนูหลัก'],

    // ---- profile menu ----
    'profile.menu_title'      => ['en' => 'Menu', 'th' => 'เมนู'],
    'profile.getting_started' => ['en' => 'Getting Started', 'th' => 'เริ่มต้นที่นี่'],
    'profile.personal_info'   => ['en' => 'Personal Information', 'th' => 'ข้อมูลผู้สอน'],
    'profile.education'       => ['en' => 'My Education', 'th' => 'การศึกษา'],
    'profile.receiving_bank'  => ['en' => 'Receiving Bank Account', 'th' => 'บัญชีรับเงิน'],
    'profile.opening_hour'    => ['en' => "Teacher's Opening Hour", 'th' => 'เวลาเปิดสอน'],
    'profile.in_review'       => ['en' => 'In Review', 'th' => 'อยู่ระหว่างตรวจสอบ'],

    // ---- auth / login ----
    'auth.forgot_password'             => ['en' => 'Forgot password?', 'th' => 'ลืมรหัสผ่าน?'],
    'auth.set_password_subtitle'       => ['en' => 'Create a secure password for your account.', 'th' => 'สร้างรหัสผ่านที่ปลอดภัยสำหรับบัญชีของคุณ'],
    'auth.enter_password'              => ['en' => 'Enter password', 'th' => 'ใส่รหัสผ่าน'],
    'auth.confirm_password'            => ['en' => 'Confirm Password', 'th' => 'ยืนยันรหัสผ่าน'],
    'auth.confirm_password_placeholder'=> ['en' => 'Confirm password', 'th' => 'ยืนยันรหัสผ่าน'],
    'auth.show_password'               => ['en' => 'Show password', 'th' => 'แสดงรหัสผ่าน'],
    'auth.strength'                    => ['en' => 'Strength', 'th' => 'ความปลอดภัย'],
    'auth.rule_length'                 => ['en' => 'At least 8 characters', 'th' => 'อย่างน้อย 8 ตัวอักษร'],
    'auth.rule_upper'                  => ['en' => 'Uppercase letter', 'th' => 'ตัวพิมพ์ใหญ่'],
    'auth.rule_lower'                  => ['en' => 'Lowercase letter', 'th' => 'ตัวพิมพ์เล็ก'],
    'auth.rule_number'                 => ['en' => 'Number', 'th' => 'ตัวเลข'],
    'auth.rule_special'                => ['en' => 'Special character', 'th' => 'อักขระพิเศษ'],
    'auth.strength_weak'               => ['en' => 'Weak', 'th' => 'อ่อน'],
    'auth.strength_fair'               => ['en' => 'Fair', 'th' => 'ปานกลาง'],
    'auth.strength_good'               => ['en' => 'Good', 'th' => 'ดี'],
    'auth.strength_strong'             => ['en' => 'Strong', 'th' => 'แข็งแกร่ง'],
    'auth.strength_very_strong'        => ['en' => 'Very Strong', 'th' => 'แข็งแกร่งมาก'],
    'auth.change_password'             => ['en' => 'Change Password', 'th' => 'เปลี่ยนรหัสผ่าน'],
    'auth.change_password_subtitle'    => ['en' => 'Choose a new password for your account.', 'th' => 'ตั้งรหัสผ่านใหม่สำหรับบัญชีของคุณ'],
    'auth.must_change_password_notice' => ['en' => 'You must change your password before you can continue.', 'th' => 'คุณต้องเปลี่ยนรหัสผ่านก่อนจึงจะสามารถใช้งานต่อได้'],

    // ---- error pages ----
    'error.404_message'  => ['en' => 'This page does not exist or may have been moved.', 'th' => 'หน้านี้ไม่มีอยู่ หรืออาจะถูกย้ายไปแล้ว'],
    'error.back_home'    => ['en' => 'Back to SAMT Music', 'th' => 'กลับสู่ SAMT Music'],

    // ---- index legal pages (UI labels only) ----
    'index.privacy_policy_students' => ['en' => '(Privacy Policy for Students)', 'th' => '(นโยบายความเป็นส่วนตัวสำหรับนักเรียน)'],
    'index.contact'                 => ['en' => 'Contact', 'th' => 'ติดต่อ'],
    'index.website'                 => ['en' => 'Website', 'th' => 'เว็บไซต์'],
];
