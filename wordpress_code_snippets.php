<?php
    // Wordpress snippet - B2BKing add to cart inventory validation
    add_action('wp_footer', function() {
        ?>
        <script>
            jQuery(document).ready(function() {
                // on click add to cart
                $('.b2bking_bulkorder_form_container_bottom_add_button').on('click', function() {
                    let ajaxurl = '<?php echo admin_url('admin-ajax.php') ?>'; // get ajaxurl
                    let productString = '';
                    let product_arr = [];

                    // loop through all bulk order form lines
                    document.querySelectorAll('.b2bking_bulkorder_form_container_content_line_product').forEach(function(textinput) {
                        var classList = $(textinput).attr('class').split(/\s+/);
                        $.each(classList, function(index, item) {
                            // foreach line if it has selected class, get selected product ID 
                            if (item.includes('b2bking_selected_product_id_')) {
                                let productID = item.split('_')[4];
                                let quantity = $(textinput).parent().find('.b2bking_bulkorder_form_container_content_line_qty').val();
                                quantity = Number(quantity);
                                let product = $(textinput).val();
                                if (product.split(' ')[2] == 'AFN') {
                                    product = product.split(' ')[2] + product.split(' ')[0].slice(1);
                                } else {
                                    product = product.split(' ')[0] + product.split(' ')[2];
                                }

                                product_arr.push([productID, product, quantity]);
                            }
                        });
                    });

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            'action': 'b2bking_add_to_cart_check_inventory_ajax',
                            'products': product_arr
                        }
                    }).then(function(data) {
                        if (data == "success") {
                            for (let i of product_arr) {
                                let productID = i[0];
                                let product = i[1];
                                let quantity = i[2];

                                if (quantity > 0) {
                                    // set product
                                    productString += productID + ':' + quantity + '|';
                                }
                            }

                            // if not empty, send
                            if (productString !== '' && productString !== undefined) {
                                // replace icon with loader
                                $('<img class="b2bking_loader_icon_button" src="'+b2bking_display_settings.loadertransparenturl+'">').insertBefore('.b2bking_bulkorder_form_container_bottom_add_button_icon');
                                $('.b2bking_bulkorder_form_container_bottom_add_button_icon').remove();
                                var datavar = {
                                    action: 'b2bking_bulkorder_add_cart',
                                    security: b2bking_display_settings.security,
                                    productstring: productString,
                                };

                                $.post(b2bking_display_settings.ajaxurl, datavar, function(response) {
                                    window.location = b2bking_display_settings.carturl;
                                });
                            }
                        } else {
                            alert("One or more items does not have inventory | " + data);
                            console.log('Fail: One or more items does not have inventory |', data);
                        }
                    }).fail(function(error) {
                        console.log("Something went wrong: ", error.responseText);
                    });
                });
            })
        </script>
        <?php
    });

    // Wordpress snippet - Change menu when user is logged in or logged out
    function my_wp_nav_menu_args($args = '') {
        if(is_user_logged_in()) {
            $user = wp_get_current_user(); // getting & setting the current user 
            $roles = ( array ) $user->roles; // obtaining the role 
            
            // Logged in menu to display
            switch(strtoupper($roles[0])) {
                case "ADMINISTRATOR":
                    $args['menu'] = 35;
                    break;
                case "ACCOUNTANT":
                    $args['menu'] = 36;
                    break;
                default:
                    $args['menu'] = 37;
                    break;
            }
        } else {
            // Non-logged-in menu to display
            $args['menu'] = 38;
        }
        return $args;
    }
    add_filter( 'wp_nav_menu_args', 'my_wp_nav_menu_args' );

    // Wordpress snippet - b2bking bulk order product form 
    add_filter('b2bking_bulkorder_indigo_search_name_display', function($name, $product) {
        $name = $product->get_name() . " - " . $product->get_description();
        return $name;
    }, 10, 2);
    
    add_filter('b2bking_bulkorder_cream_search_name_display', function($name, $product) {
        $name = $product->get_name() . " - " . $product->get_description();
        return $name;
    }, 10, 2);

    // Wordpress snippets - Product catalogue inventory check
    add_action('wp_footer', function() {
        ?>
        <script>
            jQuery(document).ready(function() {
                // add to cart button
                $('body').on('click', '.b2bking_bulkorder_indigo_add', function() {
                    let ajaxurl = '<?php echo admin_url('admin-ajax.php') ?>'; // get ajaxurl
                    let product_arr = [];

                    // loader icon
                    let thisbutton = $(this);
                    $(this).html('<img class="b2bking_loader_icon_button_indigo" src="'+b2bking_display_settings.loadertransparenturl+'">');

                    let textinput = $(this).parent().parent().find('.b2bking_bulkorder_form_container_content_line_product');
                    var productID = 0;
                    let product = $(this).parent().parent().find('.b2bking_bulkorder_indigo_name.b2bking_bulkorder_cream_name');
                    product = $(product).html();
                    if (product.split(' ')[2] == 'AFN') {
                        product = product.split(' ')[2] + product.split(' ')[0].slice(1);
                    } else {
                        product = product.split(' ')[0] + product.split(' ')[2];
                    }
                    var classList = $(textinput).attr('class').split(/\s+/);
                    $.each(classList, function(index, item) {
                        // foreach line if it has selected class, get selected product ID 
                        if (item.includes('b2bking_selected_product_id_')) {
                            productID = item.split('_')[4];
                        }
                    });
                    let qty = $(this).parent().parent().find('.b2bking_bulkorder_form_container_content_line_qty').val();

                    product_arr.push([productID, product, qty]);
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            'action': 'b2bking_add_to_cart_check_inventory_ajax',
                            'products': product_arr
                        }
                    }).then(function(data) {
                        if (data == 'success') {
                            var datavar = {
                                action: 'b2bking_bulkorder_add_cart_item',
                                security: b2bking_display_settings.security,
                                productid: productID,
                                productqty: qty,
                            };

                            $.post(b2bking_display_settings.ajaxurl, datavar, function(response) {
                                if (response === 'success') {
                                    // set button to 'Add more'
                                    $(thisbutton).html(b2bking_display_settings.add_more_indigo);
                                    // Refresh cart fragments
                                    $(document.body).trigger('wc_fragment_refresh');
                                }
                            });
                        } else {
                            console.log('Fail: Product does not have inventory. | ', data);
                            // set button to 'Add more'
                            $(thisbutton).html("No Inventory");
                            // Refresh cart fragments
                            $(document.body).trigger('wc_fragment_refresh');
                        }
                    }).fail(function(error) {
                        console.log('Something went wrong: ', error.responseText);
                        // set button to 'Add more'
                        $(thisbutton).html(b2bking_display_settings.add_more_indigo);
                        // Refresh cart fragments
                        $(document.body).trigger('wc_fragment_refresh');
                    });
                });
            });
        </script>
        <?php
    });

    // Wordpress snippets - Override B2bking_Public class function for csv upload
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
                    if ($linenumber !== 0){
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
    
    // Get all customer orders
    function get_all_customer_orders() {
        $user = wp_get_current_user();
        $roles = (array) $user->roles;

        $customer_orders = [];
        if ($roles[0] == 'administrator') {
            $customer_orders = get_posts(array(
                'numberposts' => -1,
                'post_type'   => wc_get_order_types(),
                'post_status' => array_keys(wc_get_order_statuses()),
            ));
        } else {
            $customer_orders = get_posts(array(
                'numberposts' => -1,
                'meta_key'    => '_customer_user',
                'meta_value'  => get_current_user_id(),
                'post_type'   => wc_get_order_types(),
                'post_status' => array_keys(wc_get_order_statuses()),
            ));
        }

        $orders_array = [];
        foreach($customer_orders as $key => $id) {
            $order = wc_get_order($id->ID);
            $date = date_create($order->get_date_created());
            $date = date_format($date,"Y-m-d H:i:s");
            $order_info = ['id' => $order->get_id(), 'subtotal' => $order->get_subtotal(), 'tax' => $order->get_total_tax(), 'total' => $order->get_total(), 'item_count' => $order->get_item_count(), 'date_created' => $date, 'status' => $order->get_status()];
            array_push($orders_array, $order_info);
        }

        $start_date = new DateTime("first day of this month");
        $end_date = new DateTime("last day of this month");
        
        $start_date = $start_date->format('Y-m-d');
        $end_date = $end_date->format('Y-m-d');

        if ($start_date !== '') {
            $orders_array = array_filter($orders_array, function($order) use ($start_date) {
                return $order['date_created'] > $start_date;
            });
        }
    
        if ($end_date !== '') {
            $orders_array = array_filter($orders_array, function($order) use ($end_date) {
                return $order['date_created'] < $end_date;
            });
        }
        
        $html = '<div class="statements"><form id="statement_form"><label htmlfor="statement_start_date">Start Date:</label><input id="statement_start_date" type="date" value="' . $start_date . '"><label htmlfor="statement_end_date">End Date:</label><input id="statement_end_date" type="date" value="' . $end_date . '"><label htmlfor="statement_order_status">Order Status:</label><select id="statement_order_status"><option value="">All</option><option value="Paid">Paid</option><option value="Pending Payment">Pending Payment</option></select>';

        if ($roles[0] == 'administrator') {
            $users = get_users();
            
            $html .= '<label htmlfor="statement_order_users">Customers</label><select id="statement_order_users"><option value="">All</option>';
            
            foreach($users as $user) {
                $html .= '<option value="' . esc_html($user->ID) . '">' . esc_html($user->display_name) . '</option>';
            }

            $html .= '</select>';
        }

        $html .= '<button id="statement_filter_button" type="button">Filter</button><button id="statement_clear_button">Clear</button></form><table class="statement-table"><thead><tr><th>Order ID</th><th>Date Created</th><th>Status</th><th>Subtotal</th><th>Tax</th><th>Total</th></tr></thead><tbody class="statement-table-body">';
    
        foreach($orders_array as $key => $item) {
            $html .= '<tr><td>' . $item['id'] . '</td><td>' . $item['date_created'] . '</td><td>' . $item['status'] . '</td><td>$' . $item['subtotal'] . '</td><td>$' . $item['tax'] . '</td><td>$' . $item['total'] . '</td></tr>';
        }
        
        $html .= '</tbody></table></div>';
    
        return $html;
    }
    add_shortcode('statements', 'get_all_customer_orders');

    // Wordpress snippets - Pre-populate Woocommerce checkout fields
    add_filter('woocommerce_checkout_get_value', function($input, $key ) {
        global $current_user;
        switch ($key) :
            case 'billing_first_name':
            case 'shipping_first_name':
                return $current_user->first_name;
            break;

            case 'billing_last_name':
            case 'shipping_last_name':
                return $current_user->last_name;
            break;
            case 'billing_email':
                return $current_user->user_email;
            break;
            case 'billing_phone':
                return $current_user->billing_phone;
            break;
        endswitch;
    }, 10, 2);

    //Wordpress snippets - Statement Filter Function
    add_action('wp_footer', function() {
        ?>
        <script>
            jQuery(document).ready(function() {
                $('#statement_filter_button').on('click', function() {
                    let ajaxurl = '<?php echo admin_url('admin-ajax.php') ?>'; // get ajaxurl
                    let startDate = $('#statement_start_date').val();
                    let endDate = $('#statement_end_date').val();
                    let orderStatus = $('#statement_order_status').val();
                    let userStatement = '';
                    
                    if ($('#statement_order_users').length > 0) {
                        userStatement = $('#statement_order_users').val();
                    }
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            'action': 'statement_filter_customer_orders',
                            'startDate': startDate,
                            'endDate': endDate,
                            'orderStatus': orderStatus,
                            'userStatement': userStatement
                        }
                    }).then(function(data) {
                        data = JSON.parse(data);
                        if (data.text == 'success') {
                            let tableBody = $('.statement-table-body');
                            tableBody.children().remove();
                            let tableRows = data.orders.map((item) => {
                                return '<tr><td>' + item.id + '</td><td>' + item.date_created + '</td><td>' + item.status + '</td><td>$' + item.subtotal + '</td><td>$' + item.tax + '</td><td>$' + item.total + '</td></tr>';
                            })
                            tableBody.append(tableRows);
                        } else {
                            alert('Fail: ' + data);
                            console.log('Fail: ', data);
                        }
                    }).fail(function(error) {
                        console.log('Something went wrong: ', error.responseText);
                    });
                })
            });
        </script>
        <?php
    });

    // Wordpress snippets - Statement print function
    add_action('wp_footer', function() {
        ?>
        <script>
            jQuery(document).ready(function() {
                $('#statement_print_button').on('click', function() {
                    let ajaxurl = '<?php echo admin_url('admin-ajax.php') ?>'; // get ajaxurl
                    let startDate = $('#statement_start_date').val();
                    let endDate = $('#statement_end_date').val();
                    let orderStatus = $('#statement_order_status').val();
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            'action': '',
                            'startDate': startDate,
                            'endDate': endDate,
                            'orderStatus': orderStatus
                        }, success: function(data) {
                            data = JSON.parse(data);
                            if (data.text == 'success') {
                                console.log("create pdf")
                            } else {
                                console.log('Fail: ', data);
                            }
                        }, error: function(error) {
                            console.log('Something went wrong: ', error.responseText);
                        }
                    }).then(data, function(orders) {
                        if (orders.text == 'success') {
                            $.ajax({
                                url: ajaxurl,
                                type: 'POST',
                                data: {
                                    'action': '',
                                    'statement_orders': orders.orders
                                }, success: function(data) {
                                    
                                }
                            })
                        } else {
                            console.log('Statement orders not returned. |', orders);
                        }
                    });
                });
            });
        </script>
        <?php
    });