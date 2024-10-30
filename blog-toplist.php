<?php
/*
Plugin Name: Blog Toplist
Plugin URI: http://www.iklan-promosi-percuma.com
Description: Listing another blog site from your site with alexa,technorati and pagerank ranking.
Version: 1.0.6
Author: amaniaah
Author URI: http://www.iklan-promosi-percuma.com
License: GPL2
*/
/*  Copyright 2011  Amaniaah  (email : amaniaah@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


include_once(ABSPATH."wp-content/plugins/blog-toplist/function.php");
register_activation_hook(__FILE__, "blogtoplist_actived");

// set global path variables
define('BTL_ICON', plugins_url( 'images/blog16.png' , __FILE__ ));

//load class
if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

//Create a new list table package that extends the core WP_List_Table class
class BTL_List_Table extends WP_List_Table {

    //Set up a constructor that references the parent constructor
        function __construct(){
        global $status, $page;

        //Set parent defaults
        parent::__construct( array(
            'singular'  => 'blog',     //singular name of the listed records
            'plural'    => 'blogs',    //plural name of the listed records
            'ajax'      => false        //does this table support ajax?
        ) );

    }

    //display data
        function column_default($item, $column_name){

                //find user
                $blog_user = get_userdata($item['user_id']);

                //change status name
                if($item['blog_status']=='0'){
                        $bstatus = "Pending";
                }else{
                        $bstatus = "Active";
                }

                switch($column_name){
            case 'user_id': echo $blog_user->user_login; break;
            case 'blog_url': echo '<a href="http://'.$item['blog_url'].'" title="'.$item['blog_descr'].'">'.$item['blog_url'].'</a>'; break;
            case 'blog_rank_alexa': echo $item['blog_rank_alexa']; break;
            case 'blog_rank_techno': echo $item['blog_rank_techno']; break;
            case 'blog_rank_page': echo $item['blog_rank_page']; break;
            case 'blog_status': echo $bstatus; break;
                return $item[$column_name];
            default:
                return print_r($item,true); //Show the whole array for troubleshooting purposes
        }
    }

        //display menu
    function column_blog_title($item){

        //Build row actions
        $actions = array(
            'update'      => sprintf('<a href="?page=%s&action=%s&blog=%s">Update</a>',$_REQUEST['page'],'update',$item['id']),
            'settings'      => sprintf('<a href="?page=%s-%s&blog=%s">Settings</a>',$_REQUEST['page'],'settings',$item['id']),
            'delete'    => sprintf('<a href="?page=%s&action=%s&blog=%s" onClick="return confirm(\'Are you sure to delete this web/blog?\');">Delete</a>',$_REQUEST['page'],'delete',$item['id']),
        );

        //Return the title contents
        return sprintf('%1$s <span style="color:silver">(id:%2$s)</span>%3$s',
            /*$1%s*/ $item['blog_title'],
            /*$2%s*/ $item['id'],
            /*$3%s*/ $this->row_actions($actions)
        );
    }

    //checkboxes for bulk actions.
        function column_cb($item){
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            /*$1%s*/ $this->_args['singular'],  //Let's simply repurpose the table's singular label ("movie")
            /*$2%s*/ $item['id']                //The value of the checkbox should be the record's id
        );
    }

    //build column to display
        function get_columns(){
        $columns = array(
            'cb'        => '<input type="checkbox" />', //Render a checkbox instead of text
            'blog_title'     => 'Title',
            'user_id'    => 'Username',
            'blog_url'    => 'URL',
                        'blog_rank_alexa'=>__('Alexa'),
                        'blog_rank_techno'=>__('Technorati'),
                        'blog_rank_page'=>__('Pagerank'),
                        'blog_status'=>__('Status'),
        );
        return $columns;
    }

        //column to be sortable ASC or DESC
    function get_sortable_columns() {
        $sortable_columns = array(
            'user_id'     => array('user_id',true),     //true means its already sorted
            'blog_title'     => array('blog_title',true),     //true means its already sorted
            'blog_rank_alexa'    => array('blog_rank_alexa',false),
            'blog_rank_techno'  => array('blog_rank_techno',false),
            'blog_rank_page'  => array('blog_rank_page',false),
            'blog_status'  => array('blog_status',false)
        );
        return $sortable_columns;
    }

        //display menu by rows
    function get_bulk_actions() {
        $actions = array(
            'delete'    => 'Delete'
        );
        return $actions;
    }

        //process for actions
    function process_bulk_action() {
        global $wpdb;
        //Detect when a bulk action is being triggered...
        if( 'delete'===$this->current_action() ) {
                                btl_delete($_GET['blog']);
        }
        if( 'edit'===$this->current_action() ) {
                                btl_edit($_GET['blog']);
        }
        if( 'update'===$this->current_action() ) {
                                btl_update($_GET['blog']);
        }
    }

        //prepare data for display
    function prepare_items() {
        global $wpdb;

        //data display by page
		$per_page = 5;

                //define column header
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();

		//build an array to be used by the class for column headers
		$this->_column_headers = array($columns, $hidden, $sortable);
		
		//handle bulk actions however you see fit
		$this->process_bulk_action();
		
		//instead of querying database
		$data = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."blogtoplist;", ARRAY_A);

        //checks for sorting input and sorts the data in our array accordingly
                function usort_reorder($a,$b){
            $orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'blog_title'; //If no sort, default to title
            $order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'asc'; //If no order, default to asc
            $result = strcmp($a[$orderby], $b[$orderby]); //Determine sort order
            return ($order==='asc') ? $result : -$result; //Send final sort direction to usort
        }
        usort($data, 'usort_reorder');

        //figure out what page the user is currently looking at
                $current_page = $this->get_pagenum();

        //check how many items are in our data array
                $total_items = count($data);

        //to ensure that the data is trimmed to only the current page
                $data = array_slice($data,(($current_page-1)*$per_page),$per_page);

        //add *sorted* data to the items property
                $this->items = $data;

        //register pagination options & calculations
                $this->set_pagination_args( array(
            'total_items' => $total_items,                  //WE have to calculate the total number of items
            'per_page'    => $per_page,                     //WE have to determine how many items to show on a page
            'total_pages' => ceil($total_items/$per_page)   //WE have to calculate the total number of pages
        ) );
    }

}


