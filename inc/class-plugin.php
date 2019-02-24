<?php

namespace Parsedownparty;

class Plugin {

	const METAKEY = 'kizu514_use_markdown';

	const NONCE = '_kizu514_parsedown_party';

	const CONVERTER_OPTIONS = [
		'header_style' => 'atx',
	];

	/**
	 * @var Plugin
	 */
	private static $instance = null;

	/**
	 * @var \ParsedownExtra
	 */
	private $parsedown;

	/**
	 * @var \League\HTMLToMarkdown\HtmlConverter
	 */
	private $htmlConverter;

	/**
	 * @var array
	 */
	private $supportedPages = [ 'post.php', 'post-new.php' ];

	/**
	 * @return Plugin
	 * @throws \Exception
	 */
	static public function init() {
		if ( is_null( self::$instance ) ) {
			$extra = new \ParsedownExtra();
			$converter = new \League\HTMLToMarkdown\HtmlConverter( self::CONVERTER_OPTIONS );
			self::$instance = new self( $extra, $converter );
			self::hooks( self::$instance );
		}
		return self::$instance;
	}

	/**
	 * @param Plugin $obj
	 */
	static public function hooks( Plugin $obj ) {
		add_action( 'post_submitbox_misc_actions', [ $obj, 'createMarkdownLink' ] );
		add_action( 'save_post', [ $obj, 'saveMarkdownMeta' ], 10, 2 );
		add_filter( 'wp_editor_settings', [ $obj, 'parseEditorSettings' ] );
		add_action( 'admin_enqueue_scripts', [ $obj, 'overrideEditor' ] );
		add_filter( 'the_content', [ $obj, 'parseTheContent' ], 9 );
	}

	/**
	 * @param \ParsedownExtra $parsedown
	 * @param \League\HTMLToMarkdown\HtmlConverter $html_converter
	 */
	public function __construct( $parsedown, $html_converter ) {
		$this->parsedown = $parsedown;
		$this->htmlConverter = $html_converter;
	}

	/**
	 * Use markdown for post?
	 *
	 * @param \WP_Post $post
	 *
	 * @return bool
	 */
	public function useMarkdownForPost( $post = null ) {
		if ( ! $post ) {
			$post = $this->getPost();
		}
		if ( $post ) {
			if ( $this->isGutenberg( $post ) ) {
				// Block editor currently not supported
				return false;
			}
			$meta_value = get_post_meta( $post->ID, self::METAKEY, true );
			if ( ! in_array( $meta_value, [ false, '' ], true ) ) {
				// meta value should be 0, '0', 1, or '1'.
				// false and '' means nothing was set
				return (bool) $meta_value;
			}
		}

		/**
		 * Enable markdown by default:
		 *
		 *    add_filter('parsedownparty_autoenable', '__return_true');
		 *
		 * @since 1.1
		 */
		return apply_filters( 'parsedownparty_autoenable', false );
	}

	/**
	 * Create a markdown activation link in the post editor submit box
	 *
	 * @see https://developer.wordpress.org/resource/dashicons/
	 *
	 * @param \WP_Post $post
	 */
	public function createMarkdownLink( $post ) {
		if ( $this->isGutenberg( $post ) ) {
			// Block editor currently not supported
			return;
		}
		$use_markdown = $this->useMarkdownForPost( $post );
		wp_nonce_field( $post->ID, self::NONCE );
		echo '<input type="hidden" value="' . (int) $use_markdown . '" name="' . self::METAKEY . '"  id="' . self::METAKEY . '" />';
		if ( $use_markdown ) {
			?>
			<div class="misc-pub-section">
				<span class="dashicons dashicons-editor-code"></span> Markdown:
				<a href="javascript:{}"
				onclick="document.getElementById('<?php echo self::METAKEY; ?>').value = 0; document.getElementById('post').submit(); return false;"><?php _e( 'Disable' ); ?></a>
			</div>
			<?php
		} else {
			?>
			<div class="misc-pub-section">
				<span class="dashicons dashicons-editor-code"></span> Markdown:
				<a href="javascript:{}"
				onclick="document.getElementById('<?php echo self::METAKEY; ?>').value = 1; document.getElementById('post').submit(); return false;"><?php _e( 'Enable' ); ?></a>
			</div>
			<?php
		}
	}

