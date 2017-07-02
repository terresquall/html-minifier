<?php
/*
HTML Minifier
=============
This is a static PHP class that minifies HTML. To use it, call HTMLMinifier::process() and pass your
HTML into it. You can also initialise it so that you can use the function non-statically. Either way
is fine.

@author		Terence Pek <mail@terresquall.com>
@website	www.terresquall.com
@version	2.0.2
@dated		02/07/2017
@notes		Force comment cleaning for 'all_whitespace' compression mode. 
			Fixed a bug with script tags inside conditional comments.
			Added a caching function for v2.0.1.
			- Removed pretty indents option (since it is not done yet).
*/
class HTMLMinifier {
	
	public static $CacheFolder = ''; // Set this at the end of the file. If empty, there will be no caching.
	public static $CacheExpiry = 86400; // Time in seconds. 86400 is 1 day.
	
	const VERSION = '2.0.2';
	const SIGNATURE = 'Original size: %d bytes, minified: %d bytes. HTMLMinifier: www.terresquall.com/web/html-minifier.';
	const CACHE_SIG = 'Server cached on %s.';
	
	static $Signature; // Signature processed by sprintf() is placed here.
	
	// This array contains the regular expressions for comment removal functionality in this class.
	static $RegexArray = array(
		// Things that you don't capture.
		'string_single' => "\\'.*?\\'",
		'string_double' => '\\".*?\\"',
		'css_url_function' => 'url\\([\\s\\S]*?\\)',
		
		// Things that are captured and removed.
		'comment_single' => '//[\\s\\S]*?\\R',
		'comment_multi' => '/\\*[\\s\\S]*?\\*/'
	);
	static $RegexString = false; // String version of the regex array.
	
	// For documentation.
	static $CompressionMode = array(
		'none' => 'None',
		//'pretty_indent' => 'Pretty indent',
		'all_whitespace_not_newlines' => 'All whitespace except newlines',
		'all_whitespace' => 'All whitespaces'
	);
	
	static $Defaults = array(
		// General options.
		'clean_html_comments' => true,
		'show_signature' => true, // Show signature at the end.
		
		// Stylesheet optimisations.
		'clean_css_comments' => true,
		'shift_link_tags_to_head' => false,
		'shift_style_tags_to_head' => false,
		'combine_style_tags' => false,
		
		// Javascript optimisations.
		'clean_js_comments' => true,
		'remove_comments_with_cdata_tags' => false,
		'compression_ignore_script_tags' => true,
		'shift_script_tags_to_bottom' => false,
		'combine_javascript_in_script_tags' => false,
		
		// How do you want to compress the script?
		'compression_mode' => 'all_whitespace_not_newlines',
		'compression_ignore_css' => true // Not used.
	);
	
	public function __construct() { throw new Exception("Please don't try to initialise the HTMLMinifier class! Use it as a static class."); }
		