//display admin menus
function btl_menu_items(){
    add_menu_page('Blog Toplist', 'Blog Toplist', 'activate_plugins', 'blog-toplist', 'btl_list_page',BTL_ICON);
        add_submenu_page( 'blog-toplist', 'Listing', 'Listing', 'activate_plugins', 'blog-toplist', 'btl_list_page' );
        add_submenu_page( 'blog-toplist', 'Settings', 'Settings', 'activate_plugins', 'blog-toplist-settings', 'btl_settings' );
        add_submenu_page( 'blog-toplist', 'Embed Code', 'Embed Code', 'activate_plugins', 'blog-toplist-mode', 'btl_codes' );
}
add_action('admin_menu', 'btl_menu_items');

//render page listing page
function btl_list_page(){

    //Create an instance of our package class...
    $testListTable = new BTL_List_Table();
    //Fetch, prepare, sort, and filter our data...
    $testListTable->prepare_items();

    ?>
    <div class="wrap">

        <div id="icon-users" class="icon32"><br/></div>
        <h2>Blog Toplist</h2>

        <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
        <form id="blogs-filter" method="get">
            <!-- For plugins, we also need to ensure that the form posts back to our current page -->
            <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
            <!-- Now we can render the completed list table -->
            <?php $testListTable->display() ?>
        </form>

    </div>
    <?php
}

