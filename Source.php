<?php
class Source {
	///////////////////////////// Public Member Variables ////////////////////////////
	public $language;
	public $file;
	
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
	public $extractor;
	
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
 		
 		require("extractors/{$this->language}.php");
 		$this->extractor = new Extractor($this->tokenizer);		
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
}
