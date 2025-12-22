<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
	exit;
}

add_action('admin_enqueue_scripts', function (
	$hook_suffix
) {
	if ($hook_suffix !== 'settings_page_bb-app-settings') {
		return;
	}

	wp_register_script('bb-app-settings', false);
	wp_enqueue_script('bb-app-settings');

	$settings_page_inline_js = 'document.addEventListener("DOMContentLoaded", function() {
		const blur = (element) => {
			element.style.textShadow = "0 0 8px rgba(0, 0, 0, 0.7)";
			element.style.color = "transparent";
		};
	
		const focus = (element) => {
			element.style.textShadow = "none";
			element.style.color = "initial";
		};
	
		const textarea = document.getElementById("bb_app_apns_private_key");
		const fileInput = document.getElementById("bb_app_apns_private_key_file");
	
		if (textarea) {
			textarea.addEventListener("focus", (event) => {
				focus(event.target);
			});
	
			textarea.addEventListener("blur", (event) => {
				blur(event.target);
			});
	
			blur(textarea);
		}
	
		if (fileInput) {
			fileInput.addEventListener("change", (event) => {
				const file = event.target.files[0];
	
				if (!file)
					return;
	
				const reader = new FileReader();
				reader.onload = (e) => textarea.value = e.target.result;
				reader.readAsText(file);
			});
		}
	});';

	wp_add_inline_script('bb-app-settings', $settings_page_inline_js);
});

add_action('admin_menu', function () {
	add_options_page(
		__('BbApp Settings', 'thebbapp'),
		__('BbApp', 'thebbapp'),
		'manage_options',
		'bb-app-settings',
		'bb_app_render_settings_page'
	);
});

