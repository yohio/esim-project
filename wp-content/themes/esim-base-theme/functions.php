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


// Add custom fields to user profile
add_action( 'graphql_register_types', function() {
    register_graphql_field( 'Users', 'contact_name', [
        'type' => 'text',
        'resolve' => function( $user ) {
            $contact_name = get_user_meta( $user->databaseId, 'contact_name'  );
            return ! empty( $contact_name ) ? $contact_name : null;
        }
    ]);
});

add_action( 'show_user_profile', 'ararat_profile_fields' );
add_action( 'edit_user_profile', 'ararat_profile_fields' );

function ararat_profile_fields( $user ) {

	// let's get custom field values
	$contact_name = get_user_meta( $user->ID, 'contact_name', true );
	$contact_email = get_user_meta( $user->ID, 'contact_email', true );
	$contact_phone = get_user_meta( $user->ID, 'contact_phone', true );
	$contact_country = get_user_meta( $user->ID, 'contact_country', true );

	?>
		<h3>Contact Information</h3>
		<table class="form-table">
	 		<tr>
				<th><label for="contact_name">Contact Name</label></th>
		 		<td>
					<input type="text" name="contact_name" id="contact_name" value="<?php echo esc_attr( $contact_name ) ?>" class="regular-text" />
				</td>
			</tr>
			<tr>
				<th><label for="contact_email">Contact Email</label></th>
				<td>
					<input type="text" name="contact_email" id="contact_email" value="<?php echo esc_attr( $contact_email ) ?>" class="regular-text" />
				</td>
			</tr>
			<tr>
				<th><label for="contact_phone">Contact Phone</label></th>
				<td>
					<input type="text" name="contact_phone" id="contact_phone" value="<?php echo esc_attr( $contact_phone ) ?>" class="regular-text" />
				</td>
			</tr>
			<tr>
				<th><label for="contact_country">Contact Country</label></th>
				<td>
					<input type="text" name="contact_country" id="contact_country" value="<?php echo esc_attr( $contact_country ) ?>" class="regular-text" />
				</td>
			</tr>
		</table>
	<?php
}

add_action( 'personal_options_update', 'ararat_save_profile_fields' );
add_action( 'edit_user_profile_update', 'ararat_save_profile_fields' );
 
function ararat_save_profile_fields( $user_id ) {
	
	if( ! isset( $_POST[ '_wpnonce' ] ) || ! wp_verify_nonce( $_POST[ '_wpnonce' ], 'update-user_' . $user_id ) ) {
		return;
	}
	
	if( ! current_user_can( 'edit_user', $user_id ) ) {
		return;
	}
 
	update_user_meta( $user_id, 'contact_name', sanitize_text_field( $_POST[ 'contact_name' ] ) );
	update_user_meta( $user_id, 'contact_email', sanitize_text_field( $_POST[ 'contact_email' ] ) );
	update_user_meta( $user_id, 'contact_phone', sanitize_text_field( $_POST[ 'contact_phone' ] ) );
	update_user_meta( $user_id, 'contact_country', sanitize_text_field( $_POST[ 'contact_country' ] ) );
 
}

add_action('graphql_register_types', function() {
    register_graphql_field('User', 'contactInformation', [
        'type' => 'ContactInformation',
        'description' => 'Additional contact information for the user',
        'resolve' => function($user) {
            return [
                'contactName' => get_user_meta($user->ID, 'contact_name', true),
                'contactEmail' => get_user_meta($user->ID, 'contact_email', true),
                'contactPhone' => get_user_meta($user->ID, 'contact_phone', true),
                'contactCountry' => get_user_meta($user->ID, 'contact_country', true),
            ];
        }
    ]);

    register_graphql_object_type('ContactInformation', [
        'description' => 'User contact information fields',
        'fields' => [
            'contactName' => [
                'type' => 'String',
                'description' => 'The contact name of the user'
            ],
            'contactEmail' => [
                'type' => 'String',
                'description' => 'The contact email of the user'
            ],
            'contactPhone' => [
                'type' => 'String',
                'description' => 'The contact phone number of the user'
            ],
            'contactCountry' => [
                'type' => 'String',
                'description' => 'The contact country of the user'
            ]
        ]
    ]);
});

