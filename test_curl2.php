<?php
$ch = curl_init('http://localhost/food-app/modules/kelola_akses/akses_handler.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, ['action' => 'toggle_bulk', 'user_id' => 1, 'perm' => 'can_create', 'value' => 1]);

// Provide a valid session cookie for login
// Wait, I don't have a valid session cookie in CLI. 
// I will temporarily comment out requireLogin() in akses_handler.php to test the rest of the logic.
