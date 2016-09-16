<?php
$scriptName = 'mass-edit';
$myUserAgent = 'Mozilla/5.0 (compatible; ' . $scriptName . '/1.0; chapatiyaq@gmail.com)';

include '../wiki.php';
$wikiData = getWikiData('wiki');

// Other parameters
$name = $_POST['login'];
$pass = $_POST['password'];
$interval = intval($_POST['interval']);
$preview = isset ($_POST['preview']) ? $_POST['preview'] : false;
$from = $_POST['from'];
$limit = $_POST['limit'];
$inputformat = $_POST['inputformat'];
$file = $_POST['file'];
$text = $_POST['text'];

$cookieFile = '../cookies.tmp';
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
$edits = array();
$i = 1;
foreach ($lines as $line) {
	if ($line[0] == '#') {
		continue;
	}
	list($title, $type, $text) = explode("\t", $line, 3);
	switch($type) {
	case 'text':
		$edits[$title] = str_replace("\\n", "\n", trim($text));
		break;
	case 'file':
		$edits[$title] = file_get_contents(trim($text));
		break;
	default:
		echo 'Bad format at line:' . $i;
		exit(2);
	}
	++$i;
}
$edits = array_slice($edits, $from, $limit);

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
$postdata = http_build_query(array(
	'action' => 'query',
	'meta' => 'tokens',
	'format' => 'php'
));
curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
$data = unserialize(curl_exec($curl));
$token = $data['query']['tokens']['csrftoken'];
echo '<b>CSRF token: </b>' . htmlspecialchars($token) . '<br>';

// *--
// -*- Get page information
// --*
$postdata = http_build_query(array(
	'action' => 'query',
	'prop' => 'info|revisions',
	'titles' => implode('|', array_keys($edits)),
	'format' => 'php'
));
curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);

$data = unserialize(curl_exec($curl));
$pages = $data['query']['pages'];
//echo '<pre>' . print_r($pages, true) . '</pre>';

$i = 0;
foreach ( $pages as $page ) {
	//echo $page['title'] . ': ' . $page['edittoken'] . '<br>';
	$postdata = http_build_query(array(
		'action' => 'edit',
		'assert' => 'user',
		'bot' => 'true',
		'title' => $page['title'],
		'token' => $token,
		'text' => $edits[$page['title']],
		'format' => 'php'
	));
	curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
	
	// DISPLAY TITLE
	echo '<b>' . $page['title'] . '</b><br>';
	// END DISPLAY TITLE

	if ($preview) {
		echo '<div style="white-space:pre-line;font-family:monospace;font-size:.9em;">'
			. htmlspecialchars($edits[$page['title']]) . '</div>';
	} else {
		if ($i > 0) {
			usleep($interval);
		}
		$data = unserialize(curl_exec($curl));
		echo 'Result: ' . htmlspecialchars($data['edit']['result']) . '<br>';
	}
	++$i;
}

curl_close($curl);
?>
</body>
</html>