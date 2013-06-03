<?
include_once "inc/sparqllib.php";
include_once "inc/icons.php";
include_once('/home/opendatamap/mysql-pdo.inc.php');

class SouthamptonDataSource extends DataSource
{
	static $endpoint = 'http://sparql.data.southampton.ac.uk';
	static $handlers = array(
		'http://id.southampton.ac.uk/bus-stop/'					=>       'processSouthamptonBusStopURI',
		'http://id.southampton.ac.uk/'						=>		'processSouthamptonURI',
		'http://opendatamap.ecs.soton.ac.uk/mymap/hcn1g12/eduroamwifiaccess#'	=>		'processSouthamptonURI',
		'http://id.sown.org.uk/'						=>		       'processSownURI',
	);

	static function getEntries($q, $cats)
	{
		$q = trim($q);
		
		$pos = array();
		$label = array();
		$type = array();
		$url = array();
		$icon = array();
		
		self::_createEntries($pos, $label, $type, $url, $icon, $q, $cats);
		self::createBuildingEntries($pos, $label, $type, $url, $icon, $q, $cats);
		self::createSiteEntries($pos, $label, $type, $url, $icon, $q, $cats);
		
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
		$ds[] = array('name' => 'WiFi', 'uri' => 'http://id.southampton.ac.uk/dataset/wifi', 'l' => 'http://reference.data.gov.uk/id/open-government-licence');
		$ds[] = array('name' => 'Ordnance Survey Linked Data', 'uri' => 'http://data.ordnancesurvey.co.uk', 'l' => 'http://reference.data.gov.uk/id/open-government-licence');
		return $ds;
	}

	static function getDataSetExtras()
	{
		return array("Contains Ordnance Survey data &copy; Crown copyright and database right 2011.");
	}

	static function getAll()
	{
		$q = 'SELECT uri AS id, lat, lng AS `long`, label, icon FROM points';
		return self::_query($q);
	}
	
	static function getPointInfo($uri)
	{
		$points = static::getAll();
		foreach($points as $point)
		{
			if($point['id'] == $uri)
			{
				return $point;
			}
		}
		return array();
	}

	static function _query($q, $p = array())
	{
		global $dbh;
		$stmt = $dbh->prepare($q);
		$stmt->execute($p);
		return $stmt->fetchAll();
	}

	private static function _getMatches($q, $cats)
	{
		$safecats = array();
		foreach($cats as $cat)
		{
			$safecats[] = "'".mysql_escape_string($cat)."'";
		}
		$cats = implode(',', $safecats);
		
		if($q == '')
		{
			$q = "SELECT poslabel, label, uri AS pos, icon, type FROM matches WHERE category IN ($cats)";
		}
		else
		{
			$q = mysql_escape_string($q);
			$q = "SELECT poslabel, label, uri AS pos, icon, type FROM matches WHERE (poslabel REGEXP '$q' OR label REGEXP '$q') AND category IN ($cats)";
		}
		return self::_query($q);
	}

	static function getAllBuildings()
	{
		$q = 'SELECT uri, name, outline, lat, lng, num FROM places WHERE type = "building"';
		return self::_query($q);
	}
	
	static function getBuildings($name, $num)
	{
		if(trim($name) == '')
		{
			$q = 'SELECT uri, name, num FROM places WHERE type = "building"';
			return self::_query($q);
		}
		else if(trim($num) == '')
		{
			$q = 'SELECT uri, name, num FROM places WHERE type = "building" AND (name REGEXP ?)';
			$p = array($name);
			return self::_query($q, $p);
		}
		else
		{
			$q = 'SELECT uri, name, num FROM places WHERE type = "building" AND (name REGEXP ? OR num REGEXP ?)';
			$p = array($name, $num);
			return self::_query($q, $p);
		}
	}
	
	static function getAllSites()
	{
		$q = 'SELECT uri, name, outline FROM places WHERE type = "site"';
		return self::_query($q);
	}

