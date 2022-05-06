<?php

/**
 * Plugin Name: AJAX Hits Counter + Popular Posts Widget
 * Plugin URI: http://wordpress.org/plugins/ajax-hits-counter/
 * Description: Counts page/posts hits via AJAX and display it in admin panel. Ideal for nginx whole-page-caching. Popular Posts Widget included.
 * Version: 0.9.9
 * Author: Roman Telychko
 * Author URI: http://romantelychko.com
*/

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

/**
 * AJAX_Hits_Counter
 */
class AJAX_Hits_Counter
{
    ///////////////////////////////////////////////////////////////////////////

    protected $settings = array(
        'exclude_recurred_views'         => 0,
        'use_rapid_incrementer'         => 0,
        'dont_count_admins'             => 0,
    );

    ///////////////////////////////////////////////////////////////////////////

    protected $plugin_title = 'AJAX Hits Counter';
    protected $plugin_alias = 'ajax-hits-counter';

    ///////////////////////////////////////////////////////////////////////////

	/**
	 * AJAX_Hits_Counter::init()
	 *
	 * @return      bool
	 */
    public function init()
    {
        if( is_admin() )
        {
            // load translation
        	load_theme_textdomain( $this->plugin_alias, false, get_template_directory() . '/inc/ajax-hits-counter/languages/' );

            // admin posts table
            add_filter( 'manage_posts_columns',                         array( $this, 'adminTableColumn' ) );
            add_filter( 'manage_posts_custom_column',                   array( $this, 'adminTableRow' ), 10, 2 );
            add_filter( 'manage_edit-post_sortable_columns',            array( $this, 'adminTableSortable' ) );
            add_filter( 'request',                                      array( $this, 'adminTableOrderBy' ) );

            // admin pages table
            add_filter( 'manage_pages_columns',                         array( $this, 'adminTableColumn' ) );
            add_filter( 'manage_pages_custom_column',                   array( $this, 'adminTableRow' ), 10, 2 );
            add_filter( 'manage_edit-page_sortable_columns',            array( $this, 'adminTableSortable' ) );

            // remove cached data on every post save & update hits count
            add_action( 'save_post',                                    array( $this, 'adminSave' ) );

            // add in admin menu
            add_filter( 'admin_menu',                                   array( $this, 'adminMenu' ) );

            // init admin
            add_action('admin_init',                                    array( $this, 'adminInit' ) );

            // register importer
            require_once( ABSPATH.'wp-admin/includes/import.php' );

            register_importer(
                __CLASS__.'_Importer',
                $this->plugin_title.': '.__( 'Import from', 'writing' ).' WP-PostViews',
                __( 'Imports views count (hits) from plugin', 'writing' ).' <a href="http://wordpress.org/plugins/wp-postviews">WP-PostViews</a> '.__( 'to hits of', 'writing' ).' <a href="http://wordpress.org/plugins/'.$this->plugin_alias.'/">'.$this->plugin_title.'</a>.',
                array( $this, 'adminImporter' )
                );
        }
        else
        {
            // append script to content
            add_filter( 'the_content',                                  array( $this, 'appendScript' ),       100);
        }


        // AJAX increment hits init

        add_action( 'wp_ajax_nopriv_'.$this->plugin_alias.'-increment', array( $this, 'incrementHits' ) );
        add_action( 'wp_ajax_'.$this->plugin_alias.'-increment',        array( $this, 'incrementHits' ) );

        // shortcode
        add_shortcode( 'hits',                                          array( $this, 'getHitsShortcode' ) );

        return true;
    }


    /**
     * AJAX_Hits_Counter::getOption()
     *
     * @param       string      $name
     * @return      integer
     */
    public function getOption( $name )
    {
        $temp = intval( preg_replace( '#[^01]#', '', get_option( 'ajaxhc_'.$name, $this->settings[$name]) ) );
        return in_array( $temp, array( 0, 1 ) ) ? $temp : 0;
    }

    ///////////////////////////////////////////////////////////////////////////