add_action('graphql_register_types', function() {
    register_graphql_field('User', 'userData', [
        'type' => 'UserData',
        'description' => 'Additional user data fields',
        'resolve' => function($user) {
            $user_info = get_userdata($user->userId);
            return [
                'email' => $user_info->user_email,
                'username' => $user_info->user_login,
                'capabilities' => array_keys($user_info->allcaps),
                'roles' => $user_info->roles,
                'nickname' => $user_info->nickname,
                'firstName' => $user_info->first_name,
                'lastName' => $user_info->last_name,
                'displayName' => $user_info->display_name,
                'avatar' => get_avatar_url($user_info->ID)
            ];
        }
    ]);

    register_graphql_object_type('UserData', [
        'description' => 'User data fields',
        'fields' => [
            'email' => [
                'type' => 'String',
                'description' => 'The user email'
            ],
            'username' => [
                'type' => 'String',
                'description' => 'The username'
            ],
            'capabilities' => [
                'type' => ['list_of' => 'String'],
                'description' => 'User capabilities'
            ],
            'roles' => [
                'type' => ['list_of' => 'String'],
                'description' => 'User roles'
            ],
            'nickname' => [
                'type' => 'String',
                'description' => 'User nickname'
            ],
            'firstName' => [
                'type' => 'String',
                'description' => 'User first name'
            ],
            'lastName' => [
                'type' => 'String',
                'description' => 'User last name'
            ],
            'displayName' => [
                'type' => 'String',
                'description' => 'User display name'
            ],
            'avatar' => [
                'type' => 'String',
                'description' => 'User avatar URL'
            ]
        ]
    ]);
});

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
					return !empty($source->account_owner) ? (string) $source->account_owner : null;
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
		'fromType' => 'Source',
		'toType' => 'Account',
		'fromFieldName' => 'account',
		'resolve' => function( $root, $args, $context, $info ) {
			$resolver = new AccountConnectionResolver( $root, $args, $context, $info );
			$resolver->set_query_arg( 'include', $root->id );
			return $resolver->get_connection();
		}
	]);

}

