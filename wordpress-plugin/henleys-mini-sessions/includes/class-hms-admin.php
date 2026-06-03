<?php
/**
 * Admin screens: the All Bookings table and the Settings page.
 *
 * @package HenleysMiniSessions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin UI.
 */
class HMS_Admin {

	/**
	 * Hook into admin.
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Add submenu pages under the Mini Sessions menu.
	 */
	public function menu() {
		$parent = 'edit.php?post_type=' . HMS_CPT;

		add_submenu_page(
			$parent,
			__( 'Bookings', 'henleys-mini-sessions' ),
			__( 'Bookings', 'henleys-mini-sessions' ),
			'manage_options',
			'hms-bookings',
			array( $this, 'render_bookings' )
		);

		add_submenu_page(
			$parent,
			__( 'Settings', 'henleys-mini-sessions' ),
			__( 'Settings', 'henleys-mini-sessions' ),
			'manage_options',
			'hms-settings',
			array( $this, 'render_settings' )
		);
	}

	/**
	 * Render the bookings table.
	 */
	public function render_bookings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$bookings  = HMS_Bookings::all();
		$redirect  = admin_url( 'edit.php?post_type=' . HMS_CPT . '&page=hms-bookings' );
		$action    = admin_url( 'admin-post.php' );
		$badges     = array(
			'pending'   => '#9a6700;background:#fff8e1',
			'confirmed' => '#0b69a3;background:#e7f3fb',
			'paid'      => '#0a7b4b;background:#e6f6ee',
			'cancelled' => '#a01a2d;background:#fde8ea',
		);
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Bookings', 'henleys-mini-sessions' ); ?></h1>
			<?php if ( empty( $bookings ) ) : ?>
				<p><?php esc_html_e( 'No bookings yet. Publish a session and share its booking link.', 'henleys-mini-sessions' ); ?></p>
			<?php else : ?>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Session', 'henleys-mini-sessions' ); ?></th>
						<th><?php esc_html_e( 'Time', 'henleys-mini-sessions' ); ?></th>
						<th><?php esc_html_e( 'Client', 'henleys-mini-sessions' ); ?></th>
						<th><?php esc_html_e( 'Contact', 'henleys-mini-sessions' ); ?></th>
						<th><?php esc_html_e( 'Amount', 'henleys-mini-sessions' ); ?></th>
						<th><?php esc_html_e( 'Status', 'henleys-mini-sessions' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ( $bookings as $b ) :
						$session  = get_post( $b->session_id );
						$currency = $session ? get_post_meta( $session->ID, '_hms_currency', true ) : 'AUD';
						$style    = isset( $badges[ $b->status ] ) ? $badges[ $b->status ] : $badges['pending'];
						?>
						<tr>
							<td>
								<?php echo $session ? esc_html( $session->post_title ) : esc_html__( '(deleted)', 'henleys-mini-sessions' ); ?><br />
								<small><?php echo $session ? esc_html( hms_format_date( get_post_meta( $session->ID, '_hms_session_date', true ) ) ) : ''; ?></small>
							</td>
							<td><?php echo esc_html( hms_format_time_range( $b->slot_start, $b->slot_end ) ); ?></td>
							<td>
								<strong><?php echo esc_html( $b->client_name ); ?></strong>
								<?php if ( $b->notes ) : ?>
									<br /><small style="color:#666"><?php echo esc_html( $b->notes ); ?></small>
								<?php endif; ?>
							</td>
							<td>
								<a href="mailto:<?php echo esc_attr( $b->client_email ); ?>"><?php echo esc_html( $b->client_email ); ?></a>
								<?php if ( $b->client_phone ) : ?>
									<br /><small><?php echo esc_html( $b->client_phone ); ?></small>
								<?php endif; ?>
							</td>
							<td>
								<?php echo $b->amount_cents > 0 ? esc_html( hms_format_money( $b->amount_cents, $currency ) ) : '—'; ?>
								<?php if ( $b->payment_link ) : ?>
									<br /><a href="<?php echo esc_url( $b->payment_link ); ?>" target="_blank" rel="noreferrer"><?php esc_html_e( 'Pay link', 'henleys-mini-sessions' ); ?></a>
								<?php endif; ?>
							</td>
							<td>
								<form method="post" action="<?php echo esc_url( $action ); ?>" style="display:flex;gap:6px;align-items:center">
									<?php wp_nonce_field( 'hms_update_status' ); ?>
									<input type="hidden" name="action" value="hms_update_status" />
									<input type="hidden" name="booking_id" value="<?php echo esc_attr( $b->id ); ?>" />
									<input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect ); ?>" />
									<span style="padding:2px 8px;border-radius:10px;font-size:11px;color:<?php echo esc_attr( $style ); ?>"><?php echo esc_html( ucfirst( $b->status ) ); ?></span>
									<select name="status" onchange="this.form.submit()">
										<?php foreach ( HMS_STATUS_VALUES as $opt ) : ?>
											<option value="<?php echo esc_attr( $opt ); ?>" <?php selected( $b->status, $opt ); ?>><?php echo esc_html( ucfirst( $opt ) ); ?></option>
										<?php endforeach; ?>
									</select>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Register the settings option and its sanitizer.
	 */
	public function register_settings() {
		register_setting(
			'hms_settings_group',
			HMS_OPTION,
			array( 'sanitize_callback' => array( $this, 'sanitize_settings' ) )
		);
	}

