<?php
/***
 * Order Certificate Item Details
 *
 * @version 1.0
 * @since   3.1.4
 */

if ( ! isset( $item ) ) {
	return;
}

if ( ! empty( $item['_lp_cert_id'] ) ) {
	?>
	<tr class="order-item-row" data-item_id="<?php echo $item['id']; ?>" data-id="<?php echo $item['_lp_cert_id']; ?>"
		data-remove_nonce="<?php echo wp_create_nonce( 'remove_order_item' ); ?>">
		<td class="column-name">
			<?php if ( isset( $order ) && 'pending' === $order->get_status() ) { ?>
				<a class="remove-order-item" href="">
					<span class="dashicons dashicons-trash"></span>
				</a>
			<?php } ?>
			<?php
			do_action( 'learn_press/before_order_details_item_title', $item );

			$title_course = '';

			if ( isset( $item['_lp_course_id_of_cert'] ) ) {
				$title_course = get_the_title( $item['_lp_course_id_of_cert'] );
			}

			$link_item = '<a href="' . get_the_permalink( $item['_lp_cert_id'] ) . '">' . sprintf( '%s %s - %s', __( 'Certificate:', 'learnpress-certificates' ), get_the_title( $item['_lp_cert_id'] ), $title_course ) . '</a>';
			echo apply_filters( 'learn_press/order_detail_cert_item_link', $link_item, $item );

			do_action( 'learn_press/after_order_details_cert_item_title', $item );
			?>
		</td>
		<td class="column-price align-right">
			<?php echo learn_press_format_price( isset( $item['total'] ) ? $item['total'] : 0, isset( $currency_symbol ) ? $currency_symbol : '$' ); ?>
		</td>
		<td class="column-quantity align-right">
			<small class="times">×</small>
			<?php echo isset( $item['quantity'] ) ? $item['quantity'] : 0; ?>
		</td>
		<td class="column-total align-right"><?php echo learn_press_format_price( isset( $item['total'] ) ? $item['total'] : 0, isset( $currency_symbol ) ? $currency_symbol : '$' ); ?></td>
	</tr>
	<?php
}