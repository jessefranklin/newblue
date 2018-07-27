<?php
/**
 * Event Manager for frontend user submitted events
 * @version 2.1.3
 * @author  AJDE
 *
 * You can copy this template file and place it in ...wp-content/themes/<--your-theme-name->/eventon/actionuser/ folder
 * and edit that file to customize this template.
 * This sub content template will use default page.php template from your theme and the below
 * content will be placed in content area of the page template.
 */


	// INITIAL
	$fnc = new evoau_functions();

	do_action('evoau_manager_print_styles');

?>


<div id='evoau_event_manager' class='evoau_manager' data-mce='0'>

<?php $fnc->print_hidden_editor();?>

<?php

	if(!is_user_logged_in()){
		echo "<p class='intro'>".$fnc->get_lang('Login required to manage your submitted events')." <br/><a href='".wp_login_url($current_page_link)."' class='evcal_btn evoau'><i class='fa fa-user'></i> ".$fnc->get_lang('Login Now')."</a></p>";
		return;
	}
?>

<p><?php echo $fnc->get_lang('Hello');?> <?php echo $current_user->display_name?>. <?php echo $fnc->get_lang('From your event manager dashboard you can view and manage your submitted events.');?></p>


<h3><?php echo $fnc->get_lang('My Submitted Events');?></h3>

<?php

	// GET events for the current user
	$events = $eventon_au->frontend->get_user_events($current_user->ID);

	if($events){
		?>
		<div class='evoau_manager_event_section'>
		<div class='evoau_manager_event_list'>
		<div class='eventon_actionuser_eventslist'>
			<div class='evoau_delete_trigger' style="display:none" data-eid=''>
				<p class='deletion_message'><?php evo_lang_e('Are you sure you want to delete this event');?>? <br/><span class='ow'><?php evo_lang_e('Yes');?></span> <span class='nehe'><?php evo_lang_e('No');?></span></p>
			</div>
			<?php
			do_action('evoau_manager_before_events', $events, $atts);

			// get each event
			do_action('evoau_manager_print_events', $events, $atts);

			do_action('evoau_manager_after_events', $atts);

			?>
		</div>
		<div class='evoau_manager_event'>
			<p style='margin-bottom:10px'><a class='evoau evoau_back_btn'><i class='fa fa-angle-left'></i> <?php echo $fnc->get_lang('Back to my events');?></a></p>
			<div class='evoau_manager_event_content'></div>
		</div>
		<div class='clear'></div>
		</div>
		</div>
		<?php
	}else{
		echo "<p class='evoau_outter_shell'>". evo_lang('You do not have submitted events') . "</p>";
	}
?>
</div>
