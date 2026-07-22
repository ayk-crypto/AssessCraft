<?php
define( 'ABSPATH', __DIR__ . '/' );
function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) ); }
function sanitize_text_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function sanitize_textarea_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function sanitize_hex_color( $value ) { return preg_match( '/^#[0-9a-f]{6}$/i', (string) $value ) ? $value : null; }
function __( $value ) { return $value; }
function apply_filters( $hook, $value ) {
	if ( 'assesscraft_current_plan' === $hook && isset( $GLOBALS['assesscraft_test_plan'] ) ) {
		return $GLOBALS['assesscraft_test_plan'];
	}
	if ( 'assesscraft_commercial_enforcement' === $hook ) {
		return true;
	}
	return $value;
}
function admin_url( $path = '' ) { return 'https://example.test/wp-admin/' . ltrim( $path, '/' ); }
require_once dirname( __DIR__ ) . '/includes/class-assesscraft-schema.php';
require_once dirname( __DIR__ ) . '/includes/class-assesscraft-scoring.php';
require_once dirname( __DIR__ ) . '/includes/class-assesscraft-features.php';
require_once dirname( __DIR__ ) . '/includes/class-assesscraft-entitlements.php';
