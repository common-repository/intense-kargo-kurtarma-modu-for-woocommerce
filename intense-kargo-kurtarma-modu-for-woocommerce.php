<?php
/**
 * Plugin Name: Intense Kargo Kurtarma Modu for WooCommerce
 * Plugin URI: https://intense.com.tr/
 * Description: Intense Kargo Kurtarma Modu for WooCommerce
 * Version: 1.0.1
 * Author: Intense Yazılım Int. Tekn. San. ve Tic. Ltd. Şti.
 * Author URI: http://intense.com.tr/
 * Requires PHP: 7.0
 * WC tested up to: 6.1
 * Text Domain: intense-kargo-kurtarma-modu-for-woocommerce
 *
 * @package Intense\KargoKurtarma
 */

defined( 'ABSPATH' ) || exit();

define( 'INTENSE_KARGO_KURTARMA_VERSION', '1.0.1' );
define( 'INTENSE_KARGO_KURTARMA_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'INTENSE_KARGO_KURTARMA_PLUGIN_DIR', dirname( INTENSE_KARGO_KURTARMA_PLUGIN_BASENAME ) );

require plugin_dir_path( __FILE__ ) . 'includes/class-intense-kargo-kurtarma.php';

new \Intense\KargoKurtarma\Kargo_Kurtarma();
