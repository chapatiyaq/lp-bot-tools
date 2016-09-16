<?php
$scriptName = 'create-nologo-team';
$myUserAgent = 'Mozilla/5.0 (compatible; ' . $scriptName . '/1.0; chapatiyaq@gmail.com)';

include '../wiki.php';
$wikiData = getWikiData('wiki');

// Other parameters
$templateName = $_POST['template-name'];
$teamName = $_POST['team-name'];
$teamLink = $_POST['team-link'];
$name = $_POST['login'];
$pass = $_POST['password'];

$cookieFile = '../cookies.tmp';
include '../cookies.php';

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

$postdata = http_build_query(array(
	'action' => 'query',
	'prop' => 'info|revisions',
	'intoken' => 'edit',
	'titles' => 'Template:Team/' . $templateName,
	'format' => 'php'
));
curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);

$data = unserialize(curl_exec($curl));
$pages = $data['query']['pages'];
$page = array_pop($pages);
echo $page['title'] . ': ' . $page['edittoken'] . '<br/>';
$token = $page['edittoken'];

$edit = '<span class="team-template-team-standard"><span class="team-template-image">[[File:Logo filler std.png|link={$link$}]]</span> <span class="team-template-text">{$namewithlink$}</span></span><noinclude>[[Category:Team Template Standard]]</noinclude>';
$vars = array();
if ( $teamLink != $teamName && $teamLink != '' ) {
	$vars['namewithlink'] = '[[' . $teamLink . '|' . $teamName . ']]';
	$vars['link'] = $teamLink;
} else {
	$vars['namewithlink'] = '[[' . $teamName . ']]';
	$vars['link'] = $teamName;
}
foreach ( $vars as $tag => $replacement ) {
	$edit = str_replace( '{$' . $tag. '$}', $replacement, $edit );
}

$postdata = http_build_query(array(
	'action' => 'edit',
	'bot' => 'true',
	'title' => $page['title'],
	'token' => $token,
	'text' => $edit,
	'format' => 'php'
));
curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);

echo $page['title'] . '<br/>';
echo htmlspecialchars($edit) . '<br/>';
$result = unserialize(curl_exec($curl));
echo '<h5>$result</h5><pre>' . print_r($result, true) . '</pre>';

curl_close($curl);
?>
</body>
</html>