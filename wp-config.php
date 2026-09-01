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
define( 'AUTH_KEY',          '1Edi{`odTchR^(}[<ag9Zzr9yJ?1 /:kQ(-d.`PEv]qX<XDM/M>5F7wD+D+,;6[6' );
define( 'SECURE_AUTH_KEY',   'QH8;iClrqvp1/6aE{@vmJKX%!]{R+Wa- 7k/_ZUg|;D9zvr!kH~Q@PIt$BG350Zq' );
define( 'LOGGED_IN_KEY',     '9*J_~N*x_7@K8Eb8;ZT.7:~8K(s,SWw]-C;Fst+/`?s$6v{tbc`=q!x^ MIfn8nJ' );
define( 'NONCE_KEY',         '1@u[1_,|aW%}z!R^eq6=i+j_vAn]&,#P6(B]ITj}<GQ.q5y*v.7Flk7.V0Oa/HPa' );
define( 'AUTH_SALT',         'lv5^wyD0_,MfWU6}@e-{2J,j0eh8!&MBtZ4DLLu7}XF<2sB#?dtAA/)_d4i%m,a~' );
define( 'SECURE_AUTH_SALT',  '->5V|#4rS)UD?2&2y=6I>iWh06}11[;&aqsb#;]_I&z$GRJD[fk.r*}7.x{*=z{-' );
define( 'LOGGED_IN_SALT',    'YIQcMf9.:rd}i!5K008`GXE/UMK|g+JzDm%ZO5 MS?UxkK|zOXTT2GK(FI{0/s)n' );
define( 'NONCE_SALT',        'W{SP}? <rzAR21#Nc[-xeV%tk};-[YK@LY}c.X|>y/)$czMEq/W=/`ZL[C?W/KXl' );
define( 'WP_CACHE_KEY_SALT', 'Yc3qf3oK`2}j_x6S)=bf>0++[G&~-wbG4jo$o#F|j,KL7X8m?7|(Ca@t`Z&+qdp+' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';


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
