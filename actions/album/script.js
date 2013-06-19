var css3Prefix = ['', '-webkit-', '-moz-', '-o-', '-ms-', 'ms-'];
Element.implement({
	getStyle3: function(style) {
		var result = '';
		for (var i = 0; i < css3Prefix.length; i++)
			if (result = this.getStyle(css3Prefix[i] + style))
				return result;
		return result;
	},
	setStyle3: function(style, value) {
		var styles = {};
		for (var i = 0; i < css3Prefix.length; i++)
			styles[css3Prefix[i] + style] = value;
		return this.setStyles(styles);
	}
});

// Add html5 video tag events on Mootools
Element.NativeEvents = Object.merge(Element.NativeEvents, {
	loadstart: 2, progress: 2, suspend: 2, abort: 2, error: 2, emptied: 2,
	stalled: 2, play: 2, pause: 2, loadedmetadata: 2, loadeddata: 2, waiting: 2,
	playing: 2, canplay: 2, canplaythrough: 2, seeking: 2, seeked: 2,
	timeupdate: 2, ended: 2, ratechange: 2, durationchange: 2, volumechange: 2
});

window.addEvent('domready', function() {

	if ($$('.album-admin-link').length)
		$$('.album-link,.album-admin-link').addEvents({
			mouseenter: function(e)
			{
				if (e.target.hasClass('album-admin-link'))
					var link = e.target.getPrevious('.album-link');
				else
					var link = e.target;
				if (link.linkHide)
					clearTimeout(link.linkHide);
				link.getNext('.album-admin-link').setStyle('display', 'block');
			},
			mouseout: function(e)
			{
				if (e.target.hasClass('album-admin-link'))
					var link = e.target.getPrevious('.album-link');
				else
					var link = e.target;
		
				var linkAdmin = link.getNext('.album-admin-link');
				link.linkHide = linkAdmin.setStyle.delay(0, linkAdmin, ['display', 'none']);
			}
		});

	new Simplegallery();
	new Simplegallery.Calendar();
});

