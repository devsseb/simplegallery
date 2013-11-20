<div id="loader">
	<? foreach ($response->data['albums'] as $album) :
		$margin = substr_count($album->getPath(), '/') * 20;
	?>
		<div class="album">
			<input type="hidden" name="id" value="<?=toHtml($album->getId())?>" />
			<input type="checkbox" class="album-analyze" value="<?=toHtml($album->getId())?>" />
			<div class="spacer" style="width:<?=$margin?>px"></div>
			<div class="album-names">
				<div class="album-basename"><?=toHtml(basename($album->getPath()) ?: '/')?></div>
				<input class="album-name" name="name" value="<?=toHtml($album->getName())?>" placeholder="<?=toHtml($album->getAutoName())?>" />
			</div>
		</div>
	<? endforeach; ?>
</div>
