<?php 

$header = "";
$footer = "";

include("utils.php");
include("config.php");

$project = get_url_parameter('project');
$dataset = get_url_parameter('dataset');
$selected_version = get_url_parameter('version');

//check if project specific settings page exists, if so include it
if (file_exists("config_".$project.".php")) {
	include("config_".$project.".php");
}


$string = "";

//check if project directory exists
if ((file_exists($project) && is_dir($project))) {
	//check if data file for project exists
	$file = $project."/".$dataset."/data.json";
	if (file_exists($file)) {
		$string = file_get_contents($file);
	}else{
		http_response_code(404);
		//write error message in the json content
		$string = '{"title":"Error 404: Could not find dataset \''.$project."/".$dataset.'\'!","error":"dataset","missing":"'.$project."/".$dataset.'"}';
	}
}else{
	//write error message in the json content
	http_response_code(404);
	$string = '{"title":"Error 404: Could not find project \''.$project.'\'!","error":"project","missing":"'.$project.'"}';
}

//remove all empty strings or arrays from the json tree, including texts that only consists of white spaces.
//it works recursively up the json tree and removed any element of which all child elements have been removed.
//this avoids double checks later on. Instead of checking:
//    if(array_key_exists('title',$json) && strlen($json['title']) > 0)){
// it is now possible to just check for
//    if(array_key_exists('title',$json)){
// if the array key exists, it has content that can be displayed
$json = json_clean(json_decode($string, true));

//the highlight version is either the specifically selected version from the query or the latest version, if none is selected
$highlight_version = null;
$highlight_version_text = "";
$versions = "";
if(array_key_exists('versions',$json)){ 
	$versions = $json['versions'];
	if($selected_version != ""){
		//there is a selected version, so go through the list of versions and select the one with the corresponding version-id
		foreach($versions as $key => $version) { 
			if(array_key_exists('id',$version) && $version['id']==$selected_version){
				$highlight_version = $version;
				if(array_key_exists('name',$version)){
					$highlight_version_text = $version["name"];
				}else{
					$highlight_version_text = "version "+$version['id'];
				}
				break;
			}
		}
		//no version with the specified version id has been found, so write error message into the json content
		if(empty($highlight_version)){
			http_response_code(404);
			$string = '{"title":"Error 404: Could not find version \''.$project."/".$dataset."/".$selected_version.'\'!","error":"version","missing":"'.$project."/".$dataset."/".$selected_version.'"}';
			$json = json_clean(json_decode($string, true));
			$selected_version = "";
		}
	}else{
		//there is no selected version, so sort the existing version by their date (and if multiple versions were released on the same date then by version_id) and select the latest
		$dates  = array_column($versions, 'date');
		$version_ids  = array_column($versions, 'id');
		
		array_multisort($dates, SORT_DESC, $version_ids, SORT_ASC, $versions);
		if(array_key_exists('id',$versions[0])){
			$highlight_version = $versions[0]; 
			$highlight_version_text = "the latest version";
		}
	}
				
}

$published = true;
//check if this is a published dataset
if(array_key_exists('published',$json) && ($json['published'] == "false" || $json['published'] == "hidden" )){
	$published = false;
	//show error reporting if unpublished, e.g. missing fields warning
	error_reporting(E_ALL);
	ini_set("display_errors", 1);
}
?>

<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title><?php echo $json['title']; ?></title>
	<link rel="stylesheet" type="text/css" href="<?php echo $url_base; ?>style.css"/>
	<?php //load project specific css file if it exists ?>
	<?php if (file_exists("style_".$project.".css")) { ?>
	<link rel="stylesheet" type="text/css" href="<?php echo $url_base; ?>style_<?php echo $project; ?>.css"/>	
	<?php } ?>

</head>

