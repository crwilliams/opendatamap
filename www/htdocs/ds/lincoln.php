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
}
?>
