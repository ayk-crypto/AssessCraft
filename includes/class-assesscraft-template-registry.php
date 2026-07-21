<?php
defined( 'ABSPATH' ) || exit;

final class AssessCraft_Template_Registry {
	public static function all(): array {
		$directories = apply_filters( 'assesscraft_template_directories', array( ASSESSCRAFT_DIR . 'templates' ) );
		$templates   = array();

		foreach ( array_unique( array_filter( array_map( 'strval', (array) $directories ) ) ) as $directory ) {
			$path = realpath( $directory );
			if ( ! $path || ! is_dir( $path ) || ! is_readable( $path ) ) {
				continue;
			}
			foreach ( glob( trailingslashit( $path ) . '*.json' ) ?: array() as $file ) {
				$template = self::load_file( $file );
				if ( $template ) {
					$templates[ $template['slug'] ] = $template;
				}
			}
		}

		ksort( $templates );
		return apply_filters( 'assesscraft_templates', $templates );
	}

	public static function get( string $slug ): ?array {
		$templates = self::all();
		return $templates[ sanitize_key( $slug ) ] ?? null;
	}

	private static function load_file( string $file ): ?array {
		if ( ! is_readable( $file ) || filesize( $file ) > 2 * MB_IN_BYTES ) {
			return null;
		}
		$contents = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$data     = json_decode( (string) $contents, true );
		if ( ! is_array( $data ) || 1 !== (int) ( $data['assesscraft_template'] ?? 0 ) || ! is_array( $data['config'] ?? null ) ) {
			return null;
		}
		$slug = sanitize_key( $data['slug'] ?? pathinfo( $file, PATHINFO_FILENAME ) );
		$name = sanitize_text_field( $data['name'] ?? '' );
		if ( ! $slug || ! $name ) {
			return null;
		}
		$config = self::hydrate_config( $data['config'], is_array( $data['scales'] ?? null ) ? $data['scales'] : array() );
		return array(
			'slug'        => $slug,
			'name'        => $name,
			'description' => sanitize_text_field( $data['description'] ?? '' ),
			'category'    => sanitize_text_field( $data['category'] ?? __( 'General', 'assesscraft' ) ),
			'version'     => sanitize_text_field( $data['version'] ?? '1.0.0' ),
			'config'      => AssessCraft_Schema::sanitize( $config ),
		);
	}

	private static function hydrate_config( array $config, array $scales ): array {
		foreach ( $config['stages'] ?? array() as $stage_index => $stage ) {
			foreach ( $stage['questions'] ?? array() as $question_index => $question ) {
				if ( ! empty( $question['answers'] ) || empty( $question['scale'] ) || ! is_array( $scales[ $question['scale'] ] ?? null ) ) {
					continue;
				}
				$answers = array();
				foreach ( $scales[ $question['scale'] ] as $answer_index => $answer ) {
					if ( is_array( $answer ) ) {
						$answers[] = array(
							'id'    => sanitize_key( ( $question['id'] ?? 'question' ) . '_a' . ( $answer_index + 1 ) ),
							'label' => sanitize_text_field( $answer['label'] ?? '' ),
							'score' => is_numeric( $answer['score'] ?? null ) ? (float) $answer['score'] : 0,
						);
					}
				}
				$config['stages'][ $stage_index ]['questions'][ $question_index ]['answers'] = $answers;
			}
		}
		return $config;
	}
}
