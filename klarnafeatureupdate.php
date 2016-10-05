<html>
<head>
	<title>Klarna Feature Update</title>
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
<b>Klarna Feature List Update Page</b>
<br>
**Internal Only**
<br>

<?php

require 'klarnafeaturelist_sharedfunctions.php';

$selected_platform_version_id = 0;
$selected_platform = null;
$selected_platform_id = 0;
$selectedKcoModule = null;
$selected_module_version_id = 0;
$selected_module_id = 0;
$selected_value = "";
$selected_si_id = 0;
$module_name = "";
$new_platform_version = "";
$new_platform_version_release_date = "";
$new_module_version = "";
$new_module_version_release_date = "";
$should_exit = false;
$need_password = true;
$which_field_changed = "";

function checkPassword($dbconnection) {
	$enteredPassword = "";
	$need_password = true;
	if (array_key_exists("kco_feature_pwd", $_POST)) {
		$enteredPassword = $_POST["kco_feature_pwd"];
	}

	$querystring = "SELECT password FROM kco_feature_pwd";
	$queryresult = dbQuery($dbconnection, $querystring);
	if ($result = dbFetchArray($queryresult)) {
	    if ($enteredPassword !== $result['password']) {
	    	echo "<br><br><b>Password not correct<b><br><br>";
	    }
	    else {
	    	$need_password = false;
	    	echo "<input type=\"hidden\" name=\"password_success\" value=\"password_success\"><br>\n";
	    }
	} 
	else {
		echo "Password not set.";
	}

	return $need_password;
}


function deletePlatformVersion ($dbconnection, $platformVersionId) {

	$deletestring = "DELETE FROM platform_version WHERE id =" . $platformVersionId;
	//echo $deletestring;
	dbQuery($dbconnection, $deletestring);
	if (dbCommit($dbconnection)){
		echo "<br><br><b>PLATFORM version deleted</b><br><br>"; 
	}
	else {
		echo "Platform version NOT deleted<br><br>";
	}
}



function deleteModuleVersion ($dbconnection, $selected_module_version_id) {
	$deletestring = "DELETE FROM module_version WHERE id=" . $selected_module_version_id;
	dbQuery($dbconnection, $deletestring);

	// delete from module_version_platform_version
	$deletestring = "DELETE FROM module_version_platform_version WHERE module_version_id=" . $selected_module_version_id;
	dbQuery($dbconnection, $deletestring);
	//echo $deletestring;

	// delete from module_version_feature_countries
	$deletestring = "DELETE FROM module_version_feature_countries WHERE module_version_id=" . $selected_module_version_id;
	dbQuery($dbconnection, $deletestring);
	//echo $deletestring;

	if (dbCommit($dbconnection)){
		echo "<br><br><b>MODULE version deleted</b><br><br>"; 
	}
	else {
		echo "Module version NOT deleted<br><br>";
	}
}


function getNextId($dbconnection, $table_name) {
	$querystring = "SELECT DISTINCT id from " . $table_name . " ORDER BY id DESC";
	$queryresult = dbQuery($dbconnection, $querystring);
	if ($result = dbFetchArray($queryresult)) {
	    $highest_id = $result[0];
	} 
	return ($highest_id + 1);
}


function updateFeatures ($dbconnection, $selected_module_version_id) {
	$num_features = $_POST["num_features"];

	for ($feature_id = 0; $feature_id < $num_features; $feature_id++) {

		$allowedCountries = getAllowedCountriesForFeature($dbconnection, $feature_id);

		//echo "<BR><BR>feature_id " . $feature_id;

		$countriesForFeature = [];
		$checkbox_name = "";
		foreach ($allowedCountries as $currentCountry) {
			$checkbox_name = 'feature_country' . $currentCountry . strval($feature_id);

			if (array_key_exists($checkbox_name, $_POST)) {
				$countriesFromForm = $_POST[$checkbox_name];
				$countriesForFeature[] = $countriesFromForm;
			}
		}

		/*echo "<br>\nAllowed Countries:";
		print_r($allowedCountries);
		echo "<br>\ncountriesForFeature " . $feature_id;
		print_r($countriesForFeature); */

		$countries_to_delete = array_diff($allowedCountries, $countriesForFeature);
		//echo "<br>countries_to_delete:";
		//print_r($countries_to_delete);

		// insert into database (if not already existing)
		foreach ($countriesForFeature as $country) {
			$querystring = "SELECT * from module_version_feature_countries WHERE feature_id=" . $feature_id . " AND module_version_id=" . $selected_module_version_id . " AND country='" . $country . "'";
			$queryresult = dbQuery($dbconnection, $querystring);

			if (!dbFetchArray($queryresult)) {
				$insertstring = "INSERT INTO module_version_feature_countries (feature_id, module_version_id, country) VALUES (" . $feature_id . ", " . $selected_module_version_id . ", '" . $country . "')";
				dbQuery($dbconnection, $insertstring);
			}
		}

		// delete countries 
		foreach ($countries_to_delete as $country) {
			$deletestring = "DELETE FROM module_version_feature_countries WHERE feature_id=" . $feature_id . " AND module_version_id=" . $selected_module_version_id . " AND country='" . $country . "'";
			//echo $deletestring;
			dbQuery($dbconnection, $deletestring);
		}			
	}

	if (dbCommit($dbconnection)){
		echo "<br><br><b>Feature countries updated.</b><br><br>"; 
	}
	else {
		echo "Feature countries NOT updated.<br><br>";
	}	
}


