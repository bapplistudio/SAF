$('document').ready(function()
{

	$('.window').build(function()
	{

		//-------------------------------------------------------------- select.customized option click
		this.inside('select.customized').change(function()
		{
			var $this = $(this);
			$this.attr('name', 'load_name');
			$this.closest('form').submit();
		});

		//------------------------------------------------------------------- .save_list.button a click
		// click on save button opens the save form between calling save
		this.inside('a.custom_save, .custom_save>a').click(function(event)
		{
			var $this = $(this);
			var $list = $this.closest('form');
			var $input = $list.find('input.customized');
			if (!$input.filter(':visible').length) {
				event.preventDefault();
				event.stopImmediatePropagation();
				$input.parent().find('select.customized').hide();
				$input
					.attr('name', 'save_name')
					.fadeIn(200)
					.keyup()
					.focus();
				$input.get(0).close = function()
				{
					var $this = $(this);
					$this.fadeOut(200);
					$this.removeAttr('name');
					setTimeout(function() { $this.parent().find('select.customized').show(); }, 220);
				};
			}
			else if (!$input.val()) {
				event.preventDefault();
				event.stopImmediatePropagation();
				alert('Veuillez saisir un nom puis valider, ou tapez echap pour annuler');
			}
		});
		var $input = this.inside('input.customized');
		$input.autowidth();
		// press ENTER : save, press ESCAPE : cancel
		$input.keydown(function(event)
		{
			var $this = $(this);
			if (event.keyCode == $.ui.keyCode.ENTER) {
				$this.closest('form').find('a.custom_save, .custom_save>a').click();
				event.preventDefault();
			}
			if (event.keyCode == $.ui.keyCode.ESCAPE) {
				this.close();
			}
		});
		// loose focus more than 1 second (without coming back) : cancel
		$input.blur(function()
		{
			var input = this;
			input.is_inside = false;
			setTimeout(function() { if (!input.is_inside) input.close(); }, 100);
		});
		$input.focus(function()
		{
			this.is_inside = true;
		});

	});

});
