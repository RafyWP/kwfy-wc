<?php

class Kwfy_Wc_i18n {

	public function load_plugin_textdomain() {
		load_plugin_textdomain(
			'kwfy-wc',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);
	}
}
