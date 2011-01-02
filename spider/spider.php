<?

error_reporting(E_ERROR | E_PARSE);
ignore_user_abort(TRUE);

$kod = $_REQUEST['kod']=='true';

$pages = array();
$queue = array();
$uniques = array();

$minimumDepth = 0;

class page {
	public $url = '';
	public $children = array();
	public $error = '';
	public $length = 0;
	public $hash = '';
	function page($url){
		$this->url = $url;
	}
}

function download($url,$timeout=5){
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt ($ch, CURLOPT_USERAGENT, 'Structure Spider/1.0');
	#curl_setopt ($ch, CURLOPT_VERBOSE, 1);
	#curl_setopt ($ch, CURLOPT_HTTPPROXYTUNNEL, TRUE);
	#curl_setopt ($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
	#curl_setopt ($ch, CURLOPT_PROXY,"http://proxy.shr.secureserver.net:3128");
	#curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt ($ch, CURLOPT_TIMEOUT, $timeout);
	curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, true);
	$output = curl_exec($ch);
	$response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	
	$response = array(
		'code'=>intval($response_code),
		'output'=>$output
	);
	
	return $response;
}
function getNodeAtt($node,$att){
	$na = $node->attributes;
	$a = $na->getNamedItem($att);
	return $a->nodeValue;
}
function setNodeAtt($node,$att,$value){
	$na = $node->attributes;
	$a = $na->getNamedItem($att);
	$a->nodeValue = $value;
}

