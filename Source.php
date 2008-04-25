<?php
class Source {
	///////////////////////////// Public Member Variables ////////////////////////////
	public $language;
	public $file;
	public $info;
	
	///////////////////////////// Private Variables ////////////////////////
	private $contents;
	private $full_code_lines;
	private $t;
	private $extensions = array(
		'php'	=> array('php', 'php4','php5','inc'),
		'js'	=> array('js'),
// 		'perl'	=> array('pl', 'cgi','perl','pm'),
// 		'python'=> array('py'),
// 		'ruby'	=> array('rb', 'ruby','rhtml')
	);
	private $options = array(
		'tab_length'	=>	4
	);

	public $tokenizer; 						//This holds the instance of the Tokenizer class for the current language.
	private $_token_count = 0;
	private $_documentation_block = '';
	private $_used_documentations = array();
	
	/// The Construtor
	function __construct($file) {
		$path_parts = pathinfo($file);
		
		//:TODO: We need a better language-detection system than extension sniffing
		foreach($this->extensions as $lang=>$exts) {
			if(in_array($path_parts['extension'], $exts)) {
				$this->language = $lang;
				break;
			}
		}
		if(!$this->language) return $this->_error("Language with extension $path_parts[extension] not supported - yet");
		
		$this->contents = file_get_contents($file);
		$this->convertTabsToSpaces();
		
		$this->full_code_lines = split("\n", $this->contents);
		$this->file = $file;
		
		$this->info['function'] = array();
		$this->info['class'] = array();
		$this->info['variable'] = array();
		
		require("tokenizers/{$this->language}.php");
		$this->tokenizer = new Tokenizer($this->contents, $this->language);
		
		$this->tokens = $this->tokenizer->getAllTokens();
		
		$this->_token_count = count($this->tokens);
		for($i=0; $i<$this->_token_count; $i++) {
			$token	= $this->tokens[$i]['token'];
			$type	= $this->tokens[$i]['token_type'];
			
			if($type == 'T_CLASS') {
				$this->findClassDetails($this->tokens[$i], $i);
			}
		}
		// Get the functions in the file
		//$this->findFunctions(); /// :TODO: find the functions outside the classes
		
	}
	
	/**
	 * Finds the class at the given token
	 * Arguments:
	 * 	$token - The token at which the class was found.
	 *  $i - The token index.
	 */
	function findClassDetails($token, $i) {
		$class_details = array();
		
		$class_details['name'] = $this->tokens[$i+1]['token']; //To token after the keyword class, will be the name of the class.
		if($class_details['name'][0] == '_') continue; //Private classes(?!) - don't bother about them.
		$class_details['line'] = $token['line'];
		
		$related_documentation = $this->findDocumentationToken($i);
		
		$class_details['example'] = $this->getExample();
		//:TODO: See, Link
		$class_details['desc'] = $this->getDescription();
		//dump($class_details);
		
		$class_ends_at_token = $this->findMatchingBrace($i);
		
		//$class_details['member_variables'] = $this->findVariables($class_details['line'], $class_details['ending_line'], $class_details['name']);
		$class_details['methods'] = $this->findFunctions($i, $class_ends_at_token, $class_details['name']);
		
		if(count($class_details['methods'])) //The class is added to our class list only if it has some functions in it.
			$this->saveInfo('class', $class_details);

	}
	
	/// Get the functions in the given range
	function findFunctions($from_token=0, $to_token=-1, $parent_class = '') {
		if($to_token === -1) $to_token = $this->_token_count;
		
		$all_function_details = array();
		for($i = $from_token; $i < $to_token; $i++) {
			if($this->tokens[$i]['token_type'] === 'T_FUNCTION') {
				$function_details = $this->addFunction($this->tokens[$i], $i, $parent_class);
				if($function_details) array_push($all_function_details, $function_details);
			}
		}
		
		return $all_function_details;
	}
	
	/// Find all the variables :TODO:
	function findVariables() {
		$variable = array();
		$variable['name'] = $matches[2];
		$variable['type'] = $matches[1]; //static, public, etc.
		$variable['value'] = preg_replace('/\s*'.$this->current_syntax_keyword['assignment_operator'].'\s*(.+);?/', '$1', $matches[3]);
		$variable['desc'] = trim($desc);
		
		$variable['line'] = $line_no;
		
		$this->saveInfo('variable', $variable);
	}
	
