// Add html5 video tag events on Mootools
Element.NativeEvents = Object.merge(Element.NativeEvents, {
	loadstart: 2, progress: 2, suspend: 2, abort: 2, error: 2, emptied: 2,
	stalled: 2, play: 2, pause: 2, loadedmetadata: 2, loadeddata: 2, waiting: 2,
	playing: 2, canplay: 2, canplaythrough: 2, seeking: 2, seeked: 2,
	timeupdate: 2, ended: 2, ratechange: 2, durationchange: 2, volumechange: 2
});

Element.implement({
	setStyle3: function (attribute, value) {
		['', '-webkit-', '-moz-', '-o-', '-ms-', 'ms'].each(function(prefix) {
			this.setStyle(prefix + attribute, value);
		}.bind(this));
		return this;
	}
});

Number.implement({
	toFileWeight: function() {
		var bytes = this;
		if	  (bytes>=1000000000) {bytes=(bytes/1000000000).toFixed(2)+' GB';}
		else if (bytes>=1000000)	{bytes=(bytes/1000000).toFixed(2)+' MB';}
		else if (bytes>=1000)	   {bytes=(bytes/1000).toFixed(2)+' KB';}
		else if (bytes>1)		   {bytes=bytes+' bytes';}
		else if (bytes==1)		  {bytes=bytes+' byte';}
		else						{bytes='0 byte';}
		return bytes;
	}
});

Date.defineParser('%Y:%m:%d %H:%M:%S');

String.implement({
 	toHtml: function() {
	    return new Element('span',{text:String(this)}).get('html').replace(/\n/g, '<br />');
	}
});

window.addEvent('domready', function() {

	new SimpleGallery();

});

