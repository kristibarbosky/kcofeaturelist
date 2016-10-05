<html>
<head>
	<title>Klarna Feature List</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<style type="text/css" media="screen">

/*====================================================
	- HTML Table Filter stylesheet
=====================================================*/
@import "filtergrid.css";

@import "klarnafeaturelist.css";

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
<b>Welcome to the Klarna Feature List Home Page</b>
<br>
**Internal Only**
<?php

require 'klarnafeaturelist_sharedfunctions.php';

$selected_platform = null;
$selected_platform_id = 0;
$selected_platform_version_id = 0;
$selectedKcoModule = null;
$selected_module_id = 0;
$selected_module_version_id = 0;
$selected_value = "";
$selected_si_id = 0;
$platform_versions_exist = false;
$module_name = "";
$which_field_changed = "";

// get values from page if previously selected
if ($_POST) {
	$selected_platform_id = $_POST["platform_id"];
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
	if (array_key_exists("which_field_changed", $_POST)) {
		$which_field_changed = $_POST["which_field_changed"];
	}

}

if ($which_field_changed !== "module_version_id") {
	$selected_module_version_id = 0;
}

//echo "<br><form action=\"klarnafeaturelist.php\" method=\"post\"><table id=\"platform_table\" class=\"mytable\">\n<tr><td style=\"width:30%\"><b>Platform</b></td><td style=\"width:10%\"><b>Type</b></td><td><b>Comments</b></td></tr>";

$dbconnection = getDBconnection();

$selected_platform = doPlatforms($dbconnection, $selected_platform_id);

$querystring = "SELECT version_number, date_available, id, type_of_integration FROM platform_version WHERE platform_id=" . $selected_platform_id . " ORDER BY version_number DESC";

$platform_version_query = dbQuery($dbconnection, $querystring);
	
// show platform versions
if (doPlatformVersionsExist($platform_version_query)) {

	$selected_platform_version_id = doPlatformVersions($dbconnection, $platform_version_query, $selected_platform_version_id);

	echo "</table>";

	// show module versions
	$selectedKcoModule = printModuleVersions($dbconnection, $selected_platform_version_id, $selected_module_version_id);

	echo "</form>";

	// show klarna resources
	printKlarnaResources($dbconnection, $selected_platform);

	if ($selectedKcoModule != null) {
		// show system integrator for module version
		printSystemIntegrator($dbconnection, $selectedKcoModule);
		
		// show features for module version, by country
		printModuleFeatures($dbconnection, $selectedKcoModule->getSelectedModuleVersionId(), false);
	}
}
else {
	echo "</table></select><br><br></form>";
}

echo "\nFor questions about Global Platforms and Plugins, contact <a href=\"mailto:melissa.thobe@klarna.com?Subject=KCO%20Feature%20List%Question\" target=\"_top\">Melissa Thobe</a>.  <br>For questions or issues with this application, contact <a href=\"mailto:kristi.barbosky@klarna.com?Subject=KCO%20Feature%20List%Question\" target=\"_top\">Kristi Barbosky</a>.";
?>

<script language="javascript" type="text/javascript">
//<![CDATA[
	setFilterGrid( "feature_table" );
//]]>
</script>

<p>

<?php
dbClose($dbconnection);
?>

</body>
</html>