add_action('admin_init', function () {
	register_setting('bb_app_settings', 'bb_app_content_source', [
		'type' => 'string',

		'sanitize_callback' => function (
			$value
		) {
			$allowed = ['wordpress', 'bbpress'];
			$value = is_string($value) ? strtolower($value) : 'wordpress';
			return in_array($value, $allowed, true) ? $value : 'wordpress';
		},

		'default' => 'wordpress'
	]);

	register_setting('bb_app_settings', 'bb_app_apns_team_id', [
		'type' => 'string',
		'sanitize_callback' => 'sanitize_text_field',
		'default' => '',
	]);

	register_setting('bb_app_settings', 'bb_app_apns_key_id', [
		'type' => 'string',
		'sanitize_callback' => 'sanitize_text_field',
		'default' => '',
	]);

	register_setting('bb_app_settings', 'bb_app_apns_private_key', [
		'type' => 'string',
		'sanitize_callback' => function (
			$value
		) {
			return is_string($value) ? trim($value) : '';
		},
		'default' => '',
	]);

	register_setting('bb_app_settings', 'bb_app_apns_topic', [
		'type' => 'string',
		'sanitize_callback' => 'sanitize_text_field',
		'default' => '',
	]);

	register_setting('bb_app_settings', 'bb_app_apns_sandbox', [
		'type' => 'boolean',
		'sanitize_callback' => function (
			$value
		) {
			return (bool) $value;
		},
		'default' => false,
	]);

	add_settings_section('bb_app_section_general', __('General', 'thebbapp'), '__return_false', 'bb-app-settings');
	add_settings_section('bb_app_section_bbpress', __('bbPress', 'thebbapp'), '__return_false', 'bb-app-settings');
	add_settings_section('bb_app_section_wordpress', __('WordPress', 'thebbapp'), '__return_false', 'bb-app-settings');
	add_settings_section('bb_app_section_apns', __('Apple Push Notification Service', 'thebbapp'), '__return_false', 'bb-app-settings');
	add_settings_section('bb_app_section_fcm', __('Firebase Cloud Messaging', 'thebbapp'), '__return_false', 'bb-app-settings');

	register_setting('bb_app_settings', 'bb_app_ios_app_id', [
		'type' => 'string',
		'sanitize_callback' => 'sanitize_text_field',
		'default' => '',
	]);

	register_setting('bb_app_settings', 'bb_app_android_app_id', [
		'type' => 'string',
		'sanitize_callback' => 'sanitize_text_field',
		'default' => '',
	]);

	add_settings_field('bb_app_content_source', __('Content source', 'thebbapp'), function () {
		$current = (string) get_option('bb_app_content_source', 'wordpress');
		echo '<select name="bb_app_content_source">';

		$content_sources = [
			'bbpress' => __('bbPress (Forums, Topics, Replies)', 'thebbapp'),
			'wordpress' => __('WordPress (Categories, Posts, Comments)', 'thebbapp')
		];

		foreach ($content_sources as $value => $label) {
			$selected = selected($current, $value, false);
			echo '<option value="' . esc_attr($value) . '" ' . $selected . '>' . esc_html($label) . '</option>';
		}

		echo '</select>';
	}, 'bb-app-settings', 'bb_app_section_general');

	register_setting('bb_app_settings', 'bb_app_bbpress_root_section_id', [
		'sanitize_callback' => function (
			$value
		) {
			$id = (int) $value;
			return max($id, 0);
		},
		'type' => 'integer',
		'default' => 0,
	]);

	register_setting('bb_app_settings', 'bb_app_wordpress_root_section_id', [
		'sanitize_callback' => function (
			$value
		) {
			$id = (int) $value;
			return max($id, 0);
		},
		'type' => 'integer',
		'default' => 0,
	]);

	add_settings_field('bb_app_bbpress_root_section_id', __('Root forum', 'thebbapp'), function () {
		$current = (string) get_option('bb_app_bbpress_root_section_id', '0');
		echo '<select name="bb_app_bbpress_root_section_id">';
		echo '<option value="0" ' . selected($current, '0', false) . '>' . esc_html__('None', 'thebbapp') . '</option>';

		$forums = get_posts([
			'post_type' => 'forum',
			'posts_per_page' => -1,
			'post_status' => ['publish', 'private'],
			'orderby' => 'title',
			'order' => 'ASC'
		]);

		foreach ($forums as $forum) {
			$option_value = (string) $forum->ID;
			$label = get_the_title($forum);
			echo '<option value="' . esc_attr($option_value) . '" ' . selected($current, $option_value, false) . '>' . esc_html($label) . '</option>';
		}

		echo '</select>';
	}, 'bb-app-settings', 'bb_app_section_bbpress');

	add_settings_field('bb_app_wordpress_root_section_id', __('Root category', 'thebbapp'), function (
	) {
		$current = (string) get_option('bb_app_wordpress_root_section_id', '0');
		echo '<select name="bb_app_wordpress_root_section_id">';
		echo '<option value="0" ' . selected($current, '0', false) . '>' . esc_html__('None', 'thebbapp') . '</option>';

		$terms = get_terms([
			'taxonomy' => 'category',
			'hide_empty' => false
		]);

		if (!is_wp_error($terms)) {
			foreach ($terms as $term) {
				$option_value = (string) $term->term_id;
				$label = $term->name;
				echo '<option value="' . esc_attr($option_value) . '" ' . selected($current, $option_value, false) . '>' . esc_html($label) . '</option>';
			}
		}

		echo '</select>';
	}, 'bb-app-settings', 'bb_app_section_wordpress');

	add_settings_field('bb_app_apns_team_id', __('Team ID', 'thebbapp'), function () {
		$value = esc_attr((string) get_option('bb_app_apns_team_id', ''));
		echo '<input type="text" name="bb_app_apns_team_id" value="' . $value . '" placeholder="A1B2C3D4E5" class="regular-text" />';
	}, 'bb-app-settings', 'bb_app_section_apns');

	add_settings_field('bb_app_apns_key_id', __('Key ID', 'thebbapp'), function () {
		$value = esc_attr((string) get_option('bb_app_apns_key_id', ''));
		echo '<input type="text" name="bb_app_apns_key_id" value="' . $value . '" placeholder="ABC1DEF11G" class="regular-text" />';
	}, 'bb-app-settings', 'bb_app_section_apns');

	add_settings_field('bb_app_apns_private_key', __('Private Key (.p8)', 'thebbapp'), function () {
		$value = esc_textarea((string) get_option('bb_app_apns_private_key', ''));

		echo '<textarea id="bb_app_apns_private_key" name="bb_app_apns_private_key" rows="8" cols="50" class="large-text code">' . $value . '</textarea>';
		echo '<input id="bb_app_apns_private_key_file" type="file" accept=".p8" />';
	}, 'bb-app-settings', 'bb_app_section_apns');

	add_settings_field('bb_app_apns_topic', __('Bundle ID', 'thebbapp'), function () {
		$value = esc_attr((string) get_option('bb_app_apns_topic', ''));
		echo '<input type="text" name="bb_app_apns_topic" value="' . $value . '" placeholder="com.example.app" class="regular-text" />';
	}, 'bb-app-settings', 'bb_app_section_apns');

	add_settings_field('bb_app_apns_sandbox', __('Use Sandbox', 'thebbapp'), function () {
		$checked = get_option('bb_app_apns_sandbox', false) ? 'checked' : '';
		echo '<label><input type="checkbox" name="bb_app_apns_sandbox" value="1" ' . $checked . ' /> ' . esc_html__('Send to sandbox environment', 'thebbapp') . '</label>';
	}, 'bb-app-settings', 'bb_app_section_apns');

	add_settings_field('bb_app_ios_app_id', __('iOS App Store ID', 'thebbapp'), function () {
		$value = esc_attr((string) get_option('bb_app_ios_app_id', ''));
		echo '<input type="text" name="bb_app_ios_app_id" value="' . $value . '" class="regular-text" placeholder="123456789" />';
	}, 'bb-app-settings', 'bb_app_section_apns');

	register_setting('bb_app_settings', 'bb_app_fcm_server_key', [
		'type' => 'string',
		'sanitize_callback' => 'sanitize_text_field',
		'default' => '',
	]);

	add_settings_field('bb_app_fcm_server_key', __('Server key', 'thebbapp'), function () {
		$value = esc_attr((string) get_option('bb_app_fcm_server_key', ''));
		echo '<input type="password" name="bb_app_fcm_server_key" value="' . $value . '" class="regular-text" />';
	}, 'bb-app-settings', 'bb_app_section_fcm');

	add_settings_field('bb_app_android_app_id', __('App ID', 'thebbapp'), function () {
		$value = esc_attr((string) get_option('bb_app_android_app_id', ''));
		echo '<input type="text" name="bb_app_android_app_id" value="' . $value . '" class="regular-text" placeholder="com.example.app" />';
	}, 'bb-app-settings', 'bb_app_section_fcm');
});

function bb_app_render_settings_page(): void {
	if (!current_user_can('manage_options')) {
		return;
	}
	echo '<div class="wrap">';
	echo '<h1>' . esc_html__('BbApp Settings', 'thebbapp') . '</h1>';
	echo '<form action="options.php" method="post">';
	settings_fields('bb_app_settings');
	do_settings_sections('bb-app-settings');
	submit_button();
	echo '</form>';
	echo '</div>';
}
