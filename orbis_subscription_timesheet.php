<?php 

// Globals
global $wpdb;

// Query subscription.
$query = $wpdb->prepare( "
	SELECT
		subscription.*,
		product.time_per_year
	FROM
		$wpdb->orbis_subscriptions AS subscription
			INNER JOIN
		$wpdb->orbis_subscription_products AS product
				ON subscription.type_id = product.id
	WHERE
		subscription.post_id = %d
	LIMIT
		1
	;",
	get_the_ID()
);

$subscription = $wpdb->get_row( $query );

if ( null === $subscription ) {
	return;
}

// Timesheet.
$activation_date = new \Pronamic\WordPress\DateTime\DateTime( $subscription->activation_date );

$current_date = new \Pronamic\WordPress\DateTime\DateTime();
$current_year = \intval( $current_date->format( 'Y' ) );
$current_year = \intval( $current_date->format( 'Y' ) );

$difference = $activation_date->diff( $current_date );

$start = clone $activation_date;
$start->modify( '+' . $difference->y . ' year' );

$end = new \Pronamic\WordPress\DateTime\DateTime();
$end->setDate( $end->format( 'Y' ), $end->format( 'n' ), $start->format( 'd' ) );
$end->setTime( 23, 59, 59 );

$end = ( $current_date > $end ) ? $current_date : $end;

$query = $wpdb->prepare(
	"
	SELECT
		MONTH( timesheet.date ) AS month,
		SUM( timesheet.number_seconds ) AS number_seconds
	FROM
		$wpdb->orbis_timesheets AS timesheet
			INNER JOIN
		$wpdb->orbis_subscriptions AS subscription
				ON timesheet.subscription_id = subscription.id
	WHERE 
		subscription.post_id = %d
			AND
		timesheet.date BETWEEN %s AND %s
	GROUP BY
		MONTH( timesheet.date )
	ORDER BY 
		MONTH( timesheet.date ) ASC
	",
	get_the_ID(),
	$start->format( 'Y-m-d' ),
	$end->format( 'Y-m-d' )
);

$interval = new \DateInterval( 'P1M' );
$period   = new \DatePeriod( $start, $interval, $end );

$data = $wpdb->get_results( $query, OBJECT_K );

$total_in_period = array_sum( wp_list_pluck( $data, 'number_seconds' ) );

