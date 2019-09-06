# Lightroom-nested-keywords
 A website to display and navigate Adobe Lightroom images that were tagged with hierarchical keywords. 
 This is written in HTML5, CSS3, PHP and based on the Bootstrap framework.

## Background

 Lightroom is a wonderful organizing tool for very large groups of photos.
 It can easily handle many thousands of images and offers several great 
 ways to quickly find them. An especially good method is to tag images 
 with keywords. You can organize keywords into a tree structure (hierarchies)
 to represent, for example, countries -> states -> cities -> neighborhoods.
 
  However, a limitation of Lightroom is its organizational schemes only work
 on single workstation. Although Lightroom can publish to the web, and it
 can embed keywords in metadata, the end result does not have any features
 to navigate by keyword. This project intends to fill that need. 

 A well-chosen tree structure (taxonomy, or controlled vocabulary) along 
 with consistently applying these keywords will be tremendously helpful
 to visitors on the world wide web.

 **Lightroom-nested-keywords** provides PHP functions to process embedded metadata, and an example website to display and navigate images by keyword.

## Requirements
* PHP >= 5.4
* Web server

## Non-requirements
* Does **not** require any SQL database support features. All data is stored
in XML files. It doesn't need or use MySQL or MSSQL or any other database.
* Does **not** require Lightroom. This software will process metadata stored
in image files; it doesn't matter how it was embedded in the file. 
For example, the titles and captions and keywords *could* have been written by [Photo Mechanic](https://home.camerabits.com/) or any other software.

## Installation
* Extract these files into a folder for your web server
* Modify `php/custom.php` for your own domain name

## Usage
* In a web browser, visit the home page 'index.html'
* Explore the features using example photos included

## Try your own images:
* Put full-size images in `photos/large` (typically 640px on longest edge)
* Put thumbnail images in `photos/small` (typically 120px on longest edge)
* In a web browser, update the cached image info by visiting `admin/build-keyword-database.html`

# License
`barry-ha\Lightroom-nested-keywords` is under Gnu GPL v3 license.
Please read the LICENSE file for further details.