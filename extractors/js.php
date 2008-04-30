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
	function addFunction($token, $index, $parent_class='') {
		$function_details = array();
		
		$documentation = $this->getDocumentation($index);
		
		$function_details['name'] = '';
		if($this->tokens[$index+1]['token_type'] === 'T_WORD') {					// function hello()
			$function_details['name']	= $this->tokens[$index+1]['token'];
		
		} elseif(($index-1>=0 and ($this->tokens[$index-1]['token'] == '=' or $this->tokens[$index-1]['token'] == ':'))
				and $this->tokens[$index-2]['token_type'] === 'T_WORD') {			// var hello = function()
			$function_details['name']	= $this->tokens[$index-2]['token'];
		
		} elseif(($index-1>=0 and ($this->tokens[$index-1]['token'] == '=' or $this->tokens[$index-1]['token'] == ':'))
				and $this->tokens[$index-2]['token_type'] === 'T_STRING') {			// {"hello": function()
			$function_details['name']	= str_replace(array("'", '"'),array(),$this->tokens[$index-2]['token']);
		}
		
		$function_details['name'] = $this->getName(array("Function"), $function_details['name']);
		
		//If the function name is not valid,
		if(!preg_match('/^[\w\$]+$/',$function_details['name'])) return false; //Its propably an anoymous function.

		$function_details['type']	=  '';
		
		//Is this function a constructor?
		if($function_details['name'] == $parent_class || $function_details['name'] == "__construct") {
			$function_details['type'] = 'constructor'; 
			
		} elseif($function_details['name'] and $function_details['name'][0] == '_') return false; //Private function
		
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
}