var SimpleGallery = new Class({
	initialize: function() {
	
		this.albumsContainer = $('albums');
		this.albums = $$('.album');
		this.albumsCover = $$('.album-cover');
		
		this.mediasContainer = $('medias');
		
		this.menuDeleted = $('menu-deleted');
		
		var medias = $$('.media');
		this.medias = [];
		for (var i = 0, mediaEl; mediaEl = medias[i]; i++) {
		
			var media = {
				sg: this,
				dom: mediaEl,
				index: mediaEl.get('mediaIndex').toInt(),
				id: mediaEl.get('mediaId').toInt(),
				type: mediaEl.get('mediaType'),
				width: mediaEl.get('mediaWidth').toInt(),
				height: mediaEl.get('mediaHeight').toInt(),
				rotate: mediaEl.get('mediaRotate').toInt(),
				flip: {
					horizontal: mediaEl.get('mediaFlipHorizontal'),
					vertical: mediaEl.get('mediaFlipVertical')
				},
				name: mediaEl.get('mediaName'),
				date: mediaEl.get('mediaDate'),
				exifDate: mediaEl.get('mediaExifDate'),
				exif: JSON.decode(mediaEl.get('mediaExif')),
				deleted: mediaEl.get('mediaDeleted') == 1,
				comments: JSON.decode(mediaEl.get('mediaComments'))
			};

			media.dom.img = media.dom.getElement('img');
			media.dom.addEvent('resize', function(mediaDom) {
			
				var media = mediaDom.media;
			
				var size = media.dom.getSize();
				var width = (media.rotate == 0 || media.rotate == 180) ? size.x : size.y;
				var height = (media.rotate == 0 || media.rotate == 180) ? size.y : size.x;
				media.dom.img.setStyles({
					width: width,
					height: height,
					left: (size.x - width) / 2,
					top: (size.y - height) / 2
				});
			
			
				if (media.type == 'video') {
			
					var play = this.getElement('.media-video-play');
					var size = play.getSize();
					var mediaSize = this.getSize();
				
					play.setStyles({
						left: (mediaSize.x - size.x) / 2,
						top: (mediaSize.y - size.y) / 2
					});
					
				}

			});
			
			media.dom.media = media;
			media.dom.tools = media.dom.getElement('.media-tools');

			media.dom.addEvents({
				click: function (e) {
					e.preventDefault();
				
					var mediaDom = e.target;
					if (!mediaDom.hasClass('media'))
						mediaDom = mediaDom.getParent('.media');
					this.slideshow.open(mediaDom.media.index);
				
				}.bind(this),
				mouseenter: function(e)
				{
					this.tools.setStyle('display', 'block');
				},
				mouseleave: function(e)
				{
					this.tools.setStyle('display', 'none');
				}
			});
			
			if (media.dom.tools.getElement('.rotate-left'))
				media.dom.tools.getElement('.rotate-left').addEvent('click', function(e) {
					e.stopPropagation().preventDefault();
					this.mediaRotate(e.target.getParent('a').media, 'left');
				}.bind(this));

			if (media.dom.tools.getElement('.rotate-right'))
				media.dom.tools.getElement('.rotate-right').addEvent('click', function(e) {
					e.stopPropagation().preventDefault();
					this.mediaRotate(e.target.getParent('a').media, 'right');
				}.bind(this));
			
			if (media.dom.tools.getElement('.delete'))
				media.dom.tools.getElement('.delete').addEvent('click', function(e) {
					e.stopPropagation().preventDefault();
					this.mediaDelete(e.target.getParent('a').media);
				}.bind(this));
			
			this.medias.push(media);
		}
		
		if (this.menuDeleted)
			this.menuDeleted.addEvent('click', function() {
				this.mediasContainer.toggleClass('show-deleted');
				$$('.media.deleted').each(function(domMedia) {
					this.mediaReloadWallData(domMedia.media);
				}.bind(this));
				this.mediaWall.resize();
			}.bind(this));
		
		this.slideshow = new SimpleGallery.Slideshow(this.medias);

		this.albumWall = new SimpleGallery.Wall(this.albumsContainer, this.albums, {margin: 0, width: 'static', onResize: this.albums.setStyle.bind(this.albums, 'visibility', 'visible')});
		this.mediaWall = new SimpleGallery.Wall(this.mediasContainer, medias, {onResize: medias.setStyle.bind(medias, 'visibility', 'visible')});
		
		/*** POUR TEST ***/
//		this.slideshow.open(0);

	},
	mediaReloadWallData: function(media)
	{
		var wallData = media.dom.retrieve('wallData');
		
		if (media.dom.getStyle('display') == 'none') {
			wallData.width = 0;
		} else {
			var width = (media.rotate == 0 || media.rotate == 180) ? media.width : media.height;
			var height = (media.rotate == 0 || media.rotate == 180) ? media.height : media.width;
			wallData.width = (width * 200 / height).ceil();
		}
		wallData.height = 200;
	
	},
	mediaRotate: function(media, direction)
	{

		new Request.JSON({
			url: '?media=update&id=' + media.id + '&direction=' + direction,
			onError: function(error) {
				console.error(error);
			}
		}).send();

		if (direction == 'left')
			media.rotate-= 90;
		else
			media.rotate+= 90;
		if (media.rotate < 0)
			media.rotate = 270;
		else if (media.rotate > 270)
			media.rotate = 0;
		media.dom.set('mediaRotate');
		media.dom.img.setStyle3('transform', 'rotate(' + media.rotate + 'deg)');

		this.mediaReloadWallData(media);

		this.mediaWall.resize();
		
	},
	mediaDelete: function(media)
	{
	
		var request = new Request.JSON({
			url: '?media=update&id=' + media.id,
			method: 'get',
			onError: function(error) {
				console.error(error);
			}
		});
	
		if (media.deleted) {
			media.deleted = false;
			media.dom.set('mediaDeleted', false);
			media.dom.removeClass('deleted');
			request.send({data: {restore: true}});
			media.dom.tools.getElement('.delete').set('title', locale.album.delete);
		} else {
			media.deleted = true;
			media.dom.set('mediaDeleted', true);
			media.dom.addClass('deleted');
			request.send({data: {delete: true}});
			media.dom.tools.getElement('.delete').set('title', locale.album.restore);
		}
		
		this.mediaReloadWallData(media);
		this.mediaWall.resize();
		
	},
	mediaSetDate: function(media, date)
	{
	
		new Request.JSON({
			url: '?media=update&id=' + media.id + '&date=' + date,
			method: 'get',
			onError: function(error) {
				console.error(error);
			}
		}).send();
		
	},
	mediaAddComment: function(media, text, callback)
	{
		new Request.JSON({
			url: '?media=comment',
			data: {id: media.id, text: text},
			onError: function(error) {
				console.error(error);
			},
			onSuccess: function(response)
			{
				if (callback)
					callback(response['media-comment']);
			}
		}).send();
	},
	mediaDeleteComment: function(id)
	{
		new Request.JSON({
			url: '?media=comment',
			data: {deleteId: id},
			onError: function(error) {
				console.error(error);
			}
		}).send();
	}
});

