<?php
include('../wiki.php');
?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<link rel="stylesheet" type="text/css" href="../style.css">
</head>
<body>
	<a href="../">Home</a>
	<h1>create-nologo-team</h1>
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
			<label for "template-name">Team/ </label>
			<input type="text" name="template-name" id="template-name" value=""/>
		</div>
		<div>
			<label for "team-name">Team name: </label>
			<input type="text" name="team-name" id="team-name" value=""/>
		</div>
		<div>
			<label for "team-link">Team link (opt.): </label>
			<input type="text" name="team-link" id="team-link" value=""/>
		</div>
		<div>
			<label for "login">Login: </label>
			<input type="text" name="login" id="login"/>
		</div>
		<div>
			<label for "password">Password: </label>
			<input type="password" name="password" id="password"/>
		</div>
		<input type="submit" value="Submit"/>
	</form>
</body>
</html>