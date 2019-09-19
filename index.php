<?php 

$header = "";
$footer = "";

include("utils.php");
include("config.php");

$project = get_url_parameter('project');

$projects_checked = array();
foreach($projects as $project_alt) {
	if ((file_exists($project_alt) && is_dir($project_alt))) {
		$projects_checked[] = $project_alt;
	}
}
	
$title = "Dataset Overview";
$error = false;

if($project == ''){
	if(sizeof($projects_checked) == 1){
		header("Location: ".$url_base.$projects_checked[0]."/");
		die();
	}	
	$title = "Project Overview";
	$error = true;
}else{
	if (!(file_exists($project) && is_dir($project))) {
		$error = true;
		$title = "Project Overview";
		http_response_code(404);
	}
}

if (file_exists("config_".$project.".php")) {
	include("config_".$project.".php");
}
?>
<!doctype html>
<html>
<head>
	<meta charset="utf-8">
	<title><?php echo $title; ?></title>
	<link rel="stylesheet" type="text/css" href="<?php echo $url_base; ?>style.css"/>
	<?php if (file_exists("style_".$project.".css")) { ?>
	<link rel="stylesheet" type="text/css" href="<?php echo $url_base; ?>style_<?php echo $project; ?>.css"/>	
	<?php } ?>
</head>

<body>
<div id="header" class="main-section">
	<?php 
	if(function_exists ("print_header")){
		print_header();
	}
	?>
</div>

<div id="content">
	<div id="main-info" class="main-section">
		<?php
		//only show content if no errors occurred so far. Error handling is done at the bottom.
		if(!$error){
		?>
		<h2>Datasets</h2>
		<ul>
		<?php
		$listing = array();
		foreach (new DirectoryIterator($project) as $fileInfo) {
			if($fileInfo->isDir() && !$fileInfo->isDot() && $fileInfo->getFilename() != "." && $fileInfo->getFilename() != ".." ) {
				$file = $project."/".$fileInfo->getFilename()."/data.json";

				if (file_exists($file)) {
					
					$string = file_get_contents($file);
					$json = json_decode($string, true);
					if(array_key_exists('published',$json) && ($json['published'] == "false" || $json['published'] == "hidden" )){
						continue;
					}
					
					$listing[$fileInfo->getFilename()] = $json['title'];
				}
			}
		}
		ksort($listing);
		foreach ($listing as $id => $title) {
			echo "<li><a href=\"". $id . "\"> ". $title . " (" . $id . ")</a></li>\n";
		}

?>
		</ul>
		<?php
		}else{
			if($project != ''){
				echo "		<h1>404</h1> The project you selected '".$project."' does not exist. Here are the available projects:";
			}
		?>
		<h2>Project Overview</h2>
		
		<?php
			if(sizeof($projects_checked)==0){
				echo "		<b>There are currently no public projects in this repository</b>";
			}else{
				echo "		<ul>";
				foreach($projects_checked as $project_alt) {					
					echo "			<li><a href=\"".$url_base.$project_alt."\">".$project_alt."</a></li>";
				}
				echo "		<ul>";
			}
		}
		?>
	</div>
</div>

<div id="footer">
<?php
		//include the footer specified in the settings files.
		echo $footer;
?>
</div>
</body>
</html>

