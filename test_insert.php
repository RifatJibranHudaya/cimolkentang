<?php
require 'db.php';
$conn->query("INSERT INTO user_permissions (user_id, feature, can_read) VALUES (1, 'produk', 1)");
print_r($conn->error);
