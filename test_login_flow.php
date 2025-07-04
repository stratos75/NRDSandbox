<?php
// Test Login Flow
echo "=== Testing Login Flow ===\n";

// Test 1: Direct login page access
echo "1. Testing login page access...\n";
$loginContent = file_get_contents('http://localhost:8000/login.php');
if (strpos($loginContent, 'NRD TACTICAL SANDBOX') !== false) {
    echo "   ✅ Login page loads successfully\n";
} else {
    echo "   ❌ Login page failed to load\n";
}

// Test 2: Test login authentication via POST
echo "\n2. Testing login authentication...\n";
$postData = http_build_query([
    'username' => 'admin',
    'password' => 'password123'
]);

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/x-www-form-urlencoded',
        'content' => $postData
    ]
]);

$response = file_get_contents('http://localhost:8000/login.php', false, $context);
if (strpos($response, 'Location: index.php') !== false || strpos($response, 'Redirecting') !== false) {
    echo "   ✅ Login authentication successful (redirect detected)\n";
} else {
    echo "   ❌ Login authentication failed\n";
    echo "   Response preview: " . substr($response, 0, 200) . "...\n";
}

// Test 3: Test signup page access
echo "\n3. Testing signup page access...\n";
$signupContent = file_get_contents('http://localhost:8000/signup.php?access_key=nrd_admin_2024');
if (strpos($signupContent, 'ADMIN REGISTRATION') !== false) {
    echo "   ✅ Signup page loads successfully with access key\n";
} else {
    echo "   ❌ Signup page failed to load with access key\n";
}

// Test 4: Test signup without access key (should redirect)
echo "\n4. Testing signup without access key...\n";
$signupNoKey = file_get_contents('http://localhost:8000/signup.php');
if (strpos($signupNoKey, 'LOGIN') !== false) {
    echo "   ✅ Signup without access key properly redirects to login\n";
} else {
    echo "   ❌ Signup without access key failed to redirect\n";
}

echo "\n✅ Login flow test completed!\n";