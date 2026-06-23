
<?php

session_start();

include("include/config.php");

$conn = getDBConnection();


// COUNTS

$total = $conn->query("
SELECT COUNT(*) total FROM tbl_raiseissue
")->fetch_assoc()['total'];


$pending = $conn->query("
SELECT COUNT(*) total 
FROM tbl_raiseissue
WHERE status IN('Pending','Received')
")->fetch_assoc()['total'];


$resolved = $conn->query("
SELECT COUNT(*) total
FROM tbl_raiseissue
WHERE status='Resolved'
")->fetch_assoc()['total'];


$transfer = $conn->query("
SELECT COUNT(*) total
FROM tbl_raiseissue
WHERE status='Transferred'
")->fetch_assoc()['total'];




// DEPARTMENT REPORT

$deptQuery=$conn->query("

SELECT

department,
department_head,

COUNT(*) total,

SUM(status IN('Pending','Received')) pending,

SUM(status='Resolved') resolved,

SUM(status='Transferred') transfer


FROM tbl_raiseissue

GROUP BY department,department_head

ORDER BY total DESC

");


$departments=[];


while($row=$deptQuery->fetch_assoc())
{
    $departments[]=$row;
}





// CHART DATA

$chart=$conn->query("

SELECT department,COUNT(*) total

FROM tbl_raiseissue

GROUP BY department

");


$deptName=[];
$deptCount=[];


while($row=$chart->fetch_assoc())
{

$deptName[]=$row['department'];

$deptCount[]=$row['total'];

}






// ISSUE DATA

$issues=$conn->query("

SELECT *

FROM tbl_raiseissue

ORDER BY id DESC

");

?>

<!DOCTYPE html>

<html>

<head>

<title>CEO Dashboard</title>


<link rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">


<link rel="stylesheet"
href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">


<link rel="stylesheet"
href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">


<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>


<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>


<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>


<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>


<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>


<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>



<style>


body{

background:#eef2f7;
font-family:Arial;

}



.header{

background:linear-gradient(135deg,#1e3c72,#2a5298);
color:white;

padding:30px;

border-radius:20px;

box-shadow:0 10px 25px #aaa;

}




.stat-card{

padding:25px;

border-radius:20px;

color:white;

box-shadow:0 10px 25px #ccc;

transition:.3s;

}



.stat-card:hover{

transform:translateY(-5px);

}



.blue{

background:linear-gradient(135deg,#2563eb,#1d4ed8);

}



.orange{

background:linear-gradient(135deg,#f59e0b,#d97706);

}



.green{

background:linear-gradient(135deg,#16a34a,#15803d);

}



.purple{

background:linear-gradient(135deg,#7c3aed,#5b21b6);

}




.card-box{


background:white;

padding:25px;

border-radius:20px;

box-shadow:0 10px 25px #ddd;


}



/* CHART BOX SAME HEIGHT */

.chart-box{


background:white;

padding:25px;

border-radius:20px;

box-shadow:0 10px 25px #ddd;


height:520px;


display:flex;

flex-direction:column;


}



.chart-area{


flex:1;

position:relative;


}



.chart-area canvas{


height:420px !important;

width:100% !important;


}




</style>


</head>



<body>


<div class="container-fluid p-4">



<div class="header">


<h2>
CEO Issue Monitoring Dashboard
</h2>


<p>
Department wise complaint tracking & analysis
</p>


</div>







<div class="row mt-4">


<div class="col-md-3">

<div class="stat-card blue">


<h5>Total Issues</h5>

<h1><?=$total?></h1>


</div>

</div>



<div class="col-md-3">


<div class="stat-card orange">


<h5>Pending</h5>

<h1><?=$pending?></h1>


</div>


</div>




<div class="col-md-3">


<div class="stat-card green">


<h5>Resolved</h5>

<h1><?=$resolved?></h1>


</div>


</div>





<div class="col-md-3">


<div class="stat-card purple">


<h5>Transferred</h5>

<h1><?=$transfer?></h1>


</div>


</div>


</div>









<!-- CHART SECTION -->


<div class="row mt-4">



<div class="col-md-6">


<div class="chart-box">


<h4>
Department Analysis
</h4>



<div class="chart-area">

<canvas id="deptChart"></canvas>

</div>



</div>


</div>





<div class="col-md-6">


<div class="chart-box">


<h4>
Status Analysis
</h4>



<div class="chart-area">


<canvas id="statusChart"></canvas>


</div>



</div>


</div>


</div>










<!-- DEPARTMENT TABLE -->


<div class="card-box mt-4">


<h3>
Department Performance
</h3>



<table id="deptTable" class="table table-bordered">


<thead>

<tr>

<th>Department</th>

<th>Head</th>

<th>Total</th>

<th>Pending</th>

<th>Resolved</th>

<th>Transferred</th>


</tr>


</thead>


<tbody>



<?php foreach($departments as $d){ ?>


<tr>


<td><?=$d['department']?></td>

<td><?=$d['department_head']?></td>

<td><?=$d['total']?></td>

<td><?=$d['pending']?></td>

<td><?=$d['resolved']?></td>

<td><?=$d['transfer']?></td>


</tr>


<?php } ?>


</tbody>


</table>


</div>









<div class="card-box mt-4">


<h3>
All Issues
</h3>



<table id="issueTable" class="table table-striped">


<thead>

<tr>

<th>No</th>

<th>Date</th>

<th>Department</th>

<th>Head</th>

<th>Description</th>

<th>Status</th>

<th>Transfer To</th>


</tr>


</thead>


<tbody>


<?php while($i=$issues->fetch_assoc()){ ?>


<tr>


<td><?=$i['issue_number']?></td>


<td><?=$i['created_at']?></td>


<td><?=$i['department']?></td>


<td><?=$i['department_head']?></td>


<td><?=$i['description']?></td>



<td>


<?php


if($i['status']=="Resolved")

echo "<span class='badge bg-success'>Resolved</span>";


elseif($i['status']=="Transferred")

echo "<span class='badge bg-primary'>Transferred</span>";


else

echo "<span class='badge bg-warning'>".$i['status']."</span>";


?>


</td>



<td><?=$i['transfer_to']?></td>


</tr>



<?php } ?>


</tbody>


</table>


</div>




</div>







<script>


$(document).ready(function(){


$('#deptTable,#issueTable').DataTable({

dom:'Bfrtip',

buttons:[

'excel',
'csv',
'print'

],

pageLength:10


});


});





new Chart(

document.getElementById('deptChart'),

{


type:'pie',


data:{


labels:

<?=json_encode($deptName,JSON_UNESCAPED_UNICODE)?>,


datasets:[{

data:

<?=json_encode($deptCount)?>

}]


},


options:{

responsive:true,

maintainAspectRatio:false


}


});







new Chart(

document.getElementById('statusChart'),

{


type:'bar',


data:{


labels:[

'Total',
'Pending',
'Resolved',
'Transferred'

],


datasets:[{

label:'Issues',

data:[

<?=$total?>,

<?=$pending?>,

<?=$resolved?>,

<?=$transfer?>

]


}]


},


options:{


responsive:true,

maintainAspectRatio:false


}


});



</script>




</body>

</html>