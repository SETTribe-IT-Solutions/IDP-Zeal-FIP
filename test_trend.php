<?php
require 'include/config.php';

$query = "
SELECT 
    department,
    DATE_FORMAT(created_at, '%Y-%m') as month_year,
    SUM(status IN('Pending','Received')) as pending,
    SUM(status='Resolved') as resolved,
    COUNT(*) as total
FROM tbl_raiseissue
WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
GROUP BY department, month_year
ORDER BY month_year ASC;
";
$res = $conn->query($query);
$data = [];
while($row = $res->fetch_assoc()) {
    $data[] = $row;
}
echo json_encode($data, JSON_PRETTY_PRINT);
?>