	// This is THE function that you call when you use this.
	// Refer to self::$Defaults for what to fill $options with.
	public static function process($html,$options = null,$cache_key = '') {
		
		// If cache key is provided, try to retrieve from cache first.
		if($cache_key) {
			$out = HTMLMinifier::cache($cache_key);
			if($out !== false) {
				echo $out;
				return;
			}
		}
				
		// Let's start processing our stuff here.
		$startLen = strlen($html); // For counting saved space.
		
		if($options !== null && is_array($options)) $options = array_merge(self::$Defaults,$options);
		else $options = self::$Defaults;
		
		// Force 'clean_js_comments' and 'clean_css_comments' if the compression mode is all whitespace.
		if($options['compression_mode'] === 'all_whitespace') {
			$options['clean_js_comments'] = true;
			$options['clean_css_comments'] = true;
		}
		
		// Pull out all the conditional code.
		$cond_scripts = array(); // Scripts inside IE conditional tags.
		preg_match_all('@(<\\!(?:--)?\\[if [\s\S]*?\\]>(?:<\\!-->)?)([\s\S]*?)((?:<!--)?\\<!\\[endif\\](?:--)?\\>)@i',$html,$cond_scripts);
		
		// Pull out all the script tags.
		$scripts = array();	
		$html = self::clean_script_tags($html,$scripts); // Remove script tags because DOMDocument doesn't process them properly.
		
		// Check if any of the scripts we have are in conditional tags.
		foreach($scripts as $ks => $s) {
			foreach($cond_scripts[2] as $kc => $c) {
				$strpos = strpos($c,$s[0]);
				if($strpos > -1) {
					$scripts[$ks][1] = $cond_scripts[1][$kc] . $scripts[$ks][1];
					$scripts[$ks][3] .= $cond_scripts[3][$kc];
					$scripts[$ks][0] = $scripts[$ks][1] . $scripts[$ks][2] . $scripts[$ks][3];
				}
			}
		}
		
		// Read the entire HTML file into DOM.
		libxml_use_internal_errors(true); // Suppresses errors for HTML5 tags.
		$dom = new DOMDocument();
		$dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
		
		// Remove HTML comments.
		if($options['clean_html_comments']) {
			$xpath = new DOMXPath($dom);
			foreach($xpath->query('//comment()') as $comment) {

				// If it is an if block, don't remove.
				if(preg_match('@^\\[if [\\s\\S]*?\\]@',$comment->nodeValue) || preg_match('@\\<\\!\\[endif\\]$@',$comment->nodeValue))
					continue;

				$comment->parentNode->removeChild($comment);
			}
		}
		
		self::process_stylesheet_options($dom,$options);
				
		// If we are ignoring compression of script content, then compress first before putting script tag contents back.
		$out = $dom->saveHTML();
		if($options['compression_ignore_script_tags']) {
			$out = self::compress($out,$options['compression_mode']);
			$out = self::process_script_options($out,$scripts,$options);
		} else { // Otherwise, put the script contents back, then compress the whole thing.
			$out = self::process_script_options($out,$scripts,$options);
			$out = self::compress($out,$options['compression_mode']);
		}
		
		// Remove empty conditional tags.
		$out = preg_replace('@ ?<\\!(--)?\\[if [\s\S]*?\\]>(?:<\\!-->)?(\s*?)(<!--)?\\<!\\[endif\\](--)?\\> ?@i','',$out);
		
		// Signs off before the HTML closing tag.
		if($options['show_signature']) {
			self::$Signature = sprintf(self::SIGNATURE, $startLen, strlen($out));
			$pos = strrpos($out,'</html>');
			
			// Terminate if the </html> tag is missing.
			if($pos === false) {
				trigger_error('HTMLMinifier::process(): Missing &lt;/html&gt; tag.');
				return $out;
			}
			
			$out = substr_replace($out,'<!-- ' . self::$Signature . ' --></html>',$pos,7);
		}
		
		// Cache a copy if $cache_key is provided.
		if($cache_key) HTMLMinifier::cache($cache_key,$out);
		
		return $out;
	}
	
