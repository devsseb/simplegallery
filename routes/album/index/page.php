<? if ($response->data['album']) : ?>
<div id="albums">
<? foreach ($response->data['album']->getChildren()->findAllOrderPath() as $album) :
	$name = toHtml($album->getName()?:basename($album->getPath())) ?>
	<a class="album" href="?albums&amp;id=<?=toHtml($album->getId())?>">
		<div class="album-name"><?=$name?></div>
		<div class="album-cover">
	<?
		$covers = json_decode($album->getCoverMedias());
		$coverSize = object('width', 200, 'height', 180);
		$sprite = 0;
		if ($covers) :
			foreach ($covers as $size)
				$sprite+= $size->height;
			foreach (array_reverse((array)$covers) as $id => $size) :
				$media = $response->data['albumCovers'][$id];
				$rotate = $sg->getMediaTransform($media)->rotation;
				if ($rotate == 0 || $rotate == 180) {
					$left = $coverSize->width - $size->width;
					$top = $coverSize->height - $size->height;
				} else {
					$left = $coverSize->height - $size->height;
					$top = $coverSize->width - $size->width;
				}
				$rotate+= rand(-10, 10);
				$sprite-= $size->height;
	?>
			<div class="album-cover-media" style="
				left:<?=rand(0, floor($left / 2))?>px;
				top:<?=rand(0, floor($top / 2))?>px;
				-webkit-transform:rotate(<?=$rotate?>deg);
				-moz-transform:rotate(<?=$rotate?>deg);
				transform:rotate(<?=$rotate?>deg);
				background-image:url('?album=cover&amp;id=<?=$album->getId()?>');
				background-position:0 <?=-$sprite?>px;
				width:<?=$size->width?>px;
				height:<?=$size->height?>px;
			">
				<? if ($media->type == 'video') : ?>
				<div class="media-video-play-cover" style="left:<?=floor($size->width / 2 - 25)?>px;top:<?=floor($size->height / 2 - 25)?>px;<?=$sg->getAntiMediaCssTransform($media)?>"></div>
				<? endif; ?>
			</div>
	<?
			endforeach;
		else :
	?>
			No media found
	<? endif; ?>
		</div>
	</a>
<? endforeach; ?>
</div>
<div id="medias">
<? foreach ($response->data['medias'] as $index => $media) :
		$media = new \Database\Media($media);
		if (!$sg->user->isAdmin() and $media->isDeleted())
			continue;
		$rotate = $sg->getMediaTransform($media)->rotation;
		$width = ($rotate == 0 or $rotate == 180) ? $media->getWidth() : $media->getHeight();
		$height = ($rotate == 0 or $rotate == 180) ? $media->getHeight() : $media->getWidth();
?>
	<a
		class="media<?=$media->isDeleted() ? ' deleted' : ''?>"
		href="?media=slideshow&amp;id=<?=toHtml($media->getId())?>"
		style="width:<?=ceil($width * 200 / $height)?>px;height:200px;"
		mediaId="<?=toHtml($media->getId())?>"
		mediaIndex="<?=toHtml($index)?>"
		mediaType="<?=toHtml($media->getType())?>"
		mediaWidth="<?=toHtml($media->getWidth())?>"
		mediaHeight="<?=toHtml($media->getHeight())?>"
		mediaRotate="<?=toHtml($rotate)?>"
		mediaFlipHorizontal="<?=toHtml($media->getFlipHorizontal())?>"
		mediaFlipVertical="<?=toHtml($media->getFlipVertical())?>"
		mediaName="<?=toHtml(basename($media->getPath()))?>"
		mediaDate="<?=toHtml(basename($media->getDate()))?>"
		mediaExifDate="<?=toHtml($media->getExifDate())?>"
		mediaExif="<?=toHtml($media->getExifData())?>"
		mediaDeleted="<?=toHtml($media->isDeleted())?>"
	>
		<img src="?media=brick&amp;id=<?=toHtml($media->getId())?>" style="width:<?=ceil($media->getWidth() * 200 / $media->getHeight())?>px;height:200px;<?=$sg->getMediaCssTransform($media)?>" />
		<div class="deleted-border"></div>
	<? if ($media->getType() == 'video') : ?>
		<div class="media-video-play"></div>
	<? endif; ?>
		<ul class="media-tools">
	<? if ($sg->user->isAdmin()) : ?>
		<? if ($media->getType() == 'image') : ?>
			<li class="rotate-left" title="Rotate left"></li>
			<li class="rotate-right" title="Rotate right"></li>
			<li class="delete" title="<?=$media->isDeleted() ? 'Restore' : 'Delete'?>"></li>
		<? endif; ?>
	<? endif; ?>
		</ul>
	</a>
<? endforeach; ?>
</div>

<div id="slideshow">
	<div id="slideshow-media">
		<img id="slideshow-media-image" src="data:image/gif;base64,R0lGODlhAQABAIAAAP///////yH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==" />
		<video id="slideshow-media-video" preload="metadata" controls></video>
		<img id="slideshow-media-book" src="data:image/gif;base64,R0lGODlhAQABAIAAAP///////yH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==" />
	</div>
	<div id="slideshow-panel">
		<div id="slideshow-close"></div>
		<p id="slideshow-panel-name"></p>
		<ul id="slideshow-panel-tools">
<? if ($sg->user->isAdmin()) : ?>
			<li id="slideshow-panel-rotateLeft" class="rotate-left" title="Rotate left"></li>
			<li id="slideshow-panel-rotateRight" class="rotate-right" title="Rotate right"></li>
			<li id="slideshow-panel-delete" class="delete" title="Delete"></li>
<? endif; ?>
		</ul>
		<div id="slideshow-panel-date">
<? if ($sg->user->isAdmin()) : ?>
			<input type="datetime-local" />
<? endif; ?>
		</div>
		<div id="slideshow-panel-exif-title">Exif data
			<div id="slideshow-panel-exif-toogle"></div>
			<ul id="slideshow-panel-exif"></ul>
		</div>
		
	</div>
</div>
<? else : ?>
	<div class="noalbum">no album</div>
<? endif; ?>
