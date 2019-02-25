<?php

use \Parsedownparty\Plugin;

class PluginTest extends WP_UnitTestCase {

	/**
	 * @var \Parsedownparty\Plugin
	 */
	protected $plugin;

	protected $useBlockEditor = false;

	/**
	 * Block editor (Gutenberg) currently not supported
	 *
	 * @return bool
	 */
	public function useBlockEditor() {
		return $this->useBlockEditor;
	}

	public function setUp() {
		parent::setUp();

		$this->useBlockEditor = false;
		add_filter( 'use_block_editor_for_post', [ $this, 'useBlockEditor' ] );

		$stub1 = $this
			->getMockBuilder( '\ParsedownExtra' )
			->getMock();
		$stub1
			->method( 'parse' )
			->willReturn( 'OK! (HTML)' );

		$stub2 = $this
			->getMockBuilder( '\League\HTMLToMarkdown\HtmlConverter' )
			->getMock();
		$stub2
			->method( 'convert' )
			->willReturn( 'OK! (Markdown)' );

		$this->plugin = new Plugin( $stub1, $stub2 );
	}

	public function test_init() {
		$instance = Plugin::init();
		$this->assertTrue( $instance instanceof \Parsedownparty\Plugin );
	}

	public function test_hooks() {
		$this->plugin->hooks( $this->plugin );
		$this->assertEquals( 9, has_filter( 'the_content', [ $this->plugin, 'parseTheContent' ] ) );
	}

	public function test_useMarkdownForPost() {
		$post = $this->factory()->post->create_and_get();
		$this->assertFalse( $this->plugin->useMarkdownForPost( $post ) );
		update_post_meta( $post->ID, Plugin::METAKEY, 1 );
		$this->assertTrue( $this->plugin->useMarkdownForPost( $post ) );

		// Auto enable
		$new_post = $this->factory()->post->create_and_get();
		add_filter( 'parsedownparty_autoenable', '__return_true' );
		$this->assertTrue( $this->plugin->useMarkdownForPost( $post ) );

		// Gutenberg currently not supported
		$this->useBlockEditor = true;
		$this->assertFalse( $this->plugin->useMarkdownForPost( $post ) );
		remove_filter( 'parsedownparty_autoenable', '__return_true' );		
	}

	public function test_useMarkdownForPost_Pressbooks_Export() {
		$GLOBALS['id'] = $this->factory()->post->create_and_get()->ID;
		unset( $GLOBALS['post'] );
		$this->assertFalse( $this->plugin->useMarkdownForPost() );
		update_post_meta( $GLOBALS['id'], Plugin::METAKEY, 1 );
		$this->assertTrue( $this->plugin->useMarkdownForPost() );
	}

	public function test_createMarkdownLink() {
		$post = $this->factory()->post->create_and_get();

		update_post_meta( $post->ID, Plugin::METAKEY, 1 );
		ob_start();
		$this->plugin->createMarkdownLink( $post );
		$output = ob_get_clean();
		$this->assertContains( '<span class="dashicons dashicons-editor-code"></span>', $output );
		$this->assertContains( 'Disable', $output );

		delete_post_meta( $post->ID, Plugin::METAKEY );
		ob_start();
		$this->plugin->createMarkdownLink( $post );
		$output = ob_get_clean();
		$this->assertContains( '<span class="dashicons dashicons-editor-code"></span>', $output );
		$this->assertContains( 'Enable', $output );

		// Gutenberg currently not supported
		$this->useBlockEditor = true;
		ob_start();
		$this->plugin->createMarkdownLink( $post );
		$output = ob_get_clean();
		$this->assertEmpty( $output );
	}

	public function test_saveMarkdownMeta() {
		$user_id = $this->factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );
		$post = $this->factory()->post->create_and_get();

		$nonce = wp_create_nonce( $post->ID );
		$_POST[ Plugin::NONCE ] = $nonce;
		$_POST[ Plugin::METAKEY ] = 1;
		$this->plugin->saveMarkdownMeta( $post->ID, $post );
		$this->assertTrue( $this->plugin->useMarkdownForPost( $post ) );
		$this->assertEquals( 'OK! (Markdown)', get_post( $post->ID )->post_content );

		$nonce = wp_create_nonce( $post->ID );
		$_POST[ Plugin::NONCE ] = $nonce;
		$_POST[ Plugin::METAKEY ] = 0;
		$this->plugin->saveMarkdownMeta( $post->ID, $post );
		$this->assertFalse( $this->plugin->useMarkdownForPost( $post ) );
		$this->assertEquals( 'OK! (HTML)', get_post( $post->ID )->post_content );

