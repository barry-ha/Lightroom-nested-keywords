<?php
/**
 * Name: Adobe XMP / IPTC for PHP
 * Author: Barry Hansen, barry@electromagneticsoftware.com
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl.txt
 * Description: Read Adobe XMP and IPTC information from image files
 * Requires: PHP 5.6 or higher
 * Tested With: PHP 7.2
 * Version: 1.0
 * 
 * Based on:
 *      - JS Morisset's Adobe XMP / IPTC for WordPress, v1.3.3, https://wordpress.org/plugins/adobe-xmp-for-wp/ 
 *      - PHP Adobe XMP Reader, https://github.com/cobraz/xmp 
 * 
 * Version Numbering: {major}.{minor}.{bugfix}[-{stage}.{level}]
 *
 *      {major}         Major structural code changes / re-writes or incompatible API changes.
 *      {minor}         New functionality was added or improved in a backwards-compatible manner.
 *
 * Usage:
 *      require_once('adobe-xmp-for-php.php');
 *      $adobePHP =& adobeXMPforPHP::get_instance();
 *      $image_xmp = $adobePHP->get_xmp( 'myphoto.jpg' );
 *      echo '<p>Photograph by '.$image_xmp['Creator'].'</p>';
 *      echo '<p>Hierarchical keywords = '.$image_xmp[''Hierarchical Keywords''].'</p>';
 * 
 * Barry's changes:
 *      - remove WordPress plug-in dependencies (startup, shutdown, shortcodes)
 *      - remove cache (always read image file every time; we assume fast local storage)
 *      - remove WordPress media id (use name of file on disk instead)
 *      - sort hierarchical keywords alphabetically
 *      - optionally replace selected keywords with new ones
 *
 */

