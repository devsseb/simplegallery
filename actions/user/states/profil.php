<form class="user" action="?user=profil" method="post">
	<h2 class="user-title">My profil</h2>
	<input type="hidden" name="profil" value="1" />
	<table>
		<tr><th>Name :</th><td class="user-field"><input name="name" type="text" value="<?=toHtml($sg->user->name)?>" /></td></tr>
		<tr><th>Mail :</th><td class="user-field"><input name="mail" type="text" value="<?=toHtml($sg->user->mail)?>" /></td></tr>
<? if (get($sg->user, k('mailUpdate'))) : ?>
		<tr><td colspan="2" class="user-awaiting-mail">Mail awaiting validation : <?=toHtml($sg->user->mailUpdate)?></th></tr>
<? endif; ?>
		<tr><td colspan="2" class="user-info">Leave password blank not to change </td></tr>
		<tr><th>Password :</th><td class="user-field"><input name="password" type="password" /></td></tr>
		<tr><th>Retype password :</th><td class="user-field"><input name="password-check" type="password" /></td></tr>
		<tr><td class="user-submit" colspan="2"><input type="submit" value="Update" /></td></tr>
	</table>
	<div class="user-links">
		<a href="?">Cancel</a>
	</div>
</form>