SimpleGallery.blank = 'data:image/gif;base64,R0lGODlhAQABAIAAAP///////yH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==';

SimpleGallery.Slideshow = new Class({
	Implements: [Events],
	initialize: function(medias)
	{
		this.medias = medias;
	
		this.dom = $('slideshow').inject(document.body);
		this.dom.media = $('slideshow-media').addEvent('click', this.navigationEvent.bind(this));
		this.dom.media.onselectstart = function(e) {e.preventDefault()};
		this.dom.panel = $('slideshow-panel');
		this.dom.panel.name = $('slideshow-panel-name');
		this.dom.panel.tools = {
			rotateLeft: $('slideshow-panel-rotateLeft'),
			rotateRight: $('slideshow-panel-rotateRight'),
			delete: $('slideshow-panel-delete')
		};
		if (this.dom.panel.tools.rotateLeft) {
			this.dom.panel.tools.rotateLeft && this.dom.panel.tools.rotateLeft.addEvent('click', function() {
				this.media.sg.mediaRotate(this.media, 'left');
				this.navigation(0);
			}.bind(this));
			this.dom.panel.tools.rotateRight && this.dom.panel.tools.rotateRight.addEvent('click', function() {
				this.media.sg.mediaRotate(this.media, 'right');
				this.navigation(0);
			}.bind(this));
			this.dom.panel.tools.delete && this.dom.panel.tools.delete.addEvent('click', function() {
				this.media.sg.mediaDelete(this.media);
				this.navigation(0);
			}.bind(this));
		}
		this.dom.panel.date = $('slideshow-panel-date');
		this.dom.panel.date.update = this.dom.panel.date.getElement('input');
		if (this.dom.panel.date.update)
			this.dom.panel.date.update.addEvent('blur', function() {
				var date = this.dom.panel.date.update.get('value');
				if (this.dom.panel.date.update.oldValue != date)
					this.media.sg.mediaSetDate(this.media, date);
				this.dom.panel.date.update.oldValue = date;
			}.bind(this));
		this.dom.close = $('slideshow-close').addEvent('click', this.close.bind(this));

		this.dom.panel.exif = $('extra-exif');
		this.dom.panel.comments = $('extra-comments');		
		if (this.dom.panel.comments)
			this.dom.panel.comments.input = $('extra-comments-input');
		
		$$('.slideshow-panel-extra-title').addEvent('click', function(e) {
			if (this.getParent().hasClass('slideshow-panel-extra-open'))
				this.getParent().removeClass('slideshow-panel-extra-open');
			else
				this.getParent().addClass('slideshow-panel-extra-open');
			window.fireEvent('resize');
		});
		
		if (this.dom.panel.comments) {
			this.dom.panel.comments.input.addEvents({
				keydown: function(e) {
					if (e.key == 'enter' && !e.shift)
						return false;
				}.bind(this),
				keyup: function(e) {
					if (e.key == 'enter' && !e.shift) {
						var text = e.target.get('value');
						var domComment = this.extraAddComment(0, window.sgUser.name, new Date().format('%d/%m/%Y'), text);
						this.media.sg.mediaAddComment(this.media, text, function(comment) {
							domComment.getElement('.extra-comment-date').set('text', ' ' + new Date().parse(comment.date).format('%d/%m/%Y %H:%m:%S'));
							domComment.getElement('input.extra-comment-id').set('value', comment.id);
							var domDelete = domComment.getElement('.extra-comment-delete');
							if (domDelete)
								domDelete.setStyle('display', 'inline');
						});
					
					}
				}.bind(this),
				paste: function() {
					this.set('rows', (this.get('value').match(/\n|\r\n/g) || []).length + 1);
				},
				input: function() {
					this.set('rows', (this.get('value').match(/\n|\r\n/g) || []).length + 1);
				},
				textinput: function() {
					this.set('rows', (this.get('value').match(/\n|\r\n/g) || []).length + 1);
				},
			});
		}
		
		this.image = new SimpleGallery.Slideshow.Image(this);
		this.video = new SimpleGallery.Slideshow.Video(this);
		this.book = new SimpleGallery.Slideshow.Book(this);
		
		this.mediaIndex = -1;
		this.isOpen = false;
		this.navigationEnable = true;
		
		window.addEvent('resize', this.resize.bind(this));
		this.resize();
	},
	resize: function()
	{	
		if (this.isOpen) {
			var windowSize = window.getSize();

			this.dom.setStyles({
				display: 'block',
				width: windowSize.x,
				height: windowSize.y
			});
			this.dom.media.setStyles({
				width: windowSize.x - 250,
				height: windowSize.y
			});
			this.dom.panel.setStyles({
				width: 250,
				height: windowSize.y
			});
		}

	},
	open: function(index)
	{
		this.mediaIndex = index;

		this.isOpen = true;
		
		this.resize();
		
		this.navigation(0);

	},
	close: function(e)
	{
		e.preventDefault();
		
		this.isOpen = false;
		
		this.dom.setStyle('display', 'none');
		
		this.slideshowMedia.unload();
	},
	mediaLoad: function()
	{
		if (this.medias[this.mediaIndex].deleted && !$('medias').hasClass('show-deleted'))		
			return false;
		
		this.media = this.medias[this.mediaIndex];
		if (this.slideshowMedia)
			this.slideshowMedia.unload();
		
		if (this.dom.panel.tools.rotateLeft) {
			if (this.media.type == 'image') {
				this.dom.panel.tools.rotateLeft.setStyle('display', 'block');
				this.dom.panel.tools.rotateRight.setStyle('display', 'block');
			} else {
				this.dom.panel.tools.rotateLeft.setStyle('display', 'none');
				this.dom.panel.tools.rotateRight.setStyle('display', 'none');
			}
		}
		
		
		this.slideshowMedia = this[this.media.type];
		
		this.dom.panel.name.set('html', this.medias[this.mediaIndex].name);
		if (this.dom.panel.tools.delete)
			this.dom.panel.tools.delete.set('title', this.media.deleted ? locale.album.restore : locale.album.delete);
		
		var date = this.media.date;
		if (!date || date == '0000-00-00 00:00:00')
			date = this.media.exifDate;
		if (!date || date == '0000-00-00 00:00:00' || date == '0000:00:00 00:00:00')
			date = '';
		if (this.dom.panel.date.update)
			this.dom.panel.date.update.oldValue = new Date().parse(date).format('%Y-%m-%dT%H:%M');

		if (date) {
			date = new Date().parse(date);

			if (this.dom.panel.date.update)
				this.dom.panel.date.update.value = date.format('%Y-%m-%dT%H:%M');
			else
				this.dom.panel.date.set('text', date.format('%d/%m/%Y %H:%M'));
				
		} else {
			if (this.dom.panel.date.update)
				this.dom.panel.date.update.value = '';
			else
				this.dom.panel.date.set('text', '');
		}
		
		this.dom.panel.exif.empty();
		var exif = this.medias[this.mediaIndex].exif;
		if (exif) {
			if (exif.FileSize)
				this.addExif(locale.album.exif.size + ' : ', exif.FileSize.toInt().toFileWeight());
			if (exif.FileDateTime)
				this.addExif(locale.album.exif.date + ' : ', exif.DateTime);
			if (exif.Make || exif.Model)
				this.addExif(locale.album.exif.camera + ' : ', exif.Make + ' ' + exif.Model);
			if (exif.ExifImageWidth && exif.ExifImageLength)
				this.addExif(locale.album.exif.dimension + ' : ', exif.ExifImageWidth + ' x ' + exif.ExifImageLength);
			if (exif.Flash !== undefined)
				this.addExif(locale.album.exif.flash + ' : ', (exif.Flash & 1) != 0 ? locale.album.exif.yes : locale.album.exif.no);
		}
		
		if (!this.dom.panel.exif.getElements('li').length)
			this.addExif('none', '');
		
		if (this.dom.panel.comments) {
			this.dom.panel.comments.input.getParent('li').getAllNext().destroy();
			var comments = this.medias[this.mediaIndex].comments;
			for (var i = 0, comment; comment = comments[i]; i++) {
				this.extraAddComment(comment.id, comment.username, new Date().parse(comment.date).format('%d/%m/%Y %H:%m:%S'), comment.text);
			}
		}

		this.slideshowMedia.load(this.medias[this.mediaIndex]);
		
		return true;
	},
	addExif: function(title, data)
	{
		new Element('li').adopt(
			new Element('span.exif-data-title', {html: title}),
			new Element('span.exif-data-value', {html: data})
		).inject(this.dom.panel.exif);
	},
	navigationEvent: function(e)
	{
		var size = this.dom.media.getSize();
		if (e.client.x > size.x / 2)
			var direction = 1;
		else
			var direction = -1;

		if (this.navigationEnable)
			this.navigation(direction);
			
		this.fireEvent('navigation', [direction]);

	},
	navigation: function(direction)
	{
		if (this.mediaIndex + direction >= 0 && this.mediaIndex + direction < this.medias.length) {
			this.mediaIndex+= direction;
			if (!this.mediaLoad()) {
				if (!this.navigation(direction == -1 ? -1 : 1)) {
					this.mediaIndex-= direction;
					return false;
				}
			}
			return true;
		}
		return false;
	},
	extraAddComment: function(id, username, date, text)
	{
		var domDelete = new Element('span.extra-comment-delete', {
			text: ' x',
			title: 'delete comment',
			events: {click: function(e) {
				this.extraDeleteComment(e.target.getParent('li').getElement('input.extra-comment-id').get('value'));
			}.bind(this)},
			styles: {display: id ? 'inline' : 'none'}
		});
		return new Element('li').adopt(
			new Element('input.extra-comment-id', {type: 'hidden', value: id}),
			new Element('div').adopt(
				new Element('span.extra-comment-username', {text: username}),
				new Element('span.extra-comment-date', {text: ' ' + date}),
				window.sgUser.admin ? domDelete : null
			),
			new Element('div.extra-comment-text', {html: text.toHtml()})
		).inject(this.dom.panel.comments);
	},
	extraDeleteComment: function(id)
	{
		this.media.sg.mediaDeleteComment(id);
		document.getElement('input.extra-comment-id[value=' + id + ']').getParent('li').destroy();
	}
});

