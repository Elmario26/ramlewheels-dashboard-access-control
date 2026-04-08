#!/usr/bin/env php
<?php
/**
 * RamleWheels API Endpoints Test Script
 * Tests the 3+ required working endpoints with JSON responses
 */

// Configuration
$baseUrl = 'http://localhost/ramlewheels/public';
$timeout = 5;

// Color codes for terminal output
class Colors {
    const GREEN = "\033[92m";
    const RED = "\033[91m";
    const BLUE = "\033[94m";
    const YELLOW = "\033[93m";
    const RESET = "\033[0m";
}

// Test results
$results = [];
$passCount = 0;
$failCount = 0;

echo Colors::BLUE . "\n╔════════════════════════════════════════════╗\n";
echo "║   RamleWheels API Endpoints Test Suite   ║\n";
echo "╚════════════════════════════════════════════╝\n" . Colors::RESET;
echo "Base URL: " . Colors::YELLOW . $baseUrl . Colors::RESET . "\n";
echo "Testing " . Colors::YELLOW . "3 required endpoints" . Colors::RESET . " for standardized JSON responses\n\n";

/**
 * Test an API endpoint
 */
function testEndpoint($name, $method, $endpoint, $data = null) {
    global $baseUrl, $timeout, $results, $passCount, $failCount;
    
    $url = $baseUrl . $endpoint;
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Content-Type: application/json',
        ],
    ]);
    
    if ($data && $method === 'POST') {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    $result = [
        'name' => $name,
        'method' => $method,
        'endpoint' => $endpoint,
        'url' => $url,
        'httpCode' => $httpCode,
        'success' => false,
        'isJson' => false,
        'response' => null,
    ];
    
    // Check response
    if ($error) {
        $result['error'] = $error;
        echo Colors::RED . "✗ FAILED" . Colors::RESET . ": $name (Connection Error)\n";
        echo "  Error: " . Colors::RED . $error . Colors::RESET . "\n";
        $failCount++;
    } elseif ($httpCode >= 200 && $httpCode < 300) {
        // Try to parse JSON
        $decoded = json_decode($response, true);
        if ($decoded !== null || $response === 'null') {
            $result['isJson'] = true;
            $result['success'] = true;
            $result['response'] = $decoded;
            echo Colors::GREEN . "✓ PASSED" . Colors::RESET . ": $name\n";
            echo "  HTTP: " . Colors::GREEN . $httpCode . Colors::RESET;
            echo " | Format: " . Colors::GREEN . "JSON" . Colors::RESET . "\n";
            $passCount++;
        } else {
            $result['error'] = 'Invalid JSON response';
            echo Colors::RED . "✗ FAILED" . Colors::RESET . ": $name (Not JSON)\n";
            echo "  Response: " . substr($response, 0, 100) . "...\n";
            $failCount++;
        }
    } else {
        $result['httpCode'] = $httpCode;
        echo Colors::RED . "✗ FAILED" . Colors::RESET . ": $name\n";
        echo "  HTTP Code: " . Colors::RED . $httpCode . Colors::RESET . "\n";
        $failCount++;
    }
    
    $results[] = $result;
    echo "\n";
}

// ==================== TEST ENDPOINTS ====================

echo Colors::BLUE . "Testing Required Endpoints:\n" . Colors::RESET;
echo str_repeat("─", 50) . "\n\n";

// Test 1: GET Cars (Required)
testEndpoint(
    "GET /api/cars (Cars Collection)",
    'GET',
    '/api/cars.json'
);

// Test 2: GET Services Statistics (Required)
testEndpoint(
    "GET /services/api/statistics (Service Stats)",
    'GET',
    '/services/api/statistics'
);

// Test 3: GET Users Mechanics (Required)
testEndpoint(
    "GET /users/api/mechanics (Mechanics List)",
    'GET',
    '/users/api/mechanics'
);

// Additional endpoints (Bonus)
echo Colors::YELLOW . "\nTesting Additional Endpoints:\n" . Colors::RESET;
echo str_repeat("─", 50) . "\n\n";

testEndpoint(
    "GET /services/api/recent (Recent Services)",
    'GET',
    '/services/api/recent'
);

