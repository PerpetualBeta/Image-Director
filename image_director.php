<?php

define("VERSION", "1.18.5");

if ( ($_GET == NULL) && ($_POST == NULL) ) {
	echo 'Image Director v' . VERSION . ".\n" . 'Copyright 2009-' . date('Y', time()) . ', Jonathan M. Hollin (darkblue@sdf.lonestar.org)';
	exit;
}
if (
	(isset($_GET['versionOnly']) and $_GET['versionOnly']) or
	(isset($_POST['versionOnly']) and $_POST['versionOnly']) or
	(isset($_REQUEST['versionOnly']) and $_REQUEST['versionOnly'])
) {
	echo VERSION;
	exit;
}

/*
	VERSION HISTORY

	1.00: Initial release

	1.01: Minor bug fixes

	1.02: Minor bug fixes

	1.03:
		Program now saves processed images with the same MIME type as their corresponding source images. However, in cases where the 'ds' or 'asr' options are processed then the MIME type is automatically adjusted to PNG to accommodate the Alpha data (transparency).

	1.04: April, 2010
		The problem with 1.03 is that the 'ds' and 'asr' options result in enormous PNG files (GD Library seems to be very inefficient with regards to PNG file-sizes). More often than not, transparency is not really necessary on the processed images if we can ascertain the background colour that the images will be used with.
		Thus I have added a new parameter, 'bg', which accepts a standard HTML hexadecimal colour code and renders the 'ds' and 'asr' images against it. Images which have been processed with the 'ds' or 'asr' options AND which have a 'bg' declared are now saved as JPEG images. This has resulted in average file-size reductions of ~90%!

	1.05: 4th May, 2010
		Added code to crop source image from a user-specified start point (X/Y co-ordinates). Handled all the edge cases that I could anticipate.
			1: Cropping is intelligently handled even if the specified X/Y co-ordinates would put the crop outside of the dimensions of the source image.
			2: Cropping can be performed with or without thumbnailing and with or without zoom crop.
			3: Cropping is affected by the 'w' and 'h' parameters if they are set.
			4: If zoom crop ('zc') is not enabled, then the 'w' and/or 'h' parameters, if set, dictate the end co-ordinates of the crop. Thus if an X co-ordinate ('ccx') is supplied along with a 'w' value, then the resulting "crop-to" X position will be: ccx + w, the Y co-ordinate ('ccy') works in the same manner. I call this an "absolute crop."
			5: If zoom crop ('zc') is enabled, then only one of the 'w' or 'h' parameters, if set, will have an effect - the one relating to the largest dimension of the source image. I call this a "relative crop."
		Cropping code was added following a suggestion/request from Eric Kazda at Quantum Dynamix (http://quantumdynamix.net/). Thanks Eric.

	1.06: 4th May, 2010
		Bug fix.

	1.07: 29th June, 2010
		Minor revisions for PHP 5.3 compatibility.

	1.08: 9th July, 2010
		Added facility to apply anti-aliased rounded corners to images with user-defined corner radius and background colour.

	1.09: 12th July, 2010
		Added path/filename only output to provide for recursive operations directed by the supporting library: 'id_pipe.php'.

	1.10: 12th July, 2010
		Added facility to acquire images from a remote source (via http/ftp).

	1.11: 12th July, 2010
		Added local storage/caching of images acquired from a remote source.

	1.12: 13th July, 2010
		Added an option to refresh the locally stored version of a remote file.

	1.13: 5th August, 2010
		Bug fix.

	1.14: 7th September 2010
		Added option to output Image Director's version number.

	1.15: 10th November, 2010
		Fixed a couple of mathematical rounding bugs and fixed bug that meant that the Image Director failed when directed to render rounded-corners within a CSS sprite.

	1.16: 15th December, 2010
		Fixed a bug with the rounded corner renderer that prevented rounded corners from being created if the Image Director folder was not in the website's root directory.

	1.17: 20th December, 2010
		Added automatic server-port detection.

	1.18: 20th December, 2010
		Rounded corner rendering now performed internally as opposed to the rather expensive "piped process to an external resource" method we we using before - which didn't work on every host configuration anyway.

	1.18.1: 20th December, 2010
		Added switchable over-sampling, to produce anti-aliased corners.

	1.18.2: 20th December, 2010
		Added a facility for disabling the rounding of any corner(s).

	1.18.3: 21st December, 2010
		Added an image randomiser. Point the Image Director at a directory rather than an image and it will apply the requested transforms to a random image selected from the source directory (local directories only - won't work with a remote source).

	1.18.4: 21st December, 2010
		Revised the imaging mechanism to "prefer" to generate JPEG images if it can get away with it (ie: no alpha channel needed) as these are generally smaller in file-size when compared against their PNG counterparts.

	1.18.5: 21st December, 2010
		Added CRC32B checksum of Image Director source to image filenames prior to caching. Now the caching mechanism is sensitive to code changes within image_director.php.
*/

