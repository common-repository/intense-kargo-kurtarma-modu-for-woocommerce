<?php
/**
 * @package Intense\KargoKurtarma\Includes
 */

namespace Intense\KargoKurtarma;

defined( 'ABSPATH' ) || exit();

class Kargo_Kurtarma {
	const CARGO_INTEGRATION_PLUGINS_ORDER_STATUS = 'wc-kargoya-verildi';
	const MANUAL_CARGO_PLUGIN_ORDER_STATUS = 'wc-shipping-progress';

	// Settings related
	const OPTIONS_PAGE_SLUG = 'intense-kargo-kurtarma-settings';
	const SETTINGS_GROUP = 'intense-kargo-kurtarma';
	const SETTING_RECOGNIZE_CARGO_INTEGRATIONS = 'intense_kargo_kurtarma_recognize_orders_with_cargo_integrations';
	const SETTING_RECOGNIZE_MANUAL_CARGO_PLUGIN = 'intense_kargo_kurtarma_recognize_orders_with_manual_cargo_plugin';

	public function __construct() {
		$this->hooks();
	}

	private function hooks() {
		add_action( 'init', array( $this, 'load_text_domain' ) );
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_filter( 'plugin_action_links_' . INTENSE_KARGO_KURTARMA_PLUGIN_BASENAME, array( $this, 'add_settings_link' ) );

		add_action( 'init', array( $this, 'register_order_statuses' ) );
		add_filter( 'wc_order_statuses', array( $this, 'add_order_statutes_to_wc' ) );
		add_action( 'woocommerce_admin_order_data_after_order_details', array( $this, 'show_cargo_info' ) );
	}

	public function load_text_domain() {
        load_plugin_textdomain( 'intense-kargo-kurtarma-modu-for-woocommerce', false, INTENSE_KARGO_KURTARMA_PLUGIN_DIR . '/languages/' );
    }

	public function add_settings_page() {
		add_options_page(
			'Intense Kargo Kurtarma Ayarları',
			'Intense Kargo Kurtarma Ayarları',
			'manage_options',
			self::OPTIONS_PAGE_SLUG,
			array( $this, 'settings_page_html' )
		);
	}

