<div id="albumLocale"><?=toHtml($sg->locale->lang)?></div>
<div id="albumLocales"><?=json_encode(array(
	'delete-confirm' => l('album.media.delete-confirm'),
	'me' => l('album.media.me'),
	'no-description' => l('album.media.no-description'),
	'comment-delete' => l('album.media.comment-delete'),
	'comment-delete-confirm' => l('album.media.comment-delete-confirm'),
	'date' => l('album.media.date')
))?></div>

<? if ($sg->albums) : ?>
<!-- Navigation zone -->
<div class="albums-menu-background"></div>
<div id="albumsMenu">

	<? if (!$sg->parameters->albums_calendar_disable) : ?>
<!-- Calendar zone -->
	<div class="albums-calendar">
		<input id="albumsCalendarAlbumDate" type="hidden" value="<?=toHtml(get($album, k('date_start')) | get($album, k('date_end')))?>" />
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
				style="padding-left:<?=$albumMenu->ns_depth * 15 + 5?>px;<?=get($_GET, k('id')) == $albumMenu->id ? 'background-color:rgba(119, 167, 197, 0.5);' : ''?>"
				href="?album&id=<?=toHtml($albumMenu->id)?>"
				title="<?
					$start = Locale::sdate($albumMenu->date_start);
					$end = Locale::sdate($albumMenu->date_end);
					if ($start and $end and $start != $end)
						echo $start . ' ' . l('album.date.at') . ' ' . $end;
					elseif ($start == $end)
						echo $start;
					else
						echo $start . $end;
					if (($start or $end) and $albumMenu->description != '')
						echo chr(10);
					echo toHtml($albumMenu->description);
					?>"
			><?=toHtml($albumMenu->name ? $albumMenu->name : basename($albumMenu->path))?></a>
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
	<form id="albumAdmin" method="post" action="?album=update&id=<?=toUrl($album->id)?>">
		<label for="albumAdminName"><?=l('album.name')?> :</label>
		<input id="albumAdminName" type="text" name="name" value="<?=toHtml($album->name)?>" />
		<label for="albumAdminDateStart"><?=l('album.date.start')?> :</label>
		<input id="albumAdminDateStart" type="date" placeholder="<?=l('date-format')?>" name="date-start" value="<?=toHtml($album->date_start)?>" />
		<label for="albumAdminDateEnd"><?=l('album.date.end')?> :</label>
		<input id="albumAdminDateEnd" type="date" placeholder="<?=l('date-format')?>" name="date-end" value="<?=toHtml($album->date_end)?>" />
		<label for="albumAdminDescription"><?=l('album.description')?> :</label>
		<textarea id="albumAdminDescription" name="description"><?=toHtml($album->description)?></textarea>
		<table>
		<? if (!$sg->parameters->albums_comments_disable) : ?>
			<tr>
				<td><input id="albumAdminComments" name="comments-disable" type="checkbox"<?=$album->comments_disable ? ' checked="checked"' : ''?>/></td>
				<td><label for="albumAdminComments"><?=l('admin.parameters.albums-comments-disable')?></label></td>
			</tr>
		<? endif; ?>
		<? if (!$sg->parameters->albums_medias_dates_disable) : ?>
			<tr>
				<td><input id="albumAdminMediasDates" name="medias-dates-disable" type="checkbox"<?=$album->medias_dates_disable ? ' checked="checked"' : ''?>/></td>
				<td><label for="albumAdminMediasDates"><?=l('admin.parameters.albums-medias-dates-disable')?></label></td>
			</tr>
		<? endif; ?>
			<tr>
				<td><input id="albumAdminReorder" type="checkbox" /><input type="hidden" name="reorder" id="albumAdminReorderValue" /></td>
				<td><label for="albumAdminReorder"><?=l('album.reorder')?></label></td>
			</tr>
		</table>
		
		<label for=""><?=l('album.groups')?> :</label>
		<table>
			<tr><th><?=l('album.inherited')?></th><th><?=l('album.forbidden')?></th><th><?=l('album.granted')?></th><th></th></tr>
		<? foreach ($album->groups as $group) : ?>
			<tr>
				<td class="album-admin-radio album-admin-radio-inherited-<?=$group->access_inherited == Simplegallery::ACCESS_GRANTED_INHERITED ? 'g' : 'f'?>">
					<input type="radio" name="access[<?=toHtml($group->id)?>]" value="<?=toHtml($group->access_inherited)?>" <?=$group->access < 0 ? 'checked="checked" ' : ''?>/>
				</td><td class="album-admin-radio album-admin-radio-f">
					<input type="radio" name="access[<?=toHtml($group->id)?>]" value="0" <?=$group->access == Simplegallery::ACCESS_FORBIDDEN ? 'checked="checked" ' : ''?>/>
				</td><td class="album-admin-radio album-admin-radio-g">
					<input type="radio" name="access[<?=toHtml($group->id)?>]" value="1" <?=$group->access == Simplegallery::ACCESS_GRANTED ? 'checked="checked" ' : ''?>/>
				</td><td>
					<?=toHtml($group->name)?>
				</td>
			</tr>
		<? endforeach; ?>
		</table>
		<div class="album-admin-button"><input type="submit" value="<?=l('apply')?>" /></div>
	</form>
	<? endif; ?>
	<? if (!$album->medias) : ?>
	<div id="noMedia">
		<?=l('album.no-media')?>
	</div>
	<? elseif ($noThumb) : ?>
	<div id="noThumb">
		<?=l('album.no-thumb')?>
	</div>
	<? else : ?>
	<div id="albumAction">
		<a id="albumActionDownload" href="?album&download=<?=toHtml($album->id)?>" target="_blank" title="<?=l('album.download')?>"></a>
	</div>
	
	<!-- Preview zone -->
	<div id="preview">
		<div id="mediaBackground" class="mediaBackgroundPreview">
			<img id="mediaImage"<?=
				(false and $admin and !get($sg->config->parameters, k('albums-tags-disable'))) ?
					' class="media-image-tag"' :
					''
				?> src="data:image/gif;base64,R0lGODlhAQABAIAAAP///////yH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==" />
			<video id="mediaVideo" preload="metadata" controls></video>
		<? if (false and !get($sg->config->parameters, k('albums-tags-disable'))) : ?>
			<div id="mediaTags"></div>
		<? endif; ?>
		</div>
	</div>
	
	<!-- Thumbnails zone -->
	<div id="thumbs"<?=$admin ? ' class="thumbs-adminactive"' : ''?>>
		<? foreach ($album->medias as $media) : ?>
		<a
			title="<?=toHtml($media->name)?>"
			class="thumb" 
			style="
				background:
					<?=$media->type == 'video' ? 'url(\'actions/album/images/media_video.png\') no-repeat bottom right,' : ''?>
					url('?media&amp;album=<?=toUrl($album->id)?>&amp;dim=thumb') no-repeat center -<?=($media->thumb_index-1) * 75?>px
				;
				<?=$media->styles?>
			"
			href="#<?=toUrl($media->id)?>"
			mediaUrl="?media=<?=toUrl($media->id)?>&amp;album=<?=toUrl($album->id)?>"
			mediaId="<?=toHtml($media->id)?>"
			mediaType="<?=toHtml($sg->getMediaType($media->file))?>"
			mediaOrder="<?=toHtml($media->position)?>"
			mediaWidth="<?=toHtml($media->width)?>"
			mediaHeight="<?=toHtml($media->height)?>"
			mediaOrientation="<?=toHtml($media->orientation)?>"
			mediaRotation="<?=toHtml($media->rotation)?>"
			mediaFlipHorizontal="<?=toHtml($media->flip_horizontal)?>"
			mediaFlipVertical="<?=toHtml($media->flip_vertical)?>"
			mediaDescription="<?=toHtml($media->description)?>"
			<? if (!$medias_dates_disable) : ?>
			mediaDate="<?=toHtml($media->date ? $media->date : $media->exif_date)?>"
			<? endif; ?>
			<? if (!$comments_disable) : ?>
			mediaComments="<?=toHtml(json_encode($media->comments))?>"
			<? endif; ?>
			<? if (false and !get($sg->config->parameters, k('albums-tags-disable'))) : ?>
			mediaTags="<?=toHtml(json_encode($media->data->tags))?>"
			<? endif; ?>
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
		<span id="mediaCount"></span>
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
	<div id="mediaBalloon">
		<div id="mediaBalloonPointer"></div>
		<? if ($admin) : ?>
			<? if (!$medias_dates_disable) : ?>
		<div>
			<?=l('album.media.date')?> <input type="datetime-local" placeholder="<?=l('datetime-format')?>" id="mediaDate" />
		</div>
			<? endif; ?>
		<div>
			<textarea id="mediaDescription" placeholder="<?=l('album.media.no-description')?>" cols="30" rows="2"></textarea>
		</div>
		<? else : ?>
			<? if (!$medias_dates_disable) : ?>
		<p id="mediaDate"></p>
			<? endif; ?>
		<p id="mediaDescription"></p>
		<? endif; ?>

<? /*
		<? if (false and !get($sg->config->parameters, k('albums-tags-disable'))) : ?>
		<div id="mediaTagsList"></div>
		<? endif; ?>
*/ ?>
		<? if (!$comments_disable) : ?>
		<div class="media-balloon-separator"></div>
		<label for="mediaComment"><?=l('album.media.comments')?> :</label><br />
		<textarea id="mediaComment" cols="30" rows="1"></textarea>
		<ul id="mediaComments"></ul>
		<? endif; ?>

	</div>
	<? endif; ?>
</div>
<? endif; ?>
