<?php

/**
 * Includes
 */
require_once get_stylesheet_directory() . '/includes/widgets/Orbis_Timesheets_Widget.php';

/**
 * Sets up theme defaults and registers support for various WordPress features.
 */
function orbis_pronamic_setup() {
	/* Make theme available for translation */
	load_theme_textdomain( 'orbis_pronamic', get_stylesheet_directory() . '/languages' );
}

add_action( 'after_setup_theme', 'orbis_pronamic_setup' );

/**
 * Register our sidebars and widgetized areas.
 */
function orbis_pronamic_widgets_init() {
	register_widget( 'Orbis_Timesheets_Widget' );

	register_sidebar( array(
		'name'          => __( 'Shop Widget Area', 'orbis_pronamic' ),
		'id'            => 'shop-widget',
		'before_widget' => '<aside id="%1$s" class="widget %2$s">',
		'after_widget'  => '</aside>',
		'before_title'  => '<h3 class="widget-title">',
		'after_title'   => '</h3>',
	) );
}

add_action( 'widgets_init', 'orbis_pronamic_widgets_init' );

/**
 * Woocommerce
 */

/* Number products */
function orbis_number_products() {
	return 36;
}

add_filter( 'loop_shop_per_page', 'orbis_number_products', 20 );

/* Number columns */
function orbis_products_per_row() {
	return 4;
}

add_filter( 'loop_shop_columns', 'orbis_products_per_row', 20 );

/* Thumbnail */
remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10 );

/* Grid */
function orbis_woocommerce_grid() {
	remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );
	add_action( 'woocommerce_before_main_content', 'woocommerce_get_sidebar', 20 );
}

add_action( 'template_redirect', 'orbis_woocommerce_grid' );

/**
 * Timesheets after main content.
 */
add_action( 'orbis_after_main_content', function() {
	$post_types = array(
		'orbis_project'      => 'orbis_project_timesheet',
		'orbis_subscription' => 'orbis_subscription_timesheet',
	);

	if ( ! is_singular( array_keys( $post_types ) ) ) {
		return;
	}

	$post_type = get_post_type();

	if ( array_key_exists( $post_type, $post_types ) ) {
		get_template_part( $post_types[ $post_type ] );
	}
} );

add_action( 'orbis_before_side_content', function() {
	if ( ! is_singular( 'orbis_company' ) ) {
		return;
	}

	$post = get_post();

	?>
	<div class="card mb-3">
		<div class="card-header"><?php esc_html_e( 'Agreement Form', 'orbis' ); ?></div>

		<div class="list-group">
			<?php

			$post = get_post();

			$args = array(
				'bedrijf'        => get_the_title( $post ),
				'kvk-nummer'     => get_post_meta( $post->ID, '_orbis_kvk_number', true ),
				'btw-nummer'     => get_post_meta( $post->ID, '_orbis_vat_number', true ),
				'voornaam'       => '',
				'achternaam'     => '',
				'straat'         => get_post_meta( $post->ID, '_orbis_address', true ),
				'postcode'       => get_post_meta( $post->ID, '_orbis_postcode', true ),
				'plaats'         => get_post_meta( $post->ID, '_orbis_city', true ),
				'factuur-e-mail' => get_post_meta( $post->ID, '_orbis_invoice_email', true ),
				'referentie'     => '',
				'eenmalig'       => '0',
				'jaarlijks'      => '0',
				'maandelijks'    => '0',
			);

			$url_agreement_form = 'https://www.pronamic.nl/akkoord/';

			$url_agreement_form = add_query_arg( urlencode_deep( $args ), $url_agreement_form );

			$products = array(
				(object) array(
					'name'  => 'Strippenkaart 2 uren',
					'price' => '190',
				),
				(object) array(
					'name'  => 'Strippenkaart 5 uren',
					'price' => '450',
				),
				(object) array(
					'name'  => 'Strippenkaart 10 uren',
					'price' => '850',
				),
			);

			foreach ( $products as $product ) {
				$url = add_query_arg(
					urlencode_deep(
						array(
							'referentie' => $product->name,
							'eenmalig'   => $product->price,
						)
					),
					$url_agreement_form
				);

				printf(
					'<a href="%s" class="list-group-item list-group-item-action">%s</a>',
					esc_url( $url ),
					esc_html( $product->name )
				);
			}

			?>
		</div>
	</div>
	<?php
} );
