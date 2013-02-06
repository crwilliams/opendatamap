<?
date_default_timezone_set('Europe/London');
include 'inc/simple_html_dom.php';
$html = file_get_html('http://ratings.food.gov.uk/open-data/en-GB');
foreach($html->find('table') as $table)
{
	foreach($table->find('tr') as $row)
	{
		$cells = $row->find('td');
		if(count($cells) == 4)
		{
			$name = trim(preg_replace('/[^A-Za-z0-9:\/.-]+/', '_', str_replace("'", '', trim($cells[0]->plaintext))), '_');
			$rdate = date_create_from_format("d/m/Y H:i", trim($cells[1]->find('span', 0)->plaintext)." ".trim($cells[1]->find('span', 1)->plaintext))->getTimestamp();
			$file = str_replace('\\', '/', trim($cells[3]->find('a', 0)->href));
			$lang = trim($cells[3]->find('a', 0)->plaintext);
			if(strtolower($lang) == 'welsh language')
			{
				$lang = 'cy-gb';
			}
			else if(strtolower($lang) == 'english language')
			{
				$lang = 'en-gb';
			}
			else
			{
				continue;
			}
			$filename = 'modules/food/resources/'.$lang."/".$name.".xml";
			if(!file_exists($filename) || filemtime($filename) < $rdate)
			{
				// Fetch page
				file_put_contents($filename, file_get_contents($file));
				// Update mtime
				touch($filename, $rdate);
				echo "Fetched $filename\n";
				// Sleep
				sleep(1);
			}
			else
			{
				echo "Skipped $filename\n";
			}
		}
	}
}
?>
