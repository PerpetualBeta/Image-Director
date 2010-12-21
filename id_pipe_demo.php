<?php

	/*
		You must have your script include the 'id_pipe' function. It is this function that handles the Image Director piping operation.
	*/
	include './id_pipe.php';

	/*
		The 'id_pipe' function MUST be given an ABSOLUTE URL for the source image operation. Relative URLs will not work here.
		Also note the additional parameter in the call to the Image Director, "fo=1". This tells the Image Director to process the source image and create the output file but, rather than display the processed image, the Image Director will return it's path and filename. It is this that can then be passed on for further Image Director operations.
	*/
	$image_source = 'http://farm5.static.flickr.com/4022/4324450677_9bc65dac4a_o.jpg';
	$original_image = 'http://image-director.com:8888/?src=' . $image_source . '&w=900&h=552&ccx=0&ccy=1&q=100&rf=1&fo=1';

	/*
		Now we perform the first Image Director operation, the "Master Crop". The Image Director returns the path and filename of the processed image (from this operation) which we store in the variable "$master_crop". This operation is performed via the 'id_pipe' function.
	*/
	$master_crop = id_pipe($original_image);

	$pageTitle = 'Image Director: Operation Piping (Chaining) Demonstration';

?>

<html>
<head>

	<title><?php echo $pageTitle; ?></title>
</head>
<body style="margin: 0; padding: 0;">
	<div style="background: #000; color: #fff; padding: 20px; margin: 0;">

		<pre><div id="version">Please Wait...</div></pre>

		<h1 style="font-weight: 300; font-family: 'helvetica neue', helvetica, arial, verdana, 'sans serif';"><?php echo $pageTitle; ?></h1>

		<!-- Display the original (source) image. -->
		<?php
			list($width, $height) = getimagesize($image_source);
		?>
		<p style="margin-top: 50px;">Source Image (<?php echo $image_source; ?> @ <?php echo $width; ?> &times; <?php echo $height; ?> pixels):</p>
		<img src="<?php echo $image_source; ?>" alt="Original Image" />

		<!-- Now the "Master Crop". Note that we are not calling the Image Director at all for this image, all we're doing is displaying the image that's referenced in the "$master_crop" variable - ie: the output of our 'id_pipe' call to the Image Director. -->
		<?php
			list($width, $height) = getimagesize($master_crop);
		?>
		<p style="margin-top: 50px;">Master Crop (<?php echo $width; ?> &times; <?php echo $height; ?> pixels) - becomes the source image for the remaining operations:</p>
		<img src="<?php echo $master_crop; ?>" alt="Master Crop" />

		<!-- Now for our "Tight Crop". Here we call the Image Director once again, this time telling it to use the "$master_crop" image as it's source. -->
		<?php
			$mediumSource = 'http://image-director.com:8888/?src=' . $master_crop . '&q=90&cr=12&bg=000000&t=0&ccx=226&w=620&ccy=284&h=268&oversampling=2';
			list($width, $height) = getimagesize($mediumSource);
		?>
		<p style="margin-top: 50px;">Tight Crop (Piped from Master Crop @ <?php echo $width; ?> &times; <?php echo $height; ?> pixels):</p>
		<img src="<?php echo $mediumSource; ?>" alt="Tight Crop" />

		<!-- Finally we have our "Thumbnail Crop". Here we call the Image Director a final time, again telling it to use the "$master_crop" image as it's source. -->
		<?php
			$thumbnailSource = 'http://image-director.com:8888/?src=' . $master_crop . '&w=150&h=100&q=90&cr=10&bg=000000&zc=1&oversampling=2';
			list($width, $height) = getimagesize($thumbnailSource);
		?>
		<p style="margin-top: 50px;">Thumbnail Crop (Piped from Master Crop @ <?php echo $width; ?> &times; <?php echo $height; ?> pixels):</p>
		<img src="<?php echo $thumbnailSource; ?>" alt="Thumbnail Crop" />

	</div>

	<script type="text/javascript">
		/* Note that the JavaScript here is not in any way required for Image Director Piping operations. */
		function loadXMLDoc(source,targetID) {
			if (window.XMLHttpRequest) {
				// code for IE7+, Firefox, Chrome, Opera, Safari
				xmlhttp = new XMLHttpRequest();
			} else {
				// code for IE6, IE5
				xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
			}
			xmlhttp.onreadystatechange = function() {
				if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
					document.getElementById(targetID).innerHTML=xmlhttp.responseText;
				}
			}
			xmlhttp.open("GET",source,true);
			xmlhttp.send();
		}

		loadXMLDoc('http://image-director.com:8888/','version');
	</script>
</body>
</html>