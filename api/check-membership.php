<?php
// =============================================
// check-membership.php
// Controleert of gebruiker actief lid is
// =============================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://kleo.nl');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit;
}

session_start();

$response = ['active' => false];

// Methode 1: Check via sessie (als gebruiker ingelogd is via leden.kleo.nl)
if (isset($_SESSION['member']) && $_SESSION['member'] === true) {
  $response['active'] = true;
}

// Methode 2: Check via JWT token (optioneel, voor API-based auth)
// if (isset($_COOKIE['kleo_token'])) {
//   $token = $_COOKIE['kleo_token'];
//   // Valideer token hier
//   // response['active'] = true; // TIJDELIJK voor testen
}

// Methode 3: Check via database (als je een users tabel hebt)
// if (isset($_SESSION['user_id'])) {
//   $userId = $_SESSION['user_id'];
//   // Query database om membership status te checken
//   // $response['active'] = checkDatabaseMembership($userId);
// }

// Voor development: tijdelijk altijd true voor testen
// VERWIJDER DIT IN PRODUCTIE!
// $response['active'] = true;

echo json_encode($response);
