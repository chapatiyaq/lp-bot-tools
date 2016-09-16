<?php
$scriptName = 'mass-move';
$myUserAgent = 'Mozilla/5.0 (compatible; ' . $scriptName . '/1.0; chapatiyaq@gmail.com)';

include '../wiki.php';
$wikiData = getWikiData('wiki');

$name = $_POST['login'];
$pass = $_POST['password'];
$interval = intval($_POST['interval']);
$from = $_POST['from'];
$limit = $_POST['limit'];
$inputformat = $_POST['inputformat'];
$file = $_POST['file'];
$text = $_POST['text'];

$cookieFile = '../cookies.tmp';
include '../cookies.php';

// *--
// -*- Get list
// --*
if ($inputformat == 'file') {
	$lines = file('./files/' . $file);
} else if ($inputformat == 'text') {
	$lines = explode("\n", $text);
} else {
	echo 'No input format selected';
	exit(4);
}
$moves = array();
foreach ($lines as $line) {
	list($source, $destination, $reason) = explode("\t", rtrim($line, "\r\n"), 3);
	$source = str_replace('_', ' ', $source);
	$destination = str_replace('_', ' ', $destination);
	$moves[$source] = array(
		'destination' => $destination,
		'reason' => trim($reason)
	);
}
$moves = array_slice($moves, $from, $limit);

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
$cc['url'] = $wikiData['apiUrl'];
include '../login.php';

?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
</head>
<body>
<h1><?php echo $scriptName; ?></h1>
<?php
echo 'Wiki: ' . $wikiData['name'] . ' &ndash; URL: ' . $cc['url'] . '<br>';
//echo htmlspecialchars(implode(';', $cookieVars)) . '<br>';
echo 'Login status: ' . htmlspecialchars($loginStatus) . '<br>';
//echo 'New cookie set: ' . ($isNewCookieSet ? 'true' : 'false') . '<br>';

// Initialize a new cURL session
$curl = curl_init();
curl_setopt_array($curl, $cc['options']);
curl_setopt($curl, CURLOPT_URL, $cc['url']);

// *--
// -*- Get a token
// --*
curl_setopt($curl, CURLOPT_POSTFIELDS, array(
	'action' => 'query',
	'meta' => 'tokens',
	'format' => 'php'
));
$data = unserialize(curl_exec($curl));
$token = $data['query']['tokens']['csrftoken'];

curl_setopt($curl, CURLOPT_POSTFIELDS, array(
	'action' => 'query',
	'prop' => 'info',
	'titles' => implode('|', array_keys($moves)),
	'format' => 'php'
));

$data = unserialize(curl_exec($curl));
$pages = $data['query']['pages'];

if ( count((array)$pages) == count($moves) ) {
	foreach ( $moves as $title => $move ) {

		curl_setopt($curl, CURLOPT_POSTFIELDS, array(
			'action' => 'move',
			//'bot' => 'true',
			'from' => $title,
			'to' => $move['destination'],
			'reason' => $move['reason'],
			'noredirect' => true,
			'token' => $token,
			'format' => 'php'
		));
		
		echo $title . ' → ' . $move['destination'] . ' (' . $move['reason'] . ')<br/>';
		$data = unserialize(curl_exec($curl));
		//echo '<pre>' . print_r($data, true) . '</pre>';
		usleep($interval);
	}
} else {
	echo 'One or more page has not been found. From source: '. count($moves) . ' - Found: ' . count((array)$pages);
	return;
}

curl_close($curl);
?>
</body>
</html>