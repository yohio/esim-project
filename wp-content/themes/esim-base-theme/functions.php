<?php
/**
 * Twenty Twenty-Five functions and definitions.
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package WordPress
 * @subpackage Twenty_Twenty_Five
 * @since Twenty Twenty-Five 1.0
 */

// Adds theme support for post formats.
if ( ! function_exists( 'twentytwentyfive_post_format_setup' ) ) :
	/**
	 * Adds theme support for post formats.
	 *
	 * @since Twenty Twenty-Five 1.0
	 *
	 * @return void
	 */
	function twentytwentyfive_post_format_setup() {
		add_theme_support( 'post-formats', array( 'aside', 'audio', 'chat', 'gallery', 'image', 'link', 'quote', 'status', 'video' ) );
	}
endif;
add_action( 'after_setup_theme', 'twentytwentyfive_post_format_setup' );

// Enqueues editor-style.css in the editors.
if ( ! function_exists( 'twentytwentyfive_editor_style' ) ) :
	/**
	 * Enqueues editor-style.css in the editors.
	 *
	 * @since Twenty Twenty-Five 1.0
	 *
	 * @return void
	 */
	function twentytwentyfive_editor_style() {
		add_editor_style( get_parent_theme_file_uri( 'assets/css/editor-style.css' ) );
	}
endif;
add_action( 'after_setup_theme', 'twentytwentyfive_editor_style' );

// Enqueues style.css on the front.
if ( ! function_exists( 'twentytwentyfive_enqueue_styles' ) ) :
	/**
	 * Enqueues style.css on the front.
	 *
	 * @since Twenty Twenty-Five 1.0
	 *
	 * @return void
	 */
	function twentytwentyfive_enqueue_styles() {
		wp_enqueue_style(
			'twentytwentyfive-style',
			get_parent_theme_file_uri( 'style.css' ),
			array(),
			wp_get_theme()->get( 'Version' )
		);
	}
endif;
add_action( 'wp_enqueue_scripts', 'twentytwentyfive_enqueue_styles' );

// Registers custom block styles.
if ( ! function_exists( 'twentytwentyfive_block_styles' ) ) :
	/**
	 * Registers custom block styles.
	 *
	 * @since Twenty Twenty-Five 1.0
	 *
	 * @return void
	 */
	function twentytwentyfive_block_styles() {
		register_block_style(
			'core/list',
			array(
				'name'         => 'checkmark-list',
				'label'        => __( 'Checkmark', 'twentytwentyfive' ),
				'inline_style' => '
				ul.is-style-checkmark-list {
					list-style-type: "\2713";
				}

				ul.is-style-checkmark-list li {
					padding-inline-start: 1ch;
				}',
			)
		);
	}
endif;
add_action( 'init', 'twentytwentyfive_block_styles' );

// Registers pattern categories.
if ( ! function_exists( 'twentytwentyfive_pattern_categories' ) ) :
	/**
	 * Registers pattern categories.
	 *
	 * @since Twenty Twenty-Five 1.0
	 *
	 * @return void
	 */
	function twentytwentyfive_pattern_categories() {

		register_block_pattern_category(
			'twentytwentyfive_page',
			array(
				'label'       => __( 'Pages', 'twentytwentyfive' ),
				'description' => __( 'A collection of full page layouts.', 'twentytwentyfive' ),
			)
		);

		register_block_pattern_category(
			'twentytwentyfive_post-format',
			array(
				'label'       => __( 'Post formats', 'twentytwentyfive' ),
				'description' => __( 'A collection of post format patterns.', 'twentytwentyfive' ),
			)
		);
	}
endif;
add_action( 'init', 'twentytwentyfive_pattern_categories' );

// Registers block binding sources.
if ( ! function_exists( 'twentytwentyfive_register_block_bindings' ) ) :
	/**
	 * Registers the post format block binding source.
	 *
	 * @since Twenty Twenty-Five 1.0
	 *
	 * @return void
	 */
	function twentytwentyfive_register_block_bindings() {
		register_block_bindings_source(
			'twentytwentyfive/format',
			array(
				'label'              => _x( 'Post format name', 'Label for the block binding placeholder in the editor', 'twentytwentyfive' ),
				'get_value_callback' => 'twentytwentyfive_format_binding',
			)
		);
	}
