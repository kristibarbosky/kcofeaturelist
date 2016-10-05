<?php

define('MYSQL', 'MYSQL'); 
define('POSTGRES', 'POSTGRES'); 
$whichDB = POSTGRES;

class kcoModule {
	private $module_name;
	private $module_id;
	private $module_si_id;
	private $selected_module_version_id;

	function __construct($name, $id, $si_id, $selected_version_id) {
       $this->module_name = $name;
       $this->module_id = $id;
       $this->module_si_id = $si_id;
       $this->selected_module_version_id = $selected_version_id;
   	}

   	public function getModuleName() {
   		return $this->module_name;
   	}

   	public function getModuleId() {
   		return $this->module_id;
   	}

   	public function getSystemIntegratorId() {
   		return $this->module_si_id;
   	}  

   	public function getSelectedModuleVersionId() {
   		return $this->selected_module_version_id;
   	}

   	public function __toString()
    {
        return 'Module name: ' . $this->module_name . ', module id: ' . $this->module_id . ', si id: ' . $this->module_si_id . ', selected module version id: ' . $this->selected_module_version_id;
    }   	

}

class kcoPlatform {
	private $platform_name;
	private $platform_id;

	function __construct($name, $id) {
       $this->platform_name = $name;
       $this->platform_id = $id;
   	}

   	public function getPlatformName() {
   		return $this->platform_name;
   	}

   	public function getPlatformId() {
   		return $this->platform_id;
   	} 

   	public function setPlatformName($name) {
   		$this->platform_name = $name;
   	} 	
   	
   	public function setPlatformId($id) {
   		$this->platform_id = $id;
   	} 

   	public function __toString()
    {
        return 'Platform name: ' . $this->platform_name . ', platform_id: ' . $this->platform_id;
    }
}




function getDBconnection() {
	global $whichDB;

	echo "which DB = " . $whichDB;

	if ($whichDB == MYSQL) {
		// MySQL
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
	}
	else {
		// Postgres
		$conn_string = "host=mart1-prod-vz-db1.internal.machines port=5432 dbname=kco_featurelist user=sys.pg.kcofeatrlist password=2J%cbnheZu";

		$dbconnection = pg_connect($conn_string);

		if (!$dbconnection) {
			echo "Unable to connect to Postgres DB";
			exit;
		}
	}

	return $dbconnection;
}


function dbQuery ($dbconnection, $querystring) {
	global $whichDB;

	if ($whichDB == MYSQL) {
		return mysqli_query($dbconnection, $querystring);
	}
	elseif ($whichDB == POSTGRES) {
		return pg_query($dbconnection, $querystring);
	}
}

function dbFetchArray ($result) {
	global $whichDB;

	if ($whichDB == MYSQL) {
		return mysqli_fetch_array($result);
	}
	elseif ($whichDB == POSTGRES) {
		return pg_fetch_array($result);
	} 
}


function dbNumRows ($result) {
	global $whichDB;	

	if ($whichDB == MYSQL) {
		return mysqli_num_rows($result);
	}
	elseif ($whichDB == POSTGRES) {
		return pg_num_rows($result);
	}
}


function dbCommit ($dbconnection) {
	global $whichDB;

	if ($whichDB == MYSQL) {
		return mysqli_commit($dbconnection);
	}
	elseif ($whichDB == POSTGRES) {
		return true;
	} 
}

function dbClose ($dbconnection) {
	global $whichDB;

	if ($whichDB == MYSQL) {
		return mysqli_close($dbconnection);
	}
	elseif ($whichDB == POSTGRES) {
		return pg_close($dbconnection);
	} 
}