	/// Adds a function to the list
	function addFunction($token, $index, $parent_class='') {
		$function_details = array();
		
		$documentation = $this->getDocumentation($index);
		
		$function_details['name']	= $this->tokens[$index+1]['token'];//:TODO: Get the name from the docs first - use that if present.
		$function_details['type']	=  '';
		
		//Is this function a constructor?
		if($function_details['name'] == $parent_class || $function_details['name'] == "__construct") {
			$function_details['type'] = 'constructor'; 
			
		} elseif($function_details['name'][0] == '_') return false; //Private function
		
		$function_details['line']	= $token['line'];
		
 		$function_details['args']	= $this->getArguments($index);
		$function_details['return']	= $this->getReturns();
		$function_details['example']= $this->getExample();
		
 		//:TODO:
 		//See, Link
 		
 		$function_details['desc'] = $this->getDescription();

		$function_ends_at_token = $this->tokens[$this->findMatchingBrace($index)];
		
		$function_details['code'] = $this->getCode($token['index'],$function_ends_at_token['index']+1); //Get the source of this function
		$function_details['code'] = $this->formatCode($function_details['code']);

		if(!$parent_class) $this->saveInfo('function', $function_details);
		else return $function_details;
	}
	
	/// Finds the documentation for this token. Not perfect yet.
	function findDocumentationToken($i) {
		//Get the documentation...
		$this->_documentation_block	= '';
		
		if($this->isDocumentationToken($i-1)) return $i-1;
		elseif($this->isDocumentationToken($i+1)) return $i+1;
		else {
			//Find the next { - see if there a doc block after that.
			$next_brace_at = $this->findNextBrace($i);
			if($this->isDocumentationToken($next_brace_at+1)) return $next_brace_at+1;
		}
		
		return -1; //We got nothin'
	}
	
	/// Returns true if the token given as an argument is an unused documentation string.
	function isDocumentationToken($token_index) {
		if($this->tokens[$token_index]['token_type'] == 'T_DOC_COMMENT' // If the given token is documentation - and,
					and !in_array($token_index-1, $this->_used_documentations)) { // it has not been used earlier
			// :TODO: Move these regexps to the $tokenizer->language_regexps
			$comments = preg_replace('/^\s*\/\*\*\s*\*/', '', $this->tokens[$token_index]['token']); //Remove the first /**
			$comments = preg_replace('/\s*\*\/\s*$/', '', $comments); //Remove the last */
			$comments = preg_replace('/[\t ]*\*/', '', $comments); // Removes the '*' at the beginning of each line.
			$comments = preg_replace('/\s*\/\/\//', '', $comments); //Remove the first ///
			
			$this->_documentation_block	= explode("\n", $comments); //Set that as the documentation for this class.
			$this->_used_documentations[] = $token_index; // Set this documentation as 'used' so that it will not be used anymore.
			return true;
		}
		return false;
	}
	
	/// Extracts just the documentation part using the given token and returns it.
	function getDocumentation($token_index) {
		$documentation_index = $this->findDocumentationToken($token_index);
		if($documentation_index == -1) return '';
		
		$documentation = preg_replace('/^\/\*\*(.+?)\*\/$/s',"$1",$this->tokens[$documentation_index]['token']);
		
		$docs = split("\n",$documentation);
		
		/*
		Line Beginners($line_beginning) are an intresting concept. Most of documentation comments will be formated like this...
			/**
			* This will set the template for the page - you can specify the file to be used as the template.
			* 		In most cases, this is an internal function. But it can be called by the user as well.
			* Argument : $template_file - This is the template file to be used - it must be kept in the template folder.
			* 			  $another_argument - something
		^^^^^^^
		|This much is the line beginner.
		This must be removed - without removing all the leading whitespaces.
		*/
		$found = false;
		$line_beginning = "";
		$line_beginning_char = $this->tokenizer->language_regexps['line_beginning_char']; //Could be * or # or //
		$desc = "";
		foreach ($docs as $l) {
			$l = preg_replace("/\t/",'    ',$l);//Converts tab to spaces :TODO: its hardcoded to 4 spaces
			if(preg_match('/^(\s*'.$line_beginning_char.'?\s*)[\$\w]/', $l, $matches) and !$found) { #Find the line beginner - something like '		* Hello world!' will have the line beginner '		* '
				$line_beginning = $matches[1];
				$found = true;
			}
			if(preg_match('/^(\s*'.$line_beginning_char.'?\s*)./',$l) and $line_beginning) {	#Now, remove the line beginner.
				$removed = substr($l,0,strlen($line_beginning));
				$removed = preg_replace('/[\s'.$line_beginning_char.'\:]/','', $removed);	# Just a small trick to make sure that nothing is lost.
				$l = $removed . substr($l,strlen($line_beginning));
			}
			$l = preg_replace('/^[\s\:'.$line_beginning_char.']*$/','', $l);
			
			if($l) $desc .= $l ."\n";
		}
		
		return trim($desc);
	}
	