function map($url) {
	global $pages, $queue, $domain, $kod, $uniques, $minimumDepth;
	if(isset($pages[$url]))
		return $pages[$url];
		
	$page = new page($url);
	
	$atdepth = count(explode('/',$url));
	if($minimumDepth>$atdepth) {
		$page->error = "Minimum depth not met.";
		return $page;
	}
	
	
	echo "<strong style=\"margin-top:20px;display:block;\">Downloading $url</strong><br />";
	$response = download($url);
	echo "Download Complete<br />";
	
	echo "Analyzing $url<br />";
	
	$def_protocol = 'http:';
	if(substr($url,0,8)=='https://')
		$def_protocol = 'https:';
	
	if($response['code']==200){
		
		$hash = md5($response['output']);
		if(in_array($hash, $uniques)){
			echo "<strong style=\"color:#b00\">Error: Page is duplicate</strong><br /><br />";
			$page->error = 'Duplicate';
			return $page;
		}
		$uniques[] = $hash;
		
		$page->hash = $hash;
		$page->length = strlen($response['output']);
		
		$dom = new DOMDocument();
		$dom->loadHTML($response['output']);
		
		$metas = $dom->getElementsByTagName('meta');
		foreach($metas as $meta){
			$meta_name = getNodeAtt($meta, 'name');
			if(strtoupper($meta_name)=='ROBOTS'){
				$rules = explode(',', getNodeAtt($meta, 'content'));
				$breakrule = false;
				foreach($rules as $rule){
					$content = strtoupper(trim($rule));
					if($content=='NOINDEX'||$content=='NOFOLLOW'){
						$page->error = 'No-index specified';
						return $page;
					}
				}
			}
		}
		
		echo "Finding links...<br />";
		$links = $dom->getElementsByTagName('a');
		foreach($links as $link){
			$href = getNodeAtt($link, 'href'); // Grab the URL
			$rel = getNodeAtt($link, 'rel'); // Grab the relationship
			
			if(strtolower($rel)=='nofollow')
				continue;
			
			if(empty($href))
				continue;
			$orig = $href; // Keep a copy of the original URL for reference
			
			// Simply ignore anchors
			if($href[0]=='#')
				continue;
			
			// Get rid of stupid addresses
			if(substr($href,0,7)=='mailto:')
				continue;
			if(substr($href,0,11)=='javascript:')
				continue;
			if(substr($href,0,3)=='ftp:')
				continue;
			
			if(strpos($href,'#')>0)
				$href = substr($href, 0, strpos($href,'#'));
			
			// Trim the trailing ampersands
			while(substr($href, strlen($href)-2, 1)=='&')
				$href = substr($href, 0, strlen($href) - 1);
			
			
			$obtained = false;
			
			// Determine the protocol
			$protocol = $def_protocol;
			if(substr($href,0,8)=='https://'){
				$protocol = 'https:';
				$href = substr($href, 6); // Strip off the protocol
				$obtained = true;
			} elseif(substr($href,0,7)=='http://'){
				$protocol = 'http:';
				$href = substr($href, 5); // Strip off the protocol
				$obtained = true;
			} elseif(substr($href,0,2)=='//') // Normalize for relative protocols
				$protocol = $def_protocol;
			
			// Remove any parent directory directives
			$href = preg_replace('/[^\/]*\/\.\.\//', '', $href);
			
			// Explode the URL by '/' without the relative protocol.
			$surl = explode('/',substr($url,strlen($def_protocol)+2));
			
			
			if(!$obtained){ // For those nasty relative links.
				
				// Determine relativity
				$isrel = false;
				if($href[0]=='/'){ // The post is either relative or on the same protocol (i.e.: /index.php or //google.com/)
					if(strlen($href)==1) // Reserved for '/'
						$href = '//'.$surl[0].'/';
					elseif($href[1]!='/')
						$href = '//'.$surl[0].$href;
				}else{
					// Handle really super-relative links
					// i.e.: href="file.txt"
					$shref = explode('/',substr($url, strlen($def_protocol)+2));
					$shref[count($shref)-1] = $href;
					$href = '//'.implode('/',$shref);
				}
				
			}
			
			// Give it it's protocol!
			$href = $protocol.$href;
			
			// Make sure the address doesn't already exist.
			if(in_array($href, $queue)||isset($pages[$href]))
				continue;
			
			
			
			$hurl = explode('/',substr($href,strlen($protocol)+2));
			
			
			$dom_full = explode('.',$hurl[0]);
			$domx = $dom_full[count($dom_full)-2].'.'.$dom_full[count($dom_full)-1];
			
			echo '  '.htmlentities($href).'<br />';
			if($orig!=$href)
				echo '    <small>Normalized from: '.htmlentities($orig).'</small><br />';
			
			$page->children[] = $href;
			$addtoqueue = false;
			if($kod){
				if($domx==$domain)
					$addtoqueue = true;
			}else
				$addtoqueue = true;
			
			if($addtoqueue)
				$queue[] = $href;
			
			setNodeAtt($link, 'href', md5($href).'.html');
		}
		
		// Save a copy with links intact!
		// TODO: Find a way to normalize the URLs of the images, CSS, etc. so nothing is broken.
		
		$dom->saveHTMLFile('save/'.md5($url).'.html');
		
	}else{
		echo "<strong style=\"color:#b00\">Error: {$response['code']}</strong><br /><br />";
		$page->error = $response['code'];
	}
	
	$pages[$url] =& $page;
	return $page;
}

$url = $_REQUEST['page'];

$protocol = 'http:';
if(substr($url,0,8)=='https://')
	$protocol = 'https:';
$surl = explode('/', substr($url,strlen($protocol)+2));
$domain_full = explode('.', $surl[0]);
$domain = $domain_full[count($domain_full)-2] . '.' . $domain_full[count($domain_full)-1];


echo '<pre>';
	
	echo '<strong>';
	echo $kod?'Keeping on domain':'Not keeping on domain';
	echo '</strong><br />';
	
	if(isset($_REQUEST['mdep']))
		$minimumDepth = count(explode('/',$url));
	
	$newmap =& map($url);
	
	if($newmap===false)
		echo '    <span class="red">Cannot be indexed.</span>';
	else
		$pages[$url] =& $newmap;
	

	while(current($queue)){
		$u = next($queue);
		$newmap =& map($u);
		if($newmap===false)
			echo '    <span class="red">Cannot be indexed.</span>';
		else
			$pages[$url] =& $newmap;
	}

echo 	'</pre>',
		'<textarea style="display:block;height:750px;width:60%;">',
		serialize($pages),
		'</textarea>';
?>
<div>

</div>