/*

	Program:
		Image Director: A library of routines for on-the-fly web imaging, by Jonathan M. Hollin (darkblue@sdf.lonestar.org).

	Copyright:
		Copyright 2009-2010 Jonathan M. Hollin
		Test Image, "Alba Dominguez 17", Copyright Vincent Boiteau/Studio.es (http://www.flickr.com/photos/2dogs_productions/375351116/). Used in accordance with the Creative Commons Attribution-Non-Commercial-Share Alike 2.0 Generic License.

	License:
		This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

		This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

		You should have received a copy of the GNU General Public License along with this program. If not, see: http://www.gnu.org/licenses/

	Incorporates:
		AI Grayscale algorithm from:
		http://www.ars-informatica.ca/article.php?article=18

		apple.com-style reflection code ("wet floor") from:
		http://reflection.corephp.co.uk/

		Dave Shea's CSS Sprite concept:
		http://www.alistapart.com/articles/sprites

		File handling code (including caching) and thumbnail generation from:
		http://code.google.com/p/timthumb/

		Rounded corner code from:
		http://it.toolbox.com/blogs/opensource-programming/rounded-corners-on-images-22705

	Parameters:
		src: 	Source image
		q:		Quality (default = 60, maximum = 100 (lower value = better quality but larger file-size))
		pas:		Experimental. Preserve the alpha channel of the source image.
				Can be used if the Image Director fails to work as expected with a transparent image. (0 (default) or 1)
		nocache:	Disables the internal cache look-up mechanism (cache revisions still take place though). Useful for debugging/testing.
				(0 (default) or 1)

		t:		Request thumbnail (0 or 1 (default))
		w:		Width (default = 100)
		h:		Height (default = 100)
		ccx:		Cropping start point, X co-ordinate (horizontal) (default = 0)
		ccy:		Cropping start point, Y co-ordinate (vertical) (default = 0)
		zc:		Zoom crop (0 (default) or 1)

		aig:		Request AI Grayscale conversion (0 (default) or 1).
				Note: Transparency (if any) will be lost. Works best with photos.
		aiga:	AI Grayscale blend algorithm (default 0.8)

		tint:	Request a tinted filter (0 (default) or 1).
				Note: Won't work as expected with transparent images.
				Works best with photos and images that have had the AI Grayscale applied.
		tintc:	Tint colour (6-digit RGB hexadecimal format, default is ff0000)
		tinta:	Tint alpha channel (x%: 0% will be completely opaque while 100% will be completely transparent, default is 70%)

		blur:	Request a blurred image (0 (default) or 1). Note: Transparency (if any) will be lost.
		bshift:	Gaussian iterations for blur (1-10: 10 = most blur but slower, default is 2)

		ds:		Apply a drop-shadow to the image (0 (default) or 1)
				Disabled if the apple.com-style reflection is requested.
		pb:		Photo border. Adds a white border around an image. (x: depth of border in pixels, default is 0)
				Only works on images that have the drop-shadow applied.

		asr:		Request an apple.com-style reflection (0 (default) or 1)
		asrh:	Height of the reflection (x% or x (pixels), default is 30%)
		asrfs:	Alpha fade start (x%, default is 20%)
		asrfe:	Alpha fade end (x%, default is 0%)

		csss:	Request a CSS sprite (0 (default) or 1)

		bg:		Apply a background colour. This is useful if using "asr" or "ds" options.
				If "bg" is defined then the transparency can be disabled and a JPEG file can be created rather than a much larger PNG one.

		cr:		Corner Radius, applies rounded corners to the image with a background colour specified in the "bg" parameter.
				(0 (default) or 1)
				Note: Rounding can be disabled for individual corners by sending the "no" argument to any combination of the following parameters: topleft, topright, bottomleft, bottomright (eg: "&topright=no&bottomright=no")
		arc:		Applies anti-aliasing to rounded corners, producing smoother curves, disable for faster corner rendering.
				(0 or 1 (default))

		rf:		The source image is a remote file (http/ftp), handle accordingly.
		rrf:		Refresh the local cached version of the remote file.

		fo:		Request path/filename only output for use with the supporting library: 'id_pipe.php', for recursive operations.

	Examples:
		HTML Code:
			<img src="image_director.php?src=/images/whatever.jpg" alt="" />
		Result:
			Source image is converted to 100x100 pixel thumbnail, distorted aspect ratio because we haven't requested the Zoom Crop algorithm.

		HTML Code:
			<img src="image_director.php?src=/images/whatever.jpg&amp;aig=1&amp;aiga=0.8&amp;q=80&amp;t=0" alt="" />
		Result:
			Source image is converted to weighted-average AI Grayscale at 80% quality.

		HTML Code:
			<img src="image_director.php?src=/images/whatever.jpg&amp;aig=1&amp;w=200&amp;h=200&amp;zc=1" alt="" />
		Result:
			Source image is converted to 200x200 pixel weighted-average AI Grayscale thumbnail with a correct aspect ratio as we've requested the Zoom Crop algorithm.

		HTML Code:
			<img src="image_director.php?src=/images/whatever.jpg&amp;aig=1&amp;w=200&amp;h=200&amp;zc=1&amp;ds=1&amp;pb=6&amp;tint=1&amp;tintc=5e2612" alt="" />
		Result:
			Source image is converted to 200x200 pixel weighted-average AI Grayscale thumbnail with a correct aspect ratio as we've requested the Zoom Crop algorithm. A sepia tint is applied. Then a white inner-border, 6-pixels deep, and a drop-shadow is applied to the result. For that old photographic print effect.

		HTML Code:
			<img src="image_director.php?src=/images/whatever.jpg&amp;aig=1&amp;w=300&amp;h=300&amp;zc=1&amp;csss=1" alt="" />
		Result:
			Source image is converted to 300x300 pixel CSS Sprite, AI Grayscale to colour, (thus the height of the resulting image will be 600 pixels) with a correct aspect ratio as we've requested the Zoom Crop algorithm.

		HTML Code:
			<img src="image_director.php?src=/images/whatever.jpg&amp;w=300&amp;h=300&amp;zc=1&amp;aig=1&amp;csss=1&amp;blur=1&amp;bshift=10" alt="" />
		Result:
			Source image is converted to 300x300 pixel CSS Sprite, AI Grayscale to colour, (thus the height of the resulting image will be 600 pixels) with a correct aspect ratio as we've requested the Zoom Crop algorithm. The grayscale image component will also be blurred, following 10 iterations through GD Library's guassian blur filter.

		HTML Code:
			<img src="image_director.php?src=/images/whatever.jpg&amp;w=300&amp;aig=1&amp;csss=1&amp;tint=1&amp;tintc=004080" alt="" />
		Result:
			Source image is converted to 300 pixel wide CSS Sprite, AI Grayscale to colour. The aspect ratio of the image is preserved because we haven't specified a height or requested a Zoom Crop. A blue tint (hex colour #004080, 70% transparency) is applied to the grayscale image component.

		HTML Code:
			<img src="image_director.php?src=/images/whatever.jpg&amp;t=0&amp;asr=1&amp;asrfs=60%&amp;asrh=30%" alt="" />
		Result:
			Image is rendered with an apple.com-style reflection. The reflection's starting density is 60% that of the original image (fading to 0% - complete transparency). The height of the reflection is 30% that of the original image. The original image is not resized since the "t=0" option has been specified.

		HTML Code:
			<img src="image_director.php?src=/images/whatever.jpg&amp;w=300&amp;aig=1&amp;tint=1&amp;tintc=004080&amp;blur=1&amp;bshift=4&amp;csss=1&amp;asr=1&amp;asrfs=60%&amp;asrh=30%" alt="" />
		Result:
			For this final example we've gone completely over-the-top and applied almost every adjustment possible. The image is first resized to 300 pixels wide (it retains a correct aspect ratio since we haven't specified a height or requested a Zoom Crop). The AI Grayscale is applied with it's default blending algorithm. Then a blue tint is applied (hex colour #004080, 70% transparency). Next we apply the blur filter, requesting 4 blur iterations. Then we request an apple.com-style reflection (with a reflection height 30% that of the original image and starting density 60% that of the original image). Finally we tell the Image Director that we want that whole shebang as a CSS Sprite!

			... and the Image Director doesn't bat an eyelid! :-)

*/

// Check the prerequisites:
// Start with a PHP version check...
if (version_compare('4.3.2', phpversion()) == 1) {
	displayError("This version of PHP is not fully supported. You need 4.3.2 or above.");
}
// Check that the GD Library is available...
if (extension_loaded('gd') == false && !dl('gd.so')) {
	displayError("You are missing the GD Library extension for PHP, unable to continue.");
}
// GD Library version check...
$gd_info = gd_info();
if ($gd_info['PNG Support'] == false) {
	displayError("This version of the GD extension cannot output PNG images.");
}
// Check to see if the GD function(s) exists...
if (!function_exists('imageColorsForIndex')) {
	displayError("GD Library Error - imageColorsForIndex does not exist!");
}
if (!function_exists('ImageColorAt')) {
	displayError("GD Library Error - ImageColorAt does not exist!");
}
if (!function_exists('imagesetpixel')) {
	displayError("GD Library Error - imagesetpixel does not exist!");
}
if (!function_exists('imagecolorallocate')) {
	displayError("GD Library Error - imagecolorallocate does not exist!");
}
if (!function_exists('imagecreatetruecolor')) {
	displayError("GD Library Error: imagecreatetruecolor does not exist!");
}

