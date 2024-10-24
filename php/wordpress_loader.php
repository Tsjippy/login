<?php
namespace SIM\LOGIN;
use SIM;

function loadWordpress($title){

    ob_start();
    define( 'WP_USE_THEMES', false ); // Do not use the theme files
    define( 'DISABLE_WP_CRON', true );

    require(__DIR__."/../../../../wp-load.php");
    require_once ABSPATH . WPINC . '/functions.php';

    $discard = ob_get_clean();

    require_once ABSPATH . WPINC . '/template-loader.php';

    do_action( 'wp_enqueue_scripts');
    do_action('wp_enqueue_style');

    ?>
    <!DOCTYPE html>
    <html <?php language_attributes(); ?>>
        <head>
            <meta http-equiv="Content-Type" content="<?php bloginfo( 'html_type' ); ?>; charset=<?php bloginfo( 'charset' ); ?>" />
            <title><?php echo $title;?></title>
            <link rel="icon" type="image/x-icon" href="<?php echo get_site_icon_url();?>">
            <?php
            wp_print_scripts();
            wp_print_styles();
            ?>
        </head>
        <?php
}