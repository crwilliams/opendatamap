<?
class FoodDataSource extends DataSource
{
	static function getDataFile()
	{
		global $config;
		if(preg_match('/^[A-Za-z_-]+$/', $config['datafile']))
		{
			return simplexml_load_file('/home/opendatamap/FHRS/'.$config['datafile'].'.xml');
		}
	}

	static function getAll()
	{
		$i = 0;
		$data = static::getDataFile();
		$points = array();
		foreach($data->EstablishmentCollection->EstablishmentDetail as $establishment)
		{
			$point['id'] = 'http://fhrs.example.com/'.$establishment->LocalAuthorityBusinessID;
			$point['label'] = (string)str_replace('"', '', $establishment->BusinessName);
			$point['lat'] = (string)$establishment->Geocode->Latitude;
			$point['long'] = (string)$establishment->Geocode->Longitude;
			$point['icon'] = static::getIcon((string)$establishment->BusinessType);
			if($point['lat'] == '' || $point['long'] == '') continue;
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
		$data = self::getPoints($q);
		foreach($data as $point) {
			if(!in_array(strtolower($point['ratingkey']), $cats))
				continue;
			$pos[$point['pos']] ++;
			if(preg_match('/'.$q.'/i', $point['poslabel']))
			{
				$label[$point['poslabel']] += 10;
				$type[$point['poslabel']] = "point-of-service";
				$url[$point['poslabel']] = '';
				$icon[$point['poslabel']] = $point['icon'];
			}
		}
		return array($pos, $label, $type, $url, $icon);
	}
	
	static function getDataSets(){
		return array(array('name' => 'Food Hygiene Rating Scheme', 'uri' => 'http://ratings.food.gov.uk/open-data/en-GB', 'l' => 'http://www.food.gov.uk/ratings-terms-and-conditions'));
	}

	static function processURI($uri){
		if(substr($uri, 0, strlen('http://fhrs.example.com/')) == 'http://fhrs.example.com/')
		{
			$id = substr($uri, strlen('http://fhrs.example.com/'));
			$data = static::getDataFile();
			$points = array();
			foreach($data->EstablishmentCollection->EstablishmentDetail as $establishment)
			{
				if($establishment->LocalAuthorityBusinessID == $id)
				{
					echo "<h2><img class='icon' src='".self::getIcon((string)$establishment->BusinessType)."' />".$establishment->BusinessName."<h2>";
					echo $establishment->AddressLine1.'<br/>';
					echo $establishment->AddressLine2.'<br/>';
					echo $establishment->AddressLine3.'<br/>';
					echo $establishment->AddressLine4.'<br/>';
					echo $establishment->PostCode.'<br/><br/>';
					echo "<a href='http://ratings.food.gov.uk/business/".$establishment->FHRSID."'><img src='img/fhrs/small/72ppi/".strtolower($establishment->RatingKey).".jpg' alt='Food hygiene rating: ".$establishment->RatingValue."' title='Food hygiene rating: ".$establishment->RatingValue."' /></a>";
					echo '<br /><br /><span style="font-size: 0.8em">as of '.$establishment->RatingDate.'</span><br/>';

					return true;
				}
			}
			return false;
		}
	}

	static function getPoints($q)
	{
		$i = 0;
		$data = static::getDataFile();
		$points = array();
		foreach($data->EstablishmentCollection->EstablishmentDetail as $establishment)
		{
			$point['pos'] = 'http://fhrs.example.com/'.$establishment->LocalAuthorityBusinessID;
			$point['ratingkey'] = (string)$establishment->RatingKey;
			$point['poslabel'] = (string)str_replace('"', '', $establishment->BusinessName);
			$point['icon'] = static::getIcon((string)$establishment->BusinessType);
			if(!preg_match('/'.$q.'/i', $point['poslabel']))
				continue;
			$points[] = $point;
		}
		return $points;
	}

	static function getIcon($type)
	{
		switch($type)
		{
			case 'Restaurant/Cafe/Canteen':
				return self::$iconpath.'Restaurants-and-Hotels/restaurant.png';
			case 'Hotel/bed & breakfast/guest house':
				return self::$iconpath.'Restaurants-and-Hotels/lodging_0star.png';
			case 'Retailers - supermarkets/hypermarkets':
			case 'Supermarket/Hypermarket':
				return self::$iconpath.'Stores/supermarket.png';
			case 'Hospitals/Childcare/Caring Premises':
				return self::$iconpath.'Education/family.png';
			case 'Other catering premises':
				return self::$iconpath.'Restaurants-and-Hotels/teahouse.png';
			case 'Distributors/Transporters':
				return self::$iconpath.'Transportation/truck3.png';
			case 'Pub/bar/nightclub':
				return self::$iconpath.'Restaurants-and-Hotels/bar_coktail.png';
			case 'Takeaway/sandwich shop':
				return self::$iconpath.'Restaurants-and-Hotels/takeaway.png';
			case 'School/college/university':
				return self::$iconpath.'Education/school.png';
			case 'Mobile caterer':
				return self::$iconpath.'Restaurants-and-Hotels/foodtruck.png';
			case 'Manufacturers/packers':
				return self::$iconpath.'Industry/factory.png';
			case 'Importers/Exporters':
				return self::$iconpath.'Transportation/truck3.png';
				return self::$iconpath.'Industry/boatcrane.png';
			case 'Farmers/growers':
				return self::$iconpath.'Nature/farm-2.png';
			case 'Retailers - other':
				return self::$iconpath.'Stores/conveniencestore.png';
			default:
				return self::$iconpath.'Nature/fruits.png';
		}
	}
}
?>
