<?php
/** A canvas for the selected maintenance page, without theme navigation or footer. @package CoreExtras */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Render before wp_head so blocks and builders can enqueue their required assets.
ob_start();
while ( have_posts() ) {
	the_post();
	the_content();
}
$core_extras_maintenance_content = ob_get_clean();
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
	<style>body.core-extras-maintenance-canvas { margin: 0; min-height: 100vh; }</style>
</head>
<body <?php body_class( array( 'core-extras-maintenance', 'core-extras-maintenance-canvas' ) ); ?>>
<?php wp_body_open(); ?>
<main id="core-extras-maintenance-content" class="wp-site-blocks">
	<div class="entry-content">
		<?php
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WordPress rendered this published page through the normal the_content filters above.
		echo $core_extras_maintenance_content;
		?>
	</div>
</main>
<?php wp_footer(); ?>
</body>
</html>
