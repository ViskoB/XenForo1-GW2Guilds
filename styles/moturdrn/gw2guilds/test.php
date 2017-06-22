<?php

$imageURL = "http://guilds.gw2w2w.com/guilds/midnight mayhem/128.svg";

if(get_http_response_code($imageURL) == "404")
{
	$filename = 'unknown_guild.png';

	// Content type
	header('Content-Type: image/png');

	// Get new sizes
	list($width, $height) = getimagesize($filename);
	$newwidth = 128;
	$newheight = 128;

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
		
$im = new Imagick();
$svg = file_get_contents($imageURL);
$svg = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>'.$svg;
$im->setBackgroundColor(new ImagickPixel('transparent'));
$im->readImageBlob($svg);

$im->setImageFormat("png32");

$im->writeImage('midnight-mayhem.png');

$im->clear();
$im->destroy();

exit();

function get_http_response_code($url) 
{
	$headers = get_headers($url);
	return substr($headers[0], 9, 3);
}