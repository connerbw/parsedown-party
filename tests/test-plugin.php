<?php

class PluginTest extends WP_UnitTestCase {

	/**
	 * @var \Parsedownparty\Plugin
	 */
	protected $plugin;

	public function setUp() {
		parent::setUp();

		$stub = $this
			->getMockBuilder( '\ParsedownExtra' )
			->getMock();

		$stub
			->method( 'parse' )
			->willReturn( 'OK!' );

		$this->plugin = new \Parsedownparty\Plugin( $stub );
	}

	public function test_init() {
		$instance = $this->plugin::init();
		$this->assertTrue( $instance instanceof \Parsedownparty\Plugin );
	}

	public function test_hooks() {
		$this->plugin->hooks( $this->plugin );
		$this->assertEquals( 9, has_filter( 'the_content', [ $this->plugin, 'parseTheContent' ] ) );
	}

	public function test_useMarkdownForPost() {
		$post = $this->factory()->post->create_and_get();
		$this->assertFalse( $this->plugin->useMarkdownForPost( $post ) );
		update_post_meta( $post->ID, $this->plugin::METAKEY, 1 );
		$this->assertTrue( $this->plugin->useMarkdownForPost( $post ) );
	}

	public function test_useMarkdownForPost_Pressbooks_Export() {
		$GLOBALS['id'] = $this->factory()->post->create_and_get()->ID;
		unset( $GLOBALS['post'] );
		$this->assertFalse( $this->plugin->useMarkdownForPost() );
		update_post_meta( $GLOBALS['id'], $this->plugin::METAKEY, 1 );
		$this->assertTrue( $this->plugin->useMarkdownForPost() );
	}

	public function test_createMarkdownLink() {
		$post = $this->factory()->post->create_and_get();
		ob_start();
		$this->plugin->createMarkdownLink( $post );
		$output = ob_get_clean();
		$this->assertContains( '<span class="dashicons dashicons-editor-code"></span>', $output );
	}

	public function test_saveMarkdownMeta() {
		$user_id = $this->factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );
		$post = $this->factory()->post->create_and_get();

		$nonce = wp_create_nonce( $post->ID );
		$_POST[ $this->plugin::NONCE ] = $nonce;
		$_POST[ $this->plugin::METAKEY ] = 1;
		$this->plugin->saveMarkdownMeta( $post->ID );
		$this->assertTrue( $this->plugin->useMarkdownForPost( $post ) );

		$nonce = wp_create_nonce( $post->ID );
		$_POST[ $this->plugin::NONCE ] = $nonce;
		$_POST[ $this->plugin::METAKEY ] = 0;
		$this->plugin->saveMarkdownMeta( $post->ID );
		$this->assertFalse( $this->plugin->useMarkdownForPost( $post ) );
	}

	public function test_parseEditorSettings() {
		$settings = [
			'wpautop' => true,
			'media_buttons' => true,
			'tinymce' => true,
			'quicktags' => true,
		];

		$s = $this->plugin->parseEditorSettings( $settings );
		$this->assertTrue( $s['wpautop'] );
		$this->assertTrue( $s['media_buttons'] );
		$this->assertTrue( $s['tinymce'] );
		$this->assertTrue( $s['quicktags'] );

		$GLOBALS['post'] = $this->factory()->post->create_and_get();
		update_post_meta( $GLOBALS['post']->ID, $this->plugin::METAKEY, 1 );
		$s = $this->plugin->parseEditorSettings( $settings );
		$this->assertFalse( $s['wpautop'] );
		$this->assertFalse( $s['media_buttons'] );
		$this->assertFalse( $s['tinymce'] );
		$this->assertFalse( $s['quicktags'] );
	}

	public function test_overrideEditor() {
		$this->plugin->overrideEditor();
		$this->assertEmpty( wp_scripts()->registered['code-editor']->extra );

		$GLOBALS['post'] = $this->factory()->post->create_and_get();
		update_post_meta( $GLOBALS['post']->ID, $this->plugin::METAKEY, 1 );
		$this->plugin->overrideEditor();
		$this->assertContains( 'markdown', wp_scripts()->registered['code-editor']->extra['after'][2] );
	}

	public function test_parseTheContent() {
		$content = $this->plugin->parseTheContent( 'MOCKED!' );
		$this->assertEquals( 'MOCKED!', $content );

		$GLOBALS['post'] = $this->factory()->post->create_and_get();
		update_post_meta( $GLOBALS['post']->ID, $this->plugin::METAKEY, 1 );
		$content = $this->plugin->parseTheContent( 'MOCKED!' );
		$this->assertEquals( 'OK!', $content );
	}
}
