<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://daniseveriano.tech
 * @since             1.0.0
 * @package           Picpay_Payment_Link_Gateway
 *
 * @wordpress-plugin
 * Plugin Name:       PicPay Payment Link Gateway
 * Plugin URI:        https://github.com/daniseveriano
 * Description:       Integração de Link de Pagamento do PicPay para WooCommerce, para usuários que, por alguma razão, ainda não podem utilizar o PicPay para Lojistas. É necessário ter o Woocommerce instalado e ativo no seu projeto.
 * Version:           1.0.0
 * Author:            Daniele Severiano
 * Author URI:        https://daniseveriano.tech/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       picpay-payment-link-gateway
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'PICPAY_PAYMENT_LINK_GATEWAY_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-picpay-payment-link-gateway-activator.php
 */
function activate_picpay_payment_link_gateway() {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-picpay-payment-link-gateway-activator.php';
    Picpay_Payment_Link_Gateway_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-picpay-payment-link-gateway-deactivator.php
 */
function deactivate_picpay_payment_link_gateway() {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-picpay-payment-link-gateway-deactivator.php';
    Picpay_Payment_Link_Gateway_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_picpay_payment_link_gateway' );
register_deactivation_hook( __FILE__, 'deactivate_picpay_payment_link_gateway' );

if ( ! function_exists( 'is_plugin_active' ) ) {
    include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
}

if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
    add_action( 'admin_notices', 'picpay_wc_missing_notice' );
    wp_die( __( 'O plugin PicPay Payment Link Gateway requer que o WooCommerce esteja instalado e ativo. Por favor, instale e ative o WooCommerce para utilizar este plugin.', 'picpay-payment-link-gateway' ), '', array( 'back_link' => true ) );
    exit;
}

function picpay_wc_missing_notice() {
    ?>
    <div class="error">
        <p><?php _e( 'O plugin PicPay Payment Link Gateway requer que o WooCommerce esteja instalado e ativo. Por favor, instale e ative o WooCommerce para utilizar este plugin.', 'picpay-payment-link-gateway' ); ?></p>
    </div>
    <?php
}

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-picpay-payment-link-gateway.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_picpay_payment_link_gateway() {

    $plugin = new Picpay_Payment_Link_Gateway();
    $plugin->run();

}
run_picpay_payment_link_gateway();

add_filter( 'woocommerce_payment_gateways', 'picpay_payment_link_gateway' );
function picpay_payment_link_gateway( $gateways ) {
    $gateways[] = 'WC_PicPay_Payment_Link_Gateway';
    return $gateways;
}

add_action( 'plugins_loaded', 'picpay_payment_link_init_gateway_class' );
function picpay_payment_link_init_gateway_class() {

    class WC_PicPay_Payment_Link_Gateway extends WC_Payment_Gateway {

        public function __construct() {
            $this->id = 'picpay';
            $this->method_title = __('PicPay');
            $this->title = __('PicPay');
            $this->has_fields = false;

            $this->icon = plugins_url( 'public/images/picpay-ico.png', __FILE__ );

            $this->init_form_fields();
            $this->init_settings();

            $this->enabled = $this->get_option('enabled');
            $this->title = $this->get_option('title');
            $this->picpay_username = $this->get_option('picpay_username');
            $this->show_username_field = $this->get_option('show_username_field', 'yes');
            $this->default_order_status = $this->get_option('default_order_status', 'wc-pending');

            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

            add_action('woocommerce_thankyou', array($this, 'picpay_payment_receipt_page'));
        }

        public function init_form_fields(){
            $this->form_fields = array(
                'enabled' => array(
                    'title' => __('Habilitar/Desabilitar'),
                    'type' => 'checkbox',
                    'label' => __('Habilitar pagamento via PicPay'),
                    'default' => 'no'
                ),
                'title' => array(
                    'title' => __('Título'),
                    'type' => 'text',
                    'description' => __('Este é o título que os usuários verão durante o checkout.'),
                    'default' => __('PicPay'),
                    'desc_tip' => true
                ),
                'picpay_username' => array(
                    'title' => __('Nome de usuário do PicPay'),
                    'type' => 'text',
                    'description' => __('Insira o nome de usuário do PicPay para receber os pagamentos, SEM O ARROBA (@).'),
                    'default' => 'danicosvosk',
                    'desc_tip' => true
                ),
                'show_username_field' => array(
                    'title' => __('Exibir o nome do usuário do PicPay junto com o QRCode e o Link de Pagamento?'),
                    'type' => 'select',
                    'options' => array(
                        'yes' => __('Sim'),
                        'no' => __('Não')
                    ),
                    'default' => 'yes',
                    'description' => __('Selecione se deseja exibir o nome do usuário do PicPay junto com o QRCode e o Link de Pagamento na página de finalização do pedido.')
                ),
                'default_order_status' => array(
                    'title' => __('Status padrão do pedido'),
                    'type' => 'select',
                    'options' => wc_get_order_statuses(),
                    'default' => 'wc-pending',
                    'description' => __('Este é o status do pedido que será atribuído após a conclusão do checkout.')
                )
            );
        }

        public function process_payment($order_id) {
            $order = wc_get_order($order_id);

            $default_order_status = $this->get_option('default_order_status', 'wc-pending');

            if ($default_order_status && in_array($default_order_status, array_keys(wc_get_order_statuses()))) {
                $order->update_status($default_order_status);
            }

            return array(
                'result' => 'success',
                'redirect' => $this->get_return_url($order)
            );
        }

        public function picpay_payment_receipt_page($order_id) {
            $order = wc_get_order($order_id);

            if ($order->get_payment_method() === 'picpay') {
                $picpay_username = $this->picpay_username;
                $picpay_payment_link = 'https://picpay.me/' . $picpay_username . '/' . $order->get_total();
                ?>

                <section>
                    <div style="background-color: #F0EEEE; display: flex; justify-content: center; margin-bottom: 50px;">
                        <div style="display: flex; flex-direction: column; justify-content: center; align-items: center; width: 350px; height: 650px; background-color: white; border-radius: 20px; margin-top: 50px; margin-bottom: 50px;">
                            <img style="width: 30%;" src=<?= plugins_url( 'public/images/picpay-logo.png', __FILE__ ) ?>>
                            <?php if ($this->show_username_field === 'yes') : ?>
                            <h3 style="font-size: 2rem !important;">
                                Pague <strong style="color: #21C25E;">@<?= $picpay_username ?></strong>
                            </h3>
                            <?php endif; ?>
                            <br>
                            <p style="text-align: center;">
                                Abra o PicPay pelo botão abaixo:
                            </p>
                            <br>
                            <a style="background-color: #21C25E; color: #fff; padding: 10px 20px; border-radius: 20px;" href="<?= $picpay_payment_link ?>" target="_blank"><strong>ABRA O APLICATIVO</strong></a>
                            <br>
                            <p style="text-align: center;">
                                Ou scaneie o QRCode abaixo com o seu App:
                            </p>
                            <div id="qrcode"></div>
                            <br>
                            <p style="text-align: center;">
                                Não possui conta no PicPay?
                            </p>
                            <button style="color: #21C25E !important; background-color: transparent !important; border: none !important;" id="baixar-app" target="_blank"><strong>Baixe o app gratuitamente!</strong></button>
                            <input type="hidden" id="link-de-pagamento" value="<?= $picpay_payment_link ?>">
                        </div>
                    </div>
                </section>

                <script src=<?= plugins_url( 'public/js/qrcode.min.js', __FILE__ ) ?>></script>
                <script type="text/javascript">
                    var link = document.getElementById("link-de-pagamento").value;
                    new QRCode(document.getElementById("qrcode"), link);
                </script>
                <script defer>
                    document.addEventListener('DOMContentLoaded', function() {
                        document.getElementById('baixar-app').addEventListener('click', function() {
                            var userAgent = navigator.userAgent || navigator.vendor || window.opera;
                            if (/android/i.test(userAgent)) {
                                window.location.href = 'https://play.google.com/store/apps/details?id=com.picpay&hl=pt_BR';
                            } else if (/iPad|iPhone|iPod/.test(userAgent) && !window.MSStream) {
                                window.location.href = 'https://apps.apple.com/br/app/picpay-conta-pix-e-cart%C3%A3o/id561524792';
                            } else {
                                alert('Desculpe, o aplicativo PicPay está disponível apenas para Android e iOS.');
                            }
                        });
                    });
                </script>

                <?php
            }
        }

    }
}
