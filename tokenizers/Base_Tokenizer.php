<?php
abstract class Base_Tokenizer {
	protected $whitespace = array("\n","\t","\r"," ");
	protected $word_chars = array('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z','A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','0','1','2','3','4','5','6','7','8','9','_','$');
	protected $operators	= array('+','-','*','/','%','&','++','--','=','+=','-=','*=','/=','%=','==','===','!=','!==','>','<','>=','<=','>>','<<','>>>','>>>=','>>=','<<=','&&','&=','|','||','!','!!',',',':','?','^','^=','|=');
	
	protected $position = 0;
	protected $source_file;
	protected $contents = '';
	protected $last_token = '';
	protected $last_token_type = '';
	protected $length = 0;
	protected $line = 1;
	
	protected $language_code_start;	//The tokens that start the code - in PHP that will be T_OPEN_TAG (<?php)
	protected $language_code_end;		//The tokens that end the code - in PHP that will be T_CLOSE_TAG (? > - nospace)
	protected $language_code_flag = true;		//This will be true if we are in a code region - ie. if $language_code_start is reached and not $language_code_end.
	
	function __construct($contents, $language) {
		$this->contents = $contents;
		$this->length = strlen($this->contents);
		$this->language = $language;
	}
	
	/// Returns an array with all token in the source.
	function getAllTokens() {
		$original_pos = $this->position;
		
		$this->position = 0; // Start from the beginning
		$all_tokens = array();
		while(list($token, $token_type, $line_no, $char_index) = $this->getNextToken()) {
			if($token_type == 'T_EOF') break;
			$all_tokens[] = array(
				'token'	=> $token,
				'token_type'	=> $token_type,
				'line'	=> $line_no,
				'index'	=> $char_index,
				0		=> $token,
				1		=> $token_type,
				2		=> $line_no,
				3		=> $char_index
			);
		}
		$this->position = $original_pos;
		return $all_tokens;
	}
	
	//////////////////////////// protected functions /////////////////////////////
	protected function _tokenDetails($token, $token_type) {
		$this->last_token = $token;
		$this->last_token_type = $token_type;
		
		//If we find an code ending token, the next stuff will be marked as NOT code
		foreach($this->language_code_end as $ending_token_type) {
			if($ending_token_type == $token_type) {
				$this->language_code_flag = false;
			}
		}
		
		//Comments could have \n's in them - they throw off the line count - this fixes that issue.
		$current_line = $this->line;
		$new_line_count = substr_count($token, "\n") + substr_count($token, "\r");
		$this->line += $new_line_count;
		
		return array($token, $token_type, $current_line, $this->position-strlen($token));
	}
	
	private	function _error($str) {
		die($str);
	}
}
