<?php
// redirect-to-call.php
// Enable error logging for debugging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/redirect-to-call.log');

// Get the raw phone number from the query parameter
$rawPhone = isset($_GET['phone']) ? $_GET['phone'] : '+998909354583'; // Raw value
file_put_contents(__DIR__ . '/redirect-to-call.log', "Raw phone: $rawPhone\n", FILE_APPEND);

// Decode the URL-encoded phone number
$phone = rawurldecode($rawPhone);
file_put_contents(__DIR__ . '/redirect-to-call.log', "Decoded phone: $phone\n", FILE_APPEND);

// Ensure the phone number starts with a +
if (strpos($phone, '+') !== 0) {
    $phone = '+' . $phone;
}
file_put_contents(__DIR__ . '/redirect-to-call.log', "Phone after adding +: $phone\n", FILE_APPEND);

// Sanitize the phone number for HTML output, but allow the + sign
$phone = htmlspecialchars($phone, ENT_QUOTES, 'UTF-8', false);
file_put_contents(__DIR__ . '/redirect-to-call.log', "Final phone after htmlspecialchars: $phone\n", FILE_APPEND);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Redirecting to Call</title>
    <meta http-equiv="refresh" content="0;url=tel:<?php echo $phone; ?>">
</head>
<body>
    <p>Redirecting to call...</p>
    <script>
        window.location.href = "tel:<?php echo $phone; ?>";
    </script>
</body>
</html>