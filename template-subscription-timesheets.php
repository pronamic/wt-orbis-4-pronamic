<?php 
/**
 * Template Name: Timesheets
 */

get_header();

// Globals
global $wpdb;

// Functions
function orbis_format_timestamps( array $timestamps, $format ) {
	$dates = array();
	
	foreach( $timestamps as $key => $value ) {
		$dates[$key] = date( $format, $value );
	}
	
	return $dates;
}

$subscription_id = filter_input( INPUT_GET, 'id', FILTER_SANITIZE_STRING );

$query_hours =  "
	SELECT
		hr.id AS registration_id,
		project.id AS project_id,
		project.name AS project_name,
		project.post_id AS project_post_id,
		client.id AS client_id,
		client.name AS client_name,
		client.post_id AS client_post_id,
		user.display_name AS user_name,
		hr.date AS date,
		hr.description AS description,
		hr.number_seconds AS number_seconds
	FROM
		$wpdb->orbis_timesheets AS hr
			LEFT JOIN
		$wpdb->orbis_companies AS client
				ON hr.company_id = client.id
			LEFT JOIN
		$wpdb->orbis_projects AS project
				ON hr.project_id = project.id
			LEFT JOIN
		$wpdb->users AS user
				ON hr.user_id = user.ID
	WHERE 
		subscription_id = $subscription_id
	ORDER BY 
		date 
	ASC
";

$result = $wpdb->get_results( $query_hours );

$total_seconds      = 0;
$billable_seconds   = 0;
$unbillable_seconds = 0;

foreach ( $result as $row ) {
	$row->billable_seconds   = 0;
	$row->unbillable_seconds = 0;
	
	if ( isset( $budgets[$row->project_id] ) ) {
		$project = $budgets[$row->project_id];
		
		if ( $project->invoicable ) {
			if ( $row->number_seconds < $project->seconds_available ) {
				// 1800 seconds registred < 3600 seconds available
				$row->billable_seconds   = $row->number_seconds;
			} else {
				// 3600 seconds registred < 1800 seconds available
				$seconds_avilable        = max( 0, $project->seconds_available );

				$row->billable_seconds   = $seconds_avilable;
				$row->unbillable_seconds = $row->number_seconds - $seconds_avilable;
			}
		} else {
			$row->unbillable_seconds = $row->number_seconds;
		}

		$project->seconds_available -= $row->number_seconds;
	} else {
		$row->unbillable_seconds = $row->number_seconds;
	}
	
	$total_seconds      += $row->number_seconds;
	$billable_seconds   += $row->billable_seconds;
	$unbillable_seconds += $row->unbillable_seconds;
}

$unbillable_hours = $unbillable_seconds / 60 / 60;
$billable_hours   = $billable_seconds / 60 / 60;
$total_hours      = $total_seconds / 60 / 60;

if ( $total_seconds > 0 ) {
	$total = $billable_seconds / $total_seconds  * 100;
} else {
	$total = 0;
}

$amount = $billable_hours * 75;

?>

<h1><?php echo round( $total_hours, 2 ); ?> <small><?php _e( 'Total tracked hours', 'orbis_pronamic' ); ?></small></h1>

<hr />

<table class="table table-striped table-bordered panel">
	<thead>
		<tr>
			<th><?php _e( 'User', 'orbis_pronamic' ); ?></th>
			<th><?php _e( 'Client', 'orbis_pronamic' ); ?></th>
			<th><?php _e( 'Project', 'orbis_pronamic' ); ?></th>
			<th><?php _e( 'Description', 'orbis_pronamic' ); ?></th>
			<th><?php _e( 'Time', 'orbis_pronamic' ); ?></th>
			<th><?php _e( 'Total', 'orbis_pronamic' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php $date = 0; foreach( $result as $row ) : ?>
	
			<?php if ( $date != $row->date ) : $date = $row->date; $total = 0; ?>
			
				<tr>
					<td colspan="6">
						<h2><?php echo $row->date; ?></h2>
					</td>
				</tr>
			
			<?php endif; ?>
			
			<?php $total += $row->number_seconds; ?>
	
			<tr>
				<td>
					<?php echo $row->user_name; ?>
				</td>
				<td>
					<a href="<?php echo get_permalink( $row->client_post_id ); ?>" target="_blank">
						<?php echo $row->client_name; ?>
					</a>
				</td>
				<td>
					<a href="<?php echo get_permalink( $row->project_post_id ); ?>" target="_blank">
						<?php echo $row->project_name; ?>
					</a>
				</td>
				<td><?php echo $row->description; ?></td>
				<td>
					<?php 
					
					$title = sprintf(
						__( '%s billable, %s unbillable', 'orbis_pronamic' ),
						orbis_time( $row->billable_seconds ),
						orbis_time( $row->unbillable_seconds )
					);
					
					?>
					<a href="#" data-toggle="tooltip" title="<?php echo esc_attr( $title ); ?>">
						<?php echo orbis_time( $row->number_seconds ); ?>
					</a>
				</td>
				<td><?php echo orbis_time( $total ); ?></td>
			</tr>

		<?php endforeach; ?>
	</tbody>
</table>

<?php get_footer(); ?>
