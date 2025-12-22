<?php

declare(strict_types=1);

use BbApp\PushService\{
	PushNotificationCoordinator,
	PushQueueService,
	PushSubscriptionService,
	PushTokenService,
	PushTransport\Apple\ApplePushTransport,
	PushTransport\Apple\ApplePushTransportOptions,
	PushTransport\Firebase\FirebasePushTransport,
	PushTransport\Firebase\FirebasePushTransportOptions,
	PushTransportAbstract,
	WordPressBase\WordPressBasePushDatabaseSchema,
	WordPressBase\WordPressBasePushQueueRepository,
	WordPressBase\WordPressBasePushQueueService,
	WordPressBase\WordPressBasePushSubscriptionRepository,
	WordPressBase\WordPressBasePushSubscriptionService,
	WordPressBase\WordPressBasePushTokenService,
	WordPressBase\WordPressBasePushTokenTokenRepository,
	BbPress\BbPressPushSource,
	WordPress\WordPressPushSource
};

use BbApp\ContentSource\{
	WordPress\WordPressContentSource,
	BbPress\BbPressContentSource
};

use BbApp\RestAPI\{
	BbPress\BbPressRESTAPI,
	WordPress\WordPressRESTAPI
};

use BbApp\SmartBanner\{
	SmartBanner,
	Apple\AppleSmartBanner,
	Apple\AppleSmartBannerOptions,
	GooglePlay\GooglePlaySmartBanner,
	GooglePlay\GooglePlaySmartBannerOptions
};

if (!defined('ABSPATH')) {
	exit;
}

if (!function_exists('bb_app_register_activation_hook')) {
	/**
	 * Installs database tables when plugin is activated.
	 */
	function bb_app_register_activation_hook(): void {
		$packages = bb_app_packages();
		$packages->database_schema->install();
	}
}

if (!function_exists('bb_app_register_deactivation_hook')) {
	/**
	 * Removes scheduled tasks when plugin is deactivated.
	 */
	function bb_app_register_deactivation_hook(): void {
		$packages = bb_app_packages();
		$packages->push_queue_service->dealloc();
	}
}

if (!function_exists('bb_app_register_uninstall_hook')) {
	/**
	 * Removes database tables when plugin is uninstalled.
	 */
	function bb_app_register_uninstall_hook(): void {
		$packages = bb_app_packages();
		$packages->database_schema->uninstall();
	}
}

if (!class_exists('BbAppPackages')) {
	/**
	 * Container for all BbApp service instances and dependencies.
	 *
	 * @var PushTokenService $push_token_service
	 * @var PushQueueService $push_queue_service
	 * @var PushSubscriptionService $push_subscription_service
	 */
	class BbAppPackages
	{
		public $push_token_service;
		public $push_queue_service;
		public $push_subscription_service;

		public $content_source;
		public $database_schema;
		public $push_source;
		public $rest_api;

		/**
		 * Initializes all service instances based on configured content source.
		 */
		public function __construct() {
			$option = (string) get_option('bb_app_content_source', 'wordpress');

			$push_token_repository = new WordPressBasePushTokenTokenRepository();
			$push_queue_repository = new WordPressBasePushQueueRepository();
			$push_subscription_repository = new WordPressBasePushSubscriptionRepository();
			$database_schema = new WordPressBasePushDatabaseSchema();

			if ($option === 'wordpress') {
				$content_source = new WordPressContentSource();
				$rest_api = new WordPressRESTAPI();
			} else if ($option === 'bbpress') {
				$content_source = new BbPressContentSource();
				$rest_api = new BbPressRESTAPI();
			} else {
				throw new UnexpectedValueException();
			}

			$push_subscription_service = new WordPressBasePushSubscriptionService(
				$push_subscription_repository,
				$content_source
			);

			$push_token_service = new WordPressBasePushTokenService($push_token_repository);

			if ($option === 'wordpress') {
				$push_source = new WordPressPushSource(
					$push_queue_repository,
					$push_subscription_repository,
					$content_source
				);
			} else if ($option === 'bbpress') {
				$push_source = new BbPressPushSource(
					$push_queue_repository,
					$push_subscription_repository,
					$content_source
				);
			} else {
				throw new UnexpectedValueException();
			}

			$notification_coordinator = new PushNotificationCoordinator(
				$push_token_repository,
				$push_source
			);

			$push_queue_service = new WordPressBasePushQueueService(
				$push_queue_repository,
				$notification_coordinator
			);

			$this->push_subscription_service = $push_subscription_service;
			$this->push_token_service = $push_token_service;
			$this->push_queue_service = $push_queue_service;
			$this->content_source = $content_source;
			$this->database_schema = $database_schema;
			$this->push_source = $push_source;
			$this->rest_api = $rest_api;
		}
	}
}

