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

add_action('graphql_register_types', function () {
    register_graphql_object_type('Account', [
        'description' => __('Custom Accounts from jet_cct_accounts table', 'global1sim'),
        'fields'      => [
            '_ID'            => ['type' => 'ID'],
            'account_name'   => ['type' => 'String'],
            'account_owner'  => ['type' => 'String'],
            'account_balance'=> ['type' => 'String']
        ],
    ]);

    register_graphql_field('RootQuery', 'accounts', [
        'type'        => ['list_of' => 'Account'],
        'description' => __('Get list of accounts', 'global1sim'),
        'resolve'     => function () {
            global $wpdb;
            $table_name = $wpdb->prefix . 'jet_cct_accounts';
            $results    = $wpdb->get_results("SELECT * FROM {$table_name}", ARRAY_A);

            return array_map(function ($account) {
                return [
                    '_ID'            => $account['_ID'],
                    'account_name'   => $account['account_name'],
                    'account_owner'  => $account['account_owner'],
                    'account_balance'=> $account['account_balance']
                ];
            }, $results);
        }
    ]);
});

// Hook into WPGraphQL as it builds the Schema
add_action( 'graphql_register_types', 'wpgraphql_esim_register_types' );

function wpgraphql_esim_register_types() {

register_graphql_connection([
	// The GraphQL Type that will have a field added to it to query a connection from
	'fromType' => 'RootQuery',
	// The GraphQL Type the connection will return Nodes of. This type MUST implement the "Node" interface
	'toType' => 'eSim',
	// The field name to represent the connection on the "from" Type
	'fromFieldName' => 'eSim',
	// How to resolve the connection. For now we will return null, but will visit this below.
	'resolve' => function( $root, $args, $context, $info ) {
		// we will revisit this shortly
		return null;
	} 
	]);

  // Register the GraphQL Object Type to the Schema
  register_graphql_object_type( 'eSim', [
    // Be sure to replace your-text-domain for i18n of your plugin
    'description' => __( 'eSim', 'global1sim' ),
    // By implementing the "Node" interface the eSims Object Type will automaticaly have an "id" field.
    // By implementing the "DatabaseIdentifier" interface, the eSims Object Type will automatically have a "databaseId" field
    'interfaces' => [ 'Node', 'DatabaseIdentifier' ],
    // The fields that can be queried for on the eSims type
    'fields' => [
       'id' => [
         'resolve' => function( $root, $args, $context, $info ) {
			// we will revisit this shortly
			$resolver = new eSimsConnectionResolver( $root, $args, $context, $info );
			return $resolver->get_connection();
		}
       ],
       'iccid' => [
         'type' => 'String',
         'description' => __( 'The ICCID of the eSim', 'global1sim' ),
       ],
       'msisdn' => [
         'type' => 'String',
         'description' => __( 'The MSISDN of the eSim', 'global1sim' ),
       ],
       'assigned_account' => [
         'type' => 'String',
         'description' => __( 'The Assigned Account id of the eSim', 'global1sim' ),
       ],
	   'balance' => [
         'type' => 'String',
         'description' => __( 'The balance value of the eSim', 'global1sim' ),
       ],
	   'status' => [
         'type' => 'String',
         'description' => __( 'The status of the eSim', 'global1sim' ),
       ]
    ]
  ] );

}

add_action( 'graphql_init', function() {

	/**
	 * Class ESimsLoader
	 */
	class ESimsLoader extends \WPGraphQL\Data\Loader\AbstractDataLoader {

		/**
		 * Given an array of one or more keys (ids) load the corresponding eSims
		 *
		 * @param array $keys Array of keys to identify nodes by
		 *
		 * @return array
		 */
		public function loadKeys( array $keys ): array {
			if ( empty( $keys ) ) {
				return [];
			}

			global $wpdb;

			// Prepare a SQL query to select rows that match the given IDs
			$table_name = $wpdb->prefix . 'jet_cct_esim';
			$ids        = implode( ', ', $keys );
			$query      = $wpdb->prepare( "SELECT * FROM $table_name WHERE id IN ($ids) ORDER BY id ASC", $ids );
			$results    = $wpdb->get_results($query);

			if ( empty( $results ) ) {
				return [];
			}

			// Convert the array of eSim to an associative array keyed by their IDs
			$eSimsById = [];
			foreach ( $results as $result ) {
				// ensure the eSim is returned with the eSims __typename
				$result->__typename = 'eSim';
				$eSimsById[ $result->id ] = $result;
			}

			// Create an ordered array based on the ordered IDs
			$orderedESims = [];
			foreach ( $keys as $key ) {
				if ( array_key_exists( $key, $eSimsById ) ) {
					$orderedESims[ $key ] = $eSimsById[ $key ];
				}
			}

			return $orderedESims;

		}
	}

  // Add the eSims loader to be used under the hood by WPGraphQL when loading nodes
	add_filter( 'graphql_data_loaders', function( $loaders, $context ) {
		$loaders['eSim'] = new ESimsLoader( $context );
		return $loaders;
	}, 10, 2 );

  // Filter so nodes that have a __typename will return that typename
	add_filter( 'graphql_resolve_node_type', function( $type, $node ) {
		return $node->__typename ?? $type;
	}, 10, 2 );

});

add_action( 'graphql_init', function() {

	class eSimsConnectionResolver extends \WPGraphQL\Data\Connection\AbstractConnectionResolver {

    // Tell WPGraphQL which Loader to use. We define the `eSim` loader that we registered already.
		public function get_loader_name(): string {
			return 'eSim';
		}

    // Get the arguments to pass to the query.
    // We're defaulting to an empty array as we're not supporting pagination/filtering/sorting in this example
		public function get_query_args(): array {
			return [];
		}

    // Determine the query to run. Since we're interacting with a custom database Table, we
    // use $wpdb to execute a query against the table.
    // This is where logic needs to be mapped to account for any arguments the user inputs, such as pagination, filtering, sorting, etc.
    // For this example, we are only executing the most basic query without support for pagination, etc.
    // You could use an ORM to access data or whatever else you like here.
		public function get_query(): array|bool|null {
			global $wpdb;
			$current_user_id = get_current_user_id();

			$user_id = $this->query_args['user_id'] ?? $current_user_id;

			$ids_array = $wpdb->get_results(
				$wpdb->prepare(
					sprintf(
						'SELECT id FROM %1$sjet_cct_esim WHERE user_id=%2$d LIMIT 10',
						$wpdb->prefix,
						$user_id
					)
				)
			);

			return ! empty( $ids_array ) ? array_values( array_column( $ids_array, 'id' ) ) : [];
		}

    // This determines how to get IDs. In our case, the query itself returns IDs
    // But sometimes queries, such as WP_Query might return an object with IDs as a property (i.e. $wp_query->posts )
		public function get_ids(): array|bool|null {
			return $this->get_query();
		}

    // This allows for validation on the offset. If your data set needs specific data to determine the offset, you can validate that here.
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