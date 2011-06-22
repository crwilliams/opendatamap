<?
include_once "inc/sparqllib.php";

class OxfordDataSource extends DataSource
{
	static $endpoint = "http://oxpoints.oucs.ox.ac.uk/sparql";

	static function getAll()
	{
		$tpoints = sparql_get(self::$endpoint, "
	PREFIX oxp: <http://ns.ox.ac.uk/namespace/oxpoints/2009/02/owl#>
	PREFIX foaf: <http://xmlns.com/foaf/0.1/>
	PREFIX dc: <http://purl.org/dc/elements/1.1/>
	PREFIX dct: <http://purl.org/dc/terms/>
	PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>

	SELECT ?id ?lat ?long ?label ?type WHERE {
	  ?id a ?type .
	  ?id dc:title ?label .
	    ?id oxp:occupies ?c .
	    ?c geo:lat ?lat .
	    ?c geo:long ?long .
	}
		");
	  //?id foaf:logo ?icon .
		$points = array();
		foreach($tpoints as $point)
		{
			//if($point['icon'] == '')
			$point['icon'] = self::getIcon($point);
			$points[] = $point;
		}
		return $points;
	}
	
	static function getEntries($q, $cats)
	{	
		$pos = array();
		$label = array();
		$type = array();
		$url = array();
		$icon = array();
		$data = self::getOxPoints($q);
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
				$type[$point['poslabel']] = "workstation";
				$url[$point['poslabel']] = $point['pos'];
				$icon[$point['poslabel']] = $point['icon'];
			}
		}
		return array($pos, $label, $type, $url, $icon);
	}
	
	static function getDataSets(){
		return array(array('name' => 'OxPoints', 'uri' => 'http://www.oucs.ox.ac.uk/oxpoints/', 'l' => 'http://www.opendefinition.org/licenses/cc-zero'));
	}

	static function processURI($uri){
		if(substr($uri, 0, strlen('http://oxpoints.oucs.ox.ac.uk/id/')) == 'http://oxpoints.oucs.ox.ac.uk/id/')
		{
			$info = sparql_get(self::$endpoint, "
	PREFIX oxp: <http://ns.ox.ac.uk/namespace/oxpoints/2009/02/owl#>
	PREFIX foaf: <http://xmlns.com/foaf/0.1/>
	PREFIX dc: <http://purl.org/dc/elements/1.1/>
	PREFIX dct: <http://purl.org/dc/terms/>
	PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>

	SELECT ?label ?type ?addr ?street ?postcode ?locality WHERE {
	  <$uri> a ?type .
	  <$uri> dc:title ?label .
	  <$uri> <http://www.w3.org/2006/vcard/ns#adr> ?addr .
	  ?addr <http://www.w3.org/2006/vcard/ns#street-address> ?street .
	  ?addr <http://www.w3.org/2006/vcard/ns#postal-code> ?postcode .
	  ?addr <http://www.w3.org/2006/vcard/ns#locality> ?locality .
	}
			");
			echo "<h2><img class='icon' src='".self::getIcon($info[0])."' />".$info[0]['label']."<h2>";
			$info = sparql_get(self::$endpoint, "
	PREFIX oxp: <http://ns.ox.ac.uk/namespace/oxpoints/2009/02/owl#>
	PREFIX foaf: <http://xmlns.com/foaf/0.1/>
	PREFIX dc: <http://purl.org/dc/elements/1.1/>
	PREFIX dct: <http://purl.org/dc/terms/>
	PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>

	SELECT ?label ?type ?addr ?street ?postcode ?locality WHERE {
	  <$uri> a ?type .
	  <$uri> dc:title ?label .
	  <$uri> <http://www.w3.org/2006/vcard/ns#adr> ?addr .
	  ?addr <http://www.w3.org/2006/vcard/ns#street-address> ?street .
	  ?addr <http://www.w3.org/2006/vcard/ns#postal-code> ?postcode .
	  ?addr <http://www.w3.org/2006/vcard/ns#locality> ?locality .
	}
	");
			echo $info[0]['street'].'<br/>';
			echo $info[0]['locality'].'<br/>';
			echo $info[0]['postcode'];
			return true;
		}
	}

	static function getOxPoints($q)
	{
		$tpoints = sparql_get(self::$endpoint, "
	PREFIX oxp: <http://ns.ox.ac.uk/namespace/oxpoints/2009/02/owl#>
	PREFIX foaf: <http://xmlns.com/foaf/0.1/>
	PREFIX dc: <http://purl.org/dc/elements/1.1/>
	PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>

	SELECT ?pos ?poslabel ?type WHERE {
	  ?pos a ?type .
	  ?pos dc:title ?label .
	    ?pos oxp:occupies ?c .
	    ?c geo:lat ?lat .
	    ?c geo:long ?long .
	    ?pos dc:title ?poslabel .
	}
		");
	  //OPTIONAL { ?pos <http://www.w3.org/2004/02/skos/core#hiddenLabel> ?hiddenlabel . }
		$points = array();
		foreach($tpoints as $point)
		{
			if(!preg_match('/'.$q.'/i', $point['label']) && !preg_match('/'.$q.'/i', $point['poslabel']))// && !preg_match('/'.$q.'/i', $point['hiddenlabel']))
				continue;
			$point['icon'] = self::getIcon($point);
			$point['label'] = 'point';
			$points[] = $point;
		}
		return $points;
	}

	static function visibleCategory($icon, $cats)
	{
		global $iconcats;
		if($iconcats == null) include_once "inc/categories.php";
		return in_cat($iconcats, $icon, $cats);
	}

	static function getIcon($point)
	{
		switch($point['type'])
		{
			case 'http://ns.ox.ac.uk/namespace/oxpoints/2009/02/owl#Building':
			case 'http://ns.ox.ac.uk/namespace/oxpoints/2009/02/owl#Room':
			case 'http://ns.ox.ac.uk/namespace/oxpoints/2009/02/owl#Site':
				return "http://opendatamap.ecs.soton.ac.uk/img/icon/university.png";
			case 'http://ns.ox.ac.uk/namespace/oxpoints/2009/02/owl#Hall':
			case 'http://ns.ox.ac.uk/namespace/oxpoints/2009/02/owl#College':
				return "http://opendatamap.ecs.soton.ac.uk/img/icon/university.png";
			case 'http://ns.ox.ac.uk/namespace/oxpoints/2009/02/owl#Department':
			case 'http://ns.ox.ac.uk/namespace/oxpoints/2009/02/owl#Unit':
			case 'http://ns.ox.ac.uk/namespace/oxpoints/2009/02/owl#Division':
				return "http://opendatamap.ecs.soton.ac.uk/img/icon/school.png";
			case 'http://ns.ox.ac.uk/namespace/oxpoints/2009/02/owl#StudentGroup':
				return "http://opendatamap.ecs.soton.ac.uk/img/icon/library-uni.png";
			case 'http://ns.ox.ac.uk/namespace/oxpoints/2009/02/owl#Carpark':
				return "http://opendatamap.ecs.soton.ac.uk/img/icon/parking.png";
			case 'http://ns.ox.ac.uk/namespace/oxpoints/2009/02/owl#OpenSpace':
				return "http://opendatamap.ecs.soton.ac.uk/img/icon/park-urban.png";
			case 'http://ns.ox.ac.uk/namespace/oxpoints/2009/02/owl#Library':
			case 'http://ns.ox.ac.uk/namespace/oxpoints/2009/02/owl#SubLibrary':
				return "http://opendatamap.ecs.soton.ac.uk/img/icon/library.png";
			case 'http://ns.ox.ac.uk/namespace/oxpoints/2009/02/owl#Museum':
				return "http://opendatamap.ecs.soton.ac.uk/img/icon/museum-historical.png";
		}
	}
}
?>
