<?
include 'inc/JSON.php';
//error_reporting(E_ALL);
if( !function_exists('json_encode') ) {
function json_encode($data) {
$json = new Services_JSON();
return( $json->encode($data) );
}
}

// Future-friendly json_decode
if( !function_exists('json_decode') ) {
function json_decode($data, $bool) {
if ($bool) {
$json = new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
} else {
$json = new Services_JSON();
}
return( $json->decode($data) );
}
}

class LincolnDataSource extends DataSource
{
/*
	static function getAll()
	{
		//id, lat, long, label
		$libs = simplexml_load_file('camlib.xml');
		foreach($libs->library as $lib)
		{
			if($lib->lat == null || $lib->lng == null)
				continue;
			$point['id'] = 'http://www.lib.cam.ac.uk/#'.(string)$lib->code;
			$point['lat'] = (float)$lib->lat;
			$point['long'] = (float)$lib->lng;
			$point['label'] = (string)$lib->name;
			$point['icon'] = "http://opendatamap.ecs.soton.ac.uk/img/icon/library.png";
			$points[] = $point;
		}
		return $points;
	}

	// Process library data
	static function getEntries($q, $cats)
	{	
		$pos = array();
		$label = array();
		$type = array();
		$url = array();
		$icon = array();
		$data = self::getLibraries($q);
		foreach($data as $point) {
			$point['icon'] = 'http://opendatamap.ecs.soton.ac.uk/img/icon/library.png';
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
*/

	static function getAllBuildings()
	{
		$data = json_decode(file_get_contents('https://nucleus.online.lincoln.ac.uk/locations2/buildings'));
		$data = $data->results;
		$buildings = array();
		foreach($data as $bs)
		{
			if(isset($bs->outline))
			{
				$outline = array();
				foreach($bs->outline as $op)
				{
					$outline[] = $op->lat.' '.$op->long;
				}
				$outline = 'POLYGON(('.implode(',', $outline).'))';
				
				$building = array('url' => $bs->building_permalink,
					'name' => $bs->building_name,
					'number' => $bs->building_id,
					'outline' => $outline,
					'color' => self::getColor($bs->colourscheme),
				);
				$buildings[] = $building;
			}
		}
		return $buildings;
	}

	static function getColor($color) {
		$r = str_pad(dechex($color->r), 2, '0', STR_PAD_LEFT);
		$g = str_pad(dechex($color->g), 2, '0', STR_PAD_LEFT);
		$b = str_pad(dechex($color->b), 2, '0', STR_PAD_LEFT);
		return '#'.strtoupper($r.$g.$b);
	}

	static function getDataSets(){
		return array(array('name' => 'University of Lincoln Open Data', 'uri' => 'http://data.online.lincoln.ac.uk/', 'l' => 'http://creativecommons.org/licenses/by/2.0/uk/'));
	}

/*
	static function getLibraries($q)
	{
		//poslabel, label, pos, icon
		$libs = simplexml_load_file('camlib.xml');
		foreach($libs->library as $lib)
		{
			if($lib->lat == null || $lib->lng == null)
				continue;
			$point['poslabel'] = (string)$lib->name;
			$point['pos'] = 'http://www.lib.cam.ac.uk/#'.(string)$lib->code;
			$point['label'] = 'library';//(string)$lib->name;
			$point['icon'] = "http://opendatamap.ecs.soton.ac.uk/img/icon/library.png";
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
*/
}
?>