// Cache Control (these figures can be tuned as required)...
define("CACHE_SIZE", 1000);	// Number of files to store before clearing cache
define("CACHE_CLEAR", 10);	// Maximum number of files to delete on each cache clear

// Sort out the image source...
$src = get_request("src", "");
if ($src == "" || strlen($src) <= 3) {
	displayError("No source image specified. Can't continue, so exiting!");
}

// Is the source image on a remote server?
$rf = preg_replace("/[^0-1]/", "", get_request("rf", '0'));
$rrf	= preg_replace("/[^0-1]/", "", get_request("rrf", '0'));
if ($rf == 1) {
	// Set up the file-space...
	$rfc_dir = './remote_file_capture';
	if (!file_exists($rfc_dir)) {
		// Give 777 permissions so that we can programmatically overwrite files...
		mkdir($rfc_dir);
		chmod($rfc_dir, 0777);
	}

	// Have we already retrieved this image?
	$check_asset = $rfc_dir . '/' . md5($src) . '.';
	$asset_src = '';
	if (file_exists($check_asset . 'jpg')) {
		$asset_src = $check_asset . 'jpg';
	}
	if (file_exists($check_asset . 'gif')) {
		$asset_src = $check_asset . 'gif';
	}
	if (file_exists($check_asset . 'png')) {
		$asset_src = $check_asset . 'png';
	}
	if ($asset_src && !$rrf) {
		// We have the image...
		$src = $asset_src;
		unset ($asset_src);
	} else {
		// We don't have the image. Let's get it...
		// Open the file using 'fopen', which supports remote URLs...
		$input = fopen("$src", 'rb');
		$image_data = stream_get_contents($input);
		fclose($input);

		// Generate a file name for the captured image...
		$mime_type = getimagesize($src);
		if ( stristr($mime_type['mime'], 'gif') ) {
			$imageExt = 'gif';
		} elseif ( stristr($mime_type['mime'], 'jpeg') ) {
			$imageExt = 'jpg';
		} elseif ( stristr($mime_type['mime'], 'png') ) {
			$imageExt = 'png';
		}
		$src = $rfc_dir . '/' . md5($src) . '.' . $imageExt;

		// Write the contents to a dummy file...
		$output = fopen($src, 'wb');
		fwrite($output, $image_data);
		fclose($output);
	}
}

// Clean parameters before use...
$src = cleanSource($src);

// Is the source a file or a directory?
if (file_exists($src)) {
	if (is_dir($src)) {
		// It's a directory, so let's get a random image file for processing...
		$imglist = '';
		mt_srand((double)microtime()*1000);
		$imgs = dir($src);
		while ($file = $imgs->read()) {
			if (preg_match('/\.gif$/', $file) || preg_match('/\.jpg$/', $file) || preg_match('/\.png$/', $file)) {
				$imglist .= $file .  '|';
			}
		}
		closedir($imgs->handle);
		$imglist = explode('|', $imglist);
		$no = sizeof($imglist) - 2;
		$random = mt_rand(0, $no);
		$src = rtrim($src, '/');
		$src = $src . '/' . $imglist[$random];
	}
}

// Last modified time (for caching)...
$lastModified = filemtime($src);

// Get parameters...
$t			= preg_replace("/[^0-1]/", "", get_request("t", 1));
$zoom_crop	= preg_replace("/[^0-1]/", "", get_request("zc", 0));
$aig			= preg_replace("/[^0-1]/", "", get_request("aig", 0));
$tint		= preg_replace("/[^0-1]/", "", get_request("tint", 0));
$blur		= preg_replace("/[^0-1]/", "", get_request("blur", 0));
$asr			= preg_replace("/[^0-1]/", "", get_request("asr", 0));
$csss		= preg_replace("/[^0-1]/", "", get_request("csss", 0));
$ds			= preg_replace("/[^0-1]/", "", get_request("ds", 0));
$pas			= preg_replace("/[^0-1]/", "", get_request("pas", 0));
$new_width	= preg_replace("/[^0-9]+/", "", get_request("w", 0));
$new_height	= preg_replace("/[^0-9]+/", "", get_request("h", 0));
$quality		= preg_replace("/[^0-9]+/", "", get_request("q", 60));
$aiga		= preg_replace("/[^0-9.]+/", "", get_request("aiga", 0.8));
$asrh		= preg_replace("/[^0-9%]+/", "", get_request("asrh", '30%'));
$asrfs		= preg_replace("/[^0-9%]+/", "", get_request("asrfs", '20%'));
$asrfe		= preg_replace("/[^0-9%]+/", "", get_request("asrfe", '0%'));
$pb			= preg_replace("/[^0-9]+/", "", get_request("pb", 0));
$tintc		= preg_replace("/[^0-9a-f]+/i", "", get_request("tintc", 'ff0000'));
$tinta		= preg_replace("/[^0-9%]+/", "", get_request("tinta", '70%'));
$bshift		= preg_replace("/[^0-9]+/", "", get_request("bshift", 2));
$bg			= preg_replace("/[^0-9a-f]+/i", "", get_request("bg", ''));
$cursorX		= preg_replace("/[^0-9]+/", "", get_request("ccx", 0));
$cursorY		= preg_replace("/[^0-9]+/", "", get_request("ccy", 0));
$c_radius		= preg_replace("/[^0-9]+/", "", get_request("cr", ''));
$oversample	= preg_replace("/[^0-1]/", "", get_request("arc", 1));
$filename_only	= preg_replace("/[^0-1]/", "", get_request("fo", '0'));

// We need to handle these inputs independently because we want to be able to preserve the image's aspect ratio when we resize...
if ($new_width == 0 && $new_height == 0) {
	if (!$cursorX and !$cursorY) {
		$new_width = 100;
		$new_height = 100;
	}
}

// Set path to cache directory. This can be changed to a different location.
$cache_dir = './img_dir_cache';

// Get the MIME type of the source image...
$mime_type = mime_type($src);

// Check to see if this image is in the cache already...
$nocache = (isset($_GET['nocache'])) ? 1 : 0;
if (! $nocache) {
	check_cache( $cache_dir, $mime_type );
}

// If not, then clear some space and generate a new file...
cleanCache();

// Memory allocation. Adjust this based on the style of images you're likely to be handling. Smaller is better (despite what the girls say)!
ini_set('memory_limit', "512M");

// Make sure that the src is gif/jpeg/png...
if (!valid_src_mime_type($mime_type)) {
	displayError("Invalid source image MIME type - " .$mime_type);
}

