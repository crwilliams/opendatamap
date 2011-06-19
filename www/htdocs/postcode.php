<?
include_once "inc/sparqllib.php";

$endpoint = "http://api.talis.com/stores/ordnance-survey/services/sparql";

function getPostcodeData($postcode)
{
	$data = sparql_get($endpoint, "
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

function createPostcodeEntries(&$label, &$type, &$url)
{
	$postcodedata = array();
	$postcodefile = "resources/postcodetypes";
	$file = fopen($postcodefile, 'r');
	while($line = fgets($file))
	{
		$postcodedata[] = trim($line);
	}
	fclose($file);

	$fullq = strtoupper($_GET['q']);
	$fullqs = explode(' ', $fullq);

	if(count($fullqs) == 1 || (count($fullqs) == 2 && preg_match('/^([0-9]([A-Z][A-Z]?)?)?$/', $fullqs[1])))
	{
		if(in_array($fullqs[0], $postcodedata))
		{
			$postcode = $fullq.substr($fullqs[0]." ...", strlen($fullq));
			if(strpos($postcode, '.') === false)
			{
				$data = getPostcodeData($fullq);
				if($data != null)
				{
					$postcode =  $fullq.' '.$data['wlabel'].', '.$data['dlabel'];
					$url[$postcode] = 'postcode:'.$fullq.','.$data['lat'].','.$data['long'].','.$data['p'];
				}
				else
				{
					$postcode =  $fullq.' (postcode not found)';
					$url[$postcode] = null;
				}
			}
			$label[$postcode] = 99;
			$type[$postcode] = "postcode";
		}
		if(strpos($fullq, ' ') === false && in_array($fullqs[0].'?', $postcodedata))
		{
			$postcode = $fullq.substr($fullqs[0].". ...", strlen($fullq));
			$label[$postcode] = 100;
			$type[$postcode] = "postcode";
		}
	}
}
?>
