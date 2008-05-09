<?php
include('../../iframe/common.php');
include('./Source.php');

//$file = 'data/Sql.php';
$file = '/var/www/html/Sites/openjs/openjs.com/scripts/jslibrary/code/jsl_number.js';

$source = new Source($file);

/* */
//ob_start();
render();
//$contents = ob_get_contents();
//ob_end_clean();

// Write this to a file?
// print $contents;
// */