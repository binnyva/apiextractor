<?php
require('BaseExtractor.php');

class Extractor extends BaseExtractor {
	public $language = 'javascript';
	
	function __construct($tokenizer) {
		parent::__construct($tokenizer);
	}
	
	/**
	 * Search thru the tokens to find a class token
	 */
	function findClasses() {
		$this->_token_count = count($this->tokens);
		for($i=0; $i<$this->_token_count; $i++) {
			$token	= $this->tokens[$i]['token'];
			$type	= $this->tokens[$i]['token_type'];
			
			if($type == 'T_DOC_COMMENT') {
				if(preg_match('/Class\s*\:\s*/',$token)) { //The best way to get a class in JS would be to find it in the comments.
					$this->findClassDetails($this->tokens[$i], $i);
				}
			}
		}
	} 
	
	
	/// Adds a function to the list
	function getFunctionName($index) {
		$documentation = $this->getDocumentation($index);
		
		$function_name = '';
		if($this->tokens[$index+1]['token_type'] === 'T_WORD') {					// function hello()
			$function_name	= $this->tokens[$index+1]['token'];
		
		} elseif(($index-1>=0 and ($this->tokens[$index-1]['token'] == '=' or $this->tokens[$index-1]['token'] == ':'))
				and $this->tokens[$index-2]['token_type'] === 'T_WORD') {			// var hello = function()
			$function_name	= $this->tokens[$index-2]['token'];
		
		} elseif(($index-1>=0 and ($this->tokens[$index-1]['token'] == '=' or $this->tokens[$index-1]['token'] == ':'))
				and $this->tokens[$index-2]['token_type'] === 'T_STRING') {			// {"hello": function()
			$function_name	= str_replace(array("'", '"'),array(),$this->tokens[$index-2]['token']);
		}
		
		$function_name = $this->getName(array("Function"), $function_name);
		
		//If the function name is not valid,
		if(!preg_match('/^[\w\$]+$/',$function_name)) return false; //Its propably an anoymous function.

		return $function_name;
	}
}
