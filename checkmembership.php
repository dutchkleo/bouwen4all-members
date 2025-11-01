<?php
// check-membership.php
header('Content-Type: application/json');

// Simuleer een eenvoudige lidmaatschapscontrole.
// In een echte toepassing controleer je een sessie of token vanuit leden.kleo.nl.
session_start();
$response = [ 'active' => false ];

if (isset($_SESSION['member']) && $_SESSION['member'] === true) {
  $response['active'] = true;
}

echo json_encode($response);
?>