<?php
defined( 'ABSPATH' ) || exit;

final class AssessCraft_Lead_Endpoint {
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'register_route' ) );
	}

	public function register_route(): void {
		register_rest_route(
			'assesscraft/v1',
			'/lead',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'submit' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	public function submit( WP_REST_Request $request ) {
		$assessment_id = absint( $request->get_param( 'assessment_id' ) );
		if ( ! $assessment_id || AssessCraft_Post_Type::TYPE !== get_post_type( $assessment_id ) || 'publish' !== get_post_status( $assessment_id ) ) {
			return new WP_Error( 'assesscraft_invalid_assessment', __( 'This assessment is not available.', 'assesscraft' ), array( 'status' => 404 ) );
		}

		$config = get_post_meta( $assessment_id, '_assesscraft_config', true );
		$config = AssessCraft_Schema::sanitize( is_array( $config ) ? $config : array() );
		if ( empty( $config['lead_form']['enabled'] ) ) {
			return new WP_Error( 'assesscraft_lead_disabled', __( 'Consultation requests are not enabled for this assessment.', 'assesscraft' ), array( 'status' => 403 ) );
		}
		if ( $request->get_param( 'website' ) ) {
			return rest_ensure_response( array( 'success' => true ) );
		}
		if ( ! filter_var( $request->get_param( 'consent' ), FILTER_VALIDATE_BOOLEAN ) ) {
			return new WP_Error( 'assesscraft_consent_required', __( 'Please provide consent before submitting.', 'assesscraft' ), array( 'status' => 400 ) );
		}
		if ( $this->is_rate_limited() ) {
			return new WP_Error( 'assesscraft_rate_limited', __( 'Too many requests were submitted. Please try again later.', 'assesscraft' ), array( 'status' => 429 ) );
		}

		$name    = substr( sanitize_text_field( (string) $request->get_param( 'name' ) ), 0, 120 );
		$email   = sanitize_email( (string) $request->get_param( 'email' ) );
		$company = substr( sanitize_text_field( (string) $request->get_param( 'company' ) ), 0, 160 );
		$phone   = substr( sanitize_text_field( (string) $request->get_param( 'phone' ) ), 0, 60 );
		$message = substr( sanitize_textarea_field( (string) $request->get_param( 'message' ) ), 0, 3000 );
		if ( ! $name || ! is_email( $email ) ) {
			return new WP_Error( 'assesscraft_invalid_contact', __( 'Please provide your name and a valid email address.', 'assesscraft' ), array( 'status' => 400 ) );
		}

		$responses = is_array( $request->get_param( 'responses' ) ) ? $request->get_param( 'responses' ) : array();
		$result    = AssessCraft_Scoring::calculate( $config, $responses );
		if ( empty( $result['valid'] ) ) {
			return new WP_Error( 'assesscraft_incomplete_result', __( 'The assessment result could not be verified. Please complete all required questions.', 'assesscraft' ), array( 'status' => 400 ) );
		}
		$recipient = sanitize_email( $config['lead_form']['recipient'] ?: get_option( 'admin_email' ) );
		$subject   = sanitize_text_field( $config['lead_form']['subject'] ?: __( 'New AssessCraft consultation request', 'assesscraft' ) );
		$body      = $this->build_email( $assessment_id, compact( 'name', 'email', 'company', 'phone', 'message' ), $result );
		$headers   = array( 'Content-Type: text/plain; charset=UTF-8', 'Reply-To: ' . $name . ' <' . $email . '>' );

		if ( ! $recipient || ! wp_mail( $recipient, $subject, $body, $headers ) ) {
			return new WP_Error( 'assesscraft_mail_failed', __( 'The request could not be sent. Please try again or contact the website directly.', 'assesscraft' ), array( 'status' => 500 ) );
		}
		if ( ! empty( $config['lead_form']['store_responses'] ) ) {
			AssessCraft_Lead_Store::store( $assessment_id, compact( 'name', 'email', 'company', 'phone', 'message' ), $result );
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'message' => sanitize_text_field( $config['lead_form']['success_message'] ),
			)
		);
	}

	private function build_email( int $assessment_id, array $contact, array $result ): string {
		$lines = array(
			'New AssessCraft consultation request',
			'Assessment: ' . get_the_title( $assessment_id ) . ' (#' . $assessment_id . ')',
			'Date: ' . current_time( 'mysql' ),
			'',
			'CONTACT',
			'Name: ' . $contact['name'],
			'Email: ' . $contact['email'],
			'Company: ' . $contact['company'],
			'Phone: ' . $contact['phone'],
			'Message: ' . $contact['message'],
			'',
			'ASSESSMENT SUMMARY',
			'Overall score: ' . round( (float) ( $result['overall'] ?? 0 ) ) . '%',
			'Classification: ' . sanitize_text_field( $result['classification'] ?? '' ),
			'Profile: ' . sanitize_text_field( $result['profile'] ?? '' ),
		);
		$stages = is_array( $result['stages'] ?? null ) ? array_slice( $result['stages'], 0, 50 ) : array();
		foreach ( $stages as $stage ) {
			if ( is_array( $stage ) ) {
				$lines[] = sanitize_text_field( $stage['name'] ?? 'Stage' ) . ': ' . round( (float) ( $stage['score'] ?? 0 ) ) . '%';
			}
		}
		$lines[] = '';
		$lines[] = 'Consent to share contact details and assessment results: Yes';
		return implode( "\n", $lines );
	}

	private function is_rate_limited(): bool {
		$ip  = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? 'unknown' ) );
		$key = 'assesscraft_lead_' . hash( 'sha256', $ip );
		$count = (int) get_transient( $key );
		if ( $count >= 5 ) {
			return true;
		}
		set_transient( $key, $count + 1, 15 * MINUTE_IN_SECONDS );
		return false;
	}
}
