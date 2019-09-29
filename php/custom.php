<?php
// custom.php
// Custom global values for www.nestedkeywords.com

// Please use PHP best practices for Github:
//      https://odan.github.io/2018/04/05/php-best-practices.html

$mySiteUpdate = 'September 20, 2019';

$mySubdomain = '';
$myDomain = 'nestedkeywords';
$myTLD = '.com';

// optional
$myOwnerName = 'Barry Hansen';         // site owner's name
$myWebmasterEmail = 'barry@electromagneticsoftware.com';  // email address to notify when things break

// image folder names
// Note: Use names relative to site root. 
//       Individual PHP files must add relative paths as needed.
//       If you move PHP source to a new folder, you must manually adjust the new relative path.
$myLargeImageFolder = 'photos/large';   // folder with all the images that contain hierarchical keywords
$mySmallImageFolder = 'photos/small';   // folder with matching thumbnail images
$supportedFileExtensions = array('jpg', 'png', 'gif', 'jpeg'); // use lowercase here

// data file names
$imageDatabaseBasename = 'xml/image-db';             // filename containing our Image database (file extension added later)
$keywordTreeBasename   = 'xml/keyword-tree-db';      // filename of hierarchical keyword database (extension added later)
$keywordFlatBasename   = 'xml/keyword-flat-db';      // filename of flat keyword->files database (extension added later)
$autocompleteFilename  = 'js/autocomplete-keyword.js'; // filename of keyword list in javascript for autocomplete

// CSV file names
$csvFilename = 'image-list.csv'; // output spreadsheet file containing all titles and captions

// HTML file names
$searchPage = 'search.html';     // URL to search for photos
$resultPage = 'search.html';     // URL to show list of search results (currently same as Search page)
$resultsAnchorTag = '#results';  // use this at the end of URLs to position browser at the search results
$detailPage = 'detail.html';     // URL to show one photo's details

// pre-defined keywords
$noKeywordsKeyset = array('Attributes|Metadata|No keywords');  // auto applied to images that have no keywords

// debugging
$loopLimit = 0;          // debug, set to 0 to disable the limit
$levelLimit = 0;         // debug, set to 0 to disable the limit
$indent = '          ';  // help format generated HTML

// ----------------------------------------------------------------------
function showNavBar() {
    require($path2php.'sitenav.html');
}
// ----------------------------------------------------------------------
function showAdminNavBar() {
?>
    <nav id="navigation" class="navbar navbar-default">
        <div class="container-fluid">
            <ul class="nav navbar-nav" id="main-nav">
                <li><a href="/index.html">Return to Photo Gallery</a></li>
            </ul>
        </div>
    </nav>
<?php 
}
// ----------------------------------------------------------------------
function showFooter() {
    ?>
        <address>
            <b>www.NestedKeywords.com</b> 
            <br/>GitHub 
            <a href="https://github.com/barry-ha/Lightroom-nested-keywords">barry-ha/Lightroom-Nested-Keywords</a>
        </address>
    <?php 
}
