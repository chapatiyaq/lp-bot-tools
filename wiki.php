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

function getWikiData($varName) {
	global $allowedWikis;

	if (isset($_POST[$varName])) {
		$wiki = $_POST[$varName];
		if ($wiki && !in_array($wiki, $allowedWikis)) {
			echo '<p>' . $varName . ' not recognized. Possible values are: ' . implode(', ', $allowedWikis) . '.</p>';
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
		echo '<p>' . $varname . ' not specified</p>';
		exit(1);
	}
}