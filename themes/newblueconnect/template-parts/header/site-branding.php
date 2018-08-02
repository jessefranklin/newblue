<?php
/**
 * Displays header site branding
 *
 * @package WordPress
 * @subpackage Twenty_Seventeen
 * @since 1.0
 * @version 1.0
 */

?>
<div class="site-branding">
	<div class="wrap">

		<?php the_custom_logo(); ?>
		<div class="intel-logo">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">
				<img src= "/wp-content/themes/newblueconnect/images/intel_white.png" alt="Intel Logo">
			</a>
		</div>
		<div class="site-branding-text">
			<?php if ( is_front_page() ) : ?>
				<h1 class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></h1>
			<?php else : ?>
				<p class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></p>
			<?php endif; ?>

			<?php
			$description = get_bloginfo( 'description', 'display' );

			if ( $description || is_customize_preview() ) :
			?>
				<p class="site-description"><?php echo $description; ?></p>
			<?php endif; ?>
		</div><!-- .site-branding-text -->
		<span class="header-links">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a>
			<?php if(is_user_logged_in() && current_user_can('administrator') || current_user_can('editor') || current_user_can('author') || current_user_can('contributor')) : ?>
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>create-an-event/">Create an Event</a>
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>my-events/">My Events</a>
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>resources/">Resources</a>
			<?php endif; ?>
		</span>
		<div id="evo_search" class="EVOSR_section ">
			<div class="evo_search_entry">
				<p class="evosr_search_box">
					<input type="text" placeholder="Search Calendar Events" data-role="none">
					<a class="evo_do_search"><i class="fa fa-search"></i></a>
					<span class="evosr_blur"></span>
					<span class="evosr_blur_process"></span>
					<span class="evosr_blur_text">Searching</span>
					<span style="display:none" class="data" data-number_of_months="12" data-search_all="yes" data-lang="L1"></span>
				</p>
				<p class="evosr_msg" style="display:none">What do you want to search for?</p>
			</div>
			<p class="evo_search_results_count" style="display:none"><span>10</span> Event(s) found</p>
			<div class="evo_search_results"></div>
		</div>
		<?php if ( ( twentyseventeen_is_frontpage() || ( is_home() && is_front_page() ) ) && ! has_nav_menu( 'top' ) ) : ?>
		<a href="#content" class="menu-scroll-down"><?php echo twentyseventeen_get_svg( array( 'icon' => 'arrow-right' ) ); ?><span class="screen-reader-text"><?php _e( 'Scroll down to content', 'twentyseventeen' ); ?></span></a>
	<?php endif; ?>

	</div><!-- .wrap -->
</div>
<div>
<p class="notice" style="background:#009fdf;padding:20px;margin-left:20px;margin-right:20px;">
Hello and thank you for visiting The New Blue Connect portal. The New Blue is, well, new…Currently, we are loading events by executive and region, and will keep updating this portal with global and local forums, trainings, volunteer events and more. Please bookmark this page and come back often to search, register and connect.
</p>
</div>
<!-- .site-branding -->
