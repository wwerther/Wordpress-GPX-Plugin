<html>
<!-- vim: set ts=4 et nu ai syntax=php indentexpr= enc=utf-8 :vim -->


<head>
</head>
<body>
<pre>
<?php
echo "hallo";

$filename='./test/heartrate_pretty.gpx';
$filename='./test/long.gpx';

$filename='./test/heartrate.gpx';


if (file_exists($filename)) {
   // $xml = simplexml_load_file($filename);
	$doc = new DOMDocument();
	$doc->load($filename,LIBXML_NOBLANKS);
} else {
   exit('Konnte test.xml nicht öffnen.');
}


$doc->formatOutput=true;

echo htmlspecialchars($doc->saveXML());

/*
$xml->registerXPathNamespace('c', 'http://www.garmin.com/xmlschemas/WaypointExtension/v1');



$xml->addchild('ww:Walter','test','http://wwerther.de/extensions/');

$namespaces = $xml->getNamespaces(TRUE);
var_dump($namespaces);


print htmlspecialchars($xml->asXML());

var_dump($xml);
 */
?>
</pre>
</body>
</html>