// Let's go!
if (strlen($src) && file_exists($src)) {

	// Open the source image...
	$image = open_image($mime_type, $src);
	if ($image == false) {
		displayError('Unable to open source image - ' . $src);
	}

	// Get the width and height of the source image...
	$width = imagesx($image);
	$height = imagesy($image);


	// Cropping...
	if ($cursorX or $cursorY) {
		if ($cursorX and $cursorY and $zoom_crop) {
			if ($width >= $height) {
				$cursorY = 0;
			} else {
				$cursorX = 0;
			}
		}
		if ($cursorX and !$new_width) {
			$new_width = ($width - $cursorX);
		}
		if ($cursorY and !$new_height) {
			$new_height = ($height - $cursorY);
		}
		// Boundary control...
		if ( ($cursorX >= $width) or ( ($cursorX + $new_width) > $width ) ) {
			$cursorX = ($width - $new_width);
		}
		if ( ($cursorY >= $height) or ( ($cursorY + $new_height) > $height ) ) {
			$cursorY = ($height - $new_height);
		}
		// Window...
		if ( $zoom_crop ) {
			if ( $cursorX >= $cursorY ) {
				$windowW = ($width - $cursorX);
				$windowH = $height;
			} else {
				$windowW = $width;
				$windowH = ($height - $cursorY);
			}
		} else {
			$windowW = $new_width;
			$windowH = $new_height;
			$t = 0; /* If we're cropping then thumbnailing is an automatic process, no further thumbnailing necessary. */
		}
		// Don't allow new width or height to be greater than the original...
		if ( $windowW > $width ) {
			$windowW = $width;
		}
		if ( $windowH > $height ) {
			$windowH = $height;
		}
		// Create a new true colour image...
		$canvas = imagecreatetruecolor( $windowW, $windowH );
		imagealphablending($canvas, false);
		// Create a new transparent colour for image...
		$color = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
		// Completely fill the background of the new image with allocated colour...
		imagefill($canvas, 0, 0, $color);
		// Restore transparency blending...
		imagesavealpha($canvas, true);
		// Copy the source image from supplied X and Y co-ordinates...
		imagecopyresampled( $canvas, $image, 0, 0, $cursorX, $cursorY, $width, $height, $width, $height );
		// Remove 'image' from memory...
		imagedestroy($image);
		unset ($image);
		// Copy 'canvas' to 'image'...
		$image = imagecreatetruecolor( $windowW, $windowH );
		imagecopy( $image, $canvas, 0, 0, 0, 0, $windowW, $windowH );
		// Remove 'canvas' from memory..
		imagedestroy($canvas);
		unset ($canvas);
		// Get the 'width' and 'height' values, we'll probably need them later...
		$width = imagesx($image);
		$height = imagesy($image);
	}

	// Generate a thumbnail, or a size-reduced image (if requested, or by default)...
	if ($t) {
		// Don't allow new width or height to be greater than the original...
		if ( $new_width > $width ) {
			$new_width = $width;
		}
		if ( $new_height > $height ) {
			$new_height = $height;
		}
		// Generate new dimensions if not provided...
		if ( $new_width && !$new_height ) {
			$new_height = $height * ( $new_width / $width );
		} elseif ($new_height && !$new_width) {
			$new_width = $width * ( $new_height / $height );
		} elseif (!$new_width && !$new_height) {
			$new_width = $width;
			$new_height = $height;
		}
		$new_width = round($new_width);
		$new_height = round($new_height);
		// Create a new true colour image...
		$canvas = imagecreatetruecolor( $new_width, $new_height );
		imagealphablending($canvas, false);
		// Create a new transparent colour for image...
		$color = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
		// Completely fill the background of the new image with allocated colour...
		imagefill($canvas, 0, 0, $color);
		// Restore transparency blending...
		imagesavealpha($canvas, true);
		// Zoom crop...
		if ( $zoom_crop ) {
			$src_x = $src_y = 0;
			$src_w = $width;
			$src_h = $height;
			$cmp_x = $width  / $new_width;
			$cmp_y = $height / $new_height;
			// Calculate x or y coordinate and width or height of source...
			if ( $cmp_x > $cmp_y ) {
				$src_w = round( ( $width / $cmp_x * $cmp_y ) );
				$src_x = round( ( $width - ( $width / $cmp_x * $cmp_y ) ) / 2 );
			} elseif ( $cmp_y > $cmp_x ) {
				$src_h = round( ( $height / $cmp_y * $cmp_x ) );
				$src_y = round( ( $height - ( $height / $cmp_y * $cmp_x ) ) / 2 );
			}
			imagecopyresampled( $canvas, $image, 0, 0, $src_x, $src_y, $new_width, $new_height, $src_w, $src_h );
		} else {
			// Copy and resize part of an image with resampling...
			imagecopyresampled( $canvas, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height );
		}
		// Remove 'image' from memory...
		imagedestroy($image);
		$image = 0;
		// Copy 'canvas' to 'image'...
		$image = imagecreatetruecolor( $new_width, $new_height );
		if ($pas) {
			imagesavealpha($image, true);
			imagealphablending($image, false);
		}
		imagecopy( $image, $canvas, 0, 0, 0, 0, $new_width, $new_height );
		// Remove 'canvas' from memory..
		imagedestroy($canvas);
		$canvas = 0;
		// Copy the 'new_width' and 'new_height' variables in case we need them later...
		$width = $new_width;
		$height = $new_height;
	}
	// Copy 'image' to 'source_image' (we need this if we're going to build a CSS Sprite later)...
	$source_image = imagecreatetruecolor( $width, $height );
	imagesavealpha($source_image, true);
	imagealphablending($source_image, false);
	imagecopy( $source_image, $image, 0, 0, 0, 0, $width, $height );


	// Filters:

	// Apply the Weighted Average Grayscale conversion (if requested)...
	if ($aig) {
		$image = AI_grayscale($image, $width, $height, $aiga);
	}
	// Apply a colour tint to the image (if requested)...
	if ($tint) {
		$image = tint($image, $tintc, $tinta);
	}
	// Variable, pixel-by-pixel blur (if requested)...
	if ($blur) {
		$image = blur($image, $width, $height, $bshift);
	}


	// Actions:

	// Rounded corners...
	if ($c_radius) {
		$image = roundedCorners($image, $bg, $c_radius, $width, $height);
		if ($csss) {
			// If we're generating a CSS Sprite along with rounded corners, then we'll need to apply the corner rounding to both versions of the image...
			$source_image = roundedCorners($source_image, $bg, $c_radius, $width, $height);
		}
	}

	// Generate a drop-shadow...
	if (($ds) && (!$asr) && (!$c_radius)) {
		$image = drop_shadow($image, $width, $height, $pb, $bg);
	}

	// Generate the apple.com-style reflection (if requested)...
	if ($asr) {
		if ($csss) {
			$source_image = as_reflection($source_image, $width, $height, $asrh, $asrfs, $asrfe);
		}
		$image = as_reflection($image, $width, $height, $asrh, $asrfs, $asrfe);
		$width = imagesx($image);
		$height = imagesy($image);
	}


	// At this point we have in memory:
	//	$source_image which has no filters applied but which might have an apple.com-style reflection;
	//	$image which has all requested filters applied and which might also have an apple.com-style reflection.
	// Both will be required if we're going to generate a CSS Sprite...


	// Generate the CSS Sprite (if requested)...
	if ($csss) {
		// Copy 'source_image' onto the top half of the 'canvas' (canvas' height = image's height x 2)...
		$canvas = imagecreatetruecolor( $width, ($height * 2) );
		imagesavealpha($canvas, true);
		imagealphablending($canvas, false);
		imagecopy( $canvas, $source_image, 0, 0, 0, 0, $width, $height );
		// Copy 'image' onto the bottom half of the 'canvas'...
		imagecopy( $canvas, $image, 0, $height, 0, 0, $width, $height );
		$image = $canvas;

	}

	// Output image to the browser based on MIME type...
	show_image($mime_type, $image, $cache_dir);

	// Remove image from memory...
	imagedestroy($canvas);
	imagedestroy($image);
	imagedestroy($source_image);

} else {
	// Houston, we have a problem!
	if(strlen($src)) {
		displayError("Not allowed!");
	} else {
		displayError("No source specified!");
	}
}


