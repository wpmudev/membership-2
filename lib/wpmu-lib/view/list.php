<?php
/**
 * Code-snippet for WordPress plugin list.
 * Used in function lib3()->html->addon_list()
 *
 * @since  1.1.0
 *
 * Variables:
 *   - $items
 *   - $lang
 *   - $filters
 */

$item_fields = array(
	'class',
	'title',
	'description',
	'version',
	'author',
	'active',
	'action', // Array
	'details', // Array
	'icon',
	'footer',
);

$current = 'current';

?>
<div class="wpmui-list-wrapper">

<?php if ( ! empty( $filters ) ) : ?>
<div class="wp-filter"><ul class="filter-links"><?php
	foreach ( $filters as $key => $label ) {
		printf(
			'<li><a href="#" class="filter %3$s" data-filter="%1$s">%2$s</a></li>',
			$key,
			$label,
			$current
		);
		$current = '';
	}
?></ul></div>
<?php endif; ?>

<div class="wp-list-table widefat wpmui-list-table">
<div class="the-list wpmui-list">
	<?php foreach ( $items as $item ) :
		self::$core->array->equip( $item, $item_fields );
		if ( isset( $item->action ) && is_array( $item->action ) ) {
			$item->details = self::$core->array->get( $item->details );
		} else {
			$item->action = array();
			$item->details = array();
		}

		$item_class = $item->active ? 'active' : '';
		$item_class .= ' ' . $item->class;
		?>
		<div class="list-card <?php echo esc_attr( $item_class ); ?>">
			<div class="list-card-top">
				<span class="badge-container">
					<span class="badge-active">
						<?php echo esc_html( $lang->active_badge ); ?>
					</span>
				</span>
				<div class="item-icon"><?php echo $item->icon; ?></div>
				<div class="name">
					<h4 class="<?php if ( $item->details ) : ?>toggle-details<?php endif; ?> is-no-detail">
						<?php echo esc_html( $item->title ); ?>
					</h4>
					<h4 class="is-detail">
						<?php echo esc_html( $item->title ); ?>
					</h4>
				</div>
				<div class="desc">
					<?php echo $item->description; ?>
				</div>
				<div class="action-links">
					<span class="toggle-details toggle-link is-detail close-button">
					</span>
					<?php
					foreach ( $item->action as $action ) {
						self::$core->html->element( $action );
					}
					?>
				</div>
				<div class="details">
					<?php
					foreach ( $item->details as $detail ) {
						if ( is_array( $detail ) ) {
							if ( isset( $detail['ajax_data'] )
								&& is_array( $detail['ajax_data'] )
							) {
								$detail['ajax_data']['_is_detail'] = true;
							}
						}
						self::$core->html->element( $detail );
					}
					?>
				</div>
				<div class="fader"></div>
			</div>
			<div class="list-card-bottom">
				<span class="list-card-footer is-no-detail">
					<?php echo $item->footer; ?>
				</span>
				<?php if ( $item->details ) : ?>
				<span class="toggle-details toggle-link is-no-detail">
					<?php echo esc_html( $lang->show_details ); ?>
				</span>
				<span class="toggle-details toggle-link is-detail">
					<?php echo esc_html( $lang->close_details ); ?>
				</span>
				<?php endif; ?>
			</div>
		</div>
	<?php endforeach; ?>
</div>
</div>
</div>