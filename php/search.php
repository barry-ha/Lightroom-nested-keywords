<?php
// File:      search.php
// Purpose:   Functions for searching and displaying results in HTML
// 2018-12-06 barry@electromagneticsoftware.com

// ---------- find images with key1 AND key2 AND key3 ----------
function searchForKeywords($searchKeys, $flat_keyword_database) {
    // input:   array of strings that contains keywords to look for
    //          data structure representing our flat keyword database
    //          e.g. array(key1=>array(file1, file2, ...),
    //                     key2=>array(file3, file4, ...))
    // output:  $imageList = array( 'file1', 'file2', 'file3', ...)

    global $myLargeImageFolder;
    $imageList = getListOfImages($myLargeImageFolder);   // result: start with ALL files (then we subtract most of them below)

    if (empty($searchKeys)) {
        //showError('A search was requested but it didn\'t include keywords to search for.', __FILE__, __LINE__);
    } else {
        //debugDump( $searchKeys, __FILE__, __LINE__, 'searchForKeywords()', '$searchKeys' );   // debug
        //debugDump( $flat_keyword_database, __FILE__, __LINE__, 'searchForKeywords()', '$flat_keyword_database');   // debug

        // We already have a table that tells us all the files for each keyword
        // so let PHP array processing do all of the work to look them up
        foreach ($searchKeys as $keyword) {
            $keyword = trim($keyword);
            if (array_key_exists($keyword, $flat_keyword_database)) {
                $foundFiles = $flat_keyword_database[$keyword];
                $imageList = array_intersect( $imageList, $foundFiles );
                //debugDump( $imageList, __FILE__, __LINE__, 'searchForKeywords() found something, the result is', '$imageList'); // debug
            } else {
                showError("Keyword '<b>$keyword</b>' is not in our list of keywords. "
                        ."Please check for upper/lower case and exact spelling. "
                        ."'<b>$keyword</b>' is ignored for this search.",
                        __FILE__, __LINE__);
            }
        }
    }
    //debugDump($imageList, __FILE__, __LINE__, 'searchForKeywords()', '$imageList'); // debug
    return $imageList;
}

// ---------- showKeywordSearchList ----------
function showKeywordSearchList($keywordList) {
    // Echo the search parameters that brought us to a page of results
    // input: array of strings
    //debugDump($keywordList, __FILE__, __LINE__, 'showKeywordSearchList()', '$keywordList');    // debug
    if (!empty($keywordList)) {
        echo "Keyword = ";
        $ii = 0;
        foreach ($keywordList as $oneKeyword) {
            if ($ii>0) { echo "and "; }
            echo "<b>$oneKeyword</b> ";
            $ii++;
        }
        echo PHP_EOL;
    }
}

// ---------- showTextSearchList ----------
function showTextSearchList($text) {
    // Echo the search parameter that brought us to a page of results
    // input: string
    if (!empty($text)) {
        printf(" Containing text = '<b>%s</b>'".PHP_EOL, $text);
    }
}

// --------- search for text ----------
function searchForText( $searchText, $filenames, $files_to_titles ) {
    // Top level routine to find images that contain specified text 
    // input:   $searchText = needle = string
    //          $filenames  = haystack = array of filenames
    //          $files_to_titles = database

    //debugDump($searchText, __FILE__, __LINE__, 'searchForText()', '$searchText');   // debug
    //debugDump($filenames, __FILE__, __LINE__, 'searchForText()', '$filenames');   // debug

    $resultFilenames = array();

    // clean and parse the user's search string
    unitTestAnalyzeSearchText();        // debug
    $searchWords = analyzeSearchText( $searchText );
    //debugDump($searchWords, __FILE__, __LINE__, 'searchForText()', '$searchWords'); // debug
    //debugDump($filenames, __FILE__, __LINE__, 'searchForText() started with these files', '$filenames'); // debug

    if (empty($searchWords) || empty($searchWords[0]) || empty($filenames)) {
        // no words to search for, return the original list of files
        $resultFilenames = $filenames;
        //debugDump($resultFilenames, __FILE__, __LINE__, 'searchForText() has no text to search for, returns', '$resultFilenames'); // debug

    } else {
        // we have one or more words to look for, examine given file list for each one
        foreach($filenames as $filename) {
            $title = $files_to_titles[$filename]['title'];
            $caption = $files_to_titles[$filename]['caption'];

            $found = true;  // assume success
            foreach ($searchWords as $searchWord) {
                // restore quoted strings to original format, by replacing temporary pipe character with a blank
                $searchWord = str_replace('|', ' ', $searchWord); 

                // we need to have ALL search-words found somewhere in the image to confirm success
                if (stripos($title, $searchWord) === false
                && stripos($caption, $searchWord) === false
                && stripos($filename, $searchWord) === false) {
                    $found = false;     // failed to find one of the words in this image
                    break;
               }
            }
            if ($found) {
                $resultFilenames[] = $filename; // success, add this image to search results
            }
        }
    }
    //debugDump($resultFilenames, __FILE__, __LINE__, 'searchForText() ended with', '$resultFilenames');   // debug
    return $resultFilenames;
}

