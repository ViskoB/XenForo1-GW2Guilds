<?php

	$guild_name = "";
	$size = 128;
	
	if($_GET['name'])
	{
		$guild_name = $_GET['name'];
	}
	
	if($_GET['size'] && is_numeric($_GET['size']))
	{
		$size = $_GET['size'];
	}
	
	if($guild_name != "")
	{
		$imageURL = "http://guilds.gw2w2w.com/guilds/{$guild_name}/{$size}.svg";
		
		if(get_http_response_code($imageURL) == "404")
		{
			show_unknown_image($size);
		}
		else
		{
			$image = file_get_contents($imageURL);
			if($image == 'Guild Not Found')
			{
				show_unknown_image($size);
			}
			else
			{
				header('Content-Type: image/svg+xml');
				echo file_get_contents($imageURL);
			}
		}
	}
	else
	{
		show_unknown_image($size);
	}
	
	function show_unknown_image($imageSize)
	{
		$filename = 'unknown_guild.png';

		// Content type
		header('Content-Type: image/png');

		// Get new sizes
		list($width, $height) = getimagesize($filename);
		$newwidth = $imageSize;
		$newheight = $imageSize;

		// Load
		$thumb = imagecreatetruecolor($newwidth, $newheight);
		$source = imagecreatefrompng($filename);

		// Resize
		imagealphablending($thumb, false);
		imagesavealpha($thumb,true);
		imagecopyresized($thumb, $source, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);

		// Output and free memory
		//the resized image will be 400x300
		imagepng($thumb);
		imagedestroy($thumb);
		exit();
	}
	
	function get_http_response_code($url) 
	{
		$headers = get_headers($url);
		return substr($headers[0], 9, 3);
	}