	/**
	 * Sanitize the settings array.
	 *
	 * @param array $input Raw input.
	 * @return array
	 */
	public function sanitize_settings( $input ) {
		$input = is_array( $input ) ? $input : array();
		return array(
			'studio_name'         => isset( $input['studio_name'] ) ? sanitize_text_field( $input['studio_name'] ) : '',
			'tagline'             => isset( $input['tagline'] ) ? sanitize_text_field( $input['tagline'] ) : '',
			'contact_email'       => isset( $input['contact_email'] ) ? sanitize_email( $input['contact_email'] ) : '',
			'default_currency'    => isset( $input['default_currency'] ) ? strtoupper( sanitize_text_field( $input['default_currency'] ) ) : 'AUD',
			'booking_page_id'     => isset( $input['booking_page_id'] ) ? absint( $input['booking_page_id'] ) : 0,
			'square_access_token' => isset( $input['square_access_token'] ) ? sanitize_text_field( $input['square_access_token'] ) : '',
			'square_location_id'  => isset( $input['square_location_id'] ) ? sanitize_text_field( $input['square_location_id'] ) : '',
			'square_environment'  => ( isset( $input['square_environment'] ) && 'production' === $input['square_environment'] ) ? 'production' : 'sandbox',
			'mailerlite_api_key'  => isset( $input['mailerlite_api_key'] ) ? sanitize_text_field( $input['mailerlite_api_key'] ) : '',
			'mailerlite_group_id' => isset( $input['mailerlite_group_id'] ) ? sanitize_text_field( $input['mailerlite_group_id'] ) : '',
			'mailerlite_consent_label' => isset( $input['mailerlite_consent_label'] ) ? sanitize_text_field( $input['mailerlite_consent_label'] ) : '',
		);
	}

	/**
	 * Render the settings page.
	 */
	public function render_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$s = hms_get_settings();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Mini Sessions — Settings', 'henleys-mini-sessions' ); ?></h1>

			<?php if ( hms_square_configured() ) : ?>
				<div class="notice notice-success inline"><p><?php esc_html_e( 'Square is connected. Bookings will generate a secure Square payment link.', 'henleys-mini-sessions' ); ?></p></div>
			<?php else : ?>
				<div class="notice notice-warning inline"><p><?php esc_html_e( 'Square is not configured — running in invoice mode. Add your Square Access Token and Location ID below to collect deposits online.', 'henleys-mini-sessions' ); ?></p></div>
			<?php endif; ?>