	/// Divide the matches to an array with names
	function getParts($matches, $order) {
		$correct_order = array();
		
		// preg_match_all has on odd ordering of the matches - This part will make the array structure more to my liking
		for($i = 0; $i<count($matches[0]); $i++) {
			$correct_order[$i] = array();
			
			for($j=1; $j<=count($order); $j++) { //We start from 1 as the first element is the full match - we don't want that
				$name = $order[$j-1];
				
				if(is_array($matches[$j][$i])) {
					if($name === 'brace' || $name === 'declaration') $correct_order[$i][$name] = $matches[$j][$i][1]; //If its a brace, return the index of the brace - else return the match
					else $correct_order[$i][$name] = $matches[$j][$i][0];
				}
			}
		}
		return $correct_order;
	}
	
	/// Try to get a name from the documentation - like 'Function: helloWorld' . If none is provided, return the $default_name.
	function getName($labels, $default_name) {
		for($i=0; $i<count($this->_documentation_block); $i++) {
			foreach($labels as $lab) {
				if(preg_match("/^\s*$lab\s*\:\s*([^\n\r]+)/i", $this->_documentation_block[$i], $matches)) {
					array_splice($this->_documentation_block, $i, 1);
					return $matches[1];
				}
			}
		}
		return $default_name;
	}
	
	/// If there is a '{' at the char index, find the matching brace and return its index
	function findMatchingBrace($index) {
		$starting_brace = $this->findNextBrace($index);
		if($starting_brace == -1) return -1;
		
		$open_braces = 0;
		for($i=$starting_brace; $i<$this->_token_count; $i++) {
			if($this->tokens[$i]['token_type'] === 'T_START_BLOCK') $open_braces++;
			elseif($this->tokens[$i]['token_type'] === 'T_END_BLOCK') $open_braces--;
			
			if($open_braces === 0) { return $i;	}
		}

		return -1;
	}

	/// Find and return the next brace after the given index
	function findNextBrace($index) {
		for($i=$index; $i<$this->_token_count; $i++) {
			if($this->tokens[$i]['token_type'] === 'T_START_BLOCK') return $i;
		}
		return -1;
	}
	
	/// Returns the $contents from index $from to $to
	function getCode($from=0, $to=-1, $contents='') {
		if(!$contents) $contents = $this->contents;
		if($from === 0 and $to === -1) return $contents;

		return substr($contents, $from, $to-$from);
	}
	
	/// Returns the line number where the char index can be found
	function findLine($char_no, $code='') {
		$extra_lines = explode("\n", $this->getCode($char_no, $char_no+200, $code)); // Tring to get the first line to which the char belongs
		$line = $extra_lines[0]; //Get the function declaration line.
		
		$full_code = $this->full_code_lines;
		$no_of_lines = count($full_code);
		for($i=0; $i<$no_of_lines; $i++) {
			if(strpos($full_code[$i], $line)) { // The given line is at this line
				return $i + 1;
			}
		}
	}
	
	/// Get the description from the given documentation lines
	function getDescription() {
		//This happens only after all the labeled parts are removed - so only the description is left.
		$documentation = trim($this->removeUnwantedLeadingSpace(implode("\n", $this->_documentation_block)));
		
		$documentation_lines = explode("\n", $documentation);
		$final_description = "";
		foreach($documentation_lines as $l) {
			if(ltrim($l) == $l) { //No leading whitespace
				$final_description .= $l . ' ';
			} else { //There is leading whitespaces.
				$final_description .= "<br />" . str_replace("  ", " &nbsp;", $l);
			}
		}
		
		return $this->text2html($final_description);
	}
	
	/// Extract and split the lines as needed.
	function getSection($regexp) {
		$lines = $this->isolateSection($regexp);

		return $this->splitLines(explode("\n",$lines));
	}
	
