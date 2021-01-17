<?php

$server="localhost";         /*local server localhost*/
$user="root";               /*User server mysql anda*/
$pass="ga tau mungkin loe tau?";                  /*password server mysql anda*/
$database="ripple";    /*Nama database yang dikoneksikan, sesuaikan dengan nama database anda*/

//koneksi ke server , jika gagal muncul notifikasi Error Conection Network
$conect=mysqli_connect($server,$user,$pass,$database) or die('Error Connection Network');

/* Jika anda ingin mengecek koneksi berhasil atau tidak bisa gunakan script ini*/
/* Maka jika di panggil koneksi.php akan muncul Text Koneksi Berhasil, Jika Gagal muncul Database not found*/
/*
if($conect){
    echo "Koneksi Berhasil";
}
*/
?>
