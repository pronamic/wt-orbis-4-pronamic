<?php

global $wpdb;
global $orbis_project;

$query = $wpdb->prepare(
	"
	SELECT
		YEAR( timesheet.date ) AS year,
		MONTH( timesheet.date ) AS month,
		SUM( timesheet.number_seconds ) AS number_seconds
	FROM
		$wpdb->orbis_timesheets AS timesheet
			INNER JOIN
		$wpdb->orbis_projects AS project
				ON timesheet.project_id = project.id
	WHERE 
		project.post_id = %d
	GROUP BY
		YEAR( timesheet.date ), MONTH( timesheet.date )
	ORDER BY 
		YEAR( timesheet.date ) ASC, MONTH( timesheet.date ) ASC
	",
	get_the_ID()
);

$data = $wpdb->get_results( $query );

?>
<div class="card mb-3">
	<div class="card-header">
		Tijdregistraties
	</div>

	<div class="table-responsive" id="orbis-project-timesheet-per-month">
		<table class="table table-striped table-bordered mb-0">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Month', 'orbis_pronamic' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Time', 'orbis_pronamic' ); ?></th>
				</tr>
			</thead>

			<tfoot>
				<tr>
					<th scope="row"><?php esc_html_e( 'Total', 'orbis_pronamic' ); ?></th>
					<td>
						<?php 

						$total_in_period = array_sum( wp_list_pluck( $data, 'number_seconds' ) );

						printf(
							__( '%s / %s', 'orbis_pronamic' ),
							esc_html( orbis_time( $total_in_period ) ),
							esc_html( $orbis_project->get_available_time()->format() )
						);

						?>
					</td>
				</tr>
			</tfoot>

			<tbody>

				<?php foreach ( $data as $item ) : ?>

					<tr>
						<th scope="row">
							<?php

							$date = new \Pronamic\WordPress\DateTime\DateTime( '' . $item->year . '-' . $item->month . '-01' );

							echo esc_html( ucfirst( $date->format_i18n( 'F Y' ) ) );

							?>
						</th>
						<td>
							<?php echo esc_html( orbis_time( $item->number_seconds ) ); ?>
						</td>
					</tr>

				<?php endforeach; ?>

			</tbody>
		</table>
	</div>

	<div class="card-footer">
		<button type="button" class="btn btn-secondary btn-sm float-right orbis-copy" data-clipboard-target="#orbis-project-timesheet-per-month"><i class="fas fa-paste"></i> Kopieer HTML-tabel</button>
	</div>
</div>

<?php get_template_part( 'script-copy' ); ?>
