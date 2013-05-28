<?php
function reduceBusCodes($codes)
{
	$mapping = array(
		'U1'	=> 'U1',
		'U1A'	=> 'U1',
		'U1C'	=> 'U1',
		'U1E'	=> 'U1',
		'U2'	=> 'U2',
		'U2B'	=> 'U2',
		'U2C'	=> 'U2',
		'U6'	=> 'U6',
		'U6C'	=> 'U6',
		'U6H'	=> 'U6',
		'U9'	=> 'U9',
	);
	$outcodes = array();
	foreach($codes as $code)
	{
		if(array_key_exists($code, $mapping))
		{
			$outcodes[$mapping[$code]] = true;
		}
	}
	$outcodes = array_keys($outcodes);
	asort($outcodes);
	return $outcodes;
}
?>