register_graphql_field('RootQuery', 'accountByName', [
    'type' => 'Account',
    'description' => __('Get account by account name', 'global1sim'),
    'args' => [
        'account_name' => [
            'type' => ['non_null' => 'String'],
            'description' => __('The name of the account', 'global1sim')
        ]
    ],
    'resolve' => function($root, $args) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'jet_cct_accounts';
        $account_name = sanitize_text_field($args['account_name']);
        
		// $query = $wpdb->prepare("SELECT * FROM {$table_name} WHERE account_name IN ($account_name)", $keys);
		// $result    = $wpdb->get_results($query);
        $result = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE account_name = %s",
                $account_name
            ),
            ARRAY_A
        );
		

        if (!$result) {
            return null;
        }

        $account = [];
        foreach ($result as $key => $value) {
			graphql_debug( "########## result $key value: " . $result );
            $account[$key] = $value;
        }
        
        return $account;
    }
]);

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

	register_graphql_connection([
		'fromType' => 'Customer',
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

// ******** Customer ******** //
add_action( 'graphql_register_types', 'wpgraphql_customer_register_types' );

function wpgraphql_customer_register_types() {
	register_graphql_object_type( 'Customer', [
		'description' => __( 'Customer for an account', 'global1sim' ),
		'interfaces' => [ 'Node' ],
		'fields' => [
			'_ID' => [
				'type' => 'ID',
				'description' => __( 'The ID of the Customer', 'global1sim' ),
				'resolve' => function($source) {
					return !empty($source->_ID) ? (string) $source->_ID : null;
				}
			],'first_name' => [
				'type' => 'String',
				'description' => __( 'The First Name of the Customer', 'global1sim' ),
				'resolve' => function($source) {
					return !empty($source->first_name) ? (string) $source->first_name : null;
				}
			],
			'last_name' => [
				'type' => 'String',
				'description' => __( 'The Last Name of the Customer', 'global1sim' ),
				'resolve' => function($source) {
					return !empty($source->last_name) ? (string) $source->last_name : null;
				}
			],
			'phone' => [
				'type' => 'String',
				'description' => __( 'The Phone id of the Customer', 'global1sim' ),
				'resolve' => function($source) {
					return !empty($source->phone) ? (string) $source->phone : null;
				}
			],
			'email' => [
				'type' => 'String',
				'description' => __( 'The Email of the Customer', 'global1sim' ),
				'resolve' => function($source) {
					return !empty($source->email) ? (string) $source->email : null;
				}
			],
			'assigned_esim' => [
				'type' => 'String',
				'description' => __( 'The assigned_esim of the Customer', 'global1sim' ),
				'resolve' => function($source) {
					return !empty($source->assigned_esim) ? (string) $source->assigned_esim : null;
				}
			]
		]
	] );

	register_graphql_connection([
		'fromType' => 'RootQuery',
		'toType' => 'Customer',
		'fromFieldName' => 'Customer',
		'resolve' => function( $root, $args, $context, $info ) {
			$resolver = new CustomerConnectionResolver( $root, $args, $context, $info );
			return $resolver->get_connection();
		}
	]);

	register_graphql_connection([
		'fromType' => 'Customer',
		'toType' => 'eSim',
		'fromFieldName' => 'eSim',
		'resolve' => function( $root, $args, $context, $info ) {
			$resolver = new CustomerConnectionResolver( $root, $args, $context, $info );
			// Make sure we have a valid account owner ID
			$assigned_esim = !empty($root->assigned_esim) ? $root->assigned_esim : 0;

			if ($assigned_esim) {
				$resolver->set_query_arg( 'include', $root->assigned_esim );
			}
			return $resolver->one_to_one()->get_connection();
		}
	]);

}

// ******** Source ******** //
add_action( 'graphql_register_types', 'wpgraphql_source_register_types' );

function wpgraphql_source_register_types() {
	register_graphql_object_type( 'Source', [
		'description' => __( 'Source for an account', 'global1sim' ),
		'interfaces' => [ 'Node' ],
		'fields' => [
			'_ID' => [
				'type' => 'ID',
				'description' => __( 'The ID of the Source', 'global1sim' ),
				'resolve' => function($source) {
					return !empty($source->_ID) ? (string) $source->_ID : null;
				}
			],'source_name' => [
				'type' => 'String',
				'description' => __( 'The Source Name of the Source', 'global1sim' ),
				'resolve' => function($source) {
					return !empty($source->source_name) ? (string) $source->source_name : null;
				}
			],
			'assigned_account' => [
				'type' => 'String',
				'description' => __( 'The Assigned Account of the Source', 'global1sim' ),
				'resolve' => function($source) {
					return !empty($source->assigned_account) ? (string) $source->assigned_account : null;
				}
			]
		]
	] );

	register_graphql_connection([
		'fromType' => 'RootQuery',
		'toType' => 'Source',
		'fromFieldName' => 'Source',
		'resolve' => function( $root, $args, $context, $info ) {
			$resolver = new SourceConnectionResolver( $root, $args, $context, $info );
			return $resolver->get_connection();
		}
	]);

	register_graphql_connection([
		'fromType' => 'Account',
		'toType' => 'Source',
		'fromFieldName' => 'source',
		'resolve' => function( $root, $args, $context, $info ) {
			$resolver = new SourceConnectionResolver( $root, $args, $context, $info );
			// Make sure we have a valid account owner ID
			$assigned_account = !empty($root->_ID) ? $root->_ID : 0;

			if ($assigned_account) {
				$resolver->set_query_arg( 'assigned_account', $assigned_account );
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
			if (empty($account_id) || ( $account_id == $current_user_id && current_user_can('manage_options'))) {
				$query = $wpdb->prepare("SELECT _ID FROM {$table_name}");
			}
			
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

	/**
	 * Class CustomerLoader
	 */
	class CustomerLoader extends \WPGraphQL\Data\Loader\AbstractDataLoader {

		/**
		 * Given an array of one or more keys (ids) load the corresponding Customers
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

			$table_name = $wpdb->prefix . 'jet_cct_customer';
			$placeholders = implode(',', array_fill(0, count($keys), '%d'));
			$query = $wpdb->prepare("SELECT * FROM {$table_name} WHERE _ID IN ($placeholders)", $keys);
			$results    = $wpdb->get_results($query);

			if ( empty( $results ) ) {
				return []; // Return empty array instead of null
			}

			$CustomersById = [];
			foreach ( $results as $result ) {
				$result->__typename = 'Customer';

				$CustomersById[ $result->_ID ] = $result;
			}

			$orderedCustomers = [];
			foreach ( $keys as $key ) {
				if ( array_key_exists( $key, $CustomersById ) ) {
					$orderedCustomers[ $key ] = $CustomersById[ $key ];
				}
			}

			return $orderedCustomers;

		}
	}

	add_filter( 'graphql_data_loaders', function( $loaders, $context ) {
		
		$loaders['Customer'] = new CustomerLoader( $context );
		return $loaders;
	}, 10, 2 );


	add_filter( 'graphql_resolve_node_type', function( $type, $node ) {
		return $node->__typename ?? $type;
	}, 10, 2 );

	class CustomerConnectionResolver extends \WPGraphQL\Data\Connection\AbstractConnectionResolver {

		public function get_loader_name(): string {
			return 'Customer';
		}

		public function get_query_args(): array {
			return $this->args;
		}

		public function get_query(): array|bool|null {
			global $wpdb;

			
			$assigned_esim = $this->query_args["assigned_esim"] || NULL ;
			$table_name = $wpdb->prefix . 'jet_cct_esim';
			if (empty($assigned_esim) || current_user_can('manage_options')) {
				$query = $wpdb->prepare("SELECT * FROM {$table_name}");
			} else {
				$query = $wpdb->prepare("SELECT * FROM {$table_name} WHERE _ID=$assigned_esim");
			}
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

	/**
	 * Class SourceLoader
	 */
	class SourceLoader extends \WPGraphQL\Data\Loader\AbstractDataLoader {

		/**
		 * Given an array of one or more keys (ids) load the corresponding Sources
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

			$table_name = $wpdb->prefix . 'jet_cct_source';
			$placeholders = implode(',', array_fill(0, count($keys), '%d'));
			$query = $wpdb->prepare("SELECT * FROM {$table_name} WHERE _ID IN ($placeholders)", $keys);
			$results    = $wpdb->get_results($query);

			if ( empty( $results ) ) {
				return []; // Return empty array instead of null
			}

			$SourcesById = [];
			foreach ( $results as $result ) {
				$result->__typename = 'Source';

				$SourcesById[ $result->_ID ] = $result;
			}

			$orderedSources = [];
			foreach ( $keys as $key ) {
				if ( array_key_exists( $key, $SourcesById ) ) {
					$orderedSources[ $key ] = $SourcesById[ $key ];
				}
			}

			return $orderedSources;

		}
	}

	add_filter( 'graphql_data_loaders', function( $loaders, $context ) {
		
		$loaders['Source'] = new SourceLoader( $context );
		return $loaders;
	}, 10, 2 );

	add_filter( 'graphql_resolve_node_type', function( $type, $node ) {
		return $node->__typename ?? $type;
	}, 10, 2 );

	class SourceConnectionResolver extends \WPGraphQL\Data\Connection\AbstractConnectionResolver {

		public function get_loader_name(): string {
			return 'Source';
		}

		public function get_query_args(): array {
			return $this->args;
		}

		public function get_query(): array|bool|null {
			global $wpdb;
			// graphql_debug( "########## get_query_args: " . json_encode($this->qsuery_args) );
			$assigned_account = $this->query_args["assigned_account"] || NULL ;
			$table_name = $wpdb->prefix . 'jet_cct_accounts';
			if (empty($assigned_account) || current_user_can('manage_options')) {
				$query = $wpdb->prepare("SELECT * FROM {$table_name}");
			} else {
				$query = $wpdb->prepare("SELECT * FROM {$table_name} WHERE _ID=$assigned_account");
			}
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


// Add this to your functions.php file
add_filter('graphql_RootQueryToUserConnectionWhereArgs_fields', function($fields) {
    $fields['includeAllUsers'] = [
        'type' => 'Boolean',
        'description' => 'Include all users regardless of role',
    ];
    return $fields;
});

add_filter('graphql_User_connection_query_args', function($query_args, $connection_args) {
    if (isset($connection_args['where']['includeAllUsers']) && $connection_args['where']['includeAllUsers'] === true) {
        // Remove all role and capability restrictions
        unset($query_args['role__in']);
        unset($query_args['role__not_in']);
        unset($query_args['role']);
        unset($query_args['capability']);
        unset($query_args['who']);
        
        // Set number of users to -1 to get all users
        $query_args['number'] = -1;
        
        // Add filter to remove role restrictions from the main query
        add_filter('users_pre_query', function($null, $query) {
            if (!empty($query->query_vars['capability'])) {
                unset($query->query_vars['role__in']);
            }
            if (!empty($query->query_vars['role__not_in'])) {
                unset($query->query_vars['role__not_in']);
            }
            return $null;
        }, 10, 2);

        // Add filter to modify the WHERE clause
        add_filter('users_list_table_query_args', function($args) {
            unset($args['role']);
            return $args;
        });
    }
    return $query_args;
}, 10, 2);
