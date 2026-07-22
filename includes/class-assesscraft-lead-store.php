<?php
defined( 'ABSPATH' ) || exit;

final class AssessCraft_Lead_Store {
	private const DB_VERSION = '2';
	private const DB_OPTION = 'assesscraft_leads_db_version';
	private const RETENTION_OPTION = 'assesscraft_lead_retention_days';
	private const UNINSTALL_OPTION = 'assesscraft_uninstall_behavior';
	private const CRON_HOOK = 'assesscraft_cleanup_leads';

	public function register(): void {
		add_action( 'init', array( $this, 'maybe_install' ), 5 );
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_post_assesscraft_export_leads', array( $this, 'export_csv' ) );
		add_action( 'admin_post_assesscraft_delete_lead', array( $this, 'delete_lead' ) );
		add_action( 'admin_post_assesscraft_purge_leads', array( $this, 'purge_leads' ) );
		add_action( 'admin_post_assesscraft_save_lead_settings', array( $this, 'save_settings' ) );
		add_action( self::CRON_HOOK, array( $this, 'cleanup_expired' ) );
	}

	public function maybe_install(): void {
		if ( self::DB_VERSION !== get_option( self::DB_OPTION ) ) {
			global $wpdb;
			$table = self::table();
			$charset = $wpdb->get_charset_collate();
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			assessment_id bigint(20) unsigned NOT NULL DEFAULT 0,
			assessment_name varchar(255) NOT NULL DEFAULT '',
			contact_name varchar(160) NOT NULL DEFAULT '',
			email varchar(190) NOT NULL DEFAULT '',
			company varchar(190) NOT NULL DEFAULT '',
			phone varchar(80) NOT NULL DEFAULT '',
			message text NOT NULL,
			overall_score decimal(5,2) NOT NULL DEFAULT 0,
			classification varchar(160) NOT NULL DEFAULT '',
			profile varchar(190) NOT NULL DEFAULT '',
			consent_at datetime NULL,
			source_url text NOT NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY assessment_id (assessment_id),
			KEY created_at (created_at),
			KEY email (email)
			) {$charset};" );
			update_option( self::DB_OPTION, self::DB_VERSION, false );
			if ( false === get_option( self::RETENTION_OPTION, false ) ) {
				add_option( self::RETENTION_OPTION, 90, '', false );
			}
			if ( false === get_option( self::UNINSTALL_OPTION, false ) ) {
				add_option( self::UNINSTALL_OPTION, 'keep', '', false );
			}
		}

