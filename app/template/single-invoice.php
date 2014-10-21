<?php
get_header(); ?>

<div id="main-content" class="main-content">
	<div id="primary" class="content-area">
		<div id="content" class="site-content" role="main">
			<article>
				<?php
					global $post;

					$attr = array( 'post_id' => $post->ID );
					$scode = MS_Plugin::instance()->controller->controllers['membership_shortcode'];
					echo $scode->membership_invoice( $attr );
				?>
			</article>
		</div><!-- #content -->
	</div><!-- #primary -->
</div><!-- #main-content -->

<?php
get_sidebar();
get_footer();