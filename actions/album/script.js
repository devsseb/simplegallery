window.addEvent('domready', function() {
	new Simplegallery();
});

var Simplegallery = new Class({
	initialize: function()
	{
		if (!$('media')) {
			if ($('no-media'))
				$('no-media').setStyle('display', 'block');
			return;
		}
		
		this.blank = 'data:image/gif;base64,R0lGODlhAQABAIAAAP///////yH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==';
		
		this.locale = JSON.decode($('album-locales').get('text'));

		this.mediaImage = $('mediaImage').addEvents({
			load: function(e) {
				var position = e.target.getPosition($('media'));
				var size = e.target.getSize();
				var rotate = this.mediaGetRotation();
				this.mediaUpdate.setStyles({
					left: position.x + size[rotate == 0 || rotate == 180 ? 'x' : 'y'] - 29,
					top: position.y + 5
				});
			}.bind(this),
			mouseover: function() {
				if (this.mediaImageLeave)
					clearTimeout(this.mediaImageLeave);
				this.mediaUpdate.setStyle('display', 'block');
			}.bind(this),
			mouseleave: function() {
				this.mediaImageLeave = this.mediaUpdate.setStyle.delay(500, this.mediaUpdate, ['display', 'none']);
			}.bind(this)
		});
		this.mediaVideo = $('mediaVideo');
		this.mediaUpdate = $('mediaUpdate').addEvents({
			mouseover: function() {
				if (this.mediaImageLeave)
					clearTimeout(this.mediaImageLeave);
				$('mediaDownload').set('href', '?media&album=' + this.album + '&media=' + this.media.get('title') + '&update=download');
				this.mediaUpdateAction.setStyle('display', 'block');
			}.bind(this),
			mouseleave: function() {
				this.mediaUpdateAction.setStyle('display', 'none');
			}.bind(this)
		});
		$('mediaRotateLeft').addEvent('click', function() {
			this.loadMedia(this.media, 'rotateLeft');
		}.bind(this));
		$('mediaRotateRight').addEvent('click', function() {
			this.loadMedia(this.media, 'rotateRight');
		}.bind(this));
		$('mediaFlipHorizontal').addEvent('click', function() {
			this.loadMedia(this.media, 'flipHorizontal');
		}.bind(this));
		$('mediaFlipVertical').addEvent('click', function() {
			this.loadMedia(this.media, 'flipVertical');
		}.bind(this));
		$('mediaDelete').addEvent('click', function() {
			if (confirm(this.locale['delete-confirm']))
				this.loadMedia(this.media, 'delete');
		}.bind(this));
		this.mediaUpdateAction = $('mediaUpdateAction');
		this.album = $('album').get('text');
		this.media = this.mediaImage.get('title');
		this.mediaOver = $('thumb-current');
		
		window.addEvent('keyup', function(e) {
			switch (e.key) {
				case 'left' :
					this.loadMedia(this.media.getPrevious());
				break;
				case 'right' :
					this.loadMedia(this.media.getNext());
				break;
			}
		}.bind(this));
		
		this.loadMedia($$('.thumb').addEvent('click', function(e) {
			this.loadMedia(e.target);
			return false;
		}.bind(this))[0]);
		
		
	},
	loadMedia: function(media, update)
	{
		if (!media)
			return;
		
		this.media = media;

		$$(this.mediaImage, this.mediaVideo).set({
			src: this.blank,
			styles: {display: 'none'}
		});
		this.mediaUpdate.setStyle('display', 'none');

		switch (this.media.get('mediaType')) {
			case 'image' :
			
				switch (update) {
					case 'rotateLeft' :
					case 'rotateRight' :
						var direction = update == 'rotateLeft' ? -1 : 1;
						var rotation = this.media.get('mediaRotation').toInt() + direction * 90;
						if (rotation < 0)
							rotation = 270;
						else if (rotation > 270)
							rotation = 0;
						this.media.set('mediaRotation', rotation);
					break;
					case 'flipHorizontal' :
					case 'flipVertical' :
						var orientation = update == 'flipHorizontal' ? 'horizontal' : 'vertical';
						var flip = this.media.get('mediaFlip').split(' ');
						var index;
						if (-1 == (index = flip.indexOf(orientation)))
							flip.push(orientation);
						else
							flip.splice(index, 1);
						this.media.set('mediaFlip', flip.join(' '));
					break;
				}
			
				var transform = this.mediaGetTransform();
				this.media.setStyle('-webkit-transform', transform);

				// Repositionning after rotation
				transform+= ' translateX(' + this.mediaGetTransformLag(transform) + 'px)';

				this.mediaImage.set({
					src: this.media.get('href') + (update ? '&update=' + update : ''),
					styles: {
						display: 'block',
						WebkitTransform: transform
					}
				});
			break;
			case 'video' :
				this.mediaVideo.removeProperties(/*'width', */'height');
				if (this.media.get('mediaWidth') < this.media.get('mediaHeight'))
					this.mediaVideo.set('height', 500);
/*				else
					this.mediaVideo.set('width', 500);*/
				this.mediaVideo.set({
					src: '?media&album=' + this.album + '&media=' + this.media.get('title') + (update ? '&update=' + update : ''),
					styles: {display: 'block'}
				});
			break;
		}
		if (update) {
		
			if (update == 'delete') {
				var media = this.media.getPrevious();
				if (!media)
					media = this.media.getNext();
				this.media.destroy();
				if (media)
					this.loadMedia(media);
				else {
					$$('media', 'thumbs', 'album-action').setStyle('display', 'none');
					$('no-media').setStyle('display', 'block');
				}
			} else {
				var url = this.media.getStyle('background-image');
				this.media.setStyle('background-image', 'url(' + this.blank + ')').setStyle('background-image', url);
			}
		}

		
		var position = this.media.getPosition($('thumbs'));
		this.mediaOver.setStyles({
			left: position.x - 2,
			top: position.y - 2,
			display: 'block'
		});

	},
	mediaGetRotation: function(transform)
	{
		if (!transform && this.mediaImage.getStyle('display') != 'block')
			return 0;
		var reg = /rotate\(([0-9]+)deg\)/;
		var deg = reg.exec(transform ? transform : this.mediaImage.getStyle('-webkit-transform'));
		if (!deg)
			return 0;
		
		deg = deg[1].toInt();
		var degree = deg%360;
		if (deg < 0)
			degree+= 360;
		
		return degree;

	},
	mediaGetTransformLag: function(transform)
	{
		var degree = this.mediaGetRotation(transform);
		
		var lag = 0;
		if (degree == 90 || degree == 270) {
			var thumbWidth, thumbHeight;
			thumbWidth = thumbHeight = 500;
			var width = this.media.get('mediaWidth');
			var height = this.media.get('mediaHeight');
			if (width > height)
				thumbHeight = thumbWidth * height / width;
			else
				thumbWidth = thumbHeight * width / height;

			var flipV = /scaleX\(-1\)/.test(transform);
			var lag = (thumbWidth-thumbHeight)/2;
			if ((degree == 90 && flipV) || (degree == 270 && !flipV))
				lag*= -1
		}
		
		return lag;

	},
	mediaGetTransform: function()
	{
		var flip = this.media.get('mediaFlip').split(' ');
		flip = {
			h: flip.contains('horizontal'),
			v: flip.contains('vertical')
		};

		var rotation = this.media.get('mediaRotation').toInt();
		switch (this.media.get('mediaOrientation').toInt()) {
			default :
				rotation+= 0;
				flip.h = flip.h ? -1 : 1;
				flip.v = flip.v ? -1 : 1;
			break;
			case 2 :
				rotation+= 0;
				flip.h = flip.h ? 1 : -1;
				flip.v = flip.v ? -1 : 1;
			break;
			case 3 :
				rotation+= 180;
				flip.h = flip.h ? -1 : 1;
				flip.v = flip.v ? -1 : 1;
			break;
			case 4 :
				rotation+= 0;
				flip.h = flip.h ? -1 : 1;
				flip.v = flip.v ? 1 : -1;
			break;
			case 5 :
				rotation+= 90;
				flip.h = flip.h ? -1 : 1;
				flip.v = flip.v ? 1 : -1;
			break;
			case 6 :
				rotation+= 90;
				flip.h = flip.h ? -1 : 1;
				flip.v = flip.v ? -1 : 1;
			break;
			case 7 :
				rotation+= 270;
				flip.h = flip.h ? -1 : 1;
				flip.v = flip.v ? 1 : -1;
			break;
			case 8 :
				rotation+= 270;
				flip.h = flip.h ? -1 : 1;
				flip.v = flip.v ? -1 : 1;
			break;
		}
		
		return 'rotate(' + rotation + 'deg) scaleX(' + flip.h + ') scaleY(' + flip.v + ')';
	}

});