var Simplegallery = new Class({
	Implements: [Options],
	options: {
		mediaActionTimeOut: 2000,
		slideShowSpeed: 5000
	},
	initialize: function(options)
	{
		this.mediaThumbs = $('thumbs');
		this.noMedia = $('noMedia');
		this.noThumb = $('noThumb');
	
		if (!this.mediaThumbs && this.noMedia)
			return this.noMedia.setStyle('display', 'block');
		if (this.noThumb)
			return;
		
		this.setOptions(options);
		
		this.blank = 'data:image/gif;base64,R0lGODlhAQABAIAAAP///////yH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==';
		this.locale = JSON.decode($('albumLocales').get('text'));
		this.mode = 'preview';

		this.thumb = null;
		
		if (!(this.container = $('album')))
			return;
		
		this.album = $('albumId').get('text');
		this.thumbCurrent = $('thumbCurrent');
		this.thumbShadow = $('thumbShadow');
		this.mediaAction = $('mediaAction');
			this.mediaSlideshowStart = $('mediaSlideshowStart');
			this.mediaSlideshowEnd = $('mediaSlideshowEnd');
			this.mediaPrevious = $('mediaPrevious');
			this.mediaNext = $('mediaNext');
			this.mediaSlideshowPlay = $('mediaSlideshowPlay');
			this.mediaSlideshowPauseStop = $('mediaSlideshowPauseStop');
			this.mediaSlideshowPause = $('mediaSlideshowPause');
			this.mediaSlideshowStop = $('mediaSlideshowStop');
			if (this.mediaUpdate = $('mediaUpdate')) {
				this.mediaUpdateRotateLeft = $('mediaRotateLeft');
				this.mediaUpdateRotateRight = $('mediaRotateRight');
				this.mediaUpdateFlipHorizontal = $('mediaFlipHorizontal');
				this.mediaUpdateFlipVertical = $('mediaFlipVertical');
				this.mediaUpdateDelete = $('mediaDelete');
			}
			this.mediaDownload = $('mediaDownload');
		
		this.mediaBackground = $('mediaBackground');
		this.mediaImage = $('mediaImage');
		this.mediaVideo = $('mediaVideo');
		
		this.albumsMenu = $('albumsMenu');
		this.albumsMenu.distanceTop = this.albumsMenu.getPosition().y + 10;
		if (this.albumAdmin = $('albumAdmin')) {
			this.albumAdminReorder = $('albumAdminReorder');
			this.albumAdminReorderValue = $('albumAdminReorderValue');
			this.albumAdmin.distanceTop = this.albumAdmin.getPosition().y;
		}
		
		if (!this.mediaVideo.pause)
			this.mediaVideo.pause = function(){};
		
		this.preview = $('preview');
		this.preview.distanceTop = this.preview.getPosition().y + 10;
		this.slideshow = $('slideshow');


		this.media = {
			name: '',
			type: '',
			width: 0,
			height: 0,
			orientation: 0,
			transform: ''
		};
		
		this.initEvents();
		
		if (this.thumbs) {
			var index = this.thumbs.get('href').indexOf('#' + new URI().get('fragment'));
			this.thumbs[index == -1 ? 0 : index].click();
		}

	},
	initEvents: function()
	{
		this.thumbs = $$('.thumb').addEvent('click', function(e) {
				this.mediaLoad(e.target);
		}.bind(this));

		this.mediaImage.fadeIn = new Fx.Tween(this.mediaImage, {
			property: 'opacity',
			link: 'cancel'
		});
		this.mediaVideo.fadeIn = new Fx.Tween(this.mediaVideo, {
			property: 'opacity',
			link: 'cancel',
			onComplete: function(video) {
				video.play();
			}
		});
		
		$$(this.mediaImage, this.mediaVideo).addEvents({
			load: function(src) {

				if (src.target)
					src = src.target.get('src');
				if (src && src[0] != '?')
					return;

				this.mediaElement.fadeIn.start(1);

				if (this.slideshow.play) {
					clearTimeout(this.slideshow.play);
					if (this.mode == 'slideshow' && this.media.type == 'image')
						this.slideshow.play = this.mediaLoad.delay(this.options.slideShowSpeed, this, [this.getNextThumb()]);
				}

			}.bind(this),
			mousemove: function() {
				this.setMediaActionHide();
				this.mediaAction.setStyle('display', 'block');
			}.bind(this),
			loadeddata: function(e) {
				e.target.fireEvent('load', [e.target.getElement('source').get('src')]);
			},
			ended: function() {
				if (this.slideshow.play && this.mode == 'slideshow')
					this.mediaLoad(this.getNextThumb());
			}.bind(this)
		});
		this.mediaSlideshowStart.addEvents({
			click: this.slideshowStart.bind(this)
		});
		this.mediaSlideshowEnd.addEvents({
			click: this.slideshowEnd.bind(this)
		});

		this.mediaAction.addEvents({
			mouseover: function() {
				this.setMediaActionHide();
			}.bind(this),
		});
		this.mediaPrevious.addEvent('click', function() {
			this.mediaLoad(this.getPreviousThumb());
		}.bind(this));
		this.mediaNext.addEvent('click', function() {
			this.mediaLoad(this.getNextThumb());
		}.bind(this));
		this.mediaSlideshowPlay.addEvent('click', this.slideshowPlay.bind(this));
		this.mediaSlideshowPause.addEvent('click', this.slideshowPause.bind(this));
		this.mediaSlideshowStop.addEvent('click', this.slideshowStop.bind(this));
		if (this.mediaUpdate) {
			this.mediaUpdate.addEvents({
				mouseover: function() {
					if (this.mediaActionHide)
						clearTimeout(this.mediaActionHide);
					this.mediaUpdate.getElement('ul').setStyle('display', 'block');
					return false;
				}.bind(this),
				mouseleave: function() {
					this.mediaUpdate.getElement('ul').setStyle('display', 'none');
				}.bind(this)
			});
			
			this.mediaUpdateRotateLeft.addEvent('click', function() {
				this.mediaSetUpdate('rotateLeft');
			}.bind(this));
			this.mediaUpdateRotateRight.addEvent('click', function() {
				this.mediaSetUpdate('rotateRight');
			}.bind(this));
			this.mediaUpdateFlipHorizontal.addEvent('click', function() {
				this.mediaSetUpdate('flipHorizontal');
			}.bind(this));
			this.mediaUpdateFlipVertical.addEvent('click', function() {
				this.mediaSetUpdate('flipVertical');
			}.bind(this));
			this.mediaUpdateDelete.addEvent('click', function() {
				if (confirm(this.locale['delete-confirm']))
					this.mediaSetUpdate('delete');
			}.bind(this));
			this.mediaUpdate.request = new Request.JSON();
		}

		this.slideshow.addEvents({
			click: this.slideshowEnd.bind(this)
		});

		if (this.albumAdminReorder) {
			
			this.thumbs.addEvents({
				mousedown: this.reorderStart.bind(this)
			});
		
			this.albumAdminReorder.addEvents({
				change: function(e) {
					this.reorder(e.target.get('checked'));
				}.bind(this)
			});
		}

		window.addEvents({
			scroll: this.scroll.bind(this),
			keydown: function(e) {
				if (['input', 'textarea'].contains(e.target.get('tag')))
					return;
				switch (e.key) {
					case 'left' :
						this.mediaLoad(this.getPreviousThumb());
					break;
					case 'right' :
						this.mediaLoad(this.getNextThumb());
					break;
					case 'up' :
					case 'down' :
						this.mediaLoad(this.getThumbGrid(e.key));
						e.preventDefault();
					break;
					case 'esc' :
						if (this.mode == 'slideshow')
							this.slideshowStop();
					break;
					case 'space' :
						if (this.media.type == 'video') {
							if (this.mediaVideo.paused)
								this.mediaVideo.play();
							else
								this.mediaVideo.pause();
						} else if (this.mode == 'slideshow') {
							if (this.slideshow.play)
								this.slideshowPause();
							else
								this.slideshowPlay();
						}
					break;
				}
			}.bind(this),
			resize: this.resize.bind(this),
			mousemove: this.reorderMove.bind(this),
			mouseup: this.reorderEnd.bind(this)
		});
		this.resize();

	},
	getNextThumb: function()
	{
		return this.thumb.getNext('a.thumb');
	},
	getPreviousThumb: function()
	{
		return this.thumb.getPrevious('a.thumb');
	},
	getThumbGrid: function(at)
	{
		var width = this.mediaThumbs.getSize().x;
		var thumbByLine = (width / 75).floor();
		var position = this.thumb.getPosition(this.mediaThumbs);
		var x = (position.x / 75).floor();
		var y = (position.y / 75).floor() + (at == 'up' ? -1 : 1 );
		var index = y * thumbByLine + x;
		return this.thumbs[index];
	},
	setMediaActionPosition: function()
	{
		this.mediaPrevious.setStyle('display', this.getPreviousThumb() ? 'block' : 'none');
		this.mediaNext.setStyle('display', this.getNextThumb() ? 'block' : 'none');
	
		if (this.mediaUpdate)
			$$(this.mediaUpdateRotateLeft, this.mediaUpdateRotateRight, this.mediaUpdateFlipHorizontal, this.mediaUpdateFlipVertical).setStyle('display', this.media.type == 'image' ? 'block' : 'none');
	
	
		var position = this.mediaElement.getPosition(this.mode == 'preview' ? this.container : null);
		var rotate = this.mediaGetRotation();
		this.mediaAction.setStyles({
			position: this.mode == 'preview' ? 'absolute' : 'fixed',
			width: this.mediaElement.size[rotate == 0 || rotate == 180 ? 'width' : 'height'] - 10,
			left: position.x + 6,
			top: position.y + 5
		});
	},
	setMediaActionHide: function()
	{
		if (this.mediaActionHide)
			clearTimeout(this.mediaActionHide);
		this.mediaActionHide = this.mediaAction.setStyle.delay(this.options.mediaActionTimeOut, this.mediaAction, ['display', 'none']);
	},
	
	// Load media in his html element
		// resize and transform (rotate/flip) the thumb and element
	mediaLoad: function(thumb)
	{
		if (this.reorderActive)
			return;
	
		this.mediaVideo.pause();
	
		if (!thumb) {
			if (this.slideshow.play)
				this.slideshowStop();
			return;
		}

		this.setMediaActionHide();

		this.thumb = thumb;

		this.media.name = this.thumb.get('title');
		this.media.url = this.thumb.get('mediaUrl');
		this.media.type = this.thumb.get('mediaType');
		this.media.order = this.thumb.get('mediaOrder').toInt();		
		this.media.width = this.thumb.get('mediaWidth').toInt();
		this.media.height = this.thumb.get('mediaHeight').toInt();
		this.media.rotation = this.thumb.get('mediaRotation').toInt();
		this.media.orientation = this.thumb.get('mediaOrientation').toInt();
		this.media.flip = this.thumb.get('mediaFlip').split(' ');
		this.media.flip = {
			horizontal: this.media.flip.contains('horizontal'),
			vertical: this.media.flip.contains('vertical')
		};
		this.media.transform = '';

		this.mediaImage.set('src', this.blank);
		this.mediaVideo.getElements('source').destroy();
		this.mediaVideo.load();

		this.mediaElement = this[this.media.type == 'image' ? 'mediaImage' : 'mediaVideo'];

		this[this.media.type == 'image' ? 'mediaVideo' : 'mediaImage'].setStyle('display', 'none');
		this.mediaElement.setStyles({
			display: 'block',
			opacity: 0
		});

		this.mediaSetTransform()

		var src = this.media.url + '&dim=' + this.mode;
		switch (this.media.type) {
			case 'image' :
				this.mediaElement.set('src', src);
			break;
			case 'video' :
				new Element('source', {src: src, type: 'video/webm'}).inject(this.mediaElement);
				this.mediaElement.load();
			break;
		}

		this.mediaDownload.set('href', this.media.url + '&download');

		this.resize();

	},
	mediaSetUpdate: function(update)
	{
		this.mediaUpdate.request.send({url: this.media.url + '&update=' + update});
		
		if (this.media.type == 'image') {
			switch (update) {
				case 'rotateLeft' :
				case 'rotateRight' :
					var direction = update == 'rotateLeft' ? -1 : 1;
					this.media.rotation+= direction * 90;
					if (this.media.rotation < 0)
						this.media.rotation = 270;
					else if (this.media.rotation > 270)
						this.media.rotation = 0;
					this.thumb.set('mediaRotation', this.media.rotation);
				break;
				case 'flipHorizontal' :
				case 'flipVertical' :
					var orientation = update == 'flipHorizontal' ? 'horizontal' : 'vertical';
					if (this.media.flip[orientation])
						delete this.media.flip[orientation];
					else
						this.media.flip[orientation] = true;

					var flip = [];
					if (this.media.flip['horizontal'])
						flip.push('horizontal');
					if (this.media.flip['vertical'])
						flip.push('vertical');
					this.thumb.set('mediaFlip', flip.join(' '));
				break;
			}
			
			this.mediaSetTransform();
			
			this.mediaElement.fireEvent('load', this.mediaElement.get('src'));
		}
		
		if (update == 'delete') {
			var media = this.getPreviousThumb();
			if (!media)
				media = this.getNextThumb();
			this.thumb.destroy();
			if (media)
				this.mediaLoad(media);
			else {
				$$('media', 'thumbs', 'albumAction').setStyle('display', 'none');
				$('noMedia').setStyle('display', 'block');
			}
		}
		
	},
	mediaSetSize: function()
	{
		var mode = this[this.mode];

		var size = mode.getSize();
		var angle = this.mediaGetRotation();
		
		var mediaRatio = (angle == 0 || angle == 180) ? this.media.width / this.media.height : this.media.height / this.media.width
		var x = mediaRatio > size.x / size.y;
		size = mode.size = size[x ? 'x' : 'y'];
	
		if (
			this.mode == 'slideshow' &&
			(this.media.width > this.media.height && (angle == 0 || angle == 180)) ||
			(this.media.height > this.media.width && (angle == 90 || angle == 270))
		) {
			size*= this.media.width / this.media.height;
		}

		if (size > 1000)
			size = 1000;

		this.mediaElement.size = {
			width: this.media.width > this.media.height ? size : size * this.media.width / this.media.height,
			height: this.media.width > this.media.height ? size * this.media.height / this.media.width : size
		};

		if (this.mode == 'slideshow') {
			if (angle == 0 || angle == 180)
				var sizeRef = this.mediaElement.size.height;
			else
				var sizeRef = this.mediaElement.size.width;
			this.mediaElement.size.marginTop = (mode.size - sizeRef) / 2;
		} else
			this.mediaElement.size.marginTop = 0;

		var transform = this.media.type == 'image' ? this.media.transform + ' translateX(' + this.mediaGetTransformLag() + 'px)' : 'none';

		this.mediaBackground.setStyles(this.mediaElement.size).setStyle3('transform', transform);

		this.mediaElement.setStyles({
			width: this.mediaElement.size.width,
			height: this.mediaElement.size.height
		});

		this.setMediaActionPosition();
	},
	mediaGetRotation: function()
	{
		var reg = /rotate\(([0-9]+)deg\)/;
		var deg = reg.exec(this.media.transform);
		if (!deg)
			return 0;
		
		deg = deg[1].toInt();
		var degree = deg%360;
		if (deg < 0)
			degree+= 360;
		
		return degree;

	},
	mediaGetTransformLag: function()
	{

		var degree = this.mediaGetRotation();
		var lag = 0;
		if (degree == 90 || degree == 270) {
			var thumbWidth, thumbHeight;
			thumbWidth = thumbHeight = this[this.mode].size;
			var width = this.media.width;
			var height = this.media.height;
			if (width > height)
				thumbHeight = thumbWidth * height / width;
			else
				thumbWidth = thumbHeight * width / height;

			var flipV = /scaleX\(-1\)/.test(this.media.transform);
			var lag = (thumbWidth-thumbHeight)/2;
			if ((degree == 90 && flipV) || (degree == 270 && !flipV))
				lag*= -1
		}
		return lag;

	},
	mediaSetTransform: function()
	{

		if (this.media.type == 'image') {
			var flip = {h:0,v:0};
			var rotation = this.media.rotation;
			switch (this.media.orientation) {
				default :
					rotation+= 0;
					flip.h = this.media.flip.horizontal ? -1 : 1;
					flip.v = this.media.flip.vertical ? -1 : 1;
				break;
				case 2 :
					rotation+= 0;
					flip.h = this.media.flip.horizontal ? 1 : -1;
					flip.v = this.media.flip.vertical ? -1 : 1;
				break;
				case 3 :
					rotation+= 180;
					flip.h = this.media.flip.horizontal ? -1 : 1;
					flip.v = this.media.flip.vertical ? -1 : 1;
				break;
				case 4 :
					rotation+= 0;
					flip.h = this.media.flip.horizontal ? -1 : 1;
					flip.v = this.media.flip.vertical ? 1 : -1;
				break;
				case 5 :
					rotation+= 90;
					flip.h = this.media.flip.horizontal ? -1 : 1;
					flip.v = this.media.flip.vertical ? 1 : -1;
				break;
				case 6 :
					rotation+= 90;
					flip.h = this.media.flip.horizontal ? -1 : 1;
					flip.v = this.media.flip.vertical ? -1 : 1;
				break;
				case 7 :
					rotation+= 270;
					flip.h = this.media.flip.horizontal ? -1 : 1;
					flip.v = this.media.flip.vertical ? 1 : -1;
				break;
				case 8 :
					rotation+= 270;
					flip.h = this.media.flip.horizontal ? -1 : 1;
					flip.v = this.media.flip.vertical ? -1 : 1;
				break;
			}
			this.media.transform = 'rotate(' + rotation + 'deg) scaleX(' + flip.h + ') scaleY(' + flip.v + ')';
		}
	
		this.thumb.setStyle3('transform', this.media.transform);
	
		this.mediaSetSize();
	},
	
	slideshowStart: function()
	{

		this.mode = 'slideshow';

		document.body.setStyle('overflow', 'hidden');

		this.mediaBackground.inject(this.slideshow).removeClass('mediaBackgroundPreview').addClass('mediaBackgroundSlideshow');
		this.slideshow.setStyle('display', 'block');

		this.mediaLoad(this.thumb);

		this.mediaSlideshowStart.setStyle('display', 'none');
		this.mediaSlideshowEnd.setStyle('display', 'block');
	
	},
	slideshowEnd: function()
	{
	
		if (this.slideshow.play)
			this.slideshowStop();

		this.mode = 'preview';
	
		this.mediaBackground.inject(this.preview).removeClass('mediaBackgroundSlideshow').addClass('mediaBackgroundPreview');
	
		this.slideshow.setStyle('display', 'none');
		this.mediaSlideshowStart.setStyle('display', 'block');
		this.mediaSlideshowEnd.setStyle('display', 'none');
		document.body.setStyle('overflow', 'auto');
		this.mediaLoad(this.thumb);
	},
	slideshowPlay: function()
	{
		this.slideshow.play = true;
		this.mediaSlideshowPlay.setStyle('display', 'none');
		this.mediaSlideshowPauseStop.setStyle('display', 'block');
		this.fullscreen(true);
		if (this.mode != 'slide')
			this.slideshowStart();
		else
			this.mediaLoad(this.thumb);
		
	},
	slideshowPause: function()
	{
		if (this.slideshow.play)
			clearTimeout(this.slideshow.play);
		this.slideshow.play = false;
		this.mediaSlideshowPauseStop.setStyle('display', 'none');
		this.mediaSlideshowPlay.setStyle('display', 'block');
	},
	slideshowStop: function()
	{
		this.slideshowPause();
		this.fullscreen(false);
		this.slideshowEnd();
	},
	fullscreen: function(active)
	{
		if (active) {
			if (document.documentElement.requestFullscreen)
				document.documentElement.requestFullscreen();
			else if (document.documentElement.mozRequestFullScreen)
				document.documentElement.mozRequestFullScreen();
			else if (document.documentElement.webkitRequestFullScreen)
				document.documentElement.webkitRequestFullScreen();
		} else {
			if (document.cancelFullScreen)
				document.cancelFullScreen();
			else if (document.webkitCancelFullScreen)
				document.webkitCancelFullScreen();
			else if (document.mozCancelFullScreen)
				document.mozCancelFullScreen();
		}
	},
	reorder: function(active)
	{
		if (this.reorderActive = active) {
			this.thumbCurrent.setStyles({
				width: 0,
				display: 'none'
			});
			this.thumbs.setStyle('cursor', 'move');
		} else {
			this.thumbCurrent.setStyle('width', 75);
			this.thumbs.setStyle('cursor', 'auto');
			this.resize();
		}
	},
	reorderStart: function(e)
	{
		if (!this.reorderActive)
			return false;
		
		this.reorderThumb = e.target;
		this.thumbShadow.setStyles({
			opacity: 0.7,
			display: 'block'
		}).grab(this.reorderThumb.clone());
		this.reorderMove(e);
	},
	reorderEnd: function()
	{
		if (!this.reorderActive || !this.reorderThumb || !this.reorderTarget)
			return;

		if (this.reorderTarget)
			this.reorderThumb.inject(this.reorderTarget, this.reorderTargetLeft ? 'before' : 'after');
		
		this.thumbShadow.setStyle('display', 'none').empty();
		this.reorderThumb = this.reorderTarget = false;
		this.thumbCurrent.setStyle('display', 'none');
		
		this.thumbs = $$('.thumb');
		var reorder = this.thumbs.get('title');
		this.albumAdminReorderValue.set('value', JSON.encode(reorder));
	},
	reorderMove: function(e)
	{
		if (!this.reorderActive || !this.reorderThumb)
			return;

		e.preventDefault();
		
		var position = this.mediaThumbs.getPosition();
		var size = this.mediaThumbs.getSize();
		
		position = {
			x: e.page.x - position.x - 37,
			y: e.page.y - position.y - 37
		};
		if (position.x < 0)
			position.x = 0;
		else if (position.x > size.x - 75)
			position.x = size.x - 75
			
		if (position.y < 0)
			position.y = 0;
		else if (position.y > size.y - 75)
			position.y = size.y - 75
		
		this.thumbShadow.setStyles({
			left: position.x,
			top: position.y
		});
		
		// Get media under mouse
		for (var i = 0, thumb; thumb = this.thumbs[i]; i++) {
		
			var position = thumb.getPosition();
			if (
				e.page.x < position.x ||
				e.page.y < position.y ||
				e.page.x > position.x + 75 ||
				e.page.y > position.y + 75 ||
				thumb.getStyle('position') == 'absolute'
			)
				continue;

			this.reorderTargetLeft = e.page.x <= position.x + 37;
			var position = (this.reorderTarget = thumb).getPosition(this.mediaThumbs);
			this.thumbCurrent.setStyles({
				left: position.x + (this.reorderTargetLeft ? 0 : 75) - 2,
				top: position.y - 2,
				display: 'block'
			});
			
			break;
		
		}

	},
	resize: function()
	{
		if (this.reorderActive)
			return;
	
		if (this.thumb) {
			var position = this.thumb.getPosition(this.mediaThumbs);
			this.thumbCurrent.setStyles({
				left: position.x - 2,
				top: position.y - 2,
				display: 'block'
			});
		}

		if (this.mode == 'slideshow')
			this.mediaSetSize();
			
		this.scroll();
	},
	scroll: function()
	{
		var documentHeight = document.getSize().y;
		
		var overflow = this.albumsMenu.getSize().y - documentHeight;
		if (overflow < 0)
			overflow = 0;
		if (window.getScroll().y - overflow > this.albumsMenu.distanceTop)
			this.albumsMenu.setStyles({
				position: 'fixed', 
				top: -overflow
			});
		else
			this.albumsMenu.setStyles({
				position: 'absolute', 
				top: 0
			});
		
		if (this.thumb) {
			overflow = this.preview.getSize().y - documentHeight;
			if (overflow < 0)
				overflow = 0;
			if (window.getScroll().y - overflow > this.preview.distanceTop)
				this.preview.setStyles({
					position: 'fixed', 
					top: -10 - overflow
				});
			else
				this.preview.setStyles({
					position: 'absolute', 
					top: 0
				});
			this.setMediaActionPosition();
		}
		
		if (this.albumAdmin) {
			overflow = this.albumAdmin.getSize().y - documentHeight;
			if (overflow < 0)
				overflow = 0;
			if (window.getScroll().y - overflow > this.albumAdmin.distanceTop)
				this.albumAdmin.setStyles({
					position: 'fixed', 
					top: -overflow
				});
			else
				this.albumAdmin.setStyles({
					position: 'absolute', 
					top: 0
				});
		}
	}
});

