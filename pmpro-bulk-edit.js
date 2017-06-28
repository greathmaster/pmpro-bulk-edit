(function($) {

	// we create a copy of the WP inline edit post function
	var $wp_inline_edit = inlineEditPost.edit;
	
	// and then we overwrite the function with our own code
	inlineEditPost.edit = function( id ) {
	
		// "call" the original WP edit function
		// we don't want to leave WordPress hanging
		$wp_inline_edit.apply( this, arguments );
		
		// now we take care of our business
		
		// get the post ID
		var $post_id = 0;
		if ( typeof( id ) == 'object' )
			$post_id = parseInt( this.getId( id ) );
		
		if ( $post_id > 0 ) {
			
		$.ajax({
			url:ajaxurl,
			data: {
				'action':'pmprobe_retrieve_levels_callback',
				'post_id': $post_id
			},
			success:function(data) {
					var levels = data.split(',');
					levels.forEach(function(val) {
						$("#pmproquickedit_" + val).prop('checked', true);
					});
			},
			error: function(error){}
			});			
		}
		
	};

	$( document ).on( 'click', '#bulk_edit', function() {
	
		// define the bulk edit row
		var $bulk_row = $( '#bulk-edit' );
		
		// get the selected post ids that are being edited
		var $post_ids = new Array();
		$bulk_row.find( '#bulk-titles' ).children().each( function() {
			$post_ids.push( $( this ).attr( 'id' ).replace( /^(ttle)/i, '' ) );
		});
		
		var $levels = [];
		var $change = $('#pmpro_bulk_edit_levels').val();
		
		$('input:checkbox[id^="pmprobulkedit_"]:checked').each(function(){
		
			var l = $(this).attr("id");
			var i = l.indexOf('_');
			l = l.slice(i+1);
			$levels.push(l);
		});
				
		// save the data
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'pmprobe_save_bulk_edit_callback',
				post_ids: $post_ids,
				levels:	$levels,
				change:	$change
			}
		});
	});

	function pmprobe_show_hide_bulk_edit_levels()
	{		
		var show_bulk_edit = $('#pmpro_bulk_edit_levels').val();

		if(show_bulk_edit === 'no')
			$('#pmpro_levels_bulk_edit_checkboxes').hide();
		
		else
			$('#pmpro_levels_bulk_edit_checkboxes').show();
	}
	
	$('#pmpro_bulk_edit_levels').change(function(){
		pmprobe_show_hide_bulk_edit_levels();
	});
	
	pmprobe_show_hide_bulk_edit_levels();

})(jQuery);


