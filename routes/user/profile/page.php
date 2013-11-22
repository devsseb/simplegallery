<form class="simpleform" method="post" action="?user=profile">
	<div class="simpleform-caption"><?=$sg->l('user.profile.title')?></div>
	<p>
		<label for="email"><?=$sg->l('user.email')?> :</label>
<? if ($sg->user->getEmailUpdate()) : ?>
		<span class="label-help"><?=$sg->l('user.profile.email-awaiting-validation')?> : <?=toHtml($sg->user->getEmailUpdate())?></span>
<? endif; ?>
		<br />
		<input type="email" id="email" name="email" value="<?=toHtml($sg->user->getEmail())?>" />
	</p>
	<p>
		<label for="name"><?=$sg->l('user.name')?> :</label><br />
		<input type="text" id="name" name="name" value="<?=toHtml($sg->user->getName())?>" />
	</p>
	<p>
		<label for="password"><?=$sg->l('user.password')?> :</label><br />
		<input type="password" id="password" name="password" placeholder="<?=$sg->l('user.profile.leave-password-blank')?>" />
	</p>
	<p>
		<label for="password-verification"><?=$sg->l('user.password-verification')?> :</label><br />
		<input type="password" id="password-verification" name="password-verification" placeholder="<?=$sg->l('user.profile.leave-password-blank')?>" />
	</p>
	<p class="simpleform-buttons">
		<input type="submit" value="<?=$sg->l('valid')?>" />
	</p>
</form>
