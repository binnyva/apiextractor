<?php
include('../../iframe/common.php');
include('./Source.php');

$format = isset($_REQUEST['format']) ? $_REQUEST['format'].'.php' : '';

//$file = 'data/Sql.php';
$file = '/var/www/html/Sites/openjs/openjs.com/scripts/jslibrary/code/jsl_'.$_REQUEST['file'].'.js';

$source = new Source($file);

/* */
//ob_start();
$template->options['insert_layout'] = false;
render($format);
//$contents = ob_get_contents();
//ob_end_clean();

// Write this to a file?
// print $contents;
// */