if ( ! class_exists( 'adobeXMPforPHP' ) ) {

	class adobeXMPforPHP {

		public $use_cache  = false;		// set 'false' for PHP, 'true' for WordPress
		public $max_size   = 512000;	// Maximum size read.
		public $chunk_size = 65536;	// Read 64k at a time.
		public $xmp_definitions = array(		// list of XMP items we will look for
			// okay to comment out any items that your particular project does not use
//			'Creator Email'		=> '<Iptc4xmpCore:CreatorContactInfo[^>]+?CiEmailWork="([^"]*)"',
//			'Owner Name'		=> '<rdf:Description[^>]+?aux:OwnerName="([^"]*)"',
			'Creation Date'		=> '<rdf:Description[^>]+?xmp:CreateDate="([^"]*)"',
			'Modification Date'	=> '<rdf:Description[^>]+?xmp:ModifyDate="([^"]*)"',
//			'Label'			=> '<rdf:Description[^>]+?xmp:Label="([^"]*)"',
//			'Credit'		=> '<rdf:Description[^>]+?photoshop:Credit="([^"]*)"',
//			'Source'		=> '<rdf:Description[^>]+?photoshop:Source="([^"]*)"',
			'Headline'		=> '<rdf:Description[^>]+?photoshop:Headline="([^"]*)"',
//			'City'			=> '<rdf:Description[^>]+?photoshop:City="([^"]*)"',
//			'State'			=> '<rdf:Description[^>]+?photoshop:State="([^"]*)"',
//			'Country'		=> '<rdf:Description[^>]+?photoshop:Country="([^"]*)"',
//			'Country Code'	=> '<rdf:Description[^>]+?Iptc4xmpCore:CountryCode="([^"]*)"',
//			'Location'		=> '<rdf:Description[^>]+?Iptc4xmpCore:Location="([^"]*)"',
			'Title'			=> '<dc:title>\s*<rdf:Alt>\s*(.*?)\s*<\/rdf:Alt>\s*<\/dc:title>',
			'Description'	=> '<dc:description>\s*<rdf:Alt>\s*(.*?)\s*<\/rdf:Alt>\s*<\/dc:description>',
//			'Creator'		=> '<dc:creator>\s*<rdf:Seq>\s*(.*?)\s*<\/rdf:Seq>\s*<\/dc:creator>',
//			'Keywords'		=> '<dc:subject>\s*<rdf:Bag>\s*(.*?)\s*<\/rdf:Bag>\s*<\/dc:subject>',
			'Hierarchical Keywords'	=> '<lr:hierarchicalSubject>\s*<rdf:Bag>\s*(.*?)\s*<\/rdf:Bag>\s*<\/lr:hierarchicalSubject>'
		);
		// an easy way to rename selected keywords for better display appearance
		public $substitute_from = array( '~1~DATES', '~2~LOCATIONS', '~3~ACTIONS', '~4~AGENTS', '~5~ENTITIES', '~6~ATTRIBUTES');
		public $substitute_to   = array(    'Dates',    'Locations',    'Actions',    'Agents',    'Entities',    'Attributes');

		private $avail     = array();	// Assoc array for function/class/method checks.
		private $cache_dir = '';

		private static $instance;

		public function __construct() {
		}

		public static function &get_instance() {

			if ( ! isset( self::$instance ) ) {
				self::$instance = new self;
			}

			return self::$instance;
		}

		public static function load_textdomain() {
			die( 'WordPress is not supported, so function load_textdomain() not implemented. (line '.__LINE__.')' );
		}

		public function init_plugin() {
			die( 'WordPress is not supported, so function init_plugin() not implemented. (line '.__LINE__.')' );
		}

		public function get_avail() {
			die( 'WordPress is not supported, so function get_avail() is not implemented. (line '.__LINE__.')' );
		}

		public function get_xmp( $fqname ) {	// was: function get_xmp( $pid ) {
			return $this->get_media_xmp( $fqname );
		}

		public function get_ngg_xmp( $pid ) {
			die( 'WordPress is not supported, so function get_ngg_xmp( $pid ) is not implemented. (line '.__LINE__.')' );
		}

		public function get_media_xmp( $fqname ) {	// was: function get_media_xmp( $pid ) {

			$xmp_arr = array();

			if ( $filepath = $fqname ) {

				$xmp_raw = $this->get_xmp_raw( $fqname );

				if ( ! empty( $xmp_raw ) ) {
					$xmp_arr = $this->get_xmp_array( $xmp_raw );
				} else {
					echo "<p>Unable to get_xmp_raw( $fqname ) in line ".__LINE__."</p>".PHP_EOL;
				}
			} else {
				echo "<p>Error: No filename (line ".__LINE__.")</p>".PHP_EOL;
			}

			return $xmp_arr;
		}

		public function get_xmp_raw( $filepath ) {

			$start_tag  = '<x:xmpmeta';
			$end_tag    = '</x:xmpmeta>';
//			$cache_file = $this->cache_dir . md5( $filepath ) . '.xml';
			$xmp_raw    = null; 

			if ( $this->use_cache && 
				file_exists( $cache_file ) && 
				filemtime( $cache_file ) > filemtime( $filepath ) && 
				$cache_fh = fopen( $cache_file, 'rb' ) ) {

				$xmp_raw = fread( $cache_fh, filesize( $cache_file ) );

				fclose( $cache_fh );

			} elseif ( $file_fh = fopen( $filepath, 'rb' ) ) {

				$chunk     = '';
				$file_size = filesize( $filepath );
				//echo "  <p>Starting to read file ($filepath) of size $file_size (line ".__LINE__.")</p>".PHP_EOL;	// debug

				while ( ( $file_pos = ftell( $file_fh ) ) < $file_size  && $file_pos < $this->max_size ) {

					$chunk .= fread( $file_fh, $this->chunk_size );

					if ( false !== ( $end_pos = strpos( $chunk, $end_tag ) ) ) {

						if ( false !== ( $start_pos = strpos( $chunk, $start_tag ) ) ) {

							$xmp_raw = substr( $chunk, $start_pos, $end_pos - $start_pos + strlen( $end_tag ) );

							if ( $this->use_cache && $cache_fh = fopen( $cache_file, 'wb' ) ) {

								fwrite( $cache_fh, $xmp_raw );
								fclose( $cache_fh );
							}
						}

						break;	// Stop reading after finding the xmp data.
					}
				}

				fclose( $file_fh );
			}

			return $xmp_raw;
		}

		public function get_xmp_array( $xmp_raw ) {
			//echo "  <p>Starting to process XMP array of size ".sizeof($xmp_raw)." (line ".__LINE__.")</p>".PHP_EOL;	// debug
			//echo "<pre>"; var_dump( $xmp_raw ); echo "</pre>".PHP_EOL;	// debug

			$xmp_arr = array();

			foreach ( $this->xmp_definitions as $key => $regex ) {

				/**
				 * Get a single text string.
				 */
				$xmp_arr[$key] = preg_match( '/' . $regex . '/is', $xmp_raw, $match ) ? $match[1] : '';

				/**
				 * If string contains a list, then re-assign the variable as an array with the list elements.
				 */
				$xmp_arr[$key] = preg_match_all( '/<rdf:li[^>]*>([^>]*)<\/rdf:li>/is', $xmp_arr[$key], $match ) ? $match[1] : $xmp_arr[$key];

				if ( ! empty( $xmp_arr[ $key ] ) && $key == 'Hierarchical Keywords' ) {
					/**
					 * Sort hierarchical keywords into alphabetical order
					 */
					sort( $xmp_arr[$key] );

					/**
					 * Substitute selected words with given replacement across all hierarchical keywords
					 */
					$xmp_arr[$key] = str_ireplace( $this->substitute_from, $this->substitute_to, $xmp_arr[$key]);

				}
			}

			return $xmp_arr;
		}

		public function dump_xmp( $image_xmp ) {
			// want to see what data was returned after calling 'get_xmp()'?  
			// Just call this debug routine

			//echo "<pre>"; var_dump($image_xmp); echo "</pre>".PHP_EOL;  // debug
			//echo '  <p>Creation date = '.$image_xmp['Creation Date'].'</p>'.PHP_EOL; // debug
			echo '<div class="text-left" style="border: 1px dotted red;">'.PHP_EOL;
			echo '  <!-- '.__FILE__.' ['.__LINE__.'] -->'.PHP_EOL;
    		foreach ($image_xmp as $key=>$value) {
				if (is_array($value)) {
					// this item contains an array of strings, so iterate through them
					echo "  <p><b>$key</b></p>".PHP_EOL;
					echo "  <ul>".PHP_EOL;
					foreach ( $value as $kwset ) {
						echo "    <li>$kwset</li>".PHP_EOL;
					}
					echo "  </ul>".PHP_EOL;
				} else {
					// this item is a simple string, so show it
					echo "  <p><b>$key</b> = $value</p>".PHP_EOL;
				}
			}
			echo '</div>'.PHP_EOL;
		}
	}

        global $adobeXMP;

	$adobeXMP =& adobeXMPforPHP::get_instance();
}
