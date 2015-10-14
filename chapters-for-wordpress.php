<?php
/*
* Plugin Name: Chapters for WordPress
* Version: 0.5
* Plugin URI: http://premium.wpmudev.org
* Description: Use [chapter] or [chapter name="Name"] shortcodes to distinguish chapters within post content, [chapter_list] and [chapter_total] to display details.
* Author: David (incsub)
* Author URI: http://premium.wpmudev.org/
* Requires at least: 3.9
* Tested up to: 4.0
*/

if ( ! defined( 'ABSPATH' ) ) exit;

class ChaptersWP {
	public $counter;
	
	var $version = '0.5';
	var $name = 'Chapters for WordPress';
	var $dir_name = 'chapters-for-wordpress';
	var $plugin_dir = '';
	var $shortcode = 'chapter';
	
	public function __construct() {
		$GLOBALS['plugin_dir'] = $this->plugin_dir;
		if ( defined( 'CHAPTER_SHORTCODE' ) ) {
			$shortcode = CHAPTER_SHORTCODE;
		}
		$this->counter = 0;
		add_shortcode( $this->shortcode, array( $this, 'chapter_shortcode' ) );
		add_shortcode( $this->shortcode . '_list', array( $this, 'chapter_list_shortcode' ) );
		add_shortcode( $this->shortcode . '_total', array( $this, 'chapter_total_shortcode' ) );
		add_action( 'save_post', array( $this, 'set_chapters' ) );
	}

	public function span( $class, $content ) {
		$result = '<span class="' . $class . '">';
		$result .= $content;
		$result .= '</span>';
		return $result;
	}

	public function enqueue_scripts() {
		wp_enqueue_script( 'chapters-js',
			plugins_url( 'js/chapters.js', __FILE__ ) );
		wp_enqueue_style( 'chapters-css',
			plugins_url( 'css/chapters.css', __FILE__ ) );
	}

	// Chapter setter
	public function set_chapters( $post_id ) {

	    // Get post content
		$post = get_post( $post_id );

		// Get all shortcodes in content
		$regex = get_shortcode_regex();
		$total_matches = array();
		preg_match_all ( "/$regex/", $post->post_content, $total_matches );

		$result = '';
		
		// Iterate over all matches
		foreach ( $total_matches as $matches ){
			foreach ( $matches as $match ){
				$chapter_name = '';
				if ( strtolower ( $match ) === '[' . $this->shortcode . ']' ) {
					$result .= '|';
				}
				$x = strpos ( strtolower ( $match ), '[' . $this->shortcode . ' ' );
				if ( $x !== false ) {
					$replace = str_replace( '[' . $this->shortcode . ' ', ' ', $match );
					$atts = shortcode_parse_atts ( str_replace( ']', '', $replace ) );
					$name = $atts['name'];
					$result .= $name . '|';
				}
			}
		}

		// Save meta data only if shortcode exists.
		if ( $result !== '' ) {
			update_post_meta( $post_id, 'chapters', $result );
		}
	}

	// Chapter getter
	public function get_chapters( $post_id ) {
		return get_post_meta( $post_id, 'chapters', true );
	}

	public function get_chapter_total( $post_id ) {
		$chapters = explode ( '|', $this->get_chapters( $post_id ) );
		return count ( $chapters ) - 1;
	}

	// Render chapter total
	public function chapter_total_shortcode( $atts, $content = null ) {
	    $a = shortcode_atts( array(
			'id' => get_the_ID(),
	    ), $atts );
		$result = $this->span( 'chapter-total', $this->get_chapter_total( $a['id'] ) );
		return $result;
	}

	// Render chapter list
	public function chapter_list_shortcode( $atts, $content = null ) {
	    $a = shortcode_atts( array(
			'id' => get_the_ID(),
			'chapter_text' => 'Chapter ',
			'chapter_separator' => ': ',
	    ), $atts );
		$result = '';
		$get_chapters = $this->get_chapters( $a['id'] );
		if ( $get_chapters !== '' ) {
			$result = '<ul class="chapter-list">';
			$chapters = explode ( '|', $get_chapters );
			for ( $i = 0; $i < count ( $chapters ) - 1; $i++ ) {
				$result .= '<li>';
				$count = $i + 1;
				$result .= '<a data-number="' . $count . '" href="#chapter-' . $count . '">';
				$result .= $this->span( 'chapter', $a['chapter_text'] );
				$result .= $this->span( 'chapter-number', $count );
				if ( $chapters[$i] !== '' ) {
					$result .= $this->span( 'chapter-separator', $a['chapter_separator'] );
					$result .= $this->span( 'chapter-title', $chapters[$i] );
				}
				$result .= '</a>';
				$result .= '</li>';
			}
			$result .= '</ul>';
		}
		return $result;
	}

	public function chapter_shortcode( $atts, $content = null ) {

		// Enqueue init script
		$this->enqueue_scripts();

		// Get shortcode attributes
		$a = shortcode_atts( array(
			'name' => '',
		), $atts );

		// Increment counter
		$count = $this->counter;
		$count += 1;
		$this->counter = $count;

		$post_id = get_the_ID();
		$total_chapters = $this->get_chapter_total( $post_id );

		$result = '';

		// Close any previous divs.
		if ( $count !== 1 ) {
			$result .= '</div>';
		}

		$result .= '<div class="chapter"';
		$result .= ' data-number="' . $count . '"';
		if ( $a['name'] ) {
			$result .= ' data-name="' . $a['name'] . '"';
		}
		// Add data-total to very first chapter div.
		if ( $count === 1 ) {
			$result .= ' data-total="' . $total_chapters . '"';
		}
		$result .= '>';	

		$result .= '<h3>';
		$result .= '<a id="chapter-' . $count . '">';
		$result .= __( 'Chapter ' ) . $count;
		if ( $a['name'] ) {
			$result .= ': ' . $a['name'];
		}
		$result .= '</a>';
		$result .= '</h3>';

		// Provide filter for users to customize output.
		$result = apply_filters( 'chapters_title',
			$result, $count, $a['name'] );
		return $result;
	}
}

global $chapterswp;
$chapterswp = new ChaptersWP();
?>