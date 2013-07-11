<? if (!$code) : ?>
<form class="user box" action="?user=lost" method="post">
	<h2><?=l('user.lost-password._')?></h2>
	<table>
		<tr><th><?=l('user.login.mail')?> : </th><td class="user-field"><input name="mail" type="text" /></td></tr>
		<tr><td class="box-submit" colspan="2"><input type="submit" value="<?=l('user.lost-password.submit')?>" /></td></tr>
	</table>
	<div class="box-links">
		<a href="?"><?=l('cancel')?> ...</a>
	</div>
</form>
<? else : ?>
<form class="user" action="?user=lost&pcode=<?=toHtml($code)?>" method="post">
	<h2><?=l('user.lost-password.reset')?></h2>
	<table>
		<tr><th><?=l('user.profil.password')?> : </th><td class="user-field"><input name="password" type="password" /></td></tr>
		<tr><th><?=l('user.profil.password-retype')?> : </th><td class="user-field"><input name="password-check" type="password" /></td></tr>
		<tr><td class="box-submit" colspan="2"><input type="submit" value="<?=l('user.lost-password.submit')?>" /></td></tr>
	</table>
	<div class="box-links">
		<a href="?"><?=l('cancel')?> ...</a>
	</div>
</form>
<? endif; ?>