	/// Extract just one section - begins with $regexp that is given as the param
	function isolateSection($regexp) {
		$section = '';
		$found = 0;
		$beginning_line = 0;
		$ending_line = -1;
		
		for($i=0; $i<count($this->_documentation_block); $i++) {
			$l = $this->_documentation_block[$i];
			if(preg_match("/$regexp/i", $l) and !$found) { #Find the section beginner
				$found = 1;
				$beginning_line = $i;
			} elseif($found) { #If our section has started, 
				if(preg_match('/^\s*[\w\s]+\s*\:/', $l)) {
					$ending_line = $i;
					break; #and some other section is starting up - then the section has ended - get out of the loop
				}
			}
	
			if($found) {
				if($l) $section .= $l ."\n";
			}
		}
		
		// Remove the found part from the whole documentation block - its extracted
		if($found) {
			if($ending_line == -1) $ending_line = count($this->_documentation_block);
			
			array_splice($this->_documentation_block, $beginning_line, $ending_line - $beginning_line);
		}
		
		// We have to set the removed section title as whitespace. 
		$section_without_header = preg_replace("/$regexp/", '', $section);
		$removed_length = strlen($section) - strlen($section_without_header);
		$section = str_repeat(' ', $removed_length) . $section_without_header;

		return $section;
	}
	
