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
	new Simplegallery();
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
	
		if (!this.mediaThumbs && this.noMedia)
			return this.noMedia.setStyle('display', 'block');
		
		this.setOptions(options);
		
		this.blank = 'data:image/gif;base64,R0lGODlhAQABAIAAAP///////yH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==';
		this.locale = JSON.decode($('albumLocales').get('text'));
		this.mode = 'preview';

		this.thumb = null;
		
		if (!(this.container = $('album')))
			return;
		
		this.album = $('albumId').get('text');
		this.thumbCurrent = $('thumbCurrent');
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
			load: function(e, src) {

				if (!src && e)
					src = e.target.get('src');
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
				e.target.fireEvent('load', [e, e.target.getElement('source').get('src')]);
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
			this.mediaUpdate.request = new Request.JSON({
				
			});
		}

		this.slideshow.addEvents({
			click: this.slideshowEnd.bind(this)
		});

		window.addEvents({
			scroll: function() {
				if (window.getScroll().y >= this.preview.distanceTop)
					this.preview.setStyles({
						position: 'fixed', 
						top: -10
					});
				else
					this.preview.setStyles({
						position: 'absolute', 
						top: 0
					});
				this.setMediaActionPosition();
			}.bind(this),
			keydown: function(e) {
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
			resize: this.slideshowResize.bind(this)
		});
		this.slideshowResize();

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
		if (!thumb) {
			if (this.slideshow.play)
				this.slideshowStop();
			return;
		}

		this.setMediaActionHide();

		this.mediaVideo.pause();

		this.thumb = thumb;

		this.media.name = this.thumb.get('title');
		this.media.url = this.thumb.get('mediaUrl');
		this.media.type = this.thumb.get('mediaType');
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

		this.mediaElement = this[this.media.type == 'image' ? 'mediaImage' : 'mediaVideo'];

		this[this.media.type == 'image' ? 'mediaVideo' : 'mediaImage'].setStyle('display', 'none');
		this.mediaElement.setStyle('display', 'block');

		this.mediaSetTransform()

		switch (this.media.type) {
			case 'image' :
				this.mediaElement.set('src', this.media.url + '&dim=' + this.mode);
			break;
			case 'video' :
				new Element('source', {src: this.media.url, type: 'video/webm'}).inject(this.mediaElement);
//				this.mediaElement.set('src', this.media.url);
				this.mediaElement.load();
			break;
		}

		this.mediaDownload.set('href', this.media.url + '&download');

		var position = this.thumb.getPosition($('thumbs'));

		this.thumbCurrent.setStyles({
			left: position.x - 2,
			top: position.y - 2,
			display: 'block'
		});

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
			
			this.mediaElement.fireEvent('load');
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
		var x = this.media.width / this.media.height > size.x / size.y;
		var angle = this.mediaGetRotation();
		var horizontal = (this.media.width > this.media.height && (angle == 0 || angle == 180));	
		if (horizontal)
			x = !x;
		mode.size = size[x ? 'x' : 'y'];
		if (mode.size > 1000)
			mode.size = 1000;

		this.mediaElement.size = {
			width: this.media.width > this.media.height ? mode.size : mode.size * this.media.width / this.media.height,
			height: this.media.width > this.media.height ? mode.size * this.media.height / this.media.width : mode.size
		};
		var transform = this.media.type == 'image' ? this.media.transform + ' translateX(' + this.mediaGetTransformLag() + 'px)' : 'none';

		this.mediaBackground.setStyles(this.mediaElement.size).setStyle3('transform', transform);
		this.mediaElement.setStyles({
			width: this.mediaElement.size.width,
			height: this.mediaElement.size.height,
			opacity: 0
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
	slideshowResize: function()
	{
		if (this.mode != 'slideshow')
			return;

		this.mediaSetSize();

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
	}

});