/********************************************************
* Functions:
********************************************************/

/********************************************************
* Weighted Average AI Grayscale Image Conversion...
********************************************************/

function AI_grayscale($image, $width, $height, $v) {
	for ($x = 0; $x < $width; $x++) for ($y = 0; $y < $height; $y++) {
		$rgb = imageColorsForIndex($image, ImageColorAt($image, $x, $y));
		$gray = ($rgb["red"] + $rgb["green"] + $rgb["blue"])/3;
		if ($v != 0) {
			$sum = $rgb["red"] + $rgb["green"] + $rgb["blue"];
			if ($sum == 0) $relR = $relG = $relB = 0;
			else {
				$relR = $rgb["red"]/$sum;
				$relG = $rgb["green"]/$sum;
				$relB = $rgb["blue"]/$sum;
			}
			$gray = ($rgb["red"]*$relR + $rgb["green"]*$relG + $rgb["blue"]*$relB)*$v+$gray*(1-$v);
		}
		imagesetpixel($image, $x, $y, imagecolorallocate($image, $gray, $gray, $gray));
	}
	return $image;
}

/********************************************************
* Colour tint...
********************************************************/

function tint($image, $tc, $ta) {
	// Does the input colour value start with a hash? If so then strip it...
	$tc = str_replace('#', '', $tc);
	// Get the alpha channel value...
	if (strpos($ta, '%')) {
		$ta = str_replace('%', '', $ta);
		$ta = (int) (127 * $ta / 100);
	} else {
		$ta = (int) $ta;
		if ($ta < 1 || $ta > 127) {
			$ta = 100;
		}
	}
	// Hexadecimal conversions...
	switch (strlen($tc)) {
		case 6:
			$red = hexdec(substr($tc, 0, 2));
			$green = hexdec(substr($tc, 2, 2));
			$blue = hexdec(substr($tc, 4, 2));
			break;
		case 3:
			$red = substr($tc, 0, 1);
			$green = substr($tc, 1, 1);
			$blue = substr($tc, 2, 1);
			$red = hexdec($red . $red);
			$green = hexdec($green . $green);
			$blue = hexdec($blue . $blue);
			break;
		default:
			// Wrong values passed, default to red...
			$red = 255;
			$green = 0;
			$blue = 0;
	}
	imagefilter($image, IMG_FILTER_COLORIZE, $red, $green, $blue, $ta);
	return $image;
}

/********************************************************
* Variable blur...
********************************************************/

function blur ($image, $width, $height, $dist) {
	if ($dist > 10) { $dist = 10; }
	for ($i = 1; $i <= $dist; $i ++) {
		imagefilter($image, IMG_FILTER_GAUSSIAN_BLUR);
	}
	return $image;
}

/********************************************************
* Drop-shadow...
********************************************************/

function drop_shadow ($image, $width, $height, $photo_frame, $bg) {
	// Open the drop-shadow base image...
	$shadow_mask = open_image('png', './drop_shadow.png');
	if ($shadow_mask == false) {
		displayError('Unable to open shadow mask image: Please ensure that "drop_shadow.png" is in the same directory as "image_director.php".');
	}
	imagesavealpha($shadow_mask, true);
	imagealphablending($shadow_mask, false);
	// Create the base layer canvas (the shadow mask)...
	$shadow_depth = 15;
	$shadow_mask_xy = 100;
	$canvas = imagecreatetruecolor(($width + ($shadow_depth * 2) + ($photo_frame * 2)), ($height + ($shadow_depth * 2) + ($photo_frame * 2)));
	imagesavealpha($canvas, true);
	imagealphablending($canvas, false);
	// Slice and scale the shadow mask and apply the result to the base layer canvas...
	$canvas = nine_slice_mask($shadow_mask, ($shadow_depth * 2), ($shadow_depth * 2), ($shadow_mask_xy - ($shadow_depth * 4)), ($shadow_mask_xy - ($shadow_depth * 4)), ($width + ($shadow_depth * 2) + ($photo_frame * 2)), ($height + ($shadow_depth * 2) + ($photo_frame * 2)), $bg);
	// Copy the source image onto the canvas, inside the masked area...
	imagecopy($canvas, $image, ($shadow_depth + $photo_frame), ($shadow_depth + $photo_frame), 0, 0, $width, $height);
	// Clean up...
	imagedestroy($image);
	imagedestroy($shadow_mask);
	return $canvas;
}

/********************************************************
* Rounded corner rendering...
********************************************************/

function roundedCorners($image, $backcolor, $corner_radius, $width, $height) {
	$topleft = (isset($_GET['topleft']) and $_GET['topleft'] == "no") ? false : true; // Top-left rounded corner is shown by default
	$bottomleft = (isset($_GET['bottomleft']) and $_GET['bottomleft'] == "no") ? false : true; // Bottom-left rounded corner is shown by default
	$bottomright = (isset($_GET['bottomright']) and $_GET['bottomright'] == "no") ? false : true; // Bottom-right rounded corner is shown by default
	$topright = (isset($_GET['topright']) and $_GET['topright'] == "no") ? false : true; // Top-right rounded corner is shown by default

	// Set up...
	global $oversample;
	$oversample = ($oversample == 0) ? 1 : 2;
	$endsize = $corner_radius * $oversample;
	$startsize = $endsize * 3 - 1;
	$arcsize = $startsize * 2 + 1;
	$background = imagecreatetruecolor($width, $height);
	imagecopymerge($background, $image, 0, 0, 0, 0, $width, $height, 100);
	$startx = $width * (2 * $oversample);
	$starty = $height * (2 * $oversample);
	$im_temp = imagecreatetruecolor($startx, $starty);
	imagecopyresampled($im_temp, $background, 0, 0, 0, 0, $startx, $starty, $width, $height);

	// Background colour...
	if ($backcolor) {
		$bg = imagecolorallocate($im_temp, hexdec(substr($backcolor, 0, 2)), hexdec(substr($backcolor, 2, 2)), hexdec(substr($backcolor, 4, 2)));
	} else {
		imagedestroy($background);
		imagedestroy($im_temp);
		return $image;
	}

	// Top-left corner...
	if ($topleft == true) {
		imagearc($im_temp, $startsize, $startsize, $arcsize, $arcsize, 180, 270, $bg);
		imagefilltoborder($im_temp, 0, 0, $bg, $bg);
	}

	// Bottom-left corner...
	if ($bottomleft == true) {
		imagearc($im_temp, $startsize, $starty-$startsize, $arcsize, $arcsize, 90, 180, $bg);
		imagefilltoborder($im_temp, 0, $starty, $bg, $bg);
	}

	// Bottom-right corner...
	if ($bottomright == true) {
		imagearc($im_temp, $startx-$startsize, $starty-$startsize, $arcsize, $arcsize, 0, 90, $bg);
		imagefilltoborder($im_temp, $startx, $starty, $bg, $bg);
	}

	// Top-right corner...
	if ($topright == true) {
		imagearc($im_temp, $startx-$startsize, $startsize, $arcsize, $arcsize, 270, 360, $bg);
		imagefilltoborder($im_temp, $startx, 0, $bg, $bg);
	}

	// Apply the overlay to the source image...
	imagecopyresampled($image, $im_temp, 0, 0, 0, 0, $width, $height, $startx, $starty);

	// Clean up...
	if (!$backcolor) {
		$bg = '';
	}
	imagedestroy($background);
	imagedestroy($im_temp);
	return $image;
}