endif;
add_action( 'init', 'twentytwentyfive_register_block_bindings' );

// Registers block binding callback function for the post format name.
if ( ! function_exists( 'twentytwentyfive_format_binding' ) ) :
	/**
	 * Callback function for the post format name block binding source.
	 *
	 * @since Twenty Twenty-Five 1.0
	 *
	 * @return string|void Post format name, or nothing if the format is 'standard'.
	 */
	function twentytwentyfive_format_binding() {
		$post_format_slug = get_post_format();

		if ( $post_format_slug && 'standard' !== $post_format_slug ) {
			return get_post_format_string( $post_format_slug );
		}
	}
endif;

// Make Admin notice if JWT Authentication for WP-API plugin is not installed
add_action('admin_notices', 'plugin_missing_error_notice');
function plugin_missing_error_notice() {
	if (!class_exists('Tmeister\Firebase\JWT\JWT')): ?>
        <div class="error">
            <p>JWT Authentication requires the <strong>JWT Authentication for WP-API</strong> plugin activated to handle JWT-based authentication. You can install it from the <a href="https://wordpress.org/plugins/jwt-authentication-for-wp-rest-api/">WordPress plugin directory</a></p>
        </div>
    <?php
	endif;
}

// register custom REST API route for Google JWT auth
add_action('rest_api_init', function () {
    register_rest_route('google-jwt-auth/v1', '/token', array(
        'methods' => 'POST, GET',
        'callback' => 'handle_google_jwt_auth',
        'permission_callback' => '__return_true',
    ));
});

// Handle Google JWT auth
function handle_google_jwt_auth(WP_REST_Request $request) {
    $google_token = $request->get_param('google_token');

	// Check if JWT Authentication for WP-API plugin is installed
	if (!class_exists('Tmeister\Firebase\JWT\JWT')) {
		return new WP_Error('jwt_auth_plugin_missing', __('JWT Authentication for WP-API plugin is not installed'), ['status' => 500]);
	}

    // Validate Google token
	$response = wp_remote_get("https://www.googleapis.com/oauth2/v1/userinfo?access_token={$google_token}");
    if (is_wp_error($response)) {
        return new WP_Error('token_verification_failed', __('Token verification failed'), ['status' => 401]);
    }

	$user_data = json_decode(wp_remote_retrieve_body($response), true);
	if (isset($user_data['error'])) {
        return new WP_Error('token_verification_failed', __('Token verification failed'), ['status' => 401]);
    }

	if (!isset($user_data['email'])) {
        return new WP_Error('token_verification_failed', __('Invalid response from google API'), ['status' => 401]);
    }

	// Extract user info from the response
	$email = $user_data['email'];
	$first_name = isset($user_data['given_name']) ? $user_data['given_name'] : '';
	$last_name = isset($user_data['family_name']) ? $user_data['family_name'] : '';
	$display_name = isset($user_data['name']) ? $user_data['name'] : '';

	// Check if the user already exists
	$user = get_user_by('email', $email);
	if (!$user) {
		// Create a new user if not exists
		$user_id = wp_create_user($email, wp_generate_password(), $email);
		wp_update_user([
			'ID' => $user_id,
			'first_name' => $first_name,
			'last_name' => $last_name,
			'display_name' => $display_name,
		]);
		$user = get_user_by('id', $user_id);
	}

    // Generate JWT token and pass the data as response
	$data = [
		'token'             => generate_jwt($user),
		'user_email'        => $user->user_email,
		'user_nicename'     => $user->user_nicename,
		'user_display_name' => $user->display_name,
	];

	return new WP_REST_Response(
		apply_filters( 'jwt_auth_token_before_dispatch', $data, $user ),
		200
	);
}

// Generate JWT
function generate_jwt($user) {
	$issuedAt = time();
	$notBefore = apply_filters( 'jwt_auth_not_before', $issuedAt, $issuedAt );
	$expire = apply_filters( 'jwt_auth_expire', $issuedAt + ( DAY_IN_SECONDS * 7 ), $issuedAt );
	$token = [
		'iss'  => get_bloginfo( 'url' ),
		'iat'  => $issuedAt,
		'nbf'  => $notBefore,
		'exp'  => $expire,
		'data' => [
			'user' => [
				'id' => $user->ID,
			],
		],
	];

	$secret_key = defined( 'JWT_AUTH_SECRET_KEY' ) ? JWT_AUTH_SECRET_KEY : false;
	$jwt = "";

	// Use Firebase JWT library if available
	if (class_exists('Tmeister\Firebase\JWT\JWT')) {
		$jwt = Tmeister\Firebase\JWT\JWT::encode(
			apply_filters('jwt_auth_token_before_sign', $token, $user),
			$secret_key,
			apply_filters('jwt_auth_algorithm', 'HS256')
		);
	}

    return $jwt;
}

