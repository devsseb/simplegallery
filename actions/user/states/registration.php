<form class="user" action="?user=registration" method="post">
	<h2 class="user-title">Registration</h2>
	<input type="hidden" name="registration" value="1" />
	<table>
		<tr><th>Name :</th><td class="user-field"><input name="name" type="text" /></td></tr>
		<tr><th>Mail :</th><td class="user-field"><input name="mail" type="text" /></td></tr>
		<tr><th>Login wanted :</th><td class="user-field"><input name="login" type="text" /></td></tr>
		<tr><th>Password :</th><td class="user-field"><input name="password" type="password" /></td></tr>
		<tr><th>Retype password :</th><td class="user-field"><input name="password-check" type="password" /></td></tr>
		<tr><td class="user-submit" colspan="2"><input type="submit" value="Register" /></td></tr>
	</table>
	<div class="user-links">
		<a href="?">Cancel</a>
	</div>
</form>