testEndpoint(
    "GET /users/api/statistics (Users Statistics)",
    'GET',
    '/users/api/statistics'
);

testEndpoint(
    "GET /services/api/mechanics (Service Mechanics)",
    'GET',
    '/services/api/mechanics'
);

// ==================== SUMMARY ====================

echo Colors::BLUE . "\n╔════════════════════════════════════════════╗\n";
echo "║              Test Summary Report            ║\n";
echo "╚════════════════════════════════════════════╝\n" . Colors::RESET;

echo "\nTotal Tests: " . count($results) . "\n";
echo Colors::GREEN . "Passed: " . $passCount . Colors::RESET . "\n";
echo Colors::RED . "Failed: " . $failCount . Colors::RESET . "\n";

// Check if requirement is met
$required = array_slice($results, 0, 3); // First 3 are required
$requiredPassed = array_filter($required, fn($r) => $r['success'] && $r['isJson']);

echo "\n" . Colors::BLUE . "REQUIREMENT CHECK:\n" . Colors::RESET;
echo "─ At least 3 working endpoints: ";
if (count($requiredPassed) >= 3) {
    echo Colors::GREEN . "✓ YES (" . count($requiredPassed) . "/3)" . Colors::RESET . "\n";
} else {
    echo Colors::RED . "✗ NO (" . count($requiredPassed) . "/3)" . Colors::RESET . "\n";
}

echo "─ Standardized JSON format: ";
$allJson = array_filter($required, fn($r) => $r['isJson']);
if (count($allJson) >= 3) {
    echo Colors::GREEN . "✓ YES" . Colors::RESET . "\n";
} else {
    echo Colors::RED . "✗ NO" . Colors::RESET . "\n";
}

echo "─ Ready for mobile consumption: ";
if (count($requiredPassed) >= 3 && count($allJson) >= 3) {
    echo Colors::GREEN . "✓ YES" . Colors::RESET . "\n";
} else {
    echo Colors::RED . "✗ NO" . Colors::RESET . "\n";
}

// ==================== DETAILED RESULTS ====================

echo "\n" . Colors::BLUE . "Detailed Results:\n" . Colors::RESET;
echo str_repeat("═", 50) . "\n";

foreach ($results as $i => $result) {
    echo "\n[" . ($i + 1) . "] " . $result['name'] . "\n";
    echo "    Endpoint: " . Colors::YELLOW . $result['endpoint'] . Colors::RESET . "\n";
    echo "    Method: " . $result['method'] . "\n";
    echo "    Status: ";
    
    if ($result['success']) {
        echo Colors::GREEN . "✓ SUCCESS" . Colors::RESET . " (HTTP " . $result['httpCode'] . ")\n";
        if ($result['response']) {
            echo "    Response Keys: " . implode(', ', array_keys((array)$result['response'])) . "\n";
        }
    } else {
        echo Colors::RED . "✗ FAILED" . Colors::RESET;
        if (isset($result['error'])) {
            echo " - " . $result['error'];
        } elseif (isset($result['httpCode'])) {
            echo " (HTTP " . $result['httpCode'] . ")";
        }
        echo "\n";
    }
}

echo "\n" . str_repeat("═", 50) . "\n";

// Final status
echo "\n" . Colors::BLUE . "FINAL STATUS:\n" . Colors::RESET;
if (count($requiredPassed) >= 3) {
    echo Colors::GREEN . "✓ Your API meets all mobile consumption requirements!\n" . Colors::RESET;
    echo "  • 3+ working endpoints found\n";
    echo "  • All return standardized JSON\n";
    echo "  • Ready for mobile app integration\n";
} else {
    echo Colors::RED . "✗ Additional configuration needed\n" . Colors::RESET;
    echo "  • Server may not be running at: " . $baseUrl . "\n";
    echo "  • Check database connection\n";
    echo "  • Verify endpoints are properly configured\n";
}

echo "\n";
echo "Run this command to test:\n";
echo Colors::YELLOW . "php " . __FILE__ . Colors::RESET . "\n\n";

exit(count($requiredPassed) >= 3 ? 0 : 1);
?>
