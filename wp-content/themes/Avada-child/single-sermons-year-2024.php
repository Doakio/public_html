<?php

/**
 * Template used for pages.
 *
 * @package Avada
 * @subpackage Templates
 */

// Do not allow directly accessing this file.
if (!defined('ABSPATH')) {
	exit('Direct script access denied.');
}
?>
<?php get_header(); ?>
<section id="content" style="<?php esc_attr_e( apply_filters( 'awb_content_tag_style', '' ) ); ?>">
	<?php while (have_posts()) : ?>
		<?php the_post(); ?>
		<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
			<?php echo fusion_render_rich_snippets_for_pages(); // phpcs:ignore WordPress.Security.EscapeOutput 
			?>

			<?php avada_singular_featured_image(); ?>

			<div class="post-content">
				<?php the_content(); ?>

				<?php

				$args = array(
					'posts_per_page'	=> -1,
					'post_type'			=> 'sermons',
					'meta_query' => array(
						'relation' => 'AND',
						'date_clause' => array(
							'key' => 'delivery_date',
							'compare' => 'BETWEEN',
							'value' => array('20240101', '20241231')
						),
						'service_clause' => array(
							'key' => 'service',
							'compare' => 'EXISTS',
						),
					),
					'orderby' => array(
						'date_clause' => 'ASC',
						'service_clause' => 'ASC'
					),
				);


				// query
				$the_query = new WP_Query($args);

				?>
				<?php if ($the_query->have_posts()) : ?>
					<ul>
						<?php while ($the_query->have_posts()) : $the_query->the_post(); ?>

							<h3><?php echo
								ltrim(substr(get_field('delivery_date'), 4, 2), 0) . '/' .
									ltrim(substr(get_field('delivery_date'), 6, 2), 0) . '/' .
									substr(get_field('delivery_date'), 0, 4) . ' ';
								the_field('service')
								?><br> <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
								<?php
								if (get_field('sermon_type') == 'Slideshow')
									echo ' <span style="background-color: #ffff00; color: #3366ff;">Slides</span>';
								?>
							</h3>
							<p><?php the_field('description')  ?></p><br>
						<?php endwhile; ?>
					</ul>
				<?php endif; ?>

				<?php wp_reset_query();	 // Restore global post data stomped by the_post(). 
				?>

				<?php fusion_link_pages(); ?>
			</div>
			<?php if (!post_password_required($post->ID)) : ?>
				<?php do_action('avada_before_additional_page_content'); ?>
				<?php if (class_exists('WooCommerce')) : ?>
					<?php $woo_thanks_page_id = get_option('woocommerce_thanks_page_id'); ?>
					<?php $is_woo_thanks_page = (!get_option('woocommerce_thanks_page_id')) ? false : is_page(get_option('woocommerce_thanks_page_id')); ?>
					<?php if (Avada()->settings->get('comments_pages') && !is_cart() && !is_checkout() && !is_account_page() && !$is_woo_thanks_page) : ?>
						<?php comments_template(); ?>
					<?php endif; ?>
				<?php else : ?>
					<?php if (Avada()->settings->get('comments_pages')) : ?>
						<?php comments_template(); ?>
					<?php endif; ?>
				<?php endif; ?>
				<?php do_action('avada_after_additional_page_content'); ?>
			<?php endif; // Password check. 
			?>
		</div>
	<?php endwhile; ?>
</section>
<?php do_action('avada_after_content'); ?>
<?php get_footer(); ?>