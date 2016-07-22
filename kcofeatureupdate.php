<html>
<head>
	<title>KCO Feature Update</title>
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
<b>KCO Feature List Update Page</b>
<br>
**Internal Only**
<br>

<?php

require 'kcofeaturelist_sharedfunctions.php';


$selected_platform_version_id = 0;
$selected_platform = "";
$selected_platform_id = 0;
$selected_module_version_id = 0;
$selected_module_id = 0;
$selected_value = "";
$selected_si_id = 0;
$module_name = "";
$new_platform_version = "";
$new_platform_version_release_date = "";

// get values from page if previously selected
if ($_POST) {
	$selected_platform_id = $_POST["platform"];
	if (empty($selected_platform_id)) {
		$selected_platform_id = 0;
	}
	if (array_key_exists("platform_version_id", $_POST)) {
		$selected_platform_version_id = $_POST["platform_version_id"];
	}
	if (array_key_exists("module_version_id", $_POST)) {
		$selected_module_version_id = $_POST["module_version_id"];
	}	
	if (array_key_exists("new_platform_version", $_POST)) {
		$new_platform_version = $_POST["new_platform_version"];
	}		
	if (array_key_exists("new_platform_version_release_date", $_POST)) {
		$new_platform_version_release_date = $_POST["new_platform_version_release_date"];
	}	
}

$dbconnection = getDBconnection();

// delete platform version
if (isset($_POST['delete_selected_platform_version'])) {
	$deletestring = "DELETE FROM platform_version WHERE id =\"" . $selected_platform_version_id . "\"";
	mysqli_query($dbconnection, $deletestring);
	if (mysqli_commit($dbconnection)){
		echo "<br><br><b>PLATFORM version deleted</b><br><br>"; 
	}
	else {
		echo "Platform version NOT deleted<br><br>";
	}
}
// delete module version
if (isset($_POST['delete_selected_version'])) {
	$deletestring = "DELETE FROM platform_version WHERE id =\"" . $selected_platform_version_id . "\"";
	mysqli_query($dbconnection, $deletestring);
	if (mysqli_commit($dbconnection)){
		echo "<br><br><b>PLATFORM version deleted</b><br><br>"; 
	}
	else {
		echo "Platform version NOT deleted<br><br>";
	}
}

// insert new platform version
elseif (!empty($new_platform_version)) {
	$querystring = "SELECT DISTINCT id from platform_version ORDER BY id DESC";
	$queryresult = mysqli_query($dbconnection, $querystring);
	if ($result = mysqli_fetch_array($queryresult)) {
	    $highest_id = $result[0];
	} 

	$insertstring = "INSERT INTO platform_version (id, platform_id, version_number";
	if (!empty($new_platform_version_release_date)) {
		$insertstring = $insertstring . ", date_available";
	}
	$insertstring = $insertstring . ") VALUES ('" . strval($highest_id+1) . "', '" . $selected_platform_id . "', '" . $new_platform_version;
	if (!empty($new_platform_version_release_date)) {
		$insertstring = $insertstring . "', '" . $new_platform_version_release_date;
	}
	$insertstring = $insertstring . "')";

	mysqli_query($dbconnection, $insertstring);
	if (mysqli_commit($dbconnection)){
		echo "<br><br><b>PLATFORM " . $new_platform_version . " inserted</b><br><br>"; 
	}
	else {
		echo "New platform NOT inserted<br><br>";
	} 
}

// start form
echo "<br><form action=\"kcofeatureupdate.php\" method=\"post\">";

doPlatforms($dbconnection, $selected_platform_id);

$querystring = "SELECT version_number, date_available, id FROM platform_version WHERE platform_id = " . $selected_platform_id . " ORDER BY version_number ASC";
$platform_version_query = mysqli_query($dbconnection, $querystring);

// show platform versionsm, with delete button
if (doPlatformVersionsExist($dbconnection, $platform_version_query)) {
	$selected_platform_version = doPlatformVersions($dbconnection, $platform_version_query, $selected_platform_version_id);
	echo "<input type=\"submit\" name=\"delete_selected_platform_version\" value=\"Delete\"><br>";
} else {
	echo "</select><br>";
}

// data for inserting a new platform version
if ($selected_platform_id !== 0) {
	echo "<br>Add a new platform version:<br>";
	echo "New Platform Version: <input type=\"text\" name=\"new_platform_version\">";
	echo "<br>New Version Release Date: <input type=\"date\" name=\"new_platform_version_release_date\">";
	echo "<br><input type=\"submit\" value=\"Insert\">";
	echo "</form>";			
}


mysqli_close($dbconnection);

?>

</body>
</html>