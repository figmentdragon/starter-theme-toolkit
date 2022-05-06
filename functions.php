<?php
/**
 * _THEMENAE_'s functions and definitions
 *
 * @package _THEMENAE_
 * @since _THEMENAE_ 1.0
 */

 defined( 'ABSPATH' ) || die( "Can't access directly" );

 define( 'THEME_DIR', get_template_directory() );
 define( 'THEME_URI', get_template_directory_uri() );
 define( 'VERSION', wp_get_theme( '_THEMENAE_' )->get( 'Version' ) );

require_once THEME_DIR . '/inc/theme-support.php';


 add_action( 'after_setup_theme', 'THEMENAE setup' );

 function THEMENAE_setup() {
   global $content_width;
   if ( !isset( $content_width ) ) { $content_width = 1920; }

   add_action( 'after_setup_theme', 'theme_support' );
   add_action( 'after_setup_theme', 'theme_setup' );

   add_action( 'admin_notices', 'blankslate_admin_notice' );
   add_action( 'admin_init', 'blankslate_notice_dismissed' );
   add_action( 'wp_enqueue_scripts', 'blankslate_enqueue' );
   add_action( 'wp_footer', 'blankslate_footer' );

   add_filter( 'document_title_separator', 'blankslate_document_title_separator' );
   add_filter( 'nav_menu_link_attributes', 'blankslate_schema_url', 10 );
   add_action( 'wp_body_open', 'blankslate_skip_link', 5 );

   add_filter( 'the_content_more_link', 'blankslate_read_more_link' );

   add_filter( 'excerpt_more', 'blankslate_excerpt_read_more_link' );

   add_filter( 'big_image_size_threshold', '__return_false' );
   add_filter( 'intermediate_image_sizes_advanced', 'blankslate_image_insert_override' );
   add_action( 'widgets_init', 'blankslate_widgets_init' );

   add_action( 'wp_head', 'blankslate_pingback_header' );

   add_action( 'comment_form_before', 'blankslate_enqueue_comment_reply_script' );

   add_filter( 'get_comments_number', 'blankslate_comment_count', 0 );
 }

 function blankslate_admin_notice() {
 $user_id = get_current_user_id();
 if ( !get_user_meta( $user_id, 'blankslate_notice_dismissed_4' ) && current_user_can( 'manage_options' ) )
 echo '<div class="notice notice-info"><p>' . __( '<big><strong>BlankSlate</strong>:</big> Help keep the project alive! <a href="?notice-dismiss" class="alignright">Dismiss</a> <a href="https://calmestghost.com/donate" class="button-primary" target="_blank">Make a Donation</a>', 'blankslate' ) . '</p></div>';
 }

 function blankslate_notice_dismissed() {
 $user_id = get_current_user_id();
 if ( isset( $_GET['notice-dismiss'] ) )
 add_user_meta( $user_id, 'blankslate_notice_dismissed_4', 'true', true );
 }


 function blankslate_enqueue() {
 wp_enqueue_style( 'blankslate-style', get_stylesheet_uri() );
 wp_enqueue_script( 'jquery' );
 }

 function blankslate_footer() {
 ?>
 <script>
 jQuery(document).ready(function($) {
 var deviceAgent = navigator.userAgent.toLowerCase();
 if (deviceAgent.match(/(iphone|ipod|ipad)/)) {
 $("html").addClass("ios");
 }
 if (navigator.userAgent.search("MSIE") >= 0) {
 $("html").addClass("ie");
 }
 else if (navigator.userAgent.search("Chrome") >= 0) {
 $("html").addClass("chrome");
 }
 else if (navigator.userAgent.search("Firefox") >= 0) {
 $("html").addClass("firefox");
 }
 else if (navigator.userAgent.search("Safari") >= 0 && navigator.userAgent.search("Chrome") < 0) {
 $("html").addClass("safari");
 }
 else if (navigator.userAgent.search("Opera") >= 0) {
 $("html").addClass("opera");
 }
 });
 </script>
 <?php
 }

 function blankslate_document_title_separator( $sep ) {
 $sep = '|';
 return $sep;
 }
 add_filter( 'the_title', 'blankslate_title' );
 function blankslate_title( $title ) {
 if ( $title == '' ) {
 return '...';
 } else {
 return $title;
 }
 }

 function blankslate_schema_url( $atts ) {
 $atts['itemprop'] = 'url';
 return $atts;
 }
 if ( !function_exists( 'blankslate_wp_body_open' ) ) {
 function blankslate_wp_body_open() {
 do_action( 'wp_body_open' );
 }
 }

 function blankslate_skip_link() {
 echo '<a href="#content" class="skip-link screen-reader-text">' . esc_html__( 'Skip to the content', 'blankslate' ) . '</a>';
 }

 function blankslate_read_more_link() {
 if ( !is_admin() ) {
 return ' <a href="' . esc_url( get_permalink() ) . '" class="more-link">' . sprintf( __( '...%s', 'blankslate' ), '<span class="screen-reader-text">  ' . esc_html( get_the_title() ) . '</span>' ) . '</a>';
 }
 }


 function blankslate_excerpt_read_more_link( $more ) {
 if ( !is_admin() ) {
 global $post;
 return ' <a href="' . esc_url( get_permalink( $post->ID ) ) . '" class="more-link">' . sprintf( __( '...%s', 'blankslate' ), '<span class="screen-reader-text">  ' . esc_html( get_the_title() ) . '</span>' ) . '</a>';
 }
 }

 function blankslate_image_insert_override( $sizes ) {
 unset( $sizes['medium_large'] );
 unset( $sizes['1536x1536'] );
 unset( $sizes['2048x2048'] );
 return $sizes;
 }

 function blankslate_widgets_init() {
 register_sidebar( array(
 'name' => esc_html__( 'Sidebar Widget Area', 'blankslate' ),
 'id' => 'primary-widget-area',
 'before_widget' => '<li id="%1$s" class="widget-container %2$s">',
 'after_widget' => '</li>',
 'before_title' => '<h3 class="widget-title">',
 'after_title' => '</h3>',
 ) );
 }

 function blankslate_pingback_header() {
 if ( is_singular() && pings_open() ) {
 printf( '<link rel="pingback" href="%s" />' . "\n", esc_url( get_bloginfo( 'pingback_url' ) ) );
 }
 }

 function blankslate_enqueue_comment_reply_script() {
 if ( get_option( 'thread_comments' ) ) {
 wp_enqueue_script( 'comment-reply' );
 }
 }
 function blankslate_custom_pings( $comment ) {
 ?>
 <li <?php comment_class(); ?> id="li-comment-<?php comment_ID(); ?>"><?php echo esc_url( comment_author_link() ); ?></li>
 <?php
 }

 function blankslate_comment_count( $count ) {
 if ( !is_admin() ) {
 global $id;
 $get_comments = get_comments( 'status=approve&post_id=' . $id );
 $comments_by_type = separate_comments( $get_comments );
 return count( $comments_by_type['comment'] );
 } else {
 return $count;
 }
 }
