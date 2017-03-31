<?php
$allowedWikis = array(
	// Liquipedia
	'starcraft',
	'starcraft2',
	'dota2',
	'hearthstone',
	'heroes',
	'smash',
	'counterstrike',
	'overwatch',
	'commons',
	'warcraft',
	'fighters',
	'rocketleague',
	// Special
	'tolueno'
);

function getWikiData($varName, $varValue = '') {
	global $allowedWikis;

	if ($varValue || isset($_POST[$varName])) {
		$wiki = isset($_POST[$varName]) ? $_POST[$varName] : $varValue;
		if ($wiki && !in_array($wiki, $allowedWikis)) {
			echo '<p>' . $wiki . ' not recognized. Possible values are: ' . implode(', ', $allowedWikis) . '.</p>';
			exit(1);
		}

		if ($wiki == 'tolueno') {
			return array(
				'name' => $wiki,
				'domain' => 'tolueno.fr',
				'path' => '/',
				'apiUrl' => 'http://tolueno.fr/w/api.php',
				'scriptUrl' => 'http://tolueno.fr/w/index.php'
			);
		} else {
			return array(
				'name' => $wiki,
				'domain' => 'wiki.teamliquid.net',
				'path' => '/' . $wiki . '/',
				'apiUrl' => 'http://wiki.teamliquid.net/' . $wiki .'/api.php',
				'scriptUrl' => 'http://wiki.teamliquid.net/' . $wiki .'/index.php'
			);
		}
	} else {
		echo '<p>' . $varName . ' not specified</p>';
		exit(1);
	}
}