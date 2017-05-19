<?php
/*
HTML Minifier
=============
This is a static PHP class that minifies HTML. To use it, call HTMLMinifier::process() and pass your
HTML into it. You can also initialise it so that you can use the function non-statically. Either way
is fine.

@author		Terence Pek <mail@terresquall.com>
@website	www.terresquall.com
@version	1.3.3
@dated		18/05/2017
@notes		Can think about how to show bytes saved from compression.
			The 'combine_style_tags' option no longer combines tags with different media attributes.
			- Fixed some issues with <link> and <style> tag reorganisation and compression.
			- Removed pretty indents option (since it is not done yet).
			- Now removes <script> tag contents and processes them separately.
			- Added an option to not compress <script> tag contents 'compression_ignore_script_tags'.
			- Need to not remove comments with CDATA tags inside within Javascript.
*/
//if(!class_exists('DOMDocument')) die('Native PHP class DOMDocument not found. HTMLMinifier requires PHP to have DOMDocument.');
class HTMLMinifier {
	
	const VERSION = '1.3.3';
	const SIGNATURE = 'Source minified by HTMLMinifier: www.terresquall.com/web/html-minifier.';
	
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
		//'pretty_indents' => 'Pretty indentations (not working)',
		'all_whitespace_not_newlines' => 'All whitespace except newlines',
		'all_whitespace' => 'All whitespaces'
	);
	
	static $Defaults = array(
	
		// General options.
		'clean_html_comments' => true,
		'show_signature' => false, // Show signature at the end.
		
		// Stylesheet optimisations.
		'clean_css_comments' => true,
		'shift_link_tags_to_head' => false,
		'shift_style_tags_to_head' => false,
		'combine_style_tags' => false,
		
		// Javascript optimisations.
		'clean_js_comments' => true,
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
	public static function process($html,$options = null) {
		
		$startLen = strlen($html);
		
		if($options !== null && is_array($options)) $options = array_merge(self::$Defaults,$options);
		else $options = self::$Defaults;
		
		$scripts = array();
		$html = self::clean_script_tags($html,$scripts);
		
		// Read the entire HTML file into DOM.
		libxml_use_internal_errors(true); // Suppresses errors for HTML5 tags.
		$dom = new DOMDocument();
		$dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
		
		// Remove HTML comments.
		if($options['clean_html_comments']) {
			$xpath = new DOMXPath($dom);
			foreach($xpath->query('//comment()') as $comment) {
				// If it is an is block, don't remove.
				if(preg_match('@^\\[if [\\s\\S]*?\\]@',$comment->nodeValue) && preg_match('@\\<\\!\\[endif\\]$@',$comment->nodeValue))
					continue;
				
				$comment->parentNode->removeChild($comment);
			}
		}
		
		self::process_stylesheet_options($dom,$options);
		if(!$options['compression_ignore_script_tags']) self::process_script_options($dom,$scripts,$options);
		
		// Signs off before the HTML closing tag.
		if($options['show_signature']) {
			$html = $dom->getElementsByTagName('html')->item(0);
			if($html) $html->appendChild($dom->createComment(self::SIGNATURE));
		}
		
		// Compressing of HTML source.
		$out = self::compress($dom->saveHTML(),$options['compression_mode']);
		
		// If Javascript compression is ignored.
		if($options['compression_ignore_script_tags']) {
			$dom = new DOMDocument();
			$dom->loadHTML($out, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
			self::process_script_options($dom,$scripts,$options);
			$out = $dom->saveHTML();
		}
		
		return $out;
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
	private static function clean_script_tags($html,&$scripts) {
		$matches = array();
		$l = preg_match_all('%(\\<script ?[\\s\\S]*?\\>)([\\s\\S]*?)(\\</script\\>)%i',$html,$matches);
		for($i=0;$i<$l;$i++) {
			// Ignore if there is an <src> attribute.
			//if(preg_match('%src\\=(\'[\\S]*\'|"[\\S]*")%i',$matches[1][$i])) continue;
			
			array_push($scripts,$matches[2][$i]);
			$html = str_replace($matches[0][$i],$matches[1][$i].$matches[3][$i],$html);
		}
		
		return $html;
	}
	
	// $scripts is the content of all script tags without src.
	private static function process_script_options($dom,$scripts,$options) {
		
		// Remove all Javascript comments from the script tags.
		if($options['clean_js_comments']) {
			if(count($scripts) > 0){
				foreach($scripts as $k => $src) {
					$scripts[$k] = self::removeComments($src);
					//$src->appendChild($dom->createTextNode($new_text));
				}
			}
		}
				
		// Shift all detected script tags to the foot of the document (after formatting them).
		$dom_scripts = $dom->getElementsByTagName('script');
		
		if($options['shift_script_tags_to_bottom']) {
			
			if($dom_scripts->length > 0) {
				
				$script_js = array(); // Scripts that can be combined into one tag.
				$script_others = array(); // Scripts that have to be kept distinct.
				$script_original = array(); // Stores the original order of all the script tags.
				
				// Remove all <script> tags that do not have an SRC attribute, or do not contain Javascript.
				foreach($dom_scripts as $k => $src) {
					
					// Re-append the contents of the script tag.
					$src->appendChild($dom->createTextNode($scripts[$k]));
					$type = $src->attributes->getNamedItem('type');
					
					$script_original[] = $src;
					if((!$type || $type->value === 'text/javascript') && !$src->attributes->getNamedItem('src')) {
						$script_js[] = $src;
					} else {
						$script_others[] = $src;
					}
					
				}
				
				// Remove all scripts from the DOM, as they are reinserted at the bottom.
				foreach($script_original as $src) $src->parentNode->removeChild($src);
				
				// Insert into end of body tag if there is one. Otherwise, insert into html tag.
				$body = $dom->getElementsByTagName('body');
				$html = $dom->getElementsByTagName('html');
				$host = $body ? $body->item(0) : $html->item(0);
				if(!$host) $host = $dom;
				
				if($options['combine_javascript_in_script_tags']) {
					
					// Append all distinct scripts onto the document.
					foreach($script_others as $src) $host->appendChild($src);
					
					// Create a single script tag and paste it at the end.
					$new_script = $dom->createElement('script');
					foreach($script_js as $src)
						$new_script->appendChild($dom->createTextNode($src->nodeValue));
					$host->appendChild($new_script);

				} else {
					
					// Append back all the script tags that were upended.
					foreach($script_original as $src) $host->appendChild($src);
					
				}
			
			}
		} else {
			
			// Re-add the code inside the <script> tags in the DOM.
			foreach($dom_scripts as $k => $src) {
				$src->appendChild($dom->createTextNode($scripts[$k]));
			}
			
		}
	}
	
	// Given a compression string, returns compressed HTML output.
	private static function compress($out,$type) {
		switch($type) {
		case 'pretty_indents':
			return $out;
		case 'all_whitespace_not_newlines':
			//$out = preg_replace("/(\t+|\ {2,})/",' ',$out);
			return preg_replace("/^\\s+|\\s+$/m",'',$out); // This regex removes all indentations and empty lines.
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
		case 'javascript': case 'js': case 'css':
		
			// Uses the regular expressions in self::$RegexArray.
			return preg_replace_callback(self::$RegexString,array('HTMLMinifier','removeComments_callback_js_css'),$string);
			
		case 'html':
		
			// This section is not currently used.
			$regex = array(
				'script' => '\\<(script|style)(\\s ?[\\s\\S]*?)?\\>([\\s\\S]*?)\\</(script|style)\\s*?\\>',
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
		if(preg_match('/^<(style|script)/',$matches[1])) return $matches[0]; // Exclude script / style tags.
		return (0 === strpos($matches[1], '[') || false !== strpos($matches[1], '<![')) ? $matches[0] : ''; // 
	}
	
}
?>