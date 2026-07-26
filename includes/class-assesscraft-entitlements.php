<?php
defined( 'ABSPATH' ) || exit;

final class AssessCraft_Entitlements {
	public function register(): void {
		// Feature entitlements are enforced while assessment publishing remains unlimited.
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