	static function getSites($str)
	{
		if(trim($str) == '')
		{
			$q = 'SELECT uri, name FROM places WHERE type = "site"';
			return self::_query($q);
		}
		else
		{
			$q = 'SELECT uri, name FROM places WHERE type = "site" AND name REGEXP ?';
			$p = array($str);
			return self::_query($q, $p);
		}
	}

	private static function _createEntries(&$pos, &$label, &$type, &$url, &$icon, $q, $cats)
	{
		$data = self::_getMatches($q, $cats);
		$seats = self::_getSeats();
		
		foreach($data as $point) {
			$pos[$point['pos']] ++;
			if(preg_match('/'.$q.'/i', $point['label']))
			{
				$label[$point['label']] ++;
				$type[$point['label']] = 'offering';
				
				if($point['type'] == 'bus-route')
				{
					$type[$point['label']] = 'bus-stop';
				}
			}
			if(preg_match('/'.$q.'/i', $point['poslabel']))
			{
				if($point['type'] == 'workstation')
				{
					$point['poslabel'] .= " (".$seats[$point['pos']]['freeseats']." free)";
				}
				
				$label[$point['poslabel']] += 10;
				$type[$point['poslabel']] = $point['type'];
				$url[$point['poslabel']] = $point['pos'];
				$icon[$point['poslabel']] = $point['icon'];
				
				if($point['type'] == 'bus-stop')
				{
					$routes[$point['poslabel']][] = $point['label'];
					$icon[$point['poslabel']] = $point['icon'].'?r='.implode('/', $routes[$point['poslabel']]);
				}
			}
		}
	}

	// Process building data
	static function createBuildingEntries(&$pos, &$label, &$type, &$url, &$icon, $q, $cats)
	{
		$qbd = trim(str_replace(array('building', 'buildin', 'buildi', 'build', 'buil', 'bui', 'bu', 'b'), '', strtolower($q)));
		$data = self::getBuildings($q, $qbd);
		foreach($data as $point) {
			$pos[$point['uri']] += 100;
			if(preg_match('/'.$q.'/i', $point['name']))
			{
				if($point['num'] < 100)
					$label[$point['name']] += 1000;
				else
					$label[$point['name']] += 100;
				$type[$point['name']] = "building";
				$url[$point['name']] = $point['uri'];
				$icon[$point['name']] = 'http://opendatamap.ecs.soton.ac.uk/resources/numbericon/'.$point['num'];
			}
			if(preg_match('/'.$qbd.'/i', $point['num']))
			{
				if($point['num'] < 100)
					$label['Building '.$point['num']] += 1000;
				else
					$label['Building '.$point['num']] += 100;
				$type['Building '.$point['num']] = "building";
				$url['Building '.$point['num']] = $point['uri'];
				$icon['Building '.$point['num']] = 'http://opendatamap.ecs.soton.ac.uk/resources/numbericon/'.$point['num'];
			}
		}
	}

	// Process site data
	static function createSiteEntries(&$pos, &$label, &$type, &$url, &$icon, $q, $cats)
	{
		$data = self::getSites($q);
		foreach($data as $point) {
			$pos[$point['uri']] += 100000;
			$label[$point['name']] += 100000;
			if(preg_match('/Campus/i', $point['name']))
			{
				$pos[$point['uri']] += 100000;
				$label[$point['name']] += 100000;
			}
			$type[$point['name']] = "site";
			$url[$point['name']] = $point['uri'];
			$icon[$point['name']] = 'http://opendatamap.ecs.soton.ac.uk/resources/numbericon/'.substr($point['name'], 0, 1);
		}
	}
	
	static function processURI($uri)
	{
		foreach(self::$handlers as $prefix => $handler)
		{
			if(substr($uri, 0, strlen($prefix)) == $prefix)
				return self::$handler($uri);
		}
		return false;
	}
	
