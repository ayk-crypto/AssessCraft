<?php
defined( 'ABSPATH' ) || exit;

final class AssessCraft_Admin {
	public function register(): void {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_' . AssessCraft_Post_Type::TYPE, array( $this, 'save' ) );
		add_filter( 'manage_' . AssessCraft_Post_Type::TYPE . '_posts_columns', array( $this, 'columns' ) );
		add_action( 'manage_' . AssessCraft_Post_Type::TYPE . '_posts_custom_column', array( $this, 'column_content' ), 10, 2 );
	}

	public function add_meta_boxes(): void {
		add_meta_box( 'assesscraft-builder', __( 'Assessment Configuration', 'assesscraft' ), array( $this, 'render_builder' ), AssessCraft_Post_Type::TYPE, 'normal', 'high' );
		add_meta_box( 'assesscraft-publish', __( 'Publish', 'assesscraft' ), array( $this, 'render_publish' ), AssessCraft_Post_Type::TYPE, 'side' );
	}

	public function render_builder( WP_Post $post ): void {
		$config = get_post_meta( $post->ID, '_assesscraft_config', true );
		$config = AssessCraft_Schema::sanitize( is_array( $config ) ? $config : array() );
		wp_nonce_field( 'assesscraft_save', 'assesscraft_nonce' );
		?>
		<div class="assesscraft-admin-shell">
			<p><strong><?php esc_html_e( 'Builder foundation installed.', 'assesscraft' ); ?></strong></p>
			<p><?php esc_html_e( 'The visual Overview, Builder, Scoring, Profiles, Report, Lead Form, Design, and Publish tabs will be mounted here.', 'assesscraft' ); ?></p>
			<label for="assesscraft-heading"><strong><?php esc_html_e( 'Frontend heading', 'assesscraft' ); ?></strong></label>
			<input class="widefat" id="assesscraft-heading" name="assesscraft_heading" value="<?php echo esc_attr( $config['overview']['heading'] ); ?>">
			<p><label for="assesscraft-description"><strong><?php esc_html_e( 'Introduction', 'assesscraft' ); ?></strong></label></p>
			<textarea class="widefat" rows="5" id="assesscraft-description" name="assesscraft_description"><?php echo esc_textarea( $config['overview']['description'] ); ?></textarea>
		</div>
		<?php
	}

	public function render_publish( WP_Post $post ): void {
		echo '<p>' . esc_html__( 'Place this assessment using:', 'assesscraft' ) . '</p>';
		echo '<code>[assesscraft id=&quot;' . absint( $post->ID ) . '&quot;]</code>';
	}

	public function save( int $post_id ): void {
		if ( ! isset( $_POST['assesscraft_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['assesscraft_nonce'] ) ), 'assesscraft_save' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$config = get_post_meta( $post_id, '_assesscraft_config', true );
		$config = AssessCraft_Schema::sanitize( is_array( $config ) ? $config : array() );
		$config['overview']['heading'] = isset( $_POST['assesscraft_heading'] ) ? sanitize_text_field( wp_unslash( $_POST['assesscraft_heading'] ) ) : '';
		$config['overview']['description'] = isset( $_POST['assesscraft_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['assesscraft_description'] ) ) : '';
		update_post_meta( $post_id, '_assesscraft_config', $config );
	}

	public function columns( array $columns ): array {
		$columns['assesscraft_shortcode'] = __( 'Shortcode', 'assesscraft' );
		return $columns;
	}

	public function column_content( string $column, int $post_id ): void {
		if ( 'assesscraft_shortcode' === $column ) {
			echo '<code>[assesscraft id=&quot;' . absint( $post_id ) . '&quot;]</code>';
		}
	}
}

