<?php 

include("utils.php");
include("config.php");

//get URL parameters
$project = get_url_parameter('project');
$dataset = get_url_parameter('dataset');
$selected_version = get_url_parameter('version');
$format = get_url_parameter('format');

//load project specific settings
if (file_exists("config_".$project.".php")) {
	include("config_".$project.".php");
}

$string = "";

//check if project and dataset exist 
if ((file_exists($project) && is_dir($project))) {
	$file = $project."/".$dataset."/data.json";
	if (file_exists($file)) {
		$string = file_get_contents($file);
	}else{
		http_response_code(404);
		header('Content-Type: text/plain; charset=utf-8');
		echo "Unknown dataset '".$project."/".$dataset."'";
		die();
	}
}else{
	http_response_code(404);
	echo "Unknown project '".$project."'";
	die();
}

//remove all empty strings or arrays from the json tree, including texts that only consists of white spaces.
//it works recursively up the json tree and removed any element of which all child elements have been removed.
//this avoids double checks later on. Instead of checking:
//    if(array_key_exists('title',$json) && strlen($json['title']) > 0)){
// it is now possible to just check for
//    if(array_key_exists('title',$json)){
// if the array key exists, it has content that can be displayed
$json = json_clean(json_decode($string, true));

if($format=="application/json datacite" || $format=="application/json+datacite"){
	header('Content-Type: application/json; charset=utf-8');
?>
{
  "url": <?php echo json_encode($url_base.$project."/".$dataset."/".$selected_version) ?>,
  "types": {
    "resourceTypeGeneral": "Dataset"
  },
  "creators": [
    <?php
	$joiner = "";
	foreach($json['creators'] as $creator) {
		if(array_key_exists('name',$creator)){
			echo $joiner.'{
      "name": '.json_encode($creator['name']).',
      "nameType": "Personal",
      "givenName": "",
      "familyName": "",
      "affiliation": [],
';
			if(array_key_exists('orcid',$creator)){
			echo '      "nameIdentifiers": [
        {
          "schemeUri": "https://orcid.org",
          "nameIdentifier": '.json_encode("https://orcid.org/".$creator['orcid']).',
          "nameIdentifierScheme": "ORCID"
	    }
      ]';
			}else{
				echo '      "nameIdentifiers": []';
			}
			echo '
    }';
			$joiner = ",\n    ";
		}
	}
	?>

  ],
  "titles": [
    {
      "lang": "en",
      "title": <?php echo json_encode($json["title"]) ?>,
      "titleType": null
    }
  ],
  "publisher": "<?php echo $publisher ?>",
  "descriptions": [
    {
      "lang": "en",
      "description": <?php echo json_encode($json["details"]) ?>,
      "descriptionType": "Abstract"
    }
  ],
  "state": "draft"
}
<?php
}else{
	echo "unknown format";
}
?>

