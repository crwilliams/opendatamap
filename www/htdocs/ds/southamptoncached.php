<?
include_once "inc/sparqllib.php";

class SouthamptoncachedDataSource extends DataSource
{
	static $endpoint = 'http://sparql.data.southampton.ac.uk';

	static function getEntries($q, $cats)
	{
		$q = trim($q);
		
		$pos = array();
		$label = array();
		$type = array();
		$url = array();
		$icon = array();
		self::createBuildingEntries($pos, $label, $type, $url, $icon, $q, $cats);
		self::createSiteEntries($pos, $label, $type, $url, $icon, $q, $cats);
		self::createPointOfServiceEntries($pos, $label, $type, $url, $icon, $q, $cats);
		self::createBusEntries($pos, $label, $type, $url, $icon, $q, $cats);
		self::createWorkstationEntries($pos, $label, $type, $url, $icon, $q, $cats);
		return array($pos, $label, $type, $url, $icon);
	}

	static function getDataSets()
	{
		$uri = "http://opendatamap.ecs.soton.ac.uk";
		$ds = sparql_get(self::$endpoint, "
		SELECT DISTINCT ?name ?uri ?l {
		  ?app <http://xmlns.com/foaf/0.1/homepage> <$uri> .
		  ?app <http://purl.org/dc/terms/requires> ?uri .
		  ?uri <http://purl.org/dc/terms/title> ?name .
		  OPTIONAL { ?uri <http://purl.org/dc/terms/license> ?l . }
		} ORDER BY ?name
		");
		$ds[] = array('name' => 'Ordnance Survey Linked Data', 'uri' => 'http://data.ordnancesurvey.co.uk', 'l' => 'http://reference.data.gov.uk/id/open-government-licence');
		return $ds;
	}

	static function getDataSetExtras()
	{
		return array("Contains Ordnance Survey data &copy; Crown copyright and database right 2011.  Contains Royal Mail data &copy; Royal Mail copyright and database right 2011.");
	}

	static function getAll()
	{
		$q = "SELECT uri AS id, lat, lng AS `long`, label, icon FROM points";
		return self::perform_query($q);
	}
	
	static function perform_query($q)
	{
		include_once('/home/opendatamap/mysql.inc.php');
		$res = mysql_query($q);
		$data = array();
		while($row = mysql_fetch_assoc($res))
		{
			$data[] = $row;
		}
		return $data;
	}

	static function getPointsOfService($q, $cats)
	{
		return self::getByType('point-of-service', $q, $cats);
	}

	static function getBusStops($q, $cats)
	{
		return self::getByType('bus-stop', $q, $cats);
	}

	static function getWorkstationRooms($q, $cats)
	{
		return self::getByType('workstation', $q, $cats);
	}

	static function getByType($type, $q, $cats)
	{
		$safecats = array();
		foreach($cats as $cat)
		{
			$safecats[] = "'".mysql_escape_string($cat)."'";
		}
		$cats = implode(',', $safecats);		
		
		
		if($q == '')
		{
			$q = "SELECT poslabel, label, uri AS pos, icon FROM matches WHERE type = '$type' AND category IN ($cats)";
		}
		else
		{
			$q = mysql_escape_string($q);
			$q = "SELECT poslabel, label, uri AS pos, icon FROM matches WHERE type = '$type' AND (poslabel REGEXP '$q' OR label REGEXP '$q') AND category IN ($cats)";
		}
		return self::perform_query($q);
	}
	
	static function getBuildings($q, $qbd)
	{
		return sparql_get(self::$endpoint, "
	SELECT DISTINCT ?url ?name ?number WHERE {
	  ?url a <http://id.southampton.ac.uk/ns/UoSBuilding> .
	  ?url <http://www.w3.org/2000/01/rdf-schema#label> ?name .
	  ?url <http://www.w3.org/2004/02/skos/core#notation> ?number .
	  FILTER ( REGEX( ?name, '$q', 'i') || REGEX( ?number, '$qbd', 'i') )
	} ORDER BY ?number
		");
	}
	
	static function getAllSites()
	{
		return sparql_get(self::$endpoint, "
		SELECT DISTINCT ?url ?name ?outline WHERE {
		  ?url a <http://www.w3.org/ns/org#Site> .
		  ?url <http://purl.org/dc/terms/spatial> ?outline .
		  ?url <http://www.w3.org/2000/01/rdf-schema#label> ?name .
		} 
		");
	}
	
	static function getAllBuildings()
	{
		return sparql_get(self::$endpoint, "
		SELECT DISTINCT ?url ?name ?outline ?lat ?long ?hfeature ?lfeature ?number WHERE {
		  ?url a <http://id.southampton.ac.uk/ns/UoSBuilding> .
		  OPTIONAL { ?url <http://purl.org/dc/terms/spatial> ?outline . }
		  ?url <http://www.w3.org/2003/01/geo/wgs84_pos#lat> ?lat .
		  ?url <http://www.w3.org/2003/01/geo/wgs84_pos#long> ?long .
		  ?url <http://www.w3.org/2000/01/rdf-schema#label> ?name .
		  OPTIONAL { ?url <http://purl.org/openorg/hasFeature> ?hfeature . 
		           ?hfeature <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://id.southampton.ac.uk/ns/PlaceFeature-ResidentialUse> }
		  OPTIONAL { ?url <http://purl.org/openorg/lacksFeature> ?lfeature . 
		           ?lfeature <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://id.southampton.ac.uk/ns/PlaceFeature-ResidentialUse> }
		  OPTIONAL { ?url <http://www.w3.org/2004/02/skos/core#notation> ?number . }
		} 
		");
	}

	static function getSites($q)
	{
		return sparql_get(self::$endpoint, "
	SELECT DISTINCT ?url ?name WHERE {
	  ?url a <http://www.w3.org/ns/org#Site> .
	  ?url <http://www.w3.org/2000/01/rdf-schema#label> ?name .
	  ?url <http://purl.org/dc/terms/spatial> ?outline .
	  FILTER ( REGEX( ?name, '$q', 'i') )
	} ORDER BY ?url
		");
	}

/*
	static function visibleCategory($icon, $cats)
	{
		global $iconcats;
		if($iconcats == null) include_once "inc/categories.php";
		return in_cat($iconcats, $icon, $cats);
	}
*/

	// Process point of service data
	static function createPointOfServiceEntries(&$pos, &$label, &$type, &$url, &$icon, $q, $cats)
	{
		$data = self::getPointsOfService($q, $cats);
		foreach($data as $point) {
//			if(!self::visibleCategory($point['icon'], $cats))
//				continue;
			$point['icon'] = self::convertIcon($point['icon']);
			$pos[$point['pos']] ++;
			if(preg_match('/'.$q.'/i', $point['label']))
			{
				$label[$point['label']] ++;
				$type[$point['label']] = "offering";
			}
			if(preg_match('/'.$q.'/i', $point['poslabel']))
			{
				$label[$point['poslabel']] += 10;
				$type[$point['poslabel']] = "point-of-service";
				$url[$point['poslabel']] = $point['pos'];
				$icon[$point['poslabel']] = $point['icon'];
			}
		}
	}

	// Process bus data
	static function createBusEntries(&$pos, &$label, &$type, &$url, &$icon, $q, $cats)
	{
		$data = self::getBusStops($q, $cats);
		foreach($data as $point) {
//			if(!self::visibleCategory($point['icon'], $cats))
//				continue;
			$point['icon'] = str_replace("http://google-maps-icons.googlecode.com/files/bus.png", "http://opendatamap.ecs.soton.ac.uk/resources/busicon.php", $point['icon']);
			$pos[$point['pos']] ++;
			if(preg_match('/'.$q.'/i', $point['label']))
				$label[$point['label']] ++;
				$type[$point['label']] = "bus-route";
			if(preg_match('/'.$q.'/i', $point['poslabel']))
			{
				$routes[$point['poslabel']][] = $point['label'];
				$label[$point['poslabel']] += 10;
				$type[$point['poslabel']] = "bus-stop";
				$url[$point['poslabel']] = $point['pos'];
				$icon[$point['poslabel']] = $point['icon'].'?r='.implode('/', $routes[$point['poslabel']]);
			}
		}
	}

	// Process workstation data
	static function createWorkstationEntries(&$pos, &$label, &$type, &$url, &$icon, $q, $cats)
	{
		$data = self::getWorkstationRooms($q, $cats);
		foreach($data as $point) {
			//$point['icon'] = self::$iconpath.'Education/computers.png';
//			if(!self::visibleCategory($point['icon'], $cats))
//				continue;
			$pos[$point['pos']] ++;
			if(preg_match('/'.$q.'/i', $point['label']))
			{
				$label[$point['label']] ++;
				$type[$point['label']] = "offering";
			}
			if(preg_match('/'.$q.'/i', $point['poslabel']))
			{
				$label[$point['poslabel']] += 10;
				$type[$point['poslabel']] = "workstation";
				$url[$point['poslabel']] = $point['pos'];
				$icon[$point['poslabel']] = $point['icon'];
			}
		}
	}

	// Process building data
	static function createBuildingEntries(&$pos, &$label, &$type, &$url, &$icon, $q, $cats)
	{
		$qbd = trim(str_replace(array('building', 'buildin', 'buildi', 'build', 'buil', 'bui', 'bu', 'b'), '', strtolower($q)));
		$data = self::getBuildings($q, $qbd);
		foreach($data as $point) {
			$pos[$point['url']] += 100;
			if(preg_match('/'.$q.'/i', $point['name']))
			{
				if($point['number'] < 100)
					$label[$point['name']] += 1000;
				else
					$label[$point['name']] += 100;
				$type[$point['name']] = "building";
				$url[$point['name']] = $point['url'];
				$icon[$point['name']] = 'http://opendatamap.ecs.soton.ac.uk/resources/numbericon.php?n='.$point['number'];
			}
			if(preg_match('/'.$qbd.'/i', $point['number']))
			{
				if($point['number'] < 100)
					$label['Building '.$point['number']] += 1000;
				else
					$type['Building '.$point['number']] = "building";
				$type['Building '.$point['number']] = "building";
				$url['Building '.$point['number']] = $point['url'];
				$icon['Building '.$point['number']] = 'http://opendatamap.ecs.soton.ac.uk/resources/numbericon.php?n='.$point['number'];
			}
		}
	}

	// Process site data
	static function createSiteEntries(&$pos, &$label, &$type, &$url, &$icon, $q, $cats)
	{
		$data = self::getSites($q);
		foreach($data as $point) {
			$pos[$point['url']] += 100000;
			$label[$point['name']] += 100000;
			if(preg_match('/Campus/i', $point['name']))
			{
				$pos[$point['url']] += 100000;
				$label[$point['name']] += 100000;
			}
			$type[$point['name']] = "site";
			$url[$point['name']] = $point['url'];
			$icon[$point['name']] = 'http://opendatamap.ecs.soton.ac.uk/resources/numbericon.php?n='.substr($point['name'], 0, 1);
		}
	}
	
	static function processURI($uri)
	{
		if(substr($uri, 0, strlen('http://id.southampton.ac.uk/bus-stop/')) == 'http://id.southampton.ac.uk/bus-stop/')
			return self::processSouthamptonBusStopURI($uri);
		else if(substr($uri, 0, strlen('http://id.southampton.ac.uk/')) == 'http://id.southampton.ac.uk/')
			return self::processSouthamptonURI($uri);
		else if(substr($uri, 0, strlen('http://id.sown.org.uk/')) == 'http://id.sown.org.uk/')
			return self::processSouthamptonURI($uri);
		else
			return false;
	}

	static function processSownURI($uri)
	{
		return true;
		echo '<div id="content">';
		echo '<pre>';
		$data = simplexml_load_file('https://sown-auth.ecs.soton.ac.uk/status-nagios/generateNodesXML.php');
		print_r($data);
		echo '</pre>';
		echo '</div>';
		return true;
	}
	
	static function processSouthamptonBusStopURI($uri)
	{	
		$allpos = self::getURIInfo($uri);
		$allbus = sparql_get(self::$endpoint, "
		SELECT DISTINCT ?code WHERE {
		  ?rstop <http://id.southampton.ac.uk/ns/inBusRoute> ?route .
		  ?rstop <http://id.southampton.ac.uk/ns/busStoppingAt> <$uri> .
		  ?route <http://www.w3.org/2004/02/skos/core#notation> ?code .
		  FILTER ( REGEX( ?code, '^U', 'i') )
		} ORDER BY ?code
		");
		$codes = array();
		foreach($allbus as $code)
			$codes[] = $code['code'];
		echo "<h2><img class='icon' src='http://opendatamap.ecs.soton.ac.uk/resources/busicon.php?r=".implode('/', $codes)."' />".$allpos['name'];
		echo "<a class='odl' href='$uri'>Visit&nbsp;page</a></h2>";
		echo "<h3> Served by: (click to show on map) </h3>";
		echo "<ul class='offers'>"; 
		foreach($allbus as $code) {
			echo "<li ".self::routestyle($code['code'])."onclick=\"setInputBox('^".str_replace(array("(", ")"), array("\(", "\)"), $code['code'])."$'); updateFunc();\">".$code['code']."</li>";
		}
		echo "</ul>";
		echo "<iframe style='border:none' src='bus.php?uri=".$uri."' />";
		return true;
	}

	static function getURIInfo($uri)
	{
		$info = sparql_get(self::$endpoint, "
		PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
		PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
		PREFIX spacerel: <http://data.ordnancesurvey.co.uk/ontology/spatialrelations/>
		PREFIX org: <http://www.w3.org/ns/org#>

		SELECT DISTINCT ?name ?icon ?type ?label ?notation ?ftype WHERE {
		    OPTIONAL { <$uri> rdfs:label ?name . }
		    OPTIONAL { <$uri> a ?type . }
		    OPTIONAL { <$uri> <http://www.w3.org/2004/02/skos/core#notation> ?notation . }
		    OPTIONAL { <$uri> <http://purl.org/openorg/mapIcon> ?icon . }
		    OPTIONAL { <$uri> <http://purl.org/openorg/hasFeature> ?feature . 
		        OPTIONAL { ?feature a ?ftype . }
		        OPTIONAL { ?feature rdfs:label ?label . }
		    }
		}
		");
		$res = array('name' => '', 'icon' => '', 'type' => '', 'label' => '', 'notation' => '', 'ftype' => '');
		foreach($info as $infoline)
		{
			foreach(array_keys($res) as $key)
			{
				if($res[$key] =='' && isset($infoline[$key]))
					$res[$key] = $infoline[$key];
			}
		}
		return $res;
	}

	static function processSouthamptonURI($uri)
	{
		$wifi = false;
		$res = false;
		if(substr($uri, strlen($uri)-5, 5) == '#wifi')
		{
			$uri = substr($uri, 0, strlen($uri)-5);
			$wifi = true;
		}
		if(substr($uri, strlen($uri)-12, 12) == '#residential')
		{
			$uri = substr($uri, 0, strlen($uri)-12);
			$res = true;
		}
		$allpos = self::getURIInfo($uri);
		echo "<div id='content'>";
		$computer = false;
		if($allpos['icon'] == '')
		{
			if($wifi && $allpos['type'] == "http://vocab.deri.ie/rooms#Building")
			{
				$icon = self::$iconpath."Offices/wifi.png";
				$name = 'Wi-Fi Internet Access in Building '.$allpos['notation'];
			}
			else if($res)
			{
				$icon = self::$iconpath."Restaurants-and-Hotels/lodging_0star.png";
				$name = $allpos['name'];
			}
			else if($allpos['ftype'] == "http://id.southampton.ac.uk/location-feature/Shower")
			{
				$icon = self::$iconpath."Offices/shower.png";
				$name = $allpos['label'];
			}
			else if(substr($uri, 0, 33) == "http://id.southampton.ac.uk/room/")
			{
				$icon = self::$iconpath."Education/computers.png";
				$name = $allpos['name'];
				$computer = "true";
			}
			else
			{
				$icon = "";
				$name = $allpos['name'];
			}
		}
		else
		{
			$icon = $allpos['icon'];
			$name = $allpos['name'];
		}
		$icon = self::convertIcon($icon);

		$page = sparql_get(self::$endpoint, "
		PREFIX foaf: <http://xmlns.com/foaf/0.1/>

		SELECT DISTINCT ?page WHERE {
			<$uri> foaf:page ?page .
		} ORDER BY ?page
		");

		//if(count($page) > 0)
		echo "<h2><img class='icon' src='".($icon!=""?$icon:"img/blackness.png")."' />".$name;
		if(preg_match('/http:\/\/id\.southampton\.ac\.uk\/.*/', $uri) && !$wifi)
		{
			//print_r($page[0]);
			//echo "<a class='odl' href='".$page[0]['page']."'>Visit page</a>";
			echo "<a class='odl' href='".$uri."'>Visit page</a>";
		}
		echo "</h2>";

		$allpos = sparql_get(self::$endpoint, "
		PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
		PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
		PREFIX spacerel: <http://data.ordnancesurvey.co.uk/ontology/spatialrelations/>
		PREFIX org: <http://www.w3.org/ns/org#>
		PREFIX gr: <http://purl.org/goodrelations/v1#>

		SELECT DISTINCT * WHERE {
			<$uri> gr:hasOpeningHoursSpecification ?time .
			OPTIONAL { ?time gr:validFrom ?start . }
			OPTIONAL { ?time gr:validThrough ?end . }
			?time gr:hasOpeningHoursDayOfWeek ?day .
			?time gr:opens ?opens .
			?time gr:closes ?closes .
		} ORDER BY ?start ?end ?day ?opens ?closes
		");

		self::processOpeningTimes($allpos);

		if($computer)
		{
			$allpos = sparql_get(self::$endpoint, "
		PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
		PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
		PREFIX spacerel: <http://data.ordnancesurvey.co.uk/ontology/spatialrelations/>
		PREFIX org: <http://www.w3.org/ns/org#>
		PREFIX gr: <http://purl.org/goodrelations/v1#>

		SELECT DISTINCT ?label WHERE {
			<$uri> <http://purl.org/openorg/hasFeature> ?f .
			?f a ?ft .
			?ft rdfs:label ?label .
			FILTER ( REGEX(?label, '^(WORKSTATION|SOFTWARE) -') )
		} ORDER BY ?label
			");
		}
		else if($wifi)
			$allpos = array(array('label' => 'Wi-Fi Access'));
		else
		{
			$allpos = sparql_get(self::$endpoint, "
		PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
		PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
		PREFIX spacerel: <http://data.ordnancesurvey.co.uk/ontology/spatialrelations/>
		PREFIX org: <http://www.w3.org/ns/org#>
		PREFIX gr: <http://purl.org/goodrelations/v1#>

		SELECT DISTINCT ?label WHERE {
			?o gr:availableAtOrFrom <$uri> .
			?o gr:includes ?ps .
			?ps a gr:ProductOrServicesSomeInstancesPlaceholder .
			?ps rdfs:label ?label .
		} ORDER BY ?label 
			");
		}

		if(count($allpos) == 0)
		{
			$allpos = sparql_get(self::$endpoint, "
			PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
			PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
			PREFIX spacerel: <http://data.ordnancesurvey.co.uk/ontology/spatialrelations/>
			PREFIX org: <http://www.w3.org/ns/org#>
			PREFIX gr: <http://purl.org/goodrelations/v1#>

			SELECT DISTINCT ?label WHERE {
				?o gr:availableAtOrFrom <$uri> .
				?o gr:includes ?ps .
				?ps a gr:ProductOrService .
				?ps rdfs:label ?label .
			} ORDER BY ?label 
			");
		}
		if(count($allpos) > 0)
		{
			echo "<h3> Offers: (click to show on map) </h3>";
			echo "<ul class='offers'>"; 
			foreach($allpos as $point) {
				echo "<li onclick=\"setInputBox('^".str_replace(array("(", ")"), array("\(", "\)"), $point['label'])."$'); updateFunc();\">".$point['label']."</li>";
			}
			echo "</ul>";
		}

		if($wifi)
		{
			$allpos = sparql_get(self::$endpoint, "
			PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
			PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
			PREFIX spacerel: <http://data.ordnancesurvey.co.uk/ontology/spatialrelations/>
			PREFIX org: <http://www.w3.org/ns/org#>
			PREFIX gr: <http://purl.org/goodrelations/v1#>

			SELECT DISTINCT ?r ?label WHERE {
		          ?r <http://purl.org/openorg/hasFeature> ?f .
        		  ?f a <http://id.southampton.ac.uk/syllabus/feature/RSC-_WIRELESS_NETWORK> .
        		  ?r spacerel:within <$uri> .
			  ?r <http://www.w3.org/2004/02/skos/core#notation> ?label
			} ORDER BY ?label
			");
			echo "<h3> Rooms with known coverage </h3>";
			echo "<ul class='offers'>"; 
			foreach($allpos as $room) {
				$labelparts = explode('-', $room['label']);
				echo "<li style='background-color:white'><a href='".$room['r']."'>".$labelparts[1]."</a></li>";
			}
			echo "</ul>";
		}

		if(preg_match('/http:\/\/id\.southampton\.ac\.uk\/point-of-service\/parking-(.*)/', $uri, $matches))
		{
			echo "<iframe style='border:none' src='parking.php?uri=".$_GET['uri']."' />";
			echo "</div>";
			die();
		}

		if(substr($uri, 0, strlen('http://id.sown.org.uk/')) == 'http://id.sown.org.uk/')
			self::processSownURI($uri);
		echo "</div>";
		return true;
	}

	static function routestyle($code)
	{
		$color['U1'] = array(  0, 142, 207);
		$color['U2'] = array(226,   2,  20);
		$color['U6'] = array(246, 166,  24);
		$color['U9'] = array(232,  84, 147);
		if(isset($color[$code]))
		{
			return "style='background-color:#".str_pad(dechex($color[$code][0]), 2, '0').str_pad(dechex($color[$code][1]), 2, '0').str_pad(dechex($color[$code][2]), 2, '0').";' ";
		}
	}
}
?>
