<?php
$globalcols = $cols;

$cols['fhrs_0_en-gb'] = 'c03638';
$cols['fhrs_1_en-gb'] = 'f34648';
$cols['fhrs_2_en-gb'] = 'ff8a22';
$cols['fhrs_3_en-gb'] = 'ffc11f';
$cols['fhrs_4_en-gb'] = '66c547';
$cols['fhrs_5_en-gb'] = '128e4d';

$cols['fhis_improvement_required_en-gb'] = $cols['fhrs_1_en-gb'];
$cols['fhis_pass_en-gb'] = $cols['fhrs_4_en-gb'];
$cols['fhis_pass_and_eat_safe_en-gb'] = $cols['fhrs_5_en-gb'];

foreach(array_keys($cols) as $code)
{
	if(array_key_exists($code, $globalcols))
	{
		continue;
	}
	$icons[$code][] = 'bar_coktail.png';
	$icons[$code][] = 'conveniencestore.png';
	$icons[$code][] = 'factory.png';
	$icons[$code][] = 'family.png';
	$icons[$code][] = 'farm-2.png';
	$icons[$code][] = 'foodtruck.png';
	$icons[$code][] = 'fruits.png';
	$icons[$code][] = 'lodging_0star.png';
	$icons[$code][] = 'restaurant.png';
	$icons[$code][] = 'school.png';
	$icons[$code][] = 'supermarket.png';
	$icons[$code][] = 'takeaway.png';
	$icons[$code][] = 'teahouse.png';
	$icons[$code][] = 'truck3.png';
}
?>
