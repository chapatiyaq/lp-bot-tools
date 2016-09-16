<?php
// Initialize a new cURL session
$curl = curl_init();
curl_setopt_array($curl, $cc['options']);
curl_setopt($curl, CURLOPT_URL, $cc['url']);

// *--
// -*- Check user info
// --*
$postdata = http_build_query(array(
	'action' => 'query',
	'meta' => 'userinfo',
	'format' => 'php'
));
curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
$data = unserialize(curl_exec($curl));
//echo '<b>User info: </b><pre>' . print_r($data, true) . '</pre>';

$loginStatus = '';
if (!isset($data['query']['userinfo']['anon']) && $data['query']['userinfo']['name'] == $name) {
	$loginStatus = 'Logged in from cookie as ' . $name;
} else {
	// *--
	// -*- Login
	// --*
	$postdata = http_build_query(array(
		'action' => 'login',
		'lgname' => $name,
		'format' => 'php'
	));
	curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
	$data = unserialize(curl_exec($curl));
	//echo '<h5>$data[\'login\']</h5><pre>' . print_r( $data['login'], true ) . '</pre>';
	$loginToken = $data['login']['token'];

	// Starting at MW 1.27
	//$postdata = http_build_query(array(
	//	'action' => 'query',
	//	'meta' => 'tokens',
	//	'type' => 'login',
	//	'format' => 'php'
	//));
	//curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
	//$data = unserialize(curl_exec($curl));
	//echo '<b>Login tokens data:</b><pre>' . print_r( $data, true ) . '</pre>';
	//$loginToken = $data['query']['tokens']['logintoken'];

	if ( $data['login']['result'] == 'NeedToken') {
		$postdata = http_build_query(array(
			'action' => 'login',
			'lgname' => $name,
			'lgpassword' => $pass,
			'lgtoken' => $loginToken,
			'format' => 'php'
		));
		curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
		$data = unserialize(curl_exec($curl));
		//echo '<h5>$data[\'login\']</h5><pre>' . print_r($data['login'], true) . '</pre>';

		if ($data['login']['result'] == 'Success') {
			$loginStatus = 'Logged in from login info as ' . $name;
			// *--
			// -*- Prepare cookie vars
			// --*
			$cookiePrefix = $data['login']['cookieprefix'];
			$cookieVars = array(
				$cookiePrefix . '_session=' . $data['login']['sessionid'],
				$cookiePrefix . 'UserID=' . $data['login']['lguserid'],
				$cookiePrefix . 'UserName=' . $data['login']['lgusername'],
				$cookiePrefix . 'Token=' . $data['login']['lgtoken']
			);
			$isNewCookieSet = setrawcookie($toluenoCookieName, implode('|', $cookieVars), strtotime('+1 day'), '/liquipedia/bot/', 'tolueno.fr');

			// *--
			// -*- Check user info
			// --*
			//$postdata = http_build_query(array(
			//	'action' => 'query',
			//	'format' => 'php',
			//	'meta' => 'userinfo',
			//	'uiprop' => 'hasmsg',
			//	'format' => 'php'
			//));
			//curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
			//$data = unserialize(curl_exec($curl));
			//echo '<b>User info after login:</b><pre>' . print_r( $data, true ) . '</pre>';
		} else {
			echo 'Error when logging in as ' . $name;
			exit(3);
		}
	} else {
		echo 'Error when logging in as ' . $name;
		exit(3);
	}
}

// Close the cURL session to save cookies
curl_close($curl);
?>