<?php
//load the URL parameter and return it. If it is not set, return the given default or an empty string.
function get_url_parameter($parameter, $default = ""){
	$value = $default;
	if(isset($_GET[$parameter])){
		$value = $_GET[$parameter];
	}else if(isset($_POST[$parameter])){
		$value = $_POST[$parameter];
	}
	return $value;
}


//turn URLs within a text into clickable links
function addLinks($text) {
	$linkRegEx = '/(https?:\/\/[^\s)]+)/';
	$replaceTemplate = '<a href="$1" target="_blank">$1</a>';
	return preg_replace($linkRegEx, $replaceTemplate, $text);
}

//recursively removes empty or non set fields in a json object
function json_clean($json){
	foreach($json as $key => $value){
		if(gettype($value) == "array" || gettype($value) == "object"){
			$value = json_clean($value);
		}
		if(gettype($value) == "NULL") {
			unset($json[$key]); 
		}else if((gettype($value) == "array" && sizeof($value)==0)) {
			unset($json[$key]); 
		}else if(gettype($value) == "string" && strlen(trim($value))==0) {
			unset($json[$key]); 
		}else{
			$json[$key]=$value;
		}
	}
	return $json;
}

//clean a doi link that start with any of the discouraged prefixed, such as http:// , https://dx. https://www. or doi: and convert it into the recommended https://doi.org link
function clean_doi_link($link){
	return preg_replace("/^(https?:\/\/(dx\.|www\.)?doi\.org\/|doi:)/","https://doi.org/",$link);
}

//generated a link to the identifier given and if it is a doi link, the class will be set accordingly, so that a doi icon can be displayed next to the link
function generate_doi_link($link){
	$link = clean_doi_link($link);
	if(substr( $link, 0, 7 ) === "http://" || substr( $link, 0, 8 ) === "https://" ){
		$class = "";
		if(substr( $link, 0, 16 ) === "https://doi.org/" ){
			$class = "class=\"doi-link\"";
		}
		return "<a href=\"".$link."\"".$class.">".$link."</a>";
	}else{
		return $link;
	}
}

//detect the type of creative commons license, either by the URL of license or the abbreviated name, which either needs to stand at the beginning of the string or within parentheses  
function get_creative_commons_icon($license){
	if (preg_match('#^https?://creativecommons.org/licenses/by-nc-nd/#i', $license) === 1 || preg_match('#(^|\()CC[ -_]BY[ -_]NC[ -_]ND#i', $license) === 1 ) {
		return "by-nc-nd.png";
	}else if (preg_match('#^https?://creativecommons.org/licenses/by-nc-sa/#i', $license) === 1 || preg_match('#(^|\()CC[ -_]BY[ -_]NC[ -_]SA#i', $license) === 1 ) {
		return "by-nc-sa.png";
	}else if (preg_match('#^https?://creativecommons.org/licenses/by-nc/#i', $license) === 1 || preg_match('#(^|\()CC[ -_]BY[ -_]NC#i', $license) === 1 ) {
		return "by-nc.png";
	}else if (preg_match('#^https?://creativecommons.org/licenses/by-nd/#i', $license) === 1 || preg_match('#(^|\()CC[ -_]BY[ -_]ND#i', $license) === 1 ) {
		return "by-nd.png";
	}else if (preg_match('#^https?://creativecommons.org/licenses/by-sa/#i', $license) === 1 || preg_match('#(^|\()CC[ -_]BY[ -_]SA#i', $license) === 1 ) {
		return "by-sa.png";
	}else if (preg_match('#^https?://creativecommons.org/licenses/by/#i', $license) === 1 || preg_match('#(^|\()CC[ -_]BY#i', $license) === 1 ) {
		return "by.png";
	}else if (preg_match('#^https?://creativecommons.org/publicdomain/zero/#i', $license) === 1 || preg_match('#(^|\()CC[ -_]?0#i', $license) === 1 || preg_match('#(^|\()CC[ -_]ZERO#i', $license) === 1 ) {
		return "cc-zero.png";
	}else if (preg_match('#^https?://creativecommons.org/publicdomain/mark/#i', $license) === 1 || preg_match('#(^|\()PUBLIC DOMAIN#i', $license) === 1 || preg_match('#(^|\()PD#i', $license) === 1 ) {
		return "publicdomain.png";
	}else {
		return "";
	}
}