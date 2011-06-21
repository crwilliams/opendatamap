<?
mysql_connect('localhost', 'user', 'pass');

class SqldemoDataSource extends DataSource
{
	static function getAll()
	{
		$query = "
		SELECT	id,
			lat,
			long,
			label,
			icon
		FROM	point_of_service
		";
		$res = mysql_query($query);
		$points = array();
		while($row = mysql_fetch_assoc($res))
		{
			$points[] = $row;
		}
		return $points;
	}
	
	static function getEntries($q, $cats)
	{
		$q = mysql_real_escape_string($q);
		$cats = mysql_real_escape_string($cats); //This line is unlikely to be correct, but be careful to escape input before passing to database.
		$labellimit = 100;
	
		$pos = array();
		$label = array();
		$type = array();
		$url = array();
		$icon = array();
	
		$query = "
		SELECT	point_of_service.id AS id,
			point_of_service.label AS label,
			offering.label AS offeringlabel,
			icon
			FROM	point_of_service
		JOIN	offering_at_point_of_service
		ON	point_of_service.id = offering_at_point_of_service.point_of_service_id
		JOIN	offering
		ON	offering.id = offering_at_point_of_service.offering_id
		WHERE	category IN (".$cats.")
		AND	(label LIKE '%".$q."%' OR offeringlabel LIKE '%".$q."%')
		";
		$res = mysql_query($query);
		$points = array();
		while($row = mysql_fetch_assoc($res))
		{
			$pos[$row['id']] ++;
			if(preg_match('/'.$q.'/i', $row['offeringlabel']))
			{
				$label[$row['offeringlabel']] ++;
				$type[$row['offeringlabel']] = "offering";
			}
			if(preg_match('/'.$q.'/i', $row['label']))
			{
				$label[$row['label']] += 10;
				$type[$row['label']] = "point-of-service";
				$url[$row['label']] = $row['id'];
				$icon[$row['label']] = $row['icon'];
			}
		}
	
		arsort($label);
		if(count($label) > $labellimit)
			$label = array_slice($label, 0,$labellimit);

		return array($pos, $label, $type, $url, $icon);
	}

	static function processURI($uri)
	{
		$uri = mysql_real_escape_string($uri);
		$query = "
		SELECT	label,
			icon
		FROM	point_of_service
		WHERE	id = '$uri'
		";
		$res = mysql_query($query);
		$row = mysql_fetch_assoc($res);
		echo "<div id='content'>";
		echo "<h2><img class='icon' src='".$row['icon']."' />".$row['label']."<h2>";
		echo "</div>";
		return true;
	}

}

?>
