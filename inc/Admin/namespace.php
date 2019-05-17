<?php
/**
 * Functionality for extending wp-admin.
 */

namespace Required\CommentRetentionPolicy\Admin;

use const Required\CommentRetentionPolicy\ACTIVATION_TRANSIENT_KEY;
use const Required\CommentRetentionPolicy\DELETE_CRON_ACTION;
use const Required\CommentRetentionPolicy\IP_RETENTION_OPTION;
use const Required\CommentRetentionPolicy\IP_RETENTION_PERIOD_OPTION;
use const Required\CommentRetentionPolicy\IP_RETENTION_PERIOD_UNIT_OPTION;

/**
 * Inits extensions.
 */
function bootstrap() {
	add_action( 'admin_init', __NAMESPACE__ . '\register_settings_ui' );
	add_action( 'admin_notices', __NAMESPACE__ . '\activation_notice' );
}

/**
 * Registers settings UI for options.
 */
function register_settings_ui() {
	add_settings_field(
		'comment-retention-policy',
		__( 'Retention Policy for Comment IPs', 'comment-retention-policy' ),
		function() {
			$ip_retention_option             = get_option( IP_RETENTION_OPTION );
			$ip_retention_period_option      = get_option( IP_RETENTION_PERIOD_OPTION );
			$ip_retention_period_unit_option = get_option( IP_RETENTION_PERIOD_UNIT_OPTION );
			?>
			<fieldset id="comment-retention-policy">
				<legend class="screen-reader-text">
					<span><?php _e( 'Retention Policy for Comment IPs', 'comment-retention-policy' ); ?></span>
				</legend>

				<input
					type="radio"
					name="<?php echo esc_attr( IP_RETENTION_OPTION ); ?>"
					id="<?php echo esc_attr( IP_RETENTION_OPTION ); ?>-keep"
					value="keep"
					<?php checked( 'keep', $ip_retention_option ); ?>
				>
				<label for="<?php echo esc_attr( IP_RETENTION_OPTION ); ?>-keep"><?php _e( 'Keep data', 'comment-retention-policy' ); ?></label>

				<br>

				<input
					type="radio"
					name="<?php echo esc_attr( IP_RETENTION_OPTION ); ?>"
					id="<?php echo esc_attr( IP_RETENTION_OPTION ); ?>-delete"
					value="delete"
					<?php checked( 'delete', $ip_retention_option ); ?>
				/>
				<label for="<?php echo esc_attr( IP_RETENTION_OPTION ); ?>-delete"><?php _e( 'Delete IPs for comments older than', 'comment-retention-policy' ); ?></label>

				<input
					type="number"
					name="<?php echo esc_attr( IP_RETENTION_PERIOD_OPTION ); ?>"
					id="<?php echo esc_attr( IP_RETENTION_PERIOD_OPTION ); ?>"
					value="<?php echo esc_attr( $ip_retention_period_option ); ?>"
					min="1"
					max="999"
				>
				<label for="<?php echo esc_attr( IP_RETENTION_PERIOD_OPTION ); ?>" class="screen-reader-text"><?php _e( 'Time period', 'comment-retention-policy' ); ?></label>

				<select id="<?php echo esc_attr( IP_RETENTION_PERIOD_UNIT_OPTION ); ?>" name="<?php echo esc_attr( IP_RETENTION_PERIOD_UNIT_OPTION ); ?>">
					<option value="days"<?php selected( 'days', $ip_retention_period_unit_option ); ?>><?php _e( 'Days', 'comment-retention-policy' ); ?></option>
					<option value="weeks"<?php selected( 'weeks', $ip_retention_period_unit_option ); ?>><?php _e( 'Weeks', 'comment-retention-policy' ); ?></option>
					<option value="months"<?php selected( 'months', $ip_retention_period_unit_option ); ?>><?php _e( 'Months', 'comment-retention-policy' ); ?></option>
				</select>
				<label for="<?php echo esc_attr( IP_RETENTION_PERIOD_UNIT_OPTION ); ?>" class="screen-reader-text"><?php _e( 'Time unit', 'comment-retention-policy' ); ?></label>

				<?php
				$next = wp_next_scheduled( DELETE_CRON_ACTION );
				if ( $next ) {
					$next_local = $next + get_option( 'gmt_offset' ) * HOUR_IN_SECONDS;
					printf(
						/* translators: %s: date of next run */
						'<p class="description">' . __( 'Next cleanup run: %s', 'comment-retention-policy' ) . '</p>',
						date_i18n( __( 'M j, Y @ H:i', 'comment-retention-policy' ), $next_local )
					);
					?>
					<?php
				}
				?>
			</fieldset>
			<?php
		},
		'discussion',
		'default'
	);
}

/**
 * Prints admin notice to inform users about setting a retention policy.
 */
function activation_notice() {
	if ( ! get_transient( ACTIVATION_TRANSIENT_KEY ) || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Show notice only once.
	delete_transient( ACTIVATION_TRANSIENT_KEY );

	// Show notice only when default option is set.
	$ip_retention_option = get_option( IP_RETENTION_OPTION );
	if ( 'keep' !== $ip_retention_option ) {
		return;
	}

	?>
	<div class="notice notice-info is-dismissible">
		<p>
			<?php
			printf(
				/* translators: %s: settings URL */
				__( 'Please visit the <a href="%s">discussion settings</a> to set a retention policy for comment IPs.', 'comment-retention-policy' ),
				esc_url( admin_url( 'options-discussion.php#comment-retention-policy' ) )
			);
			?>
		</p>
	</div>
	<?php
}
