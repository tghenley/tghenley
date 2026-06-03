<?php
/**
 * Booking creation and queries.
 *
 * @package HenleysMiniSessions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Booking data access.
 */
class HMS_Bookings {

	/**
	 * Create a booking, atomically claiming the slot.
	 *
	 * @param array $args Booking fields (slot_id, session_id, client_*, notes, amount_cents, payment_method).
	 * @return int|WP_Error Booking ID, or WP_Error if the slot is gone.
	 */
	public static function create( $args ) {
		global $wpdb;
		$slots_table    = HMS_Install::slots_table();
		$bookings_table = HMS_Install::bookings_table();

		$slot_id = (int) $args['slot_id'];

		// Race-safe claim: only succeeds if the slot is still open.
		$claimed = $wpdb->query( // phpcs:ignore WordPress.DB
			$wpdb->prepare( "UPDATE {$slots_table} SET status = 'booked' WHERE id = %d AND status = 'open'", $slot_id )
		);

		if ( 1 !== (int) $claimed ) {
			return new WP_Error( 'slot_taken', __( 'Sorry, that time has just been taken. Please choose another.', 'henleys-mini-sessions' ) );
		}

		$inserted = $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$bookings_table,
			array(
				'session_id'     => (int) $args['session_id'],
				'slot_id'        => $slot_id,
				'client_name'    => $args['client_name'],
				'client_email'   => $args['client_email'],
				'client_phone'   => $args['client_phone'],
				'notes'          => $args['notes'],
				'status'         => 'pending',
				'amount_cents'   => (int) $args['amount_cents'],
				'payment_method' => $args['payment_method'],
				'payment_link'   => '',
				'created_at'     => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s' )
		);

		if ( ! $inserted ) {
			// Roll the slot back so it isn't stuck as booked with no booking.
			$wpdb->update( $slots_table, array( 'status' => 'open' ), array( 'id' => $slot_id ), array( '%s' ), array( '%d' ) ); // phpcs:ignore WordPress.DB
			return new WP_Error( 'insert_failed', __( 'Something went wrong saving your booking. Please try again.', 'henleys-mini-sessions' ) );
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Get a single booking.
	 *
	 * @param int $id Booking ID.
	 * @return object|null
	 */
	public static function get( $id ) {
		global $wpdb;
		$table = HMS_Install::bookings_table();
		return $wpdb->get_row( // phpcs:ignore WordPress.DB
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id )
		);
	}

	/**
	 * Get a booking joined with its slot times.
	 *
	 * @param int $id Booking ID.
	 * @return object|null
	 */
	public static function get_with_slot( $id ) {
		global $wpdb;
		$b = HMS_Install::bookings_table();
		$s = HMS_Install::slots_table();
		return $wpdb->get_row( // phpcs:ignore WordPress.DB
			$wpdb->prepare(
				"SELECT bk.*, sl.start_time AS slot_start, sl.end_time AS slot_end
				 FROM {$b} bk JOIN {$s} sl ON sl.id = bk.slot_id
				 WHERE bk.id = %d",
				$id
			)
		);
	}

	/**
	 * All bookings for a session, with slot times, ordered by slot start.
	 *
	 * @param int $session_id Session post ID.
	 * @return array
	 */
	public static function for_session( $session_id ) {
		global $wpdb;
		$b = HMS_Install::bookings_table();
		$s = HMS_Install::slots_table();
		return $wpdb->get_results( // phpcs:ignore WordPress.DB
			$wpdb->prepare(
				"SELECT bk.*, sl.start_time AS slot_start, sl.end_time AS slot_end
				 FROM {$b} bk JOIN {$s} sl ON sl.id = bk.slot_id
				 WHERE bk.session_id = %d
				 ORDER BY sl.start_time ASC",
				$session_id
			)
		);
	}

	/**
	 * Every booking, newest first, with slot times and session title.
	 *
	 * @return array
	 */
	public static function all() {
		global $wpdb;
		$b = HMS_Install::bookings_table();
		$s = HMS_Install::slots_table();
		return $wpdb->get_results( // phpcs:ignore WordPress.DB
			"SELECT bk.*, sl.start_time AS slot_start, sl.end_time AS slot_end
			 FROM {$b} bk JOIN {$s} sl ON sl.id = bk.slot_id
			 ORDER BY bk.created_at DESC"
		);
	}

	/**
	 * Store a payment link against a booking.
	 *
	 * @param int    $id   Booking ID.
	 * @param string $link Payment URL.
	 */
	public static function set_payment_link( $id, $link ) {
		global $wpdb;
		$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			HMS_Install::bookings_table(),
			array( 'payment_link' => $link ),
			array( 'id' => $id ),
			array( '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Update booking status; cancelling frees the slot.
	 *
	 * @param int    $id     Booking ID.
	 * @param string $status New status.
	 * @return bool
	 */
	public static function update_status( $id, $status ) {
		global $wpdb;
		if ( ! in_array( $status, HMS_STATUS_VALUES, true ) ) {
			return false;
		}
		$booking = self::get( $id );
		if ( ! $booking ) {
			return false;
		}

		$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			HMS_Install::bookings_table(),
			array( 'status' => $status ),
			array( 'id' => $id ),
			array( '%s' ),
			array( '%d' )
		);

		$slot_status = ( 'cancelled' === $status ) ? 'open' : 'booked';
		$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			HMS_Install::slots_table(),
			array( 'status' => $slot_status ),
			array( 'id' => $booking->slot_id ),
			array( '%s' ),
			array( '%d' )
		);
		return true;
	}
}
