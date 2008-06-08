<?php
include('../../iframe/common.php');
include('./Source.php');

$file = '/var/www/html/Sites/openjs/openjs.com/scripts/ui/context_menu/context_menu.js';

$source = new Source($file);

/* */
//ob_start();
$format = isset($_REQUEST['format']) ? $_REQUEST['format'].'.php' : '';
if($format == 'wiki.php') $template->options['insert_layout'] = false;

render($format);
//$contents = ob_get_contents();
//ob_end_clean();

// Write this to a file?
// print $contents;
// */