?>
<div class="card mb-3">
	<div class="card-header">
		<h5 class="card-title">
			<?php

			printf(
				__( 'Tijdregistraties periode %s - %s', 'orbis_pronamic' ),
				$start->format( 'd-m-Y' ),
				$end->format( 'd-m-Y' )
			);

			?>
		</h5>

		<ul class="nav nav-tabs card-header-tabs">
			<li class="nav-item">
				<a class="nav-link active" data-toggle="tab" href="#template-simple" role="tab">Eenvoudig</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" data-toggle="tab" href="#template-table" role="tab">Tabel</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" data-toggle="tab" href="#template-message" role="tab">Bericht</a>
			</li>
		</ul>
	</div>

	<div class="tab-content">
		<div class="tab-pane active" id="template-simple" role="tabpanel">
			
			<div class="card-body">

				<?php if ( 'strippenkaart' === $subscription->status ) : ?>

					<div class="alert alert-warning mb-3" role="alert">
						<i class="fas fa-exclamation-triangle"></i> Tijdregistraties op strippenkaart.
					</div>

				<?php endif; ?>

				<?php if ( $total_in_period > $subscription->time_per_year ) : ?>

					<?php

					$company_post_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->orbis_companies WHERE id = %d;", $subscription->company_id ) );

					$search_url = \add_query_arg(
						array(
							's' => 'strippenkaart ' . $subscription->name,
						),
						\get_post_type_archive_link( 'orbis_project' )
					);

					?>

					<div class="alert alert-info mb-3" role="alert">
						<i class="fas fa-ticket-alt"></i> <a href="<?php echo \esc_url( $search_url ); ?>">Zoek "Strippenkaart"</a>.
					</div>

				<?php endif; ?>

				<?php

				$message = \sprintf(
					'Je hebt nog %s uren beschikbaar binnen het onderhoudsabonnement (%s uren).',
					\orbis_time( $subscription->time_per_year - $total_in_period ),
					\orbis_time( $subscription->time_per_year )
				);

				if ( $total_in_period > $subscription->time_per_year ) {
					$message = \sprintf(
						'🤖 We hebben in de afgelopen periode meer support uren geregistreerd dan beschikbaar zijn binnen het <a href="https://www.pronamic.nl/wordpress/wordpress-onderhoud/">WordPress onderhoud en support</a> abonnement 📈. We komen graag in contact met je om af te stemmen hoe we hier mee verder gaan 📞. Je kunt bijvoorbeeld een <a href="https://www.pronamic.nl/strippenkaarten/">strippenkaart</a> bestellen of je abonnement upgraden. We horen graag van je!',
						\orbis_time( $subscription->time_per_year )
					);
				}

				printf(
					'<div id="orbis-subscription-simple-message">%s</div>',
					$message
				);

				?>

			</div>

			<div class="card-footer">
				<button type="button" class="btn btn-secondary btn-sm orbis-copy" data-clipboard-target="#orbis-subscription-simple-message"><i class="fas fa-paste"></i> Kopieer HTML-bericht</button>
			</div>

		</div>

		<div class="tab-pane" id="template-table" role="tabpanel">
			
			<div class="card-body">

			<div class="table-responsive" id="orbis-subscription-timesheet-per-month">
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

									printf(
										__( '%s / %s', 'orbis_pronamic' ),
										esc_html( orbis_time( $total_in_period ) ),
										esc_html( orbis_time( $subscription->time_per_year ) )
									);

									?>
								</td>
							</tr>
						</tfoot>

						<tbody>

							<?php foreach ( $period as $date ) : ?>

								<tr>
									<th scope="row">
										<?php

										$key = $date->format( 'n' );

										$start_date = $date;

										$end_date = clone $start_date;
										$end_date->add( $interval );

										$time = 0;

										if ( array_key_exists( $key, $data ) ) {
											$item = $data[ $key ];	

											$time = $item->number_seconds;
										}						

										echo esc_html( ucfirst( $date->format_i18n( 'F Y' ) ) );

										?>
									</th>
									<td>
										<?php echo esc_html( orbis_time( $time ) ); ?>
									</td>
								</tr>

							<?php endforeach; ?>

						</tbody>
					</table>
				</div>

			</div>

			<div class="card-footer">
				<button type="button" class="btn btn-secondary btn-sm orbis-copy" data-clipboard-target="#orbis-subscription-timesheet-per-month"><i class="fas fa-paste"></i> Kopieer HTML-tabel</button>
			</div>

		</div>

		<div class="tab-pane" id="template-message" role="tabpanel">

			<div class="card-body">

				<div id="helpscout-auto-reply-message">
					Beste lezer,<br />
					<br />
					Bedankt voor het indienen van een supportaanvraag bij Pronamic. Hieronder vind je alvast een overzicht van de geregistreerde uren binnen het "<?php echo esc_html( get_the_title() ) ; ?>" abonnement:<br />
					<br />
					<table class="table table-striped table-bordered w-auto mb-0" border="1">
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
										esc_html( orbis_time( $subscription->time_per_year ) )
									);

									?>
								</td>
							</tr>
						</tfoot>

						<tbody>

							<?php foreach ( $period as $date ) : ?>

								<tr>
									<th scope="row">
										<?php

										$key = $date->format( 'n' );

										$start_date = $date;

										$end_date = clone $start_date;
										$end_date->add( $interval );

										$time = 0;

										if ( array_key_exists( $key, $data ) ) {
											$item = $data[ $key ];	

											$time = $item->number_seconds;
										}						

										echo esc_html( ucfirst( $date->format_i18n( 'F Y' ) ) );

										?>
									</th>
									<td>
										<?php echo esc_html( orbis_time( $time ) ); ?>
									</td>
								</tr>

							<?php endforeach; ?>

						</tbody>
					</table>

					<br />

					<?php if ( $total_in_period > $subscription->time_per_year ) : ?>

						We hebben in de afgelopen periode meer support uren geregistreerd dan beschikbaar zijn binnen het 
						<a href="https://www.pronamic.nl/wordpress/wordpress-onderhoud/">WordPress onderhoud en support</a> 
						abonnement. Om je te kunnen helpen willen we je vragen om een 
						<a href="https://www.pronamic.nl/strippenkaarten/">strippenkaart</a> te bestellen of je abonnement
						te upgraden.<br />

						<br />

					<?php endif; ?>

					Met vriendelijke groet,<br />
					Pronamic
				</div>

			</div>

			<div class="card-footer">
				<button type="button" class="btn btn-secondary btn-sm orbis-copy" data-clipboard-target="#helpscout-auto-reply-message"><i class="fas fa-paste"></i> Kopieer HTML-bericht</button>
			</div>

		</div>
	</div>
