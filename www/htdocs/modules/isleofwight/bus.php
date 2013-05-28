<?
include_once "inc/sparqllib.php";

class IsleofwightBusDataSource extends DataSource
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
		self::createBusEntries($pos, $label, $type, $url, $icon, $q, $cats);
		return array($pos, $label, $type, $url, $icon);
	}

	static function getDataSets()
	{
		return array(array('name' => 'data.gov.uk', 'uri' => 'http://data.gov.uk/', 'l' => 'http://reference.data.gov.uk/id/open-government-licence'));
	}

	static function getDataSetExtras()
	{
		return array("Contains Ordnance Survey data &copy; Crown copyright and database right 2011.  Contains Royal Mail data &copy; Royal Mail copyright and database right 2011.");
	}

	static function getAll()
	{
		//$q = "SELECT uri AS id, lat, lng AS `long`, label, icon FROM points";
		$q = "SELECT concat('bus-stop:', AtcoCode) AS id, latitude AS lat, longitude AS `long`, name AS label, 'http://opendatamap.ecs.soton.ac.uk/img/icon/Transportation/bus.png' AS icon FROM `bus` WHERE latitude > 50.5746 AND latitude < 50.7675 AND longitude > - 1.5877 AND longitude < - 1.0627 AND longitude-latitude/3 > -18.41";
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

	static function getBusStops($q, $cats)
	{
		if(!in_array('Transportation', $cats))
			return array();
		
		if($q == '')
		{
			//$q = "SELECT poslabel, label, uri AS pos, icon FROM matches WHERE type = '$type' AND category IN ($cats)";
			$q = "SELECT name AS poslabel, '' AS label, concat('bus-stop:', AtcoCode) AS pos, 'http://opendatamap.ecs.soton.ac.uk/img/icon/Transportation/bus.png' AS icon FROM `bus` WHERE latitude > 50.5746 AND latitude < 50.7675 AND longitude > - 1.5877 AND longitude < - 1.0627 AND longitude-latitude/3 > -18.41";
		}
		else
		{
			$q = mysql_escape_string($q);
			//$q = "SELECT poslabel, label, uri AS pos, icon FROM matches WHERE type = '$type' AND (poslabel REGEXP '$q' OR label REGEXP '$q') AND category IN ($cats)";
			$q = "SELECT name AS poslabel, '' AS label, concat('bus-stop:', AtcoCode) AS pos, 'http://opendatamap.ecs.soton.ac.uk/img/icon/Transportation/bus.png' AS icon FROM `bus` WHERE latitude > 50.5746 AND latitude < 50.7675 AND longitude > - 1.5877 AND longitude < - 1.0627 AND longitude-latitude/3 > -18.41 AND (name REGEXP '$q')";
		}
		return self::perform_query($q);
	}

	// Process bus data
	static function createBusEntries(&$pos, &$label, &$type, &$url, &$icon, $q, $cats)
	{
		$data = self::getBusStops($q, $cats);
		foreach($data as $point) {
//			if(!self::visibleCategory($point['icon'], $cats))
//				continue;
			$point['icon'] = str_replace("http://google-maps-icons.googlecode.com/files/bus.png", "http://opendatamap.ecs.soton.ac.uk/resources/busicon/", $point['icon']);
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

	static function processURI($uri)
	{
		if(substr($uri, 0, strlen('bus-stop:')) == 'bus-stop:')
			return self::processBusURI(substr($uri, strlen('bus-stop:')));
		else
			return false;
	}

	static function processBusURI($uri)
	{	
		include_once('/home/opendatamap/mysql.inc.php');
		$uri = mysql_real_escape_string((string)$uri);
		$q = "SELECT name, code2 as naptan FROM transport WHERE Code = '$uri' AND Type = 'bus'";
		$data = self::perform_query($q);
		echo "<h2><img class='icon' src='http://opendatamap.ecs.soton.ac.uk/img/icon/Transportation/bus.png' />".$data[0]['name'].'</h2>';
		echo '<a href="http://www.nextbuses.mobi/WebView/BusStopSearch/BusStopSearchResults/'.$data[0]['naptan'].'">Live bus times</a><br/>';
		//echo '<iframe src="http://www.nextbuses.mobi/WebView/BusStopSearch/BusStopSearchResults/'.$data[0]['naptan'].'" />';
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
			echo "<h3> Offers: (click to filter) </h3>";
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

		if(count($allpos) > 0)
		{
			//echo "<div id='openings'>";
			//echo "<h3>Opening detail:</h3>";
			foreach($allpos as $point)
			{
				if ($point['start'] != '')
				{
					$start = strtotime($point['start']);
					$start = date('d/m/Y',$start);
				}
				else 
				{
					$start = '';
				}
				if ($point['end'] != '')
				{
					$end = strtotime($point['end']);
					$end = date('d/m/Y',$end);
				}
				else
				{
					$end = '';
				}
				$open = strtotime($point['opens']);
				$open = date('H:i',$open);
				$close = strtotime($point['closes']);
				$close = date('H:i',$close);
				$ot[$start."-".$end][$point['day']][] = $open."-".$close;
			}

			$weekday = array('Monday', 'Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday');
			//echo "<table id='openings' style='font-size:0.8em'>";
			//echo "<tr>";
			foreach($weekday as $day)
			{
				$short_day = substr($day, 0,3); 
				//echo "<th>".$short_day."</th>";
			}
			//echo "<th>Valid Dates</th>";
			//echo "</tr>";

			foreach($ot as $valid => $otv)
			{
				list($from, $to) = explode('-',$valid);
				$now = mktime();
				if ($from == '')
				{
					$from = $now - 86400;
				}
				else
				{
					$from = mktime(0,0,0,substr($from,3,2),substr($from,0,2),substr($from,7,4));
				}
				if ($to == '')
				{
					$to = $now+86400;
				}
				else
				{
					$to = mktime(0,0,0,substr($to,3,2),substr($to,0,2),substr($to,7,4));
				} 

				if ( $to < $now )
				{
					continue;
				}
				if ($from > $now + (60*60*24*30))
				{ 
					continue;
				}
				$current = ($from <=  $now )&&( $to >= $now);
				if ($current)
				{ 
					//echo "<tr class='current'>"; //start of row
					foreach($weekday as $day)
					{
						//echo "<td width=\"350\">";
						if(array_key_exists('http://purl.org/goodrelations/v1#'.$day, $otv))
						{
							foreach($otv['http://purl.org/goodrelations/v1#'.$day] as $dot)
							{
								if($dot == '00:00-00:00')
									$dot = '24 hour';
								//echo $dot."<br/>";
								if($day == date('l', $now))
								{
									$todayopening[] = "<li>$dot</li>";
								}
							}
						}
						//echo "</td>";
					}
				}
				else
				{
					//echo "<tr>";
				}
				//echo "<td>".$valid."</td>";
				//echo "</tr>";
			}
			//echo "</table>";
			//echo "</div>";

			if($todayopening != null)
			{
				echo "<div id='todayopenings'>";
				echo "<h3>Today's opening hours:</h3>";
				echo "<ul style='padding-top:8px;'>";
				foreach($todayopening as $opening)
				{
					echo $opening;
				}
				echo "</ul>";
				echo "</div>";
			}
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
