<?php
/*
This class interfaces with the HTMLMinifier class to minify the HTML source.
It also interfaces with Wordpress.

Dated: 25 May 2018
*/
class HTMLMinifier_Manager {
	
	const HTACCESS_MARKER = 'Terresquall\HTMLMinifier'; // Never change this.
	const PLUGIN_OPTIONS_PREFIX = 'ts_htmlminifier_';
	const PLUGIN_OPTIONS_VERSION = 5;
	
	static $Defaults; // Set after class declaration.	
	static $CurrentOptions; // Current options for HTMLMinifier
	
	// These are variables that are outside of the HTMLMinifier class.
	static $ManagerDefaults = array(
		// For minifying resources.
		'minify_css_files' => false,
		'minify_js_files' => false,
		
		'browser_rsc_caching' => '24',
		'ignore_rsc_minify_regex' => '/jquery/i',
		
		// Which ones to minify?
		'minify_wp_admin' => false,
		'minify_frontend' => true
	);
	
	// These are the default variables for caching.
	static $CachingDefaults = array(
		'enable_rsc_caching' => false,
		'expiration_time' => 24
	);
	
	// All the usable minifier versions, for documentation purposes (switching between versions not coded).
	// Deprecated.
	static $MinifierVersions = array(
		'use_latest' => 'inc/src/HTMLMinifier.php',
		'version2' => 'inc/src/HTMLMinifier-v2.php'
	);
	
	// Called with an action hook in the main plugin file.
	public static function init() {
		
		// Imports the correct HTMLMinifier version.
		require_once HTML_MINIFIER__PLUGIN_DIR . self::$MinifierVersions['use_latest'];
		define('HTML_MINIFIER_VERSION', HTMLMinifier::VERSION);
		
		// Get defaults from the HTMLMinifier class, then append an additional option to it.
		self::$Defaults = array(
			'core' => HTMLMinifier::$Defaults,
			'manager' => self::$ManagerDefaults,
			'caching' => self::$CachingDefaults,
			'version' => self::PLUGIN_OPTIONS_VERSION
		);
		
		self::init_wp_options(); // Populates self::$CurrentOptions.
		self::upgrade_db(); // Upgrades the DB if it is outdated.
		self::check_for_rsc_query(); // Checks whether this is a resource request.
		
		//load_plugin_textdomain('html-minifier', false, 'languages'); // Multi-language support text domain.
		self::ob_start(); // Start obfuscation.
		
	}
	
	// Handles CSS and JS file queries, if they are redirected here.
	private static function check_for_rsc_query() {
		
		// Gets the URI.
		$uri = $_SERVER['REQUEST_URI'];
		if(!empty($_SERVER['QUERY_STRING']))
			$uri = preg_replace('@' . preg_quote('?' . $_SERVER['QUERY_STRING']) . '$@','',$uri); // Remove any query strings in the URI.

		// Serve a file if the file extension is CSS or Javascript.
		$ext = $mime = strtolower(pathinfo($uri,PATHINFO_EXTENSION));
		if(preg_match('@^(css|js)$@i',$ext)) {

			$filepath = rtrim(ABSPATH,'\\/') . $uri;
			
			if(is_readable($filepath)) {
				
				if($ext === 'js') $mime = 'javascript'; // This is JS's mime type.
				
				$min_css = self::$CurrentOptions['manager']['minify_css_files'];
				$min_js = self::$CurrentOptions['manager']['minify_js_files'];
				$output = file_get_contents($filepath); // 
				$alreadyMin = !empty(self::$CurrentOptions['manager']['ignore_rsc_minify_regex']) && @preg_match(self::$CurrentOptions['manager']['ignore_rsc_minify_regex'],pathinfo($filepath,PATHINFO_FILENAME));
				
				if(!$alreadyMin) {
					
					// If caching is enabled, assign a cache key so that the minified result is cached.
					$cache_key = '';
					if(!empty(self::$CurrentOptions['caching']['enable_rsc_caching'])) {
						
						// Check if cache directory is writable first.
						HTMLMinifier::$CacheFolder = HTML_MINIFIER__PLUGIN_DIR . 'cache' . DIRECTORY_SEPARATOR; // Sets the cache folder.
						if(wp_is_writable(HTMLMinifier::$CacheFolder)) {						
							$cache_key = $filepath;
							if(!empty(self::$CurrentOptions['caching']['expiration_time']))
								HTMLMinifier::$CacheExpiry = is_nan( intval(self::$CurrentOptions['caching']['expiration_time']) ) ? 86400 : intval(self::$CurrentOptions['caching']['expiration_time']) * 3600;
						} else {
							// We display an error notification if we are in WP Admin.
							// TO BE DONE.
						}
					}
					
					if($ext === 'js' && $min_js)
						$output = HTMLMinifier::minify_rsc($output,$ext,self::$CurrentOptions['core'],$cache_key);
					elseif($ext === 'css' && $min_css)
						$output = HTMLMinifier::minify_rsc($output,$ext,self::$CurrentOptions['core'],$cache_key);
						
				}
				
				// Send response headers & content.
				http_response_code(200);
				header("Content-Type: text/$mime;charset=utf-8;");
				
				// Do we want to tell the browser to cache this resource?
				if(isset(self::$CurrentOptions['manager']['browser_rsc_caching'])) {
					$browser_cache = intval(self::$CurrentOptions['manager']['browser_rsc_caching']);
					if($browser_cache) header('Cache-Control: max-age=' . ($browser_cache*3600));
				}
				
				echo $output;
				exit;
				
			} else {
				// Force 404 on WordPress if file does not exist.
				if(!file_exists($filepath)) {
					add_action('wp',array('HTMLMinifier_Manager','set_404'));
					return;
				}
				
				trigger_error('Please disable HTMLMinifier on your WordPress installation, as your PHP does not have sufficient permissions to allow HTMLMinifier to work.',E_USER_ERROR);
				exit;
			}
		}
	}
	
