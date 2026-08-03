<?php
/**
 * Plugin Name: Ureka IPP Submissions
 * Description: Securely stores IPP applications and New York registrations in WordPress with admin review, workflow, deletion, and CSV export.
 * Version: 1.0.0
 * Author: Ureka Education Group
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Ureka_IPP_Submissions {
	const VERSION        = '1.2.0';
	const DB_VERSION     = '3';
	const OPTION_VERSION = 'ureka_ipp_submissions_db_version';
	const CAPABILITY     = 'manage_options';
	const MENU_SLUG      = 'ureka-ipp-submissions';

	private static $instance;

	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'init', array( $this, 'maybe_install' ) );
		add_action( 'admin_init', array( $this, 'maybe_mark_read' ), 5 );
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );
		add_action( 'admin_post_ureka_ipp_update_status', array( $this, 'update_status' ) );
		add_action( 'admin_post_ureka_ipp_delete', array( $this, 'delete_submission' ) );
		add_action( 'admin_post_ureka_ipp_mark_unread', array( $this, 'mark_unread' ) );
		add_action( 'admin_post_ureka_ipp_bulk', array( $this, 'bulk_action' ) );
		add_action( 'admin_post_ureka_ipp_export', array( $this, 'export_csv' ) );
	}

	public static function activate() {
		self::create_table();
	}

	public function maybe_install() {
		if ( self::DB_VERSION !== get_option( self::OPTION_VERSION ) ) {
			self::create_table();
		}
	}

	private static function table() {
		global $wpdb;
		return $wpdb->prefix . 'ureka_ipp_submissions';
	}

	private static function create_table() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table   = self::table();
		$charset = $wpdb->get_charset_collate();
		$sql     = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			submission_uuid varchar(64) NOT NULL,
			submission_type varchar(32) NOT NULL DEFAULT 'application',
			status varchar(24) NOT NULL DEFAULT 'new',
			is_read tinyint(1) unsigned NOT NULL DEFAULT 0,
			first_name varchar(120) NOT NULL DEFAULT '',
			last_name varchar(120) NOT NULL DEFAULT '',
			email varchar(190) NOT NULL DEFAULT '',
			phone varchar(80) NOT NULL DEFAULT '',
			institution varchar(190) NOT NULL DEFAULT '',
			country varchar(120) NOT NULL DEFAULT '',
			readiness varchar(120) NOT NULL DEFAULT '',
			answers longtext NOT NULL,
			source_url text NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY submission_uuid (submission_uuid),
			KEY status (status),
			KEY is_read (is_read),
			KEY submission_type (submission_type),
			KEY email (email),
			KEY created_at (created_at)
		) {$charset};";

		dbDelta( $sql );

		$activity = self::activity_table();
		$log_sql  = "CREATE TABLE {$activity} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			submission_id bigint(20) unsigned NOT NULL DEFAULT 0,
			submission_uuid varchar(64) NOT NULL DEFAULT '',
			event_type varchar(40) NOT NULL,
			actor_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			actor_name varchar(190) NOT NULL DEFAULT '',
			actor_email varchar(190) NOT NULL DEFAULT '',
			subject_name varchar(240) NOT NULL DEFAULT '',
			subject_email varchar(190) NOT NULL DEFAULT '',
			details longtext NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY submission_id (submission_id),
			KEY event_type (event_type),
			KEY actor_user_id (actor_user_id),
			KEY created_at (created_at)
		) {$charset};";
		dbDelta( $log_sql );
		update_option( self::OPTION_VERSION, self::DB_VERSION, false );
	}

	private static function activity_table() {
		global $wpdb;
		return $wpdb->prefix . 'ureka_ipp_activity_log';
	}

	public function register_routes() {
		register_rest_route(
			'ureka/v1',
			'/ipp-submissions',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_submission' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	public function create_submission( WP_REST_Request $request ) {
		if ( ! $this->is_same_site_request( $request ) ) {
			return new WP_Error( 'ureka_forbidden_origin', 'This request did not come from the Ureka website.', array( 'status' => 403 ) );
		}

		$raw_body = $request->get_body();
		if ( strlen( $raw_body ) > 65536 ) {
			return new WP_Error( 'ureka_payload_too_large', 'The submission is too large.', array( 'status' => 413 ) );
		}

		if ( ! $this->within_rate_limit() ) {
			return new WP_Error( 'ureka_rate_limited', 'Too many submissions. Please try again shortly.', array( 'status' => 429 ) );
		}

		$payload = $request->get_json_params();
		if ( ! is_array( $payload ) ) {
			return new WP_Error( 'ureka_invalid_json', 'A valid JSON submission is required.', array( 'status' => 400 ) );
		}

		$type = isset( $payload['type'] ) ? sanitize_key( $payload['type'] ) : '';
		if ( ! in_array( $type, array( 'application', 'ny_interest' ), true ) ) {
			return new WP_Error( 'ureka_invalid_type', 'Unknown IPP submission type.', array( 'status' => 400 ) );
		}

		$uuid = isset( $payload['submission_uuid'] ) ? sanitize_text_field( $payload['submission_uuid'] ) : '';
		if ( ! preg_match( '/^[a-zA-Z0-9-]{16,64}$/', $uuid ) ) {
			return new WP_Error( 'ureka_invalid_id', 'A valid submission identifier is required.', array( 'status' => 400 ) );
		}

		$answers = isset( $payload['answers'] ) && is_array( $payload['answers'] ) ? $payload['answers'] : array();
		$answers = $this->sanitize_answers( $answers );
		$email   = isset( $answers['email'] ) ? sanitize_email( $answers['email'] ) : '';
		if ( ! is_email( $email ) || empty( $answers['first_name'] ) || empty( $answers['last_name'] ) ) {
			return new WP_Error( 'ureka_missing_identity', 'First name, last name, and a valid email are required.', array( 'status' => 400 ) );
		}
		if ( 'application' === $type && ( empty( $answers['readiness'] ) || empty( $answers['c1'] ) || empty( $answers['c2'] ) || empty( $answers['c3'] ) || empty( $answers['c4'] ) ) ) {
			return new WP_Error( 'ureka_incomplete_application', 'The application is incomplete.', array( 'status' => 400 ) );
		}
		if ( 'ny_interest' === $type && ( empty( $answers['country_residence'] ) || empty( $answers['status'] ) || empty( $answers['ny_consent'] ) || empty( $answers['ny_privacy'] ) ) ) {
			return new WP_Error( 'ureka_incomplete_registration', 'The registration is incomplete.', array( 'status' => 400 ) );
		}

		global $wpdb;
		$table    = self::table();
		$existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE submission_uuid = %s", $uuid ) );
		if ( $existing ) {
			return new WP_REST_Response( array( 'saved' => true, 'id' => (int) $existing, 'duplicate' => true ), 200 );
		}

		$now    = current_time( 'mysql' );
		$result = $wpdb->insert(
			$table,
			array(
				'submission_uuid' => $uuid,
				'submission_type' => $type,
				'status'          => 'new',
				'is_read'         => 0,
				'first_name'      => $this->answer_text( $answers, 'first_name', 120 ),
				'last_name'       => $this->answer_text( $answers, 'last_name', 120 ),
				'email'           => $email,
				'phone'           => $this->answer_text( $answers, 'whatsapp', 80 ),
				'institution'     => $this->answer_text( $answers, 'institution', 190 ),
				'country'         => $this->answer_text( $answers, 'country_residence', 120 ),
				'readiness'       => $this->answer_text( $answers, 'readiness', 120 ),
				'answers'         => wp_json_encode( $answers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),
				'source_url'      => isset( $payload['source_url'] ) ? esc_url_raw( $payload['source_url'] ) : '',
				'created_at'      => $now,
				'updated_at'      => $now,
			),
			array( '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		if ( false === $result ) {
			return new WP_Error( 'ureka_save_failed', 'The application could not be saved.', array( 'status' => 500 ) );
		}
		$submission_id = (int) $wpdb->insert_id;
		$this->log_activity(
			array(
				'id' => $submission_id,
				'submission_uuid' => $uuid,
				'first_name' => $this->answer_text( $answers, 'first_name', 120 ),
				'last_name' => $this->answer_text( $answers, 'last_name', 120 ),
				'email' => $email,
			),
			'created',
			array( 'type' => $type ),
			true
		);

		return new WP_REST_Response( array( 'saved' => true, 'id' => $submission_id ), 201 );
	}

	private function is_same_site_request( WP_REST_Request $request ) {
		$origin = $request->get_header( 'origin' );
		if ( ! $origin ) {
			return false;
		}
		$origin_host = wp_parse_url( $origin, PHP_URL_HOST );
		$site_host   = wp_parse_url( home_url(), PHP_URL_HOST );
		$origin_host = preg_replace( '/^www\./i', '', (string) $origin_host );
		$site_host   = preg_replace( '/^www\./i', '', (string) $site_host );
		return $origin_host && $site_host && strtolower( $origin_host ) === strtolower( $site_host );
	}

	private function within_rate_limit() {
		$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
		$key = 'ureka_ipp_rate_' . substr( hash_hmac( 'sha256', $ip, wp_salt( 'nonce' ) ), 0, 32 );
		$n   = (int) get_transient( $key );
		if ( $n >= 30 ) {
			return false;
		}
		set_transient( $key, $n + 1, 10 * MINUTE_IN_SECONDS );
		return true;
	}

	private function sanitize_answers( $answers ) {
		$allowed = array(
			'interest', 'first_name', 'last_name', 'email', 'whatsapp', 'country_residence', 'status',
			'ny_consent', 'ny_privacy', 'date_of_birth', 'nationality', 'institution', 'institution_country',
			'field', 'graduation', 'description', 'source', 'reason', 'timing', 'future', 'gain', 'interests', 'strengths', 'travel',
			'english', 'teamcomfort', 'teamstyle', 'availability', 'passport', 'visa', 'package', 'funding',
			'readiness', 'contact_pref', 'notes', 'c1', 'c2', 'c3', 'c4',
		);
		$clean = array();
		foreach ( $allowed as $key ) {
			if ( ! array_key_exists( $key, $answers ) ) {
				continue;
			}
			$value = $answers[ $key ];
			if ( is_array( $value ) ) {
				$value = array_slice( $value, 0, 10 );
				$clean[ $key ] = array_map( function ( $item ) {
					return $this->truncate( sanitize_textarea_field( (string) $item ), 1000 );
				}, $value );
			} else {
				$clean[ $key ] = $this->truncate( sanitize_textarea_field( (string) $value ), 4000 );
			}
		}
		return $clean;
	}

	private function answer_text( $answers, $key, $length ) {
		$value = isset( $answers[ $key ] ) ? $answers[ $key ] : '';
		if ( is_array( $value ) ) {
			$value = implode( ', ', $value );
		}
		return $this->truncate( sanitize_text_field( (string) $value ), $length );
	}

	private function truncate( $value, $length ) {
		return function_exists( 'mb_substr' ) ? mb_substr( $value, 0, $length ) : substr( $value, 0, $length );
	}

	public function admin_menu() {
		global $wpdb;
		$unread     = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . self::table() . ' WHERE is_read = 0' );
		$menu_title = 'IPP Submissions';
		if ( $unread ) {
			$menu_title .= ' <span class="uipp-menu-count" aria-label="' . esc_attr( sprintf( _n( '%s unread submission', '%s unread submissions', $unread, 'ureka-ipp' ), number_format_i18n( $unread ) ) ) . '">' . number_format_i18n( $unread ) . '</span>';
		}
		add_menu_page(
			'IPP Submissions',
			$menu_title,
			self::CAPABILITY,
			self::MENU_SLUG,
			array( $this, 'render_admin' ),
			'dashicons-forms',
			26
		);
		add_submenu_page(
			self::MENU_SLUG,
			'IPP Activity Log',
			'Activity Log',
			self::CAPABILITY,
			self::MENU_SLUG . '-activity',
			array( $this, 'render_activity_log' )
		);
		global $submenu;
		if ( isset( $submenu[ self::MENU_SLUG ][0][0] ) ) {
			$submenu[ self::MENU_SLUG ][0][0] = 'IPP Submissions';
		}
	}

	public function maybe_mark_read() {
		if ( ! is_admin() || ! current_user_can( self::CAPABILITY ) ) {
			return;
		}
		$page   = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
		$id     = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		if ( self::MENU_SLUG !== $page || 'view' !== $action || ! $id ) {
			return;
		}
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT id, submission_uuid, first_name, last_name, email, is_read FROM ' . self::table() . ' WHERE id = %d', $id ) );
		if ( ! $row ) {
			return;
		}
		$was_unread = ! (int) $row->is_read;
		if ( $was_unread ) {
			$wpdb->update( self::table(), array( 'is_read' => 1 ), array( 'id' => $id ), array( '%d' ), array( '%d' ) );
		}
		$this->log_activity( $row, 'viewed', array( 'marked_read' => $was_unread ) );
	}

	public function admin_assets( $hook ) {
		$base = plugin_dir_url( __FILE__ );
		wp_enqueue_style( 'ureka-ipp-menu-badge', $base . 'assets/menu-badge.css', array(), self::VERSION );
		if ( false === strpos( $hook, self::MENU_SLUG ) ) {
			return;
		}
		wp_enqueue_style( 'ureka-ipp-admin', $base . 'assets/admin.css', array(), self::VERSION );
		wp_enqueue_style( 'ureka-ipp-read-state', $base . 'assets/read-state.css', array( 'ureka-ipp-admin' ), self::VERSION );
		wp_enqueue_script( 'ureka-ipp-admin', $base . 'assets/admin.js', array(), self::VERSION, true );
	}

	public function render_admin() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to view IPP submissions.', 'ureka-ipp' ) );
		}
		$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
		$id     = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		if ( 'view' === $action && $id ) {
			$this->render_detail( $id );
			return;
		}
		$this->render_list();
	}

	public function render_activity_log() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to view the IPP activity log.', 'ureka-ipp' ) );
		}
		global $wpdb;
		$activity = self::activity_table();
		$submissions = self::table();
		$event = isset( $_GET['event_type'] ) ? sanitize_key( wp_unslash( $_GET['event_type'] ) ) : '';
		$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		$page = max( 1, isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1 );
		$per_page = 50;
		$offset = ( $page - 1 ) * $per_page;
		$clauses = array();
		$params = array();
		if ( array_key_exists( $event, $this->event_labels() ) ) {
			$clauses[] = 'a.event_type = %s';
			$params[] = $event;
		}
		if ( $search ) {
			$like = '%' . $wpdb->esc_like( $search ) . '%';
			$clauses[] = '(a.actor_name LIKE %s OR a.actor_email LIKE %s OR a.subject_name LIKE %s OR a.subject_email LIKE %s)';
			$params = array_merge( $params, array_fill( 0, 4, $like ) );
		}
		$where = $clauses ? 'WHERE ' . implode( ' AND ', $clauses ) : '';
		$count_sql = "SELECT COUNT(*) FROM {$activity} a {$where}";
		$total = (int) $wpdb->get_var( $params ? $wpdb->prepare( $count_sql, $params ) : $count_sql );
		$sql = "SELECT a.*, s.id AS live_submission_id FROM {$activity} a LEFT JOIN {$submissions} s ON s.id = a.submission_id {$where} ORDER BY a.created_at DESC, a.id DESC LIMIT %d OFFSET %d";
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, array_merge( $params, array( $per_page, $offset ) ) ) );
		?>
		<div class="wrap uipp-wrap">
			<div class="uipp-heading"><div><p class="uipp-kicker">Ureka Education Group</p><h1>IPP Activity Log</h1><p>A permanent record of who viewed, changed, exported, or deleted submissions.</p></div></div>
			<form method="get" class="uipp-filters"><input type="hidden" name="page" value="<?php echo esc_attr( self::MENU_SLUG . '-activity' ); ?>"><label><span class="screen-reader-text">Search activity</span><input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="Search administrator or applicant…"></label><label><span class="screen-reader-text">Event type</span><select name="event_type"><option value="">All activity</option><?php foreach ( $this->event_labels() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $event, $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label><button class="button">Filter</button><?php if ( $event || $search ) : ?><a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::MENU_SLUG . '-activity' ) ); ?>">Clear</a><?php endif; ?></form>
			<div class="uipp-table-wrap"><table class="widefat fixed striped uipp-table uipp-log-table"><thead><tr><th>Activity</th><th>Submission</th><th>Performed by</th><th>Date and time</th></tr></thead><tbody>
			<?php if ( ! $rows ) : ?><tr><td colspan="4" class="uipp-empty">No activity matches these filters.</td></tr><?php endif; ?>
			<?php foreach ( $rows as $row ) : ?>
			<tr><td><strong><?php echo esc_html( $this->activity_description( $row ) ); ?></strong><?php echo $this->activity_details_html( $row->details ); ?></td><td>
				<?php if ( $row->live_submission_id ) : ?><a class="uipp-name" href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::MENU_SLUG . '&action=view&id=' . (int) $row->submission_id ) ); ?>"><?php echo esc_html( $row->subject_name ?: '#' . $row->submission_id ); ?></a>
				<?php elseif ( $row->submission_id ) : ?><span class="uipp-name"><?php echo esc_html( $row->subject_name ?: '#' . $row->submission_id ); ?></span><small>Deleted submission</small>
				<?php else : ?><span class="uipp-name"><?php echo esc_html( $row->subject_name ?: 'System-wide action' ); ?></span><?php endif; ?>
				<?php if ( $row->subject_email ) : ?><span class="uipp-email"><?php echo esc_html( $row->subject_email ); ?></span><?php endif; ?></td><td><strong><?php echo esc_html( $row->actor_name ?: 'Website' ); ?></strong><?php if ( $row->actor_email ) : ?><small><?php echo esc_html( $row->actor_email ); ?></small><?php endif; ?></td><td><?php echo esc_html( mysql2date( 'j M Y, H:i:s', $row->created_at ) ); ?></td></tr>
			<?php endforeach; ?>
			</tbody></table></div>
			<?php $this->pagination( $page, (int) ceil( $total / $per_page ) ); ?>
		</div>
		<?php
	}

	private function render_list() {
		global $wpdb;
		$table    = self::table();
		$status   = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';
		$type     = isset( $_GET['type'] ) ? sanitize_key( wp_unslash( $_GET['type'] ) ) : '';
		$read     = isset( $_GET['read_state'] ) ? sanitize_key( wp_unslash( $_GET['read_state'] ) ) : '';
		$search   = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		$page     = max( 1, isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1 );
		$per_page = 25;
		$offset   = ( $page - 1 ) * $per_page;
		list( $where, $params ) = $this->query_filters( $status, $type, $read, $search );

		$count_sql = "SELECT COUNT(*) FROM {$table} {$where}";
		$total     = (int) $wpdb->get_var( $params ? $wpdb->prepare( $count_sql, $params ) : $count_sql );
		$data_sql  = "SELECT * FROM {$table} {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d";
		$rows      = $wpdb->get_results( $wpdb->prepare( $data_sql, array_merge( $params, array( $per_page, $offset ) ) ) );
		$unread_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE is_read = 0" );
		$today     = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE created_at >= %s", current_time( 'Y-m-d 00:00:00' ) ) );
		$all       = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
		?>
		<div class="wrap uipp-wrap">
			<div class="uipp-heading">
				<div><p class="uipp-kicker">Ureka Education Group</p><h1>IPP Submissions</h1><p>Review applications and New York interest registrations in one place.</p></div>
				<a class="button button-primary uipp-export" href="<?php echo esc_url( $this->action_url( 'ureka_ipp_export', array( 'status' => $status, 'type' => $type, 'read_state' => $read, 's' => $search ), 'ureka_ipp_export' ) ); ?>">Export CSV</a>
			</div>
			<?php $this->notice(); ?>
			<div class="uipp-stats">
				<div class="uipp-stat"><span>Unread</span><strong><?php echo esc_html( number_format_i18n( $unread_count ) ); ?></strong></div>
				<div class="uipp-stat"><span>Received today</span><strong><?php echo esc_html( number_format_i18n( $today ) ); ?></strong></div>
				<div class="uipp-stat"><span>All submissions</span><strong><?php echo esc_html( number_format_i18n( $all ) ); ?></strong></div>
			</div>
			<form method="get" class="uipp-filters">
				<input type="hidden" name="page" value="<?php echo esc_attr( self::MENU_SLUG ); ?>">
				<label><span class="screen-reader-text">Search</span><input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="Search name, email, institution…"></label>
				<label><span class="screen-reader-text">Type</span><select name="type"><option value="">All types</option><option value="application" <?php selected( $type, 'application' ); ?>>Geneva applications</option><option value="ny_interest" <?php selected( $type, 'ny_interest' ); ?>>New York interest</option></select></label>
				<label><span class="screen-reader-text">Read status</span><select name="read_state"><option value="">Read &amp; unread</option><option value="unread" <?php selected( $read, 'unread' ); ?>>Unread only</option><option value="read" <?php selected( $read, 'read' ); ?>>Read only</option></select></label>
				<label><span class="screen-reader-text">Status</span><select name="status"><option value="">All statuses</option><?php foreach ( $this->statuses() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $status, $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label>
				<button class="button">Filter</button>
				<?php if ( $search || $status || $type || $read ) : ?><a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::MENU_SLUG ) ); ?>">Clear</a><?php endif; ?>
			</form>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="ureka_ipp_bulk">
				<?php wp_nonce_field( 'ureka_ipp_bulk' ); ?>
				<div class="uipp-bulk"><select name="bulk_action"><option value="">Bulk actions</option><option value="mark_read">Mark as read</option><option value="mark_unread">Mark as unread</option><?php foreach ( $this->statuses() as $key => $label ) : ?><option value="status_<?php echo esc_attr( $key ); ?>">Mark <?php echo esc_html( strtolower( $label ) ); ?></option><?php endforeach; ?><option value="delete">Delete permanently</option></select><button class="button">Apply</button></div>
				<div class="uipp-table-wrap"><table class="widefat fixed striped uipp-table"><thead><tr><td class="check-column"><input type="checkbox" data-uipp-select-all aria-label="Select all"></td><th>Applicant</th><th>Type</th><th>Country</th><th>Readiness</th><th>Read</th><th>Status</th><th>Received</th></tr></thead><tbody>
				<?php if ( ! $rows ) : ?><tr><td colspan="8" class="uipp-empty">No submissions match these filters.</td></tr><?php endif; ?>
				<?php foreach ( $rows as $row ) : $view = admin_url( 'admin.php?page=' . self::MENU_SLUG . '&action=view&id=' . (int) $row->id ); ?>
				<tr class="<?php echo $row->is_read ? '' : 'uipp-unread-row'; ?>"><th class="check-column"><input type="checkbox" name="ids[]" value="<?php echo esc_attr( $row->id ); ?>" aria-label="Select <?php echo esc_attr( trim( $row->first_name . ' ' . $row->last_name ) ); ?>"></th>
				<td><a class="uipp-name" href="<?php echo esc_url( $view ); ?>"><?php echo esc_html( trim( $row->first_name . ' ' . $row->last_name ) ); ?></a><a class="uipp-email" href="mailto:<?php echo esc_attr( $row->email ); ?>"><?php echo esc_html( $row->email ); ?></a><?php if ( $row->institution ) : ?><small><?php echo esc_html( $row->institution ); ?></small><?php endif; ?></td>
				<td><span class="uipp-type"><?php echo esc_html( $this->type_label( $row->submission_type ) ); ?></span></td><td><?php echo esc_html( $row->country ?: '—' ); ?></td><td><?php echo esc_html( $row->readiness ?: '—' ); ?></td><td><span class="uipp-read-state <?php echo $row->is_read ? 'is-read' : 'is-unread'; ?>"><?php echo $row->is_read ? 'Read' : 'Unread'; ?></span></td><td><span class="uipp-status uipp-status-<?php echo esc_attr( $row->status ); ?>"><?php echo esc_html( $this->status_label( $row->status ) ); ?></span></td><td><time datetime="<?php echo esc_attr( mysql2date( DATE_ATOM, $row->created_at ) ); ?>"><?php echo esc_html( mysql2date( 'j M Y, H:i', $row->created_at ) ); ?></time></td></tr>
				<?php endforeach; ?></tbody></table></div>
			</form>
			<?php $this->pagination( $page, (int) ceil( $total / $per_page ) ); ?>
		</div>
		<?php
	}

	private function render_detail( $id ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id = %d', $id ) );
		if ( ! $row ) {
			wp_die( esc_html__( 'Submission not found.', 'ureka-ipp' ) );
		}
		$answers = json_decode( $row->answers, true );
		$answers = is_array( $answers ) ? $answers : array();
		$activity = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . self::activity_table() . ' WHERE submission_id = %d ORDER BY created_at DESC, id DESC LIMIT 20', $id ) );
		?>
		<div class="wrap uipp-wrap">
			<a class="uipp-back" href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::MENU_SLUG ) ); ?>">← Back to submissions</a>
			<?php $this->notice(); ?>
			<div class="uipp-detail-head"><div><span class="uipp-type"><?php echo esc_html( $this->type_label( $row->submission_type ) ); ?></span><h1><?php echo esc_html( trim( $row->first_name . ' ' . $row->last_name ) ); ?></h1><p>Received <?php echo esc_html( mysql2date( 'j F Y \a\t H:i', $row->created_at ) ); ?></p></div><div class="uipp-detail-badges"><span class="uipp-read-state is-read">Read</span><span class="uipp-status uipp-status-<?php echo esc_attr( $row->status ); ?>"><?php echo esc_html( $this->status_label( $row->status ) ); ?></span></div></div>
			<div class="uipp-detail-grid"><main>
				<section class="uipp-card"><h2>Contact</h2><dl class="uipp-contact"><div><dt>Email</dt><dd><a href="mailto:<?php echo esc_attr( $row->email ); ?>"><?php echo esc_html( $row->email ); ?></a></dd></div><div><dt>WhatsApp</dt><dd><?php if ( $row->phone ) : ?><a href="https://wa.me/<?php echo esc_attr( preg_replace( '/\D+/', '', $row->phone ) ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $row->phone ); ?></a><?php else : ?>—<?php endif; ?></dd></div><div><dt>Institution</dt><dd><?php echo esc_html( $row->institution ?: '—' ); ?></dd></div><div><dt>Country</dt><dd><?php echo esc_html( $row->country ?: '—' ); ?></dd></div></dl></section>
				<section class="uipp-card"><h2>All answers</h2><dl class="uipp-answers"><?php foreach ( $answers as $key => $value ) : ?><div><dt><?php echo esc_html( $this->question_label( $key ) ); ?></dt><dd><?php echo nl2br( esc_html( is_array( $value ) ? implode( ', ', $value ) : $value ) ); ?></dd></div><?php endforeach; ?></dl></section>
				<section class="uipp-card"><div class="uipp-card-heading"><h2>Activity</h2><a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::MENU_SLUG . '-activity&s=' . rawurlencode( $row->email ) ) ); ?>">View full log</a></div><ol class="uipp-activity-list"><?php foreach ( $activity as $item ) : ?><li><span class="uipp-activity-dot"></span><div><strong><?php echo esc_html( $this->activity_description( $item ) ); ?></strong><?php echo $this->activity_details_html( $item->details ); ?><p><?php echo esc_html( $item->actor_name ?: 'Website' ); ?> · <?php echo esc_html( mysql2date( 'j M Y, H:i:s', $item->created_at ) ); ?></p></div></li><?php endforeach; ?></ol></section>
			</main><aside>
				<section class="uipp-card uipp-actions"><h2>Manage</h2><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="ureka_ipp_update_status"><input type="hidden" name="id" value="<?php echo esc_attr( $row->id ); ?>"><?php wp_nonce_field( 'ureka_ipp_update_status_' . $row->id ); ?><label for="uipp-status">Workflow status</label><select id="uipp-status" name="status"><?php foreach ( $this->statuses() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $row->status, $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select><button class="button button-primary">Save status</button></form><hr><a class="button" href="mailto:<?php echo esc_attr( $row->email ); ?>">Email applicant</a><?php if ( $row->phone ) : ?><a class="button" target="_blank" rel="noopener noreferrer" href="https://wa.me/<?php echo esc_attr( preg_replace( '/\D+/', '', $row->phone ) ); ?>">Open WhatsApp</a><?php endif; ?><a class="button" href="<?php echo esc_url( $this->action_url( 'ureka_ipp_mark_unread', array( 'id' => $row->id ), 'ureka_ipp_mark_unread_' . $row->id ) ); ?>">Mark unread</a><hr><a class="uipp-delete" data-uipp-confirm="Permanently delete this submission? This cannot be undone." href="<?php echo esc_url( $this->action_url( 'ureka_ipp_delete', array( 'id' => $row->id ), 'ureka_ipp_delete_' . $row->id ) ); ?>">Delete submission</a></section>
				<section class="uipp-card uipp-meta"><h2>Record details</h2><dl><dt>Submission ID</dt><dd>#<?php echo esc_html( $row->id ); ?></dd><dt>Last updated</dt><dd><?php echo esc_html( mysql2date( 'j M Y, H:i', $row->updated_at ) ); ?></dd><?php if ( $row->source_url ) : ?><dt>Source</dt><dd><a href="<?php echo esc_url( $row->source_url ); ?>" target="_blank" rel="noopener noreferrer">IPP page ↗</a></dd><?php endif; ?></dl></section>
			</aside></div>
		</div>
		<?php
	}

	private function log_activity( $submission, $event_type, $details = array(), $system = false ) {
		global $wpdb;
		$row = is_object( $submission ) ? get_object_vars( $submission ) : (array) $submission;
		$user = $system ? false : wp_get_current_user();
		$first = isset( $row['first_name'] ) ? $row['first_name'] : '';
		$last = isset( $row['last_name'] ) ? $row['last_name'] : '';
		$wpdb->insert(
			self::activity_table(),
			array(
				'submission_id' => isset( $row['id'] ) ? absint( $row['id'] ) : 0,
				'submission_uuid' => isset( $row['submission_uuid'] ) ? sanitize_text_field( $row['submission_uuid'] ) : '',
				'event_type' => sanitize_key( $event_type ),
				'actor_user_id' => $user && $user->exists() ? (int) $user->ID : 0,
				'actor_name' => $user && $user->exists() ? sanitize_text_field( $user->display_name ) : 'Website',
				'actor_email' => $user && $user->exists() ? sanitize_email( $user->user_email ) : '',
				'subject_name' => sanitize_text_field( trim( $first . ' ' . $last ) ),
				'subject_email' => isset( $row['email'] ) ? sanitize_email( $row['email'] ) : '',
				'details' => wp_json_encode( $details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),
				'created_at' => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
		);
	}

	private function event_labels() {
		return array(
			'created' => 'Submission received',
			'viewed' => 'Submission opened',
			'marked_read' => 'Marked read',
			'marked_unread' => 'Marked unread',
			'status_changed' => 'Workflow status changed',
			'exported' => 'Submissions exported',
			'deleted' => 'Submission deleted',
		);
	}

	private function activity_description( $row ) {
		$details = json_decode( $row->details, true );
		$details = is_array( $details ) ? $details : array();
		if ( 'viewed' === $row->event_type && ! empty( $details['marked_read'] ) ) {
			return 'Opened and marked read';
		}
		$labels = $this->event_labels();
		return isset( $labels[ $row->event_type ] ) ? $labels[ $row->event_type ] : ucwords( str_replace( '_', ' ', $row->event_type ) );
	}

	private function activity_details_html( $json ) {
		$details = json_decode( $json, true );
		if ( ! is_array( $details ) ) {
			return '';
		}
		$text = '';
		if ( isset( $details['from'], $details['to'] ) ) {
			$text = $this->status_label( $details['from'] ) . ' → ' . $this->status_label( $details['to'] );
		} elseif ( isset( $details['count'] ) ) {
			$text = number_format_i18n( (int) $details['count'] ) . ' submission(s)';
		} elseif ( isset( $details['type'] ) ) {
			$text = $this->type_label( $details['type'] );
		}
		return $text ? '<small class="uipp-log-detail">' . esc_html( $text ) . '</small>' : '';
	}

	private function statuses() {
		return array( 'new' => 'New', 'reviewing' => 'Reviewing', 'contacted' => 'Contacted', 'accepted' => 'Accepted', 'declined' => 'Declined', 'archived' => 'Archived' );
	}

	private function status_label( $status ) {
		$statuses = $this->statuses();
		return isset( $statuses[ $status ] ) ? $statuses[ $status ] : ucfirst( $status );
	}

	private function type_label( $type ) {
		return 'ny_interest' === $type ? 'New York interest' : 'Geneva application';
	}

	private function question_label( $key ) {
		$labels = array(
			'interest' => 'Programme interest', 'first_name' => 'First name', 'last_name' => 'Last name', 'email' => 'Email address', 'whatsapp' => 'WhatsApp number', 'country_residence' => 'Country of residence', 'status' => 'Current status', 'ny_consent' => 'Contact consent', 'ny_privacy' => 'Privacy consent', 'date_of_birth' => 'Date of birth', 'nationality' => 'Nationality', 'institution' => 'University or institution', 'institution_country' => 'Institution country', 'field' => 'Field of study or work', 'graduation' => 'Expected graduation', 'description' => 'Current situation', 'source' => 'What encouraged the application', 'reason' => 'Main reason for applying', 'timing' => 'Why now', 'future' => 'Goal in two years', 'gain' => 'What they hope to gain', 'interests' => 'Learning areas of interest', 'strengths' => 'Team strengths', 'travel' => 'International travel experience', 'english' => 'English confidence', 'teamcomfort' => 'Cross-cultural team comfort', 'teamstyle' => 'Group project style', 'availability' => 'Programme availability', 'passport' => 'Passport status', 'visa' => 'Swiss visa requirement', 'package' => 'Programme option', 'funding' => 'Expected funding', 'readiness' => 'Readiness to proceed', 'contact_pref' => 'Preferred contact method', 'notes' => 'Additional notes', 'c1' => 'Information accuracy confirmed', 'c2' => 'Admission terms confirmed', 'c3' => 'Contact consent', 'c4' => 'Privacy and programme terms agreed',
		);
		return isset( $labels[ $key ] ) ? $labels[ $key ] : ucwords( str_replace( '_', ' ', $key ) );
	}

	private function query_filters( $status, $type, $read, $search ) {
		global $wpdb;
		$clauses = array();
		$params  = array();
		if ( array_key_exists( $status, $this->statuses() ) ) {
			$clauses[] = 'status = %s'; $params[] = $status;
		}
		if ( in_array( $type, array( 'application', 'ny_interest' ), true ) ) {
			$clauses[] = 'submission_type = %s'; $params[] = $type;
		}
		if ( in_array( $read, array( 'read', 'unread' ), true ) ) {
			$clauses[] = 'is_read = %d'; $params[] = 'read' === $read ? 1 : 0;
		}
		if ( $search ) {
			$like      = '%' . $wpdb->esc_like( $search ) . '%';
			$clauses[] = '(first_name LIKE %s OR last_name LIKE %s OR email LIKE %s OR institution LIKE %s OR country LIKE %s OR answers LIKE %s)';
			$params     = array_merge( $params, array_fill( 0, 6, $like ) );
		}
		return array( $clauses ? 'WHERE ' . implode( ' AND ', $clauses ) : '', $params );
	}

	public function update_status() {
		$this->require_admin();
		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		check_admin_referer( 'ureka_ipp_update_status_' . $id );
		$status = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : '';
		if ( $id && array_key_exists( $status, $this->statuses() ) ) {
			global $wpdb;
			$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id = %d', $id ) );
			if ( $row && $row->status !== $status ) {
				$wpdb->update( self::table(), array( 'status' => $status, 'updated_at' => current_time( 'mysql' ) ), array( 'id' => $id ), array( '%s', '%s' ), array( '%d' ) );
				$this->log_activity( $row, 'status_changed', array( 'from' => $row->status, 'to' => $status ) );
			}
		}
		$this->redirect( array( 'action' => 'view', 'id' => $id, 'uipp_notice' => 'updated' ) );
	}

	public function delete_submission() {
		$this->require_admin();
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		check_admin_referer( 'ureka_ipp_delete_' . $id );
		if ( $id ) {
			global $wpdb;
			$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id = %d', $id ) );
			if ( $row ) {
				$this->log_activity( $row, 'deleted' );
				$wpdb->delete( self::table(), array( 'id' => $id ), array( '%d' ) );
			}
		}
		$this->redirect( array( 'uipp_notice' => 'deleted' ) );
	}

	public function mark_unread() {
		$this->require_admin();
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		check_admin_referer( 'ureka_ipp_mark_unread_' . $id );
		if ( $id ) {
			global $wpdb;
			$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id = %d', $id ) );
			if ( $row && (int) $row->is_read ) {
				$wpdb->update( self::table(), array( 'is_read' => 0 ), array( 'id' => $id ), array( '%d' ), array( '%d' ) );
				$this->log_activity( $row, 'marked_unread' );
			}
		}
		$this->redirect( array( 'read_state' => 'unread', 'uipp_notice' => 'unread' ) );
	}

	public function bulk_action() {
		$this->require_admin();
		check_admin_referer( 'ureka_ipp_bulk' );
		$ids    = isset( $_POST['ids'] ) ? array_filter( array_map( 'absint', (array) wp_unslash( $_POST['ids'] ) ) ) : array();
		$action = isset( $_POST['bulk_action'] ) ? sanitize_key( wp_unslash( $_POST['bulk_action'] ) ) : '';
		if ( $ids ) {
			global $wpdb;
			$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
			$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . self::table() . " WHERE id IN ({$placeholders})", $ids ) );
			if ( 'delete' === $action ) {
				foreach ( $rows as $row ) { $this->log_activity( $row, 'deleted', array( 'bulk' => true ) ); }
				$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . self::table() . " WHERE id IN ({$placeholders})", $ids ) );
			} elseif ( 'mark_read' === $action || 'mark_unread' === $action ) {
				$is_read = 'mark_read' === $action ? 1 : 0;
				$wpdb->query( $wpdb->prepare( 'UPDATE ' . self::table() . " SET is_read = %d WHERE id IN ({$placeholders})", array_merge( array( $is_read ), $ids ) ) );
				foreach ( $rows as $row ) {
					if ( (int) $row->is_read !== $is_read ) { $this->log_activity( $row, $is_read ? 'marked_read' : 'marked_unread', array( 'bulk' => true ) ); }
				}
			} elseif ( 0 === strpos( $action, 'status_' ) ) {
				$status = substr( $action, 7 );
				if ( array_key_exists( $status, $this->statuses() ) ) {
					$wpdb->query( $wpdb->prepare( 'UPDATE ' . self::table() . " SET status = %s, updated_at = %s WHERE id IN ({$placeholders})", array_merge( array( $status, current_time( 'mysql' ) ), $ids ) ) );
					foreach ( $rows as $row ) {
						if ( $row->status !== $status ) { $this->log_activity( $row, 'status_changed', array( 'from' => $row->status, 'to' => $status, 'bulk' => true ) ); }
					}
				}
			}
		}
		$this->redirect( array( 'uipp_notice' => 'bulk' ) );
	}

	public function export_csv() {
		$this->require_admin();
		check_admin_referer( 'ureka_ipp_export' );
		$status = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';
		$type   = isset( $_GET['type'] ) ? sanitize_key( wp_unslash( $_GET['type'] ) ) : '';
		$read   = isset( $_GET['read_state'] ) ? sanitize_key( wp_unslash( $_GET['read_state'] ) ) : '';
		$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		list( $where, $params ) = $this->query_filters( $status, $type, $read, $search );
		global $wpdb;
		$sql  = 'SELECT * FROM ' . self::table() . " {$where} ORDER BY created_at DESC";
		$rows = $wpdb->get_results( $params ? $wpdb->prepare( $sql, $params ) : $sql );
		$this->log_activity( array( 'id' => 0, 'submission_uuid' => '', 'first_name' => 'CSV export', 'last_name' => '', 'email' => '' ), 'exported', array( 'count' => count( $rows ) ) );
		$keys = array();
		foreach ( $rows as $row ) { $answers = json_decode( $row->answers, true ); if ( is_array( $answers ) ) { $keys = array_values( array_unique( array_merge( $keys, array_keys( $answers ) ) ) ); } }
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="ipp-submissions-' . gmdate( 'Y-m-d' ) . '.csv"' );
		$out = fopen( 'php://output', 'w' );
		fwrite( $out, "\xEF\xBB\xBF" );
		fputcsv( $out, array_merge( array( 'ID', 'Type', 'Read status', 'Workflow status', 'Received' ), array_map( array( $this, 'question_label' ), $keys ) ) );
		foreach ( $rows as $row ) {
			$answers = json_decode( $row->answers, true ); $answers = is_array( $answers ) ? $answers : array();
			$line = array( $row->id, $this->type_label( $row->submission_type ), $row->is_read ? 'Read' : 'Unread', $this->status_label( $row->status ), $row->created_at );
			foreach ( $keys as $key ) { $value = isset( $answers[ $key ] ) ? $answers[ $key ] : ''; $line[] = $this->csv_safe( is_array( $value ) ? implode( '; ', $value ) : $value ); }
			fputcsv( $out, $line );
		}
		fclose( $out );
		exit;
	}

	private function csv_safe( $value ) {
		$value = (string) $value;
		return preg_match( '/^[=+\-@]/', $value ) ? "'" . $value : $value;
	}

	private function action_url( $action, $args, $nonce_action ) {
		$args['action'] = $action;
		return wp_nonce_url( add_query_arg( $args, admin_url( 'admin-post.php' ) ), $nonce_action );
	}

	private function require_admin() {
		if ( ! current_user_can( self::CAPABILITY ) ) { wp_die( esc_html__( 'You do not have permission to manage IPP submissions.', 'ureka-ipp' ) ); }
	}

	private function redirect( $args ) {
		wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php?page=' . self::MENU_SLUG ) ) ); exit;
	}

	private function notice() {
		$notice = isset( $_GET['uipp_notice'] ) ? sanitize_key( wp_unslash( $_GET['uipp_notice'] ) ) : '';
		$labels = array( 'updated' => 'Submission status updated.', 'deleted' => 'Submission permanently deleted.', 'unread' => 'Submission marked unread.', 'bulk' => 'Bulk action completed.' );
		if ( isset( $labels[ $notice ] ) ) { echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $labels[ $notice ] ) . '</p></div>'; }
	}

	private function pagination( $current, $total ) {
		if ( $total < 2 ) { return; }
		$base = add_query_arg( 'paged', '%#%', remove_query_arg( 'paged' ) );
		echo '<div class="uipp-pagination">' . wp_kses_post( paginate_links( array( 'base' => $base, 'format' => '', 'current' => $current, 'total' => $total, 'type' => 'list' ) ) ) . '</div>';
	}
}

register_activation_hook( __FILE__, array( 'Ureka_IPP_Submissions', 'activate' ) );
Ureka_IPP_Submissions::instance();
