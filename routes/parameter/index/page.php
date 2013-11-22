<form class="simpleform" method="post" action="?parameter">
	<div class="simpleform-caption"><?=$sg->l('parameter.index.title')?></div>
	<p>
		<label for="galleryName"><?=$sg->l('parameter.index.gallery-name')?> :</label><br />
		<input type="text" id="galleryName" name="galleryName" value="<?=$sg->parameters->getGalleryName()?>" />
	</p>
	<p>
		<label for="disableRegistration"><?=$sg->l('parameter.index.disable-registration')?> :</label><br />
		<input type="checkbox" id="disableRegistration" name="disableRegistration" value="1"<?=$sg->parameters->isDisableRegistration() ? ' checked="checked"' : ''?> />
	</p>
	<p>
		<label for="disableComments"><?=$sg->l('parameter.index.disable-comments')?> :</label><br />
		<input type="checkbox" id="disableComments" name="disableComments" value="1"<?=$sg->parameters->isDisableComments() ? ' checked="checked"' : ''?> />
	</p>
	<p class="simpleform-buttons">
		<input type="submit" value="<?=$sg->l('valid')?>" />
	</p>
</form>
