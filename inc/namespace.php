<?php
/**
 * Namespaced functions.
 */

namespace Required\CommentRetentionPolicy;

use WP_Comment_Query;

const ACTIVATION_TRANSIENT_KEY        = 'comment-retention-policy-activated';
const IP_RETENTION_OPTION             = 'comment_ips_retention';
const IP_RETENTION_PERIOD_OPTION      = 'comment_ips_retention_period';
const IP_RETENTION_PERIOD_UNIT_OPTION = 'comment_ips_retention_period_unit';
const DELETE_CRON_ACTION              = 'process_retention_period_for_comment_ips';

/**
 * Inits plugin.
 */
function bootstrap() {
	Shims\bootstrap();

	add_action( 'init', __NAMESPACE__ . '\register_settings' );
	add_action( 'add_option_' . IP_RETENTION_OPTION, __NAMESPACE__ . '\update_schedule_delete_comment_ips', 10, 0 );
	add_action( 'update_option_' . IP_RETENTION_OPTION, __NAMESPACE__ . '\update_schedule_delete_comment_ips', 10, 0 );
	add_action( 'delete_option_' . IP_RETENTION_OPTION, __NAMESPACE__ . '\update_schedule_delete_comment_ips', 10, 0 );

	add_action( DELETE_CRON_ACTION, __NAMESPACE__ . '\process_retention_period_for_comment_ips' );

	Admin\bootstrap();
}

/**
 * Activation hook.
 */
function on_plugin_activation() {
	set_transient( ACTIVATION_TRANSIENT_KEY, true, 10 );

	update_schedule_delete_comment_ips();
}

/**
 * Deactivation hook.
 */
function on_plugin_deactivation() {
	wp_unschedule_hook( DELETE_CRON_ACTION );
}

/**
 * Schedules or unschedules the event for deleting comment IPs.
 */
