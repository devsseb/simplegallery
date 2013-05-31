<div id="album-locales"><?=json_encode(array('delete-confirm' => l('album.media.delete-confirm')))?></div>

<? if ($sg->albums) : ?>
<div class="albums-menu">
	<ul class="albums">
	<? foreach ($sg->albums as $albumMenu) : ?>
		<li>
			<a
				style="padding-left:<?=$albumMenu->depth * 15 + 5?>px;<?=get($_GET, k('id')) == $albumMenu->id ? 'background-color:rgba(119, 167, 197, 0.5);' : ''?>"
				href="?album&id=<?=toHtml($albumMenu->id)?>"
			><?=$albumMenu->name?></a>
		</li>
	<? endforeach; ?>
	</ul>
</div>
<? else : ?>
<div id="no-album">
	<?=l('album.no-album')?>
</div>
<? endif; ?>
<? if ($id) : ?>
<div class="album" id="album-container">
	<div id="album" style="display:none;"><?=toHtml($album->id)?></div>
	<? if ($sg->user->admin) : ?>
	<form class="album-admin" method="post" action="?album&id=<?=toUrl($album->id)?>">
		<input type="hidden" name="id" value="<?=toHtml($album->id)?>" />
		<div class="album-admin-title"><?=l('album.name')?> :</div>
		<input class="album-admin-name" type="text" name="name" value="<?=toHtml($album->name)?>" />
		<div class="album-admin-title"><?=l('album.groups')?> :</div>
		<table>
			<tr><th><?=l('album.inherited')?></th><th><?=l('album.forbidden')?></th><th><?=l('album.granted')?></th><th></th></tr>
		<? foreach ($album->groups as $group => $access) :
			$accessInherited = $album->parent ? ($album->parent->groups[$group] < 0 ? $album->parent->groups[$group] : $album->parent->groups[$group]-2) : -2;
		?>
			<tr>
				<td class="album-admin-radio album-admin-radio-inherited-<?=$accessInherited == -2 ? 'f' : 'g'?>">
					<input type="radio" name="access[<?=toHtml($group)?>]" value="<?=toHtml($accessInherited)?>" <?=$access < 0 ? 'checked="checked" ' : ''?>/>
				</td><td class="album-admin-radio album-admin-radio-f">
					<input type="radio" name="access[<?=toHtml($group)?>]" value="0" <?=$access == 0 ? 'checked="checked" ' : ''?>/>
				</td><td class="album-admin-radio album-admin-radio-g">
					<input type="radio" name="access[<?=toHtml($group)?>]" value="1" <?=$access == 1 ? 'checked="checked" ' : ''?>/>
				</td><td>
					<?=toHtml($group)?>
				</td>
			</tr>
		<? endforeach; ?>
		</table>
		<div class="album-admin-button"><input type="submit" value="<?=l('apply')?>" /></div>
	</form>
	<? endif; ?>
	<? if (exists($album->data, 'thumbs') or !$album->medias) : ?>
	<div id="no-media">
		<?=l('album.no-media')?>
	</div>
		<? if ($album->medias) : ?>
	<div id="album-action">
		<a id="album-action-download" href="?album&download=<?=toHtml($album->id)?>" target="_blank" title="<?=l('album.download')?>"></a>
	</div>
	<div class="media" id="media">
		<img class="media-loading" src="actions/album/loading.gif" alt="<?=l('album.loading')?>" />
		<img id="mediaImage" src="actions/album/blank.gif" />
		<video id="mediaVideo" controls src="" width="500"></video>
		<div id="mediaUpdate">
			<ul id="mediaUpdateAction">
				<li title="<?=l('album.media.download')?>"><a id="mediaDownload" target="_blank" href="#"></a></li>
				<li id="mediaRotateLeft" title="<?=l('album.media.rotate-left')?>"></li>
				<li id="mediaRotateRight" title="<?=l('album.media.rotate-right')?>"></li>
				<li id="mediaFlipHorizontal" title="<?=l('album.media.flip-horizontal')?>"></li>
				<li id="mediaFlipVertical" title="<?=l('album.media.flip-vertical')?>"></li>
				<li id="mediaDelete" title="<?=l('album.media.delete')?>"></li>
			</ul>
		</div>
	</div>
	<div class="thumbs" id="thumbs">
		<?
		$thumbs = array_flip($album->data->thumbs->{'75-sprite.jpg'}->index);
		foreach ($album->medias as $media) :
			$index = get($thumbs, k($media->name), count($thumbs));
		?>
		<a
			title="<?=toHtml($media->name)?>"
			class="thumb" 
			style="
				background:transparent url('?media&amp;album=<?=toUrl($album->id)?>&amp;dim=thumb') no-repeat center -<?=$index * 75?>px;
				<?=$media->styles?>
			"
			href="?media&amp;album=<?=toUrl($album->id)?>&amp;media=<?=toUrl($media->name)?>&amp;dim=play"
			mediaType="<?=toHtml($sg->getMediaType($media->name))?>"
			mediaWidth="<?=toHtml(get($media, k('data', 'width')))?>"
			mediaHeight="<?=toHtml(get($media, k('data', 'height')))?>"
			mediaOrientation="<?=toHtml(get($media, k('data', 'orientation')))?>"
			mediaRotation="<?=toHtml(get($media, k('data', 'rotation')))?>"
			mediaFlip="<?=toHtml(get($media, k('data', 'flip')))?>"
		></a>
		<? endforeach; ?>
		<div id="thumb-current"></div>
	</div>
		<? endif; ?>
	<? endif; ?>
</div>
<? endif; ?>
