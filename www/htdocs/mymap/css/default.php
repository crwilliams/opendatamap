<?
$style = 2;
switch($style)
{
case 1:
	$c['white'] = '#FFFFFF';
	$c['blue'] = '#014359';
	$c['darkgrey'] = '#323D43';
	$c['grey'] = '#9BA3A6';
	$c['green'] = '#979E45';
	$c['turquoise'] = '#007275';
	$c['cyan'] = '#0A96A9';
	break;

case 2:
	$c['white'] = '#FFFFFF';
	$c['blue'] = '#005A87';
	$c['darkgrey'] = '#3D5A6B';
	$c['grey'] = '#9CACB6';
	$c['green'] = '#A0AF5D';
	//$c['green'] = '#617532';
	$c['turquoise'] = '#008A9B';
	$c['cyan'] = '#00A6CE';
	$c['green'] = $c['turquoise'];
	break;
}
?>
body{
	background-color:<?= $c['white'] ?>;
	color:<?= $c['darkgrey'] ?>;
}

.sidebar{
	background-color:<?= $c['blue'] ?>;
	border:solid 1px <?= $c['darkgrey'] ?>;
}

.sidebarlink{
	background-color:<?= $c['cyan'] ?>;
}

a:link,
a:visited,
a:hover{
	color:<?= $c['cyan'] ?>;
}

.footer a:link,
.footer a:visited,
.footer a:hover{
	color:<?= $c['white'] ?>;
}

.dates{
	border:solid 5px <?= $c['blue'] ?>;
}

.header,.footer{
	background-color:<?= $c['green'] ?>;
	border:solid 1px <?= $c['darkgrey'] ?>;
}

hr {
	background-color:<?= $c['green'] ?>;
	border:solid 1px <?= $c['green'] ?>;
}

table{
	margin-left:auto;
	margin-right:auto;
}