SimpleGallery.Slideshow.Media = new Class({
	initialize: function (type, args) {
		this.type = type;
		this.slideshow = args[0];
		
		this.parent = $('slideshow-media');
		this.dom = $('slideshow-media-' + type);
		
		this.isLoad = false;
		
		window.addEvent('resize', this.resize.bind(this, true));
	},
	resize: function(cssRotate)
	{
		
		if (!this.isLoad)
			return;
		
		var size = this.parent.getSize();
		var mediaSize = {
			x: this.media.width,
			y: this.media.height
		};
		var isRotate = (this.media.rotate == 90 || this.media.rotate == 270);
		if (isRotate) {
			mediaSize = {
				x: mediaSize.y,
				y: mediaSize.x
			};
		}

		var refSize = {x: size.x, y: size.y};
		if (mediaSize.x < refSize.x)
			refSize.x = mediaSize.x;
		if (mediaSize.y < refSize.y)
			refSize.y = mediaSize.y;

		if (size.x / size.y < mediaSize.x / mediaSize.y) {
			var newSize = {
				x: refSize.x,
				y: refSize.x * mediaSize.y / mediaSize.x
			};
		} else {
			var newSize = {
				x: refSize.y * mediaSize.x / mediaSize.y,
				y: refSize.y
			};
		}
		isRotate = (cssRotate && isRotate);
		this.dom.setStyles({
			width: newSize[isRotate ? 'y' : 'x'],
			height: newSize[isRotate ? 'x' : 'y'],
			left: (size.x - newSize[isRotate ? 'y' : 'x']) / 2,
			top: (size.y - newSize[isRotate ? 'x' : 'y']) / 2
		});
	},
	parentUnload: function()
	{
		this.dom.setStyle('display', 'none');
		this.isLoad = false;
		this.media = null;
	},
	unload: function()
	{
		this.parentUnload();
	}
});

