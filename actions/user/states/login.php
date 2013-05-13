<form class="user" action="?user&amp;pcode=<?=toUrl($code)?>" method="post">
	<h2 class="user-title">Authentication</h2>
	<table>
		<tr><th>Login :</th><td class="user-field"><input name="login" type="text" /></td></tr>
		<tr><th>Password :</th><td class="user-field"><input name="password" type="password" /></td></tr>
		<tr><td class="user-submit" colspan="2"><input type="submit" value="Se connecter" /></td></tr>
		<tr><td class="user-lost" colspan="2"><a href="?user=lost">Lost password ...</a><td></tr>
	</table>
	<div class="user-links">
		<a href="?user=registration">Registration ...</a>
	</div>
</form>