		// Deactivation clears the event, so ensure reactivation schedules it again.
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CRON_HOOK );
		}
	}

	public static function store( int $assessment_id, array $contact, array $result ): bool {
		global $wpdb;
		return false !== $wpdb->insert(
			self::table(),
			array(
				'assessment_id'   => $assessment_id,
				'assessment_name' => substr( sanitize_text_field( get_the_title( $assessment_id ) ), 0, 255 ),
				'contact_name'    => substr( sanitize_text_field( $contact['name'] ?? '' ), 0, 160 ),
				'email'           => substr( sanitize_email( $contact['email'] ?? '' ), 0, 190 ),
				'company'         => substr( sanitize_text_field( $contact['company'] ?? '' ), 0, 190 ),
				'phone'           => substr( sanitize_text_field( $contact['phone'] ?? '' ), 0, 80 ),
				'message'         => substr( sanitize_textarea_field( $contact['message'] ?? '' ), 0, 3000 ),
				'overall_score'   => max( 0, min( 100, (float) ( $result['overall'] ?? 0 ) ) ),
				'classification'  => substr( sanitize_text_field( $result['classification'] ?? '' ), 0, 160 ),
				'profile'         => substr( sanitize_text_field( $result['profile'] ?? '' ), 0, 190 ),
				'consent_at'      => current_time( 'mysql', true ),
				'source_url'      => substr( esc_url_raw( wp_get_referer() ?: '' ), 0, 2000 ),
				'created_at'      => current_time( 'mysql', true ),
			),
			array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%s', '%s', '%s', '%s', '%s' )
		);
	}

	public function menu(): void {
		add_submenu_page( 'edit.php?post_type=' . AssessCraft_Post_Type::TYPE, __( 'Consultation Requests', 'assesscraft' ), __( 'Consultation Requests', 'assesscraft' ), 'manage_options', 'assesscraft-leads', array( $this, 'render' ) );
	}

	public function render(): void {
		$this->guard_admin();
		$page = max( 1, absint( $_GET['paged'] ?? 1 ) );
		$filters = $this->filters();
		$per_page = 20;
		$rows = $this->query( $filters, $per_page, ( $page - 1 ) * $per_page );
		$total = $this->count( $filters );
		$assessments = $this->distinct( 'assessment_name' );
		$profiles = $this->distinct( 'profile' );
		$export_url = wp_nonce_url( add_query_arg( array_merge( array( 'action' => 'assesscraft_export_leads' ), $filters ), admin_url( 'admin-post.php' ) ), 'assesscraft_export_leads' );
		?>
		<div class="wrap ac-leads-page">
			<h1><?php esc_html_e( 'Consultation Requests', 'assesscraft' ); ?></h1>
			<?php $this->notice(); ?>
			<p class="description"><?php esc_html_e( 'Only requests from assessments with storage explicitly enabled appear here. Individual question answers are never stored.', 'assesscraft' ); ?></p>
			<div class="ac-leads-summary"><article><strong><?php echo absint( $total ); ?></strong><span><?php esc_html_e( 'Matching requests', 'assesscraft' ); ?></span></article><article><strong><?php echo absint( get_option( self::RETENTION_OPTION, 90 ) ); ?></strong><span><?php esc_html_e( 'Retention days (0 = forever)', 'assesscraft' ); ?></span></article></div>
			<form class="ac-leads-filters" method="get">
				<input type="hidden" name="post_type" value="<?php echo esc_attr( AssessCraft_Post_Type::TYPE ); ?>"><input type="hidden" name="page" value="assesscraft-leads">
				<input type="search" name="s" value="<?php echo esc_attr( $filters['s'] ); ?>" placeholder="<?php esc_attr_e( 'Search name, email, company, assessment, or profile', 'assesscraft' ); ?>">
				<select name="assessment"><option value=""><?php esc_html_e( 'All assessments', 'assesscraft' ); ?></option><?php foreach ( $assessments as $value ) : ?><option value="<?php echo esc_attr( $value ); ?>" <?php selected( $filters['assessment'], $value ); ?>><?php echo esc_html( $value ); ?></option><?php endforeach; ?></select>
				<select name="profile"><option value=""><?php esc_html_e( 'All profiles', 'assesscraft' ); ?></option><?php foreach ( $profiles as $value ) : ?><option value="<?php echo esc_attr( $value ); ?>" <?php selected( $filters['profile'], $value ); ?>><?php echo esc_html( $value ); ?></option><?php endforeach; ?></select>
				<button class="button button-primary"><?php esc_html_e( 'Filter', 'assesscraft' ); ?></button><a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . AssessCraft_Post_Type::TYPE . '&page=assesscraft-leads' ) ); ?>"><?php esc_html_e( 'Reset', 'assesscraft' ); ?></a><?php if ( AssessCraft_Features::available( 'lead_csv_export' ) ) : ?><a class="button" href="<?php echo esc_url( $export_url ); ?>"><?php esc_html_e( 'Export CSV', 'assesscraft' ); ?></a><?php else : ?><a class="button" href="<?php echo esc_url( AssessCraft_Features::upgrade_url() ); ?>"><?php esc_html_e( 'Unlock CSV export with Pro', 'assesscraft' ); ?></a><?php endif; ?>
			</form>
			<div class="ac-leads-table-wrap"><table class="widefat striped ac-leads-table"><thead><tr><th><?php esc_html_e( 'Submitted', 'assesscraft' ); ?></th><th><?php esc_html_e( 'Contact', 'assesscraft' ); ?></th><th><?php esc_html_e( 'Assessment', 'assesscraft' ); ?></th><th><?php esc_html_e( 'Score', 'assesscraft' ); ?></th><th><?php esc_html_e( 'Profile', 'assesscraft' ); ?></th><th></th></tr></thead><tbody>
			<?php if ( ! $rows ) : ?><tr><td colspan="6"><?php esc_html_e( 'No stored consultation requests match these filters.', 'assesscraft' ); ?></td></tr><?php endif; ?>
			<?php foreach ( $rows as $row ) : $delete_url = wp_nonce_url( admin_url( 'admin-post.php?action=assesscraft_delete_lead&lead=' . absint( $row->id ) ), 'assesscraft_delete_lead_' . absint( $row->id ) ); ?><tr><td><?php echo esc_html( get_date_from_gmt( $row->created_at, 'M j, Y H:i' ) ); ?></td><td><strong><?php echo esc_html( $row->contact_name ); ?></strong><br><a href="mailto:<?php echo esc_attr( $row->email ); ?>"><?php echo esc_html( $row->email ); ?></a><?php if ( $row->company ) : ?><br><small><?php echo esc_html( $row->company ); ?></small><?php endif; ?><details><summary><?php esc_html_e( 'Contact details', 'assesscraft' ); ?></summary><p><?php echo esc_html( $row->phone ); ?></p><p><?php echo nl2br( esc_html( $row->message ) ); ?></p></details></td><td><?php echo esc_html( $row->assessment_name ); ?></td><td><strong><?php echo esc_html( round( (float) $row->overall_score ) ); ?>%</strong><br><small><?php echo esc_html( $row->classification ); ?></small></td><td><?php echo esc_html( $row->profile ?: '—' ); ?></td><td><a class="button-link-delete ac-confirm-delete" href="<?php echo esc_url( $delete_url ); ?>"><?php esc_html_e( 'Delete', 'assesscraft' ); ?></a></td></tr><?php endforeach; ?>
			</tbody></table></div>
			<?php
			echo wp_kses_post(
				paginate_links(
					array(
						'base'    => add_query_arg( 'paged', '%#%' ),
						'format'  => '',
						'current' => $page,
						'total'   => max( 1, (int) ceil( $total / $per_page ) ),
					)
				)
			);
			?>
			<section class="ac-lead-privacy-settings"><h2><?php esc_html_e( 'Privacy and retention', 'assesscraft' ); ?></h2><div><?php if ( AssessCraft_Features::available( 'lead_csv_export' ) ) : ?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="assesscraft_save_lead_settings"><?php wp_nonce_field( 'assesscraft_save_lead_settings' ); ?><label><?php esc_html_e( 'Automatically delete stored requests after', 'assesscraft' ); ?> <select name="retention_days"><?php foreach ( array( 30 => '30 days', 90 => '90 days', 180 => '180 days', 365 => '365 days', 0 => 'Keep forever' ) as $days => $label ) : ?><option value="<?php echo absint( $days ); ?>" <?php selected( (int) get_option( self::RETENTION_OPTION, 90 ), $days ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label><button class="button button-primary"><?php esc_html_e( 'Save retention', 'assesscraft' ); ?></button></form><?php else : ?><p><a href="<?php echo esc_url( AssessCraft_Features::upgrade_url() ); ?>"><?php esc_html_e( 'Custom retention schedules are available in Pro.', 'assesscraft' ); ?></a></p><?php endif; ?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('<?php echo esc_js( __( 'Permanently delete every stored consultation request?', 'assesscraft' ) ); ?>');"><input type="hidden" name="action" value="assesscraft_purge_leads"><?php wp_nonce_field( 'assesscraft_purge_leads' ); ?><button class="button button-link-delete"><?php esc_html_e( 'Delete all stored requests', 'assesscraft' ); ?></button></form></div><p><?php esc_html_e( 'Storage is disabled by default for every assessment. Retention cleanup runs daily through WordPress Cron.', 'assesscraft' ); ?></p></section>
			<section class="ac-lead-privacy-settings"><h2><?php esc_html_e( 'Uninstall data choice', 'assesscraft' ); ?></h2><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="assesscraft_save_lead_settings"><input type="hidden" name="retention_days" value="<?php echo absint( get_option( self::RETENTION_OPTION, 90 ) ); ?>"><?php wp_nonce_field( 'assesscraft_save_lead_settings' ); ?><label><select name="uninstall_behavior"><option value="keep" <?php selected( get_option( self::UNINSTALL_OPTION, 'keep' ), 'keep' ); ?>><?php esc_html_e( 'Keep assessments and stored requests', 'assesscraft' ); ?></option><option value="delete" <?php selected( get_option( self::UNINSTALL_OPTION, 'keep' ), 'delete' ); ?>><?php esc_html_e( 'Permanently delete all AssessCraft data', 'assesscraft' ); ?></option></select></label> <button class="button button-primary"><?php esc_html_e( 'Save uninstall choice', 'assesscraft' ); ?></button></form><p><?php esc_html_e( 'Keeping data is the default. The delete option runs only when AssessCraft is deleted from the Plugins screen, not when it is merely deactivated.', 'assesscraft' ); ?></p></section>
		</div>
		<script>document.querySelectorAll('.ac-confirm-delete').forEach(function(link){link.addEventListener('click',function(event){if(!window.confirm('<?php echo esc_js( __( 'Permanently delete this consultation request?', 'assesscraft' ) ); ?>'))event.preventDefault();});});</script>
		<?php
	}

	public function export_csv(): void {
		$this->guard_action( 'assesscraft_export_leads' );
		if ( ! AssessCraft_Features::available( 'lead_csv_export' ) ) {
			wp_die( esc_html__( 'CSV export requires AssessCraft Pro.', 'assesscraft' ), esc_html__( 'AssessCraft Pro required', 'assesscraft' ), array( 'response' => 403 ) );
		}
		$rows = $this->query( $this->filters(), 0, 0 );
		nocache_headers(); header( 'Content-Type: text/csv; charset=utf-8' ); header( 'Content-Disposition: attachment; filename="assesscraft-consultation-requests-' . gmdate( 'Y-m-d' ) . '.csv"' );
		$output = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		fputcsv( $output, array( 'Submitted', 'Assessment', 'Name', 'Email', 'Company', 'Phone', 'Score', 'Classification', 'Profile', 'Message' ) );
		foreach ( $rows as $row ) {
			fputcsv(
				$output,
				array(
					$this->csv_cell( $row->created_at ),
					$this->csv_cell( $row->assessment_name ),
					$this->csv_cell( $row->contact_name ),
					$this->csv_cell( $row->email ),
					$this->csv_cell( $row->company ),
					$this->csv_cell( $row->phone ),
					(float) $row->overall_score,
					$this->csv_cell( $row->classification ),
					$this->csv_cell( $row->profile ),
					$this->csv_cell( $row->message ),
				)
			);
		}
		fclose( $output ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		exit;
	}

	public function delete_lead(): void {
		$id = absint( $_GET['lead'] ?? 0 ); $this->guard_action( 'assesscraft_delete_lead_' . $id ); global $wpdb; $wpdb->delete( self::table(), array( 'id' => $id ), array( '%d' ) ); $this->redirect( 'deleted' );
	}
	public function purge_leads(): void { $this->guard_action( 'assesscraft_purge_leads' ); global $wpdb; $wpdb->query( 'DELETE FROM ' . self::table() ); $this->redirect( 'purged' ); }
	public function save_settings(): void { $this->guard_action( 'assesscraft_save_lead_settings' ); if ( AssessCraft_Features::available( 'lead_csv_export' ) && isset( $_POST['retention_days'] ) ) { $allowed = array( 0, 30, 90, 180, 365 ); $days = absint( $_POST['retention_days'] ); update_option( self::RETENTION_OPTION, in_array( $days, $allowed, true ) ? $days : 90, false ); } if ( isset( $_POST['uninstall_behavior'] ) ) { $uninstall = sanitize_key( wp_unslash( $_POST['uninstall_behavior'] ) ); update_option( self::UNINSTALL_OPTION, 'delete' === $uninstall ? 'delete' : 'keep', false ); } $this->redirect( 'settings' ); }
	public function cleanup_expired(): void { $days = absint( get_option( self::RETENTION_OPTION, 90 ) ); if ( ! $days ) return; global $wpdb; $cutoff = gmdate( 'Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS ); $wpdb->query( $wpdb->prepare( 'DELETE FROM ' . self::table() . ' WHERE created_at < %s', $cutoff ) ); }

	private function filters(): array { return array( 's' => sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) ), 'assessment' => sanitize_text_field( wp_unslash( $_GET['assessment'] ?? '' ) ), 'profile' => sanitize_text_field( wp_unslash( $_GET['profile'] ?? '' ) ) ); }
	private function where( array $filters, array &$values ): string { global $wpdb; $where = '1=1'; if ( $filters['s'] ) { $like = '%' . $wpdb->esc_like( $filters['s'] ) . '%'; $where .= ' AND (contact_name LIKE %s OR email LIKE %s OR company LIKE %s OR assessment_name LIKE %s OR profile LIKE %s)'; array_push( $values, $like, $like, $like, $like, $like ); } if ( $filters['assessment'] ) { $where .= ' AND assessment_name = %s'; $values[] = $filters['assessment']; } if ( $filters['profile'] ) { $where .= ' AND profile = %s'; $values[] = $filters['profile']; } return $where; }
	private function query( array $filters, int $limit, int $offset ): array { global $wpdb; $values = array(); $where = $this->where( $filters, $values ); $sql = 'SELECT * FROM ' . self::table() . ' WHERE ' . $where . ' ORDER BY created_at DESC, id DESC'; if ( $limit ) { $sql .= ' LIMIT %d OFFSET %d'; array_push( $values, $limit, $offset ); } return (array) $wpdb->get_results( $values ? $wpdb->prepare( $sql, $values ) : $sql ); }
	private function count( array $filters ): int { global $wpdb; $values = array(); $where = $this->where( $filters, $values ); $sql = 'SELECT COUNT(*) FROM ' . self::table() . ' WHERE ' . $where; return (int) $wpdb->get_var( $values ? $wpdb->prepare( $sql, $values ) : $sql ); }
	private function distinct( string $column ): array { global $wpdb; if ( ! in_array( $column, array( 'assessment_name', 'profile' ), true ) ) return array(); return array_filter( array_map( 'strval', (array) $wpdb->get_col( 'SELECT DISTINCT ' . $column . ' FROM ' . self::table() . ' WHERE ' . $column . " <> '' ORDER BY " . $column . ' ASC' ) ) ); }
	private function csv_cell( $value ): string { $value = (string) $value; return preg_match( '/^[=+\-@]/', $value ) ? "'" . $value : $value; }
	public static function table_name(): string { global $wpdb; return $wpdb->prefix . 'assesscraft_leads'; }
	private static function table(): string { return self::table_name(); }
	private function guard_admin(): void { if ( ! current_user_can( 'manage_options' ) ) wp_die( esc_html__( 'You do not have permission to view consultation requests.', 'assesscraft' ) ); }
	private function guard_action( string $nonce ): void { $this->guard_admin(); check_admin_referer( $nonce ); }
	private function redirect( string $notice ): void { wp_safe_redirect( add_query_arg( 'assesscraft_lead_notice', $notice, admin_url( 'edit.php?post_type=' . AssessCraft_Post_Type::TYPE . '&page=assesscraft-leads' ) ) ); exit; }
	private function notice(): void { $key = sanitize_key( wp_unslash( $_GET['assesscraft_lead_notice'] ?? '' ) ); $messages = array( 'deleted' => __( 'Consultation request deleted.', 'assesscraft' ), 'purged' => __( 'All stored consultation requests deleted.', 'assesscraft' ), 'settings' => __( 'Retention settings saved.', 'assesscraft' ) ); if ( isset( $messages[ $key ] ) ) printf( '<div class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html( $messages[ $key ] ) ); }
}
