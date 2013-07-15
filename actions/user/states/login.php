<form class="user box" action="?user" method="post">
	<h2><?=l('user.login._')?></h2>
	<table>
		<tr><th><label for="userMail"><?=l('user.login.mail')?> :</label></th><td class="user-field"><input id="userMail" name="mail" type="text" autofocus="autofocus" /></td></tr>
		<tr><th><label for="userPassword"><?=l('user.login.password')?> :</label></th><td class="user-field"><input id="userPassword" name="password" type="password" /></td></tr>
		<tr><td class="box-submit" colspan="2"><input type="submit" value="<?=l('user.login.submit')?>" /></td></tr>
		<tr><td class="user-keep" colspan="2">
			<input type="checkbox" name="keep-connection" id="keepConnection" value="1"/><label for="keepConnection"><?=l('user.login.keep-connection')?></label>
		<td></tr>
	</table>
	<ul class="box-links">
		<li><a href="?user=lost"><?=l('user.lost-password._')?></a></li>
<? if (!get($sg->parameters->registration_disable)) : ?>
		<li><a href="?user=registration"><?=l('user.registration._')?></a></li>
<? endif; ?>
	</ul>
</form>
