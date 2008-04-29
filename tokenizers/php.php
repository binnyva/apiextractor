<?php
require('BaseTokenizer.php');

class Tokenizer extends BaseTokenizer {
	public $language = 'php';
	public $language_keywords = array(
		'abstract'	=> 'T_ABSTRACT',
		'array' 	=> 'T_ARRAY',
		'as'		=> 'T_AS',
		'break'		=> 'T_BREAK',
		'case'		=> 'T_CASE',
		'catch'		=> 'T_CATCH',
		'class'		=> 'T_CLASS',
		'clone'		=> 'T_CLONE',
		'const'		=> 'T_CONST',
		'continue'	=> 'T_CONTINUE',
		'declare'	=> 'T_DECLARE',
		'default'	=> 'T_DEFAULT',
		'define'	=>	'T_DEFINE',
		'do'		=> 'T_DO',
		'echo'		=> 'T_ECHO',
		'else'		=> 'T_ELSE',
		'elseif'	=> 'T_ELSEIF',
		'empty'		=> 'T_EMPTY',
		'enddeclare'=> 'T_ENDDECLARE',
		'endfor'	=> 'T_ENDFOR',
		'endforeach'=> 'T_ENDFOREACH',
		'endif'		=> 'T_ENDIF',
		'endswitch'	=> 'T_ENDSWITCH',
		'endwhile'	=> 'T_ENDWHILE',
		'eval'		=> 'T_EVAL',
		'exit'		=> 'T_EXIT',
		'extends'	=> 'T_EXTENDS',
		'final'		=> 'T_FINAL',
		'for'		=> 'T_FOR',
		'foreach'	=> 'T_FOREACH',
		'function'	=> 'T_FUNCTION',
		'global'	=> 'T_GLOBAL',
		'if'		=> 'T_IF',
		'implements'=> 'T_IMPLEMENTS',
		'include'	=> 'T_INCLUDE',
		'include_once'=> 'T_INCLUDE_ONCE',
		'instanceof'=> 'T_INSTANCEOF',
		'interface'	=> 'T_INTERFACE',
		'isset'		=> 'T_ISSET',
		'list'		=> 'T_LIST',
		'and'		=> 'T_LOGICAL_AND',
		'or'		=> 'T_LOGICAL_OR',
		'xor'		=> 'T_LOGICAL_XOR',
		'new'		=> 'T_NEW',
		'print'		=> 'T_PRINT',
		'private'	=> 'T_PRIVATE',
		'public'	=> 'T_PUBLIC',
		'protected'	=> 'T_PROTECTED',
		'require'	=> 'T_REQUIRE',
		'require_once'=> 'T_REQUIRE_ONCE',
		'return'	=> 'T_RETURN',
		'static'	=> 'T_STATIC',
		'switch'	=> 'T_SWITCH',
		'throw'		=> 'T_THROW',
		'try'		=>	'T_TRY',
		'unset'		=> 'T_UNSET',
		'use'		=> 'T_USE',
		'var'		=> 'T_VAR', 
	);
	public $language_operators = array(
		'<?php'		=>	'T_OPEN_TAG',				// Maintain this ordering.
		'<?='		=>	'T_OPEN_TAG_WITH_ECHO',		//
		'<?'		=>	'T_OPEN_TAG',				// - this one must come last among the '<?' three
		'?>'		=>	'T_CLOSE_TAG',
		
		'->'		=>	'T_OBJECT_OPERATOR',
		'#'			=> 	'T_COMMENT',
		'//'		=>	'T_COMMENT',
		'::'		=>	'T_DOUBLE_COLON',
	);
	public $language_regexps = array(
		'line_beginning_char'	=> '\*'
	);
	public $language_tokens = array(
		'T_VARIABLE_PREFIX'	=> '$',
		'T_COMMENT'			=> '//',
		'T_CLASS_SEPERATOR'	=> '::',
	);