// Add custom fields to REST API response
add_action('rest_api_init', function() {
    register_rest_field('user', 'meta', [
        'get_callback' => function($user) {
			$user_info = get_userdata($user['id']);
			error_log(print_r($user_info, true));
            return [
                'first_name' => $user_info->first_name,
                'last_name' => $user_info->last_name,
				'nickname' => $user_info->nickname,
				'roles' => $user_info->roles,
				'avatar' => get_avatar_url($user_info->ID),
				'capabilities' => $user_info->allcaps,
				'extra_capabilities' => $user_info->caps
            ];
        },
        'update_callback' => null,
        'schema' => null,
    ]);
});


// Add CORS headers to the REST API responses
add_action('init', 'add_cors_headers');
function add_cors_headers() {
    // Allow request from NextJs app
    if (isset($_SERVER['HTTP_ORIGIN'])) {
        $allowed_origins = ['http://localhost:3000', 'http://127.0.0.1:3000', 'http://localhost', 'https://localhost'];
        
        if (in_array($_SERVER['HTTP_ORIGIN'], $allowed_origins)) {
            header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
        }
    }

    header('Access-Control-Allow-Methods: GET, POST, OPTIONS, DELETE, PUT');
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Headers: Authorization, Content-Type');

    // Handle preflight requests
    if ('OPTIONS' == $_SERVER['REQUEST_METHOD']) {
        status_header(200);
        exit();
    }
}

// ******** Account ******** //
add_action( 'graphql_register_types', 'wpgraphql_account_register_types' );

function wpgraphql_account_register_types() {
	register_graphql_object_type( 'Account', [
		'description' => __( 'Custom Accounts from wp_jet_cct_accounts table', 'global1sim' ),
		'interfaces' => [ 'Node' ],
		'fields' => [
			'_ID' => [
				'type' => 'ID',
				'description' => __( 'The ID of the Account', 'global1sim' ),
				'resolve' => function($source) {
					return !empty($source->_ID) ? (string) $source->_ID : null;
				}
			],'account_name' => [
				'type' => 'String',
				'description' => __('The Name of the Account', 'global1sim'),
				'resolve' => function($source) {
					return !empty($source->account_name) ? (string) $source->account_name : null;
				}
			],
			'account_owner' => [
				'type' => 'String',
				'description' => __( 'The Owner of the Account', 'global1sim' ),
				'resolve' => function($source) {
					return !empty($source->account_name) ? (string) $source->account_name : null;
				}
			],
			'account_balance' => [
				'type' => 'String',
				'description' => __( 'The Balance value of the Account', 'global1sim' ),
				'resolve' => function($source) {
					return !empty($source->account_balance) ? (string) $source->account_balance : null;
				}
			]
		]
	] );

	register_graphql_connection([
		'fromType' => 'RootQuery',
		'toType' => 'Account',
		'fromFieldName' => 'account',
		'resolve' => function( $root, $args, $context, $info ) {
			$resolver = new AccountConnectionResolver( $root, $args, $context, $info );
			return $resolver->get_connection();
		}
	]);

}

// ******** ESIM ******** //
add_action( 'graphql_register_types', 'wpgraphql_esim_register_types' );

