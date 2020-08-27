 <?php

  $con = mysqli_connect("localhost","root","ochidarmaputra1290","ripple");
  
  $output = '';

  if(isset($_POST['search'])) {
    $search = $_POST['search'];
    $search = preg_replace("#[^0-9a-z]i#","", $search);

    $query = mysqli_query($con, "SELECT * FROM beatmaps WHERE beatmapset_id LIKE '%$search%'") or die ("Could not search");
    $count = mysqli_num_rows($query);
    
    if($count == 0){
      $output = "There was no search results!";

    }else{

      while ($row = mysqli_fetch_array($query)) {

        $town = $row ['beatmapset_id'];
        $street = $row ['beatmap_md5'];
        $bedrooms = $row ['beeatmap_id'];
        $bathroom = $row ['song_name'];

        $output .='<div> '.$town.''.$street.''.$bedrooms.''.$bathrooms.'</div>';

      }

    }
  }

  ?>