/********************************************************
* apple.com-style reflection...
********************************************************/

function as_reflection($image, $width, $height, $asrh, $asrfs, $asrfe) {
	global $bg;
	// Process the reflection height input value...
	$reflection_height = $asrh;
	// Is the input a percentage?
	if (substr($reflection_height, -1) == '%') {
		// Yes, remove the % sign...
		$reflection_height = (int) substr($reflection_height, 0, -1);
		// Gotta love auto type casting...
		if ($reflection_height == 100) {
			$reflection_height = "0.99";
		} elseif ($reflection_height < 10) {
			$reflection_height = "0.0$reflection_height";
		} else {
			$reflection_height = "0.$reflection_height";
		}
	} else {
		$reflection_height = (int) $reflection_height;
	}
	// Calculate the height of the reflection...
	if ($reflection_height < 1) {
		// The reflection height is a percentage...
		$reflection_height = $height * $reflection_height;
	} else {
		// The reflection height is a fixed pixel value...
		$reflection_height = $reflection_height;
	}
	$reflection_height = round($reflection_height);
	$new_height = $height + ($reflection_height - 1);
	// Get the Alpha fade start value...
	if (strpos($asrfs, '%') !== false) {
		$alpha_start = str_replace('%', '', $asrfs);
		$alpha_start = (int) (127 * $alpha_start / 100);
	} else {
		$alpha_start = (int) $asrfs;
		if ($alpha_start < 1 || $alpha_start > 127) {
			$alpha_start = 20;
		}
	}
	// Get the Alpha fade end value...
	if (strpos($asrfe, '%') !== false) {
		$alpha_end = str_replace('%', '', $asrfe);
		$alpha_end = (int) (127 * $alpha_end / 100);
	} else {
		$alpha_end = (int) $asrfe;
		if ($alpha_end < 1 || $alpha_end > 0) {
			$alpha_end = 0;
		}
	}
	// Create the reflection:
	// We'll build the reflection in $canvas...
	$canvas = imagecreatetruecolor($width, $reflection_height);
	// Save any alpha data that might have existed in the source image and disable blending...
	imagesavealpha($image, true);
	imagealphablending($image, false);
	// Set the canvas up for alpha data and disable blending...
	imagesavealpha($canvas, true);
	imagealphablending($canvas, false);
	// Copy the bottom-most part of the source image onto the canvas (dest params first)...
	imagecopy($canvas, $image, 0, 0, 0, ($height - $reflection_height), $width, $reflection_height);
	// Rotate the canvas 180 degrees...
	$canvas = imagerotate($canvas, 180, 0);
	// Flip the canvas...
	$temp = imagecreatetruecolor($width, $reflection_height);
	imagesavealpha($temp, true);
	imagealphablending($temp, false);
	imagecopyresampled($temp, $canvas, 0, 0, ($width-1), 0, $width, $reflection_height, 0-$width, $reflection_height);
	$canvas = $temp;
	// Apply the fade effect: This is quite simple really. There are 127 available levels of alpha, so we just step-through the reflected image, drawing a box over the top, with a set alpha level. The end result? A cool fade.
	// There are a maximum of 127 alpha fade steps we can use, so work out the alpha step rate...
	$alpha_length = abs($alpha_start - $alpha_end);
	imagelayereffect($canvas, IMG_EFFECT_OVERLAY);
	for ($y = 0; $y <= $reflection_height; $y++) {
		// Get percentage of reflection height...
		$pct = $y / $reflection_height;
		// Get percentage of alpha...
		if ($alpha_start > $alpha_end) {
			$alpha = (int) ($alpha_start - ($pct * $alpha_length));
		} else {
			$alpha = (int) ($alpha_start + ($pct * $alpha_length));
		}
		// Refactor it because of the way in which the image effect overlay works...
		$final_alpha = 127 - $alpha;
		imagefilledrectangle($canvas, 0, $y, $width, $y, imagecolorallocatealpha($canvas, 127, 127, 127, $final_alpha));
	}
	// Put the pieces together (source image and its reflection)...
	$final_image = imagecreatetruecolor($width, $new_height);
	imagesavealpha($final_image, true);
	imagealphablending($final_image, false);
	imagecopy( $final_image, $image, 0, 0, 0, 0, $width, $height );
	imagecopy( $final_image, $canvas, 0, $height, 0, 0, $width, $reflection_height );
	// Remove 'canvas' from memory..
	imagedestroy($canvas);
	unset ($canvas);
	$image = $final_image;
	// If a background colour has been declared then we will create a new canvas with the final image dimensions, fill it with the background colour and paste our ASR image on top of it. Then we will be able to save the image as a JPEG and benefit from a smaller file size.
	if ($bg) {
		$noAlphaImage = imagecreatetruecolor($width, $new_height);
		imagesavealpha($noAlphaImage, false);
		$background = imagecolorallocate($noAlphaImage, (hexdec(substr($bg, 0, 2))), (hexdec(substr($bg, 2, 2))), (hexdec(substr($bg, 4, 2))));
		imagefill($noAlphaImage, 0, 0, $background);
		imagealphablending($noAlphaImage, true);
		imagecopy( $noAlphaImage, $final_image, 0, 0, 0, 0, $width, $new_height );
		// Remove 'final_image' from memory..
		imagedestroy($final_image);
		unset ($final_image);
		$image = $noAlphaImage;
	}
	return $image;
}

/********************************************************
* Mask slicing and scaling (used for drop-shadows)...
********************************************************/

