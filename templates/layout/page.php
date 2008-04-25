<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html><head>
<title><?=$title?></title>
<link href="<?=$rel?>css/style.css" type="text/css" rel="stylesheet" />
<script src="<?=$rel?>js/JSL.js" type="text/javascript"></script>
<script src="<?=$rel?>js/script.js" type="text/javascript"></script>
<?=$includes?>
</head>
<body>
<div id="header">
</div>

<div id="main">
<!-- Begin Content -->
<?php 
/////////////////////////////////// The Template file will appear here ////////////////////////////

include($GLOBALS['template']->template); 

/////////////////////////////////// The Template file will appear here ////////////////////////////
?>
<!-- End Content -->
</div>

<div id="footer"></div>

</body>
</html>