Simplegallery.Calendar = new Class({
	initialize: function()
	{

		this.dom = {};
		if (!(this.dom.container = $$('.albums-calendar')[0]))
			return;
		
		this.dom.albumDate = $('albumsCalendarAlbumDate');
		this.dom.tbody = $('albumsCalendar').getElement('tbody');
		this.dom.monthPrevious = $('albumsCalendarMonthPrevious');
		this.dom.monthNext = $('albumsCalendarMonthNext');
		this.dom.yearPrevious = $('albumsCalendarYearPrevious');
		this.dom.yearNext = $('albumsCalendarYearNext');
		this.dom.links = $('albumsCalendarLinks');
		this.dom.months = $$('.albums-calendar-month');
		this.dom.year = $('albumsCalendarYear');

		this.initEvents();
		
		this.albumDate = this.dom.albumDate.get('value');
		this.albumsDates = JSON.decode($('albumsCalendarAlbumDates').get('value'));
		
		this.goDate = new Date(this.albumDate);
		if (isNaN(this.goDate.getTime()))
			this.goDate = new Date()
		this.go();
	},
	initEvents: function()
	{
	
		this.dom.monthPrevious.addEvent('click', function() {
			this.go('previousMonth');
		}.bind(this));
		this.dom.monthNext.addEvent('click', function() {
			this.go('nextMonth');
		}.bind(this));
		this.dom.yearPrevious.addEvent('click', function() {
			this.go('previousYear');
		}.bind(this));
		this.dom.yearNext.addEvent('click', function() {
			this.go('nextYear');
		}.bind(this));
		
		this.dom.links.addEvents({
			mouseenter: function(e) {
				clearTimeout(this.linksHide);
			}.bind(this),
			mouseleave: function(e) {
				this.linksHide = this.dom.links.setStyle.delay(500, this.dom.links, ['display', 'none']);
			}.bind(this)
		});

	},
	go: function(action)
	{
		this.dom.tbody.empty();

		if (action == 'previousMonth')
			this.goDate.decrement('month');
		else if (action == 'nextMonth')
			this.goDate.increment('month');
		else if (action == 'previousYear')
			this.goDate.decrement('year');
		else if (action == 'nextYear')
			this.goDate.increment('year');

		var month = this.goDate.get('month') + 1;
		var firstDate = new Date(this.goDate.get('year') + '-' + String.from(month).pad(2, '0', 'left') + '-01');
		var firstDay = firstDate.get('day');
		if (firstDay == 0)
			firstDay = 7;
		var week = firstDate.get('week');
		var lastDay = firstDate.get('lastDayOfMonth');

		this.dom.months.setStyle('display', 'none');
		$$('.albums-calendar-month-' + month).setStyle('display', 'inline');
		this.dom.year.set('html', this.goDate.get('year'));

		var day = 1;	
		while (day <= lastDay) {
			var tr = new Element('tr').inject(this.dom.tbody);
			new Element('th[class=albums-calendar-week-number][html=' + week + ']').inject(tr);
			for (var i = 1; i < 8; i++) {
				var dayCurrent;
				var dateCurrent = new Date(firstDate);
				var classes = [];
				if (i < firstDay) {
					classes.push('albums-calendar-day-month-previous');
					dateCurrent.decrement('day', firstDay - i);
				} else if (day > lastDay) {
					classes.push('albums-calendar-day-month-next');
					dateCurrent.increment('month');
					dateCurrent.increment('day', day++ -lastDay - 1);
				} else {
					classes.push('day');
					dateCurrent.increment('day', day++ - 1);
				}

				var dateCurrentString = dateCurrent.format('%Y-%m-%d');
				if (dateCurrentString == new Date().format('%Y-%m-%d'))
					classes.push('albums-calendar-day-current');
				if (dateCurrentString == this.albumDate)
					classes.push('albums-calendar-day-album');

				var albums = this.albumsDates[dateCurrent.format('%Y-%m-%d')];
				var td = new Element('td[class=' + classes.join(' ') + ']').inject(tr);
				td.store('albums', albums);

				if (albums) {
					new Element('a[href=?album&id=' + albums[0].id + '][title=' + albums.length + ' album' + (albums.length > 1 ? 's' : '') + '][html=' + dateCurrent.get('date') + ']').inject(td);
					td.addEvents({
						mouseenter: function(e) {
							var albums = e.target.retrieve('albums');
							if (!albums)
								return;

							clearTimeout(this.linksHide);
							this.dom.links.empty();
							for (var i = 0, album; album = albums[i]; i++)
								new Element('a[html=' + album.name + '][href=?album&id=' + album.id + ']').inject(this.dom.links)
							
							var position = e.target.getPosition(this.dom.container);
							var size = e.target.getSize();
							
							this.dom.links.setStyles({
								display: 'block',
								left: position.x,
								top: position.y + size.y
							});
						}.bind(this),
						mouseleave: function() {
							this.linksHide = this.dom.links.setStyle.delay(500, this.dom.links, ['display', 'none']);
						}.bind(this)
					});
				} else
					new Element('span[html=' + dateCurrent.get('date') + ']').inject(td);
			}
			firstDay = 0;
			week++;
		}
	}
});
