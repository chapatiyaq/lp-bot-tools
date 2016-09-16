<?php
include '../wiki.php';
$files = array_map('basename', glob('files/*.txt'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>mass-replace</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<link rel="stylesheet" type="text/css" href="../style.css">
</head>
<body>
	<a href="../">Home</a>
	<h1>mass-replace</h1>
	<form action="process.php" method="post">
		<div>
			<label for="wiki">Wiki: </label>
			<select name="wiki" id="wiki">
				<option value="0" label=" "></option>
			<?php foreach ($allowedWikis as $wiki) {
				echo '<option value="' . $wiki . '">' . $wiki . '</option>';
			} ?>
			</select>
		</div>
		<div>
			<label for="login">Login: </label>
			<input type="text" name="login" id="login"/>
		</div>
		<div>
			<label for="password">Password: </label>
			<input type="password" name="password" id="password"/>
		</div>
		<div>
			<label for="interval">Interval: </label>
			<input type="number" min="1000" name="interval" id="interval" value="1000000"/>
		</div>
		<div>
			<label for="preview">Preview mode: </label>
			<input type="checkbox" name="preview" id="preview" value="1"/>
		</div>
		<div>
			<label for="from">From index: </label>
			<input type="number" name="from" id="from" value="0"/>
		</div>
		<div>
			<label for="limit">Limit: </label>
			<input type="number" name="limit" id="limit" value="20"/>
		</div>
		<div>
			<div>
				<input type="radio" name="inputformat" value="file" id="inputformat-file" checked="1"/>
				<label for="inputformat-file">File: </label>
				<div class="container">
					<select name="file" id="file">
					<?php foreach ($files as $file) {
						echo '<option value="' . $file . '">' . $file . '</option>';
					} ?>
					</select>
				</div>
			</div>
			<div>
				<input type="radio" name="inputformat" value="text" id="inputformat-text"/>
				<label for="inputformat-text">Text: </label>
				<div class="container">
					<textarea name="text" id="text"></textarea>
				</div>
			</div>
		</div>
		<div class="info">
			Syntax: Page title[TAB]Minor[TAB]Edit summary[TAB]Type[TAB]P1[TAB]P2[TAB]P3[TAB]P4<br/>
			Minor should be left empty if the edit is not minor, and preferably set to 1 if minor.
			P3 and P4 can be unused depending on the type<br/>
			If Type is 'str_replace', then all occurences of P1 are replaced by P2.<br/>
			If Type is 'preg_replace', then a regular expression search and replace is performed, with P1 as the pattern and P2 as the replacement string.<br/>
			If Type is 'str_replace_then_preg_replace', both operations are performed one after another, with P1/P2 as the set of parameters for a simple search and replace and P3/P4 for the regular expression search and replace.<br/>
			If Type is 'preg_replace_w_condition', then a regular expression search and replace is performed, with P3 as the pattern and P4 as the replacement string only if the result of a regular expression search of pattern P1 as for result P2 ('false' or 'true').<br/>
			If Type is 'sort_parameters_in_simple_template', then the parameters for the template P1 are reordered with the order specified in P2 (list of parameters with separator '=').<br/>
			Tip (for Windows): ALT+09 to insert a [TAB] character in the textarea
		</div>
		<input type="submit" value="Submit"/>
	</form>
</body>
</html>