	/// Get the argument details of the functions
	function getArguments($index) {
		$arg_text = $this->getSection("Arg(ument)?s?\\s*[\-:]");
		$real_arguments = $this->getFormalArguments($index);
		
		if(!$arg_text and !$real_arguments) return array();
		$args = array();
		
		foreach ($arg_text as $l) {
			$text = trim($l);
			if(!$text) continue;
			
			$info = array();
			$info['optional'] = false;
			if(preg_match('/\[opt(ional)?\]/i', $text)) $info['optional'] = true; #If [optional] is present, that argument is optional
			$text = preg_replace('/\[opt(ional)?\]/i','', $text);
			
			$seperator = '';
			
			preg_match('/^([\$\w\.\[\]\'\"]+)?	#First, get the name of the $argument - some may not have it.
				\s*								#Optional space
				([\[\{\(][\w\s]+[\)\}\]])?		#Type - optional
				\s*
				([\-\=\:])?\s*		#A seperator
				(.+)$				#The description
			/x', $text, $matches);

			$info['name'] = trim($matches[1]);
			$info['type'] = $matches[2];
			$seperator	  = $matches[3];
			$info['desc'] = $matches[4];
			
			//See if its a multiline documentation - the information about 1 argument can be spread over many lines.
			$multiline_doc = false;
			if(!$info['name'] and $seperator) {
				$multiline_doc = true;
			}
			
			$info['type'] = preg_replace('/[\(\)\{\}\[\]]/', '', $info['type']); #Remove the brackets
			if(!$seperator) { #If the seperator is not there, the name is not present - its just the description
				$info['desc'] = $info['name'] . ' ' . $info['desc'];
				$info['name'] = '';
			}

			//Try to find the default value if its an optional argument
			if(stripos($info['desc'], 'default value is') !== false) {
				preg_match('/default value is([^\.]+)/i', $info['desc'], $value_matches);
				$info['default_value'] = trim($value_matches[1]);
			}
			elseif(stripos($info['desc'], 'defaults to') !== false) {
				preg_match('/defaults to([^\.]+)/i', $info['desc'], $value_matches);
				$info['default_value'] = trim($value_matches[1]);
			}
			elseif(stripos($info['desc'], 'is the default value') !== false) {
				preg_match('/([^\.]+) is the default value/i', $info['desc'], $value_matches);
				$info['default_value'] = trim($value_matches[1]);
			}
			
			$info['desc'] = $this->guessDescriptionIfEmpty($info);
		
			if($multiline_doc) $args[count($args)-1]['desc'] .= '<br />' . $info['desc']; //If its a multiline argument, add it to the previous description.
			else $args[] = $info;
			//showInfo($info);
		}
		
		//Go thru the real argument list and find the names of the arguments that are not in the documentation
		$i = 0;
		foreach($real_arguments as $arg_name=>$default_value) {
			if(!isset($args[$i])) {
				$args[$i] = array();
				$args[$i]['name'] = $arg_name;
			}
			$args[$i]['optional'] = false;
			if($default_value !== false) {
				$args[$i]['optional'] = true;
				if(!isset($args[$i]['default_value'])) $args[$i]['default_value'] = $default_value;
			}
			
			$args[$i]['desc'] = $this->guessDescriptionIfEmpty($args[$i]);
			$args[$i]['type'] = '';
			$i++;
		}
		
		return $args;
	}
	
	/// Get the formal arguments of a function whose token index is given as the argument.
	function getFormalArguments($index) {
		$paranthesis_start_at = -1;
		
		//First get the position of the '(' that marks the beginning of the argument list
		for($i=$index; $i<$this->_token_count; $i++) {
			if($this->tokens[$i]['token_type'] === 'T_START_EXPR') {
				$paranthesis_start_at = $i;
				break;
			}
		}
		
		if($paranthesis_start_at == -1) return array(); //( not found - is this possible?
		
		$arguments = array();
		$last_argument = '';
		for($i=$paranthesis_start_at+1; $i<$this->_token_count; $i++) {
			$tok = $this->tokens[$i];
			
			if($tok['token_type'] === 'T_WORD') {
				$arguments[$tok['token']] = false; // Means that this argument don't have a default value
				$last_argument = $tok['token'];
				
			} elseif($tok['token'] === '=') { // Default arugment
				$open_paranthesis = 0;
				$default_value = '';
				//Find the default value - it could be a collection of tokens after '='				
				while($i<$this->_token_count) {
					$i++;
					$token = $this->tokens[$i];
					
					if($open_paranthesis === 0 and 
							($token['token'] === ',' or //Its a coma - starting next argument 
							$token['token_type'] === 'T_END_EXPR')) { //) - end of all arguments
						$i--; //The for loop needs this token
						break;
					} else {
						$default_value .= $token['token']; //Append this to our default argumnet list.
					}

					// What if an array is a default argumnet - make sure there can be '(' and ')' in the default argument				
					if($token['token_type'] === 'T_START_EXPR')		$open_paranthesis++; // A new ( is opening up
					elseif($token['token_type'] === 'T_END_EXPR')	$open_paranthesis--; // The open ( is closed with a ')'

					$arguments[$last_argument] = $default_value; //Add the default value as the value of the argument

				}
			}
			
			if($tok['token_type'] === 'T_END_EXPR') break; // ) reached. End of argument list.
		}
		
		return $arguments;
	}
	
	/// If no description is provided, this function will try to guess a description baised on the given name.
	function guessDescriptionIfEmpty($info) {
		extract($info);
		
		if(!isset($desc) or !trim($desc)) {
			// Some fullforms for programmer shorthand
			$name = preg_replace(	array("/\bregexp\b/", "/\bstr\b/", "/\barr\b/", "/\bre\b/", "/\bfunc\b/", "/\barg\b/", "/\bargs\b/", "/\bele\b/", "/\bcls\b/"),
									array("regular expression", "string", "array", "regular expression", "function", "argument", "arguments", "element", "class"),
									strtolower($name));
			$name = str_replace($this->tokenizer->language_tokens['T_VARIABLE_PREFIX'], '', $name);
			$desc = 'The ' . format($name);
		}
		
		return $desc;
	}
	
	/// Get the returns - just 1 line - so no confusion
	function getReturns() {
		$ret_text = $this->getSection("Returns?\\s*:");

		return trim(implode(' ', $ret_text));
	}
	
	/// Get the provided examples - if any
	function getExample() {
		$example = preg_replace("/^\s*[\n\r]+/","",$this->isolateSection("Examples?\\s*:"));
		$example = preg_replace("/[\n\r]+\s*$/","",$example);
		
		return $this->removeUnwantedLeadingSpace($example, 0);
	}

	/**
	 * Make the line splitting correct. Say the documentation goes like this...
	 * 			   $one - The first argument, its such a long description so
	 *							we break it into 2 lines
	 *			   $second - This is the second argument
	 * This will be converted to an array like this...
	 * ['$one - The first argument, its such a long description so we break it into 2 lines', '$second - This is the second argument']
	 */
	function splitLines($lines) {
		$normal_whitespaces_count = $this->getNormalWhitespaceCount($lines);

		$new_lines = array();
		foreach ($lines as $l) {
			if(!trim($l)) continue;

			$whitespaces_count = $this->getWhitespaceCount($l);
			if($whitespaces_count > $normal_whitespaces_count) { #If there is more whitespaces than normal, it must me a continuation of the last line
				$new_lines[count($new_lines)-1] .= ' ' . $l;
			} else {
				$new_lines[] = $l;
			}
		}

		return $new_lines;
	}
	
	/// Find the maximum number of leading whitespaces in any section
	function getNormalWhitespaceCount($arg_text) {
		if(count($arg_text) <= 2) return 100; #If there is just 2 lines, there is no point persuing this. So return a Big Number(TM)
		$all_counts = array();
		foreach ($arg_text as $l) {
			if(!trim($l)) continue;
			$spaces = $this->getWhitespaceCount($l);
			
			if(!isset($all_counts[$spaces])) $all_counts[$spaces] = 0;
			$all_counts[$spaces]++;
		}
		$max=0;
		$whitespaces_count = 0;
		
		foreach ($all_counts as $key=>$value) {
			if($value > $max) {
				$max = $value;
				$whitespaces_count = intval($key);
			}
		}
		
		return $whitespaces_count;
	}
	
	/// Get the number of whitespaces at the beginning - after converting the tabs to 4 spaces
	function getWhitespaceCount($text) {
		preg_match('/^(\s*)/', $text, $matches);
		$spaces = str_replace("\t",'    ', $matches[1]);
		return strlen($spaces);
	}
	
	/// This will remove the extra whitespace at the beginning of each line.(except the first line)
	function removeUnwantedLeadingSpace($text, $start_at = 1) {
		if(!strlen($text)) return "";
		
		$lines = explode("\n", $text);
		$min_space = 100;
		for($i=$start_at; $i<count($lines); $i++) {
			if(!trim($lines[$i])) continue; //Empty line
			$whitespace_count = $this->getWhitespaceCount($lines[$i]);
			if($min_space > $whitespace_count) $min_space = $whitespace_count;
		}
		
		$final_text = array();
		if($start_at == 1) $final_text[] = $lines[0];
		for($i=$start_at; $i<count($lines); $i++) {
			$final_text[] = substr($lines[$i], $min_space);
		}
		
		return implode("\n", $final_text);
	}
	
	/// Format the code so that it could be used in an HTML page
	function formatCode($text) {
		$text = str_replace(array("&", "<", ">"), array("&amp;", "&lt;", "&gt;"), $text);
		
		return $this->removeUnwantedLeadingSpace($text);
	}
	
	/// Convert text to HTML - correct links, do some markup.
	function text2html($text) {
		$text = preg_replace(
			'/([^\'\"\=\/]|^)(http:\/\/|(www.))([\w\.\-\/\\\=\?\%\+\&\:]+?)([\.\?])?(\s|$)/',
			"$1<a rel='nofollow' href='http://$3$4'>http://$3$4</a>$5$6",
			$text);
		return $text;
	}
	
	/**
	 * Converts tabs to the appropriate amount of spaces while preserving formatting
	 * Link: Based on http://aidanlister.com/repos/v/function.tab2space.php
	 */
	function convertTabsToSpaces() {
		$lines  = explode("\n", $this->contents);
 
	    foreach ($lines as $line) {
	 
	        // Break out of the loop when there are no more tabs to replace
	        while (false !== $tab_pos = strpos($line, "\t")) {
	 
	            // Break the string apart, insert spaces then concatenate
	            $start = substr($line, 0, $tab_pos);
	            $tab   = str_repeat(' ', $this->options['tab_length'] - $tab_pos % $this->options['tab_length']);
	            $end   = substr($line, $tab_pos + 1);
	            $line  = $start . $tab . $end;
	        }
	 
	        $result[] = $line;
	    }
 
	    $this->contents = implode("\n", $result);
	}
	
	/// Save the info to a member variable array
	function saveInfo($type, $data) {
		$this->info[$type][] = $data;
	}
	
	/// Do all the processing necessary before display - like sorting.
	function prossessInfo() {
		uasort($this->info['function'], array($this, '_lineCompare'));
		uasort($this->info['variable'], array($this, '_lineCompare'));
	}
	
	/// A private to compare the line numbers
	private function _lineCompare($a, $b) {
		if($a['line'] < $b['line']) return -1;
		if($a['line'] > $b['line']) return 1;
		return 0;
	}
	
	######### Debug functions ############
	function showInfo($info) {
		print "Name : " . $info['name'] . "\n";
		print "Type : " . $info['type'] . "\n";
		print "Desc : " . $info['desc'] . "\n";
		print "Opt  : " . $info['optional'] . "\n\n";
	}
		function display() {
		print_r($this->info);
	}
	function _error($str) {
		die($str);
	}
} 

// :TODO: Construtor has special consideration