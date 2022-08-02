<?php
    // Override B2bking_Public class function for csv upload
    function b2bking_handle_file_upload_override() {
        // Stop immediately if form is not submitted
        if (!isset($_POST['b2bking_submit_csvorder'])) {
            return;
        }

        $error = 'no';

        // Throws a message if no file is selected
        if (!$_FILES['b2bking_csvorder']['name']) {
            wc_add_notice(esc_html__('Please choose a file', 'b2bking'), 'error');
            $error = 'yes';
        }

        // Check for valid file extension
        $allowed_extensions = array( 'csv');
        $tmp = explode('.', $_FILES['b2bking_csvorder']['name']);
        if(!in_array(end($tmp), $allowed_extensions)) {
            wc_add_notice(sprintf(esc_html__('Invalid file extension, only allowed: %s', 'b2bking'), implode(', ', $allowed_extensions)), 'error');
            $error = 'yes';
        }

        $file_size = $_FILES['b2bking_csvorder']['size'];
        $allowed_file_size = 5512000; // Here we are setting the file size limit to 5.5MB

        // Check for file size limit
        if ($file_size >= $allowed_file_size) {
            wc_add_notice( sprintf(esc_html__('File size limit exceeded, file size should be smaller than %d KB', 'b2bking'), $allowed_file_size / 1000), 'error');
            $error = 'yes';

        }

        if ($error !== 'no') {
            wc_add_notice(esc_html__('Sorry, there was an error with your file upload.', 'b2bking'), 'error');
        } else {
            wc_add_notice(esc_html__('Upload successful', 'b2bking'), 'success');

            // process upload to add to cart
            $csv = array_map('str_getcsv', file($_FILES['b2bking_csvorder']['tmp_name'], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));

            $failed_skus = array();

            $linenumber = 0;
            $url = 'http://70.25.52.182:33333/?token=uULQwr6GNmNQSbbGefmN9qOUwvJ96OYy&check=';
            foreach($csv as $line) {
                $lineelementsarray = explode(';',$line[0]);

                if (isset($lineelementsarray[1])){
                    $sku = $lineelementsarray[0];
                    $qty = $lineelementsarray[1];
                } else {
                    $sku = $line[0];
                    $qty = $line[1];
                }
                $id = wc_get_product_id_by_sku($sku);
                
                $response = wp_remote_get($url . $sku);
                $response = $response['body'];
                $response = json_decode($response);
                $part_inventory_valid = 'no';
                
                foreach($response->int as $part) {
                    if ($part->{'s'} == $sku && $qty < $part->{'q'}) {
                        $part_inventory_valid = 'yes';
                    }
                }

                if ($id !== 0 && !empty($id) && $part_inventory_valid == 'yes') {
                    WC()->cart->add_to_cart($id, $qty);
                } else {
                    if ($linenumber !== 0) {
                        WC()->cart->add_to_cart($id, $qty);
                        array_push($failed_skus, $sku);
                    }
                }

                $linenumber++;
            }

            if (!empty($failed_skus)){
                $skus_string = '';
                foreach ($failed_skus as $sku){
                    $skus_string .= $sku.', ';
                }
                $skus_string = substr($skus_string, 0, -2);
                wc_add_notice( esc_html__( 'We could not match any products with the following SKUs: ', 'b2bking' ).$skus_string, 'error' );
            }
        }
    }  

    if ($theme === 'classic'){
        ?>
        <div class="b2bking_bulkorder_form_container">
            <div class="b2bking_bulkorder_form_container_top">
                <?php esc_html_e('Bulk Order Form', 'b2bking'); ?>
            </div>
            <div class="b2bking_bulkorder_form_container_content">
                <div class="b2bking_bulkorder_form_container_content_header">
                    <?php do_action('b2bking_bulkorder_column_header_start'); ?>

                    <div class="b2bking_bulkorder_form_container_content_header_product">
                        <?php
                        if (intval(get_option( 'b2bking_search_by_sku_setting', 1 )) === 1){
                            esc_html_e('Search by', 'b2bking');
                        ?>
                            <select id="b2bking_bulkorder_searchby_select">
                                <option value="productname"><?php esc_html_e('Product Name', 'b2bking'); ?></option>
                                <option value="sku" selected><?php 

                                echo apply_filters('b2bking_sku_search_display', esc_html__('SKU', 'b2bking')); 
                                ?></option>
                            </select>
                        <?php 
                        } else {
                            esc_html_e('Product name', 'b2bking');
                        }
                        ?>
                    </div>
                    <div class="b2bking_bulkorder_form_container_content_header_qty">
                        <?php esc_html_e('Qty', 'b2bking'); ?>
                    </div>
                    <div class="b2bking_bulkorder_form_container_content_header_inv">
                        <?php esc_html_e('Inv', 'b2bking'); ?>
                    </div>
                    <?php do_action('b2bking_bulkorder_column_header_mid'); ?>
                    <div class="b2bking_bulkorder_form_container_content_header_subtotal">
                        <?php esc_html_e('Subtotal', 'b2bking'); ?>
                    </div>

                    <?php do_action('b2bking_bulkorder_column_header_end'); ?>

                </div>

                <?php
                // show 5 lines of bulk order form
                $lines = apply_filters('b2bking_bulkorder_lines_default', 5);
                for ($i = 1; $i <= $lines; $i++){
                    ?>
                    <div class="b2bking_bulkorder_form_container_content_line"><input type="text" class="b2bking_bulkorder_form_container_content_line_product" <?php 

                    if ($i === 1){
                        echo 'placeholder="'.esc_attr__('Search for a product...','b2bking').'"';
                    }

                    ?>><input type="number" min="0" class="b2bking_bulkorder_form_container_content_line_qty b2bking_bulkorder_form_container_content_line_qty_classic" step="1"><?php do_action('b2bking_bulkorder_column_header_mid_content'); ?><div class="b2bking_bulkorder_form_container_content_line_inv"></div><div class="b2bking_bulkorder_form_container_content_line_subtotal"><?php 

                    if ($this->dynamic_replace_prices_with_quotes() === 'yes' || (get_option('b2bking_guest_access_restriction_setting', 'hide_prices') === 'replace_prices_quote') && (!is_user_logged_in() || (intval(get_option( 'b2bking_multisite_separate_b2bb2c_setting', 0 )) === 1 && get_user_meta($user_data_current_user_id, 'b2bking_b2buser', true) !== 'yes'))){
                        esc_html_e('Quote','b2bking');
                    } else {
                        if (intval(get_option( 'b2bking_show_accounting_subtotals_setting', 0 )) === 1){
                            echo wc_price(0);
                        } else {
                            echo get_woocommerce_currency_symbol().'0'; 
                        }
                    }

                    ?></div><?php do_action('b2bking_bulkorder_column_header_end_content'); ?><div class="b2bking_bulkorder_form_container_content_line_livesearch"></div></div>
                    <?php
                }
                ?>

                <!-- new line button -->
                <div class="b2bking_bulkorder_form_container_newline_container">
                    <button class="b2bking_bulkorder_form_container_newline_button">
                        <svg class="b2bking_bulkorder_form_container_newline_button_icon" xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 22 22">
                            <path fill="#fff" d="M11 1.375c-5.315 0-9.625 4.31-9.625 9.625s4.31 9.625 9.625 9.625 9.625-4.31 9.625-9.625S16.315 1.375 11 1.375zm4.125 10.14a.172.172 0 01-.172.172h-3.265v3.266a.172.172 0 01-.172.172h-1.032a.172.172 0 01-.171-.172v-3.265H7.046a.172.172 0 01-.172-.172v-1.032c0-.094.077-.171.172-.171h3.266V7.046c0-.095.077-.172.171-.172h1.032c.094 0 .171.077.171.172v3.266h3.266c.095 0 .172.077.172.171v1.032z"/>
                        </svg>
                        <?php esc_html_e('new line','b2bking'); ?>
                    </button>
                </div>

                <div class="b2bking_bulkorder_form_newline_template" style="display:none"><div class="b2bking_bulkorder_form_container_content_line"><input type="text" class="b2bking_bulkorder_form_container_content_line_product"><input type="number" min="0" step="1" class="b2bking_bulkorder_form_container_content_line_qty b2bking_bulkorder_form_container_content_line_qty_classic"><?php do_action('b2bking_bulkorder_column_header_mid_newline_content'); ?><div class="b2bking_bulkorder_form_container_content_line_inv"></div><div class="b2bking_bulkorder_form_container_content_line_subtotal">pricetext</div><div class="b2bking_bulkorder_form_container_content_line_livesearch"></div></div></div>

                <!-- add to cart button -->
                <div class="b2bking_bulkorder_form_container_bottom">
                    <!-- initialize hidden loader to get it to load instantly -->
                    <img class="b2bking_loader_hidden" src="<?php echo plugins_url('../includes/assets/images/loader.svg', __FILE__); ?>">
                    <div class="b2bking_bulkorder_form_container_bottom_add">
                        <button class="b2bking_bulkorder_form_container_bottom_add_button" type="button">
                            <svg class="b2bking_bulkorder_form_container_bottom_add_button_icon" xmlns="http://www.w3.org/2000/svg" width="21" height="19" fill="none" viewBox="0 0 21 19">
                                <path fill="#fff" d="M18.401 11.875H7.714l.238 1.188h9.786c.562 0 .978.53.854 1.087l-.202.901a2.082 2.082 0 011.152 1.87c0 1.159-.93 2.096-2.072 2.079-1.087-.016-1.981-.914-2.01-2.02a2.091 2.091 0 01.612-1.543H8.428c.379.378.614.903.614 1.485 0 1.18-.967 2.131-2.14 2.076-1.04-.05-1.886-.905-1.94-1.964a2.085 2.085 0 011.022-1.914L3.423 2.375H.875A.883.883 0 010 1.485V.89C0 .399.392 0 .875 0h3.738c.416 0 .774.298.857.712l.334 1.663h14.32c.562 0 .978.53.854 1.088l-1.724 7.719a.878.878 0 01-.853.693zm-3.526-5.64h-1.75V4.75a.589.589 0 00-.583-.594h-.584a.589.589 0 00-.583.594v1.484h-1.75a.589.589 0 00-.583.594v.594c0 .328.26.594.583.594h1.75V9.5c0 .328.261.594.583.594h.584a.589.589 0 00.583-.594V8.016h1.75a.589.589 0 00.583-.594v-.594a.589.589 0 00-.583-.594z"/>
                            </svg>
                        <?php 

                        if ($this->dynamic_replace_prices_with_quotes() === 'yes' || (get_option('b2bking_guest_access_restriction_setting', 'hide_prices') === 'replace_prices_quote') && (!is_user_logged_in() || (intval(get_option( 'b2bking_multisite_separate_b2bb2c_setting', 0 )) === 1 && get_user_meta($user_data_current_user_id, 'b2bking_b2buser', true) !== 'yes'))){
                            esc_html_e('Add to Quote','b2bking'); 
                        } else {
                            esc_html_e('Add to Cart','b2bking'); 	
                        }
                        

                        ?>
                        </button>
                        <button class="b2bking_bulkorder_form_container_bottom_save_button" type="button">
                            <svg class="b2bking_bulkorder_form_container_bottom_save_button_icon" xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 22 22">
                                <path fill="#fff" d="M9.778 4.889h7.333v2.444H9.778V4.89zm0 4.889h7.333v2.444H9.778V9.778zm0 4.889h7.333v2.444H9.778v-2.444zm-4.89-9.778h2.445v2.444H4.89V4.89zm0 4.889h2.445v2.444H4.89V9.778zm0 4.889h2.445v2.444H4.89v-2.444zM20.9 0H1.1C.489 0 0 .489 0 1.1v19.8c0 .489.489 1.1 1.1 1.1h19.8c.489 0 1.1-.611 1.1-1.1V1.1c0-.611-.611-1.1-1.1-1.1zm-1.344 19.556H2.444V2.444h17.112v17.112z"/>
                            </svg>
                        <?php esc_html_e('Save list','b2bking'); ?>
                        </button>
                    </div>
                    <div class="b2bking_bulkorder_form_container_bottom_total">
                        <?php
                        if ($this->dynamic_replace_prices_with_quotes() === 'yes' || (get_option('b2bking_guest_access_restriction_setting', 'hide_prices') === 'replace_prices_quote') && (!is_user_logged_in() || (intval(get_option( 'b2bking_multisite_separate_b2bb2c_setting', 0 )) === 1 && get_user_meta($user_data_current_user_id, 'b2bking_b2buser', true) !== 'yes'))){

                        } else {
                            ?>
                                <?php esc_html_e('Total: ','b2bking'); ?><strong><?php echo wc_price(0);?></strong>
                            <?php	
                        }?>
                        
                    </div>
                </div>


            </div>
        </div>
        <?php
    }