SimpleGallery.Slideshow.Image = new Class({
	Extends: SimpleGallery.Slideshow.Media,
	initialize: function()
	{
		this.parent('image', arguments);
		this.dom.ondragstart = function(e) {e.preventDefault()};
	},
	load: function(media)
	{
		this.slideshow.navigationEnable = true;
	
		this.dom.set('src', SimpleGallery.blank);
	
		this.media = media;
	
		this.dom.setStyle3('transform', 'rotate(' + this.media.rotate + 'deg)');
		this.dom.setStyle('display', 'block');
		
		this.isLoad = true;
		
		this.resize(true);
		
		this.dom.set('src', '?media=slideshow&id=' + media.id);

	}
});

SimpleGallery.Slideshow.Video = new Class({
	Extends: SimpleGallery.Slideshow.Media,
	initialize: function()
	{
		this.parent('video', arguments);
		
		if (!this.dom.pause)
			this.dom.pause = function(){};
		
		this.dom.addEvents({
			loadeddata: function(e)
			{
				e.target.play();
			},
			click: function(e) {e.stopPropagation()}
		});
	},
	load: function(media)
	{
		this.slideshow.navigationEnable = true;
		
		this.media = media;
	
		this.dom.setStyle('display', 'block');
		
		this.isLoad = true;
		
		this.resize();
		
		this.dom.set('src', '?media=slideshow&id=' + media.id);
		

	},
	unload: function()
	{
		this.dom.pause();
		this.parentUnload();
	}
});

