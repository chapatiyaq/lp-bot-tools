<?php
$scriptName = 'upload';

include '../wiki.php';
$wikiData = getWikiData('wiki');

// Other parameters
$name = $_POST['login'];
$pass = $_POST['password'];
$interval = intval($_POST['interval']);
$ignorewarnings = isset($_POST['ignorewarnings']) ? $_POST['ignorewarnings'] : false;
$from = $_POST['from'];
$limit = $_POST['limit'];
$inputformat = $_POST['inputformat'];
$file = $_POST['file'];
$text = $_POST['text'];

$myUserAgent = 'Mozilla/5.0 (compatible; ' . $scriptName . '/1.0; chapatiyaq@gmail.com)';

$cookieFile = '../cookies.tmp';
include '../cookies.php';

function uploadFile($localfilename, $filename, $comment, $text, $token, $cookies, $ignorewarnings) {
	global $myUserAgent, $wikiData;
 
	$handle = fopen($localfilename, "rb");
	$file_body = fread($handle, filesize($localfilename));
	fclose($handle);
 
	$destination = $wikiData['apiUrl'];
	$eol = "\r\n";
	$data = '';
	$header = ''; 
	$mime_boundary = '----' . md5(time());
 
	$params = array('action' => 'upload',
					//'bot' => 'true',
					'filename' => $filename,
					'text' => $text,
					'comment' => $comment,
					'ignorewarnings' => $ignorewarnings,
					'token' => $token,
					'format' => 'php');

	//parameters 	
	foreach ($params as $key => $value){
		$data .= '--' . $mime_boundary . $eol;
		$data .= 'Content-Disposition: form-data; name="' . $key . '"' . $eol;
		$data .= 'Content-Type: text/plain; charset=UTF-8' .  $eol;
		$data .= 'Content-Transfer-Encoding: 8bit' .  $eol . $eol;
		$data .= $value . $eol;
	}
 
	//file
	$data .= '--' . $mime_boundary . $eol;
	$data .= 'Content-Disposition: form-data; name="file"; filename="'.$filename.'"' . $eol; //Filename here
	$data .= 'Content-Type: application/octet-stream; charset=UTF-8' . $eol;
	$data .= 'Content-Transfer-Encoding: binary' . $eol . $eol;
	$data .= $file_body . $eol;
	$data .= "--" . $mime_boundary . "--" . $eol . $eol; // finish with two eol's
 
	//headers
	$header .= 'Authorization: Basic ' . base64_encode('dev:tldev') . $eol;
	$header .= 'User-Agent: ' . $myUserAgent . $eol;
	$header .= 'Content-Type: multipart/form-data; boundary=' . $mime_boundary . $eol;
	$header .= 'Host: ' . $wikiData['domain'] . $eol;
	$header .= 'Cookie: '. $cookies . $eol;
	$header .= 'Content-Length: ' . strlen($data) . $eol;
	$header .= 'Connection: Keep-Alive';

	$params = array('http' => array(
						'method' => 'POST',
						'header' => $header,
						'content' => $data
		));
 
	$ctx = stream_context_create($params);
	//echo '<pre>' . print_r($params, true) . '</pre>';
	$response = @file_get_contents($destination, FILE_TEXT, $ctx);
 
	return $response;
}

// *--
// -*- Get files
// --*
if ($inputformat == 'file') {
	$lines = file('./files/' . $file);
} else if ($inputformat == 'text') {
	$lines = explode("\n", $text);
} else {
	echo 'No input format selected';
	exit(4);
}
$files = array();
$fileCompleteTitles = array();
$i = 1;
foreach ($lines as $line) {
	list($title, $type, $source, $text) = explode("\t", $line, 4);
	switch($type) {
	case 'url':
	case 'file':
		$title = str_replace('_', ' ', $title);
		$files[$title] = array('type' => $type, 'source' => trim($source), 'text' => str_replace("\\n", "\n", trim($text)));
		$fileCompleteTitles[] = 'File:' . $title;
		break;
	default:
		echo 'Bad format at line:' . $i;
		exit(2);
	}
	++$i;
}
$files = array_slice($files, $from, $limit);
$fileCompleteTitles = array_slice($fileCompleteTitles, $from, $limit);
//echo '<pre>' . print_r($files, true) . '</pre>';
//echo '<pre>' . print_r($fileCompleteTitles, true) . '</pre>';
if (count($files) == 0) {
	echo 'No files to upload';
	exit(4);
}

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
// -*- Get file information
// --*
$postdata = http_build_query(array(
	'action' => 'query',
	'prop' => 'info',
	'intoken' => 'edit',
	'titles' => implode('|', $fileCompleteTitles),
	'format' => 'php'
));
curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
$data = unserialize(curl_exec($curl));
$pages = $data['query']['pages'];
//echo '<b>Pages:</b><pre>' . print_r($pages, true) . '</pre>';

echo '<ul>';
foreach ( $pages as $page ) {
	$filename = str_replace('File:', '', $page['title']);
	
	echo '<li><b>' . $filename . '</b> ';
	//continue;
	if ($files[$filename]['type'] == 'file') {
		//echo htmlspecialchars($files[$filename]['text']) . '<br/>';
		$phpResult = uploadFile(
			$files[$filename]['source'],
			$filename,
			'',
			$files[$filename]['text'],
			$token,
			implode(';', $cookieVars),
			$ignorewarnings
		);
		$data = unserialize($phpResult);
		echo 'Upload result: ';
		if (isset($data['upload']) && isset($data['upload']['result'])) {
			echo 'Result: ' . htmlspecialchars($data['upload']['result']);
		} else {
			echo 'Failure:<pre>' . print_r($data, true) . '</pre>';
		}
	}
	echo '</li>';
	usleep($interval);
}
echo '</ul>';

curl_close($curl);
?>
</body>
</html>