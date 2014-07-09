<?php
get_header(); ?>

<div id="main-content" class="main-content">
	<div id="primary" class="content-area">
		<div id="content" class="site-content" role="main">
			<article>
				<?php
					global $post;
					echo do_shortcode( "[" . MS_Helper_Shortcode::SCODE_MS_INVOICE . " post_id='$post->ID' ]" );
				?>
			</article>	
		</div><!-- #content -->
	</div><!-- #primary -->
</div><!-- #main-content -->

<?php
get_sidebar();
get_footer();