<?php
include('../../iframe/common.php');
include('./Source.php');

//$file = '/var/www/html/openjs/scripts/jslibrary/code/jsl_ajax.js';
$file = 'data/Sql.php';
$source = new Source($file);

/* */
//ob_start();
render();
//$contents = ob_get_contents();
//ob_end_clean();

// Write this to a file?
// print $contents;
// */