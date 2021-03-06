<?
	$album = $response->data['album'];
?>
<form class="simpleform" method="post" action="?album=config&id=<?=toHtml($album->getId())?>">
	<div class="simpleform-caption"><?=$sg->l('album.config.title', $album->getPath())?></div>
	<p>
		<label for="name"><?=$sg->l('album.config.name')?> : </label><br />
		<input type="text" name="name" placeholder="<?=toHtml($album->getAutoName())?>" value="<?=toHtml($album->getName())?>" />
	</p>
	<p>
		<label for="disableComments"><?=$sg->l('album.config.disable-comments')?> :</label><br />
		<input type="checkbox" id="disableComments" name="disableComments" value="1"<?=$album->isDisableComments() ? ' checked="checked"' : ''?> />
	</p>
	
	<table class="album-groups">
		<tr><th title="<?=$sg->l('album.config.inherit')?>"><?=$sg->l('album.config.i')?></th><th title="<?=$sg->l('album.config.forbidden')?>"><?=$sg->l('album.config.f')?></th><th title="<?=$sg->l('album.config.granted')?>"><?=$sg->l('album.config.g')?></th><th><?=$sg->l('album.config.group')?></th></tr>
<? foreach ($album->getGroupCollection() as $group) : ?>
		<tr>
			<td class="access-inherited-<?=$group->getAccess_inherited() == -1 ? 'granted' : 'forbidden'?>">
				<input type="radio" title="<?=$sg->l('album.config.access-' . ($group->getAccess_inherited() == -1 ? 'granted' : 'forbidden') . '-default')?>" name="groups[<?=toHtml($group->getId())?>]" value="<?=$group->getAccess_inherited() == -1 ?: -2?>"<?=$group->getAccess() < 0 ? ' checked="checked"' : ''?> />
			</td>
			<td class="access-forbidden">
				<input type="radio" title="<?=$sg->l('album.config.access-forbidden')?>" name="groups[<?=toHtml($group->getId())?>]" value="0"<?=$group->getAccess() == 0 ? ' checked="checked"' : ''?> />
			</td>
			<td class="access-granted">
				<input type="radio" title="<?=$sg->l('album.config.access-forbidden')?>" name="groups[<?=toHtml($group->getId())?>]" value="1"<?=$group->getAccess() == 1 ? ' checked="checked"' : ''?> />
			</td>
			<td class="name-access-<?=($group->getAccess() == -1 or $group->getAccess() == 1) ? 'granted' : 'forbidden'?>">
				<?=toHtml($group->getName())?>
			</td>
		</tr>
<? endforeach; ?>
	</table>

	<p class="simpleform-buttons">
		<input type="submit" value="<?=$sg->l('valid')?>" />
	</p>
</form>
