<?php
require_once __DIR__ . '/include/config.php';
$c = db_connect();
$r = mysqli_query($c, "SELECT username, role, system_role, password FROM users WHERE role LIKE '%hod%' OR system_role LIKE '%hod%' OR username LIKE '%hod%'");
while($row = mysqli_fetch_assoc($r)) {
    print_r($row);
}
