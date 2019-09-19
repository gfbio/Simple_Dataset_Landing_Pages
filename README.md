# Simple Dataset Landing Pages
A small framework to create simple landing pages for datasets with all relevant information in one place and that are easily curated while requiring minimal technical overhead. The data is stored in JSON files and the pages are generated using a PHP template. 

## Screenshots
![Screenshot of an example dataset landing page](example/1985-002-flux1/Example_Dataset_Landing_Page_small.png?raw=true "An example dataset landing page")

![Screenshot of a version specific landing page](example/1985-002-flux1/Screenshot_Version_Specific_Landing_Page_small.png?raw=true "Section of a version specific landing page")

![Screenshot of an unpublished landing page](example/1985-002-flux1/Screenshot_Unpublished_Landing_Page_small.png?raw=true "Section of an unpublished landing page without a DOI for the latest version. Links to the ticket system and to generate the DataCite Metadata for DOI generation are shown.")

## Structure
The URL for this script consists out of up to 4 segments: **BaseURL** + **Project** + **DatasetID** + **VersionID**

BaseURL
: this is the root of the landing page section. When only the BaseURL is called, it lists the available projects.
: e.g. https://example.org/datasets/

Project
: a segment to which the individual datasets are associated. If only the URL of the project is called, it lists all available datasets with their names, sorted by their dataset ID
: e.g. "projectA" in https://example.org/datasets/projectA/

DatasetID
: an identifier for the dataset. This could be a name or numeric value. This is the actual dataset landing page. If lists all available information about the dataset and all its versions.
: e.g. "0007" in https://example.org/datasets/projectA/0007/

VersionID
: an identifier for the version. This could be a name or numeric value. This is a URL for the specific version of a dataset and contains the relevant information
: e.g. "2" in https://example.org/datasets/projectA/0007/2

On the server the files to generate the landing pages are located in the directory specified by the base URL. The projects are directories on the server which contain the directories for the datasets. Each directory of a dataset has to contain a file called data.json which contains all the relevant information about a dataset and its versions. Other files can be located in this directory as well, e.g. the download files for the dataset and its versions. The versions themselves must not be created as directories within the dataset directory, as this will most likely interfere with the URL rewrites for the version specific pages. 


## Configuration
There is a config.php file which contains several configuration parameters. 

$url_base
: the base URL described above

$projects
: a php array containing the project names that should be listed in the page of the base URL. A project is only listed there if it is contained in this array and if the corresponding directory exists on the server. 

$materials_directory
: the path to the directory which contains materials, such as images or style sheets
: the default configuration is ```$url_base."materials/";```, but it can be overwritten in the project specific settings files

$download_icon
: the download icon to be displayed in the version table
: the default configuration is ```$materials_directory."download.png";```, but it can be overwritten in the project specific settings files

$cc_icons_directory
: the directory where the Creative Commons logos are located
: the default configuration is ```$materials_directory."cc_icons/large/";```, but it can be overwritten in the project specific settings files

$header
: a header to be included in beginning of the page in the header section (not to be confused with the head element in the HTML code). This can be HTML code.

$footer
: a footer to be included at the bottom of the page in the footer section. This can be HTML code.

$publisher
: the name of the publisher of a dataset that will be used when generating the DataCite JSON for the DOI Registration

### Project Specific Configuration
All of the settings mentioned above can be overwritten in the project specific settings files. For each project, a settings file named as config_{ProjectName}.php (e.g. "config_projectA.php" for the example outlined in the beginning) is attempted to be loaded after the initial config.php was loaded. Also a CSS file  as config_{ProjectName}.css will be included as well. This allows the dataset landing pages of the different projects to be customized depending on the project.



## data.json Fields

published
: a boolean flag to indicate if the dataset should be publicly listed. 
: 
: Default: **true**
: **warning**: this flag is only a very weak protection. It is not suited to secure sensitive or embargoed data. It is only meant to prepare a page without it being listed in the public directory of datasets. Anybody with the URL can access it without restrictions and depending on the structure of your Dataset ID unpublished projects might be easily guessed or brute-forsed (e.g. if continuous numbers or dates are used)

ticket-id
: the ID of the related ticket to the system. If provided a link will generated by adding the ID at the end of the ```$ticket_url_base``` provided in the settings file, if the dataset is not published. This allows curators to quickly jump back to the ticket system to check back for relevant information.

title
: the title of the dataset. 
: **mandatory**

stable-identifier
: a stable identifier for the dataset in general. 
: **mandatory**
: Ideally this should be a resolvable URL, as it will be linked. If this is a DOI, a little DOI icon will be displayed next to it.
: stable identifiers for the individual versions can either be added in the version element of the JSON or will be generated automatically.

citation
: the preferred citation of the dataset. Any URLs that are contained will be converted and linked accordingly. 

license
: the name of the license under which the dataset is licensed. 
: **deprecated**, please use the JSON element ```licenses -> name``` instead.

license-link
: the link for the license under which the dataset is licensed. 
: **deprecated**, please use the JSON element ```licenses -> link``` instead.

licenses
: a JSON array containing one or more license elements with the license information. 
: **mandatory**

licenses -> link
: the link for the license under which the dataset is licensed. 

licenses -> name
: the name of the license under which the dataset is licensed. 
: **mandatory** (unless the deprecated ```license``` element is provided)

licenses -> details
: additional details about the license, e.g. to which part of the dataset it applies if there are multiple licenses. The details will not be part of the link, if a link is provided. Any URLs within the details will be recognized and linked accordingly. 

keywords
: a JSON array of keywords relevant to the dataset

