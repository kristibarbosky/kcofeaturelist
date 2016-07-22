<?php

function getDBconnection() {
	$hostname="localhost";
	$dbuser="kco_user";
	$password="ksKXOEVRxG4R";
	$dbname="kco_featurelist";

	//connection to the database
	$dbconnection = mysqli_connect($hostname, $dbuser, $password, $dbname);
	if (!$dbconnection) {
	    echo "Error: Unable to connect to MySQL." . PHP_EOL;
	    echo "Debugging errno: " . mysqli_connect_errno() . PHP_EOL;
	    echo "Debugging error: " . mysqli_connect_error() . PHP_EOL;
	    exit;
	}

	return $dbconnection;
}




function doPlatforms($dbconnection, $selected_platform_id) {
	echo "Select a platform <select onchange=\"this.form.submit()\" name=\"platform\">";

	// get all platforms
	$querystring = "SELECT name, id FROM platform ORDER BY name ASC";
	$platform_query = mysqli_query($dbconnection, $querystring);
 
 	$selected_value = "";
	// print platforms
	$selected_platform = "";
	while($result = mysqli_fetch_array($platform_query)) {
	    $plaform_name = $result['name'];
	    $plaform_id = $result['id'];
	    if ($selected_platform_id == $plaform_id) {
	    	$selected_value = "selected";
	    	$selected_platform = $plaform_name;
	    }
	    printf("<option value = \"%s\" %s >%s</option>\n", $plaform_id, $selected_value, $plaform_name);
		$selected_value = "";	
	}
	if (empty($selected_platform)) {
		echo ("<option selected value =\"\">");
	}

	echo "</select><br><br>";
	
	return $selected_platform;
}


function doPlatformVersionsExist($dbconnection, $platform_version_query) {
	return (mysqli_num_rows($platform_version_query) > 0);
}


function getPlatformVersionId($dbconnection, $selected_platform) {
	$querystring = "SELECT id FROM platform WHERE name=\"" . $selected_platform . "\"";
	$platform_query_by_name = mysqli_query($dbconnection, $querystring);
	$result = mysqli_fetch_array($platform_query_by_name);
	return $result['id'];
}

function doPlatformVersions ($dbconnection, $platform_version_query, $selected_platform_version_id) {
	$matching_platform_version_id = 0;

	echo "Select a platform version  <select onchange=\"this.form.submit()\" name=\"platform_version_id\">";	

	$selected_value = "";
	while($result = mysqli_fetch_array($platform_version_query)) {
	    $platform_version = $result['version_number'];	
	    $platform_version_id = $result['id'];	

	    if ($matching_platform_version_id == 0) {
			$matching_platform_version_id = $platform_version_id;
		}

	    $release_date = $result['date_available'];
	    if ($selected_platform_version_id == $platform_version_id) {
	    	$selected_value = "selected";
	    	$matching_platform_version_id = $selected_platform_version_id;
	    }

	    printf("\n<option value = \"%s\" %s >%s", $platform_version_id, $selected_value, $platform_version);
	    if (!is_null($release_date)) {
	   	 	printf(", released %s", $release_date);
	   	}
	    printf("</option>");
	    $selected_value = "";
	}

	echo "</select>";

	return $matching_platform_version_id;
}



function printModuleVersions ($dbconnection, $selected_platform_version_id, $selected_module_version_id) {
	$selected_module_id = 0;
	$querystring = "SELECT module_version.id, module.name, module_version.version_number FROM module, module_version_platform_version, module_version, platform_version WHERE module_version_platform_version.platform_version_id=platform_version.id AND module_version.module_id=module.id AND module_version_platform_version.module_version_id=module.id AND platform_version.id=\"" . $selected_platform_version_id . "\" ORDER BY module_version.version_number ASC";
	$queryresult = mysqli_query($dbconnection, $querystring);

	$matching_module_version_id = 0;
	$selected_value = "";		
	echo "<br><br>Select a module version  <select name=\"module_version_id\" onchange=\"this.form.submit()\">";	
	while($result = mysqli_fetch_array($queryresult)) {
	    $module_version = $result['version_number'];
	    $module_name = $result['name'];
	    $module_version_id = $result['id'];
		if ($matching_module_version_id == 0) {
			$matching_module_version_id = $module_version_id;
		}	    
	    if ($selected_module_version_id == $module_version_id) {
	    	$selected_value = "selected";
	    	$matching_module_version_id = $module_version_id;
	    }
	    printf("<option value = \"%s\" %s >%s, version %s</option>\n", $module_version_id, $selected_value, $module_name, $module_version);
	   	$selected_value = "";	
	}
	echo "</select><br><br>";

	return $matching_module_version_id;
}

?>