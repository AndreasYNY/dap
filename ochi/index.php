<?php
//panggil file koneksi.php yang sudah anda buat
include "koneksi.php";
?>
<!doctype html public "-//W3C//DTD HTML 4.0 //EN">
<html>
<head>
       <title>DAFTAR LOG</title>
</head>
<body>
<h1 align="center"> Daftar Users</h1>
    <table border="1" width="600px" align="center">
       <thead>
       <tr>
           <th>Waktu</th>
           <th>Nickname</th>
           <th>Alasan</th>
	   <th>Privileges</th>
       </tr>
       </thead>

       <tbody>
<?php
//ambil data dari tb_admin di database
$ambildata=mysqli_query($conect, "SELECT * FROM log order by time desc");
while($a=mysqli_fetch_array($ambildata)){
    ?>
       <tr>
     	   <td><?php echo $a['time'];?></td>
           <td><?php echo $a['nickname'];?></td>
           <td><?php echo $a['alasan'];?></td>
           <td><?php echo $a['privileges'];?></td>
       </tr>
<?php
}
?>
</tbody>

</table>

</body>
</html>
