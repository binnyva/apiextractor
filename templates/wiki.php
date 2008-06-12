<?php
header("Content-type: text/plain");
$GLOBALS['language'] = $source->language;
if($GLOBALS['language'] == 'js') $GLOBALS['language'] = 'javascript';

foreach($source->extractor->info['function'] as $func) {
	showFunction($func);
}

foreach($source->extractor->info['class'] as $class_data) {
	showClass($class_data);
}


// Functions
function showClass($class_data) {
	global $source;
	if($class_data['name'][0] == '_') return;
	?>

<?=strip_tags($class_data['desc'])?>


<?php
showArguments($class_data['args']);

showExample($class_data['example']);

if($class_data['methods']) {
	print "===Methods===\n\n";

	foreach($class_data['methods'] as $func) {
		showFunction($func, $class_data['name']);
	}
}
?>

<?php
}

function showFunction($func, $parent_class='') {
	global $source,$language;
	
	if($parent_class) $name = $parent_class . $source->tokenizer->language_tokens['T_CLASS_SEPERATOR'] . $func['name'];
	else $name = $func['name'];
	
	$argument_list = '(';
	$opened_optional_brace = 0;
	foreach($func['args'] as $arg) {
		$comma = ($argument_list == '(') ? '' : ','; //$comma is empty for the first argument

		$this_arg = "$comma $arg[name]";
		if($arg['optional']) {
			$opened_optional_brace++;

			if(isset($arg['default_value'])) $this_arg = "[$comma $arg[name] = $arg[default_value] "; // The final ] is missing ON PURPOSE.
			else $this_arg = "[$comma $arg[name] ";
		}
		
		$argument_list .= $this_arg;
	}
	// We keep the [ open until all arguments are filled - so that we can get an output like this - funcName(argument [, optional_1 [, optional_2 ] ])
	$argument_list .= str_repeat(' ]', $opened_optional_brace);
	
	$argument_list .= ' )';
	$name = $name . $argument_list;
	
	if($func['type'] == 'constructor') $name .= " [Constructor]";
?>
==<?=$name?>==

<?=strip_tags(handlePreTags($func['desc']), '<source>')?>


<?php showArguments($func['args']);

if($func['return']) { ?>
===Returns===

<?=$func['return'] . "\n"?>
<?php } ?>
<?php showExample($func['example']); ?>
===Code===

<source lang="<?=$language?>">
<?=$source->tokenizer->language_tokens['T_COMMENT'] . ' File ' . $source->file . ', Line ' . $func['line'] . "\n"?>
<?php
//str_replace("\n", "\n ", $func['code'])
print str_replace(array('&lt;', '&gt;', '&amp;'), array('<','>','&'), $func['code']);
?>
</source>

<?php
}


function showExample($example) {
	if(!trim($example)) return;
	global $source, $language;
?>
===Example===

<?php
	if(strpos($example, "<pre>") !== false) {
		$example = handlePreTags($example);
	} else {
		$example = '<source lang="'.$language.'">' . trim($example) . '</source>';
	}
	#print ' ' . str_replace("\n", "\n ", $example) . "\n";
	print trim($example) . "\n\n";
}

function showArguments($args_list) {
	if(!count($args_list)) return;
	print "===Arguments===\n\n";
	
	foreach($args_list as $arg) { ?>
;<?=$arg['name']?>
	
:<?=str_replace("\n", "\n:", strip_tags(handlePreTags($arg['desc']),'<source>'))?>

<?php 
		if($arg['type']) print ":'''Data Type: ''' $arg[type]\n";
		if($arg['optional']) {
			print ":''Optional Argument'' ";
			if(isset($arg['default_value'])) print " - if the argument is not provided, the function will use '$arg[default_value]' as the default value.";
			print "\n";
		}
	}
	
	print "\n";
}

function handlePreTags($text) {
	global $language;
	$text = str_replace(array('<br />','<br>'), array("\n","\n"), $text);
	$text = str_replace(array('&lt;', '&gt;', '&nbsp;', '&amp;'), array('<','>',' ','&'), $text);
	
	return preg_replace(
			array('/<pre[^>]*>(<code[^>]*>)?/', '/<code[^>]*>/', '/(<\/code>)?<\/pre>/', '/<\/code>/' ),
			array("\n<source lang='$language'>\n",'<source lang="'.$language.'">', "</source>\n", '</source>'), 
			$text);
}