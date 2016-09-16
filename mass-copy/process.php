<?php
$scriptName = 'mass-copy';
$myUserAgent = 'Mozilla/5.0 (compatible; ' . $scriptName . '/1.0; chapatiyaq@gmail.com)';

include '../wiki.php';
$wikifromData = getWikiData('wikifrom');
$wikitoData = getWikiData('wikito');

// Other parameters
$name = $_POST['login'];
$pass = $_POST['password'];
$interval = intval($_POST['interval']);
$from = $_POST['from'];
$limit = $_POST['limit'];
$inputformat = $_POST['inputformat'];
$file = $_POST['file'];
$text = $_POST['text'];

$cookieFile = '../cookies.tmp';
$wikiData = $wikitoData;
include '../cookies.php';

// *--
// -*- Get edits
// --*
if ($inputformat == 'file') {
	$lines = file('./files/' . $file);
} else if ($inputformat == 'text') {
	$lines = explode("\n", $text);
} else {
	echo 'No input format selected';
	exit(4);
}
$copies = array();
foreach ($lines as $line) {
	list($source, $destination, $summary) = explode("\t", rtrim($line, "\r\n"), 3);
	$source = str_replace('_', ' ', $source);
	$destination = str_replace('_', ' ', $destination);
	$copies[$source] = array(
		'destination' => $destination,
		'summary' => trim($summary)
	);
}
$copies = array_slice($copies, $from, $limit);

$cc = array();
$cc['options'] = array(
	CURLOPT_USERAGENT => $myUserAgent,
	CURLOPT_RETURNTRANSFER => 1,
	CURLOPT_ENCODING => '',
	CURLOPT_COOKIEJAR => realpath($cookieFile),
	CURLOPT_COOKIEFILE => realpath($cookieFile),
	CURLOPT_POST => true,
	//CURLOPT_USERPWD => "user:password",
	//CURLOPT_HTTPAUTH => CURLAUTH_BASIC
);

//$url = 'http://localhost/w/api.php';
$cc['url'] = $wikitoData['apiUrl'];
include '../login.php';
?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
</head>
<body>
<h1><?php echo $scriptName; ?></h1>
<?php
echo 'Destination wiki: ' . $wikitoData['name'] . ' &ndash; URL: ' . $cc['url'] . '<br>';
echo htmlspecialchars(implode(';', $cookieVars)) . '<br>';
echo 'Login status: ' . htmlspecialchars($loginStatus) . '<br>';
echo 'New cookie set: ' . ($isNewCookieSet ? 'true' : 'false') . '<br>';

// Initialize a new cURL session
$curl = curl_init();
curl_setopt_array($curl, $cc['options']);
curl_setopt($curl, CURLOPT_URL, $wikitoData['apiUrl']);

// *--
// -*- Get a token
// --*
$postdata = http_build_query(array(
	'action' => 'query',
	'meta' => 'tokens',
	'format' => 'php'
));
curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
$data = unserialize(curl_exec($curl));
$token = $data['query']['tokens']['csrftoken'];

curl_setopt($curl, CURLOPT_URL, $wikifromData['apiUrl']);
curl_setopt($curl, CURLOPT_POSTFIELDS, array(
	'action' => 'query',
	'prop' => 'info|revisions',
	'titles' => implode('|', array_keys($copies)),
	'rvprop' => 'content',
	'redirects' => true,
	'format' => 'php'
));

$data = unserialize(curl_exec($curl));
$page_contents = $data['query']['pages'];

if ( count((array)$page_contents) == count($copies) ) {
	foreach ( $copies as $title => $copy ) {
		curl_setopt($curl, CURLOPT_URL, $wikifromData['apiUrl']);
		curl_setopt($curl, CURLOPT_POSTFIELDS, array(
			'action' => 'query',
			'prop' => 'info',
			'titles' => $title,
			'redirects' => true,
			'indexpageids' => true,
			'format' => 'php'
		));

		$data = unserialize(curl_exec($curl));
		$pages = $data['query']['pages'];
		$pageids = $data['query']['pageids'];

		$page = $page_contents[$pageids[0]];
		
		// DISPLAY TITLE
		echo 'Source: ' . $title .
		     ($page['title'] != $title ? ' → ' . $page['title'] : '') .
		     '<br/>';
		// END DISPLAY TITLE

		$original_text = $page['revisions'][0]['*'];
		if (strlen($original_text) == 0) {
			continue;
		}

		$postfields = array(
			'action' => 'edit',
			'bot' => 'true',
			'title' => $copy['destination'],
			'token' => $token,
			'text' => $original_text,
			'format' => 'php'
		);
		if ($edit['summary']) {
			$postfields['summary'] = $edit['summary'];
		} else if ($wikifromData['name'] == $wikitoData['name']) {
			$postfields['summary'] = 'Copied content from [[' . $page['title'] . ']], revision ' . $page['lastrevid'] .
			'. See the history page of that article for attribution.';
		} else {
			$postfields['summary'] = 'Copied content from the Liquipedia wiki ' . $wikifromData['name'] . ', article "' . $page['title'] . '", revision ' . $page['lastrevid'] .
			'. See the history page of that article for attribution.';
		}
		curl_setopt($curl, CURLOPT_URL, $wikitoData['apiUrl']);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $postfields);
		
		echo '<h5>' . $copy['destination'] . '</h5>';
		/*echo '<pre style="background-color: #f8f8f8; border: 1px solid #f0f0f0; width: 100%;">' .
		     htmlspecialchars($original_text) .
		     '</pre>';*/
		//echo '<pre>' . print_r($postfields, true) . '</pre>';
		//var_dump(htmlspecialchars($edit));
		$data = unserialize(curl_exec($curl));
		usleep($interval);
	}
} else {
	echo 'One or more page has not been found. From source: '. count($copies) . ' - Found: ' . count((array)$page_contents);
	return;
}

curl_close($curl);
?>
</body>
</html>