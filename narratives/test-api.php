<?php
/**
 * Simple test page for Story API
 * Provides a web interface to test the story API endpoints
 */

require_once '../auth.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Story API Test - NRDSandbox</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #1a1a1a;
            color: #e0e0e0;
        }
        
        .test-section {
            background: #2d2d2d;
            border: 1px solid #00d4ff;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .test-section h3 {
            color: #00d4ff;
            margin-top: 0;
        }
        
        button {
            background: #00d4ff;
            color: #000;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 10px;
            margin-bottom: 10px;
        }
        
        button:hover {
            background: #0099cc;
        }
        
        .response {
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 4px;
            padding: 15px;
            margin-top: 10px;
            white-space: pre-wrap;
            font-family: monospace;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .error {
            background: #2d1a1a;
            border-color: #cc0000;
            color: #ff4444;
        }
        
        .success {
            background: #1a2d1a;
            border-color: #00cc00;
            color: #44ff44;
        }
        
        .nav-back {
            margin-bottom: 20px;
        }
        
        .nav-back a {
            color: #00d4ff;
            text-decoration: none;
        }
        
        .nav-back a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="nav-back">
        <a href="index.php">← Back to Story Manager</a> | 
        <a href="../index.php">← Back to Game</a>
    </div>
    
    <h1>🧪 Story API Test Interface</h1>
    <p>Test the story API endpoints to ensure everything is working correctly.</p>
    
    <div class="test-section">
        <h3>1. Get Available Stories</h3>
        <button onclick="testGetStories()">Test Get Stories</button>
        <div id="response1" class="response"></div>
    </div>
    
    <div class="test-section">
        <h3>2. Load Test Story</h3>
        <button onclick="testLoadStory()">Test Load Story</button>
        <div id="response2" class="response"></div>
    </div>
    
    <div class="test-section">
        <h3>3. Get Story Effects</h3>
        <button onclick="testGetEffects()">Test Get Effects</button>
        <div id="response3" class="response"></div>
    </div>
    
    <div class="test-section">
        <h3>4. API Status Check</h3>
        <button onclick="testApiStatus()">Test API Status</button>
        <div id="response4" class="response"></div>
    </div>

    <script>
        async function makeAPICall(action, data = {}) {
            try {
                const requestData = {
                    action: action,
                    ...data
                };
                
                const response = await fetch('story-api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(requestData)
                });
                
                const result = await response.json();
                return {
                    success: true,
                    data: result,
                    status: response.status
                };
            } catch (error) {
                return {
                    success: false,
                    error: error.message
                };
            }
        }
        
        function displayResponse(elementId, response, apiCall) {
            const element = document.getElementById(elementId);
            
            if (response.success) {
                element.className = 'response success';
                element.textContent = `✅ API Call: ${apiCall}\n` +
                                    `Status: ${response.status}\n` +
                                    `Response:\n${JSON.stringify(response.data, null, 2)}`;
            } else {
                element.className = 'response error';
                element.textContent = `❌ API Call Failed: ${apiCall}\n` +
                                    `Error: ${response.error}`;
            }
        }
        
        async function testGetStories() {
            const response = await makeAPICall('get_stories');
            displayResponse('response1', response, 'get_stories');
        }
        
        async function testLoadStory() {
            const response = await makeAPICall('load_story', {
                story_id: 'test_story'
            });
            displayResponse('response2', response, 'load_story');
        }
        
        async function testGetEffects() {
            const response = await makeAPICall('get_story_effects');
            displayResponse('response3', response, 'get_story_effects');
        }
        
        async function testApiStatus() {
            const element = document.getElementById('response4');
            element.className = 'response';
            element.textContent = 'Testing API endpoint...';
            
            try {
                // Test with invalid request first
                const invalidResponse = await fetch('story-api.php', {
                    method: 'GET'
                });
                const invalidResult = await invalidResponse.text();
                
                // Test with valid request
                const validResponse = await makeAPICall('get_stories');
                
                if (validResponse.success) {
                    element.className = 'response success';
                    element.textContent = `✅ API Status: WORKING\n` +
                                        `GET request (should fail): ${invalidResult}\n` +
                                        `POST request (should work): SUCCESS\n` +
                                        `Stories available: ${validResponse.data.stories ? validResponse.data.stories.length : 0}`;
                } else {
                    element.className = 'response error';
                    element.textContent = `❌ API Status: ERROR\n${validResponse.error}`;
                }
            } catch (error) {
                element.className = 'response error';
                element.textContent = `❌ API Status: FAILED\n${error.message}`;
            }
        }
        
        // Auto-run basic test on page load
        window.addEventListener('DOMContentLoaded', function() {
            setTimeout(testApiStatus, 500);
        });
    </script>
</body>
</html>