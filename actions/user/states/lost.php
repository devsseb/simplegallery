<? if (!$code) : ?>
<form class="user" action="?user=lost" method="post">
	<h2 class="user-title">Lost password</h2>
	<table>
		<tr><th>Login : </th><td class="user-field"><input name="login" type="text" /></td></tr>
		<tr><td class="user-submit" colspan="2"><input type="submit" value="Valider" /></td></tr>
	</table>
	<div class="user-links">
		<a href="?">Cancel ...</a>
	</div>
</form>
<? else : ?>
<form class="user" action="?user=lost&pcode=<?=toHtml($code)?>" method="post">
	<h2 class="user-title">Reset password</h2>
	<table>
		<tr><th>Password : </th><td class="user-field"><input name="password" type="password" /></td></tr>
		<tr><th>Retype password : </th><td class="user-field"><input name="password-check" type="password" /></td></tr>
		<tr><td class="user-submit" colspan="2"><input type="submit" value="Valider" /></td></tr>
	</table>
	<div class="user-links">
		<a href="?">Cancel ...</a>
	</div>
</form>
<? endif; ?>
