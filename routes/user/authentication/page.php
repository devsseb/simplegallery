<h1>Anthentication</h1>
<div class="form-container">
	<form method="post" action="?user=authentication">
		<p>
			<label for="email">Email :</label><br />
			<input type="email" id="email" name="email" value="<?=get($_POST, k('email'))?>" />
		</p>
		<p>
			<label for="password">Password :</label><br />
			<input type="password" id="password" name="password" />
		</p>
		<p>
			<label for="keep-connection">Keep connection</label>
			<label for="keep-connection" class="checkbox"><input type="checkbox" checked="checked" id="keep-connection" name="keep-connection" value="1" /></label>
		</p>
		<p class="form-control">
			<input type="submit" value="Valid" />
		</p>
		<p>
			<a href="?user=lost-password" class="form-link-right">Lost password</a>
			<a href="?user=registration" class="form-link-left">Registration</a>
		</p>
	</form>
</div>
