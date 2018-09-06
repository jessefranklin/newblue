<?php
/**
 * Template part for displaying page content in page.php
 *
 * @link https://codex.wordpress.org/Template_Hierarchy
 *
 * @package WordPress
 * @subpackage Twenty_Seventeen
 * @since 1.0
 * @version 1.0
 */

?>
<?php global $current_user; wp_get_current_user(); ?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<header class="entry-header">
		<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
		<?php twentyseventeen_edit_link( get_the_ID() ); ?>
		<?php if(is_page('my-events')) : ?>
			<div class="exec-dash">
				<div class="user-profile">
					<img src="https://via.placeholder.com/50x50">
					<div class="user-info">
						<p><?php echo $current_user->display_name ?></p>
						<p>VP North America (placeholder)</p>
						<p>Other Info (placeholder)</p>
					</div>
				</div>
				<div class="event-overview">
					<h3>5/8 Events Created</h3>
					<div class="event-rating">
						<div class="star-rating">
							<i class="fa fa-star" aria-hidden="true"></i>
							<i class="fa fa-star" aria-hidden="true"></i>
							<i class="fa fa-star" aria-hidden="true"></i>
							<i class="fa fa-star" aria-hidden="true"></i>
						</div>
						<h4>Average rating:</h4>
						<h4>4/5 Stars</h4>
					</div>
				</div>
			</div>
		<?php endif; ?>
	</header><!-- .entry-header -->
	<p class="evo_search_results_count" style="display:none"><span>10</span> Event(s) found</p>
	<div class="evo_search_results"></div>
	<div class="entry-content">
		<?php
			the_content();

			wp_link_pages( array(
				'before' => '<div class="page-links">' . __( 'Pages:', 'twentyseventeen' ),
				'after'  => '</div>',
			) );
		?>
	</div><!-- .entry-content -->
	<?php if(is_page('my-events')){
		get_sidebar('dash');
	}else{
		get_sidebar('other');
	} ?>
</article><!-- #post-## -->
