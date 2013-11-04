window.addEvent('domready', function() {
	new SimpleGallerySynchronizer();
});

var SimpleGallerySynchronizer = new Class({
	initialize: function()
	{
		this.container = $('synchronizer');
		
		this.initScrollGlue();
		
		new Request({
			url: '?album=synchronize',
			onRequest: function()
			{
				this.commands = [];
			}.bind(this),
			onProgress: function(e) {
				var commands = e.target.response.split(/\n/);
				commands.pop();
				this.progress(commands = commands.splice(this.commands.length, commands.length - this.commands.length));
				this.commands.append(commands);
			}.bind(this),
			onComplete: function()
			{
				new Element('div.synchronize-complete').adopt(
					new Element('span', {html: 'Albums synchronized'}),
					new Element('br'),
					new Element('a', {href: '?album=loader', html: 'back'})
				).inject(this.container);
			}.bind(this)
		}).send();

		
	},
	initScrollGlue: function()
	{
		this.content = $('content');
		
		this.scrollGlue = true;
				
		this.content.previousScroll = this.content.scrollTop;
		this.scroll = new Fx.Scroll(this.content, {duration: 0});
		
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
			
			var domAlbum = new Element('div.album-synchronize', {html: command.album.path || '/'}).inject(this.container);
			
			if (command.album.state == 'new')
				domAlbum.addClass('album-new');
			else if (command.album.state == 'delete')
				domAlbum.addClass('album-delete');
			else if (command.album.state == 'update') {
				domAlbum.addClass('album-update');
				if (command.album.update.oldPath)
					new Element('span.album-updates', {html: ' <= ' + command.album.update.oldPath}).inject(domAlbum);
			} else
				domAlbum.addClass('album-found');

			if (this.scrollGlue)
				this.scroll.toElement(domAlbum);
		}
	}
});



