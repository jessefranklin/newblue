<?php
/** 
 * Wishlist manager for frontend user's events in wishlist
 * @version 0.1
 * @author  AJDE
 *
 * You can copy this template file and place it in ...wp-content/themes/<--your-theme-name->/eventon/wishlist/ folder 
 * and edit that file to customize this template.
 */
	
	echo "<h2>".evo_lang('My Wishlist Events')."</h2>";
	if(!is_user_logged_in()){

		$current_page_link = get_page_link();


		$login_url = wp_login_url($current_page_link);
		echo "<p>".evo_lang('Login required to manage your wishlist events')." <br/><a href='".$login_url."' class='evcal_btn evowi'><i class='fa fa-user'></i> ".evo_lang('Login Now')."</a></p>";
		return;
	}

	$current_user = get_user_by( 'id', get_current_user_id() );
	?>

	<p><?php echo evo_lang('Hello');?> <?php echo $current_user->display_name?>. <?php echo evo_lang('You can view and manage the events you have added to your wishlist from here. Past events in your wishlist will not show in here.');?></p>
	
	<?php

	echo $evowi->front->shortcode_content($atts);