// --------- unit test "analyzeSearchText" ----------
function verifyAnalysis($given, $expected, $file, $line) {
    $ret = analyzeSearchText($given);
    if ($expected != $ret) {
        showError("Unit test fail! given '$given' did not match expected", $file, $line);
        debugDump($ret, __FILE__, __LINE__, 'verifyAnalysis()', '$ret');    // dump
        debugDump($expected, __FILE__, __LINE__, 'verifyAnalysis()', '$expected');    // dump
    }    
}
function unitTestAnalyzeSearchText() {
    verifyAnalysis('a',      array( 'a' ),                                         __FILE__, __LINE__ );
    verifyAnalysis('  a  ',  array( 'a' ),                                         __FILE__, __LINE__ );
    verifyAnalysis('a b',    array( 'a', 'b'),                                     __FILE__, __LINE__ );
    verifyAnalysis('a b c',  array( 'a', 'b', 'c'),                                __FILE__, __LINE__ );

    verifyAnalysis('"e f"',    array( 'e|f' ),                                     __FILE__, __LINE__ );
    verifyAnalysis('"g h i"',  array( 'g|h|i' ),                                   __FILE__, __LINE__ );
    verifyAnalysis('"j k l m"',array( 'j|k|l|m' ),                                 __FILE__, __LINE__ );
    verifyAnalysis('"z"',      array( 'z' ),                                       __FILE__, __LINE__ );

    verifyAnalysis('""',    array( '' ),                                           __FILE__, __LINE__ );
    verifyAnalysis('" "',   array( '|' ),                                          __FILE__, __LINE__ );
    verifyAnalysis('"  "',  array( '||' ),                                         __FILE__, __LINE__ );
    verifyAnalysis('"   "', array( '|||' ),                                        __FILE__, __LINE__ );

    verifyAnalysis('g "h i" j', array('g', 'h|i', 'j'),                            __FILE__, __LINE__ );
    verifyAnalysis('g "h i j" k', array('g', 'h|i|j', 'k'),                        __FILE__, __LINE__ );
    verifyAnalysis('"one two" "three four"', array('one|two', 'three|four'),       __FILE__, __LINE__ );
    verifyAnalysis('"one two" alone "three four"', array('one|two', 'alone', 'three|four'), __FILE__, __LINE__ );
    verifyAnalysis('"no"space"here"', array('nospacehere'),                        __FILE__, __LINE__ );

    verifyAnalysis('"unbalanced-leading-quote', array('unbalanced-leading-quote'), __FILE__, __LINE__ );
    verifyAnalysis('unbalanced-trailing-quote', array('unbalanced-trailing-quote'),__FILE__, __LINE__ );
}

// --------- analyze search words ----------
function analyzeSearchText( $searchText ) {
    // input: string containing user's search phrase
    //        allowed items include:
    //        1. a single word
    //        2. multiple words separated by spaces
    //        3. literal phrases enclosed with double quotes
    // strategy:
    //   Single words are separated just fine with space delimiters and explode() function.
    //   But what about quoted strings? 
    //   We temporarily change them to one "word" by removing quotes and replacing spaces with pipe (|).
    //   Later the search function fixes the replacement pipe before comparisons.
    // implementation:
    //   Examine search string from left to right, letter by letter
    //   When a double quote is found, start replacing blanks with pipes
    //   When next double quote is found, stop replacing blanks
    //   Finally, explode the whole batch into an array of separate words
    $result = '';
    $replacing = false;
    $searchText = trim( rtrim( $searchText ));  // remove user's leading and trailing spaces

    for ($ii = 0; $ii < strlen($searchText); $ii++){
        $char = $searchText[$ii];
        if ($char == '"') {
            $replacing = !$replacing;

        } elseif ($searchText[$ii] == ' ') {
            $result .= $replacing ? '|' : ' ';

        } else {
            $result .= $char;
        }
    }

    return explode(' ', $result);
}

// --------- search results ----------
function showSearchResultThumbnails($images, $indent='', $searchKeys=NULL, $searchText=NULL) {
    // used by: browse.html - for list of random images
    // used by: results.html - for list of files found
    //
    // Input:  $images = array( 'file1'=>array(title,caption), 'file2'=>array(title,caption), ...)
    //         $indent = string of blanks
    //         $searchKeys = array of keywords (optional)
    //         $searchText = string of text (optional)
    // Output: Generates HTML
    //

    //debugDump($images, __FILE__, __LINE__, 'showSearchResultThumbnails()', '$images');    // debug
    //debugDump($searchText, __FILE__, __LINE__, 'showSearchResultThumbnails()', '$searchText');    // debug
    
    global $detailPage;  // from custom.php
    global $mySmallImageFolder; // from custom.php
    if (!empty($images)) {
        foreach ($images as $imageFilename => $imgMetadata) {

            // get image info from array
            $title   = $imgMetadata['title'];
            $caption = $imgMetadata['caption'];

            // compute values for HTML
            // Note: Shoreline Historical Museum uses filename == Catalog ID
            $catalogID = pathinfo($imageFilename, PATHINFO_FILENAME); // returns base filename without the trailing extension
            $linkTarget = makeLinkTargetToDetailPage($detailPage, $imageFilename, $searchKeys, $searchText);
            $urlFilename = sprintf('%s/%s', $mySmallImageFolder, rawurlencode($imageFilename) );   // replace spaces with '%20' for valid URL

            // generate HTML for thumbnails
            echo $indent.'<div class="oneimage">'.PHP_EOL
                .$indent."  <a href=\"$linkTarget\">".PHP_EOL
                .$indent."    <img src=\"$urlFilename\" alt=\"$catalogID\" title=\"$catalogID\" />".PHP_EOL
                .$indent."    <div class=\"title\">$title</div>".PHP_EOL
                .$indent."    <div class=\"caption\">$catalogID</div>".PHP_EOL
                .$indent.'  </a>'.PHP_EOL
                .$indent.'</div>'.PHP_EOL;
        }
    }
}

