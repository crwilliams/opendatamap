<?
//Do not edit this file.  Instead, set these variables in modules/default/config.php, which will automatically be included if it exists.

$config['Site title'] = "Map";
$config['Site keywords'] = "map,interactive";
$config['Site description'] = "Interactive Map";
$config['default lat'] = 50.9355;
$config['default long'] = -1.39595;
$config['default zoom'] = 17;
$config['default map'] = "'Map2'";
$config['datasource'] = 'sqldemo';
$config['enabled'] = array('search', 'geobutton', 'toggleicons');
$config['categories']['Transportation'] = 'Transport';
$config['categories']['Restaurants-and-Hotels'] = 'Catering and Accommodation';
$config['categories']['Offices'] = 'Services';
$config['categories']['Culture-and-Entertainment'] = 'Culture and Entertainment';
$config['categories']['Health'] = 'Health and Beauty';
$config['categories']['Tourism'] = 'Tourism and Religion';
$config['categories']['Stores'] = 'Retail';
$config['categories']['Education'] = 'Education';
$config['categories']['Sports'] = 'Sports';
$config['categories']['Media'] = 'Events';

error_reporting(0);
$version = $_GET['v'];
$versionparts = explode('_', $version, 2);

@include 'modules/default/config.php';

$path = '.';
if(preg_match('/^[a-zA-Z0-9_-]+$/', $_GET['v']))
{
	$version = $versionparts[0];
	$path = $version;
}

if(!preg_match('/^[a-zA-Z0-9-]*$/', $version))
{
	// Version name may be unsafe.
	die();
}

@include 'modules/'.$version.'/config.php';

if(!is_array($config['datasource']))
{
	$config['datasource'] = array($config['datasource']);
}

function has($id) {
	global $config;
	return in_array($id, $config['enabled']);
}

function show($id) {
	global $config;
	if(!in_array($id, $config['enabled']))
	{
		echo "style='display:none'";
	}
}

function tidyCatName($name) {
	$name = str_replace('<br />', ' / ', $name);
	$name = preg_replace('/<[^>]*>/', '', $name);
	return $name;
}

function getAllMatches($q, $cats)
{
	global $config;
	
	$labellimit = 100;

	$pos = array();
	$label = array();
	$type = array();
	$url = array();
	$icon = array();

	foreach($config['datasource'] as $ds)
	{
		$dsclass = str_replace(' ', '', ucwords(str_replace('/', ' ', $ds))).'DataSource';
		list($npos, $nlabel, $ntype, $nurl, $nicon) = call_user_func(array($dsclass, 'getEntries'), $q, $cats);
		foreach($npos as $k => $v)
			$pos[$k] += $v;
		foreach($nlabel as $k => $v)
			$label[$k] += $v;
		foreach($ntype as $k => $v)
			$type[$k] = $v;
		foreach($nurl as $k => $v)
			$url[$k] = $v;
		foreach($nicon as $k => $v)
			$icon[$k] = $v;
	}
	
	foreach($label as $k => $v)
	{
		$label[$k] = (1000000-$v).'/'.$k;
	}
	asort($label);
	if(count($label) > $labellimit)
		$label = array_slice($label, 0, $labellimit);

	return array($pos, $label, $type, $url, $icon);
}

function getAllBookmarks()
{
	global $config;

	$bookmarks = array();
	foreach($config['datasource'] as $ds)
	{
		$dsclass = str_replace(' ', '', ucwords(str_replace('/', ' ', $ds))).'DataSource';
		foreach(call_user_func(array($dsclass, 'getBookmarks')) as $bookmark)
		{
			$bookmarks[] = $bookmark;
		}
	}
	return $bookmarks;
}

function getPointInfo($uri)
{
	global $config;

	$pointinfos = array();
	foreach($config['datasource'] as $ds)
	{
		$dsclass = str_replace(' ', '', ucwords(str_replace('/', ' ', $ds))).'DataSource';
		$pointinfo = call_user_func(array($dsclass, 'getPointInfo'), $uri);
		if(count($pointinfo) > 0)
		{
			return $pointinfo;
		}
	}
	return null;
}

class DataSource{
	static function getAll(){return array();}
	static function getEntries($q, $cats){return array();}
	static function getDataSets(){return array();}
	static function getDataSetExtras(){return array();}
	static function getAllSites(){return array();}
	static function getAllBuildings(){return array();}
	static function processURI($uri){return false;}
	static function getBookmarks(){return array();}
	static function getPointInfo(){return array();}
	
	static $iconpath = 'http://data.southampton.ac.uk/map-icons/';
	
	static function convertIcon($icon)
	{	
		return $icon;
	}

	static function processOpeningTimes($allopen)
	{
		if(count($allopen) > 0)
		{
			foreach($allopen as $point)
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
			foreach($weekday as $day)
			{
				$short_day = substr($day, 0,3); 
			}

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
					foreach($weekday as $day)
					{
						if(array_key_exists('http://purl.org/goodrelations/v1#'.$day, $otv))
						{
							foreach($otv['http://purl.org/goodrelations/v1#'.$day] as $dot)
							{
								if($dot == '00:00-00:00')
									$dot = '24 hour';
								if($day == date('l', $now))
								{
									$todayopening[] = "<li>$dot</li>";
								}
							}
						}
					}
				}
			}

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
	}

	static function processOffers($allpos)
	{
		if(count($allpos) > 0)
		{
			echo "<h3> Offers: (click to filter) </h3>";
			echo "<ul class='offers'>"; 
			foreach($allpos as $point) {
				echo "<li onclick=\"setInputBox('^".str_replace(array("(", ")"), array("\(", "\)"), $point['label'])."$'); updateFunc();\">".$point['label']."</li>";
			}
			echo "</ul>";
		}
	}
}

function getEnabledCategories()
{
	global $config;
	if($_GET['ec'] == "")
	{
		return array_keys($config['categories']);
	}
	else
	{
		return explode(',', $_GET['ec']);
	}
}

foreach($config['datasource'] as $ds)
{
	if(strpos($ds, '/') === false)
	{
		$ds .= '/ds';
	}
	include_once 'modules/'.$ds.'.php';
}
?>
