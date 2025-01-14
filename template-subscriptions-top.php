<?php 
/**
 * Template Name: Subscription Top
 */

get_header();

// Globals
global $wpdb;

// Condition
$where = '1 = 1';

$start = filter_input( INPUT_GET, 'start', FILTER_SANITIZE_STRING );
$start = empty( $start ) ? '-1 week' : $start;

if ( ! empty( $start ) ) {
	$where = $wpdb->prepare(
		'timesheet.date BETWEEN %s AND %s',
		date( 'Y-m-d', strtotime( $start ) ),
		date( 'Y-m-d' )
	);
}

// Query
$query =  "
	SELECT
		subscription.id AS subscription_id,
		subscription.post_id AS subscription_post_id,
		company.name AS company_name,
		company.post_id AS company_post_id,
		product.name AS product_name,
		subscription.name AS subscription_name,
		SUM( timesheet.number_seconds ) AS subscription_seconds
	FROM
		$wpdb->orbis_subscriptions AS subscription
			LEFT JOIN
		$wpdb->orbis_products AS product
				ON subscription.product_id = product.id
			LEFT JOIN
		$wpdb->orbis_timesheets AS timesheet
				ON subscription.id = timesheet.subscription_id
			LEFT JOIN
		$wpdb->orbis_companies AS company
				ON subscription.company_id = company.id
	WHERE
		subscription.cancel_date IS NULL
			AND
		%s
	GROUP BY
		subscription.id
	ORDER BY
		subscription_seconds DESC
	LIMIT
		0, 100
	;
";

$query = sprintf( $query, $where );

$results = $wpdb->get_results( $query );

?>

<form class="form-inline" method="get" action="">
	<div class="row">
		<div class="col-md-2">

		</div>
	
		<div class="col-md-6">			

		</div>
	
		<div class="col-md-4">
			<div class="pull-right">
				<select name="start" id="user" class="form-control">
					<?php

					$filter = array(
						''          => 'Totaal',
						'-1 year'   => 'Afgelopen jaar',
						'-6 months' => 'Afgelopen half jaar',
						'-3 months' => 'Afgelopen 3 maanden',
						'-1 month'  => 'Afgelopen maand',
						'-1 week'   => 'Afgelopen week',
					);

					foreach ( $filter as $value => $label ) {
						printf(
							'<option value="%s" %s>%s</option>',
							esc_attr( $value ),
							selected( $start, $value, false ),
							esc_html( $label )
						);
					}

					?>
				</select>

				<button type="submit" class="btn btn-default">Filter</button>
			</div>
		</div>
	</div>
</form>

<hr />

<table class="table table-striped table-bordered panel">
	<thead>
		<tr>
			<th><?php _e( 'Company', 'orbis_pronamic' ); ?></th>
			<th><?php _e( 'Subscription', 'orbis_pronamic' ); ?></th>
			<th><?php _e( 'Name', 'orbis_pronamic' ); ?></th>
			<th><?php _e( 'Time', 'orbis_pronamic' ); ?></th>
		</tr>
	</thead>
	<tbody>

		<?php foreach( $results as $row ) : ?>
		
			<tr>
				<td>
					<a href="<?php echo add_query_arg( 'p', $row->company_post_id, home_url( '/' ) ); ?>">
						<?php echo $row->company_name; ?>
					</a>
				</td>
				<td>
					<?php echo $row->product_name; ?>
				</td>
				<td>
					<a href="<?php echo add_query_arg( 'p', $row->subscription_post_id, home_url( '/' ) ); ?>">
						<?php echo $row->subscription_name; ?>
					</a>
				</td>
				<td>
					<?php echo orbis_time( $row->subscription_seconds ); ?>
				</td>
			</tr>

		<?php endforeach; ?>
	</tbody>
</table>

<?php get_footer(); ?>
