<?php

// OPLAO WEATHER WIDGET, WIDGET CLASS, SO MANY WIDGETS
class OplaoWeatherWidget extends WP_Widget
{
	function OplaoWeatherWidget() {
		parent::__construct(false, $name = 'Oplao Weather Widget');
	}

	function widget( $args, $instance ) {
		extract($args);

		$location = isset($instance['location']) ? $instance['location'] : false;
		$widget_title = isset($instance['widget_title']) ? $instance['widget_title'] : false;
		$units = isset($instance['units']) ? $instance['units'] : false;
		$size = isset($instance['size']) ? $instance['size'] : false;
		$forecast_days = isset($instance['forecast_days']) ? $instance['forecast_days'] : false;
		$widget_scheme = isset($instance['scheme']) ? $instance['scheme'] : 1;
		$show_stats = (isset($instance['show_stats']) AND $instance['show_stats'] == 1) ? 1 : 0;
		$location_case = isset($instance['location_case']) ? esc_attr($instance['location_case']) : 3;
		//$show_link = (isset($instance['show_link']) AND $instance['show_link'] == 1) ? 1 : 0;
		$show_link = 1;
		$custom_bg_color = isset($instance['custom_bg_color']) ? $instance['custom_bg_color'] : false;
		$text_color = isset($instance['text_color']) ? $instance['text_color'] : "#20a5d6";
		$coordinates = isset($instance['coordinates']) ? $instance['coordinates'] : false;

		echo $before_widget;
		if ($widget_title != "")
			echo $before_title . $widget_title . $after_title;
		echo oplao_weather_logic(array(
			'location'        => $location,
			'size'            => $size,
			'units'           => $units,
			'forecast_days'   => $forecast_days,
			'scheme'          => $widget_scheme,
			'show_stats'      => $show_stats,
			'show_link'       => $show_link,
			'custom_bg_color' => $custom_bg_color,
			'text_color'      => $text_color,
			'location_case'   => $location_case,
			'coordinates'	  => $coordinates
		));
		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['location'] = strip_tags($new_instance['location']);
		$instance['widget_title'] = strip_tags($new_instance['widget_title']);
		$instance['units'] = strip_tags($new_instance['units']);
		$instance['size'] = strip_tags($new_instance['size']);
		$instance['forecast_days'] = strip_tags($new_instance['forecast_days']);
		$instance['scheme'] = strip_tags($new_instance['scheme']);
		$instance['custom_bg_color'] = strip_tags($new_instance['custom_bg_color']);
		$instance['text_color'] = strip_tags($new_instance['text_color']);
		$instance['show_stats'] = (isset($new_instance['show_stats']) AND $new_instance['show_stats'] == 1) ? 1 : 0;
		$instance['location_case'] = isset($new_instance['location_case']) ? esc_attr($new_instance['location_case']) : 3;
		//$instance['show_link'] = (isset($new_instance['show_link']) AND $new_instance['show_link'] == 1) ? 1 : 0;
		$instance['show_link'] = 1;
		$instance['coordinates'] = strip_tags($new_instance['coordinates']);

		return $instance;
	}

