<div style="text-align:center;text-align:-webkit-center;background-color: white">
    <div style="width:800px;max-width: 100%;text-align: left;">
        <div style="text-align: center;background:#ffffff">
            <img src="https://<?= $_SERVER['SERVER_NAME'] ?>/img/main/SAMT-Master-logo-medium.png"
                style="width:100%;height:auto;padding:2em 3em;max-width: 456px;" />
        </div>
        <div style="padding: 2em; border: 1px solid #cecece;">
            <div style="padding: 1em;">
                <div>
                    สวัสดีคะ คุณ {firstname} {lastname},
                    <div style="margin-top: 4em;text-align: left">
                        <h3>เราได้รับข้อมูลการสมัครเบื้องต้นของคุณแล้ว</h3>
                        <h4>มาเริ่มรับงานกันคะ! เพียงแค่คุณ</h4>
                        <div style="margin-top:2em">&bull; สร้างโปรไฟล์</div>
                        <div>&bull; อัพโหลดเอกสารประกอบการสมัคร</div>
                        <div>&bull; แจ้งช่องทางการรับเงิน</div>
                        <div style="margin:1em 0 3em 0;"></div>
                        <div style="text-align:center">
                            <!--[if mso]>
                            <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="https://<?= $_SERVER['SERVER_NAME'] ?>/signup-email-confirmation?email={signup->email}&ccode={signup->confirmation-code}&type={signup->owner_type}" style="height:40px;v-text-anchor:middle;width:200px;" arcsize="50%" strokecolor="#15ca1b" fillcolor="#57c564">
                            <w:anchorlock/>
                            <center style="color:#ffffff;font-family:sans-serif;font-size:13px;font-weight:bold;">เริ่มต้นกันเลย!</center>
                            </v:roundrect>
                        <![endif]-->
                            <a href="https://<?= $_SERVER['SERVER_NAME'] ?>/signup-email-confirmation?email={signup->email}&ccode={signup->confirmation-code}&type={signup->owner_type}"
                                style="background-color:#ec0607;border:1px solid #ec0607;border-radius:5px;color:#ffffff;display:inline-block;font-family:sans-serif;font-size:1.1rem;font-weight:bold;line-height:40px;text-align:center;text-decoration:none;width:200px;-webkit-text-size-adjust:none;mso-hide:all;">
                                เริ่มต้นกันเลย!
                            </a>
                        </div>
                    </div>
                </div>
                <hr style="margin-top:4em;border:thin solid #f0f0f0;border-bottom: none;" />
                <small>
                    <u>หมายเหตุ:</u>
                    <ol>
                        <li>หากท่านไม่ได้ทำการขอลงทะเบียนสอนกับ <?= $_SERVER['SERVER_NAME'] ?> ท่านสามารถปิด หรือ
                            ลบอีเมลนี้ได้ทันทีคะ</li>
                        <li>ลิ้งค์ยืนยันการลงทะเบียนสอนมี อายุการใช้งาน <b>30 วัน</b> หากไม่มีการกระทำใดๆ
                            ภายในระยะเวลาดังกล่าวท่านจะต้องทำการขอลงทะเบียนสอนใหม่อีกครั้งคะ <a
                                href="http://<?= $_SERVER['SERVER_NAME'] ?>/#signup-form">ลงทะเบียน</a></li>
                    </ol>
                </small>
            </div>
            <br />
            <hr style="border-top:1px solid #cecece" />
            <div style="padding: 2em; text-align: left;">
                <br />
                Hello {firstname} {lastname},
                <div style="margin-top: 4em;text-align: left">
                    <h3>We received your application request</h3>
                    <h4>You're close to selling your course!</h4>
                    <div style="margin-top:2em">&bull; Build your Profile</div>
                    <div>&bull; Upload Application Documents</div>
                    <div>&bull; Specify Money Receiving Channel</div>
                    <div style="margin:1em 0 3em 0;"></div>
                    <div style="text-align: center">
                        <!--[if mso]>
                        <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="https://<?= $_SERVER['SERVER_NAME'] ?>/master/signup-email-confirmation?email={signup->email}&ccode={signup->confirmation-code}&type={signup->owner_type}" style="height:40px;v-text-anchor:middle;width:200px;" arcsize="50%" strokecolor="#15ca1b" fillcolor="#57c564">
                        <w:anchorlock/>
                        <center style="color:#ffffff;font-family:sans-serif;font-size:13px;font-weight:bold;">Get Started!</center>
                        </v:roundrect>
                    <![endif]-->
                        <a href="https://<?= $_SERVER['SERVER_NAME'] ?>/signup-email-confirmation?email={signup->email}&ccode={signup->confirmation-code}&type={signup->owner_type}"
                            style="background-color:#ec0607;border:1px solid #ec0607;border-radius:5px;color:#ffffff;display:inline-block;font-family:sans-serif;font-size:1.1rem;font-weight:bold;line-height:40px;text-align:center;text-decoration:none;width:200px;-webkit-text-size-adjust:none;mso-hide:all;">
                            Get Started!
                        </a>
                    </div>
                </div>
                <hr style="margin-top:4em;border:thin solid #f0f0f0;border-bottom: none;" />
                <small>
                    <u>Remarks:</u>
                    <ol>
                        <li>In case you received this email without any interaction with <?= $_SERVER['SERVER_NAME'] ?>,
                            you may discard this email immediately.</li>
                        <li>This Registration Confirmation Link is valid for <b>30 days</b> after receiving this mail.
                            You will need to Sign up again if this link has expired at the following url: <a
                                href="https://<?= $_SERVER['SERVER_NAME'] ?>/#signup-form">Apply for
                                Membership</a></li>
                    </ol>
                </small>
            </div>
        </div>
    </div>
    <div style="font-size:1em;color:#444444;margin-top: 2em;">
        <div style="margin-bottom:1em">Download SAMT Master Application</div>
        <a href="https://itunes.apple.com/th/app/samt-master/id1424840222?mt=8">
            <img src="https://<?= $_SERVER['SERVER_NAME'] ?>/img/main/appstore.png" />
        </a>
        <a href="https://play.google.com/store/apps/details?id=com.mysamt.teacher">
            <img src="https://<?= $_SERVER['SERVER_NAME'] ?>/img/main/googleplay.png" />
        </a>
    </div>
    <div style="font-size:0.8em;color:#999999;margin-top: 2em;">
        บริษัท เดอะกู้ดเชฟเพิร์ด จำกัด
    </div>
</div>