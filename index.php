<?php
include('../../iframe/common.php');
include('./Source.php');

if(isset($_REQUEST['action']) and $_REQUEST['action'] == 'Generate Docs') {
	list($file, $result) = upload('code', 'data');
	
	if($file) {
		$source = new Source('data/'. $file);
		$format = isset($_REQUEST['format']) ? $_REQUEST['format'].'.php' : 'html.php';
		if($format == 'wiki.php') $template->options['insert_layout'] = false;
		
		render($format);
		exit;
	} else {
		showMessage("Cannot Upload file: $result", '', 'error');
	}
}

render();