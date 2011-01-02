<style type="text/css">
* {font-style:Courier,Courier New,monospace;}
</style>
<?php

if(isset($_REQUEST['text'])) {
	$t = $_REQUEST['text'];
	
	$paragraphs = explode("\n", $t);
	
	function is_alpha($someString) { return (preg_match("/[A-Z\s_]/i", $someString) > 0) ? true : false; }
	function is_num($someString) { return (preg_match("/[0-9]/", $someString) > 0) ? true : false; }
	function is_cap($data) { return ord($data[0]) < 97; }
	
	function doWord($word) {
		
		if(empty($word))
			return '';
		
		$output = '';
		
		$lword = strtolower($word);
		
		$replace = array(
			'a' => 'uhh',
			'the' => 'zee',
			'this' => 'thees',
			'is' => 'ees',
			'an' => 'un',
			'i' => 'eee',
			'be' => 'buhh',
			'to' => 'tou',
			'if' => 'ouf',
			'it' => 'eet',
			'am' => 'oom',
			'of' => 'ouff',
			'on' => 'ern',
			'so' => 'soou',
			'no' => 'noou',
			'my' => 'mer',
			'he' => 'hehr',
			'in' => 'een'
		);
		
		if(is_alpha($word)) {
			
			if(isset($replace[$lword])) {
				$output = $replace[$lword];
				
				if(is_cap($word))
					$output = strtoupper($output[0]) . substr($output, 1);
				
				return $output;
			}
			
			if(strlen($word) <= 2)
				return $word;
			
			$output = str_replace('tion', 'shoon', $lword);
			$output = str_replace('ir', 'ur', $output);
			$output = str_replace('au', 'oo', $output);
			$output = str_replace('ow', 'oo', $output);
			$output = substr($output, 0, strlen($output) - 2) . str_replace('en', 'ee', substr($output, -2));
			$output = str_replace('f', 'ff', $output);
			$output = str_replace('v', 'f', $output);
			$output = str_replace('w', 'v', $output);
			$output = str_replace('pp', 'rp', $output);
			$output = str_replace('a', 'e', $output);
			$output = str_replace('u', 'oo', $output);
			
			$olen = strlen($output);
			$temp = '';
			for($x = 0; $x < $olen; $x++) {
				$xc = $output[$x];
				if($xc == 'o') {
					if(($x > 0 && $output[$x - 1] == 'o') || ($x < $olen - 1 && $output[$x + 1] == 'o')) {
						$temp .= 'o';
						continue;
					}
					$temp .= 'u';
					continue;
				}
				$temp .= $xc;
			}
			$output = $temp;
			
			if(substr($output, -1) == 'e')
				$output .= '-a';
				
			$output = str_replace('i', 'ee', $output);
			
			if(is_cap($word)) {
				$output = strtoupper($output[0]) . substr($output, 1);
			}
			
			return $output;
			
		} else {
			
			$chars = '-.,?!/\\()';
			for($i=0;$i<strlen($chars);$i++) {
				$c = $chars[$i];
				if(strpos($word, $c) !== false) {
					$word = explode($c, $word);
					foreach($word as $w)
						$output .= doWord($w);
					return $output;
				}
			}
			
			return $word;
			
		}
		
	}
	
	foreach($paragraphs as $p) {
		echo "<p>";
		$words = explode(' ', $p);
		
		$output = array();
		foreach($words as $word) {
			$output[] = doWord($word);
		}
		echo implode(' ', $output);
		
		echo " Bork bork bork!</p>";
	}
	
}

?>

<form action="?" method="post">
<textarea name="text"><?=(isset($t)?$t:'')?></textarea>
<input type="submit" value="Translate" />
</form>