function update_schedule_delete_comment_ips() {
	$ip_retention_option = get_option( IP_RETENTION_OPTION );
	if ( 'delete' !== $ip_retention_option ) {
		wp_unschedule_hook( DELETE_CRON_ACTION );
		return;
	}

	if ( ! wp_next_scheduled( DELETE_CRON_ACTION ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', DELETE_CRON_ACTION );
	}
}

/**
 * Calculates the retention period for comment IPs.
 *
 * @return int Calculated retention period in seconds.
 */
function get_retention_period_for_comment_ips() {
	$ip_retention_period      = get_option( IP_RETENTION_PERIOD_OPTION );
	$ip_retention_period_unit = get_option( IP_RETENTION_PERIOD_UNIT_OPTION );

	switch ( $ip_retention_period_unit ) {
		case 'days':
			$ip_retention_period *= DAY_IN_SECONDS;
			break;

		case 'weeks':
			$ip_retention_period *= WEEK_IN_SECONDS;
			break;

		case 'months':
			$ip_retention_period *= MONTH_IN_SECONDS;
			break;

		default:
			$ip_retention_period = 0;
			break;
	}

	return $ip_retention_period;
}

/**
 * Checks for the retention period for comment IPs and deletes them
 * if exceeded.
 *
 * Only deletes them in batches of 100 comments. In case of more the
 * event gets rescheduled 10 seconds later.
 */
function process_retention_period_for_comment_ips() {
	$ip_retention_option = get_option( IP_RETENTION_OPTION );
	if ( 'delete' !== $ip_retention_option ) {
		return;
	}

	$ip_retention_period = get_retention_period_for_comment_ips();
	if ( ! $ip_retention_period ) {
		return;
	}

	$query = new WP_Comment_Query();

	$args = [
		'type'                      => 'comment',
		'author_ip__not_in'         => [ '' ],
		'date_query'                => [
			'column'    => 'comment_date_gmt',
			'before'    => $ip_retention_period . ' seconds ago',
			'inclusive' => true,
		],
		'update_comment_meta_cache' => false,
		'fields'                    => 'ids',
		'count'                     => true,
	];

	$count = $query->query( $args );

	// Run the delete process again in 10 seconds to delete remaining comment IPs.
	if ( $count > 100 ) {
		wp_unschedule_hook( DELETE_CRON_ACTION );
		wp_schedule_event( time() + 10, 'hourly', DELETE_CRON_ACTION );
	}

	// Change query to return the first 100 comment IDs.
	$args['count']  = false;
	$args['number'] = 100;
	$comment_ids    = $query->query( $args );

	// Delete the IP.
	foreach ( $comment_ids as $comment_id ) {
		wp_update_comment(
			[
				'comment_ID'        => $comment_id,
				'comment_author_IP' => '',
			]
		);
	}
}

/**
 * Registers options.
 */
function register_settings() {
	register_setting(
		'discussion',
		IP_RETENTION_OPTION,
		[
			'show_in_rest'      => [
				'schema' => [
					'enum' => [
						'keep',
						'delete',
					],
				],
			],
			'type'              => 'string',
			'description'       => __( 'Retention policy for comment IPs.', 'comment-retention-policy' ),
			'default'           => 'keep',
			'sanitize_callback' => null, // Added below due to missing second argument, see https://core.trac.wordpress.org/ticket/15335.
		]
	);

	register_setting(
		'discussion',
		IP_RETENTION_PERIOD_OPTION,
		[
			'show_in_rest'      => true,
			'type'              => 'integer',
			'description'       => __( 'Retention period for deleting comment IPs.', 'comment-retention-policy' ),
			'default'           => 7,
			'sanitize_callback' => null, // Added below due to missing second argument, see https://core.trac.wordpress.org/ticket/15335.
		]
	);

	add_filter( 'sanitize_option_' . IP_RETENTION_PERIOD_OPTION, __NAMESPACE__ . '\sanitize_ip_retention_period_option', 10, 2 );

	register_setting(
		'discussion',
		IP_RETENTION_PERIOD_UNIT_OPTION,
		[
			'show_in_rest'      => [
				'schema' => [
					'enum' => [
						'days',
						'weeks',
						'months',
					],
				],
			],
			'type'              => 'string',
			'description'       => __( 'Retention period unit for deleting comment IPs.', 'comment-retention-policy' ),
			'default'           => 'days',
			'sanitize_callback' => null, // Added below due to missing second argument, see https://core.trac.wordpress.org/ticket/15335.
		]
	);

	add_filter( 'sanitize_option_' . IP_RETENTION_PERIOD_UNIT_OPTION, __NAMESPACE__ . '\sanitize_ip_retention_period_unit_option', 10, 2 );
}

/**
 * Sanitizes retention option from user input.
 *
 * @param string $value The unsanitized option value.
 * @param  string $option The option name.
 * @return string The sanitized option value.
 */
function sanitize_ip_retention_option( $value, $option ) {
	$value = (string) $value;
	$value = trim( $value );

	if ( in_array( $value, [ 'keep', 'delete' ], true ) ) {
		return $value;
	}

	// Fallback to previous value.
	$value = get_option( $option );

	return $value;
}

/**
 * Sanitizes retention period option from user input.
 *
 * @param string $value The unsanitized option value.
 * @param  string $option The option name.
 * @return int The sanitized option value.
 */
function sanitize_ip_retention_period_option( $value, $option ) {
	$value = (string) $value;
	$value = trim( $value );

	if ( is_numeric( $value ) ) {
		$value = (int) $value;
		if ( $value >= 1 && $value <= 999 ) {
			return $value;
		}
	}

	// Fallback to previous value.
	$value = get_option( $option );

	return $value;
}

/**
 * Sanitizes retention period unit option from user input.
 *
 * @param string $value The unsanitized option value.
 * @param  string $option The option name.
 * @return int The sanitized option value.
 */
function sanitize_ip_retention_period_unit_option( $value, $option ) {
	$value = (string) $value;
	$value = trim( $value );

	if ( in_array( $value, [ 'days', 'weeks', 'months' ], true ) ) {
		return $value;
	}

	// Fallback to previous value.
	$value = get_option( $option );

	return $value;
}