	/**
	 * AJAX_Hits_Counter::appendScript()
	 *
	 * @param       string      $content
	 * @return      string
	 */
    public function appendScript( $content )
    {
        global $post;

        if( is_single() || is_page() )
        {
            if( $this->getOption('use_rapid_incrementer')==1 )          // use rapid incrementer
            {
                $incrementer_url = get_template_directory_uri() . '/inc/ajax-hits-counter/increment-hits.rapid.php?post_id='.$post->ID.'&t=';
            }
            else                                                // use simple incrementer
            {
                $incrementer_url = admin_url( 'admin-ajax.php' ).'?action='.$this->plugin_alias.'-increment&post_id='.$post->ID.'&t=';
            }
            if( !( is_user_logged_in() && current_user_can( 'manage_options' ) && $this->getOption('dont_count_admins')==1 ) )
            {

            if( $this->getOption('exclude_recurred_views')==1 ) {
            $post_id = get_the_ID();
            $content .=
            '<script type="text/javascript">'.
                '(function()'.
                '{'.
                  'var visitcount = true;
                  var visitedPosts = JSON.parse(localStorage.getItem("visitedposts"));
                  if (visitedPosts !== null) { if (visitedPosts.indexOf("'.$post_id.'") != -1) { visitcount = false; }}
                  if (visitcount != false) {'.
                    'var XHR = ( "onload" in new XMLHttpRequest() ) ? XMLHttpRequest : XDomainRequest;'.
                    'var xhr = new XHR();'.
                    'var url = "'.$incrementer_url.'" + ( parseInt( new Date().getTime() ) ) + "&r=" + ( parseInt( Math.random() * 100000 ) );'.
                    'xhr.open("GET", url, true);'.
                    'xhr.setRequestHeader( "Cache-Control", "no-cache" );'.
                    'xhr.setRequestHeader( "Content-Type", "application/json" );'.
                    'xhr.timeout = 60000;'.
                    'xhr.send();'.
                    'xhr.onreadystatechange = function()'.
                    '{'.
                        'if( this.readyState != 4 )'.
                        '{'.
                            'return;'.
                        '}'.

                        'if( this.status && this.status == 200 )'.
                        '{'.
                            'if( typeof ajaxHitsCounterSuccessCallback === "function" )'.
                            '{ '.
                                'ajaxHitsCounterSuccessCallback( this );'.
                            '}'.
                        '}'.
                        'else'.
                        '{'.
                            'if( typeof ajaxHitsCounterFailedCallback === "function" )'.
                            '{ '.
                                'ajaxHitsCounterFailedCallback( this );'.
                            '}'.
                        '}'.
                    '}
                    if (visitedPosts !== null) { visitedPosts.push("'.$post_id.'"); }
                    else { var visitedPosts = ["'.$post_id.'"]; }'.
                    'localStorage.setItem("visitedposts", JSON.stringify(visitedPosts));'.
                  '}'.
                '})();'.
            '</script>';
          } else {
            $content .=
                '<script type="text/javascript">'.
                    '(function()'.
                    '{'.
                        'var XHR = ( "onload" in new XMLHttpRequest() ) ? XMLHttpRequest : XDomainRequest;'.
                        'var xhr = new XHR();'.
                        'var url = "'.$incrementer_url.'" + ( parseInt( new Date().getTime() ) ) + "&r=" + ( parseInt( Math.random() * 100000 ) );'.
                        'xhr.open("GET", url, true);'.
                        'xhr.setRequestHeader( "Cache-Control", "no-cache" );'.
                        'xhr.setRequestHeader( "Content-Type", "application/json" );'.
                        'xhr.timeout = 60000;'.
                        'xhr.send();'.
                        'xhr.onreadystatechange = function()'.
                        '{'.
                            'if( this.readyState != 4 )'.
                            '{'.
                                'return;'.
                            '}'.

                            'if( this.status && this.status == 200 )'.
                            '{'.
                                'if( typeof ajaxHitsCounterSuccessCallback === "function" )'.
                                '{ '.
                                    'ajaxHitsCounterSuccessCallback( this );'.
                                '}'.
                            '}'.
                            'else'.
                            '{'.
                                'if( typeof ajaxHitsCounterFailedCallback === "function" )'.
                                '{ '.
                                    'ajaxHitsCounterFailedCallback( this );'.
                                '}'.
                            '}'.
                        '}'.
                    '})();'.
                '</script>';
          }
        }
      }
        return $content;
    }

    ///////////////////////////////////////////////////////////////////////////

	/**
	 * AJAX_Hits_Counter::incrementHits()
	 *
	 * @return      void
	 */
    public function incrementHits()
    {
      header( 'Content-Type: application/json;charset=utf-8' );
        header( 'X-Robots-Tag: noindex,nofollow' );

        try
        {
            if( !isset($_GET['post_id']) || empty($_GET['post_id']) )
            {
                throw new Exception();
            }

            $post_id = $_GET['post_id'];

            if( function_exists('filter_var') )
            {
                $post_id = intval( filter_var( $post_id, FILTER_SANITIZE_NUMBER_INT ) );
            }
            else
            {
                $post_id = intval( preg_replace( '#[^0-9]#', '', $post_id ) );
            }

            if( empty($post_id) )
            {
                throw new Exception();
            }

            $current_hits = intval( get_post_meta( $post_id, 'hits', true ) );

            if( empty($current_hits) )
            {
                $current_hits = 0;
            }

            if( !( is_user_logged_in() && current_user_can( 'manage_options' ) && $this->getOption('dont_count_admins')==1 ) )
            {
                $current_hits++;
                update_post_meta( $post_id, 'hits', $current_hits );
            }

            die(
                json_encode(
                    array(
                        'post_id'   => $post_id,
                        'hits'      => $current_hits,
                    )
                )
            );
        }
        catch( Exception $e )
        {
            die(
                json_encode(
                    array(
                        'post_id'   => 0,
                        'hits'      => 0
                    )
                )
            );
        }
    }

    ///////////////////////////////////////////////////////////////////////////

	/**
	 * AJAX_Hits_Counter::getHits()
	 *
	 * @param       integer     $post_id
	 * @param       integer     $hits_count_format
	 * @return      integer
	 */
    public function getHits( $post_id, $hits_count_format = 1 )
    {
        if( function_exists('filter_var') )
        {
            $post_id = intval( filter_var( $post_id, FILTER_SANITIZE_NUMBER_INT ) );
        }
        else
        {
            $post_id = intval( preg_replace( '#[^0-9]#', '', $post_id ) );
        }

        if( empty($post_id) )
        {
            return 0;
        }

        $hits = get_post_meta( $post_id, 'hits', true );

        if( empty($hits) )
        {
            return 0;
        }

        return $this->hitsCountFormat( intval($hits), $hits_count_format );
    }

    ///////////////////////////////////////////////////////////////////////////

    /**
     * AJAX_Hits_Counter::hitsCountFormat()
     *
     * @param       integer           $number
     * @param       integer           $format
     * @return      string
     */
    public function hitsCountFormat( $number, $format = 1 )
    {
        $number = intval($number);

        switch( $format )
        {
            default:
            case 1:     // 12345
                return $number;
                break;
            case 2:     // 12,345
                return number_format( $number, 0, '', ',' );
                break;
            case 3:     // 12 345
                return number_format( $number, 0, '', ' ' );
                break;
            case 4:     // 12.345
                return number_format( $number, 0, '', '.' );
                break;
            case 5:     // 12k
            case 6:     // 12K
                $unitElements   = ( $format == 5 ) ? array( '', 'K', 'M', 'G', 'T', 'P' ) : array( '', 'k', 'm', 'g', 't', 'p' );
                $unitItem       = floor( log( intval($number), 1000 ) );

                if( !isset($unitElements[$unitItem]) )
                {
                    $unitItem = count($unitElements);
                }

                return round( ( $number / pow( 1000, $unitItem ) ), 0 ).$unitElements[$unitItem];
                break;
        }
    }

    ///////////////////////////////////////////////////////////////////////////

	/**
	 * AJAX_Hits_Counter::getHitsShortcode()
	 *
	 * @param       array           $atts
	 * @return      integer
	 */
    public function getHitsShortcode( $atts )
    {
        $post_id = isset($atts['id']) && !empty($atts['id']) ? $atts['id'] : get_the_ID();

        return $this->getHits( $post_id );
    }

    ///////////////////////////////////////////////////////////////////////////

	/**
	 * AJAX_Hits_Counter::adminInit()
	 *
	 * @return      bool
	 */
    public function adminInit()
    {
        global $current_user;

        if( isset($current_user->roles) && !empty($current_user->roles) && in_array( 'administrator', $current_user->roles ) )
        {
            // add meta box
            add_action( 'add_meta_boxes',                               array( $this, 'adminAddMetaBox' ) );
        }
    }

    ///////////////////////////////////////////////////////////////////////////

	/**
	 * AJAX_Hits_Counter::adminMenu()
	 *
	 * @return      bool
	 */
    public function adminMenu()
    {
	    // create new top-level menu
	    add_theme_page( 'Hits counter settings', 'Hits counter settings', 'administrator', 'writing-hitscount', array( $this, 'adminSettingsPage' ) );

	    // call register settings function
	    add_action( 'admin_init', array( $this, 'adminSettingsRegister' ) );
    }

    ///////////////////////////////////////////////////////////////////////////

	/**
	 * AJAX_Hits_Counter::adminSettingsPage()
	 *
	 * @return      bool
	 */
    public function adminSettingsPage()
    {
        ///////////////////////////////////////////////////////////////////////

        $this->settings['exclude_recurred_views']    = $this->getOption('exclude_recurred_views');
        $this->settings['use_rapid_incrementer']    = $this->getOption('use_rapid_incrementer');
        $this->settings['dont_count_admins']        = $this->getOption('dont_count_admins');

        ///////////////////////////////////////////////////////////////////////

        echo(
            '<div class="wrap">'.
                '<h2>'.$this->plugin_title.': '.__( 'Settings', 'writing' ).'</h2>'.
                    '<form method="post" action="options.php">'
            );

        settings_fields( 'ajaxhc' );

        echo(
            '<table class="form-table">'.
                '<tr valign="top">'.
                    '<td colspan="2">'.
                        '<input type="checkbox" '.( $this->settings['exclude_recurred_views']==1 ? ' checked="checked"' : '' ).' name="ajaxhc_exclude_recurred_views" id="ajaxhc_exclude_recurred_views" value="1" />'.
                        '<label for="ajaxhc_exclude_recurred_views">&nbsp;'.__( 'Don\'t Count Repeated Views.', 'writing' ).'</label>'.
                    '</td>'.
                '</tr>'.
                '<tr valign="top">'.
                    '<td colspan="2">'.
                        '<input type="checkbox" '.( $this->settings['use_rapid_incrementer']==1 ? ' checked="checked"' : '' ).' name="ajaxhc_use_rapid_incrementer" id="ajaxhc_use_rapid_incrementer" value="1" />'.
                        '<label for="ajaxhc_use_rapid_incrementer">&nbsp;'.__( 'Use very fast (rapid) Hits Counter Script (ATTENTION! Required Wordpress version 3.4 or higher', 'writing' ).')</label>'.
                    '</td>'.
                '</tr>'.
                '<tr valign="top">'.
                    '<td colspan="2">'.
                        '<input type="checkbox" '.( $this->settings['dont_count_admins']==1 ? ' checked="checked"' : '' ).' name="ajaxhc_dont_count_admins" id="ajaxhc_dont_count_admins" value="1" />'.
                        '<label for="ajaxhc_dont_count_admins">&nbsp;'.__( 'Don\'t count hits of admin users', 'writing' ).'</label>'.
                    '</td>'.
                '</tr>'.
            '</table>'
            );

        submit_button();

        echo(
                '</form>'.
            '</div>'
            );
    }

    ///////////////////////////////////////////////////////////////////////////

	/**
	 * AJAX_Hits_Counter::adminSettingsPage()
	 *
	 * @return      bool
	 */
    public function adminSettingsRegister()
    {
        // register settings
        register_setting( 'ajaxhc', 'ajaxhc_exclude_recurred_views' );
        register_setting( 'ajaxhc', 'ajaxhc_use_rapid_incrementer' );
        register_setting( 'ajaxhc', 'ajaxhc_dont_count_admins' );
    }

    ///////////////////////////////////////////////////////////////////////////

	/**
	 * AJAX_Hits_Counter::adminSave()
	 *
	 * @param       integer     $post_id
	 * @return      bool
	 */
    public function adminSave( $post_id )
    {
        // skip for autosave
        if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
        {
            return;
        }

        // update hits count
        if( isset($_POST['post_type']) && in_array( $_POST['post_type'], array( 'post', 'page' ) ) )
        {
            $hits = ( isset($_POST['hits']) && !empty($_POST['hits']) ? intval( preg_replace( '/[^0-9]/', '', $_POST['hits'] ) ) : 0 );

            if( $hits > 0 )
            {
                $hits_exists = get_post_meta( $post_id, 'hits', true );

                if( $hits_exists===false )
                {
                    add_post_meta( $post_id, 'hits', $hits, true );
                }
                else
                {
                    update_post_meta( $post_id, 'hits', $hits );
                }
            }
        }



        return true;
    }

    ///////////////////////////////////////////////////////////////////////////

	/**
	 * AJAX_Hits_Counter::adminTableColumn()
	 *
	 * @param       array     $column
	 * @return      array
	 */
    public function adminTableColumn( $column )
    {
        $column['hits'] = __( 'Hits', 'writing' );

        return $column;
    }

    ///////////////////////////////////////////////////////////////////////////

	/**
	 * AJAX_Hits_Counter::adminTableRow()
	 *
	 * @param       string      $column_name
	 * @param       integer     $post_id
	 * @return      string
	 */
    public function adminTableRow( $column_name, $post_id )
    {
        if( $column_name=='hits' )
        {
            $current_hits = get_post_meta( $post_id, 'hits', true );

            if( empty($current_hits) )
            {
                $current_hits = 0;
            }

            echo( $current_hits );
        }
    }

    ///////////////////////////////////////////////////////////////////////////

	/**
	 * AJAX_Hits_Counter::adminTableSortable()
	 *
	 * @param       array       $column
	 * @return      array
	 */
    public function adminTableSortable( $column )
    {
        $column['hits'] = 'hits';

        return $column;
    }

    ///////////////////////////////////////////////////////////////////////////

	/**
	 * AJAX_Hits_Counter::adminTableOrderBy()
	 *
	 * @param       array       $vars
	 * @return      array
	 */
    public function adminTableOrderBy( $vars )
    {
	    if( isset($vars['orderby']) && $vars['orderby']=='hits' )
	    {
		    $vars = array_merge(
        		    $vars,
        		    array(
			            'meta_key'  => 'hits',
			            'orderby'   => 'meta_value_num'
            		    )
        		    );
	    }

	    return $vars;
    }

    ///////////////////////////////////////////////////////////////////////////

	/**
	 * AJAX_Hits_Counter::adminAddMetaBox()
	 *
	 * @return      bool
	 */
    public function adminAddMetaBox()
    {
        add_meta_box(
            'hits',
            'Hits count',
            array( $this, 'adminAddMetaBoxPrint' ),
            'post',
            'side',
            'default'
            );

        add_meta_box(
            'hits',
            'Hits count',
            array( $this, 'adminAddMetaBoxPrint' ),
            'page',
            'side',
            'default'
            );

        return true;
    }

    ///////////////////////////////////////////////////////////////////////////

	/**
	 * AJAX_Hits_Counter::adminAddMetaBoxPrint()
	 *
	 * @param       string          $post
	 * @param       string          $metabox
	 * @return      void
	 */
    public function adminAddMetaBoxPrint( $post, $metabox )
    {
        wp_nonce_field( plugin_basename( __FILE__ ), 'ajax_hits_counter_nonce' );

        $hits = get_post_meta( $post->ID, 'hits', true );

        echo(
            '<label for="hits">'.__( 'Hits count', 'writing' ).'</label>&nbsp;&nbsp;'.
            '<input type="text" name="hits" id="hits" value="'.( !empty($hits) ? $hits : '0' ).'" />'
            );
    }

    ///////////////////////////////////////////////////////////////////////////

	/**
	 * AJAX_Hits_Counter::adminImporter()
	 *
	 * @return      html
	 */
    public function adminImporter()
    {
        ///////////////////////////////////////////////////////////////////////

	    $html =
		    '<div class="wrap">'.
		        '<h2>'.$this->plugin_title.': '.__( 'Import from', 'writing' ).' WP-PostViews</h2>'.
		        '<div class="clear"></div>';

        ///////////////////////////////////////////////////////////////////////

	    global $wpdb;

        ///////////////////////////////////////////////////////////////////////

        if( !isset($_POST['start']) || empty($_POST['start']) )
        {
            ///////////////////////////////////////////////////////////////////

            $q = '
                SELECT
	                count(post_id) as c
                FROM
                    '.$wpdb->postmeta.'
                WHERE
                    meta_key = \'views\'';

            $total = $wpdb->get_var($q);

            ///////////////////////////////////////////////////////////////////

            if( empty($total) )
            {
	            $html .=
	                '<p>'.__( 'We found', 'writing' ).' <strong>'.__( 'no items', 'writing' ).'</strong> '.__( 'to import from WP-PostViews plugin', 'writing' ).'.</p>'.
	                '<p>'.__( 'Have I hice day', 'writing' ).' ;-)</p>';
            }
            else
            {
                $html .=
                    '<p>'.__( 'We found', 'writing' ).' <strong>'.$total.' '.__( 'items', 'writing' ).'</strong> '.__( 'to import from WP-PostViews plugin', 'writing' ).'.</p>'.
                    '<p>'.__( 'To start import please click "Start import" button.', 'writing' ).'</p>'.
                    '<form method="post">'.
                        wp_nonce_field( plugin_basename( __FILE__ ), 'ajax_hits_counter_nonce', true, false ).
                        '<input type="submit" value="'.__( 'Start import', 'writing' ).'" class="button" name="start" />'.
                    '</form>';
            }

            ///////////////////////////////////////////////////////////////////
        }
        else
        {
            ///////////////////////////////////////////////////////////////////

            $q = '
                SELECT
	                post_id,
	                meta_value      as views
                FROM
                    '.$wpdb->postmeta.'
                WHERE
                    meta_key = \'views\'';

            $results = $wpdb->get_results($q);

            ///////////////////////////////////////////////////////////////////

            if( !empty($results) )
            {
                $status = array(
                    'total'         => count($results),
                    'inserted'      => 0,
                    'updated'       => 0,
                    'skipped'       => 0,
                    );

                foreach( $results as $r )
                {
                    $hits = get_post_meta( $r->post_id, 'hits', true );

                    if( $hits===false )
                    {
                        add_post_meta( $r->post_id, 'hits', $r->views, true );

                        $status['inserted']++;
                    }
                    else
                    {
                        if( $hits < $r->views )
                        {
                            update_post_meta( $r->post_id, 'hits', $r->views );

                            $status['updated']++;
                        }
                        else
                        {
                            $status['skipped']++;
                        }
                    }
                }

	            $html .=
	                '<p>'.__( 'Imported', 'writing' ).' <strong>'.$status['total'].' '.__( 'items', 'writing' ).'</strong> '.
	                     '('.__( 'inserted','writing' ).': <strong>'.$status['inserted'].'</strong>, '.__( 'updated', 'writing' ).': <strong>'.$status['updated'].'</strong>, '.__( 'skipped', 'writing' ).': <strong>'.$status['skipped'].'</strong>)</p>'.
	                '<p>'.__( 'Thank you for choosing our plugin.', 'writing' ).'</p>';
            }
            else
            {
	            $html .=
	                '<p>'.__( 'We found', 'writing' ).' <strong>'.__( 'no items', 'writing' ).'</strong> '.__( 'to import from WP-PostViews plugin', 'writing' ).'.</p>'.
	                '<p>'.__( 'Have I hice day', 'writing' ).' ;-)</p>';
            }

            ///////////////////////////////////////////////////////////////////
        }

        ///////////////////////////////////////////////////////////////////////

        $html .=
	        '</div>';

        die( $html );

        ///////////////////////////////////////////////////////////////////////
    }

    ///////////////////////////////////////////////////////////////////////////
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

// init Ajax Hits Counter
$ahc = new AJAX_Hits_Counter();
$ahc->init();

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function ajax_hits_counter_get_hits( $post_id, $hits_count_format = 1 )
{
    $ahc = new AJAX_Hits_Counter();

    return
        $ahc->getHits( $post_id, $hits_count_format );
}