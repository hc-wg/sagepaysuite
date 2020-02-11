<?php
$files = array_slice($argv, 1);

$xml = new DOMDocument();
$xml->load(__DIR__ . '/phpunit.xml');

$testsuite = $xml->createElement('testsuite');
$testsuite->setAttribute('name', 'partial');

foreach ($files as $file) {
    $testsuite->appendChild($xml->createElement('file', $file));
}

$testsuites = $xml->createElement('testsuites');
$testsuites->appendChild($testsuite);
$phpunit = $xml->getElementsByTagName('phpunit')->item(0);

try {
    $phpunit->replaceChild($testsuites, $phpunit->getElementsByTagName('testsuite')->item(0));
} catch (DOMException $de) {
    $phpunit->replaceChild($testsuites, $phpunit->getElementsByTagName('testsuites')->item(0));
}

$xml->save(__DIR__ . '/phpunit-partial.xml');

exit(0);