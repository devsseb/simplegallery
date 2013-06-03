<form class="user" action="?user" method="post">
	<h2 class="user-title"><?=l('user.login._')?></h2>
	<table>
		<tr><th><?=l('user.login.mail')?> :</th><td class="user-field"><input name="mail" type="text" autofocus="autofocus" /></td></tr>
		<tr><th><?=l('user.login.password')?> :</th><td class="user-field"><input name="password" type="password" /></td></tr>
		<tr><td class="user-submit" colspan="2"><input type="submit" value="<?=l('user.login.submit')?>" /></td></tr>
		<tr><td class="user-lost" colspan="2"><a href="?user=lost"><?=l('user.lost-password._')?> ...</a><td></tr>
	</table>
<? if (!$sg->config->parameters->{'registration-disable'}) : ?>
	<div class="user-links">
		<a href="?user=registration"><?=l('user.registration._')?></a>
	</div>
<? endif; ?>
</form>
