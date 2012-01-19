<?
$q = $_GET['query'];

$fields = array(
	'query' => $q,
	'output' => 'xml',
);

$fields_string = "";
foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
rtrim($fields_string,'&');

$url = "http://data.cs.tsinghua.edu.cn/OpenData/sparqlQuery.action";

$ch = curl_init();
curl_setopt($ch,CURLOPT_URL,$url);
curl_setopt($ch,CURLOPT_POST,count($fields));
curl_setopt($ch,CURLOPT_POSTFIELDS,$fields_string);
curl_setopt($ch,CURLOPT_HEADER,false);
curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);

$result = curl_exec($ch);
$result = strip_tags($result);
$result = html_entity_decode($result);
$result = str_replace('&', '&amp;', $result);

//$myFile = "sparql-sample.xml";
//$fh = fopen($myFile, 'w') or die("can't open file");
//fwrite($fh, $result);
//fclose($fh);

//echo "We got this from china:\n";
print($result);
