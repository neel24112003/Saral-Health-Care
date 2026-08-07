<?php
session_start();

function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function sendJsonResponse($status, $message) {
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => $status, 'message' => $message]);
    exit;
}

function redirectWithMessage($status, $message) {
    $siteUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
    $redirectBase = rtrim($siteUrl . dirname($_SERVER['PHP_SELF']), '/') . '/index.html';
    $query = http_build_query(['booking' => $status, 'message' => $message]);
    header('Location: ' . $redirectBase . '?' . $query);
    exit;
}

function sanitizeText($value) {
    return trim(strip_tags((string) $value));
}

function buildEmailTemplate($title, $intro, $bodyHtml, $footerText) {
    return <<<HTML
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title}</title>
  </head>
  <body style="margin:0;padding:0;background:#f3f7ff;font-family:Arial,Helvetica,sans-serif;color:#11253f;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f3f7ff;padding:24px 0;">
      <tr>
        <td align="center">
          <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:660px;background:#ffffff;border:1px solid #e1ebf7;border-radius:24px;overflow:hidden;">
            <tr>
              <td style="background:linear-gradient(135deg,#0f4c81 0%,#2563eb 45%,#f97316 100%);padding:32px 34px;color:#ffffff;">
                <div style="font-size:12px;letter-spacing:2.4px;text-transform:uppercase;opacity:0.92;font-weight:700;">Saral Health Care</div>
                <div style="font-size:28px;font-weight:800;margin-top:8px;line-height:1.2;">{$title}</div>
                <div style="font-size:15px;line-height:1.7;margin-top:10px;opacity:0.96;max-width:520px;">{$intro}</div>
              </td>
            </tr>
            <tr>
              <td style="padding:28px 34px 20px;">
                <div style="background:#fbfdff;border:1px solid #eaf4ff;border-radius:18px;padding:22px 24px;box-shadow:0 10px 24px -20px rgba(17,37,63,0.24);">
                  {$bodyHtml}
                </div>
              </td>
            </tr>
            <tr>
              <td style="padding:0 34px 32px;">
                <div style="font-size:13px;color:#5f6d7b;line-height:1.7;border-top:1px solid #e8eef7;padding-top:16px;">{$footerText}</div>
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
  </body>
</html>
HTML;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectWithMessage('error', 'Invalid request method.');
}

$name = sanitizeText($_POST['name'] ?? '');
$email = filter_var(trim((string) ($_POST['email'] ?? '')), FILTER_SANITIZE_EMAIL);
$phone = sanitizeText($_POST['phone'] ?? '');
$department = sanitizeText($_POST['department'] ?? '');
$date = sanitizeText($_POST['date'] ?? '');
$time = sanitizeText($_POST['time'] ?? '');
$notes = sanitizeText($_POST['notes'] ?? '');

$errors = [];
if ($name === '') {
    $errors[] = 'Please enter your full name.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please enter a valid email address.';
}
if ($phone === '' || !preg_match('/^[0-9+\-\s]{7,15}$/', $phone)) {
    $errors[] = 'Please enter a valid phone number.';
}
if ($department === '') {
    $errors[] = 'Please choose a department.';
}
if ($date === '') {
    $errors[] = 'Please choose a preferred date.';
}
if ($time === '') {
    $errors[] = 'Please choose a preferred time.';
}

if ($errors) {
    $errorMessage = implode(' ', $errors);
    if (isAjaxRequest()) {
        sendJsonResponse(false, $errorMessage);
    }
    redirectWithMessage('error', $errorMessage);
}

$hospitalEmail = '21amtics441@gmail.com';
$hospitalName = 'Saral Health Care';

