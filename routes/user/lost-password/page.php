<form class="simpleform" method="post" action="?user=lost-password">
	<div class="simpleform-caption"><?=$sg->l('user.lost-password.title')?></div>
<? if (get($response->data, k('update'))) : ?>
	<p>
		<input type="hidden" name="pcode" value="<?=toHtml($response->data['update'])?>" ?>
		<label for="password"><?=$sg->l('user.password')?> :</label><br />
		<input type="password" id="password" name="password" />
	</p>
	<p>
		<label for="password-verification"><?=$sg->l('user.password-verification')?> :</label><br />
		<input type="password" id="password-verification" name="password-verification" />
	</p>
<? else : ?>
	<p>
		<label for="email"><?=$sg->l('user.email')?> :</label><br />
		<input type="email" id="email" name="email" value="<?=toHtml(get($_POST, k('email')))?>" />
	</p>
<? endif; ?>
	<p class="simpleform-buttons">
		<input type="submit" value="<?=$sg->l('valid')?>" />
	</p>
</form>
