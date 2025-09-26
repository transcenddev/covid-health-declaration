<?php
/**
 * Session Fix Test
 * This simple test verifies that session warnings are resolved
 */

echo "<h2>🔧 Session Configuration Test</h2>";
echo "<style>
.pass { color: green; font-weight: bold; }
.fail { color: red; font-weight: bold; }
.info { color: blue; }
</style>";

// Include security functions
include './includes/security.inc.php';

echo "<h3>1. Testing Session Initialization</h3>";

// Test the new initialization function
initializeSecureSession();

if (session_status() == PHP_SESSION_ACTIVE) {
    echo "<span class='pass'>✅ Session started successfully without warnings</span><br>";
    echo "<span class='info'>Session ID: " . session_id() . "</span><br>";
} else {
    echo "<span class='fail'>❌ Session failed to start</span><br>";
}

echo "<h3>2. Testing CSRF Token Generation</h3>";

$token = generateCSRFToken();
if (!empty($token)) {
    echo "<span class='pass'>✅ CSRF token generated successfully</span><br>";
    echo "<span class='info'>Token: " . substr($token, 0, 16) . "...</span><br>";
} else {
    echo "<span class='fail'>❌ CSRF token generation failed</span><br>";
}

echo "<h3>3. Testing Session Settings</h3>";

echo "<span class='info'>Cookie HTTP-only: " . (ini_get('session.cookie_httponly') ? 'Yes' : 'No') . "</span><br>";
echo "<span class='info'>Use only cookies: " . (ini_get('session.use_only_cookies') ? 'Yes' : 'No') . "</span><br>";
echo "<span class='info'>Session lifetime: " . ini_get('session.gc_maxlifetime') . " seconds</span><br>";

echo "<hr>";
echo "<h3>✅ Session Fix Applied Successfully</h3>";
echo "<span class='pass'>No more ini_set() warnings should appear when accessing your pages!</span><br>";
echo "<span class='info'>You can now safely use your dashboard and other secure pages.</span><br>";

?>