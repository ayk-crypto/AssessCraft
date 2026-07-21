<?php
defined( 'ABSPATH' ) || exit;

final class AssessCraft_Elementor_Widget extends \Elementor\Widget_Base {
	public function get_name(): string {
		return 'assesscraft-assessment';
	}

	public function get_title(): string {
		return esc_html__( 'AssessCraft Assessment', 'assesscraft' );
	}

	public function get_icon(): string {
		return 'eicon-form-horizontal';
	}

	public function get_categories(): array {
		return array( 'general' );
	}

	public function get_keywords(): array {
		return array( 'assessment', 'quiz', 'score', 'report', 'lead' );
	}

	protected function register_controls(): void {
		$this->start_controls_section( 'content', array( 'label' => esc_html__( 'Assessment', 'assesscraft' ) ) );
		$this->add_control(
			'assessment_id',
			array(
				'label'       => esc_html__( 'Select assessment', 'assesscraft' ),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'options'     => $this->assessment_options(),
				'default'     => '',
				'label_block' => true,
			)
		);
		$this->end_controls_section();
	}

	protected function render(): void {
		$settings = $this->get_settings_for_display();
		$id = absint( $settings['assessment_id'] ?? 0 );
		if ( ! $id ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<p>' . esc_html__( 'Choose an assessment from the widget settings.', 'assesscraft' ) . '</p>';
			}
			return;
		}
		echo do_shortcode( '[assesscraft id="' . $id . '"]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	private function assessment_options(): array {
		$posts = get_posts(
			array(
				'post_type'      => AssessCraft_Post_Type::TYPE,
				'post_status'    => array( 'publish', 'draft' ),
				'posts_per_page' => 200,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);
		$options = array( '' => esc_html__( 'Select an assessment', 'assesscraft' ) );
		foreach ( $posts as $post ) {
			$options[ $post->ID ] = $post->post_title . ( 'draft' === $post->post_status ? ' — ' . esc_html__( 'Draft', 'assesscraft' ) : '' );
		}
		return $options;
	}
}