	// This is the function used for both caching and retrieving cached views. If only the first argument is passed, it
	// RETRIEVES a cached file. If the second argument is passed, it CACHES a file (unless the argument is false).
	// $path - Key in the absolute path of the URL.
	// $arg - Content that you want to cache the file with.
	public static function cache($path,$arg = null) {
		
		$hash = md5($path);
		$fullpath = self::$CacheFolder . $hash . '.html';

		// Check if we can write into the folder.
		if(!is_writable(self::$CacheFolder)) {
			trigger_error("HTMLMinifier::cache(): Assigned folder for storing the cached file '" . self::$CacheFolder . "' is not writeable or does not exist.");
			return false;
		}
		
		if($arg && gettype($arg) === 'string') {
			$arg = str_replace(self::$Signature,self::$Signature . ' ' . sprintf(self::CACHE_SIG,date('d M Y')),$arg);
			return file_put_contents($fullpath,$arg);
		} else {
			if(!file_exists($fullpath)) return false;
			elseif(!is_readable($fullpath)) { // This is just to notify the user that the cache is not working.
				trigger_error("HTMLMinifier::cache(): The cached file '$fullpath' is not readable so the cache is not working.");
				return false;
			}
			
			// Check for expiry.
			if(self::$CacheExpiry > 0 && (is_null($arg) || $arg === true)) {
				if(time() - filectime($fullpath) > self::$CacheExpiry) {
					if(!unlink($fullpath))
						trigger_error("HTMLMinifier::cache(): Unable to delete old cached file of '$path'.");
					return false;
				}
			}
			
			return file_get_contents($fullpath);
		}		
	}
	
	private static function process_stylesheet_options($dom, $options) {
		// Remove all comments from all style tags.
		if($options['clean_css_comments']) {
			$style = $dom->getElementsByTagName('style');
			if($style->length > 0){
				foreach($style as $src) {
					$new_text = self::removeComments($src->nodeValue);
					$src->nodeValue = '';
					$src->appendChild($dom->createTextNode($new_text));
				}
			}
		}
		
		// Shift all detected style / link tags to the head of the document.
		if($options['shift_style_tags_to_head'] || $options['shift_link_tags_to_head']) {
			$head = $dom->getElementsByTagName('head')->item(0);
			
			if($head) {
				
				// Moves all stylesheet link tags to the head.
				if($options['shift_link_tags_to_head']) {
					$links = $dom->getElementsByTagName('link');
			
					if($links->length > 0) {
						$to_move = array();
						
						foreach($links as $link) {
							$rel = $link->attributes->getNamedItem('rel');
							if($rel->nodeValue === 'stylesheet')
								array_push($to_move,$link); // Cannot move the node in the loop as it messes up the loop.
						}
						
						foreach($to_move as $t) $head->appendChild($t->parentNode->removeChild($t));
					}
				}
				
				// Moves all style tags to head.
				if($options['shift_style_tags_to_head']) {
					$style = $dom->getElementsByTagName('style');
					if($style->length > 0) {
						
						// If style tags need to be combined.
						if($options['combine_style_tags']) {
							
							$style_concat = array();
							while($style->length > 0) {
								$src = $style->item(0);
								$media = $src->getAttribute('media');
								
								// Select the correct array container to put this in.
								if(!$media) $media = 0;
								if(!isset($style_concat[$media])) $style_concat[$media] = array();
								
								// Add the style into the array.
								$style_concat[$media][] = $src->nodeValue;
								$src->parentNode->removeChild($src); // Delete the old style tag.
							}
							
							foreach($style_concat as $k => $substyle) {
								// Create the style element and add it to the head tag.
								$new_style = $dom->createElement('style');
								if($k !== 0) $new_style->setAttribute('media',$k);
								$new_style->appendChild($dom->createTextNode(implode(PHP_EOL,$substyle)));
								$head->appendChild($new_style);
							}
							
						} else {
							$to_move = array();
							foreach($style as $src)
								array_push($to_move,$src);
							
							foreach($to_move as $t) $head->appendChild($t->parentNode->removeChild($t));
						}
					}
				}
			}
		}
	}
	
	// Remove the content of script tags in the $dom.
	// UPGRADE: Can consider adding a functionality to check whether there are the same number of open and close script tags.
	private static function clean_script_tags($html,&$scripts) {
		$matches = array();
		$l = preg_match_all('%(\\<script\\s?[\\s\\S]*?\\>)([\\s\\S]*?)(\\</script\\>)%i',$html,$matches);
		for($i=0;$i<$l;$i++) {
			// Ignore if there is an <src> attribute.
			//if(preg_match('%src\\=(\'[\\S]*\'|"[\\S]*")%i',$matches[1][$i])) continue;
			array_push($scripts,array($matches[0][$i],$matches[1][$i],$matches[2][$i],$matches[3][$i]));
			$html = str_replace($matches[0][$i],$matches[1][$i].$matches[3][$i],$html);
		}
		
		return $html;
	}
	