function doPlatforms($dbconnection, $selected_platform_id) {
	echo "<br><form action=\"klarnafeaturelist.php\" method=\"post\"><table id=\"platform_table\" class=\"mytable\">\n<tr><th>Platform</th><th>Type</th><th>URL</th><th>Comments</th></tr>";

	echo "<tr><td style=\"width:30%\"><input type='hidden' name='which_field_changed' value='' />";
	echo "\nSelect a platform <select onchange=\"this.form.which_field_changed.value=this.name;this.form.submit()\" name=\"platform_id\"><br>";

	$kcoPlatform = new kcoPlatform("", $selected_platform_id);
	// get all platforms
	$querystring = "SELECT name, id, type, comments, url FROM platform ORDER BY name ASC";
	$platform_query = dbQuery($dbconnection, $querystring);
 
  	$selected_value = "";
	// print platforms
	$selected_platform = "";
	$platform_comments = "";
	$platform_type = "";
	$platform_url = "";
	while($result = dbFetchArray($platform_query)) {
	    $platform_name = $result['name'];
	    $platform_id = $result['id'];
	    if ($selected_platform_id == $platform_id) {
	    	$selected_value = "selected";
	    	$platform_type = $result['type'];
	    	$platform_comments = $result['comments'];
	    	$selected_platform = $platform_name;
	    	$platform_url_from_db =  $result['url'];
	    	if ($platform_url_from_db !== '') {
	    		$platform_url = "<a href=\"" . $platform_url_from_db . "\">" . $platform_name . "</a>";
	    	}
	    	$kcoPlatform->setPlatformName($platform_name);
	    }
	    printf("\n<option value = \"%s\" %s >%s</option>", $platform_id, $selected_value, $platform_name);
		$selected_value = "";	
	}
	if (empty($selected_platform)) {
		echo ("\n<option selected value =\"\">");
	}

	echo("</select></td><td style=\"width:10%\">");
	printf("%s", $platform_type);
	echo("</td><td>");
	printf("%s", $platform_url);
	echo("</td><td style=\"width:70%\">");	
	printf("%s", $platform_comments);
	echo("</td></tr><br><br>");
	
	return $kcoPlatform;
}


function doPlatformVersionsExist($result) {
	if ($result ==  FALSE) {
		return FALSE;
	}
	else {
		return (dbNumRows($result) > 0);
	}
}


function getPlatformVersionId($dbconnection, $selected_platform) {
	$querystring = "SELECT id FROM platform WHERE name=\"" . $selected_platform->getPlatformName() . "\"";
	$platform_query_by_name = dbQuery($dbconnection, $querystring);
	$result = dbFetchArray($platform_query_by_name);
	return $result['id'];
}

function getModulebyVersionId($dbconnection, $selected_module_version_id) {
	$querystring = "SELECT module.name, module.id, module.si_id FROM module, module_version WHERE module_version.id=\"" . $selected_module_version_id . "\" AND module_version.module_id=module.id";
	
	$dbquery = dbQuery($dbconnection, $querystring);
	$result = dbFetchArray($dbquery);
	if (dbNumRows($dbquery) > 0) {
		return new kcoModule($result['name'], $result['id'], $result['si_id'], 0);
	}
	else return null;
}

function doPlatformVersions ($dbconnection, $platform_version_query, $selected_platform_version_id) {
	$matching_platform_version_id = 0;

	echo "\n\n<tr><td>Select a platform version  <select onchange=\"this.form.which_field_changed.value=this.name;this.form.submit()\" name=\"platform_version_id\">";	

	$selected_value = "";
	$type_of_integration = "";
	while($result = dbFetchArray($platform_version_query)) {
	    $platform_version = $result['version_number'];	
	    $platform_version_id = $result['id'];	

	    if ($matching_platform_version_id == 0) {
			$matching_platform_version_id = $platform_version_id;
		}

	    $release_date = $result['date_available'];
	    if ($selected_platform_version_id == $platform_version_id) {
	    	$selected_value = "selected";
	    	$matching_platform_version_id = $selected_platform_version_id;
	    	$type_of_integration = $platform_version_id = $result['type_of_integration'];	
	    }

	    printf("\n<option value = \"%s\" %s >%s", $platform_version_id, $selected_value, $platform_version);
	    if (!is_null($release_date)) {
	   	 	printf(", released %s", $release_date);
	   	}
	    printf("</option>");
	    $selected_value = "";
	}

	printf("</select></td><td>%s</td></tr>", $type_of_integration);

	return $matching_platform_version_id;
}



function printModuleVersions ($dbconnection, $selected_platform_version_id, $selected_module_version_id) {
	$selected_module_id = 0;
	$matching_module_version_id = 0;
	$matchingKcoModule = null;
	$selected_value = "";	

	$querystring = "SELECT module.id, module_version.id, module.name, module_version.version_number, module.si_id FROM module, module_version_platform_version, module_version, platform_version WHERE module_version_platform_version.platform_version_id=platform_version.id AND module_version.module_id=module.id AND module_version_platform_version.module_version_id=module_version.id AND platform_version.id=" . $selected_platform_version_id . " ORDER BY module_version.version_number ASC";

	$queryresult = dbQuery($dbconnection, $querystring);
	if (dbNumRows($queryresult) > 0) {
		echo "<br><br>\n\nSelect a module version  <select name=\"module_version_id\" onchange=\"this.form.which_field_changed.value=this.name;this.form.submit()\">\n";	
		while($result = dbFetchArray($queryresult)) {
			$module_id = $result[0];
		    $module_version_id = $result[1];			
		    $module_name = $result['name'];			
		    $module_version = $result['version_number'];
			if ($matching_module_version_id == 0) {
				$matching_module_version_id = $module_version_id;
			}	    
		    if (($selected_module_version_id == $module_version_id) || ($selected_module_version_id == 0)) {
		    	$selected_value = "selected";
		    	$matching_module_version_id = $module_version_id;
		    	$selected_module_version_id = $module_version_id;
		    	$matchingKcoModule = new kcoModule($module_name, $module_id, $result['si_id'], $module_version_id);
		    }
		    printf("<option value = \"%s\" %s >%s, version %s</option>\n", $module_version_id, $selected_value, $module_name, $module_version);
		   	$selected_value = "";	
		}
		echo "</select>";
	}	

	//echo " matching_kcoModule " . $matchingKcoModule;
	return $matchingKcoModule;
}


