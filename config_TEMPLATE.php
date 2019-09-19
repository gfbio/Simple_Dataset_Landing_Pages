<?php
//
//    RENAME THIS FILE TO config.php
//
//any of the settings described in this file, may be overwritten by the project specific settings file: config_<project-name>.php

//the base URL that shows the directory under which the files index.php and page.php are located
$url_base = "http://example.org/data/";

//a php array containing the project names that should be listed in the page of the base URL. 
//	A project is only listed there if it is contained in this array and if the corresponding 
//	directory exists on the server. Projects that are not listed in here can still be accessed 
//	by calling the corresponding URL.
$projects = array("projectA","example");

//the path to the directory which contains materials, such as images or style sheets
$materials_directory = $url_base."materials/";

//the download icon to be displayed in the version table
$download_icon = $materials_directory."download.png";

//the directory where the Creative Commons logos are located
$cc_icons_directory = $materials_directory."cc_icons/large/";

//the name of the publisher of a dataset that will be used when generating the DataCite JSON for the DOI Registration
$publisher = "";

//a header to be included in beginning of the page in the header section (not to be confused with the head element in the HTML code). This can be HTML code.
$header = "";

//a footer to be included at the bottom of the page in the footer section. This can be HTML code.
$footer = "";