SimpleGallery.Slideshow.Book = new Class({
	Extends: SimpleGallery.Slideshow.Media,
	initialize: function()
	{
		this.parent('book', arguments);
		this.slideshow.addEvent('navigation', this.navigation.bind(this));
		this.domPages = new Element('div.slideshow-panel-book-pages', {styles: {
			display: 'none'
		}}).inject(this.slideshow.dom.panel);
		this.domPages.wall = new Element('div').inject(this.domPages);
		this.domCache = new Element('img', {styles: {display: 'none'}, events: {load: function() {
			this.pageCurrentCache++;
			this.cache();
		}.bind(this)}}).inject(this.domPages);
		this.dom.addEvent('load', function(e) {
			if (!this.media)
				return;
			this.media.width = this.dom.naturalWidth;
			this.media.height = this.dom.naturalHeight;
			this.resize();
			this.dom.fade('in');
		}.bind(this));
	},
	load: function(media)
	{
		this.resizePages();
		this.domPages.setStyle('display', 'block')
		var top = 0;
		var pages = [];
		this.pageCurrentCache = 1;
		for (var i = 0; i < media.exif.totalPage; i++) {
			pages.push(new Element('div.slideshow-panel-book-page', {events: { click: function(e) {
				this.page = e.target.getAllPrevious().length + 1;
				this.showPage();
			}.bind(this)}, styles: {
				width: media.exif.slideshowSizes[i].width,
				height: media.exif.slideshowSizes[i].height,
				background: 'transparent url(?media=slideshow&id=' + media.id + '&data[pages]) 0px ' + top + 'px'
			}}).store('size', media.exif.slideshowSizes[i]).store('top', top).inject(this.domPages.wall));
			top-= media.exif.slideshowSizes[i].height;
		}
		this.media = media;
		
		new SimpleGallery.Wall(this.domPages.wall, pages, {onResize: this.resizePages.bind(this)});
	
		this.slideshow.navigationEnable = false;
		this.page = 1;

		this.dom.setStyle('display', 'block');
		
		this.isLoad = true;
		this.cache();		
		this.showPage();
		

	},
	unload: function()
	{
		this.dom.set('src', SimpleGallery.blank);
		this.domPages.setStyle('display', 'none');
		this.domPages.wall.empty();
		this.parentUnload();
	},
	resizePages: function() {

		this.domPages.setStyle('height',
			this.slideshow.dom.panel.getSize().y -
			this.domPages.getPosition(this.slideshow.dom.panel).y
		);
		this.domPages.getElements('.slideshow-panel-book-page').each(function(page, index) {
			var percent = page.getSize().y / page.retrieve('size').height;
			var height = ((page.retrieve('size').height * this.media.exif.totalPage) * percent).ceil();
			var top = (page.retrieve('top') * percent).ceil();
			page.setStyles({
				backgroundSize: 'auto ' + height + 'px',
				backgroundPosition: '0px ' + top + 'px'
			});
		}.bind(this));
	},
	navigation: function(direction)
	{
		if (!this.isLoad)
			return;
		
		this.page+= direction;
		if (this.page < 1 || this.page > this.media.exif.totalPage) {
			this.slideshow.navigation(direction);
			return;
		}
		
		this.showPage();
	},
	showPage: function()
	{
		this.dom.fade('out');
		this.dom.set('src', '?media=slideshow&id=' + this.media.id + '&data[page]=' + this.page);	
	},
	cache: function()
	{
		if (this.pageCurrentCache > this.media.exif.totalPage || !this.isLoad)
			return;
		this.domCache.set('src', '?media=slideshow&id=' + this.media.id + '&data[page]=' + this.pageCurrentCache);
	}
});

