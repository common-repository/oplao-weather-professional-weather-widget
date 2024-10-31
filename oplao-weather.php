<?php

/*
Plugin Name: Oplao Weather Widget
Description: Oplao weather official WordPress weather widget. Responsive. Easy to use. Beautiful design. Live search.
Author: Oplao Weather
Author URI: https://profiles.wordpress.org/oplao/
Version: 1.1.6
Text Domain: oplao-weather
Domain Path: /languages

// CLEAR OUT THE TRANSIENT CACHE
add to your URL 'clear_oplao_widget'
For example: http://url.com/?clear_oplao_widget

*/

// INCLUDE oplao class
require_once(dirname(__FILE__) . "/inc/weather.class.php");

// SETUP
function oplao_weather_setup() {
	load_plugin_textdomain('oplao-weather', false, dirname(plugin_basename(__FILE__)) . '/languages/');
	add_action('admin_menu', 'oplao_weather_setting_page_menu');
}

add_action('plugins_loaded', 'oplao_weather_setup', 99999);

// ENQUEUE CSS
function oplao_weather_wp_head( $posts ) {
	wp_enqueue_style('oplao-weather', plugins_url('/oplao-weather.css', __FILE__));
    wp_enqueue_style('oplao-weather-css', plugins_url('/fonts/fonts.css', __FILE__));

	$use_google_font = apply_filters('oplao_weather_use_google_font', true);
	$google_font_queuename = apply_filters('oplao_weather_google_font_queue_name', 'opensans-googlefont');

	if ($use_google_font) {
		wp_enqueue_style($google_font_queuename, 'https://fonts.googleapis.com/css?family=Open+Sans:400,300');
		wp_add_inline_style('oplao-weather', ".oplao-weather-wrap { font-family: 'Open Sans', sans-serif;  font-weight: 300; font-size: 16px; line-height: 14px; } ");
	}
}

add_action('wp_enqueue_scripts', 'oplao_weather_wp_head');

//THE SHORTCODE
add_shortcode('oplao-weather', 'oplao_weather_shortcode');
function oplao_weather_shortcode( $atts ) {
	return oplao_weather_logic($atts);
}

