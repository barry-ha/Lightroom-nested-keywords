<?php
// Filename: common.php
// Purpose:  Generic functions for websites using the Electromagnetic Software framework.
// 2019-08-23 barry@electromagneticsoftware.com
require_once('custom.php');

date_default_timezone_set( 'America/Los_Angeles' );

// ---------- figure out common paths and globals -----------
$thisHost = getMyHostName();
$path2root = $_SERVER['DOCUMENT_ROOT'];	// web pages (TODO: does this work on Linux?)
if (strlen($path2root) == 0) {
	$path2root = $myfolder;		// command line
}
$path2root = addTrailingSlash($path2root);
$path2php = $path2root . 'php/';
define('PATH2ROOT', $path2root);
//showGlobalVariables();          // debug, uncomment and then you can 'view source' in the web page

function showGlobalVariables() {
                        echo "<!-- File = ".__FILE__." [".__LINE__."] -->".PHP_EOL; // debug, typ. = /var/www/htdocs/mydomain.com/php/common.php
    global $thisHost;   echo "<!-- thisHost = $thisHost -->".PHP_EOL;               // debug, typ. = www.thisdomain.com
    global $myDomain;   echo "<!-- mydomain = $myDomain -->".PHP_EOL;               // debug, typ. = thisdomain.com
    global $path2root;  echo "<!-- path2root = $path2root -->".PHP_EOL;             // debug, typ. = /var/www/htdocs/mydomain.com/
    global $path2php;   echo "<!-- path2php = $path2php -->".PHP_EOL;               // debug, typ. = /var/www/htdocs/mydomain.com/php/
                        echo PHP_EOL;                                               // debug
                        echo "<!-- referer = ".getReferer()." -->".PHP_EOL;         // debug, typ. = http://www.mydomain.com/some_page.html
                        echo "<!-- user agent = ".getUserAgent()." -->".PHP_EOL;    // debug, typ. = Mozilla/5.0 (Windows NT 10.0; Win64; etc) Gecko Firefox/68.0
                        echo PHP_EOL;                                               // debug
}

