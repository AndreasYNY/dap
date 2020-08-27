<?php 
require_once'koneksi.php';
?>
<!DOCTYPE html>
<html>
<head>
 <title>Beatmap Score Finder</title>
 <!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous">
</head>
<body>
<div class="container">
<div class="card">
  <div class="card-header bg-primary text-white">
    Beatmap Finder and Delete
  </div>
  <div class="card-body">
  <form class="form-inline" method="post">
  <div class="form-group mx-sm-3 mb-2">
    <input type="text" name="nt" class="form-control" placeholder="id">
  </div>
  <button type="submit" name= "submit" class="btn btn-primary mb-2">Submit</button>
  </form>
  </div>
</div>

<?php
if (ISSET($_POST['submit']))
{
 $cari = $_POST['nt'];
 $query2 = "SELECT * FROM scores WHERE beatmap_md5 LIKE '%$cari%' ORDER BY score DESC"; 
 $sql = mysqli_query($conn, $query2);
 $strTbl = "";
 $strTbl .= "<table class='table table-bordered table-hover'>";
 $strTbl .= "<tr>";
 $strTbl .= "<th>No ID</th>";
 $strTbl .= "<th>Beatmap MD5</th>";
 $strTbl .= "<th>User ID</th>";
 $strTbl .= "<th>Score</th>";
 $strTbl .= "<th>Mode</th>";
 $strTbl .= "<th>Option</th>";
 $strTbl .= "</tr>";
 // variable nomor urut
 $nomor = 1;
 while ($r = mysqli_fetch_array($sql)){
 $strTbl .= "<tr>";
 $strTbl .= "<td>". $r['id'] ."</td>";
 $strTbl .= "<td>". $r['beatmap_md5'] ."</td>";
 $strTbl .= "<td>". $r['userid'] ."</td>";
 $strTbl .= "<td>". number_format($r['score']) ."</td>";
 $strTbl .= "<td>". $r['play_mode'] ."</td>";
 $strTbl .= "<td><a class='btn btn-danger' href='delbeatmap.php?id=".$r['id']."' role='button'>Hapus</a></td>";
 $strTbl .= "</tr>";
 $nomor++;
 }
 $strTbl .= "</table>";
 print($strTbl);
}
?>
</div>
</body>
</html>