SimpleGallery.Wall = new Class({
	Implements: [Options, Events],
	options: {
		margin: 1,
		width: 'auto' // auto, static
	},
	initialize: function(wall, bricks, options) {

		this.wall = $(wall);
		this.bricksEls = bricks;
		this.setOptions(options);

		this.bricks = [];
		
		for (var i = 0, brick; brick = this.bricksEls[i]; i++) {

			var size = brick.getSize();
			
			var brickData = {
				domElement: brick,
				width: size.x,
				height: size.y,
				paddingWidth: brick.getStyle('padding-left').toInt() + brick.getStyle('padding-right').toInt(),
				paddingHeight: brick.getStyle('padding-top').toInt() + brick.getStyle('padding-bottom').toInt()
			}
			this.bricks.push(brickData);
			brick.store('wallData', brickData);
			
		}

		window.addEvent('resize', this.resize.bind(this));
		this.resize();
		
	},
	resize: function()
	{
		var wallWidth = this.wall.getSize().x;

		// Compute grid of brick
		var wall = [];
		var row = [];
		row.width = 0;

		for (var i = 0,brick; brick = this.bricks[i]; i++) {
			
			if (brick.width > 0) {
				row.push(brick);
				row.width+= brick.width;
			}

			if (row.width > wallWidth || i == this.bricks.length - 1) {
				wall.push(row);
				row = [];
				row.width = 0;
			}

		}

		// Place the bricks
		var position = {
			left: 0,
			top: 0
		};
		var width = 0;
		for (var y = 0,row; row = wall[y]; y++) {
			position.left = 0;

			var height = 0;
			var rowWidth = wallWidth - this.options.margin * (row.length - 1);
			for (var x = 0,brick; brick = row[x]; x++) {

				if (this.options.width == 'auto' || (x == 0 && y == 0)) {
					width = (brick.width * rowWidth / row.width).ceil();
					if (width > brick.width)
						width = brick.width;
				}

				if (x == 0)
					// Compute row height at first brick
					height = (width * brick.height / brick.width).ceil();

				var brickWidth = width;
				if (position.left + brickWidth + (x == 0 ? 0 : this.options.margin) > wallWidth)
					brickWidth = wallWidth - position.left - (x == 0 ? 0 : this.options.margin);

				brick.domElement.setStyles({
					position: 'absolute',
					left: position.left + (x == 0 ? 0 : this.options.margin),
					top: position.top + (y == 0 ? 0 : this.options.margin),
					width: brickWidth - brick.paddingWidth,
					height: height - brick.paddingHeight
				}).fireEvent('resize', brick.domElement);
				position.left+= brickWidth + (x == 0 ? 0 : this.options.margin);

			}
			
			position.top+= height + (y == 0 ? 0 : this.options.margin);
		
		}
		this.wall.setStyle('height', position.top);

		this.fireEvent('resize');
	}

});
