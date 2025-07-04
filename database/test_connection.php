<?php
// ===================================================================
// NRD SANDBOX - DATABASE CONNECTION TESTER
// Test MySQL connectivity and initialize schema for local development
// ===================================================================

// Prevent direct web access in production
if (!in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1', '::1'])) {
    die('Database testing only available in development environment.');
}

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/User.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Test - NRDSandbox</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .test-container { max-width: 800px; margin: 50px auto; padding: 20px; }
        .test-result { margin: 20px 0; padding: 15px; border-radius: 8px; }
        .success { background: rgba(0, 255, 100, 0.1); border: 1px solid #00cc66; color: #00ff66; }
        .error { background: rgba(255, 50, 50, 0.1); border: 1px solid #ff3333; color: #ff6666; }
        .info { background: rgba(0, 212, 255, 0.1); border: 1px solid #00d4ff; color: #00d4ff; }
        .test-step { margin: 10px 0; padding: 10px; background: rgba(255, 255, 255, 0.05); border-radius: 4px; }
        pre { background: rgba(0, 0, 0, 0.3); padding: 10px; border-radius: 4px; overflow-x: auto; }
        .action-buttons { margin: 20px 0; }
        .action-btn { 
            background: linear-gradient(135deg, #00d4ff, #0099cc); 
            color: #000; border: none; padding: 10px 20px; border-radius: 4px; 
            margin: 5px; cursor: pointer; font-weight: bold;
        }
        .action-btn:hover { background: linear-gradient(135deg, #00b8e6, #007399); }
    </style>
</head>
<body style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%); color: #fff; font-family: 'Segoe UI', sans-serif; min-height: 100vh;">
    <div class="test-container">
        <h1 style="text-align: center; color: #00d4ff; margin-bottom: 30px;">
            🛠️ NRDSandbox Database Tester
        </h1>
        
        <div class="action-buttons" style="text-align: center;">
            <button class="action-btn" onclick="testConnection()">🔍 Test Connection</button>
            <button class="action-btn" onclick="initializeSchema()">🏗️ Initialize Schema</button>
            <button class="action-btn" onclick="testUser()">👤 Test User System</button>
            <button class="action-btn" onclick="showEnvironment()">🌍 Show Environment</button>
        </div>
        
        <div id="results"></div>
        
        <script>
        function showResult(title, content, type = 'info') {
            const resultsDiv = document.getElementById('results');
            const resultDiv = document.createElement('div');
            resultDiv.className = `test-result ${type}`;
            resultDiv.innerHTML = `<h3>${title}</h3>${content}`;
            resultsDiv.appendChild(resultDiv);
        }
        
        function testConnection() {
            fetch('?action=test_connection')
                .then(response => response.json())
                .then(data => {
                    const type = data.status === 'success' ? 'success' : 'error';
                    const content = `
                        <p><strong>Status:</strong> ${data.status}</p>
                        <p><strong>Message:</strong> ${data.message}</p>
                        ${data.server_time ? `<p><strong>Server Time:</strong> ${data.server_time}</p>` : ''}
                        <pre>${JSON.stringify(data.environment, null, 2)}</pre>
                    `;
                    showResult('🔍 Database Connection Test', content, type);
                })
                .catch(error => {
                    showResult('🔍 Database Connection Test', `<p>Error: ${error.message}</p>`, 'error');
                });
        }
        
        function initializeSchema() {
            if (!confirm('This will create/update database tables. Continue?')) return;
            
            fetch('?action=initialize_schema')
                .then(response => response.json())
                .then(data => {
                    const type = data.status === 'success' ? 'success' : 'error';
                    showResult('🏗️ Schema Initialization', `<p>${data.message}</p>`, type);
                })
                .catch(error => {
                    showResult('🏗️ Schema Initialization', `<p>Error: ${error.message}</p>`, 'error');
                });
        }
        
        function testUser() {
            fetch('?action=test_user')
                .then(response => response.json())
                .then(data => {
                    const type = data.status === 'success' ? 'success' : 'error';
                    const content = `
                        <p><strong>Status:</strong> ${data.status}</p>
                        <p><strong>Message:</strong> ${data.message}</p>
                        ${data.user_data ? `<pre>${JSON.stringify(data.user_data, null, 2)}</pre>` : ''}
                    `;
                    showResult('👤 User System Test', content, type);
                })
                .catch(error => {
                    showResult('👤 User System Test', `<p>Error: ${error.message}</p>`, 'error');
                });
        }
        
        function showEnvironment() {
            fetch('?action=show_environment')
                .then(response => response.json())
                .then(data => {
                    const content = `<pre>${JSON.stringify(data, null, 2)}</pre>`;
                    showResult('🌍 Environment Information', content, 'info');
                })
                .catch(error => {
                    showResult('🌍 Environment Information', `<p>Error: ${error.message}</p>`, 'error');
                });
        }
        </script>
    </div>
</body>
</html>

<?php
// Handle AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    try {
        $db = Database::getInstance();
        $userManager = new User();
        
        switch ($_GET['action']) {
            case 'test_connection':
                echo json_encode($db->testConnection());
                break;
                
            case 'initialize_schema':
                try {
                    $db->initializeSchema();
                    echo json_encode([
                        'status' => 'success',
                        'message' => 'Database schema initialized successfully!'
                    ]);
                } catch (Exception $e) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => $e->getMessage()
                    ]);
                }
                break;
                
            case 'test_user':
                try {
                    // Test user authentication with default admin user
                    $user = $userManager->authenticate('admin', 'password123');
                    if ($user) {
                        echo json_encode([
                            'status' => 'success',
                            'message' => 'User authentication successful!',
                            'user_data' => $user
                        ]);
                    } else {
                        echo json_encode([
                            'status' => 'error',
                            'message' => 'User authentication failed. Make sure schema is initialized.'
                        ]);
                    }
                } catch (Exception $e) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => $e->getMessage()
                    ]);
                }
                break;
                
            case 'show_environment':
                echo json_encode([
                    'server_info' => [
                        'server_name' => $_SERVER['SERVER_NAME'],
                        'http_host' => $_SERVER['HTTP_HOST'] ?? 'unknown',
                        'php_version' => PHP_VERSION,
                        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown'
                    ],
                    'database_config' => $db->getEnvironmentInfo(),
                    'constants' => [
                        'ENVIRONMENT' => defined('ENVIRONMENT') ? ENVIRONMENT : 'not defined',
                        'BASE_URL' => defined('BASE_URL') ? BASE_URL : 'not defined',
                        'DATA_DIR' => defined('DATA_DIR') ? DATA_DIR : 'not defined'
                    ]
                ]);
                break;
                
            default:
                echo json_encode(['status' => 'error', 'message' => 'Unknown action']);
        }
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
    exit;
}
?>