<!DOCTYPE html>
<html>
<head>
<script src="js/dropdown.js" defer ></script>
</head>
<body>
<?php
	$svgHTML = file_get_contents("../output/".$_GET['svgFile']);
	echo $svgHTML.PHP_EOL;
?>
</body>
</html>

