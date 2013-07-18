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

String.implement({
 	toHtml: function() {
	    return new Element('span',{text:String(this)}).get('html');
	}
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
		Locale.use($('albumLocale').get('text'));

		this.locale = JSON.decode($('albumLocales').get('text'));
		this.mode = 'preview';

		this.thumb = null;
		this.tags = [];
		
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
			this.mediaCount = $('mediaCount');
			this.mediaDownload = $('mediaDownload');
		this.mediaBalloon = $('mediaBalloon');
		this.mediaDescription = $('mediaDescription');
		this.mediaDate = $('mediaDate');
		this.mediaComment = $('mediaComment');
		this.mediaComments = $('mediaComments');
		
		this.mediaBackground = $('mediaBackground');
		this.mediaImage = $('mediaImage');
		this.mediaVideo = $('mediaVideo');
		this.mediaTags = $('mediaTags');
		if (this.mediaTags)
			this.mediaTagsList = $('mediaTagsList');
		
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
			mouseenter: function() {
				if (this.mediaBalloonHide)
					clearTimeout(this.mediaBalloonHide);
				if (this.mediaBalloon)
					this.mediaBalloon.setStyle('display', 'block');
			}.bind(this),
			mouseleave: this.setMediaBalloonHide.bind(this),
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
		this.mediaImage.addEvents({
			click: function(e) {
				
				if (!e.target.hasClass('media-image-tag'))
					return false;
				
				var position = this.mediaElement.getPosition();
				var size = this.mediaElement.getSize();
				var angle = this.mediaGetRotation();
				position = {
					x: (e.page.x - position.x) * 100/size[(angle == 0 || angle == 180) ? 'x' : 'y'] - 50,
					y: (e.page.y - position.y) * 100/size[(angle == 0 || angle == 180) ? 'y' : 'x'] - 50
				};
				angle = -angle * Math.PI / 180;
				position = {
					x: (position.x * angle.cos() - position.y * angle.sin() + 50).round(2),
					y: (position.x * angle.sin() + position.y * angle.cos() + 50).round(2)
				};
				this.mediaAddTag(position);
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
		}
		this.mediaUpdateRequest = new Request.JSON({
/*			onComplete: function() {console.log('complete')},
			onFailure: function() {console.log('failure')},
			onException: function() {console.log('exception')},
			onProgress: function(a,b) {console.log('progress', a,b)},*/
			onSuccess: this.mediaSetUpdateSuccess.bind(this)}
		);

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
		
		if (this.mediaDate)
			this.mediaDate.addEvent('blur', function() {
				var date = this.mediaDate.get('value').replace('T', ' ');
				if (date != this.media.date)
					this.mediaSetUpdate({date: date});
			}.bind(this));
		
		if (this.mediaDescription && this.mediaDescription.get('tag') == 'textarea')
			this.mediaDescription.addEvent('blur', function() {
				var description = this.mediaDescription.get('value');
				if (description != this.media.description)
					this.mediaSetUpdate({description: description});
			}.bind(this));
			
		if (this.mediaComment)
			this.setTextareaHeightAuto(this.mediaComment);

		if (this.mediaBalloon)
			this.mediaBalloon.addEvents({
				mouseenter: function() {
					if (this.mediaBalloonHide)
						clearTimeout(this.mediaBalloonHide);
					this.mediaBalloon.setStyle('display', 'block');
				}.bind(this),
				mouseleave: this.setMediaBalloonHide.bind(this)
			});
		
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
		var width = this.mediaElement.size[rotate == 0 || rotate == 180 ? 'width' : 'height'] - 10;
		position.x+= 6;
		position.y+= 5;
		this.mediaAction.setStyles({
			position: this.mode == 'preview' ? 'absolute' : 'fixed',
			width: width,
			left: position.x,
			top: position.y
		});
		
		this.mediaCount.set('html', (this.thumb.getAllPrevious().length + 1) + '/' + this.thumbs.length);
		
		if (this.mediaBalloon)
			this.mediaBalloon.setStyles({
				left: position.x + width + 20,
				top: position.y
			}).setStyle3('border-top-right-radius', '10px').setStyle3('border-bottom-right-radius', '10px').setStyle3('border-bottom-left-radius', '10px');
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
		this.media.id = this.thumb.get('mediaId');
		this.media.type = this.thumb.get('mediaType');
		this.media.order = this.thumb.get('mediaOrder').toInt();		
		this.media.width = this.thumb.get('mediaWidth').toInt();
		this.media.height = this.thumb.get('mediaHeight').toInt();
		this.media.rotation = this.thumb.get('mediaRotation').toInt();
		this.media.orientation = this.thumb.get('mediaOrientation').toInt();
		this.media.flip = {
			horizontal: this.thumb.get('mediaFlipHorizontal'),
			vertical: this.thumb.get('mediaFlipVertical')
		};
		this.media.transform = '';
		this.media.description = this.thumb.get('mediaDescription');
		if (this.mediaDate)
			this.media.date = this.thumb.get('mediaDate');
		if (this.mediaComments)
			this.media.comments = JSON.decode(this.thumb.get('mediaComments'));
		if (this.mediaTags)
			this.media.tags = JSON.decode(this.thumb.get('mediaTags'));

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

		if (this.mediaBalloon) {
			
			if (this.mediaDate) {
				if (this.mediaDate.get('tag') == 'input')
					this.mediaDate.set('value', this.media.date.replace(' ', 'T'));
				else if (this.media.date)
					this.mediaDate.set('html', this.locale['date'] + ' ' + new Date(this.media.date).format('%x %X'));
				else
					this.mediaDate.set('html', '');
			}
		
			this.mediaDescription.set('html', this.media.description);
			if (this.mediaDescription.get('tag') == 'p') {
				if (this.media.description)
					this.mediaDescription.removeClass('media-no-description');
				else
					this.mediaDescription.addClass('media-no-description').set('html', this.locale['no-description'])
			}

			if (this.mediaComments) {
				this.mediaComments.empty();
				this.mediaComments.setStyle('display', this.media.comments.length ? 'block' : 'none');
				for (var i = 0,comment; comment = this.media.comments[i]; i++)
					this.mediaAddComment(comment.id, comment.user_name, comment.datetime, comment.value);
			}
/*
			if (this.mediaTags) {
				this.mediaTags.empty();
				for (tag in this.media.tags) {
					tag = this.media.tags[tag];
					this.mediaAddTag({x: tag.x, y: tag.y}, tag.value, true);
				}
			}
*/
		}
		
		this.resize();

	},
	mediaSetUpdate: function(update)
	{
		var url = this.media.url;
	
		if (typeof update == 'string')
			update = JSON.decode('{' + update + ':true}');

		if (this.media.type == 'image') {
			if (update.rotateLeft || update.rotateRight) {
				var direction = update.rotateLeft ? -1 : 1;
				this.media.rotation+= direction * 90;
				if (this.media.rotation < 0)
					this.media.rotation = 270;
				else if (this.media.rotation > 270)
					this.media.rotation = 0;
				this.thumb.set('mediaRotation', this.media.rotation);
			}
			if (update.flipHorizontal || update.flipVertical) {
				var angle = this.mediaGetRotation();
				if (angle == 90 || angle == 270) {
					if (update.flipHorizontal) {
						delete update.flipHorizontal;
						update.flipVertical = true;
					} else {
						delete update.flipVertical;
						update.flipHorizontal = true;
					}
				}

				var orientation = update.flipHorizontal ? 'horizontal' : 'vertical';

				this.media.flip[orientation] = !this.media.flip[orientation];
				this.thumb.set('mediaFlip' + orientation, !this.thumb.get('mediaFlip' + orientation));
			}
			
			this.mediaSetTransform();
			
			this.mediaElement.fireEvent('load', this.mediaElement.get('src'));
		}
		
		if (update.delete) {
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
		
		if (update.date != undefined)
			this.thumb.set('mediaDate', this.media.date = update.date);
		
		if (update.description != undefined)
			this.thumb.set('mediaDescription', this.media.description = update.description);

		this.mediaUpdateRequest.send({
			url: url,
			data: {update: update}
		});
		
	},
	mediaSetUpdateSuccess: function(response)
	{

		if (response.comments != undefined) {

			var comment = {
				id: response.comments.id,
				media_id: response.comments.media_id,
				user_id: response.comments.user_id,
				datetime: response.comments.datetime,
				value: response.comments.value,
				user_name: response.comments.user_name
			};
			for (var i = 0,thumb; thumb = this.thumbs[i]; i++)
				if (thumb.get('mediaId') == comment.media_id) {
					var comments = JSON.decode(this.thumb.get('mediaComments'));
					comments.push(comment);
					thumb.set('mediaComments', JSON.encode(comments));
					if (this.media.id == comment.media_id)
						this.media.comments = comments;
					break;
				}
		}
		
		if (response.commentsRemove != undefined)
			for (var i = 0,thumb; thumb = this.thumbs[i]; i++)
				if (thumb.get('mediaId') == response.media_id) {
					var comments = JSON.decode(this.thumb.get('mediaComments'));
					for (var i = 0, comment; comment = comments[i]; i++) {
						if (comment.id == response.commentsRemove) {
							comments.splice(i, 1);
							thumb.set('mediaComments', JSON.encode(comments));
							if (this.media.id == comment.media_id)
								this.media.comments = comments;
						}
					}
					break;
				}
		
	},
	mediaGetOrientation: function()
	{
		var angle = this.mediaGetRotation();
		if (
			(this.media.width > this.media.width && (angle == 0 || angle == 180)) ||
			(this.media.width < this.media.width && (angle == 90 || angle == 270))
		)
			return 'horizontal';
		else
			return 'vertical';
	},
	mediaAddComment: function(id, user, date, text, isNew)
	{
		this.mediaComment.set('value', '');
		var type = /*this.albumAdmin ||  ? 'textarea' : */'p';
		var textarea, remove;
		new Element('li').adopt(
			new Element('input[type=hidden]', {value: id}),
			new Element('div').adopt(
				new Element('span.media-comment-author', {html: user}),
				new Element('span.media-comment-date', {html: new Date(date).format('%x %X')})
			),
			textarea = new Element(type+'.media-comment-text', {html: text}),
			this.albumAdmin ? new Element('div.media-comment-delete').grab(
				remove = new Element('a[href=#]', {html: this.locale['comment-delete']})
			) : null
		).inject(this.mediaComments.setStyle('display', 'block'));
		
		if (remove)
			remove.addEvent('click', function(e) {
				e.preventDefault();
				this.mediaRemoveComment(e.target.getParent('li'));
			}.bind(this));
/*		if (type == 'textarea')
			this.setTextareaHeightAuto(textarea);*/
	},
	mediaRemoveComment: function(li)
	{
		var id = li.getElement('input[type=hidden]').get('value');
		if (confirm(this.locale['comment-delete-confirm'])) {
			this.mediaSetUpdate({commentsRemove:id});
			var ul = li.getParent('ul');
			li.destroy();
			if (!ul.getElement('li'))
				this.mediaComments.setStyle('display', 'none');
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
		var reg = /rotate\(([-0-9]+)deg\)/;
		var deg = reg.exec(this.media.transform);
		if (!deg)
			return 0;
		
		deg = deg[1].toInt();
		var degree = deg%360;
		if (deg < 0)
			degree+= 360;
		
		return degree;

	},
	mediaGetFlip: function()
	{
		return flip = {
			horizontal: /scaleX\(([-0-9]+)\)/.exec(this.media.transform)[1] == -1,
			vertical: /scaleY\(([-0-9]+)\)/.exec(this.media.transform)[1] == -1
		}
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
		var reorder = this.thumbs.get('mediaId');
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
		var scroll = window.getScroll().y;
		
		var overflow = this.albumsMenu.getSize().y - documentHeight;
		if (overflow < 0)
			overflow = 0;
		if (scroll - overflow > this.albumsMenu.distanceTop)
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
			overflow = this.mediaElement.getSize().y - documentHeight;
			if (overflow < 0)
				overflow = 0;
			if (scroll - overflow > this.preview.distanceTop)
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
			if (scroll - overflow > this.albumAdmin.distanceTop)
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
	},
	setMediaBalloonHide: function()
	{
		if (!this.mediaBalloon)
			return;
		if (this.mediaBalloonHide)
			clearTimeout(this.mediaBalloonHide);
		this.mediaBalloonHide = this.mediaBalloon.setStyle.delay(500, this.mediaBalloon, ['display', 'none']);	
		if (this.mediaDescription)
			this.mediaDescription.fireEvent('blur');
	},
	setTextareaHeightAuto: function(textarea)
	{
		textarea.addEvents({
			keydown: function(e) {
				if (e.key == 'enter' && !e.shift)
					return false;
				var nl = 1;
				if (e.key == 'enter')
					nl++;
				e.target.set('rows', (e.target.get('value').match(/\n|\r\n/g) || []).length + nl);
				
			}.bind(this),
			keyup: function(e) {
				if (e.key == 'enter' && !e.shift) {
					this.mediaSetUpdate({comments:e.target.get('value')});
					this.mediaAddComment(0, this.locale.me, new Date(), e.target.get('value').toHtml(), true);
				}
				e.target.set('rows', (e.target.get('value').match(/\n|\r\n/g) || []).length + 1);
			}.bind(this)
		});
	},
	mediaAddTag: function(coord, tag, system)
	{
		if (!this.mediaTags)
			return;
	
		// Set real position in px
		var size = this.mediaElement.getSize();
		position = {
			x: (coord.x * size.x / 100).round(),
			y: (coord.y * size.y / 100).round()
		};

		// Set invert transform
		var flip = this.mediaGetFlip();
		var transform = 'rotate(' + -this.mediaGetRotation() + 'deg)';
		if (flip.horizontal)
			transform+= ' scaleX(-1)';
		if (flip.vertical)
			transform+= ' scaleY(-1)';
		
		// Inject frame
		var tagFrame = new Element('div.media-tag', {styles: {
			left: position.x,
			top: position.y
		}}).setStyle3('transform', transform).inject(this.mediaTags);
		
		tagFrame.itemList = new Element('span', {html: tag, events: {
			mouseenter: function() {
				this.tagFrame.setStyle('display', 'block');
			},
			mouseleave: function() {
				this.tagFrame.setStyle('display', 'none');
			}
		}}).inject(this.mediaTagsList);
		tagFrame.itemList.tagFrame = tagFrame;
		
		// Span tag
		var tagText = new Element('span.media-tag-text', {html: tag}).inject(tagFrame);
		var size = tagText.getSize();
		tagText.setStyle('left', (40 - size.x)/2);
		
		// Input tag
		if (!tag || this.albumAdmin) {
			tagText.setStyle('visibility', 'hidden');
			var tagInput = new Element('input.media-tag-text', {value: tag, events: {
				keydown: function(e) {
					this.tagSetInputPosition(e.target);
					if (e.key == 'enter') {
						this.tagUpdate(e.target);
					} else if (e.key == 'esc' && !e.target.retrieve('system')) {
						e.target.getParent().destroy();
					}
				}.bind(this),
				keyup: function(e) {
					this.tagSetInputPosition(e.target);
				}.bind(this),
				blur: function(e) {
					if (e.target.get('value') == '')
						e.target.getParent().destroy();
					else
						this.tagUpdate(e.target);
				}.bind(this)
			}}).inject(tagText, 'before');
			this.tagSetInputPosition(tagInput);
			tagInput.store('coord', coord);
			tagInput.store('system', system);
			if (!system)
				tagInput.focus();
		}
		
		if (system)
			tagFrame.setStyle('display', 'none');
	},
	tagSetInputPosition: function(input)
	{
		var tagText = input.getNext();
		tagText.set('text', input.get('value'));
		var size = tagText.getSize().x;
		input.setStyles({
			width: size,
			left: (40 - size)/2
		});
	},
	tagUpdate: function(input)
	{
		this.mediaSetUpdate({tags:{
			x: input.retrieve('coord').x,
			y: input.retrieve('coord').y,
			value: input.get('value')
		}});
		if (input.get('value') == '')
			input.getParent().destroy();
	}
});


/****
	Simplegallery.Calendar
****/

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
