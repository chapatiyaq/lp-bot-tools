<?php
$toluenoCookieName = 'lpapi_' . $wikiData['name'] . '_' . $name;
if (isset($_COOKIE[$toluenoCookieName])) {
	$cookieExplode = explode('|', $_COOKIE[$toluenoCookieName]);
	$cookieFileLines = array();
	foreach ($cookieExplode as $cookie) {
		list($cookieName, $cookieValue) = explode('=', $cookie, 2);
		$cookieFileLines[] = "#HttpOnly_" . $wikiData['domain'] . "\tFALSE\t" . $wikiData['path'] . "\tFALSE\t0\t" . $cookieName . "\t". $cookieValue;
	}
	file_put_contents($cookieFile, implode("\n", $cookieFileLines));
	$cookieVars = $cookieExplode;
} else {
	file_put_contents($cookieFile, "");	
}
$isNewCookieSet = false;