function printKlarnaResources($dbconnection, $selected_platform){
	printf("Klarna Resources for <b>%s</b>:<br>", $selected_platform->getPlatformName());
	$querystring = "SELECT person.* FROM platform_klarna_persons, person WHERE person.id=platform_klarna_persons.person_id AND platform_klarna_persons.platform_id=" . $selected_platform->getPlatformId() . " ORDER BY person.last_name";
	$queryresult = dbQuery($dbconnection, $querystring);	
	echo "\n\n<table id=\"klarna_resources_table\" class=\"mytable\">";
	echo "\n<tr><th>First</th><th>Last</th><th>Role</th><th>Email</th><th>Country</th></tr>";
	$country = "";
	while($result = dbFetchArray($queryresult)) {	
		if (array_key_exists('country', $result)) {
			$country = $result['country'];
		}
		printf("\n<tr><td>%s</td><td>%s</td><td>%s</td><td><a href=\"mailto:%s\">%s</a></td><td>%s</td></tr>", $result['first_name'], $result['last_name'], $result['title'], $result['email'], $result['email'], $country);
	}	
	echo "</table></p>\n\n";
}


function printSystemIntegrator($dbconnection, $selectedKcoModule){
		$querystring = "SELECT module.name, system_integrator.* FROM system_integrator, module WHERE system_integrator.id=module.si_id AND module.id=" . $selectedKcoModule->getModuleId();
		
		$selected_si_id = 0;
		$queryresult = dbQuery($dbconnection, $querystring);	
		printf("<p>System Integrator for Module <b>%s</b>: <br>\n", $selectedKcoModule->getModuleName());
		while($result = dbFetchArray($queryresult)) {	
			$selected_si_id = $result['id'];
			printf("<b>%s</b></br>\n", $result['name']);
			printf("%s \n", $result['url']);
		}

		if ($selected_si_id !== 0) {
			// show system integrator contacts
			$querystring = "SELECT person.* FROM system_integrator_persons, person WHERE person.id=system_integrator_persons.person_id AND system_integrator_persons.si_id=" . $selected_si_id . " ORDER BY person.last_name";
			$queryresult = dbQuery($dbconnection, $querystring);	

			if (dbNumRows($queryresult) > 0) {
				echo "\n\n<table id=\"si_table\" class=\"mytable\">";
				echo "\n<tr><th>First</th><th>Last</th><th>Title</th></tr>";
			}
			while($result = dbFetchArray($queryresult)) {	
				printf("\n<tr><td>%s</td><td>%s</td><td>%s</td></tr>", $result['first_name'], $result['last_name'], $result['title']);
			}	
			echo "</table></p>\n\n";
		}
}


function getAllowedCountriesForFeature ($dbconnection, $current_feature_id) {
	$allowedFeaturesQuerystring = "SELECT * FROM feature_allowed_countries WHERE feature_id=" . $current_feature_id . " ORDER BY country_shortcode ASC";
	$queryresult_allowedfeaturecountries = dbQuery($dbconnection, $allowedFeaturesQuerystring);
	$allowedCountries = [];
	while ($result_allowedcountries = dbFetchArray($queryresult_allowedfeaturecountries)) {
		$allowedCountries[] = $result_allowedcountries['country_shortcode'];
	}

	return $allowedCountries;
}