function insertNewModuleVersion($dbconnection, $selected_module_id, $new_module_version, $selected_platform_version_id) {
	$new_module_version_id = getNextId($dbconnection, "module_version");

	// query for previous module version
	$querystring = "SELECT module_version.id FROM module_version, module_version_platform_version WHERE module_version.id=module_version_platform_version.module_version_id AND module_version_platform_version.platform_version_id=" . $selected_platform_version_id . " ORDER BY module_version.version_number DESC";
	$queryresult = dbQuery($dbconnection, $querystring);
	$previous_version = "";
	if ($result = dbFetchArray($queryresult)) {
		$previous_version = $result['id'];
	}	

	$insertstring = "INSERT INTO module_version (id, module_id, version_number";
	
	if (array_key_exists("new_module_version_release_date", $_POST)) {
		$new_module_version_release_date = $_POST["new_module_version_release_date"];
	}

	if (!empty($new_module_version_release_date)) {
		$insertstring = $insertstring . ", date_available";
	}
	$insertstring = $insertstring . ") VALUES ('" . $new_module_version_id . "', '" . $selected_module_id . "', '" . $new_module_version;
	if (!empty($new_module_version_release_date)) {
		$insertstring = $insertstring . "', '" . $new_module_version_release_date;
	}
	$insertstring = $insertstring . "')";
	//echo $insertstring . "<br>\n";

	dbQuery($dbconnection, $insertstring);

	// after inserting into module_version, insert into module_version_platform_version
	$insertstring = "INSERT INTO module_version_platform_version (module_version_id, platform_version_id) VALUES (" . $new_module_version_id . ", " . $selected_platform_version_id . ")";
	//echo $insertstring . "<br>\n";

	dbQuery($dbconnection, $insertstring);

	// insert copies of previous version features
 	if (!empty($previous_version)) {
		$querystring = "SELECT * FROM module_version_feature_countries WHERE module_version_id=" . $previous_version;
		$queryresult = dbQuery($dbconnection, $querystring);
		while ($result = dbFetchArray($queryresult)) {
			$insertstring = "INSERT INTO module_version_feature_countries (feature_id, module_version_id, country) VALUES (" . $result['feature_id'] . ", " . $new_module_version_id . ", '" . $result['country'] . "')";
			dbQuery($dbconnection, $insertstring);
			//echo $insertstring;
		}
	}

	if (dbCommit($dbconnection)){
		echo "<br><b>New module version " . $new_module_version . " inserted</b><br>"; 
	}
	else {
		echo "New module NOT inserted<br><br>";
	} 
}




// get values from page if previously selected
if ($_POST) {
	if (array_key_exists("platform_id", $_POST)) {
		$selected_platform_id = $_POST["platform_id"];
	}
	if (empty($selected_platform_id)) {
		$selected_platform_id = 0;
	}
	if (array_key_exists("platform_version_id", $_POST)) {
		$selected_platform_version_id = $_POST["platform_version_id"];
	}
	if (array_key_exists("selected_module_id", $_POST)) {
		$selected_module_id = $_POST["selected_module_id"];
	}	
	if (array_key_exists("module_version_id", $_POST)) {
		$selected_module_version_id = $_POST["module_version_id"];
	}	
	if (array_key_exists("new_platform_version", $_POST)) {
		$new_platform_version = $_POST["new_platform_version"];
	}		
	if (array_key_exists("new_module_version", $_POST)) {
		$new_module_version = $_POST["new_module_version"];
	}	
	if (array_key_exists("password_success", $_POST)) {
		$need_password = false;
	}	
	if (array_key_exists("which_field_changed", $_POST)) {
		$which_field_changed = $_POST["which_field_changed"];
	}	
}


