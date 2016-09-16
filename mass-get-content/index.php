<?php
include '../wiki.php';
$files = array_map('basename', glob('files/*.txt'));
?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<link rel="stylesheet" type="text/css" href="../style.css">
</head>
<body>
	<a href="../">Home</a>
	<h1>mass-get-content</h1>
	<form action="process.php" method="post">
		<div>
			<label for="wiki">Wiki: </label>
			<select name="wiki" id="wiki">
				<option value="0"></option>
			<?php foreach ($allowedWikis as $wiki) {
				echo '<option value="' . $wiki . '">' . $wiki . '</option>';
			} ?>
			</select>
		</div>
		<div>
			<label for "login">Login: </label>
			<input name="login" id="login"/>
		</div>
		<div>
			<label for "password">Password: </label>
			<input type="password" name="password" id="password"/>
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
			Line syntax: Page title
		</div>
		<input type="submit" value="Submit"/>
	</form>
</body>
</html>