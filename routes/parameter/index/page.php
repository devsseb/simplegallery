<form class="simpleform" method="post" action="?parameter">
	<div class="simpleform-caption">Parameters</div>
	<p>
		<label for="galleryName">Gallery name :</label><br />
		<input type="text" id="galleryName" name="galleryName" value="<?=$sg->parameters->getGalleryName()?>" />
	</p>
	<p>
		<label for="disableRegistration">Disable registration :</label><br />
		<input type="checkbox" id="disableRegistration" name="disableRegistration" value="1"<?=$sg->parameters->isDisableRegistration() ? ' checked="checked"' : ''?> />
	</p>
	<p>
		<label for="disableComments">Disable comments :</label><br />
		<input type="checkbox" id="disableComments" name="disableComments" value="1"<?=$sg->parameters->isDisableComments() ? ' checked="checked"' : ''?> />
	</p>
	<p class="simpleform-buttons">
		<input type="submit" value="Valid" />
	</p>
</form>
