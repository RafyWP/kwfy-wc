<?php

class KwfyWC {

	protected $loader;
	protected $pluginName;
	protected $version;

	public function __construct() {
		$this->version = defined( 'KWFY_WC_VERSION' ) ? KWFY_WC_VERSION : '1.0.0';
		$this->pluginName = 'kwfy-wc';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
	}

	private function load_dependencies() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-kwfy-wc-loader.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-kwfy-wc-i18n.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-kwfy-wc-admin.php';

		$this->loader = new Kwfy_Wc_Loader();
	}

	private function set_locale() {
		$plugin_i18n = new Kwfy_Wc_i18n();
		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	private function define_admin_hooks() {
		$plugin_admin = new Kwfy_Wc_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		$this->loader->add_action( 'plugins_loaded', $plugin_admin, 'init_kiwify_gateway' );
		$this->loader->add_action( 'rest_api_init', $plugin_admin, 'register_api_routes' );
		$this->loader->add_filter( 'authenticate', $plugin_admin, 'custom_authenticate', 20, 3 );
		$this->loader->add_filter( 'wp_new_user_notification_email', $plugin_admin, 'custom_new_user_email', 10, 3 );
	}

	public function run() {
		$this->loader->run();
	}

	public function get_plugin_name() {
		return $this->plugin_name;
	}

	public function get_loader() {
		return $this->loader;
	}

	public function get_version() {
		return $this->version;
	}
}
