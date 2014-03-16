<?php
/*
 * Plugin Name: Comments I've Made
 * Description: A table containing comments that a user has made across all blogs in the network
 * Author: Josh Stemmler (Paul Underwood, initial template
 * Version: 1.0
 */

if(is_admin())
{
    new Comments_List_Table();
}

function getUserId(){
	if(!function_exists('wp_get_current_user'))
	    require_once(ABSPATH . "wp-includes/pluggable.php"); 
	wp_cookie_constants();
	$current_user = wp_get_current_user();
	return $current_user->user_login;
}

/**
 * Comments_List_Table class will create the page to load the table
 */
class Comments_List_Table
{

    /**
     * Display the list table page
     *
     * @return Void
     */
    public function list_table_page()
    {
        $commentListTable = new Comment_List_Table();
        $commentListTable->prepare_items();
        ?>
            <div class="wrap">
                <div id="icon-users" class="icon32"></div>
                <h2>Comments I've Made</h2>
                <?php $commentListTable->display(); ?>
            </div>
        <?php
    }
}

// WP_List_Table is not loaded automatically so we need to load it in our application
if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * Create a new table class that will extend the WP_List_Table
 */
class Comment_List_Table extends WP_List_Table
{
    /**
     * Prepare the items for the table to process
     *
     * @return Void
     */
    public function prepare_items()
    {
        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();

        $data = $this->table_data();
        //usort( $data, array( &$this, 'sort_data' ) );

        $perPage = 5;
        $currentPage = $this->get_pagenum();
        $totalItems = count($data);

        $this->set_pagination_args( array(
            'total_items' => $totalItems,
            'per_page'    => $perPage
        ) );

        $data = array_slice($data,(($currentPage-1)*$perPage),$perPage);

        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->items = $data;
    }

    /**
     * Override the parent columns method. Defines the columns to use in your listing table
     *
     * @return Array
     */
    public function get_columns()
    {
        $columns = array(
			'blog_title' => "Blog",
			'post_title' => "Post",
            'comment_content' => 'Comment',
            'comment_date' => 'Date',
        );

        return $columns;
    }

    /**
     * Define which columns are hidden
     *
     * @return Array
     */
    public function get_hidden_columns()
    {
        return array();
    }

    /**
     * Define the sortable columns
     *
     * @return Array
     */
    public function get_sortable_columns()
    {
        return array('column_date' => array('column_date', false));
    }

	private function table_data()
    {
	global $wpdb;
	$userId = getUserId();
	$sqlstr = '';
	$blog_list = wp_get_sites($args);
	$sqlstr = "SELECT 1 as blog_id, comment_date, comment_id, comment_post_id, comment_content, comment_date_gmt, comment_author from ".$wpdb->base_prefix ."comments where comment_approved = 1 AND comment_author = \"". $userId . "\"";
	$uni = '';
	foreach ($blog_list AS $blog) {
		if($blog['blog_id'] != 1){
			$uni = ' union ';
			$sqlstr .= $uni . " SELECT ".$blog['blog_id']." as blog_id, comment_date, comment_id, comment_post_id, comment_content, comment_date_gmt, comment_author from ".$wpdb->base_prefix .$blog['blog_id']."_comments where comment_approved = 1 AND comment_author = \"". $userId . "\"";                
		}
	}
	$limit = 50; //set your limit
	$limit = ' LIMIT 0, '. $limit;
	$sqlstr .= " ORDER BY comment_date_gmt desc " . $limit; 
	//echo($sqlstr);
	//echo($current_user->user_login);
	$comm_list = $wpdb->get_results($sqlstr);
	$blognamequery1 = "SELECT option_value FROM ". $wpdb->base_prefix . "options WHERE option_name = \"blogname\"";
	$postnamequery1 = "SELECT post_title FROM ". $wpdb->base_prefix ."posts WHERE ID = {$comment->comment_post_id}";
	$blogurlquery1 = "SELECT option_value FROM ". $wpdb->base_prefix . "options WHERE option_name = \"siteurl\"";
	$posturlquery1 = "SELECT guid FROM ". $wpdb->base_prefix . "posts WHERE ID = {$comment->comment_post_id}";
	$data = array();
	
	foreach($comm_list as $comment){
		//echo $comment->comment_post_id;
		if($comment->blog_id !=1){
			$data[] = array(
					'comment_content' => $comment->comment_content,
					'comment_date' => $comment->comment_date,
					'blog_title' => $wpdb->get_var("SELECT option_value FROM ". $wpdb->base_prefix . $comment->blog_id . "_options WHERE option_name = \"blogname\""),
					'post_title' => $wpdb->get_var("SELECT post_title FROM ". $wpdb->base_prefix . $comment->blog_id . "_posts WHERE ID = ". $comment->comment_post_id),
					'blog_url' => $wpdb->get_var("SELECT option_value FROM ". $wpdb->base_prefix . $comment->blog_id . "_options WHERE option_name = \"siteurl\""),
					'post_url' => $wpdb->get_var("SELECT guid FROM ". $wpdb->base_prefix . $comment->blog_id . "_posts WHERE ID = " . $comment->comment_post_id)
					);
		}
	    else{

			$data[] = array(
					'comment_content' => $comment->comment_content,
					'comment_date' => $comment->comment_date,
					'blog_title' => $wpdb->get_var($blognamequery1),
					'post_title' => $wpdb->get_var($postnamequery1. $comment->comment_post_id),
					'blog_url' => $wpdb->get_var($blogurlquery1),
					'post_url' => $wpdb->get_var($posturlquery1. $comment->comment_post_id)
					);
				}
			}
	return $data;
	}

    /**
     * Define what data to show on each column of the table
     *
     * @param  Array $item        Data
     * @param  String $column_name - Current column name
     *
     * @return Mixed
     */
    public function column_default( $item, $column_name )
    {
        switch( $column_name ) {
			case 'blog_title':
				return "<a href =\"". $item["blog_url"]."\">" . $item[$column_name] . "</a>";
			case 'post_title':
				return "<a href =\"".$item["post_url"]."\">" . $item[$column_name] . "</a>";
            case 'comment_content':
				if(strlen($item[$column_name]) > 50){
				return substr($item[$column_name],0,50)."...";
				}
				if(strlen($item[$column_name])<50){
				return $item[$column_name];
				}
            case 'comment_date':
                return $item[$column_name];
            default:
                return print_r( $item, true ) ;
        }
    }
}

add_action( 'wp_dashboard_setup', 'comments_dashboard_setup' );
function comments_dashboard_setup() {
    wp_add_dashboard_widget(
        'comments-dashboard-widget',
        'Comments I\'ve Made',
        'comments_dashboard_content',
        $control_callback = null
    );
}

function comments_dashboard_content() {
		$commentListTable = new Comment_List_Table();
        $commentListTable->prepare_items();
		$commentListTable->display();
}
?>