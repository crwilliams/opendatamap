<?
include_once "inc/sparqllib.php";

class PostcodeDataSource extends DataSource
{
	static $endpoint = "http://api.talis.com/stores/ordnance-survey/services/sparql";

	static function getPostcodeData($postcode)
	{
		$data = sparql_get(self::$endpoint, "
	SELECT ?p ?lat ?long ?wlabel ?dlabel WHERE {
		?p <http://www.w3.org/2000/01/rdf-schema#label> '$postcode' .
		?p <http://www.w3.org/2003/01/geo/wgs84_pos#lat> ?lat .
		?p <http://www.w3.org/2003/01/geo/wgs84_pos#long> ?long .
		?p <http://data.ordnancesurvey.co.uk/ontology/postcode/ward> ?w .
		?w <http://www.w3.org/2004/02/skos/core#prefLabel> ?wlabel .
		?p <http://data.ordnancesurvey.co.uk/ontology/postcode/district> ?d .
		?d <http://www.w3.org/2004/02/skos/core#prefLabel> ?dlabel .
	}
		");
		if(count($data) == 1)
			return $data[0];
		else
			return null;
	}

	static function getEntries($q, $cats)
	{
		$q = strtoupper($q);
		
		$pos = array();
		$label = array();
		$type = array();
		$url = array();
		$icon = array();
		
		$postcodedata = array();
		$postcodefile = "resources/postcodetypes";
		$file = fopen($postcodefile, 'r');
		while($line = fgets($file))
		{
			$postcodedata[] = trim($line);
		}
		fclose($file);

		$fullqs = explode(' ', $q);

		if(count($fullqs) == 1 || (count($fullqs) == 2 && preg_match('/^([0-9]([A-Z][A-Z]?)?)?$/', $fullqs[1])))
		{
			if(in_array($fullqs[0], $postcodedata))
			{
				$postcode = $q.substr($fullqs[0]." ...", strlen($q));
				if(strpos($postcode, '.') === false)
				{
					$data = self::getPostcodeData($q);
					if($data != null)
					{
						$postcode =  $q.' '.$data['wlabel'].', '.$data['dlabel'];
						$url[$postcode] = 'postcode:'.$q.','.$data['lat'].','.$data['long'].','.$data['p'];
					}
					else
					{
						$postcode =  $q.' (postcode not found)';
						$url[$postcode] = null;
					}
				}
				$label[$postcode] = 99;
				$type[$postcode] = "postcode";
			}
			if(strpos($q, ' ') === false && in_array($fullqs[0].'?', $postcodedata))
			{
				$postcode = $q.substr($fullqs[0].". ...", strlen($q));
				$label[$postcode] = 100;
				$type[$postcode] = "postcode";
			}
		}
		return array($pos, $label, $type, $url, $icon);
	}

	static function getDataSets()
	{
		return array(array('name' => 'Ordnance Survey Linked Data', 'uri' => 'http://data.ordnancesurvey.co.uk', 'l' => 'http://reference.data.gov.uk/id/open-government-licence'));
	}

	static function getDataSetExtras()
	{
		return array("Contains Ordnance Survey data &copy; Crown copyright and database right 2011.", "Contains Royal Mail data &copy; Royal Mail copyright and database right 2011.");
	}
}
?>
