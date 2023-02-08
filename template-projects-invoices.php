<?php 
/**
 * Template Name: Projects Invoices
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
		'pi.create_date BETWEEN %s AND %s',
		date( 'Y-m-d', strtotime( $start ) ),
		date( 'Y-m-d', strtotime( 'tomorrow' ) )
	);
}

// Query
$query =  "
	SELECT
		pi.id AS id,
		company.name AS company_name,
		company.post_id AS company_post_id,
		project.name AS project_name,
		project.post_id AS project_post_id,
		pi.amount AS invoice_amount,
		pi.create_date AS invoice_date,
		pi.invoice_number AS invoice_number
	FROM
		orbis_projects_invoices AS pi
			LEFT JOIN
		orbis_projects AS project
				ON pi.project_id = project.id
			LEFT JOIN
		orbis_companies AS company
				ON project.principal_id = company.id
	WHERE
		%s
	;
";

$query = sprintf( $query, $where );

$results = $wpdb->get_results( $query );

?>

<div class="d-flex justify-content-end">
	<form class="form-inline" method="get" action="">
		<span>
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

			<button type="submit" class="btn btn-primary">Filter</button>
		</span>
	</form>
</div>

<hr />

<table class="table table-striped table-bordered panel">
	<thead>
		<tr>
			<th><?php _e( 'Company', 'orbis_pronamic' ); ?></th>
			<th><?php _e( 'Project', 'orbis_pronamic' ); ?></th>
			<th><?php _e( 'Invoice Date', 'orbis_pronamic' ); ?></th>
			<th><?php _e( 'Invoice Number', 'orbis_pronamic' ); ?></th>
			<th><?php _e( 'Price', 'orbis_pronamic' ); ?></th>
		</tr>
	</thead>

	<tfoot>
		<tr>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td>
				<?php

				$total = array_sum( wp_list_pluck( $results, 'invoice_amount' ) );

				echo orbis_price( $total );

				?>
			</td>
		</tr>
	</tfoot>

	<tbody>

		<?php foreach( $results as $row ) : ?>
		
			<tr>
				<td>
					<a href="<?php echo add_query_arg( 'p', $row->company_post_id, home_url( '/' ) ); ?>">
						<?php echo $row->company_name; ?>
					</a>
				</td>
				<td>
					<a href="<?php echo add_query_arg( 'p', $row->project_post_id, home_url( '/' ) ); ?>">
						<?php echo $row->project_name; ?>
					</a>
				</td>
				<td>
					<?php echo $row->invoice_date; ?>
				</td>
				<td>
					<?php echo $row->invoice_number; ?>
				</td>
				<td>
					<?php echo orbis_price( $row->invoice_amount ); ?>
				</td>
			</tr>

		<?php endforeach; ?>
	</tbody>
</table>

<?php get_footer(); ?>