// THE LOGIC
function oplao_weather_logic( $atts ) {
	global $oplao_weather_sizes;
    $mod_url = plugin_dir_url(__FILE__);
	$rtn = "";
	$weather_data = array();
	$location = isset($atts['location']) ? $atts['location'] : false;
	$owm_city_id = isset($atts['owm_city_id']) ? $atts['owm_city_id'] : false;
	$units = (isset($atts['units']) AND strtoupper($atts['units']) == "C") ? "metric" : "imperial";
	$units_display = $units == "metric" ? __('C', 'oplao-weather') : __('f', 'oplao-weather');
	$days_to_show = isset($atts['forecast_days']) ? $atts['forecast_days'] : 5;
	$show_stats = (isset($atts['show_stats']) AND $atts['show_stats'] == 1) ? 1 : 0;
	$inline_style = isset($atts['inline_style']) ? $atts['inline_style'] : '';
	$widget_scheme = isset($atts['scheme']) ? $atts['scheme'] : 1;
	$location_case = isset($atts['location_case']) ? esc_attr($atts['location_case']) : 3;
	$locale = 'en';
	$sytem_locale = get_locale();
	$available_locales = apply_filters('oplao_weather_available_locales', array('en'));
    $geo_location = isset($atts['coordinates']) ? $atts['coordinates'] : false;
    
    $humidity_txt = __('Humidity', 'oplao-weather');
    $feelslike_txt = __('Feels like', 'oplao-weatr');
    $wind_txt = __('Wind', 'oplao-weather');


    switch ($days_to_show) {
        case 3:
            $size = "3d";
            break;
        case 5:
            $size = "5d";
            break;
        case 6:
            $days_to_show = 5;
            $size = "wide";
            break;
        case "hide":
            $days_to_show = 0;
            $size = "1d";
            break;
    }

    $class = "";
    switch ($widget_scheme) {
        case 1:
            $class .= "theme_1";
            $size = "1d";
            $days_to_show = 4;
            break;
        case 2:
            $class .= "theme_2";
            break;
        case 3:
            $class .= "theme_3";
            break;
        case 4:
            $class .= "theme_4";
            break;
        case 5:
            $class .= "theme_5";
            break;
        case 6:
            $class .= "theme_6";
            break;

    }

	// CHECK FOR LOCALE
	if (in_array($sytem_locale, $available_locales))
		$locale = $sytem_locale;

	// CHECK FOR LOCALE BY FIRST TWO DIGITS
	if (in_array(substr($sytem_locale, 0, 2), $available_locales))
		$locale = substr($sytem_locale, 0, 2);

	// OVERRIDE LOCALE PARAMETER
	if (isset($atts['locale']))
		$locale = $atts['locale'];

	// DISPLAY SYMBOL
	$units_display_symbol = apply_filters('oplao_weather_units_display', "&deg;");
	if (isset($atts['units_display_symbol']))
		$units_display_symbol = $atts['units_display_symbol'];

	// NO LOCATION, ABORT ABORT!!!!
	if (!$location)
		return oplao_weather_error();

	//FIND AND CACHE CITY ID
	if ($owm_city_id) {
		$city_name_slug = sanitize_title($location);
	} else if (is_numeric($location)) {
		$city_name_slug = sanitize_title($location);
	} else {
		$city_name_slug = sanitize_title($location);
	}

	$location_parts = explode(',',trim($location));
	$location_city = $location_parts[0];
	$location_country=trim(end($location_parts));

	// TRANSIENT NAME
	$weather_transient_name = 'awe_' . $city_name_slug . "_" . $days_to_show . "_" . strtolower($units) . '_' . $locale;

	// CLEAR THE TRANSIENT
	if (isset($_GET['clear_oplao_widget']))
		delete_transient($weather_transient_name);

	// GET KEY
	$key = 'fb901751d7894f6e8b6113816162011';

	// GET WEATHER DATA
	if (get_transient($weather_transient_name)) {
		$weather_data = get_transient($weather_transient_name);
	} else {
		$weather_data['now'] = array();
		$weather_data['forecast'] = array();
		$weather_data['location'] = array();
		if ($days_to_show != "hide") {
			// FORECAST
			$weather_oplao = CWeather::get_forecast_weather_geo($key, $geo_location, $days_to_show + 1);
			$weather_data['now'] = $weather_oplao->current;
			$weather_data['forecast'] = $weather_oplao->forecast;
		} else {
			// NOW
			$weather_oplao = CWeather::get_current_weather_geo($key, $geo_location);
			$weather_data['now'] = $weather_oplao->current;
		}
		$weather_data['location'] = $weather_oplao->location;

		if ($weather_data['now'] OR $weather_data['forecast']) {
			set_transient($weather_transient_name, $weather_data, apply_filters('oplao_weather_cache', 1800));
		}
	}

	// NO WEATHER
	if (!$weather_data OR !isset($weather_data['now']))
		return oplao_weather_error();

	// TODAYS TEMPS
	$today = $weather_data['now'];
	$today_temp = ($units == "imperial") ? (int)$today->temp_f : (int)$today->temp_c;
	$location = $weather_data['location'];

	// BACKGROUND DATA, CLASSES AND OR IMAGES
	$background_classes = array();
	$background_classes[] = "weather-wrap";
	$background_classes[] = "awecf";
	$background_classes[] = "awe_" . $size;

	// WIND
	$wind_direction = false;
	if (isset($today->wind_dir))
		$wind_direction = apply_filters('oplao_weather_wind_direction', __($today->wind_dir, 'oplao-weather'));

	$background_classes[] = ($show_stats) ? "awe_with_stats" : "awe_without_stats";

	// ADD WEATHER CONDITIONS CLASSES TO WRAP
	if (isset($today->condition)) {
		$weather_code = $today->condition->code;
		$weather_descr = explode(' ', strtolower(trim($today->condition->text)));
		$weather_description_slug = sanitize_title($weather_descr[ count($weather_descr) - 1 ]);
		$background_classes[] = "awe-code-" . $weather_code;
		$background_classes[] = "awe-desc-" . $weather_description_slug;
	}

	// EXTRA STYLES
	$background_class_string = @implode(" ", apply_filters('oplao_weather_background_classes', $background_classes));
	$today_sign = ($today_temp > 0) ? '+' : '';

	if ($inline_style){
		$inline_style = "style='{$inline_style}'";
	}

	$background_class_string .= ' ' . $class;
	// DISPLAY WIDGET
    $rtn .= "<div id=\"weather-{$city_name_slug}\" $inline_style class=\"{$background_class_string}\">";

    $rtn .= "<div class=\"weather-cover awe_" . $size . "\">";

	// Picture
	if (isset($today->condition->icon)) {
		$rtn1 = '<div class="weather-todays-stats-big-pict">';
		$folder = 'large';
		$pict_apend = 'lg';
		$pict = explode('/', $today->condition->icon);
		$day_case = $pict[ count($pict) - 2 ];
		$pict_code = (int)$pict[ count($pict) - 1 ];
		$path_oplao_pict = plugin_dir_url(__FILE__) . "img/{$folder}/{$pict_code}_{$day_case}_{$pict_apend}.png";
		$rtn1 .= "<img src=$path_oplao_pict>";
		$rtn1 .= '</div><!-- /.oplao-weather-todays-stats -->';
	}

	//location
	$location_cases = array(
		1 => $location->name . ', ' . $location->region . ', ' . $location->country,
		2 => $location->name . ', ' . $location->region,
		3 => $location->name . ', ' . $location->country,
		4 => $location->name
	);

    $current_date = date("D d");
    $current_time = date("H:i");

    $wind_speed = ($units == "imperial") ? (int)$today->wind_mph : (int)$today->wind_kph;
    $feelslike = ($units == "imperial") ? $today->feelslike_f : $today->feelslike_c;
    $wind_speed_text = ($units == "imperial") ? __('mph', 'oplao-weather') : __('m/s', 'oplao-weather');
    $pressure = (int)$today->pressure_in;
    $humidity = $today->humidity;

    $units_icon = $units == "imperial" ? 'icon_f.png' : 'icon_c.png';
    $today_sign = $units == "imperial" ? '' : $today_sign;

    $normal_local = $location_city .", ". $location_country;
    if(strlen($normal_local) > 32){
        $normal_local = $location_city;
    }
    if(strlen($normal_local) > 20 && $size == "wide"){
        $normal_local = substr($location_city,0,20);
    }

    // WEATHER LANG CHANGE
    $current_condition = __(trim($today->condition->text), 'oplao-weather');

    if (in_array($widget_scheme, array(1)) && in_array($size, array('1d'))) {

        $rtn .= "<div class=\"weather-cover\">
		<div class=\"weather-head\">
			<div class=\"awe_title\">WEATHER</div>
			<div class=\"awe_info_dt\">{$normal_local}, {$current_date}, {$current_time}</div>
		</div>";
    }

    if (in_array($widget_scheme, array(2)) && in_array($size, array('1d'))) {
        $rtn .= "<div class=\"weather-head\">
			            <div class=\"awe_title\">{$normal_local}</div>
		                    </div>
		                        <div class=\"weather-body\">
			                         <div class=\"awe_left\">
				                     {$rtn1}				              
			                    </div>
			                    <div class=\"awe_right\">
				                <div class=\"awe_temp\">{$today_sign}{$today_temp}<span>{$units_display_symbol}{$units_display}</span></div>
				                <div class=\"current_weather\">{$current_condition}</div>
			                </div>
		                </div>
		                <div class=\"weather-more-link\"><a href=\"http://www.oplao.com/\" target=\"_blank\" title=\"Free weather\"><img src=\"{$mod_url}/img/logo_t1.png\"></a></div>";
    }

    if (in_array($widget_scheme, array(3)) && in_array($size, array('1d'))) {
        $rtn .= "<div class=\"weather-head\">
			            <div class=\"awe_title\">{$normal_local}</div>
		                    </div>
		                        <div class=\"weather-body\">
			                         <div class=\"awe_left\">
				                     {$rtn1}
				                <div class=\"current_data\">{$current_date}</div>
			                </div>
			                    <div class=\"awe_right\">
				                <div class=\"awe_temp\">{$today_sign}{$today_temp}<span>{$units_display_symbol}{$units_display}</span></div>
				                <div class=\"current_weather\">{$current_condition}</div>
			                </div>
		                </div>
		                <div class=\"weather-more-link\"><a href=\"http://www.oplao.com/\" target=\"_blank\" title=\"Free weather\"><img src=\"{$mod_url}/img/logo_t1.png\"></a></div>";
    }

    if (in_array($widget_scheme, array(4)) && in_array($size, array('1d'))) {
        $rtn .= "<div class=\"weather-head\">
			<div class=\"awe_title\">{$normal_local}</div>
		</div>
		<div class=\"weather-body\">
			<div class=\"awe_center\">
				{$rtn1}
				<div class=\"awe_temp\">{$today_sign}{$today_temp}<span>{$units_display_symbol}{$units_display}</span></div>
				<div class=\"weather-data\">
					<div>
						<div class=\"awe_feels\">
							<div>{$feelslike_txt}</div>
							<div>{$feelslike}{$units_display_symbol}{$units_display}</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class=\"weather-more-link\"><a href=\"http://www.oplao.com/\" target=\"_blank\" title=\"Free weather\"><img
					src=\"{$mod_url}/img/logo_t1_d3.png\"></a></div>";
    }

    if (in_array($widget_scheme, array(5)) && in_array($size, array('1d'))) {
        $rtn .= "<div class=\"weather-head\">
			<div class=\"awe_title\">{$normal_local}</div>
		</div>
		<div class=\"weather-body\">
			<div class=\"awe_left\">
				{$rtn1}
			</div>
			<div class=\"awe_right\">
				<div class=\"awe_temp\">{$today_sign}{$today_temp}<span>{$units_display_symbol}{$units_display}</span></div>
				<div class=\"current_weather\">{$current_condition}</div>
				<div class=\"weather-data\">
					<div>
						<div class=\"awe_feels\">
							<div>{$feelslike_txt}</div>
							<div>{$feelslike}{$units_display_symbol}{$units_display}</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class=\"weather-more-link\"><a href=\"http://www.oplao.com/\" target=\"_blank\" title=\"Free weather\"><img src=\"{$mod_url}/img/logo_t1.png\"></a></div>";
    }

    if (in_array($widget_scheme, array(6)) && in_array($size, array('1d'))) {
        $rtn .= "<div class=\"weather-head\">
			<div class=\"awe_title\">{$normal_local}</div>
		</div>
		<div class=\"weather-body\">
			<div class=\"awe_center\">
				{$rtn1}
				<div class=\"awe_temp\">{$today_sign}{$today_temp}<span>{$units_display_symbol}{$units_display}</span></div>
				<div class=\"weather-data\">
					<div>
						<div class=\"awe_feels\">
							<div>{$feelslike_txt}</div>
							<div>{$feelslike}{$units_display_symbol}{$units_display}</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class=\"weather-more-link\"><a href=\"http://www.oplao.com/\" target=\"_blank\" title=\"Free weather\"><img
					src=\"{$mod_url}/img/logo_t6.png\"></a></div>";
    }

    if (in_array($widget_scheme, array(2)) && in_array($size, array('3d','5d'))) {
        $rtn .= "<div class=\"weather-head\">
			<div class=\"awe_title\">{$normal_local}</div>
		</div>
		<div class=\"weather-body\">
			<div class=\"awe_left\">
				{$rtn1}				
			</div>
			<div class=\"awe_right\">
				<div class=\"awe_temp\">{$today_sign}{$today_temp}<span>{$units_display_symbol}{$units_display}</span></div>
				<div class=\"current_weather\">{$current_condition}</div>
				<div class=\"weather-data\">
					<div>
						<div class=\"awe_feels\">
							<div>{$feelslike_txt}</div>
							<div>{$feelslike}{$units_display_symbol}{$units_display}</div>
						</div>
						<div class=\"awe_wind\">
							<div>{$wind_txt}</div>
							<div>{$wind_speed} {$wind_speed_text}</div>
						</div>
						<div class=\"awe_gust\">
							<div>{$humidity_txt}</div>
							<div>{$humidity}%</div>
						</div>
					</div>
				</div>
			</div>
		</div>";
    }

    if (in_array($widget_scheme, array(3)) && in_array($size, array('3d','5d'))) {
        $rtn .= "<div class=\"weather-head\">
			<div class=\"awe_title\">{$normal_local}</div>
		</div>
		<div class=\"weather-body\">
			<div class=\"awe_center\">
				{$rtn1}
				<div class=\"awe_temp\">{$today_sign}{$today_temp}<span>{$units_display_symbol}{$units_display}</span></div>
				<div class=\"weather-data\">
					<div>
						<div class=\"awe_feels\">
							<div>{$feelslike_txt}</div>
							<div>{$feelslike}{$units_display_symbol}{$units_display}</div>
						</div>
						<div class=\"awe_wind\">
							<div>{$wind_txt}</div>
							<div>{$wind_speed} {$wind_speed_text}</div>
						</div>
						<div class=\"awe_gust\">
							<div>{$humidity_txt}</div>
							<div>{$humidity}%</div>
						</div>
					</div>
				</div>
			</div>
		</div>";
    }

    if (in_array($widget_scheme, array(4)) && in_array($size, array('3d','5d'))) {
        $rtn .= "<div class=\"weather-head\">
			<div class=\"awe_title\">{$normal_local}</div>
		</div>
		<div class=\"weather-body\">
			<div class=\"awe_center\">
				{$rtn1}
				<div class=\"current_weather\">{$current_condition}</div>
				<div class=\"awe_temp\">{$today_sign}{$today_temp}<span>{$units_display_symbol}{$units_display}</span></div>
				<div class=\"weather-data\">
					<div>
						<div class=\"awe_feels\">
							<div>{$feelslike_txt}</div>
							<div>{$feelslike}{$units_display_symbol}{$units_display}</div>
						</div>
						<div class=\"awe_wind\">
							<div>{$wind_txt}</div>
							<div>{$wind_speed} {$wind_speed_text}</div>
						</div>
						<div class=\"awe_gust\">
							<div>{$humidity_txt}</div>
							<div>{$humidity}%</div>
						</div>
					</div>
				</div>
			</div>
		</div>";
    }

    if (in_array($widget_scheme, array(5)) && in_array($size, array('3d','5d'))) {
        $rtn .= "<div class=\"weather-head\">
			<div class=\"awe_title\">{$normal_local}</div>
		</div>
		<div class=\"weather-body\">
			<div class=\"awe_left\">
				{$rtn1}
			</div>
			<div class=\"awe_right\">
				<div class=\"awe_temp\">{$today_sign}{$today_temp}<span>{$units_display_symbol}{$units_display}</span></div>
				<div class=\"current_weather\">{$current_condition}</div>
			</div>
		</div>
		<div class=\"awe_center\">
			<div class=\"weather-data\">
				<div>
					<div class=\"awe_feels\">
						<div>{$feelslike_txt}</div>
						<div>{$feelslike}{$units_display_symbol}{$units_display}</div>
					</div>
					<div class=\"awe_wind\">
						<div>{$wind_txt}</div>
						<div>{$wind_speed} {$wind_speed_text}</div>
					</div>
					<div class=\"awe_gust\">
						<div>{$humidity_txt}</div>
						<div>{$humidity}%</div>
					</div>
				</div>
			</div>
		</div>";
    }

    if (in_array($widget_scheme, array(6)) && in_array($size, array('3d','5d'))) {
        $rtn .= "<div class=\"weather-head\">
			<div class=\"awe_title\">{$normal_local}</div>
		</div>
		<div class=\"weather-body\">
			<div class=\"awe_center\">
				{$rtn1}
				<div class=\"current_weather\">{$current_condition}</div>
				<div class=\"awe_temp\">{$today_sign}{$today_temp}<span>{$units_display_symbol}{$units_display}</span></div>
				<div class=\"weather-data\">
					<div>
						<div class=\"awe_feels\">
							<div>{$feelslike_txt}</div>
						    <div>{$feelslike}{$units_display_symbol}{$units_display}</div>
						</div>
						<div class=\"awe_wind\">
							<div>{$wind_txt}</div>
						    <div>{$wind_speed} {$wind_speed_text}</div>
						</div>
						<div class=\"awe_gust\">
							<div>{$humidity_txt}</div>
							<div>{$humidity}%</div>
						</div>
					</div>
				</div>
			</div>
		</div>";
    }

    if (in_array($size, array('wide'))) {

        $rtn .= "<div class=\"weather-left\">
			<div class=\"fsection\">
				{$rtn1}
				<div class=\"current_data\"><a href=\"http://www.oplao.com/\" target=\"_blank\" title=\"Free weather\"><img
					src = \"{$mod_url}/img/logo_t1_d3.png\" ></a ></div>
			</div>
			<div class=\"tsection\">
				<div class=\"awe_title\">{$normal_local}</div>
				<div class=\"awe_temp\"><span class=\"plas\">{$today_sign}</span>{$today_temp}<span>{$units_display_symbol}{$units_display}</span></div>
				<div class=\"current_weather\">{$current_condition}</div>
			</div>
		</div>";
    }

	//show forecast
	if ($days_to_show != "hide") {
        if (in_array($size, array('wide'))) {
            $rtn .= "<div class=\"weather-center\">";
            $rtn .= "<div class=\"weather-forecast awe_days_{$days_to_show} awecf \">";
            $rtn .= "<div class=\"weather-forecast-day\">";
        } else {
            $rtn .= "<div class=\"weather-forecast awe_days_{$days_to_show} awecf \">";
            $rtn .= "<div class=\"weather-forecast-day\">";}
        $c = 1;
        $dt_today = date('Ymd');
        $forecast = $weather_data['forecast'];


        $days_to_show = (int)$days_to_show;
        $days_of_week = $days_of_week = apply_filters('apixu_weather_days_of_week', array(__('Sun', 'apixu-weather'), __('Mon', 'apixu-weather'), __('Tue', 'apixu-weather'), __('Wed', 'apixu-weather'), __('Thu', 'apixu-weather'), __('Fri', 'apixu-weather'), __('Sat', 'apixu-weather')));
        foreach ((array)$forecast->forecastday as $forecast) {
            //print_r($forecast->hour[date("G")]->feelslike_f);
            if ($dt_today >= date('Ymd', $forecast->date_epoch)) {
                $c = 1;
                continue;
            }
            $forecast->temp = ($units == "imperial") ? (int)$forecast->day->avgtemp_f : (int)$forecast->day->avgtemp_c;
            $forecast->temp_full = ($units == "imperial") ? $forecast->day->avgtemp_f : $forecast->day->avgtemp_c;
            $forecast->wind = ($units == "imperial") ? (int)$forecast->day->maxwind_mph : (int)$forecast->day->maxwind_kph;
            $forecast->wind_text = ($units == "imperial") ? __('mph', 'oplao-weather') : __('kmh', 'oplao-weather');
            $forecast->max_temp = ($units == "imperial") ? (int)$forecast->day->maxtemp_f : (int)$forecast->day->maxtemp_c;
            $forecast->min_temp = ($units == "imperial") ? (int)$forecast->day->mintemp_f : (int)$forecast->day->mintemp_c;
            $day_of_week = $days_of_week[date('w', $forecast->date_epoch)];

            if (isset($forecast->day->condition->icon)) {
                $rtn_ = '<div>';
                // SELECT SIZE IMG FORECAST
                $folder = 'large';
                $pict_apend = 'lg';
                $pict = explode('/', $forecast->day->condition->icon);
                $day_case = $pict[count($pict) - 2];
                $pict_code = (int)$pict[count($pict) - 1];
                $path_pict_sm = plugin_dir_url(__FILE__)."img/{$folder}/{$pict_code}_{$day_case}_{$pict_apend}.png";
                $rtn_ .= "<img src=$path_pict_sm>";
                $rtn_ .= '</div>';
            }

            if (in_array($widget_scheme, array(1)) && in_array($size, array('1d'))) {
                $rtn .= "<div class=\"block\">
					<div class=\"awe_day\">{$day_of_week}</div>
					{$rtn_}
					<div class=\"awe_temp_min\">{$forecast->min_temp}<span>{$units_display_symbol}</span></div>
					<div class=\"awe_temp_max\">{$forecast->max_temp}<span>{$units_display_symbol}</span></div>
				</div>";
            }

            if (in_array($widget_scheme, array(2,3,4,5,6)) && in_array($size, array('3d','5d'))) {
                $rtn .= "<div class=\"block\">
					<div class=\"awe_day\">{$day_of_week}</div>
					<div class=\"img\"><img src=$path_pict_sm></div>
					<div class=\"awe_data\">{$forecast->min_temp}{$units_display_symbol} / {$forecast->max_temp}{$units_display_symbol}</div>
				</div>";
            }

            if (in_array($size, array('wide'))) {
                $rtn .= "<div class=\"block\">
						<div class=\"awe_day\">{$day_of_week}</div>
						<img src=$path_pict_sm>
						<div class=\"awe_temp\">$forecast->temp<span>{$units_display_symbol}{$units_display}</span></div>
					</div>";
            }

            if ($c == $days_to_show) {
                break;
            }
            $c++;
        }

        $rtn .= "</div><!-- /.weather-forecast-day -->";
        $rtn .= "</div><!-- /.weather-forecast -->";
	}

    if (in_array($size, array('wide'))) {
        $rtn .= "</div>";
    }

    if (in_array($widget_scheme, array(2,3,5)) && in_array($size, array('3d','5d'))) {
        $rtn .= "<div class=\"weather-more-link\"><a href=\"http://www.oplao.com/\" target=\"_blank\" title=\"Free weather\"><img
					src=\"{$mod_url}/img/logo_t1_d3.png\"></a></div>";
    }

    if (in_array($widget_scheme, array(4,6)) && in_array($size, array('3d','5d'))) {
        $rtn .= "<div class=\"weather-more-link\"><a href=\"http://www.oplao.com/\" target=\"_blank\" title=\"Free weather\"><img
					src=\"{$mod_url}/img/logo_t4_d3.png\"></a></div>";
    }

    $rtn .= "</div><!-- /.weather-cover -->";

	return $rtn;
}