	public function settings_page_html() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<ul>
				<li><?php esc_html_e( 'Bu eklenti; Intense Kargo eklentileri kullanıcılarının eklentiyi kullanmayı bıraktıklarında "Kargoya verildi" sipariş durumunun sistemde olmaması sebebiyle görünmeyen siparişleri görünür hale getirir', 'intense-kargo-kurtarma-modu-for-woocommerce' ) ?>.</li>
				<li><?php esc_html_e( 'Geçmiş kargo bilgileri yönetici sipariş detayında görünür, ancak güncellenemez.', 'intense-kargo-kurtarma-modu-for-woocommerce' ) ?></li>
			</ul>
			<form action="options.php" method="post">
				<?php
				settings_fields( self::SETTINGS_GROUP );
				do_settings_sections( self::OPTIONS_PAGE_SLUG );
				submit_button( __( 'Ayarları Kaydet' , 'intense-kargo-kurtarma-modu-for-woocommerce' ) );
				?>
			</form>
		</div>
		<?php
	}

	public function register_settings() {
		register_setting( self::SETTINGS_GROUP, self::SETTING_RECOGNIZE_CARGO_INTEGRATIONS );
		register_setting( self::SETTINGS_GROUP, self::SETTING_RECOGNIZE_MANUAL_CARGO_PLUGIN );

		add_settings_section( 'intense-kargo-kurtarma-section', '', '__return_empty_string', self::OPTIONS_PAGE_SLUG );

		add_settings_field(
			'intense-kargo-kurtarma-field-recognize-cargo-integrations',
			__( 'Intense kargo entegrasyonlarını (Yurtiçi, MNG, UPS, Aras, PTT, Sürat) tanı', 'intense-kargo-kurtarma-modu-for-woocommerce' ),
			array( $this, 'settings_field_html' ),
			self::OPTIONS_PAGE_SLUG,
			'intense-kargo-kurtarma-section',
			array( 'intense_setting_name' => self::SETTING_RECOGNIZE_CARGO_INTEGRATIONS )
		);

		add_settings_field(
			'intense-kargo-kurtarma-field-recognize-manual-cargo',
			__( 'Intense Kargo Takip Modülü eklentisini tanı', 'intense-kargo-kurtarma-modu-for-woocommerce' ),
			array( $this, 'settings_field_html' ),
			self::OPTIONS_PAGE_SLUG,
			'intense-kargo-kurtarma-section',
			array( 'intense_setting_name' => self::SETTING_RECOGNIZE_MANUAL_CARGO_PLUGIN )
		);
	}

	public function settings_field_html( $args ) {
		$setting = $args['intense_setting_name'];
		$is_checked = (bool) get_option( $setting );

		if ( $setting === self::SETTING_RECOGNIZE_CARGO_INTEGRATIONS ) {
			$description = __( 'Intense kargo entegrasyonunu artık kullanmıyorsanız kargoya verildi durumundaki siparişlerinizi tekrar görebilmek için işaretleyiniz. Aktif edildiğinde, entegrasyon uygulamamızın kargoya verildi durumundaki siparişler Kargoya Verildi (e) durumunda görünecektir.', 'intense-kargo-kurtarma-modu-for-woocommerce' );
		} else if ( $setting === self::SETTING_RECOGNIZE_MANUAL_CARGO_PLUGIN ) {
			$description = __( 'Intense Kargo Takip Modülünü artık kullanmıyorsanız kargoya verildi durumundaki siparişlerinizi tekrar görebilmek için işaretleyiniz. Aktif edildiğinde, manuel kargo eklentimizin kargoya verildi durumundaki siparişler, Kargoya Verildi (m) durumunda görünecektir.', 'intense-kargo-kurtarma-modu-for-woocommerce' );
		}
		?>
		<input type="checkbox" name="<?php echo esc_attr( $setting ) ?>" <?php checked( $is_checked ) ?>></input>
		<p class="description"><?php echo esc_html( $description ) ?></p>
		<?php
	}

	public function add_settings_link( $links ) {
		$action_links = array(
			'settings' => '<a href="' . admin_url( 'admin.php?page=' . self::OPTIONS_PAGE_SLUG ) . '" aria-label="' . esc_attr__( 'Ayarları aç', 'intense-kargo-kurtarma-modu-for-woocommerce' ) . '">' . esc_html__( 'Ayarlar', 'intense-kargo-kurtarma-modu-for-woocommerce' ) . '</a>',
		);

		return array_merge( $action_links, $links );
	}

	public function register_order_statuses() {
		if ( get_option( self::SETTING_RECOGNIZE_CARGO_INTEGRATIONS ) ) {
			register_post_status( self::CARGO_INTEGRATION_PLUGINS_ORDER_STATUS, array(
				'label' => __( 'Kargoya Verildi (e)', 'intense-kargo-kurtarma-modu-for-woocommerce' ),
				'public' => true,
				'label_count' => _n_noop( 'Kargoya Verildi (%s)', 'Kargoya Verildi (%s)', 'intense-kargo-kurtarma-modu-for-woocommerce' )
			) );
		}

		if ( get_option( self::SETTING_RECOGNIZE_MANUAL_CARGO_PLUGIN ) ) {
			register_post_status( self::MANUAL_CARGO_PLUGIN_ORDER_STATUS, array(
				'label' => __( 'Kargoya Verildi (m)', 'intense-kargo-kurtarma-modu-for-woocommerce' ),
				'public' => true,
				'label_count' => _n_noop( 'Kargoya Verildi (%s)', 'Kargoya Verildi (%s)', 'intense-kargo-kurtarma-modu-for-woocommerce' )
			) );
		}
	}

	public function add_order_statutes_to_wc( $order_statuses ) {
		if ( get_option( self::SETTING_RECOGNIZE_CARGO_INTEGRATIONS ) ) {
			$order_statuses[self::CARGO_INTEGRATION_PLUGINS_ORDER_STATUS] = __( 'Kargoya Verildi (e)', 'intense-kargo-kurtarma-modu-for-woocommerce' );
		}

		if ( get_option( self::SETTING_RECOGNIZE_MANUAL_CARGO_PLUGIN ) ) {
			$order_statuses[self::MANUAL_CARGO_PLUGIN_ORDER_STATUS] = __( 'Kargoya Verildi (m)', 'intense-kargo-kurtarma-modu-for-woocommerce' );
		}

		return $order_statuses;
	}

	public function show_cargo_info( $order ) {
		$is_recog_cargo_integrations = get_option( self::SETTING_RECOGNIZE_CARGO_INTEGRATIONS );
		$is_recog_manuel_cargo = get_option( self::SETTING_RECOGNIZE_MANUAL_CARGO_PLUGIN );

		if ( $is_recog_cargo_integrations || $is_recog_manuel_cargo ) {
			$order_id = $order->get_id();

			if ( $is_recog_cargo_integrations ) {
				$integration_cargo_company  = get_post_meta( $order_id, '_intense_kargo_firmasi', true );
				$integration_tracking_number = get_post_meta( $order_id, '_intense_kargo_takip_no', true );	
			}

			if ( $is_recog_manuel_cargo ) {
				$manuel_cargo_company = get_post_meta( $order_id, 'shipping_company', true );
				$manuel_tracking_number = get_post_meta( $order_id, 'shipping_number', true );
			} ?>
			<br class="clear" />
			<?php if ( $is_recog_cargo_integrations ): ?>
				<h4><?php esc_html_e( 'Kargo Bilgileri (entegrasyon)', 'intense-kargo-kurtarma-modu-for-woocommerce' ) ?></h4>
				<div class="address">
					<p><strong><?php esc_html_e( 'Kargo Firması:', 'intense-kargo-kurtarma-modu-for-woocommerce' ) ?></strong> <?php echo esc_html( $integration_cargo_company ); ?></p>
					<p><strong><?php esc_html_e( 'Kargo Takip Numarası:', 'intense-kargo-kurtarma-modu-for-woocommerce' ) ?></strong> <?php echo esc_html( $integration_tracking_number ); ?></p>
				</div>
			<?php endif; ?>
			<?php if ( $is_recog_manuel_cargo ): ?>
				<h4><?php esc_html_e( 'Kargo Bilgileri (manuel)', 'intense-kargo-kurtarma-modu-for-woocommerce' ) ?></h4>
				<div class="address">
					<p><strong><?php esc_html_e( 'Kargo Firması:', 'intense-kargo-kurtarma-modu-for-woocommerce' ) ?></strong> <?php echo esc_html( $manuel_cargo_company ); ?></p>
					<p><strong><?php esc_html_e( 'Kargo Takip Numarası:', 'intense-kargo-kurtarma-modu-for-woocommerce' ) ?></strong> <?php echo esc_html( $manuel_tracking_number ); ?></p>
				</div>
			<?php endif;
		}
	}
}
