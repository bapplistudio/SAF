$('document').ready(function()
{

	$('body').build(function()
	{
		if (!this.length) return;

		// sort and decoration
		this.inside('ul.property_tree').sortcontent();
		this.inside('.property_select').prepend($('<span>').addClass('joint'));

		// search
		this.inside('.property_select>input[name=search]').each(function()
		{
			var last_search = '';
			var search_step = 0;
			$(this).keyup(function(event)
			{
				var $this = $(this);
				if (event.keyCode == $.ui.keyCode.ESCAPE) {
					$this.closest('#column_select.popup').fadeOut(200);
				}
				else {
					var new_search = $this.val();
					if ((last_search != new_search) && !search_step) {
						search_step = 1;
						last_search = new_search;
						$.ajax(
							window.app.uri_base + '/SAF/Framework/Property/search'
								+ '/' + $this.closest('[data-class]').data('class').replace('/', '\\')
								+ '?search=' + encodeURI(new_search)
								+ '&as_widget' + window.app.andSID(),
							{
								success: function(data) {
									search_step = 2;
									var $property_tree = $this.parent().children('.property_tree');
									$property_tree.html(data);
									$property_tree.build();
								}
							}
						);
						var retry = function()
						{
							if (search_step == 1) {
								setTimeout(retry, 200);
							}
							else {
								search_step = 0;
								if ($this.val() != last_search) {
									$this.keyup();
								}
							}
						};
						setTimeout(retry, 500);
					}
				}
			});
		});

		// create tree
		this.inside('ul.property_tree>li>a').click(function(event)
		{
			var $this = $(this);
			var $li = $(this).closest('li');
			if ($li.children('section').length) {
				if ($li.children('section:visible').length) {
					$this.removeClass('expanded');
					$li.children('section:visible').hide();
				}
				else {
					$this.addClass('expanded');
					$li.children('section:not(:visible)').show();
				}
				event.stopImmediatePropagation();
				event.preventDefault();
			}
			else {
				$this.addClass('expanded');
			}
		});

		// draggable items
		this.inside('.property, fieldset>div[id]>label').draggable({
			appendTo:    'body',
			containment: 'body',
			cursorAt:    { left: 10, top: 10 },
			delay:       500,
			scroll:      false,

			helper: function()
			{
				var $this = $(this);
				return $('<div>')
					.addClass('property')
					.attr('data-class',    $this.closest('.window').data('class'))
					.attr('data-feature',  $this.closest('.window').data('feature'))
					.attr('data-property', $this.data('property'))
					.css('z-index', ++zindex_counter)
					.html($this.text());
			},

			drag: function(event, ui)
			{
				var $this = $(this);
				var $droppable = $this.data('over-droppable');
				if ($droppable != undefined) {
					var draggable_left = ui.offset.left;
					var count = 0;
					var found = 0;
					$droppable.find('thead>tr:first>th:not(:first)').each(function() {
						count ++;
						var $this = $(this);
						var $prev = $this.prev('th');
						var left = $prev.offset().left + $prev.width();
						var right = $this.offset().left + $this.width();
						if ((draggable_left > left) && (draggable_left <= right)) {
							found = (draggable_left <= ((left + right) / 2)) ? count : (count + 1);
							var old = $droppable.data('insert-after');
							if (found != old) {
								if (old != undefined) {
									$droppable.find('colgroup>col:nth-child(' + old + ')').removeClass('insert_after');
								}
								if (found > 1) {
									$droppable.find('colgroup>col:nth-child(' + found + ')').addClass('insert_after');
									$droppable.data('insert-after', found);
								}
							}
						}
					});
					$this.closest('.closeable-popup').fadeOut(200);
				}
			},

			stop: function()
			{
				$(this).closest('.closeable-popup').fadeIn(200);
			}

		});

	});

	// hide popup select box when clicking outside of it
	$(document).click(function(event)
	{
		var $column_select = $('#column_select.popup>.property_select');
		if ($column_select.length) {
			var offset = $column_select.offset();
			if (!(
				(event.pageX > offset.left) && (event.pageX < (offset.left + $column_select.width()))
				&& (event.pageY > offset.top) && (event.pageY < (offset.top + $column_select.height()))
			)) {
				$column_select.parent().fadeOut(200);
			}
		}
	});

});