	function form( $instance ) {
		global $oplao_weather_sizes;
		$location = isset($instance['location']) ? esc_attr($instance['location']) : "";
		$widget_title = isset($instance['widget_title']) ? esc_attr($instance['widget_title']) : "";
		$selected_size = isset($instance['size']) ? esc_attr($instance['size']) : "tall";
		$units = (isset($instance['units']) AND strtoupper($instance['units']) == "F") ? "F" : "C";
		$forecast_days = isset($instance['forecast_days']) ? esc_attr($instance['forecast_days']) : 3;
		$show_stats = (isset($instance['show_stats']) AND $instance['show_stats'] == 1) ? 1 : 0;
		//$show_link = (isset($instance['show_link']) AND $instance['show_link'] == 1) ? 1 : 0;
		$show_link = 1;
		$custom_bg_color = isset($instance['custom_bg_color']) ? esc_attr($instance['custom_bg_color']) : "";
		$text_color = isset($instance['text_color']) ? esc_attr($instance['text_color']) : "#ffffff";
		$widget_scheme = isset($instance['scheme']) ? esc_attr($instance['scheme']) : 1;
		$location_case = isset($instance['location_case']) ? esc_attr($instance['location_case']) : 3;
		$coordinates = isset($instance['coordinates']) ? esc_attr($instance['coordinates']) : "";

		$appid = "fb901751d7894f6e8b6113816162011";
		$wp_theme = wp_get_theme();
		$wp_theme = $wp_theme->get('TextDomain');
		?>

		<style>
			.awe-suggest {
				font-size: 0.9em;
				border-bottom: solid 1px #ccc;
				padding: 5px 1px;
				font-weight: bold;
			}

			.awe-size-options {
				padding: 1px 10px;
				background: #efefef;
			}
		</style>

		<script type="text/javascript">

			function GetLocate(str) {
				if(str.length >= 3) {
					jQuery.ajaxSetup({async: false});
					jQuery('.countryList').html("");
					jQuery.getJSON("https://bd.oplao.com/geoLocation/find.json?nameStarts=" + str + "&max=5", function (data) {
						jQuery.each(data, function (index, element) {
							var str_city = element.name;
							var str_country = element.countryName;
							str_city = str_city.replace("'", "\\'");
							str_country = str_country.replace("'", "\\'");
							jQuery('.countryList').append(jQuery('<div><a href="javascript:;" onclick="SetData(\'' + str_country.toString() + '\',\'' + str_city.toString() + '\',\'' + element.lat.toString() + '\',\'' + element.lng.toString() + '\');">' + element.name + ', ' + element.countryName + '</a></div>'));
						});
					});
				}
			}

			function SetData(country,city,lat,lng) {
				jQuery(".awe-location-search-field-oplao").val(city + ", " + country);
				jQuery(".awe-coordinates-field-oplao").val(lat + "," + lng);
				jQuery('.countryList').html("");
			}
		</script>


		<?php if (!$appid) { ?>
			<div style="background: #dc3232; color: #fff; padding: 10px; margin: 10px;">
				<?php
				echo __("As of October 2015 oplao weather requires an APP ID key to access their weather data.", 'oplao-weather');
				echo " <a href='http://www.oplao.com/signup.aspx' target='_blank' style='color: #fff;'>";
				echo __('Get your APPID', 'oplao-weather');
				echo "</a> ";
				echo __("and add it to the new settings page.");
				?>
			</div>
		<?php } ?>
		<p style="display: none;">
			<label for="<?php echo $this->get_field_id('coordinates'); ?>">coordinates
				<input class="widefat awe-coordinates-field-oplao" style="margin-top: 4px;"
					   id="<?php echo $this->get_field_id('coordinates'); ?>" name="<?php echo $this->get_field_name('coordinates'); ?>" type="text" value="<?php echo $coordinates; ?>"/>
			</label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('location'); ?>">
				<?php _e('Search for Your Location:', 'oplao-weather'); ?><br/>
				<small><?php _e('(i.e: Minsk,BY or - London,UK)', 'oplao-weather'); ?></small>
			</label>
			<input data-cityidfield="<?php echo $this->get_field_id('owm_city_id'); ?>" data-unitsfield="<?php echo $this->get_field_id('units'); ?>" class="widefat awe-location-search-field-oplao" style="margin-top: 4px;"
				   id="<?php echo $this->get_field_id('location'); ?>" name="<?php echo $this->get_field_name('location'); ?>" type="text" value="<?php echo $location; ?>" onkeyup="GetLocate(this.value)"/>
		</p>
		<div class="countryList"></div>
		<p>
			<label for="<?php echo $this->get_field_id('units'); ?>"><?php _e('Units:', 'oplao-weather'); ?></label> &nbsp;
			<input id="<?php echo $this->get_field_id('units'); ?>" name="<?php echo $this->get_field_name('units'); ?>" type="radio" value="F" <?php if ($units == "F")
				echo ' checked="checked"'; ?> /> F &nbsp; &nbsp;
			<input id="<?php echo $this->get_field_id('units'); ?>" name="<?php echo $this->get_field_name('units'); ?>" type="radio" value="C" <?php if ($units == "C")
				echo ' checked="checked"'; ?> /> C
		</p>

		<p>
			<label for="<?php echo $this->get_field_id('forecast_days'); ?>"><?php _e('Forecast:', 'oplao-weather'); ?></label>
			<select class="widefat" id="<?php echo $this->get_field_id('forecast_days'); ?>" name="<?php echo $this->get_field_name('forecast_days'); ?>">
				<option value="hide"<?php if ($forecast_days == 'hide')
					echo " selected=\"selected\""; ?>><?php _e('1 Day', 'oplao-weather'); ?>
				</option>
				<option value="3"<?php if ($forecast_days == 3)
					echo " selected=\"selected\""; ?>><?php _e('3 Day', 'oplao-weather'); ?>
				</option>
				<option value="5"<?php if ($forecast_days == 5)
					echo " selected=\"selected\""; ?>><?php _e('5 Day', 'oplao-weather'); ?>
				</option>
				<option value="6"<?php if ($forecast_days == 6)
					echo " selected=\"selected\""; ?>><?php _e('5 Days(wide)', 'oplao-weather'); ?>
				</option>
			</select>
		</p>

		<p class="scheme>
			<label for="<?php echo $this->get_field_id('scheme'); ?>"><?php _e('Sample №:', 'oplao-weather'); ?></label>
		<select class="widefat" id="<?php echo $this->get_field_id('scheme'); ?>" name="<?php echo $this->get_field_name('scheme'); ?>">
			<option value="2"<?php if ($widget_scheme == 2)
                echo " selected=\"selected\""; ?>><?php _e('1 Poly', 'oplao-weather'); ?></option>
			<option value="3"<?php if ($widget_scheme == 3)
                echo " selected=\"selected\""; ?>><?php _e('2 Gold', 'oplao-weather'); ?></option>
			<option value="4"<?php if ($widget_scheme == 4)
                echo " selected=\"selected\""; ?>><?php _e('3 Orange', 'oplao-weather'); ?></option>
			<option value="5"<?php if ($widget_scheme == 5)
                echo " selected=\"selected\""; ?>><?php _e('4 Blue', 'oplao-weather'); ?></option>
			<option value="6"<?php if ($widget_scheme == 6)
                echo " selected=\"selected\""; ?>><?php _e('5 Turquoise', 'oplao-weather'); ?></option>
		</select>
		</p>
		<p class="location-case">
			<label for="<?php echo $this->get_field_id('location_case'); ?>"><?php _e('Show location as:', 'oplao-weather'); ?></label>
		<select class="widefat" id="<?php echo $this->get_field_id('location_case'); ?>" name="<?php echo $this->get_field_name('location_case'); ?>">
			<?php
			$widget_location_case_ids = array(1 => 'City Name, State Name, Country', 2 => 'City Name, State Name', 3 => 'City Name, Country', 4 => 'Only City Name');
			foreach ($widget_location_case_ids as $k => $v) {
				$selected = ($location_case == $k) ? 'selected="selected"' : '';
				echo "<option value=\"{$k}\" {$selected}>{$v}</option>";
			}
			?>
		</select>
		</p>

		<!--<p>-->
		<!--	<input id="--><?php //echo $this->get_field_id('show_link'); ?><!--" name="--><?php //echo $this->get_field_name('show_link'); ?><!--" type="checkbox" value="1" --><?php //if ($show_link)
		//		echo ' checked="checked"'; ?><!-- />-->
		<!--	<label for="--><?php //echo $this->get_field_id('show_link'); ?><!--">--><?php //_e('Link to oplao weather', 'oplao-weather'); ?><!--</label> &nbsp;-->
		<!--</p>-->

		<p>
			<label for="<?php echo $this->get_field_id('widget_title'); ?>"><?php _e('Widget Title: (optional)', 'oplao-weather'); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id('widget_title'); ?>" name="<?php echo $this->get_field_name('widget_title'); ?>" type="text" value="<?php echo $widget_title; ?>"/>
		</p>
		<?php
	}
}

add_action('widgets_init', create_function('', 'return register_widget("OplaoWeatherWidget");'));