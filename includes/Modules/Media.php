<?php
/** Local media replacement with staged files, backups and rollback. @package CoreExtras */

namespace CoreExtras\Modules;

use CoreExtras\Guard;

final class Media {
	const TYPES = array( 'jpg|jpeg|jpe' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp', 'avif' => 'image/avif' );

	public function __construct( $settings ) {}

	public function register() {
		add_action( 'add_meta_boxes_attachment', array( $this, 'metabox' ) );
		add_action( 'admin_post_core_extras_replace', array( $this, 'handle' ) );
		add_action( 'admin_footer-post.php', array( $this, 'form' ) );
		add_action( 'admin_notices', array( $this, 'notice' ) );
	}

	public function metabox( $post ) {
		if ( current_user_can( 'upload_files' ) && current_user_can( 'edit_post', $post->ID ) ) {
			add_meta_box( 'core-extras-replace', __( 'Replace file', 'core-extras' ), array( $this, 'box' ), 'attachment', 'side' );
		}
	}

	public function box( $post ) {
		echo '<p>' . esc_html__( 'Replace this JPEG, PNG, GIF, WebP or AVIF with the same file type and extension. The attachment ID and public URL stay the same. Image dimensions may change; cached copies may take time to refresh.', 'core-extras' ) . '</p>';
		echo '<p><label for="core-extras-file">' . esc_html__( 'Replacement file', 'core-extras' ) . '</label><br><input id="core-extras-file" form="core-extras-replace-form" type="file" name="replacement" required accept="image/jpeg,image/png,image/gif,image/webp,image/avif"></p>';
		echo '<p><button type="submit" class="button" form="core-extras-replace-form">' . esc_html__( 'Replace file', 'core-extras' ) . '</button></p>';
	}

	/** Keep the upload separate from WordPress’s existing attachment edit form. */
	public function form() {
		$post = get_post();
		if ( ! $post || 'attachment' !== $post->post_type || ! current_user_can( 'edit_post', $post->ID ) || ! current_user_can( 'upload_files' ) ) {
			return;
		}
		echo '<form hidden id="core-extras-replace-form" method="post" enctype="multipart/form-data" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		echo '<input type="hidden" name="action" value="core_extras_replace"><input type="hidden" name="attachment_id" value="' . esc_attr( $post->ID ) . '">';
		Guard::nonce( 'core_extras_replace_' . $post->ID );
		echo '</form>';
	}

	public function notice() {
		$screen = get_current_screen();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only notice flag; no state change.
		if ( $screen && 'attachment' === $screen->id && isset( $_GET['core_extras_replaced'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'File replaced. Image dimensions may have changed. Cached copies may take time to refresh.', 'core-extras' ) . '</p></div>';
		}
	}

	/** Validate bytes and extension with both WordPress and independent content checks. */
	public static function validate_file( $file, $target, $mime ) {
		if ( ! is_array( $file ) || ! isset( $file['tmp_name'], $file['name'], $file['error'] ) || UPLOAD_ERR_OK !== $file['error'] || ! is_string( $file['tmp_name'] ) || ! is_file( $file['tmp_name'] ) ) {
			return self::error( 'upload' );
		}
		if ( ! is_string( $file['name'] ) || strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) ) !== strtolower( pathinfo( $target, PATHINFO_EXTENSION ) ) || filesize( $file['tmp_name'] ) < 1 || filesize( $file['tmp_name'] ) > wp_max_upload_size() ) {
			return self::error( 'type' );
		}
		$allowed = array_intersect( self::TYPES, get_allowed_mime_types() );
		$checked = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'], $allowed );
		if ( ! $checked['ext'] || $checked['type'] !== $mime || ! empty( $checked['proper_filename'] ) || ! class_exists( 'finfo' ) ) {
			return self::error( 'type' );
		}
		$info = wp_getimagesize( $file['tmp_name'] );
		if ( ! $info || ( $info['mime'] ?? '' ) !== $mime || ( new \finfo( FILEINFO_MIME_TYPE ) )->file( $file['tmp_name'] ) !== $mime ) {
			return self::error( 'type' );
		}
		// Require a decoder as well as a plausible image header; damaged images fail closed.
		$editor = wp_get_image_editor( $file['tmp_name'] );
		return is_wp_error( $editor ) ? self::error( 'type' ) : true;
	}

