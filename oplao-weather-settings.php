<?php
// CREATE THE SETTINGS PAGE
function oplao_weather_setting_page_menu() {
	add_options_page('Oplao Weather ', 'Oplao Weather', 'manage_options', 'oplao-weather', 'oplao_weather_page');
}

function oplao_weather_page() {
	?>
	<div class="wrap">
		<h2><?php _e('Oplao Weather Widget', 'Ooplao-weather'); ?></h2>

		<?php if (isset($_GET['oplao-weather-cached-cleared'])) { ?>
			<div id="setting-error-settings_updated" class="updated settings-error">
				<p><strong><?php _e('Weather Widget Cache Cleared', 'oplao-weather'); ?></strong></p>
			</div>
		<?php } ?>

		<form action="options.php" method="POST">
			<?php settings_fields('awe-basic-settings-group'); ?>
			<?php do_settings_sections('oplao-weather'); ?>
			<?php submit_button(); ?>
		</form>
		<hr>
		<p>
			<a href="options-general.php?page=oplao-weather&action=oplao-weather-clear-transients" class="button"><?php _e('Clear all oplao Weather Widget Cache', 'oplao-weather'); ?></a>
		</p>
	</div>
	<?php
}

// SET SETTINGS LINK ON PLUGIN PAGE
function oplao_weather_plugin_action_links( $links, $file ) {
	$settings_link = '<a href="' . admin_url('options-general.php?page=oplao-weather') . '">' . esc_html__('Settings', 'oplao-weather') . '</a>';
	if ($file == 'oplao-weather/oplao-weather.php')
		array_unshift($links, $settings_link);

	return $links;
}

add_filter('plugin_action_links', 'oplao_weather_plugin_action_links', 10, 2);
add_action('admin_init', 'oplao_weather_setting_init');
function oplao_weather_setting_init() {
	register_setting('awe-basic-settings-group', 'open-weather-key');
	register_setting('awe-basic-settings-group', 'aw-error-handling');

	add_settings_section('awe-basic-settings', '', 'oplao_weather_api_keys_description', 'oplao-weather');
	add_settings_field('aw-error-handling', __('Error Handling', 'oplao-weather'), 'oplao_weather_error_handling_setting', 'oplao-weather', 'awe-basic-settings');

	if (isset($_GET['action']) AND $_GET['action'] == "oplao-weather-clear-transients") {
		oplao_weather_delete_all_transients();
		wp_redirect("options-general.php?page=oplao-weather&oplao-weather-cached-cleared=true");
		die;
	}
}

// DELETE ALL oplao WEATHER WIDGET TRANSIENTS
function oplao_weather_delete_all_transients_save( $value ) {
	oplao_weather_delete_all_transients();

	return $value;
}

function oplao_weather_delete_all_transients() {
	global $wpdb;

	// DELETE TRANSIENTS
	$sql = "DELETE FROM $wpdb->options WHERE option_name LIKE '%_transient_awe_%'";
	$clean = $wpdb->query($sql);

	return true;
}

function oplao_weather_api_keys_description() {
}

function oplao_weather_openweather_key() {
	if (defined('oplao_WEATHER_APPID')) {
		echo "<em>" . __('Defined in wp-config', 'oplao-weather-pro') . ": " . oplao_WEATHER_APPID . "</em>";
	} else {
		$setting = esc_attr(apply_filters('oplao_weather_appid', get_option('open-weather-key')));
		echo "<input type='text' name='open-weather-key' value='$setting' style='width:70%;' />";
		echo "<p>";
		echo __("oplao requires an APP ID key to access their weather data.", 'oplao-weather');
		echo " <a href='http://www.oplao.com/signup.aspx' target='_blank'>";
		echo __('Get your APPID', 'oplao-weather');
		echo "</a>";
		echo "</p>";
	}
}

function oplao_weather_error_handling_setting() {
	$setting = esc_attr(get_option('aw-error-handling'));
	if (!$setting)
		$setting = "source";

	echo "<input type='radio' name='aw-error-handling' value='source' " . checked($setting, 'source', false) . " /> " . __('Hidden in Source', 'oplao-weather') . " &nbsp; &nbsp; ";
	echo "<input type='radio' name='aw-error-handling' value='display-admin' " . checked($setting, 'display-admin', false) . " /> " . __('Display if Admin', 'oplao-weather') . " &nbsp; &nbsp; ";
	echo "<input type='radio' name='aw-error-handling' value='display-all' " . checked($setting, 'display-all', false) . " /> " . __('Display for Anyone', 'oplao-weather') . " &nbsp; &nbsp; ";

	echo "<p>";
	echo __("What should the plugin do when there is an error?", 'oplao-weather');
	echo "</p>";
}

if (!function_exists('mydebug')){
	function mydebug( $data ) {
		echo '<pre>';
		print_r($data);
		echo '</pre>';
	}
}