// RETURN ERROR
function oplao_weather_error( $msg = false ) {
	$error_handling = get_option('aw-error-handling');
	if (!$error_handling)
		$error_handling = "source";
	if (!$msg)
		$msg = __('No weather information available', 'oplao-weather');

	if ($error_handling == "display-admin") {
		// DISPLAY ADMIN
		if (current_user_can('manage_options')) {
			return "<div class='weather-error'>" . $msg . "</div>";
		}
	} else if ($error_handling == "display-all") {
		// DISPLAY ALL
		return "<div class='weather-error'>" . $msg . "</div>";
	} else {
		return apply_filters('weather_error', "<!-- WEATHER ERROR: " . $msg . " -->");
	}
}

// ENQUEUE ADMIN SCRIPTS
function oplao_weather_admin_scripts( $hook ) {
	if ('widgets.php' != $hook)
		return;
	wp_enqueue_style('jquery');
	wp_enqueue_style('underscore');
	wp_enqueue_script('weather_admin_script', plugin_dir_url(__FILE__) . '/weather-widget-admin.js', array('jquery', 'underscore'));
	wp_localize_script('weather_admin_script', 'awe_script', array(
			'no_owm_city'    => esc_attr(__("No city found in oplao.", 'oplao-weather')),
			'one_city_found' => esc_attr(__('Only one location found. The ID has been set automatically above.', 'oplao-weather')),
			'confirm_city'   => esc_attr(__('Please confirm your city: &nbsp;', 'oplao-weather')),
		)
	);
}

add_action('admin_enqueue_scripts', 'oplao_weather_admin_scripts');

// WIDGET
require_once(dirname(__FILE__) . "/widget.php");

// SETTINGS
require_once(dirname(__FILE__) . "/oplao-weather-settings.php");