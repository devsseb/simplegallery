<div id="albumLocales"><?=json_encode(array('delete-confirm' => l('album.media.delete-confirm')))?></div>

<? if ($sg->albums) : ?>
<!-- Navigation zone -->
<div class="albums-menu-background"></div>
<div class="albums-menu">
	<ul class="albums">
	<? foreach ($sg->albums as $albumMenu) : ?>
		<li>
			<a
				style="padding-left:<?=$albumMenu->depth * 15 + 5?>px;<?=get($_GET, k('id')) == $albumMenu->id ? 'background-color:rgba(119, 167, 197, 0.5);' : ''?>"
				href="?album&id=<?=toHtml($albumMenu->id)?>"
				title="<?
					$start = Locale::sdate($albumMenu->data->date->start);
					$end = Locale::sdate($albumMenu->data->date->end);
					if ($start and $end and $start != $end)
						echo $start . ' ' . l('album.date.at') . ' ' . $end;
					elseif ($start == $end)
						echo $start;
					else
						echo $start . $end;
					if (($start or $end) and $albumMenu->data->description != '')
						echo chr(10);
					echo toHtml($albumMenu->data->description);
					?>"
			><?=toHtml($albumMenu->data->name)?></a>
		</li>
	<? endforeach; ?>
	</ul>
</div>
<? else : ?>
<div id="noAlbum">
	<?=l('album.no-album')?>
</div>
<? endif; ?>
<? if ($id) : ?>
<div id="album">
	<div id="albumId" style="display:none;"><?=toHtml($album->id)?></div>
	<? if ($sg->user->admin) : ?>
	<!-- Update zone -->
	<form class="album-admin" method="post" action="?album&id=<?=toUrl($album->id)?>">
		<input type="hidden" name="id" value="<?=toHtml($album->id)?>" />
		<label for="albumAdminName"><?=l('album.name')?> :</label>
		<input id="albumAdminName" type="text" name="name" value="<?=toHtml(get($album->data, k('name')))?>" />
		<label for="albumAdminDateStart"><?=l('album.date.start')?> :</label>
		<input id="albumAdminDateStart" type="date" name="date-start" value="<?=toHtml($album->data->date->start)?>" />
		<label for="albumAdminDateEnd"><?=l('album.date.end')?> :</label>
		<input id="albumAdminDateEnd" type="date" name="date-end" value="<?=toHtml($album->data->date->end)?>" />
		<label for="albumAdminDescription"><?=l('album.description')?> :</label>
		<textarea id="albumAdminDescription" name="description"><?=toHtml(get($album->data, k('description')))?></textarea>
		<label for="albumAdminReorder"><?=l('album.reorder')?> : </label>
		<input id="albumAdminReorder" type="checkbox" />
		<input type="hidden" name="reorder" id="albumAdminReorderValue" />
		<label for=""><?=l('album.groups')?> :</label>
		<table>
			<tr><th><?=l('album.inherited')?></th><th><?=l('album.forbidden')?></th><th><?=l('album.granted')?></th><th></th></tr>
		<? foreach ($album->groups as $group => $access) :
			$accessInherited = $album->parentGroups ? ($album->parentGroups[$group] < 0 ? $album->parentGroups[$group] : $album->parentGroups[$group]-2) : -2;
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
	<div id="noMedia">
		<?=l('album.no-media')?>
	</div>
		<? if ($album->medias) : ?>
	<div id="albumAction">
		<a id="albumActionDownload" href="?album&download=<?=toHtml($album->id)?>" target="_blank" title="<?=l('album.download')?>"></a>
	</div>
	
	<!-- Preview zone -->
	<div id="preview">
		<div id="mediaBackground" class="mediaBackgroundPreview">
			<img id="mediaImage" src="data:image/gif;base64,R0lGODlhAQABAIAAAP///////yH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==" />
			<video id="mediaVideo" controls></video>
		</div>
	</div>
	
	<!-- Thumbnails zone -->
	<div id="thumbs">
		<?
		$thumbs = array_flip($album->data->thumbs->{'75-sprite.jpg'}->index);
		foreach ($album->medias as $media) :
			$index = get($thumbs, k($media->name), count($thumbs));
		?>
		<a
			title="<?=toHtml($media->name)?>"
			class="thumb" 
			style="
				background:
					<?=$media->type == 'video' ? 'url(\'actions/album/images/media_video.png\') no-repeat bottom right,' : ''?>
					url('?media&amp;album=<?=toUrl($album->id)?>&amp;dim=thumb') no-repeat center -<?=$index * 75?>px
				;
				<?=$media->styles?>
			"
			href="#<?=toUrl($media->name)?>"
			mediaUrl="?media&amp;album=<?=toUrl($album->id)?>&amp;media=<?=toUrl($media->name)?>"
			mediaType="<?=toHtml($sg->getMediaType($media->name))?>"
			mediaOrder="<?=toHtml($media->data->order)?>"
			mediaWidth="<?=toHtml($media->data->width)?>"
			mediaHeight="<?=toHtml($media->data->height)?>"
			mediaOrientation="<?=toHtml($media->data->orientation)?>"
			mediaRotation="<?=toHtml($media->data->rotation)?>"
			mediaFlip="<?=toHtml($media->data->flip)?>"
		></a>
		<? endforeach; ?>
		<div id="thumbCurrent"></div>
	</div>
	
	<!-- Slideshow zone -->
	<div id="slideshow"></div>
	
	<!-- Media action zone -->
	<div id="mediaAction">
		<a href="#" id="mediaPrevious" title="<?=l('album.media.previous')?>"></a>
		<a href="#" id="mediaNext" title="<?=l('album.media.next')?>"></a>
		<a href="#" id="mediaSlideshowPlay" title="<?=l('album.media.slideshow-play')?>"></a>
		<a href="#" id="mediaSlideshowStart" title="<?=l('album.media.slideshow-start')?>"></a>
		<a href="#" id="mediaSlideshowEnd" title="<?=l('album.media.slideshow-end')?>"></a>
		<a href="#" id="mediaDownload" title="<?=l('album.media.download')?>" target="_blank"></a>
		<div id="mediaSlideshowPauseStop">
			<a href="#" id="mediaSlideshowPause" title="<?=l('album.media.slideshow-pause')?>"></a>
			<a href="#" id="mediaSlideshowStop" title="<?=l('album.media.slideshow-stop')?>"></a>
		</div>
		<? if ($sg->user->admin) : ?>
		<div id="mediaUpdate">
			<ul>
				<li id="mediaRotateLeft" title="<?=l('album.media.rotate-left')?>"></li>
				<li id="mediaRotateRight" title="<?=l('album.media.rotate-right')?>"></li>
				<li id="mediaFlipHorizontal" title="<?=l('album.media.flip-horizontal')?>"></li>
				<li id="mediaFlipVertical" title="<?=l('album.media.flip-vertical')?>"></li>
				<li id="mediaDelete" title="<?=l('album.media.delete')?>"></li>
			</ul>
		</div>
		<? endif; ?>
	</div>
		<? endif; ?>
	<? endif; ?>
</div>
<? endif; ?>