function wpgraphql_esim_register_types() {
	register_graphql_object_type( 'eSim', [
		'description' => __( 'eSim for an account', 'global1sim' ),
		'interfaces' => [ 'Node' ],
		'fields' => [
			'_ID' => [
				'type' => 'ID',
				'description' => __( 'The ID of the eSim', 'global1sim' ),
				'resolve' => function($source) {
					return !empty($source->_ID) ? (string) $source->_ID : null;
				}
			],'iccid' => [
				'type' => 'String',
				'description' => __( 'The ICCID of the eSim', 'global1sim' ),
				'resolve' => function($source) {
					return !empty($source->iccid) ? (string) $source->iccid : null;
				}
			],
			'msisdn' => [
				'type' => 'String',
				'description' => __( 'The MSISDN of the eSim', 'global1sim' ),
				'resolve' => function($source) {
					return !empty($source->msisdn) ? (string) $source->msisdn : null;
				}
			],
			'assigned_account' => [
				'type' => 'String',
				'description' => __( 'The Assigned Account id of the eSim', 'global1sim' ),
				'resolve' => function($source) {
					return !empty($source->assigned_account) ? (string) $source->assigned_account : null;
				}
			],
			'balance' => [
				'type' => 'String',
				'description' => __( 'The balance value of the eSim', 'global1sim' ),
				'resolve' => function($source) {
					return !empty($source->balance) ? (string) $source->balance : null;
				}
			],
			'status' => [
				'type' => 'String',
				'description' => __( 'The status of the eSim', 'global1sim' ),
				'resolve' => function($source) {
					return !empty($source->status) ? (string) $source->status : null;
				}
			]
		]
	] );

	register_graphql_connection([
		'fromType' => 'RootQuery',
		'toType' => 'eSim',
		'fromFieldName' => 'eSim',
		'resolve' => function( $root, $args, $context, $info ) {
			$resolver = new ESIMConnectionResolver( $root, $args, $context, $info );
			return $resolver->get_connection();
		}
	]);

	register_graphql_connection([
		'fromType' => 'eSim',
		'toType' => 'Account',
		'fromFieldName' => 'account',
		'oneToOne' => true,
		'resolve' => function( $root, $args, $context, $info ) {
			$resolver = new AccountConnectionResolver( $root, $args, $context, $info );
			$resolver->set_query_arg( 'include', $root->id );
			return $resolver->one_to_one()->get_connection();
		}
	]);

	register_graphql_connection([
		'fromType' => 'Account',
		'toType' => 'eSim',
		'fromFieldName' => 'eSim',
		'oneToOne' => false, // Remove this or set to false for list fields
		'resolve' => function( $root, $args, $context, $info ) {
        $resolver = new ESIMConnectionResolver( $root, $args, $context, $info );
        
			// Make sure we have a valid account owner ID
			$account_id = !empty($root->_ID) ? $root->_ID : null;
			
			if ($account_id) {
				$resolver->set_query_arg( 'assigned_account', $account_id );
			}
			
			return $resolver->get_connection();
		}
	]);

}