// reset the selected_module_version_id if needed
if ($which_field_changed !== "module_version_id" && 
	strpos($which_field_changed, "feature_country") === false &&
	!isset($_POST['delete_selected_module_version'])) {
	$selected_module_version_id = 0;
}

$dbconnection = getDBconnection();

// check password
if (isset($_POST['kco_feature_pwd_submit'])) {
	$need_password = checkPassword($dbconnection);
}
// delete platform version
elseif (isset($_POST['delete_selected_platform_version'])) {
	deletePlatformVersion($dbconnection, $selected_platform_version_id);
}
// delete module version
elseif (isset($_POST['delete_selected_module_version'])) {
	deleteModuleVersion($dbconnection, $selected_module_version_id);
	$selected_module_version_id = 0;
}
// update countries for features
elseif (isset($_POST['update_feature_countries'])) {
	updateFeatures($dbconnection, $selected_module_version_id);
}
// insert new module
elseif (isset($_POST['insert_new_module_name'])) {
	$new_module_name = "";
	if (array_key_exists("new_module_name", $_POST)) {
		$new_module_name = $_POST["new_module_name"];
	}
	$si_id = $_POST["si"];
	if ($si_id == "new_si") {
		if (array_key_exists("new_si_name", $_POST)) {
			$si_name = $_POST["new_si_name"];
			if (!empty($si_name)) {
				$si_url = $_POST["new_si_url"];
				$si_id = getNextId($dbconnection, "system_integrator");
				// insert new SI
				$insertstring = "INSERT INTO system_integrator (id, name, url) VALUES ('" . $si_id . "', '" . $si_name . "', '" . $si_url . "')";
			
				dbQuery($dbconnection, $insertstring);
				if (dbCommit($dbconnection)){
					echo "<br><br><b>SI " . $si_name . " inserted</b><br><br>"; 
				}
				else {
					echo "New SI NOT inserted<br><br>";
				} 
			}
		}
	}

	// insert new module
	if (!empty($new_module_name) &&  $si_id != 0) {
		$nextId = getNextId($dbconnection, "module");
		$insertstring = "INSERT INTO module (id, name, si_id) VALUES ('" . $nextId . "', '" . $new_module_name . "', '" . $si_id . "')";
		
		dbQuery($dbconnection, $insertstring);

		// insert new module version too
		insertNewModuleVersion($dbconnection, $nextId, $new_module_version, $selected_platform_version_id);	

		if (dbCommit($dbconnection)) {
			echo "<br><br><b>New Module " . $new_module_name . " inserted</b><br><br>"; 
		}
		else {
			echo "New Module NOT inserted<br><br>";
		}		
	}
}

// insert new module form
elseif (isset($_POST['insert_new_module'])){
	echo "<form action=\"klarnafeatureupdate.php\" method=\"post\">";
	echo "<input type=\"hidden\" name=\"platform_version_id\" value=\"" . $selected_platform_version_id . "\"><br>";
	echo "<br>New Module Name: <input type=\"text\" name=\"new_module_name\"><br>";

	$querystring = "SELECT * from system_integrator ORDER BY name ASC";
	$queryresult = dbQuery($dbconnection, $querystring);

	echo "<br>System Integrators:<br>";
	echo "<input type=\"radio\" name=\"si\" value=\"new_si\" checked>New SI<br>";
	echo "New SI name: <input type=\"text\" name=\"new_si_name\"><br>";
	echo "New SI URL: <input type=\"text\" name=\"new_si_url\"><br>";
	while ($result = dbFetchArray($queryresult)) {
	    echo "<input type=\"radio\" name=\"si\" value=\"" . $result['id'] . "\"";
	    echo ">" . $result['name']. "<br>";
	} 

	echo "<br>New Module Version: <input type=\"text\" name=\"new_module_version\">";
	echo "<br>New Version Release Date: <input type=\"date\" name=\"new_module_version_release_date\">";

	echo "<br><br><input type=\"submit\" name=\"insert_new_module_name\" value=\"Insert\">";	
	echo "<input type=\"hidden\" name=\"password_success\" value=\"password_success\"><br>\n";
	echo "</form>";
	$should_exit = true;
}
/*elseif (isset($_POST['insert_new_is'])){
	echo "<form action=\"klarnafeatureupdate.php\" method=\"post\">";
	echo "New SI Name: <input type=\"text\" name=\"new_si\">";
	
	echo "<br><input type=\"submit\" value=\"Insert\">";	
	echo "</form>";
	$should_exit = true;
} */

