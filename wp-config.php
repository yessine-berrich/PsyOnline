<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'psyonline01' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

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
define( 'AUTH_KEY',         's@^I>n>NLo5,;7C/8b}@:[finW+XP7@--5<t*rhDwGyt.-Y~#Jk[1F (Xyi^Kr*M' );
define( 'SECURE_AUTH_KEY',  '/<3U_NbQwPJ%:otELDss]35}o];2s*DJTBI:Co Avuo0V6Bubj1n+7g#Fm,rM}6r' );
define( 'LOGGED_IN_KEY',    '59*#T?%T ES*F Xop5&~hijTo,^Xup~um^agx1@:LP% GcDe$Vik#P>:2X~<3r3w' );
define( 'NONCE_KEY',        'vG(=1t ]+29Op(`pFX*1#)dI0XaPj:Q;6ufKYl}Xe,&+dibOX5OP:x#Xe|1wn=}E' );
define( 'AUTH_SALT',        '*uJjPQPaQ# +7+6p0T4l;=OEcZtug.,Ag]vM]@27b^_jD;|QeKV{QgDp%ReKjh24' );
define( 'SECURE_AUTH_SALT', 'ybs}.sx-@c:a>7[%+,QP9zEry+C>lezaKp%?=7ap(e_G2t~>ZUVd1ak+{Zg6>Iw&' );
define( 'LOGGED_IN_SALT',   'zq9G4]!ZZ0&!E6d<~RtEN3ZXH-O,dzX.d~g~0)!p5q,_<gg}eRB#V3Cu9f[;<&O;' );
define( 'NONCE_SALT',       'zG&{vdAs>?Jb_,[m>1cHIjDyQ*C,($n@`%~1u}a4zD*a<y4_rmo^P-H8x4eN{J|<' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'wp_';

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
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
