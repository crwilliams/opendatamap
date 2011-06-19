<?
include_once "inc/sparqllib.php";

class OxfordDataSource extends DataSource
{
	$endpoint = "http://oxpoints.oucs.ox.ac.uk/sparql";

	function getAllOxPoints()
	{
		$tpoints = sparql_get($endpoint, "
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
			switch($point['type'])
			{
				case 'http://ns.ox.ac.uk/namespace/oxpoints/2009/02/owl#Building':
				case 'http://ns.ox.ac.uk/namespace/oxpoints/2009/02/owl#Room':
				case 'http://ns.ox.ac.uk/namespace/oxpoints/2009/02/owl#Site':
					$point['icon'] = "http://opendatamap.ecs.soton.ac.uk/img/icon/university.png";
					break;
				case 'http://ns.ox.ac.uk/namespace/oxpoints/2009/02/owl#Hall':
				case 'http://ns.ox.ac.uk/namespace/oxpoints/2009/02/owl#College':
					$point['icon'] = "http://opendatamap.ecs.soton.ac.uk/img/icon/university.png";
					break;
				case 'http://ns.ox.ac.uk/namespace/oxpoints/2009/02/owl#Department':
				case 'http://ns.ox.ac.uk/namespace/oxpoints/2009/02/owl#Unit':
				case 'http://ns.ox.ac.uk/namespace/oxpoints/2009/02/owl#Division':
					$point['icon'] = "http://opendatamap.ecs.soton.ac.uk/img/icon/school.png";
					break;
				case 'http://ns.ox.ac.uk/namespace/oxpoints/2009/02/owl#StudentGroup':
					$point['icon'] = "http://opendatamap.ecs.soton.ac.uk/img/icon/library-uni.png";
					break;
				case 'http://ns.ox.ac.uk/namespace/oxpoints/2009/02/owl#Carpark':
					$point['icon'] = "http://opendatamap.ecs.soton.ac.uk/img/icon/parking.png";
					break;
				case 'http://ns.ox.ac.uk/namespace/oxpoints/2009/02/owl#OpenSpace':
					$point['icon'] = "http://opendatamap.ecs.soton.ac.uk/img/icon/park-urban.png";
					break;
				case 'http://ns.ox.ac.uk/namespace/oxpoints/2009/02/owl#Library':
				case 'http://ns.ox.ac.uk/namespace/oxpoints/2009/02/owl#SubLibrary':
					$point['icon'] = "http://opendatamap.ecs.soton.ac.uk/img/icon/library.png";
					break;
				case 'http://ns.ox.ac.uk/namespace/oxpoints/2009/02/owl#Museum':
					$point['icon'] = "http://opendatamap.ecs.soton.ac.uk/img/icon/museum-historical.png";
					break;
			}
			$points[] = $point;
		}
		return $points;
	}

	function getOxPoints($q)
	{
		$tpoints = sparql_get($endpoint, "
	PREFIX oxp: <http://ns.ox.ac.uk/namespace/oxpoints/2009/02/owl#>
	PREFIX foaf: <http://xmlns.com/foaf/0.1/>
	PREFIX dc: <http://purl.org/dc/elements/1.1/>
	PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>

	SELECT ?pos ?poslabel WHERE {
	  ?pos a ?type .
	  ?pos dc:title ?label .
	    ?pos oxp:occupies ?c .
	    ?c geo:lat ?lat .
	    ?c geo:long ?long .
	    ?pos dc:title ?poslabel .
	}
		");
		$points = array();
		foreach($tpoints as $point)
		{
			if(!preg_match('/'.$q.'/i', $point['label']) && !preg_match('/'.$q.'/i', $point['poslabel']))
				continue;
			$point['icon'] = "http://opendatamap.ecs.soton.ac.uk/img/icon/computer.png";
			$point['label'] = 'point';
			$points[] = $point;
		}
		return $points;
	}

	function visibleCategory($icon, $cats)
	{
		global $iconcats;
		if($iconcats == null) include_once "inc/categories.php";
		return in_cat($iconcats, $icon, $cats);
	}

	function createOxPointEntries(&$pos, &$label, &$type, &$url, &$icon, $q, $cats)
	{
		$data = getOxPoints($q);
		foreach($data as $point) {
			if(!visibleCategory($point['icon'], $cats))
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
	}
}
?>