//render submenu page
function btl_codes(){
?>
                <div class="wrap admin_wpbannerslite_wrap">
                        <div id="icon-options-general" class="icon32"><br /></div><h2>Blog Toplist - Embed Code</h2>
                        <div class="postbox-container" style="width: 100%;">
                                <div class="metabox-holder">
                                        <div class="meta-box-sortables ui-sortable">
                                                <div class="postbox">
                                                        <!--<div class="handlediv" title="Click to toggle"><br></div>-->
                                                        <h3 class="hndle" style="cursor: default;"><span>Embed codes for listing Blog Toplist any where.</span></h3>
                                                        <div class="inside">
                                                                <table class="wpbannerslite_useroptions">
                                                                        <tr>
                                                                                <th>PHP Code:</th>
                                                                                <td><textarea id="wpbannerslite_php" style="width: 98%; height: 50px;" onclick="this.focus();this.select();" readonly="readonly"><?php echo htmlspecialchars('<?php if (function_exists("bloglist_show")) echo bloglist_show(10); ?>', ENT_QUOTES);?></textarea><br /><em>This is PHP-code. You can insert it into desired place of your active theme by editing appropriate files (Ex.: header.php, single.php, or any other).</em></td>
                                                                        </tr>
                                                                        <tr>
                                                                                <th>HTML+JavaScript Code:</th>
                                                                                <td><textarea id="wpbannerslite_js" style="width: 98%; height: 50px;" onclick="this.focus();this.select();" readonly="readonly"><?php echo htmlspecialchars('<div id="10"></div><script defer="defer" type="text/javascript" src="'.get_bloginfo("wpurl").'/wp-content/plugins/blog-toplist/blog-show.php?top=10"></script>', ENT_QUOTES);?></textarea><br /><em>This is HTML+JavaScript code. You can insert it into desired place of your active theme by editing appropriate files or you can insert it into body of your posts, pages and text-widgets. </em><textarea id="wpbannerslite_js" style="width: 98%; height: 70px;" onclick="this.focus();this.select();" readonly="readonly"><?php echo htmlspecialchars('<link rel="stylesheet" type="text/css" href="'.get_bloginfo("wpurl").'/wp-content/plugins/blog-toplist/images/style.css?ver=1.0" media="screen" /><div id="10"></div><script defer="defer" type="text/javascript" src="'.get_bloginfo("wpurl").'/wp-content/plugins/blog-toplist/blog-show.php?top=10"></script>', ENT_QUOTES);?></textarea><em><br />You also can use this embed code with non-WordPress part of your website or other website (it uses jQuery).</em></td>
                                                                        </tr>
                                                                        <tr>
                                                                                <th>Shortcode:</th>
                                                                                <td><textarea id="wpbannerslite_shortcode" style="width: 98%; height: 50px;" onclick="this.focus();this.select();" readonly="readonly"><?php echo htmlspecialchars('[bloglist top="10"]', ENT_QUOTES);?></textarea><br /><em>This is shortcode you can insert it into body of your posts and pages. Default 10 blog or website listing.</em></td>
                                                                        </tr>
                                                                </table>
                                                                <br class="clear">
                                                        </div>
                                                </div>
                                        </div>
                                </div>
                        </div>
                </div>
<?php
}