	// $scripts is the content of all script tags without src.
	private static function process_script_options($html,$scripts,$options) {
		
		// Remove all Javascript comments from the script tags.
		if($options['clean_js_comments']) {
			if(count($scripts) > 0){
				foreach($scripts as $k => $src)
					$scripts[$k][2] = self::removeComments($src[2]);
			}
		}
				
		// Shift all detected script tags to the foot of the document (after formatting them).
		$matches = array();
		$l = preg_match_all('%(\\<script\\s?[\\s\\S]*?\\>)(\\</script\\>)%i',$html,$matches);
		
		// For detecting errors in reinjection.
		if($l !== count($scripts)) die('HTMLMinifier::process_script_options() script error: Number of input and output script tags are different.');
		
		if($options['shift_script_tags_to_bottom']) {
			
			if($l > 0) {
				
				$script_js = array(); // Scripts that can be combined into one tag.
				$script_others = array(); // Scripts that have to be kept distinct.
				
				// Remove all <script> tags that do not have an SRC attribute, or do not contain Javascript.
				// Also preps the data into different arrays for easier organisation later.
				for($i=0;$i < $l;$i++) {
					
					// Remove all of the old <script> tags as they will be moved to the bottom.
					$pos = strpos($html,$matches[0][$i]);
					$html = substr_replace($html,'',$pos,strlen($matches[0][$i]));
					
					// If this is a conditional script tag, classify it.
					if(preg_match('/^<\\!(--)?\\[if/i',$scripts[$i][1])) {
						$script_others[] = $i;
						continue;
					} 
					
					// Read tag contents as XML for easier pulling of attributes.
					$xml = simplexml_load_string($matches[0][$i]);
					
					// Re-append the contents of the script tag.
					$type = strval($xml['type']);
					
					// Categorise the script tags for easier processing later.
					if((!$type || $type === 'text/javascript') && !$xml['src']) {
						$script_js[] = $i;
					} else {
						$script_others[] = $i;
					}
				}
				
				// Warn and terminate if there is no </body> tag.
				$body = strrpos($html,'</body>');
				if($body === false) {
					trigger_error('HTMLMinifier::process_script_options(): Your HTML document is malformed. It either has a &lt;body&gt; tag that is not closed, or no &lt;body&gt; tag. Minification stopped.');
					return $html;
				}
				
				// Start processing otherwise.
				if($options['combine_javascript_in_script_tags']) {
					
					// Append all distinct scripts (e.g. type="text/html" or others) onto the document.
					foreach($script_others as $i) {
						$html = substr_replace($html,$scripts[$i][1] . $scripts[$i][2] . $scripts[$i][3] .  '</body>',$body,7); // Add script before the end of body tag.
						$body = strrpos($html,'</body>');
					}
					
					// Create a single script tag and paste it at the end.
					$new_script = '<script>';
					foreach($script_js as $i)
						$new_script .= $scripts[$i][2];
					$new_script .= '</script>';
					$html = substr_replace($html,$new_script . '</body>',$body,7);

				} else {
					
					// Append back all the script tags in the original order if not combining.
					for($i=0;$i<$l;$i++) {
						$html = substr_replace($html,$scripts[$i][1] . $scripts[$i][2] . $scripts[$i][3] . '</body>',$body,7);
						$body = strrpos($html,'</body>');
					}
					
				}
			
			}
		} else {
			
			for($i=0;$i<$l;$i++) {
				$pos = strpos($html,$matches[0][$i]);
				$html = substr_replace($html,$matches[1][$i] . $scripts[$i][2] . $matches[2][$i],$pos,strlen($matches[0][$i]));
			}

		}
		
		return $html;
	}
	
