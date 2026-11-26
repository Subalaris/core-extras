<?php
/** Optional, unsortable content information columns with native thumbnails. @package CoreExtras */

namespace CoreExtras\Modules;

final class Columns {
	private $settings;

	public function __construct( $settings ) {
		$this->settings = $settings;
	}

	public function register() {
		add_action( 'init', array( $this, 'types' ), 100 );
	}

	public function types() {
		foreach ( $this->settings['post_types'] as $type ) {
			if ( ! post_type_exists( $type ) ) {
				continue;
			}
			add_filter( 'manage_' . $type . '_posts_columns', array( $this, 'columns' ) );
			add_action( 'manage_' . $type . '_posts_custom_column', array( $this, 'cell' ), 10, 2 );
		}
	}

	public function columns( $columns ) {
		if ( $this->settings['show_id'] ) {
			$columns['core_extras_id'] = __( 'ID', 'core-extras' );
		}
		if ( $this->settings['show_thumbnail'] ) {
			$columns['core_extras_thumbnail'] = __( 'Featured image', 'core-extras' );
		}
		if ( $this->settings['show_modified'] ) {
			$columns['core_extras_modified'] = __( 'Last modified', 'core-extras' );
		}
		return $columns;
	}

	public function cell( $column, $id ) {
		if ( ! current_user_can( 'read_post', $id ) ) {
			return;
		}
		switch ( $column ) {
			case 'core_extras_id':
				if ( $this->settings['show_id'] ) {
					echo esc_html( $id );
				}
				break;
			case 'core_extras_thumbnail':
				if ( $this->settings['show_thumbnail'] ) {
					$image = get_the_post_thumbnail( $id, array( 50, 50 ), array( 'alt' => '', 'loading' => 'lazy' ) );
					echo $image ?: esc_html__( 'No featured image', 'core-extras' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core-generated image markup.
				}
				break;
			case 'core_extras_modified':
				if ( $this->settings['show_modified'] ) {
					echo esc_html( get_post_modified_time( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), false, $id, true ) );
				}
				break;
		}
	}
}