		// Gutenberg currently not supported
		$this->useBlockEditor = true;
		$nonce = wp_create_nonce( $post->ID );
		$_POST[ Plugin::NONCE ] = $nonce;
		$_POST[ Plugin::METAKEY ] = 1;
		$this->plugin->saveMarkdownMeta( $post->ID, $post );
		$this->assertFalse( $this->plugin->useMarkdownForPost( $post ) );
		$this->assertEquals( 'OK! (HTML)', get_post( $post->ID )->post_content );
	}

	public function test_parseEditorSettings() {
		$settings = [
			'wpautop' => true,
			'media_buttons' => true,
			'tinymce' => true,
			'quicktags' => true,
		];

		$GLOBALS['pagenow'] = 'post.php';
		$s = $this->plugin->parseEditorSettings( $settings );
		$this->assertTrue( $s['wpautop'] );
		$this->assertTrue( $s['media_buttons'] );
		$this->assertTrue( $s['tinymce'] );
		$this->assertTrue( $s['quicktags'] );

		$GLOBALS['pagenow'] = 'revisions.php';
		$GLOBALS['post'] = $this->factory()->post->create_and_get();
		update_post_meta( $GLOBALS['post']->ID, Plugin::METAKEY, 1 );
		$s = $this->plugin->parseEditorSettings( $settings );
		$this->assertTrue( $s['wpautop'] );
		$this->assertTrue( $s['media_buttons'] );
		$this->assertTrue( $s['tinymce'] );
		$this->assertTrue( $s['quicktags'] );

		$GLOBALS['pagenow'] = 'post.php';
		$s = $this->plugin->parseEditorSettings( $settings );
		$this->assertFalse( $s['wpautop'] );
		$this->assertFalse( $s['media_buttons'] );
		$this->assertFalse( $s['tinymce'] );
		$this->assertFalse( $s['quicktags'] );
	}

	public function test_overrideEditor() {
		$GLOBALS['pagenow'] = 'post.php';
		$this->plugin->overrideEditor();
		$this->assertEmpty( wp_scripts()->registered['code-editor']->extra );

		$GLOBALS['pagenow'] = 'revisions.php';
		$GLOBALS['post'] = $this->factory()->post->create_and_get();
		update_post_meta( $GLOBALS['post']->ID, Plugin::METAKEY, 1 );
		$this->assertEmpty( wp_scripts()->registered['code-editor']->extra );
		$this->plugin->overrideEditor();
		$this->assertEmpty( wp_scripts()->registered['code-editor']->extra );

		$GLOBALS['pagenow'] = 'post.php';
		$this->plugin->overrideEditor();
		$this->assertContains( 'markdown', wp_scripts()->registered['code-editor']->extra['after'][2] );
	}

	public function test_parseTheContent() {
		$GLOBALS['post'] = $this->factory()->post->create_and_get();
		update_post_meta( $GLOBALS['post']->ID, Plugin::METAKEY, 1 );
		$content = $this->plugin->parseTheContent( 'MOCKED!' );
		$this->assertEquals( 'OK! (HTML)', $content );
		$this->assertEquals( $content, get_transient( Plugin::METAKEY . "_{$GLOBALS['post']->ID}" ) ); // Cached

		update_post_meta( $GLOBALS['post']->ID, Plugin::METAKEY, 0 );
		$content = $this->plugin->parseTheContent( 'MOCKED!'  );
		$this->assertEquals( 'MOCKED!', $content );
		$this->assertEmpty( get_transient( Plugin::METAKEY . "_{$GLOBALS['post']->ID}" ) ); // Cache deleted

		global $wp_query;
		$wp_query->is_preview = true;
		update_post_meta( $GLOBALS['post']->ID, Plugin::METAKEY, 1 );
		$content = $this->plugin->parseTheContent( 'MOCKED!' );
		$this->assertEquals( 'OK! (HTML)', $content );
		$this->assertEmpty( get_transient( Plugin::METAKEY . "_{$GLOBALS['post']->ID}" ) ); // No cache

		$wp_query->is_preview = true;
		update_post_meta( $GLOBALS['post']->ID, Plugin::METAKEY, 0 );
		$content = $this->plugin->parseTheContent( 'MOCKED!'  );
		$this->assertEquals( 'MOCKED!', $content );
		$this->assertEmpty( get_transient( Plugin::METAKEY . "_{$GLOBALS['post']->ID}" ) ); // No Cache, no Markdown
	}


	/**
	 * @see https://github.com/connerbw/parsedown-party/issues/13
	 * @see https://github.com/thephpleague/html-to-markdown/issues/165
	 */
	public function test_paragraphs_following_tables() {
		$test = <<< EOF
<p>line 1</p>
<p>line 2</p>
<table style="border-collapse: collapse; width: 100%;" border="1">
<tbody>
<tr>
<td style="width: 33.3333%;">d</td>
<td style="width: 33.3333%;">d</td>
<td style="width: 33.3333%;">d</td>
</tr>
<tr>
<td style="width: 33.3333%;">d</td>
<td style="width: 33.3333%;">d</td>
<td style="width: 33.3333%;">d</td>
</tr>
</tbody>
</table>
<p>line 4</p>
<p>line 5</p>
EOF;

		$c = new \League\HTMLToMarkdown\HtmlConverter( \Parsedownparty\Plugin::CONVERTER_OPTIONS );
		$markdown = $c->convert( $test );
		$this->assertNotContains( '</table>line 4', $markdown );
		$this->assertContains( "</table>\n\nline 4\n\nline 5", $markdown );
	}

}
