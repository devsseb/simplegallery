window.addEvent('domready', function() {
	new SimpleGalleryMediasAnalyzer();
});

var SimpleGalleryMediasAnalyzer = new Class({
	initialize: function()
	{

		this.container = $('analyzer');
		
		this.initScrollGlue();

		new Request({
			url: '?album=analyze',
			data: {albums: JSON.decode($('albums').get('value'))},
			onRequest: function()
			{
				this.commands = [];
				this.albums = {};
				this.lastAlbum = false;
			}.bind(this),
			onProgress: function(e) {
				var commands = e.target.response.split(/\n/);
				commands.pop();
				this.progress(commands = commands.splice(this.commands.length, commands.length - this.commands.length));
				this.commands.append(commands);
			}.bind(this),
			onComplete: this.complete.bind(this, 'analyze')
		}).send();

		
	},
	initScrollGlue: function()
	{
		this.content = $('content');
		
		this.scrollGlue = true;
				
		this.content.previousScroll = this.content.scrollTop;
		this.scroll = new Fx.Scroll(this.content, {duration: 0, link: 'cancel'});
		
		this.content.addEvent('scroll', function(e) {
		
			var direction = this.content.scrollTop > this.content.previousScroll ? 1 : (this.content.scrollTop == this.content.previousScroll ? 0 : -1);

			this.content.previousScroll = this.content.scrollTop;
			
			if (direction < 0)
				this.scrollGlue = false;
			else if (direction > 0 && this.content.scrollTop + this.content.getSize().y == this.content.scrollHeight)
				this.scrollGlue = true;
		}.bind(this));
		
	},
	progress: function(commands)
	{
		for (var i = 0, command; command = commands[i]; i++) {
			command = JSON.decode(command);

			var album = command.media.path.replace(/\\/g,'/').replace(/\/[^\/]*$/, '') + '/';
			
			if (album != this.lastAlbum) {
				this.lastAlbum = album;
				var domAlbum = new Element('div.album', {html: album}).inject(this.container);
				this.albums[album] = domAlbum;
			}
			
			var domMedia = new Element('div.media-synchronize', {html: command.media.path || '/'}).inject(this.container);
			domMedia.store('media', command.media);
			
			if (command.media.state == 'new')
				domMedia.addClass('media-new');
			else if (command.media.state == 'delete')
				domMedia.addClass('media-delete');
			else if (command.media.state == 'update') {
				domMedia.addClass('media-update');
				if (command.media.update.oldPath)
					new Element('span.media-updates', {html: ' <= ' + command.media.update.oldPath}).inject(domMedia);
			} else
				domMedia.addClass('media-found');

			
			var cb = new Element('input.media-check[type=checkbox]').inject(domMedia, 'top');
			if (command.media.state != 'found')
				cb.set('checked', 'checked');
			
			domMedia.addEvent('click', function(e) {
			
				if (e.target.get('tag') != 'input') {
					var cb = this.getElement('input[type=checkbox]');
					cb.set('checked', !cb.get('checked'));
				}
			});

			if (this.scrollGlue)
				this.scroll.toElement(domMedia);
		}
	},
	synchronize: function(e)
	{
		e.preventDefault();
		
		this.mediaIndex = 0;
		this.medias = $$('.media-check:checked').getParent();
		$$('.media-check').set('disabled', 'disabled');
		$$('.synchronize-complete').destroy();

		this.scroll.toTop();
		this.scrollGlue = true;
		this.lastAlbum = false;
		this.synchronizeMedia();		
	},
	synchronizeMedia: function()
	{
		var domMedia = this.medias[this.mediaIndex];
		if (!domMedia) {
			this.synchronizeCover();
			return;
		}
		
		var media = domMedia.retrieve('media');

		if (this.lastAlbum != media.album) {
			this.synchronizeCover();
			return;
		}

		if (this.scrollGlue)
			this.scroll.toElement(domMedia);
		
		new Request({
			url: '?media=synchronize',
			data: {
				album: media.album,
				media: media.path
			},
			onRequest: function()
			{
				this.commands = [];
			}.bind(this),
			onProgress: function(e) {
				var commands = e.target.response.split(/\n/);
				commands.pop();
				this.mediaProgress(commands = commands.splice(this.commands.length, commands.length - this.commands.length));
				this.commands.append(commands);
			}.bind(this),
			onComplete: function()
			{
				this.medias[this.mediaIndex].set('class', 'media-synchronize');
			
				this.mediaIndex++;
				this.synchronizeMedia();
			}.bind(this)
		}).send();
		
	},
	mediaProgress: function(commands)
	{
		for (var i = 0, command; command = commands[i]; i++) {
			command = JSON.decode(command);

			if (command.media.state != 'found') {
			
				var domMedia = this.medias[this.mediaIndex];
				var domProgress = domMedia.getElement('.media-progress');
				
				if (command.media.state == 'convert') {
					
					if (!domProgress) {
						domProgress = new Element('div.media-progress').inject(domMedia);
						domProgress.set('tween', {property: 'width', unit: '%'});
					}
					domProgress.tween(command.media.progress);
				} else {
					if (domProgress)
						domProgress.destroy();
					new Element('span.media-synchronization', {html: locale.album['thumbnail-' + command.media.state == 'new' ? 'generated' : 'updated'].replace('%1', command.media.code)}).inject(domMedia);
				}
			}
			
		}
	},
	synchronizeCover: function()
	{
		var album = this.lastAlbum;
	
		if (album) {
			new Request({
				url: '?album=synchronize',
				data: {
					id: album
				},
				onRequest: function()
				{
					this.commands = [];
				}.bind(this),
				onProgress: function(e) {
					var commands = e.target.response.split(/\n/);
					commands.pop();
					this.coverProgress(commands = commands.splice(this.commands.length, commands.length - this.commands.length));
					this.commands.append(commands);
				}.bind(this),
				onComplete: function()
				{
					this.synchronizeMedia();
				}.bind(this)
			}).send();
		
		}
		
		this.lastAlbum = this.medias[this.mediaIndex];
		if (!this.lastAlbum) {
			if (album)
				this.complete('synchronyze');
			return;
		}
		this.lastAlbum = this.lastAlbum.retrieve('media').album;
		if (!album)
			this.synchronizeMedia();
		
	},
	coverProgress: function(commands)
	{
		for (var i = 0, command; command = commands[i]; i++) {
			command = JSON.decode(command);

			if (this.albums[command.album.fs] && command.album.state != 'found')
				new Element('span.album-cover-synchronization', {html: locale.album['cover-' + command.media.state == 'new' ? 'generated' : 'updated']}).inject(this.albums[command.album.fs]);
		}
	},
	complete: function(state)
	{
	
		if (event.target.response) {
			
			new Element('div.synchronize-complete').adopt(
				new Element('span', {html: locale.album['medias-' + state == 'analyze' ? 'analyzed' : 'synchronized']}),
				new Element('br'),
				new Element('a.syncrhonize', {href: '#', html: locale.album['synchronize-medias-selected'], events: {click: this.synchronize.bind(this)}})
			).inject(this.container);

			$$('.media-check').set('disabled');
		
			this.scroll.toBottom();
			
		} else if (state == 'analyze') {
		
			new Element('div.synchronize-complete').adopt(
				new Element('span.no', {html: locale.album['no-media-found']})
			).inject(this.container);
		
		}
	}
});