	/**
	 * Save markdown post meta
	 *
	 * @param int $post_id Post ID.
	 * @param \WP_Post $post Post object.
	 */
	public function saveMarkdownMeta( $post_id, $post ) {

		// Markdown to HTML conversions are cached in transients, delete them
		delete_transient( self::METAKEY . "_{$post_id}" );
		if ( $post->post_type === 'revision' ) {
			delete_transient( self::METAKEY . "_{$post->post_parent}" );
		}

		// Should we abort?
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! isset( $_POST[ self::NONCE ] ) || ! wp_verify_nonce( $_POST[ self::NONCE ], $post_id ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		if ( $this->isGutenberg( $post ) ) {
			// Block editor currently not supported
			return;
		}

		// If $use_markdown_old and $use_markdown are not the same, then we are converting from HTML to Markdown (or vice versa)
		$use_markdown_old = get_post_meta( $post_id, self::METAKEY, true );
		$use_markdown = ! empty( $_POST[ self::METAKEY ] ) ? 1 : 0;
		if ( (string) $use_markdown_old !== (string) $use_markdown ) {
			static $recursion = false; // Set a static variable to fix infinite hook loop
			if ( ! $recursion ) {
				$recursion = true;
				if ( $use_markdown ) {
					$content = $this->htmlConverter->convert( wpautop( $post->post_content ) ); // HTML To Markdown
				} else {
					$content = $this->parsedown->parse( $post->post_content ); // Markdown To HTML
				}
				wp_update_post(
					[
						'ID' => $post_id,
						'post_content' => $content,
					]
				);
				update_post_meta( $post_id, self::METAKEY, $use_markdown );
				$recursion = false;
			}
		}
	}

	/**
	 * If markdown, then disable most of the post editor settings
	 *
	 * @param array $settings
	 *
	 * @return array
	 */
	public function parseEditorSettings( $settings ) {
		global $pagenow;
		if ( in_array( $pagenow, $this->supportedPages, true ) && $this->useMarkdownForPost() ) {
			$settings['wpautop'] = false;
			$settings['media_buttons'] = false;
			$settings['tinymce'] = false;
			$settings['quicktags'] = false;
		}
		return $settings;
	}

	/**
	 * If markdown, then replace the post editor with CodeMirror configured for markdown
	 *
	 * @see https://make.wordpress.org/core/2017/10/22/code-editing-improvements-in-wordpress-4-9/
	 */
	public function overrideEditor() {
		global $pagenow;
		if ( in_array( $pagenow, $this->supportedPages, true ) && $this->useMarkdownForPost() ) {
			$args = [
				'type' => 'text/x-markdown',
			];
			$settings = wp_enqueue_code_editor( $args );
			if ( false === $settings ) {
				// Bail if user disabled CodeMirror.
				return;
			}
			wp_add_inline_script(
				'code-editor',
				sprintf( 'jQuery( function() { wp.codeEditor.initialize( "content", %s ); } );', wp_json_encode( $settings ) )
			);
		}
	}

	/**
	 * If markdown, then parse the_content using Parsedown
	 * Cached in a transient
	 *
	 * @param string $content
	 *
	 * @return string
	 */
	public function parseTheContent( $content ) {

		$post = $this->getPost();
		$post_id = $post ? $post->ID : 0;
		$transient = self::METAKEY . "_{$post_id}";
		$use_markdown = $this->useMarkdownForPost();

		// Preview
		if ( is_preview() ) {
			if ( $use_markdown ) {
				return $this->parsedown->parse( $content );
			} else {
				// This preview is not markdown, return unchanged
				return $content;
			}
		}

		// Post
		if ( $use_markdown ) {
			// Markdown to HTML conversions are cached in transients, try to use it
			$cached_content = get_transient( $transient );
			if ( $post_id && $cached_content ) {
				return $cached_content;
			} else {
				$content = $this->parsedown->parse( $content );
				set_transient( $transient, $content );
				return $content;
			}
		}

		// This post is not markdown, delete the cache and return unchanged
		delete_transient( $transient );
		return $content;
	}

	/**
	 * @return null|\WP_Post
	 */
	public function getPost() {
		$post = get_post();
		if ( ! $post ) {
			// Try to find using deprecated means
			global $id;
			$post = get_post( $id );
		}
		return $post;
	}

	/**
	 * @param int|\WP_Post $post Post ID or WP_Post object.
	 *
	 * @return bool
	 */
	public function isGutenberg( $post ) {
		if ( ! function_exists( 'use_block_editor_for_post' ) ) {
			return false;
		}
		return use_block_editor_for_post( $post );
	}
}
