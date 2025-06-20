<?php
/****
 * whoami - A simple PHP microservice to inspect client network info
 * 
 * @author Johan VIVIEN
 * @contact hotfirenet@gmail.com
 * @license MIT
 * @date 2025-06-20
 * @version 1.0.0
 * @version 1.0.0
 */
define('WHOAMI_VERSION', '1.0.0');

$token = getenv('API_TOKEN');
if (!$token) {
    $envFile = __DIR__ . '/.env';
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            list($name, $value) = explode('=', $line, 2);
            if (trim($name) === 'API_TOKEN') {
                $token = trim($value);
            }
        }
    } else {
        $generatedToken = bin2hex(random_bytes(16));
        file_put_contents($envFile, "API_TOKEN=$generatedToken\n");
        $token = $generatedToken;
        http_response_code(201);
        header('Content-Type: application/json');
        echo json_encode(['message' => 'Token generated and saved in .env', 'token' => $generatedToken], JSON_PRETTY_PRINT);
        exit;
    }
}
define('API_TOKEN', $token ?? '');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (!isset($_GET['token']) || $_GET['token'] !== API_TOKEN) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

$response = [
    'ip' => $ip,
    'ip_type' => filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? 'IPv6' :
                 (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? 'IPv4' : 'unknown'),
    'protocol' => $_SERVER['SERVER_PROTOCOL'] ?? 'unknown',
    'port' => $_SERVER['REMOTE_PORT'] ?? 'unknown',
    'via_proxy' => isset($_SERVER['HTTP_X_FORWARDED_FOR']) || isset($_SERVER['HTTP_VIA']),
    'forwarded_for' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '',
    'via' => $_SERVER['HTTP_VIA'] ?? '',
    'host' => $_SERVER['HTTP_HOST'] ?? '',
    'server_name' => $_SERVER['SERVER_NAME'] ?? '',
    'server_addr' => $_SERVER['SERVER_ADDR'] ?? '',
    'version' => WHOAMI_VERSION,
    'headers' => [
        'User-Agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'X-Forwarded-For' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '',
        'Accept' => $_SERVER['HTTP_ACCEPT'] ?? '',
        'Accept-Language' => $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
    ],
    'timestamp' => date('c')
];

header('Content-Type: application/json');
echo json_encode($response, JSON_PRETTY_PRINT);