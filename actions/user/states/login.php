<form class="user" action="?user&amp;pcode=<?=toUrl($code)?>" method="post">
	<h2 class="user-title"><?=l('user.login._')?></h2>
	<table>
		<tr><th><?=l('user.login.login')?> :</th><td class="user-field"><input name="login" type="text" /></td></tr>
		<tr><th><?=l('user.login.password')?> :</th><td class="user-field"><input name="password" type="password" /></td></tr>
		<tr><td class="user-submit" colspan="2"><input type="submit" value="<?=l('user.login.submit')?>" /></td></tr>
		<tr><td class="user-lost" colspan="2"><a href="?user=lost"><?=l('user.lost-password._')?> ...</a><td></tr>
	</table>
	<div class="user-links">
		<a href="?user=registration"><?=l('user.registration._')?></a>
	</div>
</form>
