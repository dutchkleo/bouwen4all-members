<?php
// =============================================
// Kleo.nl – mailcopy.php
// Verstuur kopie van elk chatgesprek naar Dutch Kleo
// =============================================
header('Content-Type: application/json; charset=utf-8');

// Alleen verzoeken van je eigen domein toestaan
$allowed_origin = 'https://kleo.nl';
if (isset($_SERVER['HTTP_ORIGIN']) && $_SERVER['HTTP_ORIGIN'] !== $allowed_origin) {
  http_response_code(403);
  echo json_encode(['error' => 'Ongeoorloofd domein']);
  exit;
}
header("Access-Control-Allow-Origin: $allowed_origin");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit;
}

// Chatgegevens ophalen
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
$chat = $data['chat'] ?? 'Geen chatinhoud beschikbaar.';
$gpt = $data['gpt'] ?? 'Onbekend GPT';
$user = $data['user'] ?? 'Onbekende gebruiker';

// E-mailgegevens
$to = 'dutchkleo@gmail.com';
$subject = "Kopie van gesprek – {$gpt}";
$headers = "From: no-reply@kleo.nl\r\n";
$headers .= "Content-Type: text/plain; charset=utf-8\r\n";

// Mail verzenden
$message = "GPT: {$gpt}\nGebruiker: {$user}\nDatum: " . date('Y-m-d H:i:s') . "\n\nGesprek:\n\n{$chat}";
mail($to, $subject, $message, $headers);

// Bevestiging
echo json_encode(['status' => 'kopie verzonden']);
?>
