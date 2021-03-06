<?php
include '../wiki.php';
$files = array_map('basename', glob('files/*.txt'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>mass-protect</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<link rel="stylesheet" type="text/css" href="../style.css">
</head>
<body>
	<a href="../">Home</a>
	<h1>mass-protect</h1>
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
			<label for="edit">Edit protection: </label>
			<select name="edit" id="edit">
				<option value="all" selected="selected">Allow all users</option>
				<option value="autoconfirmed">Allow only editors</option>
				<option value="sysop">Allow only reviewers and administrators</option>
			</select>
		</div>
		<div>
			<label for="expiry">Expiry: </label>
			<input type="text" name="expiry" id="expiry"/>
		</div>
		<div class="info">
			Timestamp in <a href="http://www.gnu.org/software/tar/manual/html_node/Date-input-formats.html">GNU timestamp format</a>. Can be 'infinite', '1 day', 'next Monday 16:04:57'...
		</div>
		<div>
			<label for="reason">Reason: </label>
			<input type="text" name="reason" id="reason"/>
		</div>
		<div>
			<label for="interval">Interval: </label>
			<input type="number" min="1000" name="interval" id="interval" value="500000"/>
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
			Line syntax: Page title
		</div>
		<input type="submit" value="Submit"/>
	</form>
</body>
</html>