<body>
<div id="header" class="main-section">
	<?php 
		//include the header specified in the project specific settings files.
		echo $header;
		
		//show warning if it is an unpublished dataset, along with some additional information for the curator, like the link to the submission ticket and the link to the JSON for the DOI creation. 
		if(!$published && !array_key_exists('error',$json)){
			echo "	<span style=\"color:red; font-size:16pt; font-weight:bold;\">Unpublished Dataset</span>";
			if(array_key_exists('ticket-id',$json)){
				echo "	<br /><a href=\"".$ticket_url_base.$json['ticket-id']."\">Ticket: ".$json['ticket-id']."</a>";
			}
			
			if(empty($highlight_version) || !array_key_exists('stable-identifier',$highlight_version)){
				echo "	<br /><a href=\"".$url_base."convert.php?project=".$project."&dataset=".$dataset."&version=".$highlight_version['id']."&format=application/json+datacite\">Generate JSON for DOI creation</a>";
			}
		}
	?>
	<?php 
		//show warning to the user the this is currently the landingpage for a specific version, not for the generic dataset
		if($selected_version != "" && !array_key_exists('error',$json)){
			echo "	<div class=\"version-warning\">You are currently viewing the dataset landing page for <b>".$highlight_version_text."</b> of this dataset. <br /><br/> Please use the link <a href=\"".$url_base.$project."/".$dataset."/\">".$url_base.$project."/".$dataset."/</a> to get to the most recent version.</div>";
		}
	?>