	// Given a compression string, returns compressed HTML output.
	private static function compress($out,$type) {
		switch($type) {
		case 'all_whitespace_not_newlines':
		case 'pretty_indent':
			/*$out = preg_replace_callback(
				'@(?<!^)\\</?([a-z]+)(\\s+[\\s\\S]+?)?\\>@im',
				function($r) { return PHP_EOL . $r[0]; },
				preg_replace("/^\\s+|\\s+$/m",'',$out)
			);*/
			$out = preg_replace("/^\\s+|\\s+$/m",'',$out);/*
			if($type === 'pretty_indent') {
				$lines = preg_split('/$\\R?^/m',$out);
				$tabs = -1;
				foreach($lines as $k => $l) {
					$match = array();
					if(preg_match('/^<([a-z]+)/i',$l,$match)) {
						$tabs++;
					} elseif(true) {
						
					}
					for($i=0;$i<$tabs;$i++) $lines[$k] = "\t" . $lines[$k];
				}
			}*/
			return $out; // This regex removes all indentations and empty lines.
		case 'all_whitespace':
			//$out = str_replace(PHP_EOL,'',$out); // Doesn't work with Wordpress source for some reason.
			return preg_replace('/\\s+/m',' ',$out); // Temporary fix for Wordpress source.
			// Can implement regex to clean whitespace between html tags instead to clear this newline problem. Then Javascript / CSS will have their separate newline handlers.
		}
		return $out;
	}
	
	// Removes CSS, JS or HTML comments.
	public static function removeComments($string,$type = 'javascript') {
		
		// Compute the regex string once.
		if(!self::$RegexString) self::$RegexString = '@('.implode('|',self::$RegexArray).')@';
		
		switch(strtolower($type)) {
		case 'javascript': case 'js':
			
			// Do not remove comments used to start or end CDATA blocks.
			if(preg_match('/(\\<\\!\\[CDATA\\[|\\]\\]\\>)/',$string)) return $string;
			
		case 'css':
		
			// Uses the regular expressions in self::$RegexArray.
			return preg_replace_callback(self::$RegexString,array('HTMLMinifier','removeComments_callback_js_css'),$string);
			
		case 'html':
			
			// NOT USED. Xpath used to pull out comments instead.
			$regex = array(
				'script' => '\\<(script|style)(\\s+[\\s\\S]*?)?\\>([\\s\\S]*?)\\</(script|style)\\s*?\\>',
				'comment' => '<!--([\\s\\S]*?)-->' // Remove all comments except [if ] blocks.
			);
			$full_regex = '@('.implode('|',$regex).')@';
			
			return preg_replace_callback($full_regex,array('HTMLMinifier','removeComments_callback_html'),$string);
		}
	}
	
	public static function removeComments_callback_js_css($matches) {
		// Don't change anything if the captured match is a string or the url() function in CSS.
		if(preg_match('/^'.self::$RegexArray['string_single'].'$/',$matches[0])) return $matches[0];
		if(preg_match('/^'.self::$RegexArray['string_double'].'$/',$matches[0])) return $matches[0];
		if(preg_match('/^'.self::$RegexArray['css_url_function'].'$/',$matches[0])) return $matches[0];
		return PHP_EOL;
	}
	
	public static function removeComments_callback_html($matches) {
		if(preg_match('/^<(style|script)/',$matches[1])) return $matches[0]; // Exclude script / style tags, since the regex in the caller captures them too.
		if(substr($matches[1],0,1) === '>') return $matches[0]; // Excludes <!--> sequences, which are not comments but part of a conditional statement.  
		return (0 === strpos($matches[1], '[') || false !== strpos($matches[1], '<![')) ? $matches[0] : ''; 
	}
}
HTMLMinifier::$CacheFolder = APPPATH . 'cache/';
?>