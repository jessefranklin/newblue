jQuery('.edit-link').find('.button-set-goals').remove();
jQuery('.button-set-goals').show();
jQuery('#saveGoal').on('click', function(){
	var goals = jQuery('#TB_ajaxContent input').val();
	jQuery.ajax({
			type: 'POST',
			url: set_goals.ajaxurl,
			data: {'goals' : goals,'action':'set-goals'}, 
			success: function(data) {
				jQuery('.goalsNo').html(goals);
				jQuery('#TB_closeWindowButton').trigger('click');
			}
	});	
});

	