	// For triggering a 404.
	public static function set_404() {
		global $wp_query;
		$wp_query->set_404();
		status_header(404);
		return;
	}
	
	// Adds a snippet to the WordPress .htaccess so that we can process .css and .js files too.
	// $add will add the HTMLMinifier .htaccess redirector, if false will remove instead.
	public static function write_htaccess($add = true) {
		$htaccess = ABSPATH . '.htaccess';
		
		if(wp_is_writable($htaccess)) {
			$content = file_get_contents($htaccess);
			$preg_quote = preg_quote(self::HTACCESS_MARKER);
			$has_htaccess = preg_match('@(\\r|\\n|\\r\\n)*?#BEGIN ' . $preg_quote . '[\\s\\S]*?#END ' . $preg_quote . '(\\r|\\n|\\r\\n)*@',$content,$match,PREG_OFFSET_CAPTURE);
			
			// If there is an existing entry in .htaccess, remove it.
			if($has_htaccess)
				$content = str_replace($match[0][0],PHP_EOL,$content,$match[0][1]);
			
			// If we are adding a new .htaccess entry, then do it.
			if($add) {
				$idx = strpos($content,'RewriteBase /',strpos($content,'#BEGIN WordPress')+17) + 13; // Find the point to split the string.
				$split = array( substr($content,0,$idx),substr($content,$idx) ); // Splits the string.
				$add = '#BEGIN ' . self::HTACCESS_MARKER . PHP_EOL . file_get_contents(HTML_MINIFIER__PLUGIN_DIR . 'inc/mod.htaccess') . PHP_EOL . '#END ' . self::HTACCESS_MARKER;
				$content = trim($split[0]) . PHP_EOL . PHP_EOL . $add . PHP_EOL . PHP_EOL . trim($split[1]);
			}
			
			// Save output.
			file_put_contents($htaccess,$content,LOCK_EX);
		}
	}
	
	private static function ob_start() {
		
		// Check if we should minify WP-Admin and stop if we should not.
		if(is_admin()) {
			if(empty(self::$CurrentOptions['manager']['minify_wp_admin'])) return false;
			self::$CurrentOptions['core']['shift_script_tags_to_bottom'] = false; // Force false because it will break Wordpress otherwise.
		} else {
			if(isset(self::$CurrentOptions['manager']['minify_frontend']) && !self::$CurrentOptions['manager']['minify_frontend']) return false;
		}
		
		HTMLMinifier::$Defaults = self::$CurrentOptions['core'];
		return ob_start(array('HTMLMinifier','process')); // The Wordpress engine will close this ob.
	}
	
	// Retrieves saved settings from WP Database and makes sure the settings are correct.
	private static function init_wp_options() {
		if(!empty(self::$CurrentOptions)) return; // Don't rerun if already initialized.
		
		$option = get_option( self::PLUGIN_OPTIONS_PREFIX . 'options');
		
		if($option === false) { // If there are no options, i.e. first-time use.
			$option = self::$Defaults;
			add_option( self::PLUGIN_OPTIONS_PREFIX . 'options',$option);
		}

		self::$CurrentOptions = $option;
	}
	
	// Checks if we should update the database for this plugin.
	private static function upgrade_db() {
		$option = self::$CurrentOptions;
		if(!isset($option['version']) || $option['version'] < self::PLUGIN_OPTIONS_VERSION) {
			require_once HTML_MINIFIER__PLUGIN_DIR . 'inc/HTMLMinifier.upgrader.php';
			$option = HTMLMinifier_Upgrader::run($option);
			self::update_options($option,true); // Save the upgrade.
		}
		self::$CurrentOptions = $option;
		return $option;
	}
	
	public static function clear_cache() {
		$glob = glob(HTML_MINIFIER__PLUGIN_DIR . 'cache' . DIRECTORY_SEPARATOR . '*');
		if(count($glob) <= 0) return false;
		foreach($glob as $file) {
			if(preg_match('/^index\\.php$/i',basename($file))) continue;
			unlink($file);
		}
		return true;
	}
	
