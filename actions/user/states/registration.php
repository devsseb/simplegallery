<form class="user" action="?user=registration" method="post">
<? if ($code) : ?>
	<h2 class="user-title"><?=l('user.registration.new-password')?></h2>
	<table>
		<tr><th><?=l('user.profil.password')?> :</th><td class="user-field"><input name="password" type="password" /></td></tr>
		<tr><th><?=l('user.profil.password-retype')?> :</th><td class="user-field"><input name="password-check" type="password" /></td></tr>
		<tr><td class="user-submit" colspan="2">
			<input type="hidden" name="rcode" value="<?=toHtml($code)?>" />
			<input type="submit" value="<?=l('user.registration.password-create')?>" />
		</td></tr>
	</table>
<? else : ?>
	<h2 class="user-title"><?=l('user.registration._')?></h2>
	<table>
		<tr><th><?=l('user.profil.name')?> :</th><td class="user-field"><input name="name" type="text" /></td></tr>
		<tr><th><?=l('user.profil.mail')?> :</th><td class="user-field"><input name="mail" type="text" /></td></tr>
		<tr><th><?=l('user.profil.password')?> :</th><td class="user-field"><input name="password" type="password" /></td></tr>
		<tr><th><?=l('user.profil.password-retype')?> :</th><td class="user-field"><input name="password-check" type="password" /></td></tr>
		<tr><td class="user-submit" colspan="2"><input type="submit" value="<?=l('user.registration.register')?>" /></td></tr>
	</table>
<? endif; ?>
	<div class="user-links">
		<a href="?"><?=l('cancel')?></a>
	</div>
</form>
