<?php
/**
 * Code-snippet for WordPress pointers.
 * Used in function lib3()->html->pointer()
 *
 * @since  1.0.0
 *
 * Variables:
 *   - $pointer_id
 *   - $html_el
 *   - $title
 *   - $body
 *   - $once
 *   - $modal
 *   - $blur
 */

$class = 'wpmui-pointer prepared';
if ( ! empty( $title ) ) {
	$title = '<h3>' . $title . '</h3>';
} else {
	$title = '';
	$class .= ' no-title';
}

$code = sprintf(
	'<div class="%3$s">%1$s<p>%2$s</p></div>',
	$title,
	$body,
	esc_attr( $class )
);

// Remove linebreaks to avoid JS errors
$code = str_replace( array("\r", "\n"), '', $code );

?>
<script>
	jQuery(document).ready(function() {
		var wpcontent = jQuery( '#wpbody' ),
			body = jQuery( 'body' );

		if ( jQuery().pointer !== undefined ) {
			var target = jQuery( '<?php echo $html_el; ?>' );
			if ( ! target.length ) { return; }
			target = target.first();

			<?php if ( $blur ) : ?>
			wpcontent.addClass( 'wpmui-blur' );
			<?php else : ?>
			body.addClass( 'no-blur' );
			<?php endif; ?>

			<?php if ( $modal ) : ?>
			var modal = wpmUi._make_modal( 'light' );
			if ( undefined !== modal ) {
				modal.on( 'click', function( ev ) {
					target.pointer( 'close' );
				});
			} else {
				wpmUi._close_modal();
			}
			<?php endif; ?>

			// Insert the pointer HTML code
			target.pointer({
				content: '<?php echo $code; ?>',
				position: {
					edge: 'left',
					align: 'center'
				},
				close: function() {
					<?php if ( $blur ) : ?>
					wpcontent.removeClass( 'wpmui-blur' );
					<?php else : ?>
					body.removeClass( 'no-blur' );
					<?php endif; ?>

					<?php if ( $modal ) : ?>
					wpmUi._close_modal();
					<?php endif; ?>

					<?php if ( $once ) : ?>
					jQuery.post( ajaxurl, {
						pointer: '<?php echo esc_js( $pointer_id ) ?>',
						action: 'dismiss-wp-pointer'
					});
					<?php endif; ?>
				}
			}).pointer('open');

			// Modify the default pointer style
			jQuery( '.wpmui-pointer.prepared' ).each(function() {
				var me = jQuery(this),
					ptr = me.closest('.wp-pointer');
				me.removeClass('prepared');
				ptr.addClass( me.attr( 'class' ) );
				me.removeClass('wpmui-pointer');
			});
		}
	});
</script>