details
: a text with additional details about the dataset. This could be an abstract for the dataset.
: **mandatory**

related-datasets
: a JSON array containing one or more elements for related datasets with the their corresponding information. 

related-datasets -> link
: the link to the related dataset

related-datasets -> name
: the name to the related dataset

related-datasets -> details
: additional details about related dataset. The details will not be part of the link, if a link is provided. Any URLs within the details will be recognized and linked accordingly. 

related-publications
: a JSON array containing one or more elements for related publications of this dataset with the their corresponding information. 

related-publications -> link
: the link to the related publication

related-publications -> name
: the name to the related publication

related-publications -> details
: additional details about related publications. The details will not be part of the link, if a link is provided. Any URLs within the details will be recognized and linked accordingly. 

creators
: a JSON array containing one or more elements the creators of this dataset with the their corresponding information. 

creators -> orcid
: the ORCID id of the creator. Either the full URL or just the numbers

creators -> name
: the name of the creator
: **mandatory**

creator
: an element to represent one or more creators as a continuous text.
: **deprecated**, please use the JSON element ```creators -> name``` instead.

contributors
: a JSON array containing one or more elements the contributors to this dataset with the their corresponding information. 

contributors -> orcid
: the ORCID id of the contributor. Either the full URL or just the numbers

contributors -> name
: the name of the contributor
: **mandatory**

contributor
: an element to represent one or more contributors as a continuous text.
: **deprecated**, please use the JSON element ```contributors -> name``` instead.

format
: the format of the dataset

technical-contact
: the name of the technical contact. The technical contact will only be displayed, if this element is provided, regardless if ```technical-contact-email``` and ```technical-contact-link``` are filled

technical-contact-email
: the email address of the technical contact. It will be linked using a mailto:link.

technical-contact-link
: a URL to a contact page of the technical contact. It will be linked if no ```technical-contact-email``` is provided.

created
: the date when the dataset was created. This should preferably be in the [ISO 8901](https://xkcd.com/1179/) format.

last-updated
: the date when the dataset was last updated. This should preferably be in the [ISO 8901](https://xkcd.com/1179/) format.

record-basis
: the kind of data that is represented by the dataset

download-link
: the download link to the data of this dataset

versions
: a JSON array containing one or more elements for the versions of this dataset with the their corresponding information. 

versions -> id
: the id of the version. This will be part of the link to the version specific subpage. In the version table, versions with the same date are sorted by their ID in ascending alphabetical order.

versions -> download-link
: the download link to the data of this version of the dataset

versions -> name
: the name of this version. If no name is provided, the version id will be used instead.

versions -> date
: the date of the version. This should absolutely be in the [ISO 8901](https://xkcd.com/1179/) format, as the version table is sorted by the dates in descending order (newest first).  

versions -> comment
: a comment about the version, e.g. what has change compared to the previous version.

versions -> format
: the format of the version. This will overwrite the general format of the dataset allowing for individual releases being done in different formats. If no format is provided here, the format of the dataset is used instead.

versions -> stable-identifier
: a stable identifier for the dataset in general. 
: Ideally this should be a resolvable URL, as it will be linked on the version specific landing page (next to the stable identifier of the dataset). If this is a DOI, a little DOI icon will be displayed next to it.
: If this is not set the URL of the version specific landing page will be set as the stable identifier of the version.


## Configuration and Setup

1. Clone the content of this repository to publicly accessible directory on the server
1. Rename config_TEMPLATE_.php to config.php and adjust the values accordingly
1. Create a directory for the project *(for testing you can used the provided "example" directory)*
1. Create a directory for a dataset *(for testing you can used the provided "1985-002-flux1" directory within the "example" directory)*
1. Upload a data.json file *(for testing you can used the provided data.json file within the "1985-002-flux1" directory)*
1. Create project specific settings files and style sheets if necessary
1. Configure the URL rewrite of your server. Example for Apache and nginx are described below, for other servers you will have to adjust it yourself.
1. Enter the baseURL in your browser and start exploring the landing pages  

### URL Rewrites
The landing pages require URL rewrites to create the desired URL structure and to hide the technology specific paths, i.e. the php files.

#### Apache
In Apache the rewrites can easily be configured using a .htaccess file in the root directory of the application. This requires that the mod_rewrite extension is enabled. 

The content of ```.htaccess```:
```
<IfModule mod_rewrite.c>
RewriteEngine on
RewriteCond %{REQUEST_FILENAME} -f
RewriteRule (.*) - [L]

RewriteRule ^([^/]*)/$ index.php?project=$1 [L]
RewriteRule ^([^/]*)/([^/]*)/(index\.html)?$ page.php?project=$1&dataset=$2 [L]
RewriteRule ^([^/]*)/([^/]*)/([^/]*)?$ page.php?project=$1&dataset=$2&version=$3 [L]
</IfModule>
```

#### nginx
The rewrites in nginx have to be done in the corresponding nginx settings files and should look like this (the code below might have to be adjusted, depending on your local setup):
```
location / {
	root      /var/www/;
	index     index.php index.html index.htm;
	try_files $uri $uri/ @rewrite;
	autoindex off;
	
	rewrite ^/data/([^/]*)/$ /data/index.php?project=$1 last;
	rewrite ^/data/([^/]*)/([^/]*)/(index\.html)?$ /data/page.php?project=$1&dataset=$2 last;	
}
	
location @rewrite {
	rewrite ^/data/([^/]*)/([^/]*)/([^/]*)?$ /data/page.php?project=$1&dataset=$2&version=$3 last;
}
```