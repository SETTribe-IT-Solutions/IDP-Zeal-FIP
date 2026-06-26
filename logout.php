<?php
// Start output buffering to avoid "headers already sent" issues
ob_start();
session_start();

// Debug: append request info to a log to verify the script is being invoked
$debugEntry = [
    'time' => date('c'),
    'request_uri' => isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '',
    'php_self' => isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '',
    'method' => isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '',
    'headers_sent' => headers_sent(),
    'remote_addr' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '',
];
@file_put_contents(__DIR__ . '/logout_debug.log', json_encode($debugEntry) . PHP_EOL, FILE_APPEND | LOCK_EX);
// Unset all session variables
$_SESSION = array();

// Destroy the session cookie if it exists
if (ini_get("use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Redirect to login page
// Determine base path for redirect
$basePath = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
if ($basePath === '' || $basePath === '/') {
        $basePath = '';
}

// Build absolute URL for reliability
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
$redirectUrl = $host ? "{$scheme}://{$host}{$basePath}/landingpage.php" : "{$basePath}/landingpage.php";


// Prefer PHP redirect when possible
if (!headers_sent()) {
        header("Location: {$redirectUrl}");
        // End buffering and exit to stop further output
        ob_end_flush();
        exit;
}

// Fallback: output small HTML page with meta-refresh and JS redirect
?>
<!doctype html>
<html>
    <head>
        <meta http-equiv="refresh" content="0;url=<?php echo htmlspecialchars($loginUrl, ENT_QUOTES); ?>">
        <script>window.location.replace(<?php echo json_encode($loginUrl); ?>);</script>
        <title>Redirecting...</title>
    </head>
    <body>
        Redirecting to login...
        <noscript><a href="<?php echo htmlspecialchars($loginUrl, ENT_QUOTES); ?>">Click here if not redirected</a></noscript>
    </body>
</html>
<?php
// Flush buffer if any and end script
ob_end_flush();
exit;
?>
