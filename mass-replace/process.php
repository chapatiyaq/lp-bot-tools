<?php
// include the Diff class
require_once './class.Diff.php';

function sort_parameters_in_simple_template($original_text, $template, $param_list, &$count) {
	$parameters = explode('=', $param_list);
	$template_start = substr($template, 0, 1);
	$pattern_template = '['
	                    . strtolower($template_start)
	                    . strtoupper($template_start)
	                    . ']'
	                    . preg_replace('/[ _]/', '[ _]', substr($template, 1));
	/*$offset = 0;
	$level = 0;
	$preprocessed_text = $original_text;
	while (preg_match('/\{\{[^\{\}]*\}\}/', $preprocessed_text, $matches, PREG_OFFSET_CAPTURE, $offset)) {
		echo $matches[0][1] . '<br/>';
		$offset = $matches[0][1] + 1;
	}*/
	$preprocessed = false;
	$preprocessed_text = preg_replace_callback(
		'/(?s)\{\{([^\{\}]*)\}\}/',
		function ($matches) {
			return '<nowiki>@@' . str_replace('|', '[@!@]', $matches[1]) . '@@</nowiki>';
		},
		$original_text
	);
	$pattern = '/(?s)(\{\{\s*([tT]emplate:)*' . $pattern_template . '\s*)(\|((?!\}\}).)*)\}\}/';
	if (preg_match($pattern, $original_text) === false) {
		$preprocessed_text = $original_text;
	} else {
		$preprocessed = true;
	}
	$param_regex_pattern = '/(?s)\|[\s]*([^\s=\|\[\]\{\}][^=\|\[\]\{\}]*[^\s=\|\[\]\{\}]|[^\s=\|\[\]\{\}]|)[\s]*=[\s]*([^\|]*)/';
	$modified_text = preg_replace_callback(
		$pattern,
		function ($matches) use ($parameters, $param_regex_pattern) {
			preg_match_all($param_regex_pattern, $matches[3], $param_matches);
			//echo 'Param matches:';
			//echo '<pre>' . print_r($param_matches, true) . '</pre>';
			$param_output = '';
			$blank_space = false;
			foreach ($parameters as $param) {
				if ($param == '' && !$blank_space) {
					$param_output .= "\n";
					$blank_space = true;
				} else if (($index = array_search($param, array_reverse($param_matches[1]))) !== FALSE) {
					$index = count($param_matches[1]) - 1 - $index;
					$param_output .= preg_replace('/\n[\s]+$/', "\n", $param_matches[0][$index]);
					$blank_space = false;
				}
			}
			return $matches[1] . preg_replace('/\n[\s]+$/', "\n", $param_output) . '}}';
		},
		$preprocessed_text,
		1,
		$count
	);
	if ($preprocessed) {
		$modified_text = preg_replace_callback(
		'/(?s)<nowiki>@@(((?!@@).)*)@@<\/nowiki>/',
		function ($matches) {
			return '{{' . str_replace('[@!@]', '|', $matches[1]) . '}}';
		},
		$modified_text);
	}
	return $modified_text;
}

$scriptName = 'mass-replace';
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
foreach ($lines as $line) {
	list($title, $minor, $summary, $type, $p1, $p2, $p3, $p4) = explode("\t", rtrim($line, "\r\n"), 8);
	$edit = array();
	switch($type) {
	case 'str_replace':
		$edit = array(
			'minor' => (trim($minor) == "minor"),
			'summary' => trim($summary), 
			'replace_type' => 'STR_REPLACE',
			'condition' => false,
			'search' => $p1, 'replace' => $p2);
		break;
	case 'preg_replace':
		$edit = array(
			'minor' => (trim($minor) == "minor"),
			'summary' => trim($summary), 
			'replace_type' => 'PREG_REPLACE',
			'condition' => false,
			'regex_pattern' => $p1, 'regex_replacement' => $p2);
		break;
	case 'str_replace_then_preg_replace':
		$edit = array(
			'minor' => (trim($minor) == "minor"),
			'summary' => trim($summary), 
			'replace_type' => 'STR_REPLACE_THEN_PREG_REPLACE',
			'condition' => false,
			'search' => $p1, 'replace' => $p2,
			'regex_pattern' => $p3, 'regex_replacement' => $p4);
		break;
	case 'preg_replace_w_condition':
		$edit = array(
			'minor' => (trim($minor) == "minor"),
			'summary' => trim($summary), 
			'replace_type' => 'PREG_REPLACE',
			'condition' => array($p1, $p2), 'regex_pattern' => $p3, 'regex_replacement' => $p4);
		break;
	case 'sort_parameters_in_simple_template':
		$edit = array(
			'minor' => (trim($minor) == "minor"),
			'summary' => trim($summary), 
			'replace_type' => 'SORT_PARAMS_SIMPLE',
			'condition' => false,
			'template' => $p1, 'param_list' => $p2);
		break;
	default:
		echo 'Bad format';
		return;
	}
	if (!isset($edits[$title])) {
		$edits[$title] = array();
	}
	$edits[$title][] = $edit;
	unset($edit);

}

$titles = array_keys($edits);
$titles = array_slice($titles, $from, $limit);
$edits = array_intersect_key($edits, array_flip($titles));

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
	<link rel="stylesheet" type="text/css" href="style.css">
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

