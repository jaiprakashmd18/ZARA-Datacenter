<?php
// ============================================================
// upload-email.php — Zara Datacenter Resume Upload + Notification (SendGrid SMTP)
// ============================================================

// ---- Nextcloud credentials ----
$WEBDAV_BASE = "https://nextcloud.webistzu.space/remote.php/dav/files/zaradatacenter/";
$WEBDAV_USER = "zaradatacenter";
$WEBDAV_PASS = "Hi4gG-kgcEa-iKq2C-NPHad-FsqJX";

// ---- Notification Email (SendGrid) ----
define('EMAIL_TO', 'datacenterzara@gmail.com');    // Receiver (you)
define('EMAIL_FROM', 'datacenterzara@gmail.com');  // Sender (same)
define('EMAIL_FROM_NAME', 'Zara Datacenter Careers');

// ---- SendGrid SMTP Config ----
$USE_SMTP = true;
$SMTP_HOST = 'smtp.sendgrid.net';
$SMTP_PORT = 587;
$SMTP_USER = 'apikey'; // literal string "apikey"
$SMTP_PASS = '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';
$SMTP_SECURE = 'tls';

header('Content-Type: application/json');
function json_error($m){ echo json_encode(['status'=>'error','message'=>$m]); exit; }
function json_success($m,$extra=[]){ echo json_encode(array_merge(['status'=>'success','message'=>$m],$extra)); exit; }

// ---- Validate upload ----
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_error("Invalid request method");
if (!isset($_FILES['file-input']) || $_FILES['file-input']['error'] !== UPLOAD_ERR_OK)
    json_error("No file uploaded or upload error");

// ---- Collect form data ----
$name     = trim($_POST['full-name'] ?? '');
$email    = trim($_POST['email'] ?? '');
$phone    = trim($_POST['phone'] ?? '');
$position = trim($_POST['position'] ?? '');

// ---- File info ----
$file = $_FILES['file-input'];
$tmp_name = $file['tmp_name'];
$original_name = basename($file['name']);
$filesize = filesize($tmp_name);
if ($filesize > 5 * 1024 * 1024) json_error("File too large (max 5MB)");
$safe_name = preg_replace('/[^A-Za-z0-9_\-\. ]/', '_', $original_name);

// ---- Upload to Nextcloud ----
$folder = "Resumes/";
$target = rtrim($WEBDAV_BASE, '/') . '/' . $folder . rawurlencode($safe_name);

// Ensure folder exists
$mk = curl_init(rtrim($WEBDAV_BASE,'/').'/'.$folder);
curl_setopt_array($mk, [
  CURLOPT_USERPWD => "$WEBDAV_USER:$WEBDAV_PASS",
  CURLOPT_CUSTOMREQUEST => "MKCOL",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_HEADER => true
]);
curl_exec($mk);
curl_close($mk);

// Upload file
$ch = curl_init($target);
$fh = fopen($tmp_name, 'r');
curl_setopt_array($ch, [
  CURLOPT_USERPWD => "$WEBDAV_USER:$WEBDAV_PASS",
  CURLOPT_PUT => true,
  CURLOPT_INFILE => $fh,
  CURLOPT_INFILESIZE => $filesize,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_HEADER => true,
  CURLOPT_TIMEOUT => 60
]);
$response = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
fclose($fh);

if ($code < 200 || $code >= 300)
    json_error("Upload failed (HTTP $code)");

// ---- Send notification via PHPMailer + SendGrid ----
require __DIR__ . '/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = $SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = $SMTP_USER;
    $mail->Password = $SMTP_PASS;
    $mail->SMTPSecure = $SMTP_SECURE;
    $mail->Port = $SMTP_PORT;

    $mail->setFrom(EMAIL_FROM, EMAIL_FROM_NAME);
    $mail->addAddress(EMAIL_TO);
    if (!empty($email)) $mail->addReplyTo($email, $name ?: $email);
    $mail->Subject = "New Resume Submission: " . ($name ?: 'Unknown Applicant');

    $body = "
        <h3>New Resume Submitted</h3>
        <p><b>Name:</b> {$name}</p>
        <p><b>Email:</b> {$email}</p>
        <p><b>Phone:</b> {$phone}</p>
        <p><b>Position:</b> {$position}</p>
        <p><b>File:</b> {$safe_name}</p>
        <p>✅ Uploaded to Nextcloud folder: <code>{$folder}</code></p>
    ";

    $mail->isHTML(true);
    $mail->Body = $body;
    $mail->AltBody = strip_tags($body);
    $mail->addAttachment($tmp_name, $safe_name);
    $mail->send();

    json_success("Uploaded to Nextcloud & notification email sent!", ['filename'=>$safe_name]);
} catch (Exception $e) {
    json_success("Uploaded to Nextcloud, but email failed: ".$mail->ErrorInfo, ['filename'=>$safe_name]);
}
?>
