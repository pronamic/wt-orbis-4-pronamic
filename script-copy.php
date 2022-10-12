<script src="https://unpkg.com/popper.js@1"></script>
<script src="https://unpkg.com/tippy.js@5"></script>

<script src="https://cdn.jsdelivr.net/npm/clipboard@2.0.4/dist/clipboard.min.js" integrity="sha256-FiZwavyI2V6+EXO1U+xzLG3IKldpiTFf3153ea9zikQ=" crossorigin="anonymous"></script>

<script type="text/javascript">
	var clipboard = new ClipboardJS( '.orbis-copy', {
		text: function( trigger ) {
			var selector = trigger.getAttribute( 'data-clipboard-target' );

			var element = document.querySelector( selector );

			return element.innerHTML;
		}
	} );

	clipboard.on( 'success', function( e ) {
		if ( ! e.trigger._tippy ) {
			tippy( e.trigger, {
	  			content: 'Gekopieerd',
	  			trigger: 'manual'
			} );
		}

		e.trigger._tippy.show();
	} );

	// Global config for all <button>s
	
</script>