// ------------- title_prefix -----------------------------------------
function title_prefix() {
	global $isTest, $serverName;
	if ($isTest) {
		$prefix = ucfirst(strtolower(trim($serverName)));
		if (empty($prefix)) {
			echo 'Test - ';
		} else {
			echo $prefix .' - ';
		}
	}
}
//-------------------------helper--------------------------------------
function getMyHostName() {
	// TODO: how is this different from PHP built-in function gethostname()?
	if (isset($_SERVER['HTTP_HOST'])) {
		$thisHost = $_SERVER['HTTP_HOST'];	// web pages
	} else {
		$thisHost = 'localhost';		// command line
	}
	return $thisHost;
}
function getUserAgent() {
	if (isset($_SERVER['HTTP_USER_AGENT'])) {
		$agent = $_SERVER['HTTP_USER_AGENT'];
	} else {
		$agent = 'unknown';
	}
	return $agent;
}
function getReferer() {
	if (isset($_SERVER['HTTP_REFERER'])) {
		$referer = $_SERVER['HTTP_REFERER'];
	} else {
		$referer = 'unspec';
	}
	return $referer;
}
// -------------------- getCleanGET --------------------------------------
// Get string parameter from url GET array, using default value if param was not provided
// Do it safely: trim whitespace and avoid 'undefined index' warnings from PHP
function getCleanGET($param, $default='') {
	if (isset($_GET[$param])) {

		$ret = $_GET[$param];
		if (empty($ret)) {
			$ret = $default;
		}
        if ($param == 'text') {
        } else {
            // make HTML safe
            $ret = htmlspecialchars($ret);
        }

	} else {
		$ret = $default;
	}
        //echo "<!-- common.php[".__LINE__."]   $param = $ret -->".PHP_EOL;  // debug 
	return $ret;
}
// -------------------- append CSS class name ----------------------------
function appendClassname($class, $new) {
	// given a CSS class name, append another string separated by space
	if (empty($class)) {
		$s = $new;
	} elseif (substr($class, -1, 1) != ' ') {
		$s = $class . ' ' . $new;
	}
	return $s;
}
// -------------------- trailing slash -----------------------------------
function addTrailingSlash($dir) {
	if (substr($dir, -1, 1) != '/') {
		$dir .= '/';		// forward (not back) slash required by Linux hosts (is Windows compatible)
	}
	return $dir;
}
function removeTrailingSlash($dir) {
	return rtrim($dir, '/');
}
// -------------------- leading slash ------------------------------------
function addLeadingSlash($dir) {
	if (strlen($dir) == 0) {
		$result = '/';
	} elseif (substr($dir, 0,1) == '/') {
		$result = $dir;
	} else {
		$result = '/' . $dir;	// forward (not back) slash required by Linux hosts (is Windows compatible)
	}
	//echo "<!-- input '$dir', output '$result' -->".PHP_EOL;	// debug
	return $result;
}
function removeLeadingSlash($dir) {
	return ltrim($dir, '/');
}
// ----------------------------- firstword()-------------------------------
function firstword($fullname) {
	$ii = strpos($fullname, ' ');
	if ($ii == false) { $ii = strlen($fullname); }
	return substr($fullname, 0, $ii);
}
// -------------------- current year --------------------------------------
function getYear() {
	return date('Y');			// 2016
}
// -------------------- copyright year ------------------------------------
function copyrightyear($year = 'auto') {
	$currentyear = date('Y');
	if (intval($year) == 'auto')       { $year = $currentyear; }

	if (intval($year) == $currentyear) { $result = $currentyear; }
	if (intval($year) < $currentyear)  { $result = intval($year).'&ndash;'.$currentyear; }
	if (intval($year) > $currentyear)  { $result = $currentyear; }
	echo $result;
}
// ------------------------------------------------------------------------
function daysUntil($year,$month,$day) {
	$targetdate = mktime(0,0,0, $month,$day,$year);
	$today = mktime(0,0,0, date('m'), date('d'), date('Y'));
	$days = floor( ($targetdate - $today)/60/60/24 );
//	echo ' ' . $days . ' ';	// debug
	return $days;
}
// -------------------------------------------------------------------------
function getServerName() {
	$serverName = getenv('COMPUTERNAME');	// Windows returns environment vbl, but Linux returns ''
	if (empty($serverName)) {
		$serverName = firstword( php_uname() );	// Linux
	}
	$serverName = ucfirst(strtolower(trim($serverName)));
	return $serverName;
}
function checkFileWrite($filename, $sourceFile, $sourceLine) {
    if (!is_writable(PATH2ROOT.$filename)) {
        showError("Server error: Incorrect file permissions. Unable to write to ".PATH2ROOT.$filename, $sourceFile, $sourceLine);
    } else {
        //echo "<br/>".PATH2ROOT.$filename." is writeable".PHP_EOL; // debug
    }
}
function showError($msg, $sourceFile, $sourceLine) {
    // issue HTML for a bootstrap error box
    $template = '<p class="alert alert-danger" role="alert">'
                    .'%s '
                    .'&nbsp;&nbsp;&nbsp; <span style="color:gray;">%s[%s]</span>'
                .'</p>'.PHP_EOL;
    $src = basename($sourceFile);
    //echo '<p class="alert alert-danger" role="alert">'.$msg.'&nbsp; <div br/>'.$src.'['.$sourceLine.']</p>'.PHP_EOL;
    printf($template, $msg, $src, $sourceLine);
}
function showWarning($msg, $file='', $line='') {
    $linetext = (empty($line)) ? '' : "[$line]";
    $filetext = (empty($file)) ? '' : '&nbsp;&nbsp;&nbsp; <span style="color:gray;">'.basename($file).$linetext.'</span>';

    // issue HTML for a bootstrap error box
    if (empty($line)) {
        echo '<p class="alert alert-warning" role="alert">'.$msg.'</p>'.PHP_EOL;
    } else {
        echo '<p class="alert alert-warning" role="alert">'.$msg.$filetext.'</p>'.PHP_EOL;
    }
}
function showFencepost($file, $line) {
    $filename = basename($file);
    // extremely basic replacement for "echo file.html [line]"
    echo "<br/>$filename [$line]".PHP_EOL;
}
// ---------- improved var_dump ----------
function debugDump($var, $file, $line, $funcname, $varname='') {
    // input:  $var = caller's data structure
    //         $file = caller's __FILE__
    //         $line = caller's __LINE__
    //         $funcname = caller's subroutine name
    //         $varname = short description of what we're dumping
    $template = '<pre class="text-left">%s[%s] %s'.PHP_EOL.'%s = ';    // src file, src line, funcname, varname

    $shortfilename = basename($file);

    printf($template, $shortfilename, $line, $funcname, $varname);
    print_r( $var );
    echo '</pre>'.PHP_EOL;
}
// ---------- getListOfImages() ----------
function getListOfImages($imagefolder) {
    global $supportedFileExtensions;
    global $loopLimit;
    //echo "<p>Current working directory = " . getcwd() . " </p>".PHP_EOL;    // debug

    if (!file_exists(PATH2ROOT.$imagefolder)) {
        // error, folder not found, issue message
        showError("<strong>Error:</strong> Image folder ($imagefolder) was not found.", __FILE__, __LINE__);

    } elseif (!is_dir(PATH2ROOT.$imagefolder)) {
        // error, filename not folder name, issue another message
        showError("<strong>Error:</strong> Specified folder ($imagefolder) is not actually a folder.", __FILE__, __FILE__, __LINE__);

    } else {
        // Scan all files in the directory
        $files = array();
        $ii = 0;
        foreach (scandir(PATH2ROOT.$imagefolder) as $file) {
            // exclude folders
            if ('.' === $file) continue;
            if ('..' === $file) continue;
            if ('Thumbs.db' == $file) continue;
            if (is_dir($file)) {
                //echo "<p>Ignoring folder ($file) [".__LINE__."]</p>".PHP_EOL;   // debug
                continue;
            }

            // exclude non-image files
            $path_parts = pathinfo($file);
            $ext = strtolower( $path_parts['extension'] );
            if (in_array($ext, $supportedFileExtensions)) {
                // cool, this file type is supported, do nothing
                //echo "Image: $file<br/>".PHP_EOL;   // debug
            } else {
                showWarning("Webmaster: Please remove file '$file' from the image folder '$imagefolder'. "
                            ."We don't support file extension '.$ext'.", __FILE__, __LINE__);
                continue;
            }

            // save this file in the array, and do bookkeeping at end-of-loop
            $files[] = $file;
            $ii++;
            if ($loopLimit && $ii>=$loopLimit) {
                showWarning("Ending loop early to scan images due to debug loop limit ($loopLimit).", __FILE__, __LINE__);
                break;
            }
        }
        natcasesort($files);    // "natural order" case-insensitive sorting

        // Note that both natsort and natcasesort maintain the key-value associations of the array. 
        // If you natsort a numerically indexed array, a for-loop will not produce the sorted order; 
        // a foreach loop, however, will produce the sorted order, but the indices won't be in numeric order. 
        // To break the original key-value associations, use array_values() on the sorted array, 
        // like so:
        $files = array_values( $files );
    }
    if (empty($files)) {
        showWarning("Sorry, no photos exist in the image folder ($imagefolder)", __FILE__, __LINE__);
    }
    return $files;
}
// ---------- showImageStatistics() ----------
function showImageStatistics($file_list) {
    // You should call this function after reading the folder full of images
    //echo "<h4>Image Statistics</h4>".PHP_EOL;
    //echo '<pre>$file_list = '; var_dump($file_list); echo "</pre>".PHP_EOL;  // debug

    // report statistics about images found
    echo '<ul class="statistics">'.PHP_EOL
        ."  <li><b>".sizeof($file_list)."</b> image files</li>".PHP_EOL 
        ."  <li>First image name: <b>".$file_list[0]."</b></li>".PHP_EOL 
        ."  <li>Last image name: <b>".$file_list[ sizeof($file_list)-1 ]."</b></li>".PHP_EOL
        ."</ul>".PHP_EOL;
}
// ---------- getImageMetadata() ----------
function getImageMetadata($image_filename) {
    global $imglistSHM; // from imagelist.php
    return; // TODO un-comment to resume development

    $info = null;
    if (empty($image_filename)) {
        return "Image filename was not specified.";
    } elseif (!file_exists($image_filename)) {
        showError("Image file was not found on disk. ($image_filename)", __FILE__, __LINE__);
    } else {

        // spike! try this...
        // read the image's IPTC metadata, and return array of all attributes
        $imageKeywords = array();

        // "exif_read_data()" reads the EXIF headers from an image file.
        // EXIF headers tend to be present in JPEG/TIFF images generated by digital cameras
        // and scanners, but unfortunately each digital camera maker has a different idea 
        // of how to actually tag their images, so you can't always rely on a specific Exif 
        // header being present. 
        // However, we depend on Adobe Lightroom saving images in a consistent format.
        if (!function_exists('exif_read_data')) {
            showError("Web host PHP does not support exif_read_data (contact the webmaster).", __FILE__, __LINE__);
            // If you are on Windows, edit your php.ini file and uncomment these two lines:
            //    extension=php_mbstring.dll
            //    extension=php_exif.dll 
            //
            // make sure the order of the extensions are same as above (exif depends on mbstring and hence mbstring should be loaded first).
        } else {
            //ini_set('exif.encode_unicode', 'UTF-8');
            $exif = exif_read_data($image_filename, 'ANY_TAG', TRUE);
            //echo "<!-- ".PHP_EOL;
            if ($exif === false) {
                showError("No header data found in '$image_filename'.", __FILE__, __LINE__);
            } else {
                //echo "<p class=\"alert alert-success\">Image contains headers</p>".PHP_EOL;   // debug
                //echo "$image_filename:<br />".PHP_EOL;    // debug

                //echo "<pre class=\"text-left\">".PHP_EOL; // debug
                //var_dump($exif);
                //echo "</pre>".PHP_EOL;

                echo "<div class=\"row text-left\" style=\"border: 1px dotted red\">".PHP_EOL;
                // first, output all plain strings
                foreach ($exif as $key => $section) {
                    if (is_array($section)) {
                        // nothing
                    } else {
                        echo "$key: $section<br />".PHP_EOL;
                    }
                }
                // second, output all sub-arrays
                foreach ($exif as $key => $section) {
                    if (is_array($section)) {
                        foreach ($section as $name => $val) {
                            echo "$key.$name: $val<br />".PHP_EOL;
                        }
                    } else {
                        // nothing
                    }
                }
                echo "</div>".PHP_EOL;

            }
            //echo " -->".PHP_EOL;
        }
    }
    return $info;
}
function getImageCaptionFromXML($image_filename) {
    // input: base filename, used as key to find a record in XML file
    // TODO - replace this XML lookup with code to examine image and retrieve IPTC metadata
    global $imglistSHM; // from imagelist.php
    if (!array_key_exists($image_filename, $imglistSHM)) {
        return $info = "Image not found in database ($image_filename)";
    } else {
        $info = $imglistSHM[$image_filename];   // TODO: retrieve array of image info
    }

    if (is_array($info)) {
        $caption = $info[1];  // normal, image was found
    } else {
        $caption = $info;     // failed, return error message
    }
    return $caption;
}
function serializeToDisk($data, $outputfile) {
    // Measurements show 'serialize' is faster than XML or JSON output
    $string = serialize($data);
    file_put_contents($outputfile, $string);
}
function unserializeFromDisk($filename) {
    // Measurements show 'serialize' is faster than XML or JSON output
    $string = file_get_contents( $filename );
    return unserialize($string);
}
function makeLinkTargetToDetailPage($detailPage, $imageFilename, $searchKeys, $searchText) {
    // Given all the parts of a link, compose a link target:
    // As in: Currently browsing _(PEOPLE)_AND_(1900S)_AND_"BALLARD"_ images.
    //                           ====key=========key==========text===
    // used by: detail.html, results.html
    $linkTarget = $detailPage;

    $linkTarget .= '?file='.urlencode($imageFilename);

    if (!empty($searchKeys)) {
        $ii = 1;
        foreach ($searchKeys as $key) {
            $urlKey = urlencode($key);
            $linkTarget .= sprintf('&key%s=%s', $ii, $urlKey);
            $ii++;
        }
    }

    if (!empty($searchText)) {
        $urlText = urlencode($searchText);
        //echo "<!-- common.php[".__LINE__."] searchText = $urlText -->".PHP_EOL;  // debug
        $linkTarget .= sprintf('&text=%s', $urlText);
    }

    return $linkTarget;
}
function makeLinkTargetFromList($webpage, $keywords, $searchText, $anchor='') {
    // Given an array of zero or more keywords and some text, compose a link target:
    // As in: Currently browsing _(PEOPLE)_AND_(1900S)_AND_(SWIM)_ image 2 of 7.
    //                           =================================
    // used by: detail.html, results.html
    $link_target = $webpage;

    $ii = 1;
    if (!empty($keywords)) {
        // compose the target for links using an array of searchable keywords
        foreach ($keywords as $item) {
            $link_target .= ($ii==1) ? '?' : '&';
            $link_target .= "key$ii=".urlencode($item);
            $ii++;
        }
    }

    if (!empty($searchText)) {
        $link_target .= ($ii==1) ? '?' : '&';
        $link_target .= "text=".urlencode($searchText);
    }

    if (!empty($anchor)) {
        // tell the web browser to position the browser window to a given anchor tag, e.g. <a id='results'>
        // the URL always puts the anchor tag at the trailing end of the URL
        // defined by '$resultsAnchorTag' in custom.php
        $link_target .= $anchor;
    }
    return $link_target;
}

// --------- shuffle associative array -----
function shuffle_with_keys($array, $maxcount) {
    // input:  array( [0]=>array(), [1]=>array(), ...)
    // Note: Cannot shuffle Objects such as SimpleXML array
    if (empty($maxcount)) {
        showError("Error: Missing 'maxcount' for shuffle_with_keys().", __FILE__, __LINE__);
    }
    if (!is_array($array)) {
        showError("Error: Illegal non-array argument to shuffle_with_keys().", __FILE__, __LINE__);
    }

    $keys = array_keys($array); // extract only keys from the array
    shuffle($keys);             // shuffle the keys

    $aux = array();             // auxiliary array to hold the new order
    $ii = 1;
    foreach($keys as $key) {    // iterate thru the new order of the keys
      $aux[$key] = $array[$key];// insert the key, value pair in its new order
      unset($array[$key]);      // remove the element from the old array to save memory

      if ($ii++ >= $maxcount) break;
    }

    return $aux;
} 