	private static function error( $code = 'replace' ) {
		$messages = array(
			'upload' => __( 'No valid replacement upload was received.', 'core-extras' ),
			'type' => __( 'Use a valid supported image with exactly the original MIME type and extension, within the upload size limit.', 'core-extras' ),
			'permission' => __( 'You cannot replace this attachment.', 'core-extras' ),
			'path' => __( 'Replacement requires writable local files inside this site’s uploads directory. Remote storage and symbolic links are not supported.', 'core-extras' ),
			'busy' => __( 'This attachment is already being replaced. Try again shortly.', 'core-extras' ),
			'replace' => __( 'Replacement failed. The original file and attachment data were retained.', 'core-extras' ),
		);
		return new \WP_Error( 'core_extras_media_' . $code, $messages[ $code ] );
	}

	/** Isolate all new files until metadata generation has succeeded. */
	public function replace( $id, $file ) {
		$post = get_post( $id );
		if ( ! $post || 'attachment' !== $post->post_type || ! current_user_can( 'upload_files' ) || ! current_user_can( 'edit_post', $id ) ) {
			return self::error( 'permission' );
		}
		$target = get_attached_file( $id, true );
		$uploads = wp_get_upload_dir();
		$base = realpath( $uploads['basedir'] );
		$real = $target ? realpath( $target ) : false;
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_writable -- Local upload transaction requires actual OS write permissions; remote filesystems are unsupported.
		if ( ! $base || ! $real || is_link( $target ) || wp_normalize_path( $target ) !== wp_normalize_path( $real ) || ! str_starts_with( wp_normalize_path( $real ), trailingslashit( wp_normalize_path( $base ) ) ) || ! is_writable( $target ) || ! is_writable( dirname( $target ) ) ) {
			return self::error( 'path' );
		}
		$valid = self::validate_file( $file, $target, $post->post_mime_type );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}
		if ( ! is_uploaded_file( $file['tmp_name'] ) ) {
			return self::error( 'upload' );
		}
		return $this->transaction( $id, $file['tmp_name'], $target );
	}

	/** Transaction engine also accepts validated local fixtures in the test harness.
	 * Local filesystem rename is the commit boundary. Existing derivatives stay
	 * in place until every new file is ready. Every overwritten file has a backup.
	 */
	public function transaction( $id, $source, $target ) {
		$lock_key = 'site_staples_media_lock_' . $id;
		if ( ! add_option( $lock_key, time(), '', false ) ) {
			return self::error( 'busy' );
		}
		$stage = dirname( $target ) . '/.core-extras-' . wp_generate_password( 24, false );
		$had_meta = metadata_exists( 'post', $id, '_wp_attachment_metadata' );
		$meta = get_post_meta( $id, '_wp_attachment_metadata', true );
		$backup_sizes = get_post_meta( $id, '_wp_attachment_backup_sizes', true );
		$committed = array();
		$backups = array();
		$success = false;
		try {
			if ( ! wp_mkdir_p( $stage ) || ! copy( $source, $stage . '/' . basename( $target ) ) ) {
				throw new \RuntimeException();
			}
			$new_meta = $this->generate( $stage . '/' . basename( $target ), $id );
			if ( is_wp_error( $new_meta ) || empty( $new_meta['width'] ) || empty( $new_meta['height'] ) ) {
				throw new \RuntimeException();
			}
			foreach ( glob( $stage . '/*' ) as $path ) {
				$destination = dirname( $target ) . '/' . basename( $path );
				if ( ! is_file( $path ) || is_link( $path ) ) {
					throw new \RuntimeException();
				}
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_writable -- Local upload transaction requires actual OS write permissions; remote filesystems are unsupported.
				if ( is_link( $destination ) || ( file_exists( $destination ) && ! is_writable( $destination ) ) ) {
					throw new \RuntimeException();
				}
				if ( file_exists( $destination ) ) {
					$backup = $stage . '/backup-' . basename( $path );
					if ( ! copy( $destination, $backup ) ) {
						throw new \RuntimeException();
					}
					$backups[ $destination ] = $backup;
				}
				// phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename -- Atomic local rename is required for the commit and rollback boundary.
				if ( ! rename( $path, $destination ) ) {
					throw new \RuntimeException();
				}
				$committed[] = $destination;
			}
			// All paths in final metadata refer to the unchanged attachment location.
			$new_meta['file'] = _wp_relative_upload_path( $target );
			wp_update_attachment_metadata( $id, $new_meta );
			if ( get_post_meta( $id, '_wp_attachment_metadata', true ) !== $new_meta ) {
				throw new \RuntimeException();
			}
			delete_post_meta( $id, '_wp_attachment_backup_sizes' );
			$success = true;
		} catch ( \Throwable $exception ) {
			foreach ( array_reverse( $committed ) as $destination ) {
				if ( isset( $backups[ $destination ] ) ) {
					// phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename -- Atomic local rename is required for the commit and rollback boundary.
					rename( $backups[ $destination ], $destination );
				} else {
					wp_delete_file( $destination );
				}
			}
			if ( $had_meta ) {
				update_post_meta( $id, '_wp_attachment_metadata', $meta );
			} else {
				delete_post_meta( $id, '_wp_attachment_metadata' );
			}
			if ( $backup_sizes ) {
				update_post_meta( $id, '_wp_attachment_backup_sizes', $backup_sizes );
			}
		} finally {
			foreach ( glob( $stage . '/*' ) ?: array() as $path ) {
				wp_delete_file( $path );
			}
			if ( is_dir( $stage ) ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- Remove only the empty local staging directory created by this transaction.
				rmdir( $stage );
			}
			delete_option( $lock_key );
		}
		if ( $success ) {
			// Remove obsolete derivatives only after the commit and only from this directory.
			$new_files = array_map( static function ( $size ) { return $size['file']; }, $new_meta['sizes'] ?? array() );
			foreach ( $meta['sizes'] ?? array() as $size ) {
				$name = $size['file'] ?? '';
				if ( $name && basename( $name ) === $name && ! in_array( $name, $new_files, true ) && $name !== basename( $target ) ) {
					wp_delete_file( dirname( $target ) . '/' . $name );
				}
			}
		}
		return $success ? true : self::error();
	}

	/** Native image editor generates registered sizes without updating live metadata. */
	private function generate( $path, $id ) {
		$editor = wp_get_image_editor( $path );
		if ( is_wp_error( $editor ) ) {
			return $editor;
		}
		$dimensions = $editor->get_size();
		$sizes = wp_get_registered_image_subsizes();
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Intentional WordPress core hook so existing image-size filters also apply to replacements.
		$sizes = apply_filters( 'intermediate_image_sizes_advanced', $sizes, array( 'width' => $dimensions['width'], 'height' => $dimensions['height'] ), $id );
		$generated = $editor->multi_resize( $sizes );
		foreach ( $sizes as $name => $size ) {
			$needed = image_resize_dimensions( $dimensions['width'], $dimensions['height'], $size['width'], $size['height'], $size['crop'] );
			if ( $needed && empty( $generated[ $name ] ) ) {
				return self::error();
			}
		}
		return array(
			'width' => $dimensions['width'],
			'height' => $dimensions['height'],
			'file' => _wp_relative_upload_path( $path ),
			'filesize' => filesize( $path ),
			'sizes' => $generated,
			'image_meta' => wp_read_image_metadata( $path ) ?: array(),
		);
	}

	public function handle() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- ID scopes the nonce verified by Guard::post() immediately below; no mutation before verification.
		$id = isset( $_POST['attachment_id'] ) ? absint( $_POST['attachment_id'] ) : 0;
		Guard::post( 'core_extras_replace_' . $id, 'upload_files' );
		require_once ABSPATH . 'wp-admin/includes/image.php';
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Missing -- Guard::post() verifies the nonce above; validated as bytes and MIME by replace().
		Guard::result( $this->replace( $id, $_FILES['replacement'] ?? array() ) );
		wp_safe_redirect( add_query_arg( 'core_extras_replaced', '1', get_edit_post_link( $id, 'raw' ) ) );
		exit;
	}
}
