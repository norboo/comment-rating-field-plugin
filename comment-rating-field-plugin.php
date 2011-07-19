<?php
/**
* Plugin Name: Comment Rating Field Plugin
* Plugin URI: http://www.n7studios.co.uk/2010/06/04/wordpress-comment-rating-field-plugin/
* Version: 1.2
* Author: <a href="http://www.n7studios.co.uk/">Tim Carr</a>
* Description: Adds a 5 star rating field to the comments form in Wordpress.  Requires Wordpress 3.0+
*/

/**
* Comment Rating Field Plugin Class
* 
* @package Wordpress
* @subpackage Comment Rating Field Plugin
* @author Tim Carr
* @version 1.2
* @copyright n7 Studios
*/
class CommentRatingFieldPlugin {
    /**
    * Constructor.
    */
    function CommentRatingFieldPlugin() {
        // Plugin Details
        $this->plugin->name = 'comment-rating-field-plugin';
        $this->plugin->displayName = 'Comment Rating Field Plugin';
        $this->plugin->url = WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__));          

        // Settings
        $this->settings = get_option($this->plugin->name);
        if ($this->settings['saved'] != '1') $this->Install();

        // Action and Filter Hooks
        add_action('comment_post', array(&$this, 'SaveRating')); // Save Rating Field
        add_action('comment_text', array(&$this, 'DisplayRating')); // Displays Rating on Comments              
        add_filter('comments_template', array(&$this, 'DisplayAverageRating')); // Displays Average Rating on Comments Template

        // Register and load CSS
        wp_register_style('crfp-rating-css', $this->plugin->url.'css/rating.css');
        wp_enqueue_style('crfp-rating-css');
        
        // Register and Enqueue jQuery and Rating Scripts
        if (is_admin()) {
            add_action('admin_menu', array(&$this, 'AddAdminMenu'));
            wp_register_style($this->plugin->name.'-admin-css', $this->plugin->url.'css/admin.css'); 
            wp_enqueue_style($this->plugin->name.'-admin-css');
        } else {
            wp_register_script('crfp-jquery-rating-pack', $this->plugin->url.'js/jquery.rating.pack.js');
            wp_register_script('crfp-jquery-rating-settings', $this->plugin->url.'js/jquery.rating.settings.js');
            wp_enqueue_script('jquery');
            wp_enqueue_script('crfp-jquery-rating-pack');
            wp_enqueue_script('crfp-jquery-rating-settings');
        }
    }
    
    /**
    * Routine for defining default settings for the plugin.
    */
    function Install() {
        // Include all Post categories by default
        $categories = get_categories('hide_empty=0&taxonomy=category');
        foreach ($categories as $key=>$category) {
            if ($category->slug == 'uncategorized') continue; // Skip Uncategorized
            $newSettings['taxonomies']['categories'][$category->term_id] = '1';
        }
        
        // Enable on Pages and mark as saved
        $newSettings['enabled']['pages'] = '1';
        $newSettings['averageRatingText'] = 'Average Rating: ';
        $newSettings['saved'] = '1';
        
        // Save and get new settings
        update_option($this->plugin->name, $newSettings);
        $this->settings = get_option($this->plugin->name);    
    }
    
    /**
    * Adds a single option panel to Wordpress Administration
    */
    function AddAdminMenu() {
        add_menu_page($this->plugin->displayName, $this->plugin->displayName, 9, $this->plugin->name, array(&$this, 'AdminPanel'), $this->plugin->url.'images/icons/crfp-small.png');
    }
    
    /**
    * Outputs the plugin Admin Panel in Wordpress Admin
    */
    function AdminPanel() {
        // Save Settings
        if (isset($_POST['submit'])) {
            update_option($this->plugin->name, $_POST[$this->plugin->name]);
            $this->message = 'Settings Updated.'; 
        }
        
        // Load form
        $this->settings = get_option($this->plugin->name); 
        include_once(WP_PLUGIN_DIR.'/'.$this->plugin->name.'/admin/settings.php');  
    } 
 
    /**
    * Saves the POSTed rating for the given comment ID to the comment meta table
    * 
    * @param int $commentID
    */
    function SaveRating($commentID) {
        add_comment_meta($commentID, 'crfp-rating', $_POST['crfp-rating'], true);
    }
    
    /**
    * Displays the Average Rating on the Comments Template, if required
    */
    function DisplayAverageRating() {
        global $wpdb, $post;
        
        if (!$this->settings['enabled']['average']) return; // Nothing to do

        // Get average
        $results = $wpdb->get_results(" SELECT ".$wpdb->prefix."commentmeta.meta_value
                                        FROM ".$wpdb->prefix."comments
                                        LEFT JOIN ".$wpdb->prefix."commentmeta
                                        ON ".$wpdb->prefix."comments.comment_ID = ".$wpdb->prefix."commentmeta.comment_id
                                        WHERE ".$wpdb->prefix."comments.comment_post_ID = '".mysql_real_escape_string($post->ID)."'
                                        AND ".$wpdb->prefix."commentmeta.meta_key = 'crfp-rating'
                                        AND ".$wpdb->prefix."commentmeta.meta_value != 0
                                        GROUP BY ".$wpdb->prefix."commentmeta.comment_id");
                               
        if (count($results) == 0) return; // No results
                                        
        $totalRatings = count($results);
        foreach ($results as $key=>$result) $totalRating += $result->meta_value;
        if ($totalRatings == 0 OR $totalRating == 0) return; // No votes - shouldn't reach this condition
        $averageRating = round(($totalRating / $totalRatings), 0);
        
        echo ('<div class="crfp-average-rating">'.$this->settings['averageRatingText'].'<div class="crfp-rating crfp-rating-'.$averageRating.'"></div></div>');    
    }
    
    /**
    * Appends the rating to the end of the comment text for the given comment ID
    * 
    * @param text $comment
    */
    function DisplayRating($comment) {
        global $post;
        
        $commentID = get_comment_ID();
        
        // Check whether we need to display ratings
        if (!$this->display) { // Prevents checking for every comment in a single Post
            if (is_page() AND $this->settings['enabled']['pages'] == '1') {
                $this->display = true;
            } elseif (is_single()) {
                $postCats = wp_get_post_categories($post->ID);
                foreach ($this->settings['taxonomies']['categories'] as $catID=>$enabled) {
                    if (in_array($catID, $postCats)) {
                        $this->display = true;
                        break;
                    }
                }    
            }
        }

        // Display rating?
        if ($this->display) {
            $rating = get_comment_meta($commentID, 'crfp-rating', true);
            if ($rating == '') $rating = 0;
            return $comment.'<div class="crfp-rating crfp-rating-'.$rating.'"></div>';
        }
        
        // Just return comment without rating
        return $comment;    
    }    
}
$crfp = new CommentRatingFieldPlugin(); // Invoke class
?>
