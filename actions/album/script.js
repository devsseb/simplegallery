/*TODO
	test delete
*/
var css3Prefix = ['', '-webkit-', '-moz-', '-o-', '-ms-'];
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
		if (!$('thumbs') && $('noMedia'))
			return $('noMedia').setStyle('display', 'block');
		
		this.setOptions(options);
		
		this.blank = 'data:image/gif;base64,R0lGODlhAQABAIAAAP///////yH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==';
		this.locale = JSON.decode($('albumLocales').get('text'));
		this.mode = 'preview';

		this.thumb = null;
		
		this.container = $('album');
		this.album = $('albumId').get('text');
		this.thumbOver = $('thumbCurrent');
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
		this.mediaLoading = $('mediaLoading');
		
		this.preview = $('preview');
		this.preview.image = $('previewImage');
		this.preview.video = $('previewVideo');
		this.preview.size = 500;
		this.preview.distanceTop = this.preview.getPosition().y + 10;

		this.slideshow = $('slideshow');
		this.slide = $('slide');
		this.slide.image = $('slideImage');
		this.slide.video = $('slideVideo');
		this.slide.size = 1000;

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
		
		$$(this.preview.image, this.preview.video, this.slide.image, this.slide.video).addEvents({
			load: function(e) {
				this.setMediaActionPosition();

				this.mediaDownload.set('href', this.media.url + '&download');

				if (this.slideshow.play) {
					clearTimeout(this.slideshow.play);
					if (this.mode == 'slide' && this.media.type == 'image')
						this.slideshow.play = this.mediaLoad.delay(this.options.slideShowSpeed, this, [this.getNextThumb()]);
				}
					
				
			}.bind(this),
			mousemove: function() {
				this.setMediaActionHide();
				this.mediaAction.setStyle('display', 'block');
			}.bind(this),
			canplay: function(e) {
				e.target.fireEvent('load', [e]);
				e.target.play();
			},
			ended: function() {
				if (this.slideshow.play && this.mode == 'slide')
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
				if (window.scrollY >= this.preview.distanceTop)
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
			keyup: function(e) {
				switch (e.key) {
					case 'left' :
						this.mediaLoad(this.getPreviousThumb());
					break;
					case 'right' :
						this.mediaLoad(this.getNextThumb());
					break;
					case 'esc' :
						if (this.mode == 'slide')
							this.slideshowStop();
					break;
					case 'space' :
						if (this.mode == 'slide') {
						
							if (this.media.type == 'video') {
								if (this.slide.video.paused)
									this.slide.video.play();
								else
									this.slide.video.pause();
							} else {
								if (this.slideshow.play)
									this.slideshowPause();
								else
									this.slideshowPlay();
							}
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
	setMediaActionPosition: function()
	{
		this.mediaPrevious.setStyle('display', this.getPreviousThumb() ? 'block' : 'none');
		this.mediaNext.setStyle('display', this.getNextThumb() ? 'block' : 'none');
	
		this.mediaUpdateRotateLeft.setStyle('display', this.media.type == 'image' ? 'block' : 'none');
		this.mediaUpdateRotateRight.setStyle('display', this.media.type == 'image' ? 'block' : 'none');
		this.mediaUpdateFlipHorizontal.setStyle('display', this.media.type == 'image' ? 'block' : 'none');
		this.mediaUpdateFlipVertical.setStyle('display', this.media.type == 'image' ? 'block' : 'none');
	
		var element = this[this.mode][this.media.type];
		var position = element.getPosition(this.container);
		var size = element.getSize();
		var rotate = this.mediaGetRotation();
		this.mediaAction.setStyles({
			width: size[rotate == 0 || rotate == 180 ? 'x' : 'y'] - 10,
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
	mediaLoad: function(thumb)
	{
		if (!thumb) {
			if (this.slideshow.play)
				this.slideshowStop();
			return;
		}

		this.setMediaActionHide();

		var mode = this[this.mode];

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

		$$(mode.image, mode.video).set('src', this.blank);

		this.mediaSetTransform()

		switch (this.media.type) {
			case 'image' :
				
				mode.video.setStyle('display', 'none');
				
				mode.image.set('src', this.media.url + '&dim=' + this.mode);

			break;
			case 'video' :
			
				mode.image.setStyle('display', 'none');
				
				mode.video.src = this.media.url;
				mode.video.load();
				
				
			break;
		}
		this[this.mode][this.media.type].setStyle('display', 'block');

		var position = this.thumb.getPosition($('thumbs'));
		this.thumbOver.setStyles({
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
		
		this.mediaSetTransform();
		
	},
	mediaSetSize: function()
	{
		var mode = this[this.mode];
		switch (this.media.type) {
			case 'image' :
				// Repositionning after rotation
				var transform = this.media.transform + ' translateX(' + this.mediaGetTransformLag() + 'px)';
				mode.image.setStyles({
					width: this.media.width > this.media.height ? mode.size : 'auto',
					height: this.media.width > this.media.height ? 'auto' : mode.size
				}).setStyle3('transform', transform);
			break;
			case 'video' :
				mode.video.set('width', mode.size);
				if (this.media.width < this.media.height)
					mode.video.set('height', mode.size);
				else
					mode.video.removeProperty('height');	
				mode.video.setStyle('display', 'block');
				mode.video.src = this.media.url;
				mode.video.load();
				
				
			break;
		}
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
			if ((degree == 90 && flipV) || (degree == 270 && !flipV))
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

		this.mode = 'slide';

		this.mediaLoad(this.thumb);

		this.slideshow.setStyle('display', 'block');
		this.mediaSlideshowStart.setStyle('display', 'none');
		this.mediaSlideshowEnd.setStyle('display', 'block');
		this.slideshowResize();
	
	},
	slideshowEnd: function()
	{
	
		if (this.slideshow.play)
			this.slideshowStop();

		this.mode = 'preview';
	
		this.mediaLoading.inject(this[this.mode], 'top').setStyles({
			left: this[this.mode].size / 2 - 64
		});
	
		this.slideshow.setStyle('display', 'none');
		this.mediaSlideshowStart.setStyle('display', 'block');
		this.mediaSlideshowEnd.setStyle('display', 'none');
		this.mediaLoad(this.thumb);
	},
	slideshowResize: function()
	{
		if (this.mode != 'slide')
			return;

		this.slide.size = Object.values(this.slideshow.getSize()).min();
		if (this.slide.size > 1000)
			this.slide.size = 1000;
		this.slide.setStyles({
			width: this.slide.size,
			height: this.slide.size
		});

		this.mediaLoading.inject(this[this.mode], 'top').setStyles({
			left: this[this.mode].size / 2 - 64
		});

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