$subject = 'New appointment request from ' . $name;
$hospitalBody = <<<HTML
<p style="margin:0 0 8px;font-size:15px;color:#11253f;"><strong>New appointment request received from the website.</strong></p>
<p style="margin:0 0 16px;font-size:14px;color:#5f6d7b;">A patient has submitted an appointment request. Please review the details below.</p>
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border-collapse:collapse;">
  <tr>
    <td style="padding:8px 0;border-bottom:1px solid #edf3fb;font-size:14px;color:#11253f;"><strong style="color:#2563eb;">Name:</strong> {$name}</td>
  </tr>
  <tr>
    <td style="padding:8px 0;border-bottom:1px solid #edf3fb;font-size:14px;color:#11253f;"><strong style="color:#2563eb;">Email:</strong> {$email}</td>
  </tr>
  <tr>
    <td style="padding:8px 0;border-bottom:1px solid #edf3fb;font-size:14px;color:#11253f;"><strong style="color:#2563eb;">Phone:</strong> {$phone}</td>
  </tr>
  <tr>
    <td style="padding:8px 0;border-bottom:1px solid #edf3fb;font-size:14px;color:#11253f;"><strong style="color:#2563eb;">Department:</strong> {$department}</td>
  </tr>
  <tr>
    <td style="padding:8px 0;border-bottom:1px solid #edf3fb;font-size:14px;color:#11253f;"><strong style="color:#2563eb;">Date:</strong> {$date}</td>
  </tr>
  <tr>
    <td style="padding:8px 0;border-bottom:1px solid #edf3fb;font-size:14px;color:#11253f;"><strong style="color:#2563eb;">Time:</strong> {$time}</td>
  </tr>
  <tr>
    <td style="padding:8px 0;font-size:14px;color:#11253f;"><strong style="color:#2563eb;">Notes:</strong> {$notes}</td>
  </tr>
</table>
HTML;

$patientBody = <<<HTML
<p style="margin:0 0 8px;font-size:15px;color:#11253f;"><strong>Thank you for choosing {$hospitalName}.</strong></p>
<p style="margin:0 0 14px;font-size:14px;color:#5f6d7b;">Your appointment request for {$department} on {$date} at {$time} has been received successfully.</p>
<div style="background:#f8fbff;border:1px solid #eaf4ff;border-radius:14px;padding:14px 16px;font-size:14px;color:#11253f;">
  <p style="margin:0 0 6px;">Our care team will contact you shortly at <strong>{$phone}</strong> or <strong>{$email}</strong>.</p>
  <p style="margin:0;">We look forward to helping you with your consultation.</p>
</div>
HTML;

$hospitalMessage = buildEmailTemplate(
    'New Appointment Request',
    'A new consultation request has been submitted through the Saral Health Care website.',
    $hospitalBody,
    'This message was generated automatically from the appointment form.'
);

$patientMessage = buildEmailTemplate(
    'Appointment Request Received',
    'Your appointment request is now in our system and our team will follow up soon.',
    $patientBody,
    'Thank you for trusting Saral Health Care.'
);

$sent = false;

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';

    $mailer = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $smtpHost = 'smtp.gmail.com';
        $smtpPort = 587;
        $smtpUser = '21amtics441@gmail.com';
        $smtpPass = 'pnse hkyn uclf leki';
        $smtpSecure = 'tls';

        $mailer->isSMTP();
        $mailer->Host = $smtpHost;
        $mailer->Port = (int) $smtpPort;
        $mailer->SMTPAuth = true;
        $mailer->Username = $smtpUser;
        $mailer->Password = $smtpPass;
        $mailer->SMTPSecure = $smtpSecure;
        $mailer->SMTPDebug = 0;
        $mailer->CharSet = 'UTF-8';
        $mailer->Timeout = 20;
        $mailer->setFrom($smtpUser, $hospitalName);
        $mailer->addAddress($hospitalEmail, $hospitalName);
        $mailer->addReplyTo($email, $name);
        $mailer->Subject = $subject;
        $mailer->isHTML(true);
        $mailer->Body = $hospitalMessage;
        $mailer->send();

        $mailer->clearAllRecipients();
        $mailer->clearReplyTos();
        $mailer->setFrom($smtpUser, $hospitalName);
        $mailer->addAddress($email, $name);
        $mailer->Subject = 'Your appointment request was received';
        $mailer->Body = $patientMessage;
        $mailer->send();
        $sent = true;
    } catch (Throwable $e) {
        $sent = false;
    }
}

if (!$sent) {
    $headers = "From: {$hospitalName} <{$hospitalEmail}>\r\n";
    $headers .= "Reply-To: {$name} <{$email}>\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $hospitalSent = @mail($hospitalEmail, $subject, $hospitalMessage, $headers);
    $patientSent = @mail($email, 'Your appointment request was received', $patientMessage, $headers);
    $sent = $hospitalSent && $patientSent;
}

$successMessage = 'Appointment request sent successfully. We will contact you shortly.';
$errorMessage = 'We could not send your request right now. Please call the hospital directly.';

if ($sent) {
    if (isAjaxRequest()) {
        sendJsonResponse(true, $successMessage);
    }
    redirectWithMessage('success', $successMessage);
}

if (isAjaxRequest()) {
    sendJsonResponse(false, $errorMessage);
}
redirectWithMessage('error', $errorMessage);
