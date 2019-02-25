=== Parsedown Party ===

Contributors: conner_bw, greatislander
Tags: markdown, parsedown
Requires at least: 4.9
Tested up to: 5.1
Requires PHP: 5.6
Stable tag: trunk
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.en.html

[![Build Status](https://travis-ci.org/connerbw/parsedown-party.svg?branch=master)](https://travis-ci.org/connerbw/parsedownparty) [![Code Coverage](https://codecov.io/gh/connerbw/parsedownparty/branch/master/graph/badge.svg)](https://codecov.io/gh/connerbw/parsedownparty) [![Packagist](https://img.shields.io/packagist/v/connerbw/parsedownparty.svg)](https://packagist.org/packages/connerbw/parsedownparty)

Markdown editing for WordPress.

== Description ==

This plugin lets you use [Markdown](https://github.com/erusev/parsedown) for individual posts on a case-by-case basis. [Markdown can be activated](https://github.com/thephpleague/html-to-markdown) using a toggle in the post editor submit box. When enabled, it replaces the WordPress post editor with [CodeMirror](https://make.wordpress.org/core/2017/10/22/code-editing-improvements-in-wordpress-4-9/) in Markdown mode.

Works on posts using the [Classic Editor](https://en-ca.wordpress.org/plugins/classic-editor/). Gutenberg is currently not supported.

== Screenshots ==

1. Parsedown Party in the post editor.

== Frequently Asked Questions ==

= If I turn this on for a post, can I go back to HTML if I change my mind? =

Yes.

= Can I automatically enable Markdown for all new posts? =

Yes. Add the following line to your theme's `functions.php` (or another suitable place):

`add_filter( 'parsedownparty_autoenable', '__return_true' );`

= Is this plugin compatible with Pressbooks? =

[Yes.](https://pressbooks.org/)

= Does this plugin work with Gutenberg? =

Not yet. It works on posts using the [Classic Editor](https://github.com/WordPress/classic-editor). The block editor is currently not supported.

= I'm a software developer, how can I help? =

This plugin follows [Pressbooks coding standards](https://docs.pressbooks.org/coding-standards/) and development [happens on GitHub](https://github.com/connerbw/parsedown-party).

The philosophy behind this plugin is: Take a best of breed [Markdown Parser](https://github.com/erusev/parsedown), combine it with WordPress' built-in [CodeMirror](https://make.wordpress.org/core/2017/10/22/code-editing-improvements-in-wordpress-4-9/) libraries, and let users write posts in Markdown. Things like two-pane WYSIWYG editors are out of scope for this particular plugin (the Preview button works fine.) The design goal is to modify WordPress Core as little as possible while providing decent Markdown support for content.

== Changelog ==

= 1.2.0 =
- Compatibility with WordPress 5.1 (and Classic Editor)
- Fixed paragraphs following tables

= 1.1.1 =
- Fix cache glitch when previewing.

= 1.1.0 =
- When enabling Markdown on an existing post, convert HTML to Markdown.
- Cache parsed content using transients.

= 1.0.2 =
- Update Parsedown to version 1.7.1

= 1.0.1 =
- Add `parsedownparty_autoenable` filter to allow Markdown to be enabled by default.

= 1.0.0 =
- Initial release.
