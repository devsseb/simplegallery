<div id="albumLocales"><?=json_encode(array('delete-confirm' => l('album.media.delete-confirm')))?></div>

<? if ($sg->albums) : ?>
<!-- Navigation zone -->
<div class="albums-menu-background"></div>
<div id="albumsMenu">

	<? if (!get($sg->config->parameters, k('albums-calendar-disable'))) : ?>
<!-- Calendar zone -->
	<div class="albums-calendar">
		<input id="albumsCalendarAlbumDate" type="hidden" value="<?=toHtml($album->data->date->start | $album->data->date->end)?>" />
		<input id="albumsCalendarAlbumDates" type="hidden" value="<?=toHtml($dates)?>" />
		<table id="albumsCalendar">
			<thead>
				<tr>
					<th id="albumsCalendarYearPrevious">&lt;&lt;</th>
					<th id="albumsCalendarMonthPrevious">&lt;</th>
					<th colspan="4">
						<span id="albumsCalendarYear"></span><br/>
		<? for($i = 1 ; $i < 13 ; $i++) : ?>
						<span class="albums-calendar-month albums-calendar-month-<?=$i?>"><?=l('date.month.' . $i)?></span>
		<? endfor; ?>
						
					</th>
					<th id="albumsCalendarMonthNext">&gt;</th>
					<th id="albumsCalendarYearNext">&gt;&gt;</th>
				</tr>
				<tr>
					<th></th>
		<? for($i = 1 ; $i < 8 ; $i++) : ?>
					<th><?=substr(l('date.week.' . $i), 0, 1)?></th>
		<? endfor; ?>
				</tr>
			</thead>
			<tbody></tbody>
		</table>
		<div id="albumsCalendarLinks"></div>
	</div>
	<? endif; ?>
	<ul id="albums">
	<? foreach ($sg->albums as $albumMenu) : ?>
<!-- Albums list zone -->
		<li>
			<a class="album-link"
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
		<? if ($sg->user->admin) : ?>
			<a class="album-admin-link" href="?album=admin&id=<?=toHtml($albumMenu->id)?>"></a>
		<? endif; ?>
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
	<? if ($admin) : ?>
	<!-- Update zone -->
	<form id="albumAdmin" method="post" action="?album=admin&id=<?=toUrl($album->id)?>">
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
		<div class="album-admin-links">
			<a href="?album&amp;id=<?=$album->id?>&amp;generate-data"><?=l('album.generate-data')?></a>
		</div>
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
			<video id="mediaVideo" preload="metadata" controls></video>
		</div>
	</div>
	
	<!-- Thumbnails zone -->
	<div id="thumbs"<?=$admin ? ' class="thumbs-adminactive"' : ''?>>
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
		<? if ($admin) : ?>
		<div id="thumbShadow"></div>
		<? endif; ?>
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
		<? if ($admin) : ?>
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
