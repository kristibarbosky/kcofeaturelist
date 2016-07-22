<html>
<head>
	<title>KCO Feature List</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<style type="text/css" media="screen">

/*====================================================
	- HTML Table Filter stylesheet
=====================================================*/
@import "filtergrid.css";

/*====================================================
	- General html elements
=====================================================*/
body{ 
	margin:15px; padding:15px; border:1px solid #666;
	font-family:Arial, Helvetica, sans-serif; font-size:88%; 
}
h2{ margin-top: 50px; }
caption{ margin:10px 0 0 5px; padding:10px; text-align:left; }
pre{ font-size:13px; margin:5px; padding:5px; background-color:#f4f4f4; border:1px solid #ccc;  }
.mytable{
	width:100%; font-size:12px;
	border:1px solid #ccc;
}
th{ background-color:#0074C8; color:#FFF; padding:2px; border:1px solid #ccc; text-align: left;}
td{ padding:2px; border-bottom:1px solid #ccc; border-right:1px solid #ccc; }
</style>
<script language="javascript" type="text/javascript" src="tablefilter.js"></script>

</head>
<body>
<img src="https://cdn.klarna.com/1.0/shared/image/generic/logo/en_us/basic/blue-black.png?width=200"/>
<br>
<br>
<b>Welcome to the KCO Feature List Home Page</b>
<br>
**Internal Only**
<br>

<?php

require 'kcofeaturelist_sharedfunctions.php';


$selected_platform = "";
$selected_platform_id = 1;
$selected_platform_version_id = 0;
$selected_module_id = 0;
$selected_module_version_id = 0;
$selected_value = "";
$selected_si_id = 0;
$platform_versions_exist = false;
$module_name = "";

// get values from page if previously selected
if ($_POST) {
	$selected_platform_id = $_POST["platform"];
	if (empty($selected_platform_id)) {
		$selected_platform_id = 0;
	}
	if (array_key_exists("platform_version_id", $_POST)) {
		$selected_platform_version_id = $_POST["platform_version_id"];
	}
	if (empty($selected_platform_version_id)){
		$selected_platform_version_id = 0;
	}
	if (array_key_exists("module_version_id", $_POST)) {
		$selected_module_version_id = $_POST["module_version_id"];
	}
}

echo "<br><form action=\"kcofeaturelist.php\" method=\"post\">";

$dbconnection = getDBconnection();

$selected_platform = doPlatforms($dbconnection, $selected_platform_id);

$querystring = "SELECT version_number, date_available, id FROM platform_version WHERE platform_id = " . $selected_platform_id . " ORDER BY version_number ASC";

$platform_version_query = mysqli_query($dbconnection, $querystring);
	
// show platform versions
if (doPlatformVersionsExist($dbconnection, $platform_version_query)) {
	$selected_platform_version_id = doPlatformVersions($dbconnection, $platform_version_query, $selected_platform_version_id);

	// show module versions
	$selected_module_version_id = printModuleVersions($dbconnection, $selected_platform_version_id, $selected_module_version_id);

	echo "</form>";

	// show klarna resources
	printf("Klarna Resources for <b>%s</b>:<br>", $selected_platform);
	$querystring = "SELECT person.* FROM platform_klarna_persons, person WHERE person.id=platform_klarna_persons.person_id AND platform_klarna_persons.platform_id=\"" . $selected_platform_id . "\"" ;
	$queryresult = mysqli_query($dbconnection, $querystring);	
	echo "\n\n<table id=\"klarna_resources_table\" class=\"mytable\">";
	echo "\n<tr><th>First</th><th>Last</th><th>Title</th></tr>";
	while($result = mysqli_fetch_array($queryresult)) {	
		printf("\n<tr><td>%s</td><td>%s</td><td>%s</td></tr>", $result['first_name'], $result['last_name'], $result['title']);
	}	
	echo "</table></p>\n\n";


	// show system integrator for module version
	if ($selected_module_id !== 0) {
		$querystring = "SELECT system_integrator.* FROM system_integrator, module WHERE system_integrator.id=module.si_id AND module.id=\"" . $selected_module_id . "\"" ;
		$queryresult = mysqli_query($dbconnection, $querystring);	
		printf("<p>System Integrator for Module <b>%s</b> <br>", $module_name);
		while($result = mysqli_fetch_array($queryresult)) {	
			$selected_si_id = $result['id'];
			printf("<b>%s</b></br>\n", $result['name']);
			printf("%s \n", $result['url']);
		}

		// show system integrator contacts
		$querystring = "SELECT person.* FROM system_integrator_persons, person WHERE person.id=system_integrator_persons.person_id AND system_integrator_persons.si_id=\"" . $selected_si_id . "\"" ;
		$queryresult = mysqli_query($dbconnection, $querystring);	
		echo "\n\n<table id=\"si_table\" class=\"mytable\">";
		echo "\n<tr><th>First</th><th>Last</th><th>Title</th></tr>";
		while($result = mysqli_fetch_array($queryresult)) {	
			printf("\n<tr><td>%s</td><td>%s</td><td>%s</td></tr>", $result['first_name'], $result['last_name'], $result['title']);
		}	
		echo "</table></p>\n\n";
	}


	// show features for module version, by country
	$querystring = "SELECT DISTINCT feature.name, feature.id, feature_type.type, feature_type.subtype, feature.order_state, feature.comments FROM feature_type, feature, module_version_feature_countries WHERE feature_type.id=feature.type_id AND module_version_feature_countries.feature_id=feature.id AND module_version_feature_countries.module_version_id=\"" . $selected_module_version_id . "\" ORDER BY feature_type.type, feature_type.subtype, feature.order_state, feature.name ASC" ;
	$queryresult = mysqli_query($dbconnection, $querystring);	
	echo "\n\n<br>Features by country<table id=\"feature_table\" class=\"mytable\">";
	echo "<tr><th>Feature ID</th><th>Feature Type</th><th>Order State</th><th>Name</th><th>Countries</th><th>Comments</th></tr>\n";
	while($result = mysqli_fetch_array($queryresult)) {	
		$current_feature_id = $result['id'];
		$querystring = "SELECT * FROM module_version_feature_countries WHERE module_version_feature_countries.feature_id=$current_feature_id AND module_version_feature_countries.module_version_id=\"" . $selected_module_version_id . "\" ORDER BY module_version_feature_countries.country ASC";
		$queryresult_featurecountries = mysqli_query($dbconnection, $querystring);
		$countrieslist = '';
		while ($result_country = mysqli_fetch_array($queryresult_featurecountries)) {
			if($countrieslist !== '') {
				$countrieslist = $countrieslist . " , ";
			}
			$country = $result_country['country'];
			$querystring = "SELECT * FROM api WHERE feature_id=" . $current_feature_id . " AND country_shortcode=\"" . $country . "\"";
			//echo $querystring;
			$queryresult_api = mysqli_query($dbconnection, $querystring);
			$apiurl = "";
			if ($result_api = mysqli_fetch_array($queryresult_api)) {
				$apiurl = $result_api['url'];
				$countrieslist = $countrieslist . "<a href=\"". $apiurl . "\">" . $country . "</a>";
			}
			else {
				$countrieslist = $countrieslist . $country;
			}
			
		}
		if($countrieslist == '') {
			$countrieslist = "None";
		}
		printf("<tr><td>%s</td><td>%s-%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>\n", $current_feature_id, $result['type'], $result['subtype'], $result['order_state'], $result['name'], $countrieslist, $result['comments']);
	}	
	echo "</table></p>\n\n"; 
}
else {
		echo "</select><br><br></form>";
}
?>

<script language="javascript" type="text/javascript">
//<![CDATA[
	setFilterGrid( "feature_table" );
//]]>
</script>

<p>
	<!--
Show all features/export report
<br>
Query by module => show supported features
<br>
Query by feature => Show all modules that support it
</p>
	-->

<?php

mysqli_close($dbconnection);

?>

</body>
</html>