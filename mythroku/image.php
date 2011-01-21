<?php

   //get the local info from the settings file
   require_once('./settings.php');
   include('resizeimage.php');
 
   if (isset($_GET['image'])) {
     
     $image = new SimpleImage();
     $image->load($mythtvdata . "/video_covers/" . $_GET['image']);
   	if ($RokuDisplayType == 'HD' ) {
   			$image->resizeToWidth(250);
		} else {
			$image->resizeToWidth(150);
		}
   	$image->output();
   }

?>
