<?php
// =============================================
// Kleo.nl – proxy.php (Verbeterde versie)
// Veilige doorgang naar OpenAI API met chat history
// =============================================

header('Content-Type: application/json; charset=utf-8');

// --- Configuratie ---
$allowed_origin = 'https://kleo.nl';
$rate_limit_window = 60;  // 1 minuut
$max_requests = 20;       // max 20 verzoeken per minuut

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
if (!$apiKey || $apiKey === 'sk-PASTE_HIER_JE_OPENAI_KEY') {
  http_response_code(500);
  echo json_encode(['error' => 'API-sleutel niet correct geconfigureerd']);
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
  echo json_encode(['error' => 'Te veel verzoeken. Maximaal ' . $max_requests . ' per minuut.']);
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

// Validatie: messages array moet bestaan
if (!isset($input['messages']) || !is_array($input['messages'])) {
  http_response_code(400);
  echo json_encode(['error' => 'Messages array ontbreekt']);
  exit;
}

// Zorg dat model is ingesteld
if (!isset($input['model'])) {
  $input['model'] = 'gpt-4o-mini';
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
  CURLOPT_POSTFIELDS => json_encode($input),
  CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

// --- Error handling ---
if ($curl_error) {
  http_response_code(500);
  echo json_encode(['error' => 'Verbindingsfout: ' . $curl_error]);
  exit;
}

// --- Respons doorgeven ---
http_response_code($httpcode ?: 200);
echo $response ?: json_encode(['error' => 'Geen antwoord van OpenAI.']);
