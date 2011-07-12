<?
include_once "inc/sparqllib.php";

class EnglandDataSource extends DataSource
{
	static $endpoint = "http://services.data.gov.uk/education/sparql";

	static function getAll()
	{
		$tpoints = sparql_get(self::$endpoint, "
PREFIX school: <http://education.data.gov.uk/def/school/>
PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>

SELECT ?id ?lat ?long ?label ?type WHERE {
  ?id school:typeOfEstablishment ?type .
  {
    { ?id school:typeOfEstablishment school:TypeOfEstablishment_TERM_Higher_Education_Institutions . }
    UNION
    { ?id school:typeOfEstablishment school:TypeOfEstablishment_TERM_Further_Education . }
  } .
  ?id rdfs:label ?label .
  ?id geo:lat ?lat .
  ?id geo:long ?long .
}
		");
		$points = array();
		foreach($tpoints as $point)
		{
			$point['icon'] = self::getIcon($point);
			if($point['icon'] == null)
				continue;
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
		$data = self::getMatches($q);
		foreach($data as $point) {
			if(!self::visibleCategory($point['icon'], $cats))
				continue;
			$pos[$point['pos']] ++;
			if(preg_match('/'.$q.'/i', $point['label']))
			{
				$label[$point['label']] += 10;
				$type[$point['label']] = "workstation";
				$url[$point['label']] = $point['pos'];
				$icon[$point['label']] = $point['icon'];
			}
		}
		return array($pos, $label, $type, $url, $icon);
	}
	
	static function getDataSets(){
		return array(array('name' => 'data.gov.uk', 'uri' => 'http://data.gov.uk/', 'l' => 'http://reference.data.gov.uk/id/open-government-licence'));
	}

	static function processURI($uri){
		if(substr($uri, 0, strlen('http://education.data.gov.uk/id/school/')) == 'http://education.data.gov.uk/id/school/')
		{
			$info = sparql_get(self::$endpoint, "
PREFIX school: <http://education.data.gov.uk/def/school/>
PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>

SELECT ?label ?web ?add1 ?add2 ?add3 ?town ?region ?postcode ?type ?prefemail ?altemail WHERE {
  OPTIONAL { <$uri> school:websiteAddress ?web . }
  <$uri> school:typeOfEstablishment ?type .
  {
    { <$uri> school:typeOfEstablishment school:TypeOfEstablishment_TERM_Higher_Education_Institutions . }
    UNION
    { <$uri> school:typeOfEstablishment school:TypeOfEstablishment_TERM_Further_Education . }
  } .
  <$uri> rdfs:label ?label .
  <$uri> school:address ?add .
  OPTIONAL { ?add school:address1 ?add1 . }
  OPTIONAL { ?add school:address2 ?add2 . }
  OPTIONAL { ?add school:address3 ?add3 . }
  OPTIONAL { ?add school:town ?town . }
  OPTIONAL { ?add school:region ?region . }
  OPTIONAL { ?add school:postcode ?postcode . }
  OPTIONAL { <$uri> school:SCUpreferredemail ?prefemail . }
  OPTIONAL { <$uri> school:SCUAlternativeEmail ?altemail . }
}
			");
			echo "<h2><img class='icon' src='".self::getIcon($info[0])."' />".$info[0]['label']."<h2>";
			$web = $info[0]['web'];
			if($web != '')
			{
				if(!preg_match('/^[a-z]+:\/\//', $web))
					$web = 'http://'.$web;
				echo "<a href='".$web."'>".$web.'</a><br/><br/>';
			}
			$email = $info[0]['prefemail'];
			if($email != '')
			{
				echo "<a href='mailto:".$email."'>".$email.'</a><br/><br/>';
			}
			if($info[0]['add1'] != '') echo $info[0]['add1'].'<br/>';
			if($info[0]['add2'] != '') echo $info[0]['add2'].'<br/>';
			if($info[0]['add3'] != '') echo $info[0]['add3'].'<br/>';
			if($info[0]['town'] != '') echo $info[0]['town'].'<br/>';
			if($info[0]['region'] != '') echo $info[0]['region'].'<br/>';
			if($info[0]['postcode'] != '') echo $info[0]['postcode'].'<br/>';
			return true;
		}
	}

	static function getMatches($q)
	{
		$tpoints = sparql_get(self::$endpoint, "
PREFIX school: <http://education.data.gov.uk/def/school/>
PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>

SELECT ?pos ?lat ?long ?label ?type WHERE {
  ?pos school:typeOfEstablishment ?type .
  {
    { ?pos school:typeOfEstablishment school:TypeOfEstablishment_TERM_Higher_Education_Institutions . }
    UNION
    { ?pos school:typeOfEstablishment school:TypeOfEstablishment_TERM_Further_Education . }
  } .
  ?pos rdfs:label ?label .
  ?pos geo:lat ?lat .
  ?pos geo:long ?long .
}
		");
		$points = array();
		foreach($tpoints as $point)
		{
			if(!preg_match('/'.$q.'/i', $point['label']))// && !preg_match('/'.$q.'/i', $point['hiddenlabel']))
				continue;
			$point['icon'] = self::getIcon($point);
			if($point['icon'] == null)
				continue;
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
			case 'http://education.data.gov.uk/def/school/TypeOfEstablishment_TERM_Higher_Education_Institutions':
				return "http://opendatamap.ecs.soton.ac.uk/img/icon/Education/university.png";
			case 'http://education.data.gov.uk/def/school/TypeOfEstablishment_TERM_Further_Education':
				return "http://opendatamap.ecs.soton.ac.uk/img/icon/Education/school.png";
		}
		return null;
	}
}
?>
