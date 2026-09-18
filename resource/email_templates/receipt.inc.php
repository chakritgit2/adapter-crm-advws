<div style="max-width: 600px; margin: 0 auto; padding: 20px;">
    <!-- Header -->
    <div
        style="background-color: #ffffff; padding: 24px; border-radius: 12px 12px 0 0; border: 1px solid #e2e8f0; border-bottom: none;">
        <div style="text-align: center; margin-bottom: 20px;">
            <img src="https://www.xn--72ci0esbkc6qd.com/img/logo.png" alt="Company Logo" style="max-height: 60px;">
        </div>
        <h1 style="color: #0f172a; font-size: 24px; font-weight: bold; margin: 0; text-align: center;">Payment Receipt
        </h1>
    </div>

    <!-- Main Content -->
    <div
        style="background-color: #ffffff; padding: 24px; border: 1px solid #e2e8f0; border-top: none; border-bottom: none;">
        <!-- Thank You Message -->
        <div style="margin-bottom: 24px;">
            <h2 style="color: #0f172a; font-size: 18px; font-weight: bold; margin: 0 0 12px 0;">Thank you for your
                booking!</h2>
            <p style="margin: 0; color: #64748b;">We're excited to confirm your booking. Below is a summary of your
                payment.</p>
        </div>

        <!-- Booking Details -->
        <div style="background-color: #f1f5f9; padding: 16px; border-radius: 8px; margin-bottom: 24px;">
            <h3 style="color: #0f172a; font-size: 16px; font-weight: bold; margin: 0 0 12px 0;">Booking Details</h3>
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 8px 0; color: #64748b; width: 40%;">Booking ID:</td>
                    <td style="padding: 8px 0; font-weight: 500; color: #334155;"><b>{booking_id}</b></td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #64748b;">Date & Time:</td>
                    <td style="padding: 8px 0; font-weight: 500; color: #334155;">{booking_date} - {booking_time}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #64748b;">Studio:</td>
                    <td style="padding: 8px 0; font-weight: 500; color: #334155;">{branch_name}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #64748b;">Duration:</td>
                    <td style="padding: 8px 0; font-weight: 500; color: #334155;">{duration}</td>
                </tr>
            </table>
        </div>

        <!-- Payment Details -->
        <div style="margin-bottom: 24px;">
            <h3 style="color: #0f172a; font-size: 16px; font-weight: bold; margin: 0 0 12px 0;">Payment Details</h3>
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 8px 0; color: #64748b; border-bottom: 1px solid #e2e8f0;">Room</td>
                    <td
                        style="padding: 8px 0; text-align: right; font-weight: 500; color: #334155; border-bottom: 1px solid #e2e8f0;">
                        {room_prices}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #64748b; border-bottom: 1px solid #e2e8f0;">Additional Services
                    </td>
                    <td
                        style="padding: 8px 0; text-align: right; font-weight: 500; color: #334155; border-bottom: 1px solid #e2e8f0;">
                        {additional_services_fee}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #64748b; border-bottom: 1px solid #e2e8f0;">Discount</td>
                    <td
                        style="padding: 8px 0; text-align: right; font-weight: 500; color: #10b981; border-bottom: 1px solid #e2e8f0;">
                        {discount_amount}</td>
                </tr>
                <tr>
                    <td style="padding: 12px 0; font-weight: bold; color: #0f172a; font-size: 16px;">Total Paid</td>
                    <td style="padding: 12px 0; text-align: right; font-weight: bold; color: #0f172a; font-size: 16px;">
                        {total_amount}</td>
                </tr>
            </table>
        </div>

        <!-- Payment Method -->
        <div style="background-color: #f1f5f9; padding: 16px; border-radius: 8px; margin-bottom: 24px;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 8px 0; color: #64748b; width: 40%;">Payment Method:</td>
                    <td style="padding: 8px 0; font-weight: 500; color: #334155;">QR, CASH</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #64748b;">Status:</td>
                    <td style="padding: 8px 0;">
                        {status}
                    </td>
                </tr>
            </table>
        </div>

        <!-- Call to Action -->
        <div style="text-align: center; margin-bottom: 24px;">
            <a href="{booking_url}"
                style="display: inline-block; background-color: #2563eb; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-weight: 500; font-size: 16px; margin-top:12px;">View
                Booking Details</a>
        </div>
    </div>

    <!-- Additional Information -->
    <div
        style="background-color: #ffffff; padding: 0px 24px 24px 24px; border: 1px solid #e2e8f0; border-top: none; border-radius: 0 0 12px 12px;">
        <h3 style="color: #0f172a; font-size: 16px; font-weight: bold; margin: 0 0 12px 0;">Important Information</h3>
        <ul style="padding-left: 20px; margin: 0 0 24px 0; color: #64748b;">
            <li style="margin-bottom: 8px;">Please arrive 15 minutes before your booking time.</li>
            <li style="margin-bottom: 8px;">Cancellations must be made at least 24 hours in advance for a full refund.
            </li>
            <li style="margin-bottom: 8px;">For any questions or assistance, please contact our support team.</li>
        </ul>

        <!-- Support Contact -->
        <div style="text-align: center;padding-top: 12px;">
            <p style="margin: 0 0 8px 0; color: #64748b;">Need help? Contact our support team</p>
            <a href="mailto:hongzom@mysamt.com"
                style="color: #2563eb; text-decoration: none; font-weight: 500;">hongzom@mysamt.com</a>
        </div>
    </div>

    <!-- Footer -->
    <div style="text-align: center; padding: 24px 0;">
        <p style="margin: 0 0 8px 0; color: #94a3b8; font-size: 14px;"> 2025 ห้องซ้อม.com All rights reserved.</p>
        <div style="margin-bottom: 16px;">
            <a href="{privacy_policy_url}"
                style="color: #64748b; text-decoration: none; font-size: 14px; margin: 0 8px;">Privacy Policy</a>
            <a href="{terms_of_service_url}" style="color: #64748b; text-decoration: none; font-size: 14px; margin: 0 8px;">Terms
                of Service</a>
        </div>
        <p style="margin: 0; color: #94a3b8; font-size: 12px;">This is an automated email. Please do not reply to this. <a href="{{unsubscribe_url}}">Unsubscribe</a>
            message.</p>
    </div>
</div>