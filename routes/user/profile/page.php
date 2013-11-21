<form class="simpleform" method="post" action="?user=profile">
	<div class="simpleform-caption">My profile</div>
	<p>
		<label for="email">Email :</label>
<? if ($sg->user->getEmailUpdate()) : ?>
		<span class="label-help">Mail awaiting validation : <?=toHtml($sg->user->getEmailUpdate())?></span>
<? endif; ?>
		<br />
		<input type="email" id="email" name="email" value="<?=toHtml($sg->user->getEmail())?>" />
	</p>
	<p>
		<label for="name">Name :</label><br />
		<input type="text" id="name" name="name" value="<?=toHtml($sg->user->getName())?>" />
	</p>
	<p>
		<label for="password">Password :</label><br />
		<input type="password" id="password" name="password" placeholder="Leave password blank to not update it" />
	</p>
	<p>
		<label for="password-verification">Password verification :</label><br />
		<input type="password" id="password-verification" name="password-verification" placeholder="Leave password blank to not update it" />
	</p>
	<p class="simpleform-buttons">
		<input type="submit" value="Valid" />
	</p>
</form>
