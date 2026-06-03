<?php
require 'c:/laragon/www/food-app/db.php';
$conn->query("ALTER TABLE branches ADD COLUMN map_url TEXT DEFAULT NULL AFTER alamat");
echo "Column map_url added successfully.";
