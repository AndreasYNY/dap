<?php

// php code to search data in mysql database and set it in input text

if(isset($_POST['search']))
{
    // id to search
    $id = $_POST['id'];

    // connect to mysql
    $connect = mysqli_connect("localhost", "root", "ochidarmaputra1290","ripple"); 
    // mysql search query
    $query = "SELECT `beatmap_id`, `song_name`, `beatmap_md5` FROM `beatmaps` WHERE `beatmapset_id` = $id OR `beatmap_id` = $id LIMIT 1";
    $result = mysqli_query($connect, $query);
    // if id exist
    // show data in inputs
    if(mysqli_num_rows($result) > 0)
    {
      while ($row = mysqli_fetch_array($result))
      {
        $bsid = $row['beatmap_id'];
        $songname = $row['song_name'];
        $beatmapmd5 = $row['beatmap_md5'];
      }
    }
    // if the id not exist
    // show a message and clear inputs
    else {
        echo "Undifined ID";
            $bsid = "";
            $songname = "";
            $beatmapmd5 = "";
    }
    mysqli_free_result($result);
    mysqli_close($connect);
}

// in the first time inputs are empty
else{
    $bsid = "";
    $songname = "";
    $beatmapmd5 = "";
}


?>

<!DOCTYPE html>

<html>
    <head>
        <title> FIND DATA </title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
	<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js" integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous"></script>
    </head>
    <body>
    <form action="index.php" method="post">
	<br><br>
	INPUT ID:
	<input type="text" name="id"><br><br>
        BEATMAP ID:
	<?php echo $bsid;?><br><br>
 	SONG NAME:
	<?php echo $songname;?><br><br>
        BEATMAP MD5:
	<input type="text" name="beatmapmd5" value="<?php echo $beatmapmd5;?>"><br><br>
        <input type="submit" name="search" value="Find">
    </form>
    </body>
</html>
