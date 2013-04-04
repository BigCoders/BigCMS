<?php
	set_time_limit(600);
	
	function images_square($original_file,$destination_file = false,$square_size=96){
		// get width and height of original image
		$imagedata = getimagesize($original_file);
		$original_width = $imagedata[0];	
		$original_height = $imagedata[1];
		
		if($original_width > $original_height){$new_height = $square_size;$new_width = $new_height*($original_width/$original_height);}
		if($original_height > $original_width){$new_width = $square_size;$new_height = $new_width*($original_height/$original_width);}
		if($original_height == $original_width){$new_width = $square_size;$new_height = $square_size;}
		$new_width = round($new_width);$new_height = round($new_height);

		// load the image
		switch($imagedata['mime']){
			case 'image/gif':if(!($original_image = @imagecreatefromgif($original_file))){return false;};break;
			case 'image/jpeg':if(!($original_image = @imagecreatefromjpeg($original_file))){return false;};break;
			case 'image/png':if(!($original_image = @imagecreatefrompng($original_file))){return false;};break;
			default: return false;
		}
		
		$smaller_image = imagecreatetruecolor($new_width, $new_height);
		$white = imagecolorallocate($smaller_image,255,255,255);
		imagefill($smaller_image, 0, 0, $white);
		$square_image = imagecreatetruecolor($square_size, $square_size);
		$white = imagecolorallocate($square_image,255,255,255);
		imagefill($square_image, 0, 0, $white);
		imagecopyresampled($smaller_image,$original_image,0,0,0,0,$new_width,$new_height,$original_width,$original_height);
		
		if($new_width>$new_height){
			$difference = $new_width-$new_height;
			$half_difference = round($difference/2);
			imagecopyresampled($square_image,$smaller_image,-$half_difference+1,0,0,0,$square_size+$difference,$square_size,$new_width,$new_height);
		}
		if($new_height>$new_width){
			$difference = $new_height-$new_width;
			$half_difference = round($difference/2);
			imagecopyresampled($square_image,$smaller_image,0,-$half_difference+1,0,0,$square_size,$square_size+$difference,$new_width,$new_height);
		}
		if($new_height == $new_width){
			imagecopyresampled($square_image,$smaller_image,0,0,0,0,$square_size,$square_size,$new_width,$new_height);
		}
		

		// if no destination file was given then display a png		
		if($destination_file === false){imagepng($square_image,NULL,9);}
		$destEnd = strtolower(substr($destination_file,-4));

		// save the smaller image FILE if destination file given
		if($destEnd == '.jpg' || $destEnd == 'jpeg'){imagejpeg($square_image,$destination_file,100);}
		if(substr_count(strtolower($destination_file), ".gif")){
			imagegif($square_image,$destination_file);
		}
		if(substr_count(strtolower($destination_file), ".png")){
			imagepng($square_image,$destination_file,9);
		}

		imagedestroy($original_image);
		imagedestroy($smaller_image);
		imagedestroy($square_image);
	}
	
	function images_adjust($image, $width, $height, $adjust = 'max'){ 
		$wImage = imagesx($image);
		$hImage = imagesy($image);
	
		if($adjust == 'min') {if($wImage < $hImage){$r = $width/$wImage;}else{$r = $height/$hImage;}}
		else{if($wImage > $hImage){$r = $width/$wImage;}else{$r = $height/$hImage;}}
		
		$newWidth = $wImage*$r;
		$newHeight = $hImage*$r;
	
		$wAdjust = $hAdjust = $wDstAdjust = $hDstAdjust = 0;
		if($newWidth > $width){$wAdjust = (($newWidth-$width)/2)/$r;$hAdjust = (($newHeight-$height)/2)/$r;}
		else{$wDstAdjust = ($width-$newWidth)/2;$hDstAdjust = ($height-$newHeight)/2;}
		
		$newImage = imagecreatetruecolor($width, $height);
		$white = imagecolorallocate($newImage,255,255,255);
		imagefill($newImage, 0, 0, $white);
		imagecopyresampled($newImage,$image,$wDstAdjust,$hDstAdjust,$wAdjust,$hAdjust,$newWidth,$newHeight,$wImage,$hImage);
		
		return $newImage;
	}
	
	function images_resize($res,$maxWidth = 0,$maxHeight = 0,$adjust='max'){
	    $imgWidth = imagesx($res);
	    $imgHeight = imagesy($res);
	    if($imgWidth === false || $imgHeight === false){return false;}
	
	    $imgRatio = $imgWidth/$imgHeight;
	    if($maxWidth != 0 && $maxHeight != 0){$maxRatio = $maxWidth/$maxHeight;}
	
	    switch(true){
	        case ($maxWidth == 0):$maxWidth = $imgWidth * ($maxHeight/$imgHeight);break;
	        case ($maxHeight == 0):$maxHeight = $imgHeight * ($maxWidth/$imgWidth);break;
	        case ($adjust == 'max'):if($imgRatio>$maxRatio){$maxHeight = $imgHeight * ($maxWidth/$imgWidth);}else{$maxWidth = $imgWidth * ($maxHeight/$imgHeight);}break;
	        case ($adjust == 'min'):if($imgRatio>$maxRatio){$maxWidth = $imgWidth * ($maxHeight/$imgHeight);}else{$maxHeight = $imgHeight * ($maxWidth/$imgWidth);}break;
	        default:return false;
	    }
	
	    $new = imagecreatetruecolor($maxWidth,$maxHeight);
	    $white = imagecolorallocate($new,255,255,255);
	    imagefill($new, 0, 0, $white);
	    imagecopyresampled($new,$res,0,0,0,0,$maxWidth,$maxHeight,$imgWidth,$imgHeight);
	    return $new;
	}

	function image_getResource($path){
		$imgProp = getimagesize($path);
		if($imgProp === false){return false;}
		switch($imgProp['mime']){
	    	case 'image/gif':if(!($im = @imagecreatefromgif($path))){return false;};break;
			case 'image/jpeg':if(!($im = @imagecreatefromjpeg($path))){return false;};break;
			case 'image/png':if(!($im = @imagecreatefrompng($path))){return false;};break;
			default:return false;
		}
		return $im;
	}

	function imageResource_resize($res,$maxWidth = 0,$maxHeight = 0,$adjust='max'){	
		$imgWidth = imagesx($res);
		$imgHeight = imagesy($res);
		if($imgWidth === false || $imgHeight === false){return false;}

		$imgRatio = $imgWidth/$imgHeight;
		if($maxWidth != 0 && $maxHeight != 0){$maxRatio = $maxWidth/$maxHeight;}

		switch(true){
			case ($maxWidth == 0):$maxWidth = $imgWidth * ($maxHeight/$imgHeight);break;
			case ($maxHeight == 0):$maxHeight = $imgHeight * ($maxWidth/$imgWidth);break;
			case ($adjust == 'max'):if($imgRatio>$maxRatio){$maxHeight = $imgHeight * ($maxWidth/$imgWidth);}else{$maxWidth = $imgWidth * ($maxHeight/$imgHeight);}break;
			case ($adjust == 'min'):if($imgRatio>$maxRatio){$maxWidth = $imgWidth * ($maxHeight/$imgHeight);}else{$maxHeight = $imgHeight * ($maxWidth/$imgWidth);}break;
			default:return false;
		}

		$new = imagecreatetruecolor($maxWidth,$maxHeight);
		$white = imagecolorallocate($new,255,255,255);
		imagefill($new,0,0,$white);
		imagecopyresampled($new,$res,0,0,0,0,$maxWidth,$maxHeight,$imgWidth,$imgHeight);
		return $new;
	}

	function imageResource_crop($res,$width,$height){
		$imgWidth = imagesx($res);
		$imgHeight = imagesy($res);
		
		$xini = ($imgWidth<$width) ? floor(($imgWidth-$width)/2) : floor(($width-$imgWidth)/2);
		$yini = ($imgHeight<$height) ? floor(($imgHeight-$height)/2) : floor(($height-$imgHeight)/2);
		if(isset($GLOBALS['blueCommerce']['imageAdjust']) && $GLOBALS['blueCommerce']['imageAdjust'] == 'max'){
			$xini = ($imgWidth>$width) ? floor(($imgWidth-$width)/2) : floor(($width-$imgWidth)/2);
			$yini = ($imgHeight>$height) ? floor(($imgHeight-$height)/2) : floor(($height-$imgHeight)/2);
		}

		$image = imagecreatetruecolor($width,$height);
		$white = imagecolorallocate($image,255,255,255);
		imagefill($image,0,0,$white);
		imagecopy($image,$res,$xini,$yini,0,0,$imgWidth,$imgHeight);
		return $image;
	}
?>