	/// Constructor
	function __construct($contents) {
		$this->language_code_start	= array('T_OPEN_TAG','T_OPEN_TAG_WITH_ECHO');
		$this->language_code_end	= array('T_CLOSE_TAG');
		$this->language_code_flag	= false;
		parent::__construct($contents, 'php');
	}
	
	function getNextToken() {
		if($this->last_token_type == 'T_EOF') return false;
		
		//If the code part has not started yet - for eg. <?php must be reached for the code to begin
		if(!$this->language_code_flag) {
			//Get a list of all tokens that start the code - like '<?php', '<?', '<?=' for php
			$code_starting_tokens = array();
			foreach($this->language_code_start as $starting_token_type) {
				foreach($this->language_operators as $token=>$token_type) {
					if($starting_token_type == $token_type) {
						$code_starting_tokens[] = $token;
					}
				}
			}
			
			//Find the nearest code starting token
			$nearest_starting_token_at = -1;
			foreach($code_starting_tokens as $token) { // Go thru each starting code,
				$token_at = strpos($this->contents, $token, $this->position); //Find the distance from current char to the starting code,
				if($token_at === false) continue; //Token not found

				if($token_at == 0) { //If its 0, check no further
					$nearest_starting_token_at = $token_at;
					break;
				}
				
				if($token_at < $nearest_starting_token_at  //If its lower than the lowest distance so far, set that as the nearest distance.
							or $nearest_starting_token_at == -1) { //First elements distance
					$nearest_starting_token_at = $token_at;
				}
			}
			$this->language_code_flag = true; //Once we find the starter token, the things after that is code. Even if we did not find a starter token, the EOF is treated as a code token
			
			$token_start_position = $this->position;
			
			if($nearest_starting_token_at == -1) {//The code starting code was not found.
				$this->position = $this->length;//Send end of file as the position - then T_EOF will be returned the next time
				return $this->_tokenDetails(substr($this->contents, $token_start_position), 'T_NOT_CODE'); //The stuff from current char to EOF is not code
			}
			
			$this->position = $nearest_starting_token_at; //Set the position as the position of the next code starting token. Next call will return a code starting token
			
			//If there is something between current position and the next start code token, return it first. In the next getNextToken() call, that will be nothing.
			if($nearest_starting_token_at != 0) {
				return $this->_tokenDetails(substr($this->contents, $token_start_position, $this->position-$token_start_position), 'T_NOT_CODE');
			}
			
			//If control reaches here, that means the next token is a start code token or EOF. So treat the rest as code.
		}
		
		//If the char is a whitespace, go thru the code until that block of whitespace is finised
		do {
			if ($this->position >= $this->length) return $this->_tokenDetails('', 'T_EOF');
			
			$c = $this->contents[$this->position];
			$this->position++;
			if ($c == "\n" or $c == "\r") $this->line++;
		} while (in_array($c, $this->whitespace));
		
		
		if (in_array($c, $this->word_chars)) { //Its a \w char - could be a identifier.
			if ($this->position < $this->length) {
				while (in_array($this->contents[$this->position], $this->word_chars)) {
					$c .= $this->contents[$this->position];
					$this->position++;
					if ($this->position == $this->length) break;
				}
			}
	
			// small and surprisingly unugly hack for 1E-10 representation
			if ($this->position != $this->length and preg_match('/^\d+[Ee]$/', $c) and $this->contents[$this->position] == '-') {
				$this->position += 1;
				list($next_word, $next_type) = $this->getNextToken(); // :RECURSION: :TODO: - make sure this works
				$c .= '-' . $next_word;
				return $this->_tokenDetails($c, 'T_WORD');
			}
			
			if(isset($this->language_keywords[$c])) return $this->_tokenDetails($c, $this->language_keywords[$c]); //Is it a keyword?
			//if($variable_prefix and $[0] == $this->variable_prefix) return $this->_tokenDetails($c, 'T_VARIABLE');
			return $this->_tokenDetails($c, 'T_WORD');
		}
	
		if ($c == '(' || $c == '[') {
			return $this->_tokenDetails($c, 'T_START_EXPR');
		}
	
		if ($c == ')' || $c == ']') {
			return $this->_tokenDetails($c, 'T_END_EXPR');
		}
	
		if ($c == '{') {
			return $this->_tokenDetails($c, 'T_START_BLOCK');
		}
	
		if ($c == '}') {
			return $this->_tokenDetails($c, 'T_END_BLOCK');
		}
	
		if ($c == ';') {
			return $this->_tokenDetails($c, 'T_END_COMMAND');
		}
	
		if ($c == '/') {
			// peek for comment /* ... */
			if ($this->contents[$this->position] == '*') {
				$comment_start_pos = $this->position - 1;
				$comment = '';
				$this->position += 1;
				if ($this->position < $this->length){
					while (!($this->contents[$this->position] == '*' && $this->contents[$this->position + 1] == '/') && $this->position < $this->length) {//If NOT end of comment
						$comment .= $this->contents[$this->position];
						$this->position += 1;
						if ($this->position >= $this->length) break;
					}
				}
				$this->position +=2; // to account for the final '*/'
				
				
				if(substr($this->contents, $comment_start_pos, 3) === '/**') {
					return $this->_tokenDetails("/*$comment*/", 'T_DOC_COMMENT');
				}
				else return $this->_tokenDetails("/*$comment*/", 'T_BLOCK_COMMENT');
			}
			// peek for comment // ...
			if ($this->contents[$this->position] == '/') {
				$comment_start_pos = $this->position - 1;
				$comment = $c;
				while ($this->contents[$this->position] != "\n" && $this->contents[$this->position] != "\r") {
					$comment .= $this->contents[$this->position];
					$this->position += 1;
					if ($this->position >= $this->length) break;
				}
				
				if(substr($this->contents, $comment_start_pos, 3) == '///') return $this->_tokenDetails($comment, 'T_DOC_COMMENT');
				return $this->_tokenDetails($comment, 'T_COMMENT');
			}
	
		}
	
		if ($c == "'" || // string
			$c == '"' || // string - :TODO: Should we give special consideration for this?
			($c == '/' && 
				(($this->last_token_type == 'T_WORD' and $this->last_token == 'return') 
				or ($this->last_token_type == 'T_START_EXPR' || $this->last_token_type == 'T_END_BLOCK' || $this->last_token_type == 'T_OPERATOR' || $this->last_token_type == 'T_EOF' || $this->last_token_type == 'T_END_COMMAND')))) { // regexp
			$sep = $c;
			$c   = '';
			$esc = false;
	
			if ($this->position < $this->length) {
	
				while ($esc || $this->contents[$this->position] != $sep) {
					$c .= $this->contents[$this->position];
					if (!$esc) {
						$esc = $this->contents[$this->position] == '\\';
					} else {
						$esc = false;
					}
					$this->position += 1;
					if ($this->position >= $this->length) break;
				}
	
			}
	
			$this->position += 1;
			
			return $this->_tokenDetails($sep . $c . $sep, 'T_STRING');
		}
		
		$original_pos = $this->position;
		//Match all possible custom operators to see if anyone fits this case.
		foreach($this->language_operators as $key=>$token_name) {
			if($c . substr($this->contents, $this->position, strlen($key)-1) == $key) {
				$this->position = $this->position + strlen($key)-1;
				return $this->_tokenDetails($key, $token_name);
			}
		}
		$this->position = $original_pos;
	
		//Stuff That was not caught anywhere else.
		if (in_array($c, $this->operators)) {
			while ($this->position < $this->length and in_array($c . $this->contents[$this->position], $this->operators)) {
				$c .= $this->contents[$this->position];
				$this->position += 1;
			
				if ($this->position >= $this->length) break;
			}
			return $this->_tokenDetails($c, 'T_OPERATOR');
		}
	
		return $this->_tokenDetails($c, 'T_UNKNOWN');
	}
}
