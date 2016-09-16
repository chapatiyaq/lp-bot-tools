<?php
$scriptName = 'mass-protect';
$myUserAgent = 'Mozilla/5.0 (compatible; ' . $scriptName . '/1.0; chapatiyaq@gmail.com)';

include '../wiki.php';
$wikiData = getWikiData('wiki');

// Other parameters
$name = $_POST['login'];
$pass = $_POST['password'];
$editProtection = $_POST['edit'];
$expiry = $_POST['expiry'];
$reason = $_POST['reason'];
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
$titles = array();
foreach ($lines as $line) {
	$titles[] = trim($line);
}
$titles = array_slice($titles, $from, $limit);

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
$postdata = http_build_query(array(
	'action' => 'query',
	'meta' => 'tokens',
	'format' => 'php'
));
curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
$data = unserialize(curl_exec($curl));
$token = $data['query']['tokens']['csrftoken'];

// *--
// -*- Get page information
// --*
curl_setopt($curl, CURLOPT_POSTFIELDS, array(
	'action' => 'query',
	'prop' => 'info|revisions',
	'titles' => implode('|', $titles),
	'format' => 'php'
));

$data = unserialize(curl_exec($curl));
$pages = $data['query']['pages'];

foreach ( $pages as $page ) {
	curl_setopt($curl, CURLOPT_POSTFIELDS, array(
		'action' => 'protect',
		'bot' => 'true',
		'title' => $page['title'],
		'token' => $token,
		'protections' => 'edit=' . $editProtection,
		'expiry' => $expiry,
		'reason' => $reason,
		'format' => 'php'
	));
	
	echo $page['title'] . '<br/>';
	$data = unserialize(curl_exec($curl));
	usleep($interval);
}

curl_close($curl);
?>
</body>
</html>