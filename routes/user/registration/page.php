<form class="simpleform" method="post" action="?user=registration">
	<div class="simpleform-caption">Registration</div>
	<p>
		<label for="email">Email :</label><br />
		<input type="email" id="email" name="email" value="<?=get($_POST, k('email'))?>" />
	</p>
	<p>
		<label for="name">Name :</label><br />
		<input type="text" id="name" name="name" value="<?=get($_POST, k('name'))?>" />
	</p>
	<p>
		<label for="password">Password :</label><br />
		<input type="password" id="password" name="password" />
	</p>
	<p>
		<label for="password-verification">Password verification :</label><br />
		<input type="password" id="password-verification" name="password-verification" />
	</p>
	<p class="simpleform-buttons">
		<input type="submit" value="Valid" />
	</p>
</form>
