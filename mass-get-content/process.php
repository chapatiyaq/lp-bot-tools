<?php
$scriptName = 'mass-get-content';
$myUserAgent = 'Mozilla/5.0 (compatible; ' . $scriptName . '/1.0; chapatiyaq@gmail.com)';

include '../wiki.php';
$wikiData = getWikiData('wiki');

// Other parameters
$name = isset($_POST['login']) ? $_POST['login'] : '';
$pass = isset($_POST['password']) ? $_POST['password'] : '';
$from = $_POST['from'];
$limit = $_POST['limit'];
$inputformat = $_POST['inputformat'];
$file = $_POST['file'];
$text = $_POST['text'];

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
?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
</head>
<body>
<h1><?php echo $scriptName; ?></h1>
<?php
echo 'Wiki: ' . $wikiData['name'] . ' &ndash; URL: ' . $cc['url'] . '<br>';

// Initialize a new cURL session
$curl = curl_init();
curl_setopt_array($curl, $cc['options']);
curl_setopt($curl, CURLOPT_URL, $cc['url']);

curl_setopt($curl, CURLOPT_POSTFIELDS, array(
	'action' => 'query',
	'prop' => 'revisions',
	'rvprop' => 'content',
	'titles' => implode('|', $titles),
	'format' => 'php'
));

$data = unserialize(curl_exec($curl));
$pages = $data['query']['pages'];
//echo '<h5>$pages</h5><pre>' . print_r($pages, true) . '</pre>';
//echo $page['title'] . ': ' . $page['edittoken'] . '<br/>';
//$token = $page['edittoken'];

echo '<table style="width: 100%;">';
echo '<tr><th>Title</th><th style="width: 100%;">Content</th></tr>';
foreach($pages as $page) {
	echo '<tr>';
	echo '<td style="white-space: nowrap;"><a href="' . $wikiData['scriptUrl'] . '?title=' . str_replace(' ', '_', $page['title']) . '">' . $page['title'] . '</a></td>';
	echo '<td><pre style="background-color: #f8f8f8; border: 1px solid #f0f0f0; width: 100%;">' . str_replace("\n", '\\n', htmlspecialchars($page['revisions'][0]['*'])) . '</pre></td>';
	echo '</tr>';
}
echo '</table>';
return;

curl_close($curl);
?>
</body>
</html>