function printModuleFeatures($dbconnection, $selected_module_version_id, $update_allowed) {
	$allFeaturesQueryString = "SELECT feature.id, feature.name, feature.comments, feature_type.type, feature_type.subtype, feature.order_state, feature.product_name from feature, feature_type WHERE feature_type.id=feature.type_id ORDER BY feature_type.type, feature_type.subtype, feature.order_state, feature.name ASC";
	$allFeaturesResult = dbQuery($dbconnection, $allFeaturesQueryString);

	/*$querystring = "SELECT DISTINCT feature.product_name, feature.name, feature.id, feature_type.type, feature_type.subtype, feature.order_state, feature.comments FROM feature_type, feature, module_version_feature_countries WHERE feature_type.id=feature.type_id AND module_version_feature_countries.feature_id=feature.id AND module_version_feature_countries.module_version_id=\"" . $selected_module_version_id . "\" ORDER BY feature_type.type, feature_type.subtype, feature.order_state, feature.name ASC" ;
	$queryresult = dbQuery($dbconnection, $querystring);	*/
	echo "\n\n<b>Features by country:</b><br>\n<table id=\"feature_table\" class=\"mytable\">";
	if (!$update_allowed) {
		echo "\nTo filter the table below, type into the column header box and press enter.  To clear filter, remember to press enter.<br>";
	}
	echo "<tr><th>Product</th><th>Feature Type</th><th>Order State</th><th>Feature Name</th><th>Countries for this Module Version</th>";
	if (!$update_allowed) {
		echo "<th>Applicable Countries for Feature</td>";
	}
	echo "<th>Comments</th></tr>\n";
	$counter = 0;
	while($result = dbFetchArray($allFeaturesResult)) {	
		$counter++;
		$countrieslist = '';
		$allowed_countries_list = '';
		$current_feature_id = $result['id'];
		$current_product_name = $result['product_name'];

		// get countries that apply for this feature & module version
		$querystring = "SELECT * FROM module_version_feature_countries WHERE module_version_feature_countries.feature_id=$current_feature_id AND module_version_feature_countries.module_version_id=" . $selected_module_version_id . " ORDER BY module_version_feature_countries.country ASC";
		$queryresult_featurecountries = dbQuery($dbconnection, $querystring);
		$countries = [];
		while ($result_country = dbFetchArray($queryresult_featurecountries)) {
			$countries[] = $result_country['country'];
		}

		/*echo "Countries for feature from module_version_feature_countries for current feature " . $current_feature_id;
		print_r($countries);
		echo "<br><br>\n\n"; */

		// build allowed country list for this feature
		$allowedCountries = getAllowedCountriesForFeature($dbconnection, $current_feature_id);
		foreach ($allowedCountries as $country) {
			if($allowed_countries_list !== '') {
				$allowed_countries_list = $allowed_countries_list . ", ";
			}			
			$allowed_countries_list = $allowed_countries_list . $country;
		}

		/*echo "Allowed Countries for feature  : ";
		print_r($allowedCountries);
		echo "<br><br>\n\n"; */

		// if doing an update, show all allowed countries, checking the ones that apply
		if ($update_allowed) {
			foreach ($allowedCountries as $currentCountry) {
				$countrieslist = $countrieslist . "\n<br><input onchange=\"this.form.which_field_changed.value=this.name;\" type=\"checkbox\" name=\"feature_country" . $currentCountry . $current_feature_id . "\" ";
				if (in_array($currentCountry, $countries)){
					$countrieslist = $countrieslist . " checked ";
				}
				$countrieslist = $countrieslist . " value=\"" . $currentCountry . "\">" . $currentCountry;
			}
		}

		// not update page, view only
		else {
			foreach ($countries as $country) {		
				if($countrieslist !== '') {
					$countrieslist = $countrieslist . " , ";
				}

				$querystring = "SELECT * FROM api WHERE feature_id=" . $current_feature_id . " AND country_shortcode='" . $country . "'";
				//echo $querystring;
				$queryresult_api = dbQuery($dbconnection, $querystring);
				$apiurl = "";
				if ($result_api = dbFetchArray($queryresult_api)) {
					$apiurl = $result_api['url'];
					$countrieslist = $countrieslist . "<a href=\"". $apiurl . "\">" . $country . "</a>";
				}
				else {
					$countrieslist = $countrieslist . $country;
				}
			}
		}

		if (empty($countrieslist)) {
			$countrieslist = "None";
		}
		if (empty($allowed_countries_list)) {
			$allowed_countries_list = "None";
		}

		if ($update_allowed) {
			printf("<tr><td>%s</td><td>%s-%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>\n", $current_product_name, $result['type'], $result['subtype'], $result['order_state'], $result['name'], $countrieslist, $result['comments']);
			printf("<input type=\"hidden\" name=\"num_features\" value=\"%s\">", $counter);	
		}
		else {
			printf("<tr><td>%s</td><td>%s-%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>\n", $current_product_name, $result['type'], $result['subtype'], $result['order_state'], $result['name'], $countrieslist, $allowed_countries_list, $result['comments']);
		}	
	}
	echo "</table></p>\n\n"; 
}

?>