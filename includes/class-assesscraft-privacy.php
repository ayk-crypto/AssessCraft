<?php
defined( 'ABSPATH' ) || exit;

final class AssessCraft_Privacy {
	public function register(): void {
		add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'register_exporter' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'register_eraser' ) );
		add_action( 'admin_init', array( $this, 'add_policy_content' ) );
	}

	public function register_exporter( array $exporters ): array {
		$exporters['assesscraft-consultation-requests'] = array(
			'exporter_friendly_name' => __( 'AssessCraft consultation requests', 'assesscraft' ),
			'callback'               => array( $this, 'export' ),
		);
		return $exporters;
	}

	public function register_eraser( array $erasers ): array {
		$erasers['assesscraft-consultation-requests'] = array(
			'eraser_friendly_name' => __( 'AssessCraft consultation requests', 'assesscraft' ),
			'callback'             => array( $this, 'erase' ),
		);
		return $erasers;
	}

	public function export( string $email_address, int $page = 1 ): array {
		global $wpdb;
		$table = AssessCraft_Lead_Store::table_name();
		$limit = 50;
		$rows = $wpdb->get_results(
			// The table name comes from the plugin's own fixed table-name method.
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->prepare( "SELECT * FROM {$table} WHERE email = %s ORDER BY id ASC LIMIT %d OFFSET %d", sanitize_email( $email_address ), $limit, ( max( 1, $page ) - 1 ) * $limit ),
			ARRAY_A
		);
		$data = array();
		foreach ( (array) $rows as $row ) {
			$data[] = array(
				'group_id'    => 'assesscraft-consultation-requests',
				'group_label' => __( 'AssessCraft consultation requests', 'assesscraft' ),
				'item_id'     => 'assesscraft-lead-' . absint( $row['id'] ?? 0 ),
				'data'        => $this->export_fields( $row ),
			);
		}
		return array( 'data' => $data, 'done' => count( $rows ) < $limit );
	}

	public function erase( string $email_address, int $page = 1 ): array {
		global $wpdb;
		$email = sanitize_email( $email_address );
		// The table name comes from the plugin's own fixed table-name method.
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$count = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . AssessCraft_Lead_Store::table_name() . ' WHERE email = %s', $email ) );
		$deleted = $count ? $wpdb->delete( AssessCraft_Lead_Store::table_name(), array( 'email' => $email ), array( '%s' ) ) : 0;
		return array(
			'items_removed'  => (bool) $deleted,
			'items_retained' => false,
			'messages'       => $count && false === $deleted ? array( __( 'AssessCraft could not erase the matching consultation requests.', 'assesscraft' ) ) : array(),
			'done'           => true,
		);
	}

	public function add_policy_content(): void {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}
		$content = '<p>' . esc_html__( 'AssessCraft can optionally store consultation requests in this website’s WordPress database. Storage is disabled by default for each assessment. Stored records can include contact details, assessment name, overall score, classification, profile, consent time, and submission time. Individual question answers are not stored in the consultation-request dashboard. Website administrators can configure retention, export or erase records through WordPress privacy tools, and delete stored requests from AssessCraft.', 'assesscraft' ) . '</p>';
		wp_add_privacy_policy_content( 'AssessCraft', wp_kses_post( wpautop( $content ) ) );
	}

	private function export_fields( array $row ): array {
		$labels = array(
			'assessment_name' => __( 'Assessment', 'assesscraft' ), 'contact_name' => __( 'Name', 'assesscraft' ),
			'email' => __( 'Email', 'assesscraft' ), 'company' => __( 'Company', 'assesscraft' ), 'phone' => __( 'Phone', 'assesscraft' ),
			'message' => __( 'Message', 'assesscraft' ), 'overall_score' => __( 'Overall score', 'assesscraft' ),
			'classification' => __( 'Classification', 'assesscraft' ), 'profile' => __( 'Profile', 'assesscraft' ),
			'consent_at' => __( 'Consent recorded', 'assesscraft' ), 'source_url' => __( 'Source URL', 'assesscraft' ), 'created_at' => __( 'Submitted', 'assesscraft' ),
		);
		$data = array();
		foreach ( $labels as $key => $label ) {
			if ( isset( $row[ $key ] ) && '' !== (string) $row[ $key ] ) {
				$data[] = array( 'name' => $label, 'value' => (string) $row[ $key ] );
			}
		}
		return $data;
	}
}
