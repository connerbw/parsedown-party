<?php

/*
Plugin Name: Parsedown Party
Plugin URI: https://github.com/connerbw/parsedownparty/
Description: Markdown editing for WordPress.
Author: KIZU514
Author URI: https://kizu514.com/
License: GPLv2
Version: 1.0.1
Requires PHP: 5.6
Requires at least: 4.9
Tested up to: 4.9
Text Domain: parsedownparty
*/

require_once( __DIR__ . '/inc/class-plugin.php' );

if ( ! class_exists( '\ParsedownExtra' ) ) {
	if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
		require_once __DIR__ . '/vendor/autoload.php';
	} else {
		$title = __( 'Dependencies Missing', 'parsedownparty' );
		$body = __( 'Please run <code>composer install</code> from the root of the Parsedown Party plugin directory.', 'parsedownparty' );
		$message = "<h1>{$title}</h1><p>{$body}</p>";
		wp_die( $message, $title );
	}
}

add_action( 'init', [ '\ParsedownParty\Plugin', 'init' ] );
