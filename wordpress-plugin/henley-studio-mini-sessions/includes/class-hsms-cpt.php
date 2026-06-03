<?php
/**
 * The "mini_session" custom post type, its details meta box and admin columns.
 *
 * @package HenleyStudioMiniSessions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and manages the mini_session post type.
 */
class HSMS_CPT_Manager {

	/**
	 * Meta keys used for session details.
	 */
	const META = array(
		'_hsms_session_date' => 'sanitize_text_field',
		'_hsms_location'     => 'sanitize_text_field',
		'_hsms_price_cents'  => 'absint',
		'_hsms_deposit_cents'=> 'absint',
		'_hsms_currency'     => 'sanitize_text_field',
		'_hsms_slot_duration'=> 'absint',
		'_hsms_start_time'   => 'sanitize_text_field',
		'_hsms_end_time'     => 'sanitize_text_field',
		'_hsms_break_min'    => 'absint',
	);

	/**
	 * Hook everything up.
	 */
	public function init() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'save_post_' . HSMS_CPT, array( $this, 'save' ), 10, 2 );

		add_filter( 'manage_' . HSMS_CPT . '_posts_columns', array( $this, 'columns' ) );
		add_action( 'manage_' . HSMS_CPT . '_posts_custom_column', array( $this, 'render_column' ), 10, 2 );
	}

	/**
	 * Register the post type.
	 */
	public static function register_post_type() {
		$labels = array(
			'name'               => __( 'Mini Sessions', 'henley-studio-mini-sessions' ),
			'singular_name'      => __( 'Mini Session', 'henley-studio-mini-sessions' ),
			'add_new'            => __( 'Add Session', 'henley-studio-mini-sessions' ),
			'add_new_item'       => __( 'Add New Mini Session', 'henley-studio-mini-sessions' ),
			'edit_item'          => __( 'Edit Mini Session', 'henley-studio-mini-sessions' ),
			'new_item'           => __( 'New Mini Session', 'henley-studio-mini-sessions' ),
			'view_item'          => __( 'View Session', 'henley-studio-mini-sessions' ),
			'search_items'       => __( 'Search Sessions', 'henley-studio-mini-sessions' ),
			'not_found'          => __( 'No sessions yet.', 'henley-studio-mini-sessions' ),
			'menu_name'          => __( 'Mini Sessions', 'henley-studio-mini-sessions' ),
		);

		register_post_type(
			HSMS_CPT,
			array(
				'labels'        => $labels,
				'public'        => false,
				'show_ui'       => true,
				'show_in_menu'  => true,
				'menu_icon'     => 'dashicons-camera',
				'menu_position' => 25,
				'supports'      => array( 'title', 'editor', 'thumbnail' ),
				'has_archive'   => false,
				'rewrite'       => false,
				'show_in_rest'  => true,
			)
		);
	}

	/**
	 * Add the details meta box.
	 */
	public function add_meta_box() {
		add_meta_box(
			'hsms_session_details',
			__( 'Session details, schedule & pricing', 'henley-studio-mini-sessions' ),
			array( $this, 'render_meta_box' ),
			HSMS_CPT,
			'normal',
			'high'
		);
	}

	/**
	 * Render the details meta box.
	 *
	 * @param WP_Post $post Current post.
	 */
	public function render_meta_box( $post ) {
		wp_nonce_field( 'hsms_save_session', 'hsms_session_nonce' );

		$date      = get_post_meta( $post->ID, '_hsms_session_date', true );
		$location  = get_post_meta( $post->ID, '_hsms_location', true );
		$price     = (int) get_post_meta( $post->ID, '_hsms_price_cents', true );
		$deposit   = (int) get_post_meta( $post->ID, '_hsms_deposit_cents', true );
		$currency  = get_post_meta( $post->ID, '_hsms_currency', true );
		$duration  = (int) get_post_meta( $post->ID, '_hsms_slot_duration', true );
		$start     = get_post_meta( $post->ID, '_hsms_start_time', true );
		$end       = get_post_meta( $post->ID, '_hsms_end_time', true );
		$break     = (int) get_post_meta( $post->ID, '_hsms_break_min', true );

		$currency  = $currency ? $currency : hsms_get_setting( 'default_currency', 'AUD' );
		$duration  = $duration ? $duration : 20;
		$start     = $start ? $start : '09:00';
		$end       = $end ? $end : '13:00';

		$counts        = HSMS_Slots::counts( $post->ID );
		$has_slots     = $counts['total'] > 0;
		$booking_count = count( HSMS_Bookings::for_session( $post->ID ) );

		echo '<style>.hsms-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:16px}.hsms-field label{display:block;font-weight:600;margin-bottom:4px}.hsms-field input{width:100%}.hsms-note{color:#666;font-size:12px;margin-top:4px}</style>';
		echo '<p>' . esc_html__( 'Use the title above for the session name and the editor for the description shown to clients. Set a featured image to use as the session photo.', 'henley-studio-mini-sessions' ) . '</p>';

		echo '<div class="hsms-grid">';

		$this->field( 'hsms_session_date', __( 'Session date', 'henley-studio-mini-sessions' ), 'date', $date );
		$this->field( 'hsms_location', __( 'Location', 'henley-studio-mini-sessions' ), 'text', $location, 'e.g. Henley Beach foreshore' );

		$this->field( 'hsms_price', __( 'Full price', 'henley-studio-mini-sessions' ), 'number', $price ? number_format( $price / 100, 2, '.', '' ) : '0', '', 'step="0.01" min="0"' );
		$this->field( 'hsms_deposit', __( 'Deposit collected at booking', 'henley-studio-mini-sessions' ), 'number', $deposit ? number_format( $deposit / 100, 2, '.', '' ) : '0', '', 'step="0.01" min="0"' );

		$this->field( 'hsms_currency', __( 'Currency (ISO code)', 'henley-studio-mini-sessions' ), 'text', $currency, 'AUD', 'maxlength="3"' );
		$this->field( 'hsms_slot_duration', __( 'Slot length (minutes)', 'henley-studio-mini-sessions' ), 'number', (string) $duration, '', 'min="5" max="240"' );

		$this->field( 'hsms_start_time', __( 'First slot starts', 'henley-studio-mini-sessions' ), 'time', $start );
		$this->field( 'hsms_end_time', __( 'Last slot ends by', 'henley-studio-mini-sessions' ), 'time', $end );

		$this->field( 'hsms_break_min', __( 'Gap between slots (minutes)', 'henley-studio-mini-sessions' ), 'number', (string) $break, '', 'min="0" max="120"' );

		echo '</div>';

		echo '<hr style="margin:18px 0" />';
		if ( $has_slots ) {
			printf(
				'<p><strong>%s</strong> %s</p>',
				esc_html( sprintf( /* translators: 1: open, 2: total */ __( '%1$d of %2$d slots open.', 'henley-studio-mini-sessions' ), $counts['open'], $counts['total'] ) ),
				$booking_count > 0
					? esc_html( sprintf( /* translators: %d bookings */ __( '(%d booking(s) so far.)', 'henley-studio-mini-sessions' ), $booking_count ) )
					: ''
			);

			if ( 0 === $booking_count ) {
				echo '<label><input type="checkbox" name="hsms_regenerate_slots" value="1" /> ' . esc_html__( 'Regenerate time slots from the schedule above (replaces existing slots). Only available while there are no bookings.', 'henley-studio-mini-sessions' ) . '</label>';
			} else {
				echo '<p class="hsms-note">' . esc_html__( 'Slots are locked because bookings exist. Changing the schedule will not move existing slots.', 'henley-studio-mini-sessions' ) . '</p>';
			}
		} else {
			echo '<p class="hsms-note">' . esc_html__( 'Time slots will be generated automatically from the schedule above when you save.', 'henley-studio-mini-sessions' ) . '</p>';
		}
	}

	/**
	 * Render a single labelled input inside the grid.
	 *
	 * @param string $name        Field name/id.
	 * @param string $label       Label text.
	 * @param string $type        Input type.
	 * @param string $value       Current value.
	 * @param string $placeholder Placeholder.
	 * @param string $attrs       Extra attributes.
	 */
	private function field( $name, $label, $type, $value, $placeholder = '', $attrs = '' ) {
		printf(
			'<div class="hsms-field"><label for="%1$s">%2$s</label><input type="%3$s" id="%1$s" name="%1$s" value="%4$s" placeholder="%5$s" %6$s /></div>',
			esc_attr( $name ),
			esc_html( $label ),
			esc_attr( $type ),
			esc_attr( $value ),
			esc_attr( $placeholder ),
			$attrs // phpcs:ignore WordPress.Security.EscapeOutput -- static, trusted attribute strings.
		);
	}

	/**
	 * Persist meta and (re)generate slots on save.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public function save( $post_id, $post ) {
		if ( ! isset( $_POST['hsms_session_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['hsms_session_nonce'] ) ), 'hsms_save_session' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$currency = isset( $_POST['hsms_currency'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_POST['hsms_currency'] ) ) ) : 'AUD';

		$values = array(
			'_hsms_session_date'  => isset( $_POST['hsms_session_date'] ) ? sanitize_text_field( wp_unslash( $_POST['hsms_session_date'] ) ) : '',
			'_hsms_location'      => isset( $_POST['hsms_location'] ) ? sanitize_text_field( wp_unslash( $_POST['hsms_location'] ) ) : '',
			'_hsms_price_cents'   => isset( $_POST['hsms_price'] ) ? hsms_dollars_to_cents( wp_unslash( $_POST['hsms_price'] ) ) : 0,
			'_hsms_deposit_cents' => isset( $_POST['hsms_deposit'] ) ? hsms_dollars_to_cents( wp_unslash( $_POST['hsms_deposit'] ) ) : 0,
			'_hsms_currency'      => $currency ? $currency : 'AUD',
			'_hsms_slot_duration' => isset( $_POST['hsms_slot_duration'] ) ? max( 5, absint( $_POST['hsms_slot_duration'] ) ) : 20,
			'_hsms_start_time'    => isset( $_POST['hsms_start_time'] ) ? sanitize_text_field( wp_unslash( $_POST['hsms_start_time'] ) ) : '09:00',
			'_hsms_end_time'      => isset( $_POST['hsms_end_time'] ) ? sanitize_text_field( wp_unslash( $_POST['hsms_end_time'] ) ) : '13:00',
			'_hsms_break_min'     => isset( $_POST['hsms_break_min'] ) ? absint( $_POST['hsms_break_min'] ) : 0,
		);

		// Deposit can never exceed the full price (when a price is set).
		if ( $values['_hsms_price_cents'] > 0 && $values['_hsms_deposit_cents'] > $values['_hsms_price_cents'] ) {
			$values['_hsms_deposit_cents'] = $values['_hsms_price_cents'];
		}

		foreach ( $values as $key => $value ) {
			update_post_meta( $post_id, $key, $value );
		}

		// Generate slots on first save, or regenerate on request when safe.
		$counts        = HSMS_Slots::counts( $post_id );
		$booking_count = count( HSMS_Bookings::for_session( $post_id ) );
		$regenerate    = ! empty( $_POST['hsms_regenerate_slots'] );

		if ( 0 === $counts['total'] ) {
			HSMS_Slots::generate( $post_id );
		} elseif ( $regenerate && 0 === $booking_count ) {
			HSMS_Slots::regenerate( $post_id );
		}
	}

	/**
	 * Admin list columns.
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public function columns( $columns ) {
		$new = array();
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				$new['hsms_date']    = __( 'Session date', 'henley-studio-mini-sessions' );
				$new['hsms_slots']   = __( 'Booked', 'henley-studio-mini-sessions' );
				$new['hsms_price']   = __( 'Price', 'henley-studio-mini-sessions' );
			}
		}
		return $new;
	}

	/**
	 * Render custom column content.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Post ID.
	 */
	public function render_column( $column, $post_id ) {
		switch ( $column ) {
			case 'hsms_date':
				$date = get_post_meta( $post_id, '_hsms_session_date', true );
				echo $date ? esc_html( hsms_format_date( $date ) ) : '—';
				break;
			case 'hsms_slots':
				$counts = HSMS_Slots::counts( $post_id );
				$booked = $counts['total'] - $counts['open'];
				echo esc_html( $booked . ' / ' . $counts['total'] );
				break;
			case 'hsms_price':
				$price    = (int) get_post_meta( $post_id, '_hsms_price_cents', true );
				$currency = get_post_meta( $post_id, '_hsms_currency', true );
				echo $price > 0 ? esc_html( hsms_format_money( $price, $currency ) ) : esc_html__( 'Free', 'henley-studio-mini-sessions' );
				break;
		}
	}
}