//shortcode to display
function bloglist_show( $atts ){
        extract(shortcode_atts(array(
            'top' => $atts,
        ), $atts));

        global $wpdb,$current_user;
        $related = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."blogtoplist where blog_status='1' order by blog_rank_alexa desc limit $top;");

        $chkusr = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."blogtoplist where user_id='".$current_user->ID."'");

        if($chkusr->user_id==$current_user->ID){
                $retval .= '';
        }else{
                if($_POST){
                        $newblog = $_POST['addblog'];
                        $newid = $current_user->ID;
                        $wpdb->get_results("INSERT INTO wp_blogtoplist (user_id,blog_url) VALUES ('".$newid."','".$newblog."');");
                        $retval .= '<div class="addblog">Add success '.$_POST['addblog'].'. Your web/blog will be review.</div>';
                }else{
                $retval .= '<div class="addblog"><a href="">Add Your Blog</a></div><form id="webid" action="" method="POST"><div>http://<input type="text" id="addblog" name="addblog" value=""><input type="submit" value="Add"></form></div>';
                }
        }

        $retval .= '<table width="100%" cellpadding="2" cellspacing="2" class="btl_table">';
        $retval .= '<tr>';
        $retval .= '<th>Rank</th><th>Web/Blog</th><th>Title</th>';
		if(get_option('btl_ar')=="on"){ $retval .= '<th>Alexa</th>';}else{ $retval .= '';}
		if(get_option('btl_tr')=="on"){ $retval .= '<th>Technorati</th>';}else{ $retval .= '';}
		if(get_option('btl_pr')=="on"){ $retval .= '<th>Pagerank</th>';}else{ $retval .= '';}
        $retval .= '</tr>';


        if ( $related ) {
                $ii=0;
                foreach($related as $r) {
                        $ii++;
                        $ii % 2 == 0 ? $bgclr = "row1" : $bgclr = "row2";
                        if (strlen($r->blog_descr) > 20){
                        $descr = substr($r->blog_descr, 0, 20)."...";
                        }else{
                        $descr = $r->blog_descr;
                        }
						$bra = number_format($r->blog_rank_alexa,0);
						$brt = number_format($r->blog_rank_techno,0);
						$brp = number_format($r->blog_rank_page,0);						
                        $retval .= '<tr class="'.$bgclr.'"><td align="center">'.$ii.'</td><td><a href="http://'.$r->blog_url.'" title="'.$r->blog_title.'" target="_blanl">'.$r->blog_url.'</a></td><td><abbr title="'.$r->blog_descr.'">'.$descr.'</abbr></td>';
						if(get_option('btl_ar')=="on"){ $retval .= '<td align="center"><a href="http://www.alexa.com/siteinfo/http://www.'.$r->blog_url.'" target="_blank">'.$bra.'</a></td>';}else{ $retval .= '';}
						if(get_option('btl_tr')=="on"){ $retval .= '<td align="center">'.$brt.'</td>';}else{ $retval .= '';}
						if(get_option('btl_pr')=="on"){ $retval .= '<td align="center">'.$brp.'</td>';}else{ $retval .= '';}
                        $retval .= '</tr>';
                }
        } else {
                $retval .= '
<tr class="row1"><td colspan="6" align="center">No related links found</td></tr>';
        }
        $retval .= '</table>';
        $retval .= '<div class="plugin"><a href="http://wordpress.org/extend/plugins/blog-toplist/" target="_blank">Blog Toplist - WP Plugins</a></div><div style="clear:both"></div>';

        return $retval;
}
add_shortcode( 'bloglist', 'bloglist_show' );

//add style in front page
add_action("wp_head", "front_header");
function front_header()
{
        echo '<link rel="stylesheet" type="text/css" href="'.get_bloginfo("wpurl").'/wp-content/plugins/blog-toplist/images/style.css?ver=1.0" media="screen" />';
}

// register the custom sidebar widgets
function btl_widgets_init() {
    if (!is_blog_installed())
        return;

        //add for paid banner
        register_widget('Btl_Widget');

    do_action('widgets_init');
}
add_action('init', 'btl_widgets_init', 1);

function blogtoplist_actived()
{
        global $wpdb;

        $table_name = $wpdb->prefix . "blogtoplist";
                if($wpdb->get_var("show tables like '".$table_name."'") != $table_name)
                {
                $sql = "CREATE TABLE  `".$table_name."` (
                  `id` int(10) unsigned NOT NULL auto_increment,
                  `user_id` bigint(20) unsigned NOT NULL default '0',
                  `blog_url` varchar(100) NOT NULL,
                  `blog_title` varchar(100) default NULL,
                  `blog_descr` varchar(250) default NULL,
                  `blog_rank_alexa` varchar(45) NOT NULL default 'n/a',
                  `blog_rank_techno` varchar(45) NOT NULL default 'n/a',
                  `blog_rank_page` varchar(45) NOT NULL default 'n/a',
                  `blog_status` enum('0','1') NOT NULL default '0' COMMENT '0=pending,1=actived',
                  PRIMARY KEY  (`id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
                require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
                dbDelta($sql);
				add_option( 'btl_ar', 'on', '', 'yes' );
				add_option( 'btl_tr', 'on', '', 'yes' );
				add_option( 'btl_pr', 'on', '', 'yes' );
                }
}

function blogtoplist_deactivate() {
        echo '
        <div class="updated"><p>If you wish to use <strong>Blog Toplist plugin</strong>, please deactivate <strong>Blog Toplist Manager plugin</strong>.</p></div>';
}

if (!function_exists("blogtoplist_actived")) {
        add_action('admin_notices', 'blogtoplist_deactivate');
}


// Register the contextual help for the settings page
add_action( 'contextual_help','btl_help', 10, 3 );

// Print Scripts and Styles
add_action('admin_print_scripts','btl_page_scripts');
add_action('admin_print_styles','btl_page_styles');

//check database version below version 1.0.3
add_action('admin_notices', 'chk_db');
?>