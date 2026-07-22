<?php
defined( 'ABSPATH' ) || exit;

final class AssessCraft_Entitlements {
	private const NOTICE_KEY = 'assesscraft_entitlement_notice_';

	public function register(): void {
		add_filter( 'wp_insert_post_data', array( $this, 'enforce_publish_limit' ), 20, 2 );
		add_action( 'admin_notices', array( $this, 'render_notice' ) );
	}

	public function enforce_publish_limit( array $data, array $postarr ): array {
		if ( AssessCraft_Post_Type::TYPE !== ( $data['post_type'] ?? '' ) || 'publish' !== ( $data['post_status'] ?? '' ) ) {
			return $data;
		}

		$limit = AssessCraft_Features::limit( 'published_assessments' );
		if ( $limit < 0 ) {
			return $data;
		}

		$post_id = absint( $postarr['ID'] ?? 0 );
		if ( $post_id && 'publish' === get_post_status( $post_id ) ) {
			return $data;
		}

		$published = get_posts(
			array(
				'post_type'      => AssessCraft_Post_Type::TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => $limit + 1,
				'fields'         => 'ids',
				'post__not_in'   => $post_id ? array( $post_id ) : array(),
			)
		);

		if ( count( $published ) >= $limit ) {
			$data['post_status'] = 'draft';
			set_transient( self::NOTICE_KEY . get_current_user_id(), 'publish-limit', MINUTE_IN_SECONDS );
		}

		return $data;
	}

	public function render_notice(): void {
		$key    = self::NOTICE_KEY . get_current_user_id();
		$notice = get_transient( $key );
		if ( ! $notice ) {
			return;
		}
		delete_transient( $key );
		printf(
			'<div class="notice notice-warning is-dismissible"><p>%1$s <a href="%2$s">%3$s</a></p></div>',
			esc_html__( 'The Free plan supports one published assessment. This assessment was saved as a draft.', 'assesscraft' ),
			esc_url( AssessCraft_Features::upgrade_url() ),
			esc_html__( 'Upgrade to publish unlimited assessments.', 'assesscraft' )
		);
	}

	public static function preserve_restricted_config( array $current, array $posted ): array {
		if ( ! AssessCraft_Features::available( 'weighted_scoring' ) ) {
			$current_stages = array_column( $current['stages'] ?? array(), null, 'id' );
			foreach ( $posted['stages'] ?? array() as &$stage ) {
				$old_stage       = $current_stages[ $stage['id'] ?? '' ] ?? array();
				$stage['weight'] = $old_stage['weight'] ?? 1;
				$current_questions = array_column( $old_stage['questions'] ?? array(), null, 'id' );
				foreach ( $stage['questions'] ?? array() as &$question ) {
					$old_question       = $current_questions[ $question['id'] ?? '' ] ?? array();
					$question['reverse'] = ! empty( $old_question['reverse'] );
				}
				unset( $question );
			}
			unset( $stage );
		}

		$profile_limit = AssessCraft_Features::limit( 'profiles' );
		if ( $profile_limit >= 0 ) {
			$editable = array_slice( $posted['profiles'] ?? array(), 0, $profile_limit );
			$locked   = array_slice( $current['profiles'] ?? array(), $profile_limit );
			$posted['profiles'] = array_merge( $editable, $locked );
		}

		if ( ! AssessCraft_Features::available( 'consultation_email' ) ) {
			foreach ( array( 'send_results', 'recipient', 'subject' ) as $key ) {
				$posted['lead_form'][ $key ] = $current['lead_form'][ $key ] ?? null;
			}
		}

		if ( ! AssessCraft_Features::available( 'advanced_design' ) ) {
			foreach ( array( 'background', 'surface', 'text', 'muted', 'button_text', 'font', 'radius', 'width' ) as $key ) {
				$posted['design'][ $key ] = $current['design'][ $key ] ?? null;
			}
		}

		return $posted;
	}
}
