<?php
// =============================================
// Kleo.nl – proxy.php
// Veilige doorgang naar OpenAI API
// =============================================

header('Content-Type: application/json; charset=utf-8');

// --- Configuratie ---
$allowed_origin = 'https://kleo.nl'; // alleen jouw domein
$rate_limit_window = 10;  // tijdsframe in seconden
$max_requests = 20;       // max aantal verzoeken per window

// --- CORS-beveiliging ---
if (isset($_SERVER['HTTP_ORIGIN'])) {
  if ($_SERVER['HTTP_ORIGIN'] !== $allowed_origin) {
    http_response_code(403);
    echo json_encode(['error' => 'Domein niet toegestaan.']);
    exit;
  }
  header("Access-Control-Allow-Origin: $allowed_origin");
  header("Access-Control-Allow-Methods: POST, OPTIONS");
  header("Access-Control-Allow-Headers: Content-Type");
}
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit;
}

// --- API-sleutel ophalen uit omgeving (.htaccess) ---
$apiKey = getenv('OPENAI_API_KEY');
if (!$apiKey) {
  http_response_code(500);
  echo json_encode(['error' => 'API-sleutel niet gevonden']);
  exit;
}

// --- Rate limiting ---
$ip = $_SERVER['REMOTE_ADDR'];
$logfile = __DIR__ . '/ratelimit.json';
$now = time();
$requests = [];

if (file_exists($logfile)) {
  $requests = json_decode(file_get_contents($logfile), true) ?? [];
}

// oude requests opruimen
$requests[$ip] = array_filter(($requests[$ip] ?? []), fn($t) => $t > $now - $rate_limit_window);

// te veel verzoeken?
if (count($requests[$ip]) >= $max_requests) {
  http_response_code(429);
  echo json_encode(['error' => 'Te veel verzoeken, probeer later opnieuw.']);
  exit;
}

// nieuw verzoek registreren
$requests[$ip][] = $now;
file_put_contents($logfile, json_encode($requests));

// --- Inkomende data ---
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
  http_response_code(400);
  echo json_encode(['error' => 'Geen geldige JSON ontvangen']);
  exit;
}

// --- OpenAI-aanvraag ---
$ch = curl_init('https://api.openai.com/v1/chat/completions');
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_HTTPHEADER => [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $apiKey
  ],
  CURLOPT_POST => true,
  CURLOPT_POSTFIELDS => json_encode($input)
]);

$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// --- Respons doorgeven ---
http_response_code($httpcode ?: 200);
echo $response ?: json_encode(['error' => 'Geen antwoord van OpenAI.']);
