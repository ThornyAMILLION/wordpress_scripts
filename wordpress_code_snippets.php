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
                        data = JSON.parse(data);
                        if (data == "success") {
                            for (let i of product_arr) {
                                let productID = i[0];
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
                            alert("One or more items does not have inventory | " + data.text);
                            console.log('Fail: One or more items does not have inventory |', data.text);
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
                        product = product.split(' ')[0];
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
                        data = JSON.parse(data);
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
                            console.log('Fail: Product does not have inventory. | ', data.text);
                            // set button to 'Add more'
                            $(thisbutton).html("No Inventory");
                            $(thisbutton).prop('disabled', true);
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
    
    // Wordpress snippets - Get all of current customer orders
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
        foreach($customer_orders as $id) {
            $order = wc_get_order($id->ID);
            $order_user = $order->get_user();
            $date = date_create($order->get_date_created());
            $date2 = date_format($date, "F");
            $date = date_format($date, "Y-m-d H:i:s");
            
            if (get_post_meta($order->get_id(), 'statement_month', true) == '') {
                update_post_meta($order->get_id(), 'statement_month', $date2);
            } else {
                $date2 = get_post_meta($order->get_id(), 'statement_month', true);
            }

            $order_info = ['id' => $order->get_id(), 'subtotal' => $order->get_subtotal(), 'tax' => $order->get_total_tax(), 'total' => $order->get_total(), 'item_count' => $order->get_item_count(), 'date_created' => $date, 'status' => $order->get_status(), 'statement_month' => $date2, 'user' => $order_user->display_name];
            array_push($orders_array, $order_info);
        }

        $start_date = new DateTime("first day of this month");
        $end_date = new DateTime("last day of this month");
        $month = date_format($start_date, 'F');

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

        if ($month !== '') {
            $orders_array = array_filter($orders_array, function($order) use ($month) {
                return $order['statement_month'] == $month;
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

        $html .= '<button id="statement_filter_button" type="button">Filter</button><button id="statement_clear_button">Clear</button></form><div id="statement-table"><table><thead><tr><th>Order ID</th><th>User</th><th>Date Created</th><th>Status</th><th>Subtotal</th><th>Tax</th><th>Total</th><th>Statement Month</th></tr></thead><tbody class="statement-table-body">';
    
        foreach($orders_array as $item) {
            $html .= '<tr><td class="statement-order-id">' . $item['id'] . '</td><td>' . $item['user'] . '</td><td>' . $item['date_created'] . '</td><td>' . $item['status'] . '</td><td>$' . number_format($item['subtotal'], 2) . '</td><td>$' . number_format($item['tax'], 2) . '</td><td>$' . number_format($item['total'], 2) . '</td><td>';
		
            if ($roles[0] == 'administrator') {
                $next_month = date('F', strtotime($item['statement_month'] . '+1 month'));
                $prev_month = date('F', strtotime($item['statement_month'] . 'last month'));
				$html .= '<select class="statement-select-month"><option value="' . $item['statement_month'] . '">' . $item['statement_month'] . '</option><option value="' . $prev_month . '">' . $prev_month . '</option><option value="' . $next_month . '">' . $next_month . '</option></select>';
            } else {
                $html .= $item['statement_month'];
            }
            
            $html .= '</td></tr>';
        }
        
        $html .= '</tbody></table></div><button id="statement_print_button" type="button">Print</button></div>';
    
        return $html;
    }
    add_shortcode('statements', 'get_all_customer_orders');

    // Wordpress snippets - Pre-populate Woocommerce checkout fields
    add_filter('woocommerce_checkout_get_value', function($input, $key) {
        $current_user = wp_get_current_user();
        $current_user_id = get_current_user_id();

        switch ($key) {
            case 'billing_first_name':
            case 'shipping_first_name':
                return $current_user->first_name;
                break;
            case 'billing_last_name':
            case 'shipping_last_name':
                return $current_user->last_name;
                break;
            case 'billing_email':
            case 'shipping_email':
                return $current_user->user_email;
                break;
            case 'billing_phone':
                return get_user_meta($current_user_id, 'billing_phone', true);
                break;
            case 'billing_address_1':
                return get_user_meta($current_user_id, 'billing_address_1', true);
                break;
            case 'billing_city':
                return get_user_meta($current_user_id, 'billing_city', true);
                break;
            case 'billing_postcode':
                return get_user_meta($current_user_id, 'billing_postcode', true);
                break;
            default:
                break;
        }
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
                            tableBody.append(data.rows);
                        } else {
                            alert('Fail: ' + data);
                            console.log('Fail:' + data);
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
                    let userStatement = '';
                    
                    if ($('#statement_order_users').length > 0) {
                        userStatement = $('#statement_order_users').val();
                    }

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            'action': 'statement_info',
                            'startDate': startDate,
                            'endDate': endDate,
                            'orderStatus': orderStatus,
                            'userStatement': userStatement
                        }
                    }).then(function(data) {
                        data = JSON.parse(data);
                        if (data.text == 'success') {
                            let printwin = window.open("");
                            printwin.document.write(data.header);
                            printwin.document.write(data.body); 
                            printwin.document.write(data.footer);
                            printwin.document.write(data.style);
                            printwin.stop();
                            printwin.print();
                            printwin.close();
                        } else {
                            console.log('Fail: ' + data);
                        }
                    }).fail(function(error) {
                        console.log('Something went wrong: ' + error.responseText);
                    });
                });
            });
        </script>
        <?php
    });

    // Wordpress snippets - Make billing details input fields read only
    add_action('woocommerce_checkout_fields','customization_readonly_billing_fields', 10, 1);
    function customization_readonly_billing_fields($checkout_fields) {
        foreach ($checkout_fields['billing'] as $key => $field) {
            $checkout_fields['billing'][$key]['custom_attributes'] = array('readonly'=>'readonly');
        }
        return $checkout_fields;
    }

    // Wordpress snippets - Show users recents orders
    function get_customer_recent_orders() {
        $customer_orders = get_posts(array(
            'numberposts' => 3,
            'meta_key'    => '_customer_user',
            'meta_value'  => get_current_user_id(),
            'post_type'   => wc_get_order_types(),
            'post_status' => array_keys(wc_get_order_statuses()),
        ));
        

        $orders_array = [];
        foreach($customer_orders as $id) {
            $order = wc_get_order($id->ID);
            $date = date_create($order->get_date_created());
            $date = date_format($date,"Y-m-d H:i:s");
            $order_info = ['id' => $order->get_id(), 'subtotal' => $order->get_subtotal(), 'tax' => $order->get_total_tax(), 'total' => $order->get_total(), 'item_count' => $order->get_item_count(), 'date_created' => $date, 'status' => $order->get_status()];
            array_push($orders_array, $order_info);
        }

        $html = '<div class="statements"><div id="statement-table"><table><thead><tr><th>Order ID</th><th>Date Created</th><th>Status</th><th>Subtotal</th><th>Tax</th><th>Total</th></tr></thead><tbody class="statement-table-body">';

        foreach($orders_array as $item) {
            $html .= '<tr><td>' . $item['id'] . '</td><td>' . $item['date_created'] . '</td><td>' . $item['status'] . '</td><td>$' . $item['subtotal'] . '</td><td>$' . $item['tax'] . '</td><td>$' . $item['total'] . '</td></tr>';
        }

        $html .= '</tbody></table></div></div>';

        return $html;
    }
    add_shortcode('recent_orders', 'get_customer_recent_orders');

    // Wordpress snippets - Hide mini-cart dropdown
    add_filter( 'woocommerce_widget_cart_is_hidden', '__return_true' );

    // Wordpress snippets - Check quantity of cart items
    add_action('wp_footer', function() {
        ?>
        <script>
            function check_quantity_of_cart_items() {
                let products = $('.shop_table.cart .cart_item'); 
                if (products.length > 0) {
                    let ajaxurl = '<?php echo admin_url('admin-ajax.php') ?>'; // get ajaxurl
                    
                    let productsArray = []; 
                    for (let i of products) {
                        let displayName = $(i).find('.product-name')[0].innerText;
                        displayName = displayName.split(' ')[0] + displayName.split(' ')[2];	
                        let productQty = Number($(i).find('.qty').val());
                        productsArray.push(['', displayName.trim(), productQty]);
                    }
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            'action': 'b2bking_add_to_cart_check_inventory_ajax',
                            'products': productsArray
                        }
                    }).then(function(data) {
                        data = JSON.parse(data);
                        if (data == 'success') {
                            console.log(data)
                        } else {
                            console.log('Fail:', data.text);
                            for (let item of data.data) {
                                let product_variation = item[0].slice((item[0].length - 3));
                                let product = item[0].slice(0, (item[0].length - 3));
                                product = product + " - " + product_variation;
                                
                                let elem = $(".product-name").filter(function() {
                                    return $(this).text().trim() == product;
                                });
                                
                                elem.parent().children().css({
                                    'cssText': 'background-color: yellow !important'
                                });
                            }
                            
                            $('.checkout-button').css({
                                'cssText': 'pointer-events: none'
                            })
                        }
                    }).fail(function(error) {
                        console.log('Something went wrong: ', error);
                    })
                }
            }
            
            jQuery(document).ready(function() {
                check_quantity_of_cart_items();
            });
        </script>
        <?php
    });

    // Wordpress snippets - Check user input change
    add_action('wp_footer', function() {
        ?>
        <script>
            jQuery(document).ready(function() {//setup before functions
                var typingTimer;                //timer identifier
                var doneTypingInterval = 500;  //time in ms, 5 seconds for example
    
                //on keyup, start the countdown
                $('.shop_table.cart').keyup(function(){
                    clearTimeout(typingTimer);
                    typingTimer = setTimeout(doneTyping, doneTypingInterval);
                });
    
                //user is "finished typing", do something
                function doneTyping() {
                    check_quantity_of_cart_items();
                }
            })
        </script>
        <?php
    });		

    // Wordpress snippets - Change order statement month
    add_action('wp_footer', function() {
        ?>
        <script>
            jQuery(document).ready(function() {
                // add to cart button
                $('.statement-select-month').on('change', function() {
                    let ajaxurl = '<?php echo admin_url('admin-ajax.php') ?>'; // get ajaxurl
                    let month = $(this).val();
                    let orderId = $(this).parent().parent().find('.statement-order-id')[0].innerText;
				
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            'action': 'change_statement_month',
                            'month': month,
                            'orderId': orderId
                        }
                    }).then(function(data) {
                        data = JSON.parse(data);
                        if (data == 'success') {
                            alert('Statement Month changed');
                            console.log('statement month changed');
                        } else {
                            alert('Fail:' + data);
                            console.log("Fail: " + data);
                        } 
                    }).fail(function(error) {
                        console.log("Something went wrong: " + error.responseText);
                    })
                });
            });
        </script>
        <?php
    });

    // Wordpress Snippets - Show inventory of selected product
    add_action('wp_footer', function() {
        ?>
        <script>
            jQuery(document).ready(function() {
                $('.b2bking_bulkorder_form_container_content_line_livesearch').on('click', 'div.b2bking_livesearch_product_result', function() {
                    let ajaxurl = '<?php echo admin_url('admin-ajax.php') ?>'; // get ajaxurl
                    let thisSearchResult = $(this)[0];
                    let product_display = $(this)[0].innerText;
                    product = product_display.split(' ');
                    product = product[3].replace(/\(|\)/g, ""); 

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            'action': 'get_product_inventory',
                            'product': product
                        }
                    }).then(function(data) {
                        data = JSON.parse(data);
                        if (data.text == 'success') {
                            $(thisSearchResult).parent().parent().find('.b2bking_bulkorder_form_container_content_line_inv')[0].innerText = data.inventory;
                        } else {
                            console.log("Fail: " + data);
                        }
                    }).fail(function(error) {
                        console.log("Something went wrong: " + error.responseText);
                    });
                })
            });
        </script>
        <?php
    });

    // Wordpress snippets - Clear line inventory on clicking clear
    add_action('wp_footer', function() {
        ?>
        <script>
            jQuery(document).ready(function() {
                $('.b2bking_bulkorder_form_container_content_line').on('click', 'button.b2bking_bulkorder_clear', function() {
                    let thisButton = $(this)[0];
                    $(thisButton).parent().find('.b2bking_bulkorder_form_container_content_line_inv')[0].innerText = "";
                });
            });
        </script>
        <?php
    });

    // Wordpress snippets - Show all products snippet
    add_shortcode( 'my_purchased_products', 'get_all_customer_purchased_products' );
    function get_all_customer_purchased_products() {
        // GET CURRENT USER
        $current_user = wp_get_current_user();
        if (0 == $current_user->ID) {
            return;
        }

        // GET USER ORDERS (COMPLETED + PROCESSING)
        $customer_orders = get_posts(array(
            'numberposts' => -1,
            'meta_key'    => '_customer_user',
            'meta_value'  => $current_user->ID,
            'post_type'   => wc_get_order_types(),
            'post_status' => array("wc-completed", "wc-processing", "wc-on-hold", "wc-shipped"),
        ));

        // LOOP THROUGH ORDERS AND GET PRODUCT IDS
        if (!$customer_orders) {
            return;
        }

        $products = [];
        foreach ($customer_orders as $customer_order) {
            $order = wc_get_order($customer_order->ID);
            $items = $order->get_items();
            foreach ($items as $item) {

                $item_tax = $item->get_total() * 0.13;
                if ($order->get_payment_method() != 'cod' && $order->get_total() > get_post_meta($order->get_id(), 'two_percent_early_pay', true)) {
                    $item_total = ($item->get_total() * 0.98) + $item_tax;
                } else {
                    $item_total = $item->get_total() + $item_tax;
                }

                $product_info = ['product_name' => $item->get_name(), 'product_id' => $item->get_id(), 'order_id' => $customer_order->ID, 'quantity' => $item->get_quantity(), 'product_total' => $item_total, 'product_tax' => $item_tax, 'product_subtotal' => $item->get_total(), 'variation_id' => $item->get_variation_id()];
                array_push($products, $product_info);
            }
        }

        $html = '<table class="customer-products-purchased"><thead><tr><th>Id</th><th>Product Name</th><th>Product Id</th><th>Order Id</th><th>Quantity</th><th>Subtotal</th><th>Tax</th><th>Total</th><th></th></tr></thead><tbody>';

        foreach($products as $product) {
            $html .= '<tr class="purchased-product"><td class="product-item-id">' . $product['product_id'] . '</td><td class="product-name">' . $product['product_name'] . '</td><td class="product-id">' . $product['variation_id'] . '</td><td class="order-id">' . $product['order_id'] . '</td><td class="product-quantity"><input type="number" value="' . $product['quantity'] . '" min="1" max="' . $product['quantity'] . '"></td><td class="product-subtotal">$' . number_format($product['product_subtotal'], 2) . '</td><td class="product-tax">$' . number_format($product['product_tax'], 2) . '</td><td class="product-total">$' . number_format($product['product_total'], 2) . '</td><td class="return-button-add"><button class="return-add-to-cart" type="button">+</button></button></td></tr>';
        }

        $html .= '</tbody></table><table class="return-cart"><thead><tr><th>Id</th><th>Product Name</th><th>Product Id</th><th>Order Id</th><th>Quantity</th><th>Subtotal</th><th>Tax</th><th>Total</th><th></th></tr></thead><tbody class="return-to-cart-body"></tbody></table>';

        return $html;
    }

    // Wordpress snippets - Add to return cart
    add_action('wp_footer', function() {
        ?>
        <script>
            jQuery(document).ready(function() {
                $('.return-add-to-cart').on('click', function() {
                    let thisbutton = $(this);
                    let tablebody = $('.return-to-cart-body');
                    let tablerow = $(thisbutton).parent().parent().children();
                    let cartProductIds = $('.return-to-cart-body .product-item-id');
                    
                    let isMatch = false;
                    for (let item of cartProductIds) {
                        if (item.innerText == tablerow[0].innerText) {
                            isMatch = true;
                            break;
                        }
                    }

                    if (isMatch == false) {
                        let html = '<tr>';
                        for (let tableItem of tablerow) {
                            if (tableItem.className == 'product-id') {
                                html += '<td class="product-id">' + tableItem.innerText + '</td>';
                            } else if (tableItem.className == 'product-item-id') {
                                html += '<td class="product-item-id">' + tableItem.innerText + '</td>';
                            } else if (tableItem.className == 'product-quantity') {
                                html += '<td>' + tableItem.children[0].value + '</td>';
                            } else if (tableItem.className == 'return-button-add') {
                                html += '<td><button class="return-button-remove" type="button">-</button></td>';
                            } else {
                                html += '<td>' + tableItem.innerText + '</td>';
                            }
                        }
                        html += '</tr>';
                        tablebody.append(html);
                    } else {
                        alert('Item is already in cart');
                    }
                })
            });
        </script>
        <?php
    });

    // Wordpress snippets - Remove from return cart
    add_action('wp_footer', function() {
        ?>
        <script>
            jQuery(document).ready(function() {
                $('.return-to-cart-body').on('click', 'button.return-button-remove', function() {
                    $(this).parent().parent().remove();
                })
            });
        </script>
        <?php
    });

    // Wordpress snippets - Request Return
    add_action('wp_footer', function() {
        ?>
        <script>
            jQuery(document).ready(function() {
                $('.request-return-button').on('click', function() {
                    let refund_method = 'wallet_method';
                    let count = 0;
                    let product_info = {};
                    let selected_product = {};
                    let rr_subject = 'Return';
                    let rr_reason = '';
                    let orders = [];
                    let order_ids = [];
                    
                    let tableRows = $('.return-to-cart-body tr');
                    for (let row of tableRows) {
                        let columns = $(row).children();
                        let order_id = columns[3].innerText;
                        
                        let isMatch = false;
                        for (let orderid of order_ids) {
                            if (orderid == order_id) {
                                isMatch = true;
                                break;
                            }
                        }
                        
                        if (isMatch == false) {
                            order_ids.push(order_id);
                        }
                    }
                    
                    for (let orderid of order_ids) {
                        let total_refund = 0;
                        selected_product = {};
                        for (let row of tableRows) {
                            let columns = $(row).children();
                            if (orderid == columns[3].innerText) {
                                product_info = {};
                                let variation_id = columns[2].innerText;
                                let product_id = '';
                                let item_id = columns[0].innerText;
                                let product_price = columns[7].innerText;
                                let product_qty = columns[4].innerText;
                                product_price = product_price.replace('$', '');
                                product_info['product_id'] = product_id;
                                product_info['variation_id'] = variation_id;
                                product_info['item_id'] = item_id;
                                product_info['price'] = product_price;
                                product_info['qty'] = product_qty;
                                selected_product[count] = product_info;
                                count++;
                                total_refund += Number(product_price);
                            }
                        }
                        orders.push([orderid, selected_product, total_refund])
                    }				
                    
                    let responses = orders.map((order) => {
                        let data = {
                            action	:'wps_rma_save_return_request',
                            products: order[1],
                            amount	: order[2],
                            subject	: rr_subject,
                            reason	: rr_reason,
                            orderid : order[0],
                            bankdetails : $( '#wps_rma_bank_details' ).val(),
                            refund_method : refund_method,
                            security_check	: wrael_common_param.wps_rma_nonce
                        }
    
                        return $.ajax({
                            url: wrael_common_param.ajaxurl, 
                            type: 'POST',  
                            data: data,
                            dataType :'json',
                        })
                    })

                    $.when(responses).done(function() {
                        console.log("success");
                    })
                })
            });
        </script>
        <?php
    });