</div>

<?php get_template_part( 'script-copy' ); ?>

<?php

// Query timesheet.
$query = $wpdb->prepare( "
	SELECT
		timesheet.id AS entry_id,
		timesheet.date AS entry_date,
		timesheet.description AS entry_description,
		timesheet.number_seconds AS entry_number_seoncds,
		user.display_name AS user_display_name
	FROM
		$wpdb->orbis_timesheets AS timesheet
			LEFT JOIN
		$wpdb->orbis_subscriptions AS subscription
				ON timesheet.subscription_id = subscription.id
			LEFT JOIN
		$wpdb->users AS user
				ON timesheet.user_id = user.ID
	WHERE 
		subscription.post_id = %d
	ORDER BY 
		timesheet.date ASC
	;",
	get_the_ID()
);

$results = $wpdb->get_results( $query );

$note = get_option( 'orbis_timesheets_note' );

?>
<div class="card mb-3">
	<div class="card-header">Tijdregistraties</div>

	<?php if ( empty( $results ) ) : ?>

		<div class="card-body">
			<p class="text-muted m-0">
				<?php _e( 'There are no time registrations for this subscription.', 'orbis_pronamic' ); ?>
			</p>
		</div>

	<?php else : ?>

		<?php if ( $note ) : ?>

			<div class="card-body">
				<div class="alert alert-warning mb-0" role="alert">
					<i class="fas fa-exclamation-triangle"></i> <?php echo wp_kses_post( $note ); ?>
				</div>
			</div>

		<?php endif; ?>

		<div class="table-responsive">
			<table class="table table-striped table-bordered mb-0">
				<thead>
					<tr>
						<th><?php _e( 'Date', 'orbis_pronamic' ); ?></th>
						<th><?php _e( 'User', 'orbis_pronamic' ); ?></th>
						<th><?php _e( 'Description', 'orbis_pronamic' ); ?></th>
						<th><?php _e( 'Time', 'orbis_pronamic' ); ?></th>
					</tr>
				</thead>

				<tfoot>
					<tr>
						<td></td>
						<td></td>
						<td></td>
						<td>
							<?php 

							$total = array_sum( wp_list_pluck( $results, 'entry_number_seoncds' ) );

							printf(
								'<strong>%s</strong>',
								esc_html( orbis_time( $total ) )
							);

							?>
						</td>
					</tr>
				</tfoot>

				<tbody>

					<?php foreach( $results as $row ) : ?>

						<tr>
							<td>
								<?php echo $row->entry_date; ?>
							</td>
							<td>
								<?php echo $row->user_display_name; ?>
							</td>
							<td>
								<?php echo $row->entry_description; ?>
							</td>
							<td>
								<?php echo orbis_time( $row->entry_number_seoncds ); ?>
							</td>
						</tr>

					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

	<?php endif; ?>

</div>
