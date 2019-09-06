<?php 
/* File: image-database.php
 * Purpose:
 *       Everything needed to completely refresh our image information database
 *       e.g. image filename, title, caption
 * Used by:
 *       build-keyword-database.html
 * Based on:
 *       
 * Author:
 *       2018-12-03 Barry Hansen, barry.hansen@gmail.com
 */
// ----- begin edit -----
//    (settings are in 'custom.php')
// ----- end edit -----

require_once($path2php.'adobe-xmp-for-php.php');  // for reading Lightroom's hierarchical keywords from images
require_once($path2php.'custom.php');     // for $myLargeImageFolder and other globals

function buildImageDatabase($imagefolder, $file_list) {
    // Top level routine that contains all work necessary to read all the image files,
    // build a database of image information, and write the result to disk
    //
    // input:   $imagefolder = where to read all the image files from (relative to site root)
    //          $file_list = array(file1, file2, ...) = getListOfImages(folder)
    // returns: $image_db = array(filename => array(title, caption))
    global $path2root;
    $image_db = array();   // container for image metadata

    $adobePHP =& adobeXMPforPHP::get_instance();
    $ii = 0;    // loop counter
    foreach ($file_list as $file) {
        // read lightroom keywords for one file
        $fqname = "$path2root$imagefolder/$file";
        $image_xmp = $adobePHP->get_xmp( $fqname );
        //$title = trim( $image_xmp['Headline'] );  // 2019-08-01: no longer used, because 'Headline' is part of IPTC 
                                                    // and we want to get Lightroom's title instead
        $title = trim( $image_xmp['Title'][0] );
        $caption = trim( $image_xmp['Description'][0] );
        //$adobePHP->dump_xmp($image_xmp);    // debug
        //echo PHP_EOL."<pre>"; var_dump($image_xmp); echo "</pre>".PHP_EOL;               // debug
        //echo "$ii) $file : title = $title<br/>".PHP_EOL;   // debug
        //echo "$ii) $file : caption = $caption<br/>".PHP_EOL;   // debug

        // add image to database (even if it has no title)
        $image_db[$file] = array('title'=>$title, 'caption'=>$caption);

        if (empty($title)) {
            // show helpful warning to webmaster
            global $detailPage;
            $urlFolder = $imagefolder;              // do not 'urlencode' the folder name because it would damage slashes
            $urlFilename = rawurlencode($file);     // replace spaces with '%20' for valid URL
            $urlFQname = "$urlFolder/$urlFilename";

            showWarning(sprintf('Image <a href="%s">%s</a> does not contain a title ', $urlFQname, $file)
                       .sprintf('<a href="%s?file=%s">(details)</a> ', $detailPage, rawurlencode($file), $file)
                       ."(see Lightroom's IPTC Headline).");
        }
        //debugDump($image_db, __FILE__, __LINE__, 'buildImageDatabase()', '$image_db');    // debug
        $ii++;
        global $loopLimit;     // see custom.php. For production, set to 0 to disable the limit
        if ($loopLimit && $ii>=$loopLimit) {
            showWarning("Ending loop early to count images after $loopLimit files.", __FILE__, __LINE__);   // debug
            break;
        }
    }

    return $image_db;
}

// ---------- 
function lookupTitleCaption( $filenames, $image_db ) {
    // Given a list of image filenames, look up their Title and Caption from the database
    //
    // input:   $filenames = array( file1, file2, file3, ...)
    //          $image_db  = array( file1=>array(title1, caption1),
    //                              file2=>array(title2, caption2), ...)
    //
    // returns: $images = array( file1=>array(title1, caption1, link1),
    //                           file2=>array(title2, caption2, link2),
    //                           file3=>array(title3, caption3, link3), ... )
    $images = array();
    //debugDump($filenames, __FILE__, __LINE__, 'lookupTitleCaption()', '$filenames');    // debug
    if (empty($filenames)) {
        //showWarning('Received request to retrieve titles and captions, '
        //         .'but no filenames were included in the request.', __FILE__, __LINE__);
    } else {
        global $detailPage;
        foreach($filenames as $filename) {
            $title = $image_db[$filename]['title'];
            $caption = $image_db[$filename]['caption'];
            $link = $detailPage.'?file='.urlencode($filename);

            $images[$filename] = array('title'=>$title, 'caption'=>$caption, 'link'=>$link);
        }
        //debugDump($images, __FILE__, __LINE__, 'lookupTitleCaption()', '$images');  // debug
    }
    return $images;
}