if (!function_exists('bb_app_packages')) {
	/**
	 * Returns singleton instance of BbApp packages container.
	 */
	function bb_app_packages(): BbAppPackages {
		static $packages = null;

		if ($packages === null) {
			$packages = new BbAppPackages();
		}

		return $packages;
	}
}

if (!function_exists('bb_app_current_guest_id')) {
	/**
	 * Gets or sets the current guest ID for anonymous users.
	 */
	function bb_app_current_guest_id(
		?string $guest_id = null
	): ?string {
		static $value = null;

		if (
			!empty($guest_id) &&
			preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $guest_id)
		) {
			$value = $guest_id;
		}

		return $value;
	}
}

if (!function_exists('bb_app_is_serving_request')) {
	/**
	 * Check whether the current request is within bb-app context.
	 */
	function bb_app_is_serving_request(
		string $user_agent = 'BbApp/1.0',
		string $param = 'BbApp'
	): bool {
		return !empty($_GET[$param]) ||
			(mb_stristr($_SERVER['HTTP_USER_AGENT'] ?: uniqid(), $user_agent) !== false);
	}
}

if (!function_exists('bb_app_rest_api_init')) {
	/**
	 * Registers REST API routes and fields on rest_api_init hook.
	 */
	function bb_app_rest_api_init(): void {
		$packages = bb_app_packages();
		$packages->content_source->register();
		$packages->push_source->register();
		$packages->push_subscription_service->register();
		$packages->push_token_service->register();
		$packages->rest_api->register();
	}
}

if (!function_exists('bb_app_init')) {
	/**
	 * Initializes the BbApp plugin, registering hooks and configuring services.
	 */
	function bb_app_init(): void {
		if (bb_app_is_serving_request()) {
			if (isset($_GET['auth_type']) && $_GET['auth_type'] === 'basic') {
				$auth = new BbApp\WPBasicAuth\Auth();
				$auth->add_filters();
			}

			add_action('rest_api_init', 'bb_app_rest_api_init');
		}

		add_action('wp_head', 'bb_app_smart_banner_output', 5);

		$packages = bb_app_packages();
		$packages->content_source->init();
		$packages->push_subscription_service->init();
		$packages->push_token_service->init();
		$packages->push_queue_service->init();
		$packages->rest_api->init();

		if (
			($team_id = (string) get_option('bb_app_apns_team_id', '')) &&
			($key_id = (string) get_option('bb_app_apns_key_id', '')) &&
			($private_key = (string) get_option('bb_app_apns_private_key', '')) &&
			($bundle_id = (string) get_option('bb_app_apns_topic', ''))
		) {
			PushTransportAbstract::register(new ApplePushTransport(
				$packages->content_source,

				new ApplePushTransportOptions(
					$team_id,
					$key_id,
					$private_key,
					$bundle_id,
					(bool) get_option('bb_app_apns_sandbox')
				)
			));
		}

		if (($server_key = (string) get_option('bb_app_fcm_server_key', ''))) {
			PushTransportAbstract::register(new FirebasePushTransport(
				$packages->content_source,
				new FirebasePushTransportOptions($server_key)
			));
		}
	}
}

if (!function_exists('bb_app_smart_banner_output')) {
	/**
	 * Outputs smart banner meta tags for iOS and Android app deep linking.
	 */
	function bb_app_smart_banner_output(): void {
		global $post;

		if (!is_singular() || !isset($post) || !($post instanceof WP_Post)) {
			return;
		}

		$packages = bb_app_packages();
		$entity_types = $packages->content_source->get_entity_types();
		$permalink = get_permalink($post);
		$appleAppStoreId = (string) get_option('bb_app_ios_app_id', '');
		$androidPackageName = (string) get_option('bb_app_android_app_id', '');
		$implementations = [];
		$deep_link_path = null;

		if (!empty($appleAppStoreId)) {
			$implementations[] = new AppleSmartBanner(new AppleSmartBannerOptions(
				$appleAppStoreId
			));
		}

		if (!empty($androidPackageName)) {
			$implementations[] = new GooglePlaySmartBanner(new GooglePlaySmartBannerOptions(
				$androidPackageName
			));
		}

		if (in_array($post->post_type, $entity_types, true) && !empty($permalink) && is_string($permalink)) {
			$deep_link_path = $packages->content_source->get_content_path(
				array_search($post->post_type, $entity_types),
				$post->ID
			);
		}

		print SmartBanner::generate(
			$implementations,
			$deep_link_path,
			$permalink
		);
	}
}
