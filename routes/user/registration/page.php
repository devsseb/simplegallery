<h1>Registration</h1>
<div class="form-container">
	<form method="post" action="?user=registration">
		<p>
			<label for="mail">Mail :</label><br />
			<input type="mail" id="mail" name="email" value="<?=get($_POST, k('email'))?>" />
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
		<p class="form-control">
			<input type="submit" value="Valider" />
		</p>
		<p>
			<a href="?" class="form-link-right">Cancel</a>
		</p>
	</form>
</div>