// insert new platform version
elseif (!empty($new_platform_version)) {
	$nextId = getNextId($dbconnection, "platform_version");
	$insertstring = "INSERT INTO platform_version (id, platform_id, version_number";

	if (array_key_exists("new_platform_version_release_date", $_POST)) {
		$new_platform_version_release_date = $_POST["new_platform_version_release_date"];
	}

	if (!empty($new_platform_version_release_date)) {
		$insertstring = $insertstring . ", date_available";
	}
	$insertstring = $insertstring . ") VALUES ('" . $nextId . "', '" . $selected_platform_id . "', '" . $new_platform_version;
	if (!empty($new_platform_version_release_date)) {
		$insertstring = $insertstring . "', '" . $new_platform_version_release_date;
	}
	$insertstring = $insertstring . "')";

	echo "$insertstring";
	dbQuery($dbconnection, $insertstring);
	if (dbCommit($dbconnection)){
		echo "<br><br><b>PLATFORM " . $new_platform_version . " inserted</b><br><br>"; 
	}
	else {
		echo "New platform NOT inserted<br><br>";
	} 
}


// insert new module version
elseif (!empty($new_module_version)) {
	insertNewModuleVersion($dbconnection, $selected_module_id, $new_module_version, $selected_platform_version_id);
}


// start form
echo "<form action=\"klarnafeatureupdate.php\" method=\"post\">";

if ($need_password) {
	echo "\n<br><br>Password: <input type=\"password\" name=\"kco_feature_pwd\" autofocus><br>";	
	echo "<input type=\"submit\" name=\"kco_feature_pwd_submit\" value=\"Submit\"><br>";
	echo "</form>";
}
elseif (!$should_exit) {
	$need_password = false;
	//echo "<table class=\"platformtable\">\n<tr><td style=\"width:30%\"><b>Platform</b></td><td style=\"width:10%\"><b>Type</b></td><td><b>Comments</b></td></tr>";
	echo"<input type=\"hidden\" name=\"password_success\" value=\"password_success\"><br>\n";

	$selected_platform = doPlatforms($dbconnection, $selected_platform_id);

	$querystring = "SELECT version_number, date_available, id, type_of_integration FROM platform_version WHERE platform_id = " . $selected_platform_id . " ORDER BY version_number DESC";
	$platform_version_query = dbQuery($dbconnection, $querystring);


	// show platform versions, with delete button
	if (doPlatformVersionsExist($platform_version_query)) {
		$selected_platform_version_id = doPlatformVersions($dbconnection, $platform_version_query, $selected_platform_version_id);
		echo "<tr><td><input type=\"submit\" name=\"delete_selected_platform_version\" value=\"Delete platform version\"><br></td></tr>";
	} else {
		$selected_module_version_id = 0;
		$selected_platform_version_id = 0;
		echo "</select><br>";
	}
	echo "</table>";

	// data for inserting a new platform version
	if ($selected_platform->getPlatformId() !== 0) {
		echo "<br>Add a new platform version:<br>";
		echo "New Platform Version: <input type=\"text\" name=\"new_platform_version\">";
		echo "<br>New Version Release Date: <input type=\"date\" name=\"new_platform_version_release_date\">";
		echo "<br><input type=\"submit\" value=\"Insert new platform version\">";		
	}

	echo "<br><br><hr>";

	// show module versions, with delete button
	if ($selected_platform_version_id > 0) {
		$selectedKcoModule = printModuleVersions($dbconnection, $selected_platform_version_id, $selected_module_version_id);
		if ($selectedKcoModule != null) {
			echo "  <input type=\"submit\" name=\"delete_selected_module_version\" value=\"Delete\"><br>";
		}

		// data for inserting a new module version
		echo "<br><br>Add a new module version:<br>";
		if ($selectedKcoModule != null) {
			echo "Module name: " . $selectedKcoModule->getModuleName();	
			echo "<input type=\"hidden\" name=\"selected_module_id\" value=\"" . $selectedKcoModule->getModuleId() . "\">";
			echo "   <input type=\"submit\" value=\"Insert New Module\" name=\"insert_new_module\">";
			echo "<br>New Module Version: <input type=\"text\" name=\"new_module_version\">";
			echo "<br>New Version Release Date: <input type=\"date\" name=\"new_module_version_release_date\">";
			echo "<br><input type=\"submit\" value=\"Insert new module version\"><br>Features by country will be copied from previous module version.<br>\n";

			echo "<hr>";

			// show features for module version, by country
			printModuleFeatures($dbconnection, $selectedKcoModule->getSelectedModuleVersionId(), true);

			echo "<input type=\"submit\" value=\"Update Countries for Features\" name=\"update_feature_countries\">\n";
		}
		else {
			echo "<input type=\"submit\" value=\"Insert New Module\" name=\"insert_new_module\">";
		}

	}
	echo "</form>";	
}
dbClose($dbconnection);

?>


</body>
</html>