			<form method="post" action="options.php">
				<?php settings_fields( 'hms_settings_group' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="hms_studio_name"><?php esc_html_e( 'Studio name', 'henleys-mini-sessions' ); ?></label></th>
						<td><input type="text" id="hms_studio_name" class="regular-text" name="<?php echo esc_attr( HMS_OPTION ); ?>[studio_name]" value="<?php echo esc_attr( $s['studio_name'] ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="hms_tagline"><?php esc_html_e( 'Tagline', 'henleys-mini-sessions' ); ?></label></th>
						<td><input type="text" id="hms_tagline" class="regular-text" name="<?php echo esc_attr( HMS_OPTION ); ?>[tagline]" value="<?php echo esc_attr( $s['tagline'] ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="hms_contact_email"><?php esc_html_e( 'Contact / notification email', 'henleys-mini-sessions' ); ?></label></th>
						<td><input type="email" id="hms_contact_email" class="regular-text" name="<?php echo esc_attr( HMS_OPTION ); ?>[contact_email]" value="<?php echo esc_attr( $s['contact_email'] ); ?>" />
						<p class="description"><?php esc_html_e( 'New booking notifications are sent here.', 'henleys-mini-sessions' ); ?></p></td>
					</tr>
					<tr>
						<th scope="row"><label for="hms_default_currency"><?php esc_html_e( 'Default currency', 'henleys-mini-sessions' ); ?></label></th>
						<td><input type="text" id="hms_default_currency" maxlength="3" name="<?php echo esc_attr( HMS_OPTION ); ?>[default_currency]" value="<?php echo esc_attr( $s['default_currency'] ); ?>" style="width:80px;text-transform:uppercase" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="hms_booking_page_id"><?php esc_html_e( 'Booking page', 'henleys-mini-sessions' ); ?></label></th>
						<td>
							<?php
							wp_dropdown_pages(
								array(
									'name'              => esc_attr( HMS_OPTION ) . '[booking_page_id]',
									'id'                => 'hms_booking_page_id',
									'selected'          => (int) $s['booking_page_id'],
									'show_option_none'  => __( '— Select the page with the [mini_sessions] shortcode —', 'henleys-mini-sessions' ),
									'option_none_value' => 0,
								)
							);
							?>
							<p class="description"><?php esc_html_e( 'Create a page, add the [mini_sessions] shortcode to it, then select it here so links work correctly.', 'henleys-mini-sessions' ); ?></p>
						</td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'Square payments', 'henleys-mini-sessions' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Get these from developer.squareup.com. Leave blank to use invoice mode. Square hosts the checkout, so card data never touches your site.', 'henleys-mini-sessions' ); ?></p>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="hms_sq_token"><?php esc_html_e( 'Access token', 'henleys-mini-sessions' ); ?></label></th>
						<td><input type="password" id="hms_sq_token" class="regular-text" autocomplete="off" name="<?php echo esc_attr( HMS_OPTION ); ?>[square_access_token]" value="<?php echo esc_attr( $s['square_access_token'] ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="hms_sq_loc"><?php esc_html_e( 'Location ID', 'henleys-mini-sessions' ); ?></label></th>
						<td><input type="text" id="hms_sq_loc" class="regular-text" name="<?php echo esc_attr( HMS_OPTION ); ?>[square_location_id]" value="<?php echo esc_attr( $s['square_location_id'] ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="hms_sq_env"><?php esc_html_e( 'Environment', 'henleys-mini-sessions' ); ?></label></th>
						<td>
							<select id="hms_sq_env" name="<?php echo esc_attr( HMS_OPTION ); ?>[square_environment]">
								<option value="sandbox" <?php selected( $s['square_environment'], 'sandbox' ); ?>><?php esc_html_e( 'Sandbox (testing)', 'henleys-mini-sessions' ); ?></option>
								<option value="production" <?php selected( $s['square_environment'], 'production' ); ?>><?php esc_html_e( 'Production (live)', 'henleys-mini-sessions' ); ?></option>
							</select>
						</td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'Newsletter (MailerLite)', 'henleys-mini-sessions' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Optional. When set, a consent checkbox appears on the booking form and consenting clients are added to your MailerLite group. Get an API key in MailerLite under Integrations → API.', 'henleys-mini-sessions' ); ?></p>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="hms_ml_key"><?php esc_html_e( 'API key', 'henleys-mini-sessions' ); ?></label></th>
						<td><input type="password" id="hms_ml_key" class="regular-text" autocomplete="off" name="<?php echo esc_attr( HMS_OPTION ); ?>[mailerlite_api_key]" value="<?php echo esc_attr( $s['mailerlite_api_key'] ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="hms_ml_group"><?php esc_html_e( 'Group ID', 'henleys-mini-sessions' ); ?></label></th>
						<td><input type="text" id="hms_ml_group" class="regular-text" name="<?php echo esc_attr( HMS_OPTION ); ?>[mailerlite_group_id]" value="<?php echo esc_attr( $s['mailerlite_group_id'] ); ?>" />
						<p class="description"><?php esc_html_e( 'Optional. The numeric ID of the MailerLite group to add subscribers to. Leave blank to add them with no group.', 'henleys-mini-sessions' ); ?></p></td>
					</tr>
					<tr>
						<th scope="row"><label for="hms_ml_label"><?php esc_html_e( 'Consent checkbox text', 'henleys-mini-sessions' ); ?></label></th>
						<td><input type="text" id="hms_ml_label" class="large-text" name="<?php echo esc_attr( HMS_OPTION ); ?>[mailerlite_consent_label]" value="<?php echo esc_attr( $s['mailerlite_consent_label'] ); ?>" /></td>
					</tr>
				</table>

				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