function showTime($start, $end, $text) { 
    $elapsednum = number_format( $end - $start, 4).' ms';
    echo "<!-- $elapsednum Elapsed time in $text -->".PHP_EOL;    // debug - remove after choosing between readImageDatbaseXML/PHP
}
// ---------- writeImageDatabase ----------
//    and     writeImageDatabaseXML
//    and     writeImageDatabasePHP
function writeImageDatabase($image_db, $outputfile) {
    // Debug development: try different methods of saving data structures to disk.
    // To SAVE data, run all three methods and compare timing (although time is not
    // critical since this SAVE routine is run only infrequently by an administrator)

    $t0 = microtime();
    writeImageDatabaseXML($image_db, $outputfile.'.xml');
    showTime( $t0, microtime(), 'writeImageDatabaseXML');   // debug

    $t0 = microtime();
    serializeToDisk($image_db, PATH2ROOT.$outputfile.'.php');         // from common.php
    showTime( $t0, microtime(), 'writeImageDatabasePHP');   // debug
}

function writeImageDatabaseXML($image_db, $outputfile) {
    // Save the image database into an XML file
    //
    // This is the converse of readImageDatabase()
    //
    // SimpleXML is good for parsing existing XML documents, but it can't create a new XML file from scratch.
    // The easiest way to generate an XML document is to build a PHP array whose structure mirrors 
    // the XML document and then to iterate through the array, printing each element with 
    // appropriate formatting.  https://www.tutorialspoint.com/php/php_and_xml.htm

    // Example output:
    //      <shmimages>
    //          <image>
    //              <name>SHM-001.jpg</name>
    //              <title>1922 Lake Forest Park</title>
    //              <caption>Also see photo LFP-1922</caption>
    //          </image>
    //          (repeat)
    //      </shmimages>

    //debugDump($image_db, __FILE__, __LINE__, 'writeImageDatabaseXML()', '$image_db');   // debug
    $xml = new SimpleXMLElement('<shmimages/>');
    $today = date('r');
    $xml->addAttribute('generated', $today);

    // Process list of images
    foreach ($image_db as $filename => $info) {
        //echo "<br/>\$filename => \$info: &nbsp; $filename => ".$info['title'].PHP_EOL;   // debug

        if (is_array($info)) {
            $image = $xml->addChild('image');
            $image->name = $filename;
            $image->title = $info['title'];
            $image->caption = $info['caption'];
        } else {
            showError("Internal Error: Expected array but got key=>value of ($filename)=>($info).", __FILE__, __LINE__);
        }

    }

    //echo '<pre>$xml = '; echo $xml->asXML(); echo '</pre>'.PHP_EOL; // debug
    $xml->asXML(PATH2ROOT.$outputfile);
}

// ---------- readImageDatabase ----------
//    and     readImageDatabaseXML
//    and     readImageDatabasePHP
//    and     readImageDatabaseJSON
function readImageDatabase($imageDatabaseBasename) {
    // Debug development: try different methods of reading data structures from disk.
    // To READ data, run all three methods and compare timing. 
    // Here, elapsed time is important because every web visitor must wait for it.
    // Result:
    //      Assuming 700 images, 400 keywords, we measured:
    //      0.0087 ms Elapsed time in readImageDatabaseXML
    //      0.0063 ms Elapsed time in readImageDatabaseJSON
    //      0.0018 ms Elapsed time in readImageDatabasePHP (winner!)

    $t0 = microtime();
    $imginfo = readImageDatabaseXML($imageDatabaseBasename);
    showTime( $t0, microtime(), 'readImageDatabaseXML');   // debug

    //$t0 = microtime();
    //$imginfo = readImageDatabasePHP($imageDatabaseBasename);
    //showTime( $t0, microtime(), 'readImageDatabasePHP');   // debug

    //$t0 = microtime();
    //$imginfo = readImageDatabaseJSON($imageDatabaseBasename.'.json');
    //showTime( $t0, microtime(), 'readImageDatabaseJSON');   // debug

    return $imginfo;
}

function readImageDatabaseXML($imageDatabaseBasename) {
    // Fetch the image database into a data structure
    //
    // This is the converse of writeImageDatabase()

    // Validate input file
    $filename = $imageDatabaseBasename.'.xml';
    if (file_exists(PATH2ROOT.$filename)) {

        // Read XML, return object of type SimpleXML
        $simplexml = simplexml_load_file(PATH2ROOT.$filename);
        if ($simplexml === FALSE) {
            showError("Unable to load XML file '$filename', ",__FILE__, __LINE__);
            return;
        }

        // Convert SimpleXML Object into plain strings, 
        // to avoid casting everything to strings later and
        // plain strings are required for shuffle($keys) to randomize the list
        $imginfo = array();
        foreach ($simplexml as $obj) {
            $filename = (string)$obj->name;
            $imginfo[$filename]['title'] = (string)$obj->title;
            $imginfo[$filename]['caption'] = (string)$obj->caption;
        }

    } else {
        showError("Internal error: Unable to load image database. XML file was not found. "
                ."<br/>readImageDatabaseXML('$filename').", __FILE__, __LINE__);

    }
    return $imginfo;
}

function readImageDatabasePHP($filename) {
    $image_db = file_get_contents($filename);
    return unserialize($image_db);
}

function readImageDatabaseJSON($filename) {
    $image_db = file_get_contents($filename);
    return json_decode($image_db);
}

