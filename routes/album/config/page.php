<?
	$album = $response->data['album'];
?>
<h1>Album config<br /><?=toHtml($album->getPath())?></h1>
<div class="form-container">
	<form method="post" action="?album=config&id=<?=toHtml($album->getId())?>">
		<p>
			<label for="name">Name : </label><br />
			<input type="text" name="name" placeholder="<?=toHtml(basename($album->getPath()))?>" value="<?=toHtml($album->getName())?>" />
		</p>
		
		<table class="album-groups">
			<tr><th>I</th><th>F</th><th>G</th><th>Group</th></tr>
	<? foreach ($album->getGroupCollection() as $group) : ?>
			<tr>
				<td class="access-inherited-<?=$group->getAccess_inherited() == -1 ? 'granted' : 'forbidden'?>"><input type="radio" name="groups[<?=toHtml($group->getId())?>]" value="<?=$group->getAccess_inherited() == -1 ?: -2?>"<?=$group->getAccess() < 0 ? ' checked="checked"' : ''?> /></td>
				<td class="access-forbidden"><input type="radio" name="groups[<?=toHtml($group->getId())?>]" value="0"<?=$group->getAccess() == 0 ? ' checked="checked"' : ''?> /></td>
				<td class="access-granted"><input type="radio" name="groups[<?=toHtml($group->getId())?>]" value="1"<?=$group->getAccess() == 1 ? ' checked="checked"' : ''?> /></td>
				<td class="name-access-<?=($group->getAccess() == -1 or $group->getAccess() == 1) ? 'granted' : 'forbidden'?>"><?=toHtml($group->getName())?></td>
			</tr>
	<? endforeach; ?>
		</table>

		<p class="form-control">
			<input type="submit" value="Valid" />
		</p>
	</form>
</div>
