<?
include_once "inc/sparqllib.php";
include_once "inc/arc/ARC2.php";
include_once "inc/Graphite.php";

class IsleOfWightDataSource extends DataSource
{
	static $endpoint = 'http://sparql.data.southampton.ac.uk';

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
		$graph = new Graphite();
		$graph->load('file:/home/opendatamap/isleofwight.rdf');
		$graph->load('http://opendatamap.ecs.soton.ac.uk/dev/colin/mymap/crwilliams/isleofwight');
		$points = array();
		foreach($graph->allOfType('http://purl.org/goodrelations/v1#LocationOfSalesOrServiceProvisioning') as $point)
		{
			$p['id'] = $point->toString();
			$p['label'] = ($point->getString('rdfs:label'));
			$p['lat'] = ($point->getString('geo:lat'));
			$p['long'] = ($point->getString('geo:long'));
			$p['icon'] = ($point->getString('http://purl.org/openorg/mapIcon'));
			$points[] = $p;
		}
		return $points;
	}

	static function getEntries($q, $cats)
	{
		$q = str_replace("\\", "\\\\\\\\\\\\\\", trim($q));
		
		$pos = array();
		$label = array();
		$type = array();
		$url = array();
		$icon = array();
		self::createPointOfServiceEntries($pos, $label, $type, $url, $icon, $q, $cats);
		return array($pos, $label, $type, $url, $icon);
	}

	static function createPointOfServiceEntries(&$pos, &$label, &$type, &$url, &$icon, $q, $cats)
	{
		$data = self::getPointsOfService($q);
		foreach($data as $point) {
			if(!self::visibleCategory($point['icon'], $cats))
				continue;
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

	static function getPointsOfService($q)
	{
		//if($q == '')
		//	$filter = '';
		//else
		//	$filter = "FILTER ( REGEX( ?label, '$q', 'i') || REGEX( ?poslabel, '$q', 'i') )";
		$graph = new Graphite();
		$graph->load('file:/home/opendatamap/isleofwight.rdf');
		$points = array();
		foreach($graph->allOfType('http://purl.org/goodrelations/v1#LocationOfSalesOrServiceProvisioning') as $point)
		{
			foreach($point->all('-http://purl.org/goodrelations/v1#availableAtOrFrom') as $offering)
			{
				foreach($offering->all('http://purl.org/goodrelations/v1#includes') as $include)
				{
					$p['label'] = ($include->getString('rdfs:label'));
					$p['pos'] = $point->toString();
					$p['poslabel'] = ($point->getString('rdfs:label'));
					$p['icon'] = ($point->getString('http://purl.org/openorg/mapIcon'));
					if($q == '' || preg_match(strtolower('/'.$q.'/'), strtolower($p['label'])) || preg_match(strtolower('/'.$q.'/'), strtolower($p['poslabel'])))
						$points[] = $p;
				}
			}
		}
		return $points;
	}

	static function visibleCategory($icon, $cats)
	{
		global $iconcats;
		if($iconcats == null) include_once "inc/categories.php";
		return in_cat($iconcats, $icon, $cats);
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
		echo "<h2><img class='icon' src='http://opendatamap.ecs.soton.ac.uk/resources/busicon.php?r=".implode('/', $codes)."' />".$allpos[0]['name'];
		echo "<a class='odl' href='$uri'>Visit&nbsp;page</a></h2>";
		echo "<h3> Served by: (click to filter) </h3>";
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
		return sparql_get(self::$endpoint, "
		PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
		PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
		PREFIX spacerel: <http://data.ordnancesurvey.co.uk/ontology/spatialrelations/>
		PREFIX org: <http://www.w3.org/ns/org#>

		SELECT DISTINCT ?name ?icon ?type WHERE {
		    OPTIONAL { <$uri> rdfs:label ?name . }
		    OPTIONAL { <$uri> <http://purl.org/openorg/mapIcon> ?icon . }
		    OPTIONAL { <$uri> <http://purl.org/openorg/hasFeature> ?feature . 
		        OPTIONAL { ?feature a ?type . }
		        OPTIONAL { ?feature rdfs:label ?label . }
		    }
		}
		");
	}

	static function processSouthamptonURI($uri)
	{
		$graph = new Graphite();
		$graph->load('file:/home/opendatamap/isleofwight.rdf');
		$res = $graph->resource($uri);

		echo "<div id='content'>";
		
		$name = $res->getString('rdfs:label');
		$icon = $res->getString('http://purl.org/openorg/mapIcon');

		echo "<h2><img class='icon' src='".($icon!=""?$icon:"img/blackness.png")."' />".$name;
		echo "</h2>";

		$allpos = array();
		foreach($res->all('-http://purl.org/goodrelations/v1#availableAtOrFrom') as $offering)
		{
			foreach($offering->all('http://purl.org/goodrelations/v1#includes') as $include)
			{
				if($include->isType('http://purl.org/goodrelations/v1#ProductOrServicesSomeInstancesPlaceholder'))
				{
					$p['label'] = ($include->getString('rdfs:label'));
					$allpos[] = $p;
				}
			}
		}
		if(count($allpos) == 0)
		{
			foreach($res->all('-http://purl.org/goodrelations/v1#availableAtOrFrom') as $offering)
			{
				foreach($offering->all('http://purl.org/goodrelations/v1#includes') as $include)
				{
					if($include->isType('http://purl.org/goodrelations/v1#ProductOrService'))
					{
						$p['label'] = ($include->getString('rdfs:label'));
						$allpos[] = $p;
					}
				}
			}
		}
		/*
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
		*/

		self::processOffers($allpos);

		$allopen = array();
		foreach($res->all('http://purl.org/goodrelations/v1#hasOpeningHoursSpecification') as $time)
		{
			if($time->has('http://purl.org/goodrelations/v1#validFrom'))
				$open['start'] = $time->getString('http://purl.org/goodrelations/v1#validFrom');
			else
				$open['start'] = null;
			if($time->has('http://purl.org/goodrelations/v1#validThrough'))
				$open['end'] = $time->getString('http://purl.org/goodrelations/v1#validThrough');
			else
				$open['end'] = null;
			$open['day'] = $time->getString('http://purl.org/goodrelations/v1#hasOpeningHoursDayOfWeek');
			$open['opens'] = $time->getString('http://purl.org/goodrelations/v1#opens');
			$open['closes'] = $time->getString('http://purl.org/goodrelations/v1#closes');
			$allopen[] = $open;
		}
		/*
		$allopen = sparql_get(self::$endpoint, "
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
		*/

		self::processOpeningTimes($allopen);

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
