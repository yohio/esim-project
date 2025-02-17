<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'local' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', 'root' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',          ' T/XS{uSv~$uBg]t.VB&Ru.I6ED)G`.HUqop/]?9,|@0L]X3 kU_d)r2w7qv~T,/' );
define( 'SECURE_AUTH_KEY',   'u0.}J68s,+(=D+!;c?.m(aGw(8<}r*KtP*)tdj*V%H(<BM~>)C)c]o]L=~!xxDkJ' );
define( 'LOGGED_IN_KEY',     '2%3`h;uegL;z$y26ND{(+QV>COiQLHp9(m@Ox>6cDw%xYi4 fP)^l~$Wscw`r&Ou' );
define( 'NONCE_KEY',         'seOjL#<V6IK -m|&UK{<]:W-m+`,_7j3Sj*mPi=Y 4ZZpQKt KViB#I|T4coaLH!' );
define( 'AUTH_SALT',         'iXRwMe4U$_Mh.47hg$&+0$9S&1CA,TT)`H%mM-%DNb:1KqByg|k1,HMt<su8rhh2' );
define( 'SECURE_AUTH_SALT',  '3D<`;DR6hl3qk:8o,tr?DmJm$dWiC=@Zs>oDlHcwH#v1Gz)3b,a@ZH;PDq0j9X+8' );
define( 'LOGGED_IN_SALT',    '(ddfGTRUp-5Ci(8Y>U<b:iEOS`%%ZAf1K!IDwz59nv=>CH&5NHdx)4`[+@H^xVfI' );
define( 'NONCE_SALT',        '`cC15j(g;M9Q1Z]ONWv#*_:{lACZ D;yW[4%iTYoM}!KpA{`-y3=.Nw~(2]4^{Ow' );
define( 'WP_CACHE_KEY_SALT', 'F5ce!!#y3OE}i74Bx^S3=(;VJd(D-?pdbB}GZ95Hq@u]JyCfGm9}QOex&DdtR#5_' );

/**#@-*/
// JWT auth refresh token.
define( 'GRAPHQL_JWT_AUTH_SECRET_KEY', 'gJjwZi|O(##Qgd)A,LAGrX7sc!Iq(LT578;XL2DEmuZmjfY.(87sZC52+,4lXTpX' );
// Define JWT secret key (use the same JWT_SECRET as in .env.local)
define('JWT_AUTH_SECRET_KEY', 'gJjwZi|O(##Qgd)A,LAGrX7sc!Iq(LT578;XL2DEmuZmjfY.(87sZC52+,4lXTpX');
define('JWT_AUTH_CORS_ENABLE', true);

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('GRAPHQL_DEBUG', true);

/* Add any custom values between this line and the "stop editing" line. */



/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}

define( 'WP_ENVIRONMENT_TYPE', 'local' );
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