function nine_slice_mask($src_im, $rect_x, $rect_y, $rect_w, $rect_h, $target_w, $target_h, $bg) {
	// The Chop Shop:
	// You can mess with this code if you want to, but I promise you it'll probably end in tears. Trust me, many brave souls have gone before you and none have prevailed. You have been warned!
	// Shout outs and credits for this to Sam Kelly at DuroSoft (http://www.durosoft.com/) and latin4567@gmail.com whose source-code on php.net is replicated here. If it fucks anything up, you now know who to blame.
	$src_w = imagesx($src_im);
	$src_h = imagesy($src_im);
	$im = create_blank_PNG($target_w, $target_h);
	if ($bg) {
		$background = imagecolorallocate($im, (hexdec(substr($bg, 0, 2))), (hexdec(substr($bg, 2, 2))), (hexdec(substr($bg, 4, 2))));
		imagefill($im, 0, 0, $background);
	}
	imagealphablending($im,true);
	$left_w = $rect_x;
	$right_w = $src_w - ($rect_x + $rect_w);
	$left_src_y = ceil($rect_h / 2) - 1 + $rect_y;
	$right_src_y = $left_src_y;
	$left_src_x = 0;
	$right_src_x = $left_w + $rect_w;
	$top_src_x = ceil($rect_w / 2) - 1 + $rect_x;
	$bottom_src_x = $top_src_x;
	$bottom_src_y = $rect_y + $rect_h;
	$bottom_h = $src_h - $bottom_src_y;
	$left_tile = create_blank_PNG($left_w, 1);
	imagecopy($left_tile, $src_im, 0, 0, 0, $left_src_y, $left_w, 1);
	$right_tile = create_blank_PNG($right_w, 1);
	imagecopy($right_tile, $src_im, 0, 0, $right_src_x, $right_src_y, $right_w, 1);
	$top_tile = create_blank_PNG(1, $rect_y);
	imagecopy($top_tile, $src_im, 0, 0, $top_src_x, 0, 1, $rect_y);
	$bottom_tile = create_blank_PNG(1, $bottom_h);
	imagecopy($bottom_tile, $src_im, 0, 0, $bottom_src_x, $bottom_src_y, 1, $bottom_h);
	$inner_tile = create_blank_PNG(4, 4);
	imagecopy($inner_tile, $src_im, 0, 0, ceil($src_w / 2) - 1, ceil($src_h / 2) - 1, 4, 4);
	imagecopy($im, $src_im, 0, 0, 0, 0, $left_w, $rect_y);
	imagecopy($im, $src_im, 0, $target_h - $bottom_h, 0, $bottom_src_y, $rect_x, $bottom_h);
	imagecopy($im, $src_im, $target_w - $right_w, 0, $right_src_x, 0, $right_w, $rect_y);
	imagecopy($im, $src_im, $target_w - $right_w, $target_h - $bottom_h, $src_w - $right_w, $bottom_src_y, $right_w, $bottom_h);
	imagesettile($im, $top_tile);
	imagefilledrectangle($im, $left_w, 0, $target_w - $right_w - 1, $rect_y, IMG_COLOR_TILED);
	imagesettile($im, $left_tile);
	imagefilledrectangle($im, 0, $rect_y, $left_w, $target_h - $bottom_h - 1, IMG_COLOR_TILED);
	$right_side = create_blank_PNG($right_w, $target_h - $rect_y - $bottom_h);
	imagesettile($right_side, $right_tile);
	imagefilledrectangle($right_side, 0, 0, $right_w, $target_h - $rect_y - $bottom_h, IMG_COLOR_TILED);
	imagecopy($im, $right_side, $target_w - $right_w, $rect_y, 0, 0, $right_w, $target_h - $rect_y - $bottom_h);
	$bottom_side = create_blank_PNG($target_w - $right_w - $left_w, $bottom_h);
	imagesettile($bottom_side, $bottom_tile);
	imagefilledrectangle($bottom_side, 0, 0, $target_w - $right_w - $left_w, $bottom_h, IMG_COLOR_TILED);
	imagecopy($im, $bottom_side, $right_w, $target_h - $bottom_h, 0, 0, $target_w - $right_w - $left_w, $bottom_h);
	imagedestroy($left_tile);
	imagedestroy($right_tile);
	imagedestroy($top_tile);
	imagedestroy($bottom_tile);
	imagedestroy($inner_tile);
	imagedestroy($right_side);
	imagedestroy($bottom_side);
	return $im;
}

/********************************************************
* Create a blank PNG canvas...
********************************************************/

function create_blank_PNG($w, $h) {
	$im = imagecreatetruecolor($w, $h);
	imagesavealpha($im, true);
	imagealphablending($im, false);
	$transparent = imagecolorallocatealpha($im, 0, 0, 0, 127);
	imagefill($im, 0, 0, $transparent);
	return $im;
}

/********************************************************
* Show image...
********************************************************/

function show_image($mime_type, $image_resized, $cache_dir) {
	global $c_radius, $ds, $asr, $quality, $bg, $filename_only;
	if ( ($ds && !$bg) || ($asr && !$bg) ) {
		$mime_type = 'image/png';
	}
	if ( stristr($mime_type, 'png') && !$asr && $c_radius && $bg ) {
		$mime_type = 'image/jpeg';
	}
	// Check to see if we can write to the cache directory...
	$is_writable = 0;
	$cache_file_name = $cache_dir . '/' . get_cache_file($mime_type);
	if (touch($cache_file_name)) {
		// Give 666 so that we can programmatically overwrite files...
		chmod($cache_file_name, 0666);
		$is_writable = 1;
	} else {
		$cache_file_name = NULL;
		header('Content-type: ' . $mime_type);
	}
	if (stristr($mime_type, 'png')) {
		imagepng($image_resized, $cache_file_name, 9, PNG_ALL_FILTERS);
	} elseif (stristr($mime_type, 'jpeg')) {
		imagejpeg($image_resized, $cache_file_name, $quality);
	} elseif (stristr($mime_type, 'gif')) {
		imagegif($image_resized, $cache_file_name);
	}
	if($is_writable) {
		if ($filename_only) {
			echo $cache_file_name;
			exit;
		} else {
			show_cache_file($cache_dir, $mime_type);
		}
	}
	imagedestroy($image_resized);
	displayError("Error showing image!");
}

/********************************************************
* Get Request...
********************************************************/

function get_request( $property, $default = 0 ) {
	if ( isset($_REQUEST[$property]) ) {
		return $_REQUEST[$property];
	} else {
		return $default;
	}
}

/********************************************************
* Open image...
********************************************************/

function open_image($mime_type, $src) {
	if (stristr($mime_type, 'gif')) {
		$image = imagecreatefromgif($src);
	} elseif (stristr($mime_type, 'jpeg')) {
		@ini_set('gd.jpeg_ignore_warning', 1);
		$image = imagecreatefromjpeg($src);
	} elseif ( stristr($mime_type, 'png')) {
		$image = imagecreatefrompng($src);
	}
	return $image;
}

/********************************************************
* Clean out old files from the cache...
********************************************************/

function cleanCache() {
	global $cache_dir;
	$pattern = $cache_dir . "/*";
	$files = glob($pattern, GLOB_BRACE);
	$yesterday = time() - (24 * 60 * 60);
	if (count($files) > 0) {
		usort($files, "filemtime_compare");
		$i = 0;
		if (count($files) > CACHE_SIZE) {
			foreach ($files as $file) {
				$i ++;
				if ($i >= CACHE_CLEAR) {
					return;
				}
				if (filemtime($file) > $yesterday) {
					return;
				}
				unlink($file);
			}
		}
	}
}

/********************************************************
* Compare the file time of two files...
********************************************************/

function filemtime_compare($a, $b) {
	return filemtime($a) - filemtime($b);
}

/********************************************************
* Determine the file's MIME type...
********************************************************/

