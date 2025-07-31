<?php

//Begin Really Simple Security session cookie settings
@ini_set('session.cookie_httponly', true);
@ini_set('session.cookie_secure', true);
@ini_set('session.use_only_cookies', true);
//END Really Simple Security cookie settings
//Begin Really Simple Security key
define('RSSSL_KEY', 'xyK79AY5kr4bx2D6fcndAGcPLVPthtULQDvG6wSflI0OM14svv0y8CSGq3kAaIZt');
//END Really Simple Security key
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
define( 'DB_NAME', 'doakst7_wp_48aeu' );

/** Database username */
define( 'DB_USER', 'doakst7_wp_zyabe' );

/** Database password */
define( 'DB_PASSWORD', 'Kd4_ms4HMF?~7~2j' );

/** Database hostname */
define( 'DB_HOST', 'localhost:3306' );

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
define('AUTH_KEY', 'OO69H+8u0t!@wJLf+n*de2vn66GfF|3T:i9D3e&~KU/u1z|:9cEoe/aj!&9TXu!8');
define('SECURE_AUTH_KEY', '/24Vo:kp*X+_SC3&/e7j@(U*%96/5(B72_0EB9XS/!V5j0y/LDu!w@-J5o-k#0:9');
define('LOGGED_IN_KEY', 'dIkksK):0jU[7738eZ6e9IDcd9(TegB4*n2yi6IGQ]5c5!#;4A-iS(y421[7VCe;');
define('NONCE_KEY', 'ZwX/Tg5D8:-48MT5rwt8(eOR)|@8SP35nBrVOl;hs8R7g8ub4E%8l6od3r3zx6)_');
define('AUTH_SALT', '3379J(GQwVzZb6a3yir/(XJ74hEDs-6l8ZnpG#lUD6@%c9vAGdY6fj![wu#(:m1Y');
define('SECURE_AUTH_SALT', '8:8cu6i&aw+sfoVVOYS@N-86_1F6fG~20~p8T-ZS6~U2*#3v/Nl)|0BQe02v6K3V');
define('LOGGED_IN_SALT', '2*Gk6!74y7TWUx8m3g393(WzBaRf!qCT6P73do(dw7lQV1Y[t32|~u]m1XU12mD~');
define('NONCE_SALT', 'm9*uEi@x#9;A2]06Ym8~oy4J1nU*4%O(8@fj:0f5rkU95]_mdQvdADo1KkLk09z5');


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'SkLlYYd_';


/* Add any custom values between this line and the "stop editing" line. */

define('WP_ALLOW_MULTISITE', true);
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

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
