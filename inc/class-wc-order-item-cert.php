<?php

/**
 * Class WC_Order_Item_LP_Cert
 */
class WC_Order_Item_LP_Cert extends WC_Order_Item_Product {

	public function set_product_id( $value ) {
		if ( $value > 0 && 'lp_cert' !== get_post_type( absint( $value ) ) ) {
			$this->error( 'order_item_product_invalid_product_id', __( 'Invalid product ID', 'woocommerce' ) );
		}

		$cert_id = wc_get_order_item_meta( $this->get_id(), '_lp_cert_id' );

		if ( 'lp_cert' == get_post_type( absint( $cert_id ) ) ) {
			$value = $cert_id;
		}
		$this->set_prop( 'product_id', absint( $value ) );
	}
}