add_action( 'graphql_init', function() {

	/**
	 * Class AccountLoader
	 */
	class AccountLoader extends \WPGraphQL\Data\Loader\AbstractDataLoader {

		/**
		 * Given an array of one or more keys (ids) load the corresponding Accounts
		 *
		 * @param array $keys Array of keys to identify nodes by
		 *
		 * @return array|null
		 */
		public function loadKeys( array $keys ): ?array {
			if ( empty( $keys ) ) {
				return null;
			}

			global $wpdb;

			
			$table_name = $wpdb->prefix . 'jet_cct_accounts';
			$placeholders = implode(',', array_fill(0, count($keys), '%d'));
			$query = $wpdb->prepare("SELECT * FROM {$table_name} WHERE _ID IN ($placeholders)", $keys);
			$results    = $wpdb->get_results($query);
			
			if ( empty( $results ) ) {
				return null;
			}

			$AccountsById = [];
			foreach ( $results as $result ) {
				$result->__typename = 'Account';

				$AccountsById[ $result->_ID ] = $result;
			}

			

			$orderedAccounts = [];
			foreach ( $keys as $key ) {
				if ( array_key_exists( $key, $AccountsById ) ) {
					$orderedAccounts[ $key ] = $AccountsById[ $key ];
				}
			}

			return $orderedAccounts;

		}
	}

	add_filter( 'graphql_data_loaders', function( $loaders, $context ) {
		$loaders['Account'] = new AccountLoader( $context );
		return $loaders;
	}, 10, 2 );

	add_filter( 'graphql_resolve_node_type', function( $type, $node ) {
		return $node->__typename ?? $type;
	}, 10, 2 );

	class AccountConnectionResolver extends \WPGraphQL\Data\Connection\AbstractConnectionResolver {

		public function get_loader_name(): string {
			return 'Account';
		}

		public function get_query_args(): array {
			return $this->args;
		}

		public function get_query(): array|bool|null {
			global $wpdb;
			$table_name = $wpdb->prefix . 'jet_cct_accounts';
			$query = "SELECT _ID FROM {$table_name}";
			$ids_array = $wpdb->get_results($query);
			return !empty($ids_array) ? array_values(array_column($ids_array, '_ID')) : [];
		}

		// This determines how to get IDs. In our case, the query itself returns IDs
		// But sometimes queries, such as WP_Query might return an object with IDs as a property (i.e. $wp_query->posts )
		public function get_ids(): array|bool|null {
			return $this->get_query();
		}

		public function is_valid_offset( $offset ): bool {
			return true;
		}

		// This gives a chance to validate that the Model being resolved is valid.
		// We're skipping this and always saying the data is valid, but this is a good
		// place to add some validation before returning data
		public function is_valid_model( $model ): bool {
			return true;
		}

		// You can implement logic here to determine whether or not to execute.
		// for example, if the data is private you could set to false if the user is not logged in, etc
		public function should_execute(): bool {
			return true;
		}

	}

	/**
	 * Class ESIMLoader
	 */
	class ESIMLoader extends \WPGraphQL\Data\Loader\AbstractDataLoader {

		/**
		 * Given an array of one or more keys (ids) load the corresponding eSims
		 *
		 * @param array $keys Array of keys to identify nodes by
		 *
		 * @return array|null
		 */
		public function loadKeys( array $keys ): ?array {
			if ( empty( $keys ) ) {
				return []; // Return empty array instead of null
			}

			global $wpdb;

			$table_name = $wpdb->prefix . 'jet_cct_esim';
			$placeholders = implode(',', array_fill(0, count($keys), '%d'));
			$query = $wpdb->prepare("SELECT * FROM {$table_name} WHERE _ID IN ($placeholders)", $keys);
			$results    = $wpdb->get_results($query);

			if ( empty( $results ) ) {
				return []; // Return empty array instead of null
			}

			$eSimsById = [];
			foreach ( $results as $result ) {
				$result->__typename = 'eSim';

				$eSimsById[ $result->_ID ] = $result;
			}

			$orderedESIMS = [];
			foreach ( $keys as $key ) {
				if ( array_key_exists( $key, $eSimsById ) ) {
					$orderedESIMS[ $key ] = $eSimsById[ $key ];
				}
			}

			return $orderedESIMS;

		}
	}

	add_filter( 'graphql_data_loaders', function( $loaders, $context ) {
		$loaders['eSim'] = new ESIMLoader( $context );
		return $loaders;
	}, 10, 2 );

	add_filter( 'graphql_resolve_node_type', function( $type, $node ) {
		return $node->__typename ?? $type;
	}, 10, 2 );

	class ESIMConnectionResolver extends \WPGraphQL\Data\Connection\AbstractConnectionResolver {

		public function get_loader_name(): string {
			return 'eSim';
		}

		public function get_query_args(): array {
			return $this->args;
		}

		public function get_query(): array|bool|null {
			global $wpdb;

			
			$current_user_id = get_current_user_id();
			$account_id = $this->query_args['assigned_account'] ?? $current_user_id;
			$table_name = $wpdb->prefix . 'jet_cct_esim';
			$query = $wpdb->prepare("SELECT _ID FROM {$table_name} WHERE assigned_account=$account_id");
			$ids_array = $wpdb->get_results($query);

			return ! empty( $ids_array ) ? array_values( array_column( $ids_array, '_ID' ) ) : [];
		}

		// This determines how to get IDs. In our case, the query itself returns IDs
		// But sometimes queries, such as WP_Query might return an object with IDs as a property (i.e. $wp_query->posts )
		public function get_ids(): array|bool|null {
			return $this->get_query();
		}

		public function is_valid_offset( $offset ): bool {
			return true;
		}

		// This gives a chance to validate that the Model being resolved is valid.
		// We're skipping this and always saying the data is valid, but this is a good
		// place to add some validation before returning data
		public function is_valid_model( $model ): bool {
			return true;
		}

		// You can implement logic here to determine whether or not to execute.
		// for example, if the data is private you could set to false if the user is not logged in, etc
		public function should_execute(): bool {
			return true;
		}

	}

});