</div>
<?php
//only show content if no errors occurred so far. Error handling is done at the bottom.
if(!array_key_exists('error',$json)){
?>
<div id="content">
	<div id="main-info" class="main-section">
		<div id="links" class="right">
		<?php 
			if(!empty($highlight_version) && array_key_exists('download-link',$highlight_version)){
				echo "		<a class=\"download-link button\" href=\"". $highlight_version['download-link'] ."\" title=\"Download ".$highlight_version_text." of the data set\">Download Data<br /><small>(".(substr($highlight_version_text,0,4)=="the "? substr($highlight_version_text,4):$highlight_version_text).")</small></a>";
			}else if(array_key_exists('download-link',$json)){
				//show generic download link, if the highlight version (selected or latest) doesn't have one
				echo "		<a class=\"download-link button\" href=\"". $json['download-link'] ."\" title=\"Download the Latest Version of the data set\">Download Data<br /><small>(latest version)</small></a>";
			}
		?>
		</div>
		<h2>Title</h2>
		<span class="dataset-info large"><?php echo $json['title'] ?></span>
		
		<h2>Stable Identifier</h2>
		<span class="dataset-info large"><span class="link-description">For the dataset:</span><br /><span class="indent"><?php echo generate_doi_link($json['stable-identifier'])?></span></span>
		<?php 
			//show stable identifier for the highlight version (selected or latest) by displaying the stable identifier (if it is a DOI, an icon will be added) or by generating a local URL
			if(!(empty($highlight_version))){
				if(array_key_exists('stable-identifier',$highlight_version)){
					echo"		<span class=\"dataset-info large\"><span class=\"link-description\">For ".$highlight_version_text." of the dataset: </span><br /><span class=\"indent\">".generate_doi_link($highlight_version['stable-identifier'])."</span></span>";
				}else if(array_key_exists('id',$highlight_version)){
					echo"		<span class=\"dataset-info large\"><span class=\"link-description\">For ".$highlight_version_text." of the dataset: </span><br /><span class=\"indent\"><a href=\"".$url_base.$project."/".$dataset."/".$highlight_version['id']."\">".$url_base.$project."/".$dataset."/".$highlight_version['id']."</a></span></span>";
				}
			}
		?>
		<h2>Citation</h2>
		<?php //links in the citation will be automatically recognized and linked ?>
		<span class="dataset-info large"><?php echo addLinks($json['citation']); ?></span>
			
		<h2>Data</h2>
		<table id="version-table">
			<tr>
				<th>Version</th>
				<th>Date</th>
				<th>Comment</th>
				<th>Format</th>
				<th>Download</th>
			</tr>
		<?php 
			if(array_key_exists('versions',$json)){ 
			
				$versions = $json['versions'];
				$dates  = array_column($versions, 'date');
				$version_ids  = array_column($versions, 'id');
				//sort the version by their date (and if multiple versions were released on the same date then by version_id)
				array_multisort($dates, SORT_DESC, $version_ids, SORT_ASC, $versions);

				foreach($versions as $key => $version) { 
					$highlight = false;
					//mark the current row as selected if the id is the same as the id of the selected version
					if(array_key_exists('id',$version) && $version['id']==$selected_version){
						$output = "<tr class=\"selected-version\"><td><b>";
						$highlight = true;
					}else{
						$output = "<tr><td>";
					}
					//print the name if the version with a perma link to this version
					if(array_key_exists('id',$version)){
						$output = $output."<a href=\"".$version['id']."\" title=\"Perma-Link to this version of the dataset\">";
						if(array_key_exists('name',$version)){
							$output = $output.$version['name'];
						}else{
							//if no name exists, use id as an alternative
							$output = $output.$version['id'];
						}
						$output = $output."</a>";
					}else if(array_key_exists('name',$version)){
						$output = $output.$version['name'];
					}
					if($highlight){
						//close the bold marking of the version name
						$output = $output."</b>";
					}
					$output = $output."</td><td>";
					//print version date
					if(array_key_exists('date',$version)){
						$output = $output.$version['date'];
					}
					$output = $output."</td><td>";
					//print version comment
					if(array_key_exists('comment',$version)){
						$output = $output.$version['comment'];
					}
					$output = $output."</td><td>";
					//print version format
					if(array_key_exists('format',$version)){
						$output = $output.$version['format'];
					}else if(array_key_exists('format',$json)){
						//if there is no version specific format, but a dataset specific format, use this one as the version format
						$output = $output.$json['format'];
					}
					$output = $output."</td><td>";
					//print version download link
					if(array_key_exists('download-link',$version)){
						$output = $output."<a href=\"".$version['download-link']."\"><img src=\"".$download_icon."\" class=\"icon download-icon\" alt=\"download icon\" title=\"download this version of the dataset\"/></a>";
					}
					
					$output = $output."</td></tr>";
					
					echo $output;

				}
			}else{
				//there is no versions element in the json, so one entry is generated for the single version represented by the data
				if($selected_version=="1"){
					//if the selected version is one, select this version (it is the only one after all)
					$output = "<tr class=\"selected-version\"><td><b><a href=\"1\" title=\"Perma-Link to this version of the dataset\">Version 1</a></b></td><td>";
					$highlight = true;
				}else{
					//print permalink as if this is version 1
					$output = "<tr><td><a href=\"1\" title=\"Perma-Link to this version of the dataset\">Version 1</a></td><td>";
				}
				//take version date from field "last-updated"
				if(array_key_exists('last-updated',$json)){ 
					$output = $output.$json['last-updated']; 
				}
				//hardcoded version comment
				$output = $output."</td><td><i>(original version)</i></td><td>";
				if(array_key_exists('format',$json)){ 
					$output = $output.$json['format']; 
				}			
				$output = $output."</td><td>";
				//take version download link from dataset download link
				if(array_key_exists('download-link',$json)){
					$output = $output."<a href=\"".$json['download-link']."\"><img src=\"".$download_icon."\" class=\"icon download-icon\" alt=\"download icon\" title=\"download this version of the dataset\"/></a>";
				}
				$output = $output."</td></tr>";
				
				echo $output;
			} ?>
			
		</table>
		<?php //adjust plural for the headline of this section, depending on the number of related datasets. The section headline will also be displayed if there are no related datasets ?>
		<?php if(array_key_exists('licenses',$json) && sizeof($json['licenses']) > 1 ){ ?>
		<h2>Licenses</h2>
		<?php }else{ ?>
		<h2>License</h2>
		<?php } ?>
		<span class="dataset-info large">
			<?php if(array_key_exists('licenses',$json)){
				//the joiner will add linebreaks between individual licenses, but not after the last or before the first one
				$joiner = "";
				foreach($json['licenses'] as $license) {
					echo $joiner;
					if(array_key_exists('link',$license)){
						//get the icon URL if the license is a Creative Commons License
						$cc_icon = get_creative_commons_icon($license['link']);
						//if an Icon URL is returned, then display it
						if($cc_icon != ""){
							echo "<img src=\"".$cc_icons_directory.$cc_icon."\" alt=\"License Logo\" class=\"cc-icon\" />";
						}
						//show license with link
						if(array_key_exists('name',$license)){
							echo "<a href=\"".$license['link']."\">".$license['name']."</a>";
						}else{
							echo "<a href=\"".$license['link']."\">".$license['link']."</a>";
						}
						//add license details, if it exists
						if(array_key_exists('details',$license)){
							echo " <span class=\"license-details\">(".$license['details'].")</span>";
						}
					}else if(array_key_exists('name',$license)){
						//get the icon URL if the license is a Creative Commons License
						$cc_icon = get_creative_commons_icon($license['name']);
						//if an Icon URL is returned, then display it
						if($cc_icon != ""){
							echo "<img src=\"".$cc_icons_directory.$cc_icon."\" alt=\"License Logo\" class=\"cc-icon\" />";
						}
						echo $license['name'];
						//add license details, if it exists
						if(array_key_exists('details',$license)){
							echo " <span class=\"license-details\">(".$license['details'].")</span>";
						}
					} 
					$joiner = "<br />";
				}
			}else if(array_key_exists('license-link',$json)){
				//get the icon URL if the license is a Creative Commons License
				$cc_icon = get_creative_commons_icon($json['license-link']);
				//if an Icon URL is returned, then display it
				if($cc_icon != ""){
					echo "<img src=\"".$cc_icons_directory.$cc_icon."\" alt=\"License Logo\" class=\"cc-icon\" />";
				}
				//show license with link
				if(array_key_exists('license',$json)){
					echo "<a href=\"".$json['license-link']."\">".$json['license']."</a>";
				}else{
					echo "<a href=\"".$json['license-link']."\">".$json['license-link']."</a>";
				}
			}else if(array_key_exists('license',$json)){
				//get the icon URL if the license is a Creative Commons License
				$cc_icon = get_creative_commons_icon($json['license']);
				//if an Icon URL is returned, then display it
				if($cc_icon != ""){
					echo "<img src=\"".$cc_icons_directory.$cc_icon."\" alt=\"License Logo\" class=\"cc-icon\" />";
				}
				echo $json['license'];
			} 
			?></span>
		
		<h2>Details</h2>
		<?php //links in the description will be automatically recognized and linked ?>
		<span class="dataset-info large"><?php echo addLinks($json['details']); ?></span>
	</div>
	
	<div id="related-datasets" class="main-section">
		<?php //adjust plural for the headline of this section, depending on the number of related datasets. The section headline will also be displayed if there are no related datasets ?>
		<?php if(array_key_exists('related-datasets',$json) && sizeof($json['related-datasets']) == 1 ){ ?>
		<h2>Related Dataset</h2>
		<?php }else{ ?>
		<h2>Related Datasets</h2>
		<?php } ?>
		<?php if(array_key_exists('related-datasets',$json)){ ?>
			<ul>
			<?php 
			foreach($json['related-datasets'] as $related) { 
				//without a name, the related dataset is not displayed
				if(array_key_exists('name',$related)){
					//the details segment gets added at the end (outside of the link, if it exists)
					$details = "";
					if(array_key_exists('details',$related)){
						$details = " ".$related['details'];
					}
					//check if the related dataset will be linked or not
					if(array_key_exists('link',$related)){
						echo "<li><a href=\"".$related['link']."\">".$related['name']."</a>".addLinks($details)."</li>";
					}else{
						//if there is no link for the related publication, existing links within the text will be automatically recognized and converted to proper links.
						echo "<li>".addLinks($related['name'].$details)."</li>";
					} 
				}
			}
			?>
			</ul>
		<?php }else{ ?>
			<span class="dataset-info large">none</span>
		<?php } ?>
	</div>
	<div id="related-publications" class="main-section">
		<?php //adjust plural for the headline of this section, depending on the number of related publications. The section headline will also be displayed if there are no related publications ?>
		<?php if(array_key_exists('related-publications',$json) && sizeof($json['related-publications']) == 1 ){ ?>
		<h2>Related Publication</h2>
		<?php }else{ ?>
		<h2>Related Publications</h2>
		<?php } ?>
		<?php if(array_key_exists('related-publications',$json)){ ?>
			<ul>
			<?php 
			foreach($json['related-publications'] as $related) { 
				//without a name, the related publication is not displayed
				if(array_key_exists('name',$related)){
					//the details segment gets added at the end (outside of the link, if it exists)
					$details = "";
					if(array_key_exists('details',$related)){
						$details = " ".$related['details'];
					}
					//check if the related publication will be linked or not
					if(array_key_exists('link',$related)){
						echo "<li><a href=\"".$related['link']."\">".$related['name']."</a>".addLinks($details)."</li>";
					}else{
						//if there is no link for the related publication, existing links within the text will be automatically recognized and converted to proper links.
						echo "<li>".addLinks($related['name'].$details)."</li>";
					} 
				}
			}
			?>
			</ul>
		<?php }else{ ?>
			<span class="dataset-info large">none</span>
		<?php } ?>
	</div>
	<div id="additional-info" class="main-section">
		<h2>Additional Info</h2>
		<div id="details-left" >
			<table>
			<?php //the key "creators" will contain the array with individual creator names (and possibly ORCIDs).?>
			<?php if(array_key_exists('creators',$json)){ ?>
				<tr>
					<?php //adjust plural for the headline of this section, depending on the number of creators.?>
					<?php if(sizeof($json['creators']) == 1){ ?>
					<td class="table-heading">Creator</td>
					<?php }else{ ?>
					<td class="table-heading">Creators</td>
					<?php } ?>
					<td><?php 
						//the joiner gets replace by a comma and a space later on, to only appear between multiple entries
						$joiner = "";
						foreach($json['creators'] as $creator) {
							//without a name, the creator is not displayed
							if(array_key_exists('name',$creator)){
								echo $joiner.$creator['name'];
								//add ORCID icon with link if ORCID-ID is present
								if(array_key_exists('orcid',$creator)){
									//check if orcid field contains only the ID or the URI (with "https://orcid.org/" at the beginning)
									if(substr($creator['orcid'],0,18)=="https://orcid.org/"){
										$orcid_link = $creator['orcid'];
									}else{
										$orcid_link = "https://orcid.org/".$creator['orcid'];
									}
									echo " <a href=\"".$orcid_link."\"><img src=\"".$materials_directory."orcid.png\" alt=\"\" title=\"ORCID iD of ".$creator['name']."\" /></a>";
								}
								$joiner = ", ";
							}
						}
					?></td>
				</tr>
			<?php }else if(array_key_exists('creator',$json)){ ?>
				<?php //alternative: if the creators are not separated but marked as one continues string (using the key "creator" instead of "creators" for the array format), they will be displayed here.?>
				<tr>
					<td class="table-heading">Creator(s)</td>
					<td><?php echo $json['creator']; ?></td>
				</tr>
			<?php } ?>
			<?php //the key "creators" will contain the array with individual creator names (and possibly ORCIDs).?>
			<?php if(array_key_exists('contributors',$json)){ ?>
				<tr>
					<?php //adjust plural for the headline of this section, depending on the number of contributers.?>
					<?php if(sizeof($json['contributors']) == 1){ ?>
					<td class="table-heading">Contributor</td>
					<?php }else{ ?>
					<td class="table-heading">Contributors</td>
					<?php } ?>
					<td><?php 
						//the joiner gets replace by a comma and a space later on, to only appear between multiple entries
						$joiner = "";
						foreach($json['contributors'] as $contributor) {
							//without a name, the contributer is not displayed
							if(array_key_exists('name',$contributor)){
								echo $joiner.$contributor['name'];
								//add ORCID icon with link if ORCID-ID is present
								if(array_key_exists('orcid',$contributor)){
									//check if orcid field contains only the ID or the URI (with "https://orcid.org/" at the beginning)
									if(substr($contributor['orcid'],0,18)=="https://orcid.org/"){
										$orcid_link = $contributor['orcid'];
									}else{
										$orcid_link = "https://orcid.org/".$contributor['orcid'];
									}
									echo " <a href=\"".$orcid_link."\"><img src=\"".$materials_directory."orcid.png\" alt=\"\" title=\"ORCID iD of ".$contributor['name']."\" /></a>";
								}
								$joiner = ", ";
							}
						}
					?></td>
				</tr>
			<?php }else if(array_key_exists('contributor',$json)){ ?>
				<?php //alternative: if the contributers are not separated but marked as one continues string (using the key "contributer" instead of "contributers" for the array format), they will be displayed here.?>
				<tr>
					<td class="table-heading">Contributor(s)</td>
					<td><?php echo $json['contributor']; ?></td>
				</tr>
			<?php } ?>
			<?php if(array_key_exists('technical-contact',$json)){ ?>
				<tr>
					<td class="table-heading">Technical Contact</td>
					<td><?php 
						//if contact e-mail exists, link email
						if(array_key_exists('technical-contact-email',$json)){
							echo "<a href=\"mailto:".$json['technical-contact-email']."\">".$json['technical-contact']."</a>";
						//if contact link exists, add link
						}else if(array_key_exists('technical-contact-link',$json)){
							echo "<a href=\"".$json['technical-contact-link']."\">".$json['technical-contact']."</a>";
						//if no contact way is specified, show name
						}else{
							echo $json['technical-contact'];
						} ?></td>
				</tr>
			<?php } ?>
			</table>
		</div>
		<div id="details-right" >
			<table>
			<?php //various other details are displayed as they are, if the corresponding field exists in the json file?>
			<?php if(array_key_exists('last-updated',$json)){ ?>
				<tr>
					<td class="table-heading">Last updated</td>
					<td><?php echo $json['last-updated']; ?></td>
				</tr>
			<?php } ?>
			<?php if(array_key_exists('created',$json)){ ?>
				<tr>
					<td class="table-heading">Created</td>
					<td><?php echo $json['created']; ?></td>
				</tr>
			<?php } ?>
			<?php if(array_key_exists('record-basis',$json)){ ?>
				<tr>
					<td class="table-heading">Record Basis</td>
					<td><?php echo $json['record-basis']; ?></td>
				</tr>
			<?php } ?>
			<?php if(array_key_exists('keywords',$json)){ ?>
				<tr>
					<td class="table-heading">Keyword(s)</td>
					<td><?php 
						//display the individual keyworkds as a contanitated string using a joiner 
						$joiner = "";
						foreach($json['keywords'] as $keyword) {
							echo $joiner.$keyword;
							$joiner = ", ";
						}
					?></td>
				</tr>
			<?php } ?>
			</table>
		</div>
	</div>
</div>
<?php }else{ 
	//Error handling, depending on which part of the query was missing: the project, the data set or the version. Provide fallback to the next higher possibility
	if($json['error'] == "project"){
		echo "<h2>404</h2> The project you selected '".$json['missing']."' does not exist. Please go back to the project overview page: <a href=\"".$url_base."\">".$url_base."</a>";
	}else if($json['error'] == "dataset"){
		echo "<h2>404</h2> The dataset you selected '".$json['missing']."' does not exist. Please go back to the dataset overview page: <a href=\"".$url_base.$project."/\">".$url_base.$project."/</a>";
	}else if($json['error'] == "version"){
		echo "<h2>404</h2> The version you selected '".$json['missing']."' does not exist. Please go back to the current version of the dataset: <a href=\"".$url_base.$project."/".$dataset."\">".$url_base.$project."/".$dataset."</a>";
	}else{
		echo "<h2>404</h2> Unknown error! Please go back to the project overview page: <a href=\"".$url_base."\">".$url_base."</a>";
	}
}
?>

<div id="footer">
<?php
		//include the footer specified in the project specific settings files.
		echo $footer;
?>
</div>
</body>
</html>