curl_setopt($curl, CURLOPT_POSTFIELDS, array(
	'action' => 'query',
	'prop' => 'info|revisions',
	'titles' => implode('|', $titles),
	'rvprop' => 'content',
	'redirects' => true,
	'format' => 'php'
));

$data = unserialize(curl_exec($curl));
$page_contents = $data['query']['pages'];
//echo '<pre>' . print_r($page_contents, true) . '</pre>';

if ( count($page_contents) == count($edits) ) {
	foreach ( $edits as $title => $title_edits ) {
		//echo $page['title'] . ': ' . $page['edittoken'] . '<br/>';

		curl_setopt($curl, CURLOPT_POSTFIELDS, array(
			'action' => 'query',
			'prop' => 'info',
			'intoken' => 'edit',
			'titles' => $title,
			'redirects' => true,
			'indexpageids' => true,
			'format' => 'php'
		));

		$data = unserialize(curl_exec($curl));
		$pages = $data['query']['pages'];
		$pageids = $data['query']['pageids'];

		$page = $page_contents[$pageids[0]];
		$page_edit = $pages[$pageids[0]];
		$token = $page_edit['edittoken'];
		
		// DISPLAY TITLE
		echo $title . ($page['title'] != $title ? ' → ' . $page['title'] : '') . '<br/>';
		// END DISPLAY TITLE

		$original_text = $page['revisions'][0]['*'];
		$modified_text = $original_text;
		$summaries = array();
		$minor = true;
		$count = 0;

		foreach ($title_edits as $edit) {
			if ($edit['condition'] && is_array($edit['condition'])) {
				$result = (preg_match($edit['condition'][0], $modified_text) == 1);
				$expected_result = ($edit['condition'][1] == 'false' ? false : true);
				$do_replace = ($result == $expected_result);
				if ($do_replace) {
					echo 'Condition OK<br/>';
				}
			} else {
				echo 'No condition<br>';
				$do_replace = true;
			}
			if ($do_replace) {
				if ($edit['replace_type'] == 'STR_REPLACE') {
					$modified_text = str_replace($edit['search'], str_replace("\\n", "\n", $edit['replace']), $modified_text, $count);
					echo $count . 'x STR_REPLACE ' . $edit['search'] . ' → ' . $edit['replace'] . '<br>';
				} else if ($edit['replace_type'] == 'PREG_REPLACE') {
					$modified_text = preg_replace($edit['regex_pattern'], str_replace("\\n", "\n", $edit['regex_replacement']), $modified_text, -1, $count);
					echo $count . 'x PREG_REPLACE ' . $edit['regex_pattern'] . ' → ' . $edit['regex_replacement'] . '<br>';
				} else if ($edit['replace_type'] == 'STR_REPLACE_THEN_PREG_REPLACE') {
					$modified_text = str_replace($edit['search'], str_replace("\\n", "\n", $edit['replace']), $modified_text, $count1);
					$modified_text = preg_replace($edit['regex_pattern'], str_replace("\\n", "\n", $edit['regex_replacement']), $modified_text, -1, $count2);
					$count = $count1 + $count2;
					echo $count1 . 'x + ' . $count2 . 'x STR_REPLACE_THEN_PREG_REPLACE ' . $edit['search'] . ' → ' . $edit['replace'] . ' then ' . $edit['regex_pattern'] . ' → ' . $edit['regex_replacement'] . '<br>';
				} else if ($edit['replace_type'] == 'SORT_PARAMS_SIMPLE') {
					$modified_text = sort_parameters_in_simple_template($modified_text, $edit['template'], $edit['param_list'], $count);
					echo $count . 'x SORT_PARAMS_SIMPLE ' . $edit['template'] . ' - ' . $edit['param_list'] . '<br>';
				}
				if ($count) {
					$summaries[] = $edit['summary'];
					$minor &= $edit['minor'];
				}
			}
		}
		$summary = implode(', ', array_filter($summaries));

		$postfields = array(
			'action' => 'edit',
			'bot' => 'true',
			'summary' => $summary,
			'title' => $page['title'],
			'token' => $token,
			'text' => $modified_text,
			'format' => 'php'
		);
		if ($minor) {
			$postfields['minor'] = 'true';
		} else {
			$postfields['notminor'] = 'true';
		}
		curl_setopt($curl, CURLOPT_POSTFIELDS, $postfields);

		if ($preview) {
			echo '<div>';
			if ($minor) {
				echo '<abbr title="Minor" style="font-weight: bold;">m</abbr>&nbsp;';
			}
			echo 'Summary: ' . $summary;
			echo '<div>';
			echo '<textarea style="width:50%; display:inline-block;" rows="3">' . htmlspecialchars($original_text) . '</textarea>';
			echo '<textarea style="width:50%; display:inline-block;" rows="3">' . htmlspecialchars($modified_text) . '</textarea>';
			echo '</div>';
			echo '<div>';
			echo Diff::toTable(Diff::compare($original_text, $modified_text), '', '');
			echo '</div></div>';
		}
		unset($modified_text);
		//var_dump(htmlspecialchars($edit));
		if (!$preview) {
			$data = unserialize(curl_exec($curl));
			//echo '<h5>$data</h5><pre>' . print_r($data, true) . '</pre>';
		}
		usleep($interval);
	}
} else {
	echo 'One or more page has not been found. From source: '. count($edits) . ' - Found: ' . count($page_contents);
	return;
}

curl_close($curl);
?>
</body>
</html>