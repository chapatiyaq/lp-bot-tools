<?php
include '../wiki.php';
$files = array_map('basename', glob('files/*.txt'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>mass-copy</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<link rel="stylesheet" type="text/css" href="../style.css">
</head>
<body>
	<a href="../">Home</a>
	<h1>mass-copy</h1>
	<form action="process.php" method="post">
		<div>
			<label for="wikifrom">Source wiki: </label>
			<select name="wikifrom" id="wikifrom">
				<option value="0" label=" "></option>
			<?php foreach ($allowedWikis as $wiki) {
				echo '<option value="' . $wiki . '">' . $wiki . '</option>';
			} ?>
			</select>
		</div>
		<div>
			<label for="wikito">Destination wiki: </label>
			<select name="wikito" id="wikito">
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
			<input type="number" min="1000" name="interval" id="interval" value="200000"/>
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
				<input type="radio" name="inputformat" value="file" id="inputformat-file" checked="checked"/>
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
			Syntax: Source title[TAB]Destination title[TAB]Edit summary<br/>
			Tip (for Windows): ALT+09 to insert a [TAB] character in the textarea
		</div>
		<input type="submit" value="Submit"/>
	</form>
</body>
</html>