	static function processSownURI($uri)
	{
		return self::processSouthamptonURI($uri);
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
		echo "<h2><img class='icon' src='http://opendatamap.ecs.soton.ac.uk/resources/busicon/".implode('+', reduceBusCodes($codes))."' />".$allpos['name'];
		echo "<a class='odl' href='$uri'>Visit&nbsp;page</a></h2>";
		echo "<h3> Served by: (click to show on map) </h3>";
		echo "<ul class='offers'>"; 
		foreach($allbus as $code) {
			echo "<li ".self::routestyle($code['code'])."onclick=\"window.searchResults.setInputBox('".str_replace(array("(", ")"), array("\(", "\)"), $code['code'])."', true); window.searchResults.updateFunc();\">".$code['code']."</li>";
		}
		echo "</ul>";
		echo "<iframe style='border:none' src='bus.php?uri=".$uri."' />";
		return true;
	}

	static function getURIInfo($uri)
	{
		$q = "SELECT poslabel AS name, icon, type, label FROM matches WHERE uri = ?";
		$p = array($uri);
		$data = self::_query($q, $p);
		return $data[0];
	}

	static function processSouthamptonURI($uri)
	{
		$res = false;
		if(substr($uri, strlen($uri)-12, 12) == '#residential')
		{
			$uri = substr($uri, 0, strlen($uri)-12);
			$res = true;
		}
		$allpos = self::getURIInfo($uri);
		if(substr($uri, strlen($uri)-5, 5) == '#wifi')
		{
			$uri = substr($uri, 0, strlen($uri)-5);
		}
		$type = $allpos['type'];
		echo "<div id='content'>";
		if($allpos['icon'] == '')
		{
			if($res)
			{
				$icon = self::$iconpath."Restaurants-and-Hotels/lodging_0star.png";
				$name = $allpos['name'];
			}
			else if($allpos['type'] == "shower")
			{
				$icon = self::$iconpath."Offices/shower.png";
				$name = $allpos['label'];
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

		$rating = sparql_get(self::$endpoint, "
		PREFIX oo: <http://purl.org/openorg/>

		SELECT DISTINCT ?ratingKey ?ratingValue ?page WHERE {
			<$uri> oo:ukfhrsRatingValue ?ratingValue .
			<$uri> oo:ukfhrsRatingKey ?ratingKey .
			<$uri> oo:ukfhrsPage ?page .
		} ORDER BY ?rating
		");

		echo "<h2><img class='icon' src='".($icon!=""?$icon:"img/blackness.png")."' />".$name;
		if($type == 'event')
		{
			$eventdata = sparql_get(self::$endpoint, "
		PREFIX foaf: <http://xmlns.com/foaf/0.1/>
		PREFIX dct: <http://purl.org/dc/terms/>

		SELECT * WHERE {
			<$uri> dct:description ?d .
			OPTIONAL { <$uri> foaf:homepage ?h . }
		}
			");
			if(isset($eventdata[0]['h']))
			{
				echo "<a class='odl' href='".$eventdata[0]['h']."'>Visit page</a>";
			}
		}
		else if(preg_match('/http:\/\/id\.southampton\.ac\.uk\/.*/', $uri) && !$wifi)
		{
			echo "<a class='odl' href='".$uri."'>Visit page</a>";
		}
		echo "</h2>";

		if(count($rating) > 0)
		{
			echo "<a href='".$rating[0]['page']."'><img style='float:right' src='img/fhrs/small/72ppi/".strtolower($rating[0]['ratingKey']).".jpg' alt='Food hygiene rating: ".$rating[0]['ratingValue']."' title='Food hygiene rating: ".$rating[0]['ratingValue']."' /></a>";
		}

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

		if($type == 'workstation')
		{
			$allpos = sparql_get(self::$endpoint, "
		PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
		PREFIX spacerel: <http://data.ordnancesurvey.co.uk/ontology/spatialrelations/>
		PREFIX gr: <http://purl.org/goodrelations/v1#> 
		PREFIX oo: <http://purl.org/openorg/>

		SELECT DISTINCT ?label WHERE {
			<$uri> spacerel:within ?s .
			?s a <http://vocab.deri.ie/rooms#Room> .
			?s oo:hasFeature ?f .
			?f a ?ft .
			?ft rdfs:label ?label .
 			FILTER ( REGEX(?label, '^(WORKSTATION|SOFTWARE) -') )
		} ORDER BY ?poslabel
			");
			$seats = self::_getSeats($uri);
			echo "<h3> Workstations Available: </h3>";
			if($seats['freeseats'] == 0)
			{
				$seats['freeseats'] = 'None';
			}
			echo $seats['freeseats']." <span style='font-size:0.8em'>out of ".$seats['allseats']."</span>";
		}
		else if($type == 'wifi')
		{
			$allpos = array(array('label' => 'Wi-Fi Access'));
		}
		else if($type == 'event')
		{
			$eventtimes = sparql_get(self::$endpoint, "
		PREFIX foaf: <http://xmlns.com/foaf/0.1/>
		PREFIX dct: <http://purl.org/dc/terms/>

		SELECT ?s ?e WHERE {
			<$uri> <http://purl.org/NET/c4dm/event.owl#time> ?t .
			?t <http://purl.org/NET/c4dm/timeline.owl#start> ?s .
			OPTIONAL { ?t <http://purl.org/NET/c4dm/timeline.owl#end> ?e . }
		}
			");
			date_default_timezone_set('Europe/London');
			foreach($eventtimes as $eventtime)
			{
				$s = new DateTime($eventtime['s']);
				echo $s->format('D jS M Y H:i');
				if(isset($eventtime['e']))
				{
					echo ' - ';
					$e = new DateTime($eventtime['e']);
					if($s->format('D jS M Y') == $e->format('D jS M Y'))
					{
						echo $e->format('H:i');
					}
					else
					{
						echo $e->format('D jS M Y H:i');
					}
				}
				echo '<br />';
			}
			echo '<hr />';
			echo $eventdata[0]['d'];
		}
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
				echo "<li onclick=\"window.searchResults.setInputBox('".str_replace(array("(", ")"), array("\(", "\)"), $point['label'])."', true); window.searchResults.updateFunc();\">".$point['label']."</li>";
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
		$color['U1A'] = $color['U1'];
		$color['U1C'] = $color['U1'];
		$color['U1E'] = $color['U1'];
		$color['U2'] = array(226,   2,  20);
		$color['U2B'] = $color['U2'];
		$color['U2C'] = $color['U2'];
		$color['U6'] = array(246, 166,  24);
		$color['U6C'] = $color['U6'];
		$color['U6H'] = $color['U6'];
		$color['U9'] = array(232,  84, 147);
		if(isset($color[$code]))
		{
			return "style='background-color:#".str_pad(dechex($color[$code][0]), 2, '0').str_pad(dechex($color[$code][1]), 2, '0').str_pad(dechex($color[$code][2]), 2, '0').";' ";
		}
	}

	static function getFreeSeats($pos)
	{
		$freeseats = sparql_get(self::$endpoint, "
	        PREFIX soton: <http://id.southampton.ac.uk/ns/>

	        SELECT ?freeseats WHERE {
	          <$pos> soton:workstationFreeSeats ?freeseats .
	        }
		");
		return $freeseats[0]['freeseats'];
	}

	private static function _getSeats($pos = null)
	{
		if(is_null($pos))
		{
			$seats = sparql_get(self::$endpoint, "
	        	PREFIX soton: <http://id.southampton.ac.uk/ns/>
	
		        SELECT ?uri ?freeseats ?allseats WHERE {
		          ?uri soton:workstationFreeSeats ?freeseats .
		          ?uri soton:workstationSeats ?allseats .
		        }
			", '', 1);
			foreach($seats as $seat)
			{
				$res[$seat['uri']]['freeseats'] = $seat['freeseats'];
				$res[$seat['uri']]['allseats'] = $seat['allseats'];
			}
			return $res;
		}
		else
		{
			$seats = sparql_get(self::$endpoint, "
	        	PREFIX soton: <http://id.southampton.ac.uk/ns/>
	
		        SELECT ?freeseats ?allseats WHERE {
		          <$pos> soton:workstationFreeSeats ?freeseats .
		          <$pos> soton:workstationSeats ?allseats .
		        }
			", '', 1);
			return $seats[0];
		}
	}
}
?>
