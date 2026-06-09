<?php
require 'db.php';
$res = $conn->query("SELECT * FROM user_permissions");
print_r($res->fetch_all(MYSQLI_ASSOC));
