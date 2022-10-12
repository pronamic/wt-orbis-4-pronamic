<?php 
/**
 * Template Name: Projects Top
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
		project.id AS project_id,
		project.post_id AS project_post_id,
		project.name AS project_name,
		company.name AS company_name,
		company.post_id AS company_post_id,
		SUM( timesheet.number_seconds ) AS subscription_seconds
	FROM
		$wpdb->orbis_projects AS project
			LEFT JOIN
		$wpdb->orbis_timesheets AS timesheet
				ON project.id = timesheet.project_id
			LEFT JOIN
		$wpdb->orbis_companies AS company
				ON project.principal_id = company.id
	WHERE
		%s
	GROUP BY
		project.id
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
			<th><?php _e( 'Project', 'orbis_pronamic' ); ?></th>
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
					<a href="<?php echo add_query_arg( 'p', $row->project_post_id, home_url( '/' ) ); ?>">
						<?php echo $row->project_name; ?>
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
