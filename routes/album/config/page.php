<?
	$album = $response->data['album'];
?>
<form class="simpleform" method="post" action="?album=config&id=<?=toHtml($album->getId())?>">
	<div class="simpleform-caption">Configurating the album <?=toHtml($album->getPath())?></div>
	<p>
		<label for="name">Name : </label><br />
		<input type="text" name="name" placeholder="<?=toHtml($album->getAutoName())?>" value="<?=toHtml($album->getName())?>" />
	</p>
	
	<table class="album-groups">
		<tr><th title="Inherit">I</th><th title="Forbidden">F</th><th title="Granted">G</th><th>Group</th></tr>
<? foreach ($album->getGroupCollection() as $group) : ?>
		<tr>
			<td class="access-inherited-<?=$group->getAccess_inherited() == -1 ? 'granted' : 'forbidden'?>">
				<input type="radio" title="Access is <?=$group->getAccess_inherited() == -1 ? 'granted' : 'forbidden'?> by default" name="groups[<?=toHtml($group->getId())?>]" value="<?=$group->getAccess_inherited() == -1 ?: -2?>"<?=$group->getAccess() < 0 ? ' checked="checked"' : ''?> />
			</td>
			<td class="access-forbidden">
				<input type="radio" title="Access is forbidden" name="groups[<?=toHtml($group->getId())?>]" value="0"<?=$group->getAccess() == 0 ? ' checked="checked"' : ''?> />
			</td>
			<td class="access-granted">
				<input type="radio" title="Access is granted" name="groups[<?=toHtml($group->getId())?>]" value="1"<?=$group->getAccess() == 1 ? ' checked="checked"' : ''?> />
			</td>
			<td class="name-access-<?=($group->getAccess() == -1 or $group->getAccess() == 1) ? 'granted' : 'forbidden'?>">
				<?=toHtml($group->getName())?>
			</td>
		</tr>
<? endforeach; ?>
	</table>

	<p class="simpleform-buttons">
		<input type="submit" value="Valid" />
	</p>
</form>
