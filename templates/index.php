<?php 

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
	?><div class="class" id="class-<?=strtolower($class_data['name'])?>">
<h1><?=$class_data['name']?></h1>

<p><?=$class_data['desc']?></p>

<?php 
showExample($class_data['example']); 

if($class_data['methods']) {
	print "<h2>Methods</h2>\n<ul>\n";
	
	foreach($class_data['methods'] as $func) {
		$name = $func['name'];
	
		$link = "#function-" . strtolower((($class_data['name']) ? $class_data['name'] . '-' : '') . $func['name']); 
		?>
<li><a href="<?=$link?>"><?=$name?><?php
	if($func['type'] == 'constructor') print " [Constructor]";
?></a></li>
<?php
	}
	print "</ul>\n\n";
	

	foreach($class_data['methods'] as $func) {
		showFunction($func, $class_data['name']);
	}
}
?>
</div>
<?php
}

function showFunction($func, $parent_class='') {
	global $source;
	$language = $source->language;
	
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
<div class="function" id="function-<?=strtolower((($parent_class) ? $parent_class . '-' : '') . $func['name'])?>">
<h3><?=$name?></h3>

<p><?=$func['desc']?></p>

<?php if($func['args']) { ?>
<h4>Arguments</h4>

<dl>
<?php foreach($func['args'] as $arg) { ?>
<dt><?=$arg['name']?></dt>
<dd>
<p><?=$arg['desc']?><?php
if($arg['type']) print "<br /><strong>Data Type: </strong> $arg[type]<br />";
if($arg['optional']) {
	print "<br /><em>Optional Argument</em>";
	if(isset($arg['default_value'])) print " - if the argument is not provided, the function will use '$arg[default_value]' as the default value.";
}
?></p>
</dd>

<?php } ?>
</dl>
<?php } ?>

<?php if($func['return']) { ?>
<h4>Returns</h4>

<p><?=$func['return']?></p>
<?php } ?>

<?php showExample($func['example']); ?>

<div class="code">
<h4>Code</h4>
<pre><code class="<?=$language?>"><?=$source->tokenizer->language_tokens['T_COMMENT'] . ' File ' . $source->file . ', Line ' . $func['line'] . "\n"?>
<?=$func['code']?></code></pre>
</div>
</div>

<?php
}


function showExample($example) {
	if(!$example) return;
	global $source;
?>
<h4>Example</h4>

<?php 
	if(strpos($example, "<pre>") === false) print '<pre><code class="' . $source->language . '">' . $example . '</code></pre>';
	else print $example;
}