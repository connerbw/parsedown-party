=== Parsedown Party ===

Contributors: conner_bw, greatislander
Tags: markdown, parsedown
Requires at least: 4.9
Tested up to: 4.9
Requires PHP: 5.6
Stable tag: trunk
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.en.html

[![Build Status](https://travis-ci.org/connerbw/parsedownparty.svg?branch=master)](https://travis-ci.org/connerbw/parsedownparty) [![Code Coverage](https://codecov.io/gh/connerbw/parsedownparty/branch/master/graph/badge.svg)](https://codecov.io/gh/connerbw/parsedownparty) [![Packagist](https://img.shields.io/packagist/v/connerbw/parsedownparty.svg)](https://packagist.org/packages/connerbw/parsedownparty)

Markdown editing for WordPress.

== Description ==

This plugin lets you use [Markdown](https://github.com/erusev/parsedown) for individual posts on a case-by-case basis. Markdown can be activated using a toggle in the post editor submit box. When enabled, it replaces the WordPress post editor with [CodeMirror](https://make.wordpress.org/core/2017/10/22/code-editing-improvements-in-wordpress-4-9/) in Markdown mode.

== Screenshots ==

1. Parsedown Party in the post editor.

== Frequently Asked Questions ==

= If I turn this on for a post, can I go back to HTML editing mode if I change my mind? =

Yes.

= Can I automatically enable Markdown for all new posts? =

Yes. Add the following line to your theme's `functions.php` (or another suitable place):

`add_filter( 'parsedownparty_autoenable', '__return_true' );`

= Is this Plugin compatible with Pressbooks? =

[Yes.](https://pressbooks.org/)

= I'm a software developer, how can I help? =

This plugin follows [Pressbooks coding standards](https://docs.pressbooks.org/coding-standards/) and development [happens on GitHub](https://github.com/connerbw/parsedownparty).

The philosophy behind this plugin is: Take a best of breed [Markdown Parser](https://github.com/erusev/parsedown), combine it with WordPress' built-in [CodeMirror](https://make.wordpress.org/core/2017/10/22/code-editing-improvements-in-wordpress-4-9/) libraries, and let users write posts in Markdown. Things like two-pane WYSIWYG editors are out of scope for this particular plugin (the Preview button works fine.) The design goal is to modify WordPress Core as little as possible while providing decent Markdown support for content.

== Changelog ==

= 1.0.1 =
Add `parsedownparty_autoenable` filter to allow Markdown to be enabled by default. 

= 1.0.0 =
Initial release.
