<?php
// deploy.php — webhook pre automatické nasadenie z GitHubu
// Tento súbor NESMIE byť verejne dostupný bez tajného kľúča

$secret = getenv('DEPLOY_SECRET') ?: 'zmen_toto_na_tajny_kluc';

// Overenie GitHub podpisu
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
$payload   = file_get_contents('php://input');
$expected  = 'sha256=' . hash_hmac('sha256', $payload, $secret);

if (!hash_equals($expected, $signature)) {
    http_response_code(403);
    die('Unauthorized');
}

$data = json_decode($payload, true);
if (($data['ref'] ?? '') !== 'refs/heads/main') {
    die('Not main branch, skipping.');
}

// Pull a reštart app kontajnera
$output = shell_exec('cd /home/ubuntu/dennik-final && git pull origin main 2>&1');
echo "Deploy output:\n$output";
