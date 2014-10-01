<?php
/**
 * The base configurations of the WordPress.
 *
 * This file has the following configurations: MySQL settings, Table Prefix,
 * Secret Keys, WordPress Language, and ABSPATH. You can find more information
 * by visiting {@link http://codex.wordpress.org/Editing_wp-config.php Editing
 * wp-config.php} Codex page. You can get the MySQL settings from your web host.
 *
 * This file is used by the wp-config.php creation script during the
 * installation. You don't have to use the web site, you can just copy this file
 * to "wp-config.php" and fill in the values.
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'i553397_wp1');

/** MySQL database username */
define('DB_USER', 'i553397_wp1');

/** MySQL database password */
define('DB_PASSWORD', 'R@0@itOT@]31])8');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'gBKfquXEq4J8hsQ47CnGFKI4tu5Od3gH9J10uqAoDSgnsfBbvc4qjkY0LHnByd1h');
define('SECURE_AUTH_KEY',  'cGieVH5qvfNTw9G3X6bt86F8mtCwfYXw92EbPrmPQQlD3Zq1444bQL4OEAQyucVV');
define('LOGGED_IN_KEY',    'wGh0Y3TFMIfcxmBt26Tm5Q7ugW9UvjA4KtfLPKEcc7FgDpdSAp4Vy5NiLTknV0OJ');
define('NONCE_KEY',        'yq3YNsHxgrEmWXPQKTWVB7rPULSwne31UlVUUh75ph65XeQIoK7HWarMfbQvI5Bg');
define('AUTH_SALT',        'hfEESLO12nSI2YhrVVLbXjL9RY4Xntyd1cevuP8SYuGnJeA0nI18RuX9gb4XlpZN');
define('SECURE_AUTH_SALT', 'vaiWKmp474m4Q85BNz0iEZW5pDgdhmnTd68Y58C9q5CjxNl9FY1YNfB9qfWZldoF');
define('LOGGED_IN_SALT',   'o5KfURHG3P7PpQWZUMUurTHjdCql6fSOn4BP5gHZa10mOChxFIeiAvh7Cz9qWRRx');
define('NONCE_SALT',       'qaLQVPECFMIO9zgIrGlHrUzZX09LsqF5wHcAJk6HfFLBaTDNmfPjkQGOEvviS3bE');

/**
 * Other customizations.
 */
define('FS_METHOD','direct');define('FS_CHMOD_DIR',0755);define('FS_CHMOD_FILE',0644);
define('WP_TEMP_DIR',dirname(__FILE__).'/wp-content/uploads');

/**
 * Turn off automatic updates since these are managed upstream.
 */
define('AUTOMATIC_UPDATER_DISABLED', true);


/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each a unique
 * prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * WordPress Localized Language, defaults to English.
 *
 * Change this to localize WordPress. A corresponding MO file for the chosen
 * language must be installed to wp-content/languages. For example, install
 * de_DE.mo to wp-content/languages and set WPLANG to 'de_DE' to enable German
 * language support.
 */
define('WPLANG', '');

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 */
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