	// This function hijacks the main query and injects a cached page if there is any.
	public static function posts_request($request, $query) {
		if ( is_home() && $query->is_main_query() ) {
			$page = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
			//$key = 'homepage_query_cache_' . $page;
			//if(wp_cache_get( $key, 'cache_group' ))
			$request = null;
		}
		//var_dump($request,$query);
		//return null;
	}
	
	// Wrapper around the HTMLMinifier function, adding an additional option unique to Wordpress.
	// $save_options: If true, save the preset that we are getting.
	public static function get_presets($type) {
		$r = HTMLMinifier::get_presets($type);
		if($r) {
			return array(
				'core' => $r,
				'manager' => self::$CurrentOptions['manager'],
				'version' => self::$CurrentOptions['version']
			);
		}
		return $r;
	}
	
	// This is for setting the currently existing HTML Minifier options.
	// Lets move the filters from HTMLMinifier_Admin here.
	public static function update_options($options, $sanitize_fields = false) {
		
		if($sanitize_fields) {
			
			// If an array does not have essential data in its arrays, populate it with defaults.
			if(!isset($options['core']) || !is_array($options['core'])) $options['core'] = HTMLMinifier::$Defaults;
			if(!isset($options['manager']) || !is_array($options['manager'])) $options['manager'] = self::$ManagerDefaults;
			if(!isset($options['caching']) || !is_array($options['caching'])) $options['caching'] = self::$CachingDefaults;
			
			// Loops through the CORE array to see if it is all right.
			foreach($options['core'] as $k => $v) {
				
				// Remove entries that are not used by HTMLMinifier (e.g. "submit" value, nonces, user-hacked entries).
				if(!array_key_exists($k,HTMLMinifier::$Defaults)) {
					unset($options['core'][$k]);
					continue;
				}
				
				// Sanitizes for variables depending on how they are used.
				switch($k) {
				default:
					// Force non-array objects into 0s or 1s.
					if(gettype($options['core'][$k]) !== 'array')
						$options['core'][$k] = $options['core'][$k] ? 1 : 0;
					
					break;
				
				case 'compression_mode':
					// If the submitted compression mode is not valid, then force it to the default value.
					if(!array_key_exists($v,HTMLMinifier::$CompressionMode) || gettype($v) !== 'string')
						$options['core'][$k] = self::$Defaults['core'][$k];
				}
			}
			
			// Loops through the MANAGER array to see if it is all right.
			foreach($options['manager'] as $k => $v) {
				
				// Remove entries that are not used by HTMLMinifier (e.g. "submit" value, nonces, user-hacked entries).
				if(!array_key_exists($k,self::$ManagerDefaults)) {
					unset($options['manager'][$k]);
					continue;
				}
				
				// Sanitizes for variables depending on how they are used.
				switch($k) {
				default:
					// Force non-array objects into 0s or 1s.
					if(gettype($options['manager'][$k]) !== 'array')
						$options['manager'][$k] = $options['manager'][$k] ? 1 : 0;
					break;
				case 'browser_rsc_caching':
					$options['manager'][$k] = intval($options['manager'][$k]);
				case 'ignore_rsc_minify_regex':
					break;
				}
			}
			
			// Add / remove a snippet to the HTACCESS when we want to minify JS or CSS files.
			if(!empty($options['manager']['minify_css_files']) || !empty($options['manager']['minify_js_files'])) self::write_htaccess(true);
			else self::write_htaccess(false);
			
			// Loops through the CACHING array to see if it is all right.
			foreach($options['caching'] as $k => $v) {
				
				// Remove entries that are not used by HTMLMinifier (e.g. "submit" value, nonces, user-hacked entries).
				if(!array_key_exists($k,self::$CachingDefaults)) {
					unset($options['caching'][$k]);
					continue;
				}
				
				// Sanitizes for variables depending on how they are used.
				switch($k) {
				default:
					// Force non-array objects into 0s or 1s.
					if(gettype($options['caching'][$k]) !== 'array')
						$options['caching'][$k] = $options['caching'][$k] ? 1 : 0;
					break;
				case 'expiration_time':
					$options['caching'][$k] = intval($options['caching'][$k]);
					break;
				}
			}
			
			// Recreates the array so that only the 3 main categories remain.
			$options = array(
				'core' => $options['core'],
				'manager' => $options['manager'],
				'caching' => $options['caching'],
				'version' => $options['version']
			);
			
		}
		
		self::$CurrentOptions = $options;
		
		return update_option( self::PLUGIN_OPTIONS_PREFIX . 'options',$options);
	}
	
	// Remove the entry for this plugin if it is uninstalled.
	public static function uninstall() { 
		delete_option(self::PLUGIN_OPTIONS_PREFIX . 'options');
		self::write_htaccess(false);
	}
	
	// Activation hook.
	public static function activate() { 
		self::init_wp_options();
		//self::upgrade_db();
		$options = self::$CurrentOptions['manager'];
		
		// Add / remove a snippet to the HTACCESS when we want to minify JS or CSS files.
		if($options['minify_css_files'] || $options['minify_js_files']) self::write_htaccess(true);
		else self::write_htaccess(false);
	}
	
	// Deactivation hook.
	public static function deactivate() { self::write_htaccess(false); }
}