function mime_type($file) {
	// Is this a Windows box?
	if (stristr(PHP_OS, 'WIN')) {
		$os = 'WIN';
	} else {
		$os = PHP_OS;
	}
	// Most reliable examination of MIME type, if available...
	$mime_type = '';
	if (function_exists('mime_content_type')) {
		$mime_type = mime_content_type($file);
	}
	// Didn't work? Use PECL fileinfo to determine MIME type...
	if (!valid_src_mime_type($mime_type)) {
		if (function_exists('finfo_open')) {
			$finfo = finfo_open(FILEINFO_MIME);
			$mime_type = finfo_file($finfo, $file);
			finfo_close($finfo);
		}
	}
	// Still no resolution? Try to determine MIME type by using *nix file command...
	// This should not be executed on Windows!
	if (!valid_src_mime_type($mime_type) && $os != "WIN") {
		if (preg_match("/FREEBSD|LINUX/", $os)) {
			$mime_type = trim(@shell_exec('file -bi "' . $file . '"'));
		}
	}
	// If all else fails, use file's extension to determine MIME type...
	if (!valid_src_mime_type($mime_type)) {
		// Set defaults...
		$mime_type = 'image/png';
		// File details...
		$fileDetails = pathinfo($file);
		$ext = strtolower($fileDetails["extension"]);
		// MIME types...
		$types = array(
 			'jpg'  => 'image/jpeg',
 			'jpeg' => 'image/jpeg',
 			'png'  => 'image/png',
 			'gif'  => 'image/gif'
 		);
		if (strlen($ext) && strlen($types[$ext])) {
			$mime_type = $types[$ext];
		}
	}
	return $mime_type;
}

/********************************************************
* Have we got a valid MIME type?
********************************************************/

function valid_src_mime_type($mime_type) {
	if (preg_match("/jpg|jpeg|gif|png/i", $mime_type)) {
		return true;
	}
	return false;
}

/********************************************************
* Check to see if this image is in the cache already...
********************************************************/

function check_cache($cache_dir, $mime_type) {
	// Make sure the cache directory exists...
	if (!file_exists($cache_dir)) {
		// Give 777 permissions so that we can programmatically overwrite files...
		mkdir($cache_dir);
		chmod($cache_dir, 0777);
	}
	show_cache_file($cache_dir, $mime_type);
}

/********************************************************
* Retrieve and display the file from the cache...
********************************************************/

function show_cache_file($cache_dir, $mime_type) {
	global $filename_only;
	$cache_file = $cache_dir . '/' . get_cache_file($mime_type);

	if (file_exists($cache_file)) {
		if ($filename_only) {
			echo $cache_file;
			exit;
		} else {
			$gmdate_mod = gmdate("D, d M Y H:i:s", filemtime($cache_file));
			$gmdate_modE = gmdate("D, d M Y H:i:s", strtotime("+1 year", filemtime($cache_file)));
			if (! strstr($gmdate_mod, "GMT")) {
				$gmdate_mod .= " GMT";
			}
			if (! strstr($gmdate_modE, "GMT")) {
				$gmdate_modE .= " GMT";
			}
			if (isset($_SERVER["HTTP_IF_MODIFIED_SINCE"])) {
				// Check for updates...
				$if_modified_since = preg_replace("/;.*$/", "", $_SERVER["HTTP_IF_MODIFIED_SINCE"]);
				if ($if_modified_since == $gmdate_mod) {
					header("HTTP/1.1 304 Not Modified");
					exit;
				}
			}
			$fileSize = filesize($cache_file);
			// Send headers then display image...
			header("Content-Type: " . $mime_type);
			header("Accept-Ranges: bytes");
			header("Last-Modified: " . $gmdate_mod);
			header("Content-Length: " . $fileSize);
			header("Cache-Control: max-age=9999, must-revalidate");
			header("Expires: " . $gmdate_modE);
			readfile($cache_file);
			exit;
		}
	}
}

/********************************************************
* Retrieve the file from the cache...
********************************************************/

function get_cache_file($mime_type) {
	global $ds, $asr, $lastModified, $bg;
	static $cache_file;
	if (!$cache_file) {
		$cachename = $_SERVER['QUERY_STRING'] . hash_file('crc32b', dirname('__FILENAME__') . $_SERVER['PHP_SELF']) . VERSION . $lastModified;
		if ( stristr($mime_type, 'gif') && (!$ds) && (!$asr) ) {
			$imageExt = 'gif';
		} elseif ( stristr($mime_type, 'gif') && (($ds && !$bg) || ($asr && !$bg)) ) {
			$imageExt = 'png';
		} elseif ( stristr($mime_type, 'jpeg') && (!$ds) && (!$asr) ) {
			$imageExt = 'jpg';
		} elseif ( stristr($mime_type, 'jpeg') && (($ds && $bg) || ($asr && $bg)) ) {
			$imageExt = 'jpg';
		} elseif ( stristr($mime_type, 'png') || ($ds && !$bg) || ($asr && !$bg) ) {
			$imageExt = 'png';
		} elseif ( stristr($mime_type, 'png') && (($ds && $bg) || ($asr && $bg)) ) {
			$imageExt = 'jpg';
		}
		$cache_file = md5($cachename) . '.' . $imageExt;
	}
	return $cache_file;
}

/********************************************************
* Tidy up the image source URI...
********************************************************/

function cleanSource($src) {
	// Remove slash from start of string...
	if (strpos($src, '/') == 0) {
		$src = substr($src, -(strlen($src) - 1));
	}
	// Remove http/https/ftp...
	$src = preg_replace("/^((ht|f)tp(s|):\/\/)/i", "", $src);
	// Remove domain name from the source url...
	$host = $_SERVER["HTTP_HOST"];
	$src = str_replace($host, "", $src);
	$host = str_replace("www.", "", $host);
	$src = str_replace($host, "", $src);

	// SECURITY: Don't allow users the ability to use '../' in order to gain access to files below document root.

	// src should be specified relative to document root like:
	// src=images/img.jpg or src=/images/img.jpg
	// not like:
	// src=../images/img.jpg
	$src = preg_replace("/\.\.+\//", "", $src);

	// Get the path to the image on the file system...
	$src = get_document_root($src) . '/' . $src;
	return $src;
}

/********************************************************
* Get the document root...
********************************************************/

function get_document_root ($src) {
	// Check for unix servers...
	if (@file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . $src)) {
		return $_SERVER['DOCUMENT_ROOT'];
	}
	$paths = array(
		".",
		"..",
		"../..",
		"../../..",
		"../../../..",
		"../../../../.."
	);
	foreach ($paths as $path) {
		if (@file_exists($path . '/' . $src)) {
			return $path;
		}
	}
	// Special check for Microsoft servers...
	if (!isset($_SERVER['DOCUMENT_ROOT'])) {
		$path = str_replace("/", "\\", $_SERVER['ORIG_PATH_INFO']);
		$path = str_replace($path, "", $_SERVER['SCRIPT_FILENAME']);
		if ( @file_exists( $path . '/' . $src ) ) {
			return $path;
		}
	}
	displayError('File not found - ' . $src);
}

/********************************************************
* Error Handling...
********************************************************/

function displayError($errorString = '') {
	header('HTTP/1.1 400 Bad Request');
	die('Error: ' . $errorString);
}

?>