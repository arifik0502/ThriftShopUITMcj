<?php
/*this file sheet is for php mailer functions
    hadif hashim*/

// PHPMailer temporarily disabled - logging instead
// Download PHPMailer from: https://github.com/PHPMailer/PHPMailer
// Place the PHPMailer folder in your project directory

// use PHPMailer\PHPMailer\PHPMailer;
// use PHPMailer\PHPMailer\Exception;

// require 'PHPMailer/src/Exception.php';
// require 'PHPMailer/src/PHPMailer.php';
// require 'PHPMailer/src/SMTP.php';

/**
 * Send email using PHPMailer (DISABLED)
 */
function sendEmail($to, $subject, $body, $altBody = '') {
    // Email functionality disabled - log instead
    error_log("Email queued: To: $to, Subject: $subject");
    return true;
    
    /* ORIGINAL CODE - COMMENTED OUT
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;

        // Recipients
        $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = $altBody ?: strip_tags($body);

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
    */
}

/**
 * Send welcome email to new users (DISABLED)
 */
function sendWelcomeEmail($email, $username) {
    error_log("Welcome email queued for: $email (Username: $username)");
    return true;
    
    /* ORIGINAL CODE - COMMENTED OUT
    $subject = "Welcome to " . SITE_NAME;
    $body = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #1a1a1a; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f9f9f9; }
            .button { display: inline-block; padding: 12px 30px; background: #d4af37; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Welcome to " . SITE_NAME . "</h1>
            </div>
            <div class='content'>
                <h2>Hello $username!</h2>
                <p>Thank you for joining our marketplace. We're excited to have you on board!</p>
                <p>You can now:</p>
                <ul>
                    <li>Browse thousands of products</li>
                    <li>Add items to your wishlist</li>
                    <li>Book tickets for bus, train, and grabs</li>
                    <li>Manage your profile and orders</li>
                </ul>
                <a href='" . SITE_URL . "main.php' class='button'>Start Shopping</a>
                <p>If you have any questions, feel free to contact our support team.</p>
                <p>Best regards,<br>The " . SITE_NAME . " Team</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($email, $subject, $body);
    */
}

/**
 * Send order confirmation email (DISABLED)
 */
function sendOrderConfirmation($email, $username, $order_id, $total) {
    error_log("Order confirmation queued: Email: $email, Order: $order_id, Total: $total");
    return true;
    
    /* ORIGINAL CODE - COMMENTED OUT
    $subject = "Order Confirmation - #" . $order_id;
    $body = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #1a1a1a; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f9f9f9; }
            .order-info { background: white; padding: 15px; margin: 20px 0; border-left: 4px solid #d4af37; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Order Confirmation</h1>
            </div>
            <div class='content'>
                <h2>Thank you for your order, $username!</h2>
                <div class='order-info'>
                    <p><strong>Order ID:</strong> #$order_id</p>
                    <p><strong>Total Amount:</strong> $" . number_format($total, 2) . "</p>
                    <p><strong>Status:</strong> Processing</p>
                </div>
                <p>We'll send you another email once your order has been shipped.</p>
                <p>You can track your order status in your account dashboard.</p>
                <p>Best regards,<br>The " . SITE_NAME . " Team</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($email, $subject, $body);
    */
}

/**
 * Send ticket booking confirmation (DISABLED)
 */
function sendTicketConfirmation($email, $booking_reference, $details = []) {
    error_log("Ticket confirmation queued: Email: $email, Reference: $booking_reference");
    return true;
    
    /* ORIGINAL CODE - COMMENTED OUT
    $subject = "Ticket Booking Confirmation - " . $booking_reference;
    $body = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #1a1a1a; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f9f9f9; }
            .ticket-info { background: white; padding: 20px; margin: 20px 0; border: 2px solid #d4af37; border-radius: 8px; }
            .reference { font-size: 24px; color: #d4af37; text-align: center; margin: 20px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Ticket Booking Confirmed</h1>
            </div>
            <div class='content'>
                <h2>Your ticket has been booked successfully!</h2>
                <div class='reference'>$booking_reference</div>
                <div class='ticket-info'>
                    <p><strong>Booking Reference:</strong> $booking_reference</p>
                    <p><strong>Status:</strong> Confirmed</p>
                    <p>Please save this reference number for your records.</p>
                </div>
                <p>You can view your booking details in your account dashboard.</p>
                <p>Have a safe journey!</p>
                <p>Best regards,<br>The " . SITE_NAME . " Team</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($email, $subject, $body);
    */
}

/**
 * Send password reset email (DISABLED)
 */
function sendPasswordResetEmail($email, $username, $reset_token) {
    error_log("Password reset queued for: $email (Username: $username)");
    return true;
    
    /* ORIGINAL CODE - COMMENTED OUT
    $reset_link = SITE_URL . "reset-password.php?token=" . $reset_token;
    $subject = "Password Reset Request";
    $body = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #1a1a1a; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f9f9f9; }
            .button { display: inline-block; padding: 12px 30px; background: #d4af37; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            .warning { background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 20px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Password Reset Request</h1>
            </div>
            <div class='content'>
                <h2>Hello $username,</h2>
                <p>We received a request to reset your password. Click the button below to create a new password:</p>
                <a href='$reset_link' class='button'>Reset Password</a>
                <p>This link will expire in 1 hour.</p>
                <div class='warning'>
                    <strong>⚠️ Security Notice:</strong><br>
                    If you didn't request this password reset, please ignore this email. Your password will remain unchanged.
                </div>
                <p>Best regards,<br>The " . SITE_NAME . " Team</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($email, $subject, $body);
    */
}
?>
