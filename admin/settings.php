<div class="wrap">
    <div id="crfp-title" class="icon32"></div> 
    <h2><?php echo $this->plugin->displayName; ?> &raquo; Settings</h2>
           
    <?php    
    if ($this->message != '') {
        ?>
        <div class="updated"><p><?php echo $this->message; ?></p></div>  
        <?php
    }
    ?>        

    <div id="poststuff" class="metabox-holder">
        <!-- Content -->
        <div id="post-body">
            <div id="post-body-content">
                <div id="normal-sortables" class="meta-box-sortables ui-sortable" style="position: relative;">
                    <!-- Donate -->
                    <div class="postbox">
                        <h3 class="hndle">Donate</h3>
                        <div class="inside">
                        <!-- Donate -->
                        <?php
                        if ($this->settings['donated'] == '1') {
                            ?>
                            <p>Thanks for donating.</p>
                            <form id="post" name="post" method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>"> 
                            <p>
                                <strong>Actually, I'd like to display the Donate button.</strong>
                                <input type="checkbox" name="<?php echo $this->plugin->name; ?>[donated]" value="0"<?php echo ($this->settings['donated'] == 0 ? ' checked' : ''); ?> />   
                            </p>
                            <?php
                        } else {
                            ?>
                            <p>If you've found this plugin useful, any donation would be gratefully received to help cover ongoing support and maintenance. You can choose the amount you wish to donate when you click the Donate button.</p> 
                            <form action="https://www.paypal.com/cgi-bin/webscr" method="post">
                                <input type="hidden" name="cmd" value="_s-xclick">
                                <input type="hidden" name="hosted_button_id" value="RYU84ZXNAPWA8">
                                <p>
                                    <input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
                                </p>
                                <img alt="" border="0" src="https://www.paypalobjects.com/en_GB/i/scr/pixel.gif" width="1" height="1">
                            </form>
                            
                            <form id="post" name="post" method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>"> 
                            <p>
                                <strong>I've already made a donation; please stop displaying the Donate button.</strong>
                                <input type="checkbox" name="<?php echo $this->plugin->name; ?>[donated]" value="1"<?php echo ($this->settings['donated'] == 1 ? ' checked' : ''); ?> />   
                            </p>
                            <?php
                        }
                        ?>
                        </div>
                    </div>
                                            
                        <!-- Settings -->
                    
                        <div class="postbox">
                            <h3 class="hndle">Display Settings</h3>
                            <div class="inside">
                                <p>
                                    <strong>Enable on Pages</strong>
                                    <input type="checkbox" name="<?php echo $this->plugin->name; ?>[enabled][pages]" value="1"<?php echo ($this->settings['enabled']['pages'] == 1 ? ' checked' : ''); ?> />   
                                </p>

                                <p><strong>Enable on Post Categories</strong></p>
                                <p>
                                    <label class="screen-reader-text" for="label">Enable on Post Categories</label>
                                    <?php    
                                    $categories = get_categories('hide_empty=0&taxonomy=category');
                                    foreach ($categories as $key=>$category) {
                                        if ($category->slug == 'uncategorized') continue; // Skip Uncategorized
                                        ?>
                                        <input type="checkbox" name="<?php echo $this->plugin->name; ?>[taxonomies][categories][<?php echo $category->term_id; ?>]" value="1"<?php echo ($this->settings['taxonomies']['categories'][$category->term_id] == 1 ? ' checked' : ''); ?> /> <?php echo $category->name; ?><br />       
                                        <?php
                                    }
                                    ?>
                                </p>
                                
                                <p>
                                    <strong>Display Average Rating</strong>
                                    <input type="checkbox" name="<?php echo $this->plugin->name; ?>[enabled][average]" value="1"<?php echo ($this->settings['enabled']['average'] == 1 ? ' checked' : ''); ?> />   
                                </p>
                                <p>
                                    <strong>Average Rating Text</strong>
                                    <input type="text" name="<?php echo $this->plugin->name; ?>[averageRatingText]" value="<?php echo ($this->settings['averageRatingText']); ?>" class="widefat" />   
                                </p>
                                <p class="description">If Display Average Rating above is selected, optionally define text to appear before the average rating stars are displayed.</p>
                            </div>
                        </div>
                        
                        <!-- Save -->
                        <div class="submit">
                            <input type="hidden" name="<?php echo $this->plugin->name; ?>[saved]" value="1" />
                            <input type="submit" name="submit" value="Save" /> 
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
