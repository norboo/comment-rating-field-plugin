<?php
/**
* Plugin Name: Comment Rating Field Plugin
* Plugin URI: http://www.wpcube.co.uk/crfp
* Version: 1.42
* Author: <a href="http://www.wpcube.co.uk/">WP Cube</a>
* Description: Adds a 5 star rating field to the comments form in WordPress.  Requires WordPress 3.0+
*/

/**
* Comment Rating Field Plugin Class
* 
* @package WordPress
* @subpackage Comment Rating Field Plugin
* @author Tim Carr
* @version 1.42
* @copyright WP Cube
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
        
        add_action('comment_post', array(&$this, 'SaveRating')); // Save Rating Field on Comment Post
	    add_action('comment_text', array(&$this, 'DisplayRating')); // Displays Rating on Comments 
	    add_filter('the_content', array(&$this, 'DisplayAverageRating')); // Displays Average Rating below Content
		
        if (is_admin()) {
            add_action('admin_menu', array(&$this, 'AddAdminMenu'));
            add_action('wp_set_comment_status', array(&$this, 'UpdatePostRatingByCommentID')); // Recalculate average rating on comment approval / hold / spam
	        add_action('deleted_comment', array(&$this, 'UpdatePostRatingByCommentID')); // Recalculate average rating on comment delete
        } else {
	        // Register and load CSS
	        wp_register_style('crfp-rating-css', $this->plugin->url.'css/rating.css');
	        wp_enqueue_style('crfp-rating-css');
        
            // Register and load JS
            wp_enqueue_script('jquery');
            wp_enqueue_script('crfp-jquery-rating-pack', $this->plugin->url.'js/jquery.rating.pack.js', 'jquery', false, true);
    
            // Action and Filter Hooks
	        add_action('wp_footer', array(&$this, 'DisplayRatingField')); // Displays Rating Field on Comments Form 
        }
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
            $this->message = __('Settings Updated.'); 
        }
        
        // Load form
        $this->settings = get_option($this->plugin->name); 
        include_once(WP_PLUGIN_DIR.'/'.$this->plugin->name.'/admin/settings.php');  
    } 
    
    /**
    * Saves the POSTed rating for the given comment ID to the comment meta table,
    * as well as storing the total ratings and average on the post itself.
    * 
    * @param int $commentID
    */
    function SaveRating($commentID) {
    	// Save rating against comment
        add_comment_meta($commentID, 'crfp-rating', $_POST['crfp-rating'], true);
        
        // Get post ID from comment and store total and average ratings against post
        // Run here in case comments are set to always be approved
        $this->UpdatePostRatingByCommentID($commentID); 
    }
    
    /**
    * Calculates the average rating and total number of ratings
    * for the given post ID, storing it in the post meta.
    *
    * @param int @postID Post ID
    * @return bool Rating Updated
    */
    function UpdatePostRatingByPostID($postID) {
    	global $wpdb;	

		// Calculate average rating and total ratings
        $results = $wpdb->get_results(" SELECT ".$wpdb->prefix."commentmeta.meta_value
                                        FROM ".$wpdb->prefix."comments
                                        LEFT JOIN ".$wpdb->prefix."commentmeta
                                        ON ".$wpdb->prefix."comments.comment_ID = ".$wpdb->prefix."commentmeta.comment_id
                                        WHERE ".$wpdb->prefix."comments.comment_post_ID = '".mysql_real_escape_string($postID)."'
                                        AND ".$wpdb->prefix."comments.comment_approved = '1'
                                        AND ".$wpdb->prefix."commentmeta.meta_key = 'crfp-rating'
                                        AND ".$wpdb->prefix."commentmeta.meta_value != 0
                                        GROUP BY ".$wpdb->prefix."commentmeta.comment_id"); 
                  
        if (count($results) == 0) {
        	$totalRatings = 0;
        	$averageRating = 0;
        } else {                            
	        $totalRatings = count($results);
	        foreach ($results as $key=>$result) $totalRating += $result->meta_value;
	        $averageRating = (($totalRatings == 0 OR $totalRating == 0) ? 0 : round(($totalRating / $totalRatings), 0));
        }

        update_post_meta($postID, 'crfp-total-ratings', $totalRatings);
        update_post_meta($postID, 'crfp-average-rating', $averageRating);
        
        return true;
    }

    /**
    * Called by WP action, passes function call to UpdatePostRatingByPostID
    *
    * @param int $commentID Comment ID
    * @return int Comment ID
    */
    function UpdatePostRatingByCommentID($commentID) {
    	$comment = get_comment($commentID);
    	$this->UpdatePostRatingByPostID($comment->comment_post_ID);
    	return true;
    }
    
    /**
    * Checks if the post can have a rating
    *
    * @return bool Post can have rating
    */
    function PostCanHaveRating() {
		global $post;

    	$displayRatingField = false; // Don't display rating field by default
    	wp_reset_query(); // Reset to default loop query so we can test if a single Page or Post

    	if (!is_array($this->settings)) return; // No settings defined
    	if ($post->comment_status != 'open') return; // Comments are no longer open
    	if (!is_singular()) return; // Not a single Post
		
    	// Check if post type is enabled
    	$type = get_post_type($post->ID);
    	if (is_array($this->settings['enabled']) AND $this->settings['enabled'][$type]) {
    		// Post type enabled, regardless of taxonomies
    		$displayRatingField = true;	
    	} elseif (is_array($this->settings['taxonomies'])) {    	
	    	// Get all terms assigned to this Post
	    	// Check if we need to display ratings here
			$taxonomies = get_taxonomies();
			$ignoreTaxonomies = array('post_tag', 'nav_menu', 'link_category', 'post_format');
			foreach ($taxonomies as $key=>$taxonomyProgName) {
				if (in_array($taxonomyProgName, $ignoreTaxonomies)) continue; // Skip ignored taxonomies
				if (!is_array($this->settings['taxonomies'][$taxonomyProgName])) continue; // Skip this taxonomy
				
				// Get terms and build array of term IDs
				unset($terms, $termIDs);
				$terms = wp_get_post_terms($post->ID, $taxonomyProgName);
				foreach ($terms as $key=>$term) $termIDs[] = $term->term_id;

				// Check if any of the post term IDs have been selected within the plugin
				if ($termIDs) {
					foreach ($this->settings['taxonomies'][$taxonomyProgName] as $termID=>$intVal) {
						if (in_array($termID, $termIDs)) {
		    				$displayRatingField = true;
		    				break;
		    			}	
					}
				}
	    	}
    	}

    	return $displayRatingField;
    }

    /**
    * Displays the Average Rating below the Content, if required
    *
    * @param string $content Post Content
    * @return string Post Content w/ Ratings HTML
    */
    function DisplayAverageRating($content) {
        global $post;
        
        if (!$this->settings['enabled']['average']) return $content; // Don't display average
        $averageRating = get_post_meta($post->ID, 'crfp-average-rating', true); // Get average rating
        
        // Check if the meta key exists; if not go and run the calculation
        if ($averageRating == '') {
        	$this->UpdatePostRatingByPostID($post->ID);
        	$averageRating = get_post_meta($post->ID, 'crfp-average-rating', true); // Get average rating
        }

        // If still no rating, a rating has never been left, so don't display one
        if ($averageRating == '' OR $averageRating == 0) return $content;
        
        // Build rating HTML
        $ratingHTML = '<div class="crfp-average-rating">'.$this->settings['averageRatingText'].'<div class="crfp-rating crfp-rating-'.$averageRating.'"></div></div>';
        
        // Return rating widget with content
        return $content.$ratingHTML;   
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
        	$this->display = $this->PostCanHaveRating();
    	}

        // Display rating?
        if ($this->display) {
            $rating = get_comment_meta($commentID, 'crfp-rating', true);
            if ($rating == '') $rating = 0;
            return $comment.'<div class="rating'.($this->settings['displayStyle'] == 'grey' ? ' rating-always-on' : '').'"><div class="crfp-rating crfp-rating-'.$rating.'">'.$rating.'</div></div>';
        }
        
        // Just return comment without rating
        return $comment;  
    }  
    
    /**
    * Appends the rating field to the end of the comment form, if required
    */
    function DisplayRatingField() {
    	global $post;
    	
    	if (!$this->PostCanHaveRating()) return;
    	
    	// If here, output rating field
    	$label = (($this->settings['ratingFieldLabel'] != '') ? '<label for="rating-star">'.$this->settings['ratingFieldLabel'].'</label>' : ''); 
    	$field = $label.'<input name="rating-star" type="radio" class="star" value="1" /><input name="rating-star" type="radio" class="star" value="2" /><input name="rating-star" type="radio" class="star" value="3" /><input name="rating-star" type="radio" class="star" value="4" /><input name="rating-star" type="radio" class="star" value="5" /><input type="hidden" name="crfp-rating" value="0" />';    	    	
    	?>
		<script type="text/javascript">
	    	jQuery(document).ready(function($) {
			    if ($('form#commentform textarea[name=comment]').length > 0) {
			    	// If parent tag is a container for the comment field, append rating after the parent
			    	var commentField = $('form#commentform textarea[name=comment]');
			    	var parentTagName = $(commentField).parent().get(0).tagName;
			    	if (parentTagName == 'P' || parentTagName == 'DIV' || parentTagName == 'LI') {
			    		// Append rating field as a new element
			    		$(commentField).parent().after('<'+parentTagName+' class="crfp-field"><?php echo $field; ?></'+parentTagName+'>');
			    	} else {
			    		// Append rating field straight after comment field
			    		$(commentField).after('<?php echo $field; ?>');
			    	}
			    
			    	$('input.star').rating(); // Invoke rating plugin
			    	$('div.star-rating a').bind('click', function(e) { $('input[name=crfp-rating]').val($(this).html()); }); // Stores rating in hidden field ready for POST
			    	$('div.rating-cancel a').bind('click', function(e) { $('input[name=crfp-rating]').val('0'); }); // Stores rating in hidden field ready for POST
				}
			});
		</script>
    	<?php		
    }  
}
$crfp = new CommentRatingFieldPlugin(); // Invoke class
?>
