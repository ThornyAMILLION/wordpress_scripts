<?php
    // Wordpress child theme functions.php code
    // Load jQuery
    wp_enqueue_script('jquery');

    function change_order_status() { 
        ?>
        <script>
            jQuery(document).ready(function($) {
                let ajaxurl = '<?php echo admin_url('admin-ajax.php') ?>'; // get ajaxurl
    
                // This does the ajax request (The Call).
                $('#wpforms-submit-11444').click(function() {
                    let orderNum = $('#wpforms-11444-field_1').val();
                    let orderStatus = $('#wpforms-11444-field_2').val();
                    let orderDiscountCheck = $('#wpforms-11444-field_3_1').is(':checked');
    
                    $.ajax({
                        url: ajaxurl, // Since WP 2.8 ajaxurl is always defined and points to admin-ajax.php
                        type: 'POST',
                        data: {
                            'action': 'change_order_status_ajax', // This is our PHP function below
                            'orderNum': orderNum,
                            'orderStatus': orderStatus,
                            'discountCheck': orderDiscountCheck
                        }
                    }).then(function(data) {
                        // This outputs the result of the ajax request (The Callback)
                        console.log(data);
                    }).fail(function(errorThrown) {
                        window.alert(errorThrown);
                    });
                });
            });
        </script>
        <?php 
    }
    add_action('wp_footer', 'change_order_status');

    function change_order_status_ajax() {
        if(isset($_POST)) {
            $order_number = $_POST['orderNum'];
            $order_status = $_POST['orderStatus'];
            $order_discount_check = $_POST['discountCheck'];
            $order = wc_get_order($order_number);
            
            $text_response = "Order status changed to ";

            switch($order_status) {
                case 'On Hold':
                    $order->update_status('wc-on-hold');
                    echo $text_response . $order_status;
                    break;
                case 'Processing':
                    $order->update_status('wc-processing');
                    echo $text_response . $order_status;
                    break;
                case 'Completed':
                    if ($order_discount_check == "true") {
                        $new_total = round(get_post_meta($order->get_id(), 'two_percent_early_pay', true), 2);
					    $order->set_total(strval($new_total));
                    }

                    $order->update_status('wc-completed');
                    echo $text_response . $order_status;
                    break;
                case 'Pending Payment':
                    $order->update_status('wc-pending-payment');
                    echo $text_response . $order_status;
                    break;
                default:
                    echo "Order status does not match options";
                    break;
            }
        }
        
        // Always die in functions echoing AJAX content
        die();
    }
    // This bit is a special action hook that works with the WordPress AJAX functionality.
    add_action('wp_ajax_change_order_status_ajax', 'change_order_status_ajax');
    add_action('wp_ajax_nopriv_change_order_status_ajax', 'change_order_status_ajax'); 

    add_filter('wc_product_has_unique_sku', '__return_false');

    function new_order_ajax_post() {
        // The $_POST contains all the data sent via AJAX from the Javascript call
        if (isset($_POST)) {
            $order_number = $_POST['orderNum'];
            $order = wc_get_order($order_number);

            $url = "https://wptest.maxbrakes.com/";
            $args = array(
                'method' => 'POST',
                'timeout' => 45,
                'redirection' => 5,
                'httpversion' => '1.0',
                'blocking' => true,
                'headers' => array(),
                'body' => $order,
                'cookies' => array()
            );
            
            $response = wp_remote_post($url, $args);
            $response = json_encode($response);		
            
            // Return the result to the Javascript function (The Callback)
            if (is_wp_error($response)) {
                $error_message = $response->get_error_message();
                echo "Something went wrong: $error_message";
            } else {
                echo $response;
            }
        }

    // Always die in functions echoing AJAX content
    die();
    }
    // This bit is a special action hook that works with the WordPress AJAX functionality.
    add_action('wp_ajax_new_order_ajax_post', 'new_order_ajax_post');
    add_action('wp_ajax_nopriv_new_order_ajax_post', 'new_order_ajax_post'); 

    function b2bking_add_to_cart_check_inventory_ajax() {
        // The $_POST contains all the data sent via AJAX from the Javascript call
        if (isset($_POST)) {
            $products = $_POST['products'];
            $url = 'http://70.25.52.182:33333/?token=uULQwr6GNmNQSbbGefmN9qOUwvJ96OYy&check=';
            $products_len = count($products);
            $count = 0;
            $no_inventory = [];
    
            foreach($products as $product) {
                $response = wp_remote_get($url . $product[1]);
                $response = $response['body'];
                $response = json_decode($response);
    
                foreach($response->int as $part) {
                    if ($part->{'s'} == $product[1] && $product[2] < $part->{'q'}) {
                        $count += 1;
                    } else if ($part->{'s'} == $product[1] && $product[2] > $part->{'q'}) {
                        array_push($no_inventory, array($product[1], $part->{'q'}));
                    }
                }
            }
            
            if ($count == $products_len) {
                $data = "success";
            } else {
                $data = ['data' => $no_inventory, 'text' => "Count - " . $count . ' products - ' . $products_len];
            }
            
            echo json_encode($data);
        }
        die();
    }
    // This bit is a special action hook that works with the WordPress AJAX functionality.
    add_action('wp_ajax_b2bking_add_to_cart_check_inventory_ajax', 'b2bking_add_to_cart_check_inventory_ajax');
    add_action('wp_ajax_nopriv_b2bking_add_to_cart_check_inventory_ajax', 'b2bking_add_to_cart_check_inventory_ajax'); 

    function my_redirect_if_user_not_logged_in() {
        $homepage_id = get_option('page_on_front');
        if (!is_user_logged_in() && is_page($homepage_id)) {
            wp_redirect('https://wptest.maxbrakes.com/login');
            exit;
        }
    }
    add_action('template_redirect', 'my_redirect_if_user_not_logged_in');

    // Add custom field to orders to show 2 percent early pay
    function new_order_meta_data($order_id) {
        $order = wc_get_order($order_id);
        if ($order->get_payment_method() != 'cod') {
            $tax_total = $order->get_total_tax();
    
            // Calculate subtotal excluding items that do not have 2% discount
            $prices_to_discount = 0;
            $prices_to_add = 0;
            foreach ($order->get_items() as $item) {
                $product = wc_get_product($item->get_product_id());
                $temp_item = $product->get_sku();
                if (!stristr($temp_item, 'AFN')) {
                    $prices_to_discount += number_format($item->get_subtotal());
                } else {
                    $prices_to_add += number_format($item->get_subtotal());
                }
            }
    
            $two_percent_total = ($prices_to_discount * 0.98) + $prices_to_add + $tax_total;
            update_post_meta($order_id, 'two_percent_early_pay', $two_percent_total);
        } else {
            update_post_meta($order_id, 'two_percent_early_pay', $order->get_total());
        }
        $date = date_create($order->get_date_created());
        $date = date_format($date, "F");
        update_post_meta($order_id, 'statement_month', $date);
    }
    add_action('woocommerce_checkout_update_order_meta', 'new_order_meta_data');

    // Remove cart and mini cart product links
    add_filter( 'woocommerce_cart_item_permalink', '__return_null' );
    add_filter( 'woocommerce_mini_cart_item_name_permalink', '__return_null' );

    function statement_filter_customer_orders() {
        if (isset($_POST)) {
            $user = wp_get_current_user();
            $roles = (array) $user->roles;
            $start_date = $_POST['startDate'];
            $end_date = $_POST['endDate'];
            $month = '';
            $order_status = $_POST['orderStatus'];
            $user_statement = '';
            
            $customer_orders = [];
            if ($roles[0] == 'administrator') {
                $user_statement = $_POST['userStatement'];
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

                $order_info = ['id' => $order->get_id(), 'subtotal' => $order->get_subtotal(), 'tax' => $order->get_total_tax(), 'total' => $order->get_total(), 'item_count' => $order->get_item_count(), 'date_created' => $date, 'status' => $order->get_status(), 'user_id' => $order->get_user_id(), 'statement_month' => $date2, 'user' => $order_user->display_name];
                array_push($orders_array, $order_info);
            }

            if ($start_date !== '') {
                $date = new DateTime($start_date);
                $month = date_format($date, 'F');

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

            if ($order_status !== '') {
                if ($order_status == 'Paid') {
                    $orders_array = array_filter($orders_array, function($order) {
                        return $order['status'] == 'completed' || $order['status'] == 'return-approved' || $order['status'] == 'return-requested';
                    });
                } else {
                    $orders_array = array_filter($orders_array, function($order) {
                        return $order['status'] != 'completed' && $order['status'] != 'return-approved' && $order['status'] != 'return-requested';
                    });
                }
            }

            if ($user_statement !== '') {
                $orders_array = array_filter($orders_array, function($order) use($user_statement) {
                    return $order['user_id'] == $user_statement;
                });
            }

            $orders_array = array_values($orders_array);
            $html = '';
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

            if (empty($orders_array)) {
                $data = 'fail: no orders match filters.';
            } else {
                $data = ['text' => 'success', 'rows' => $html];
            }
        }
        echo json_encode($data);
        die();
    }
    // This bit is a special action hook that works with the WordPress AJAX functionality.
    add_action('wp_ajax_statement_filter_customer_orders', 'statement_filter_customer_orders');
    add_action('wp_ajax_nopriv_statement_filter_customer_orders', 'statement_filter_customer_orders'); 

    // Wordpress snippets - Open new window to allow for users to print statement as pdf
    function statement_info() {
        if (isset($_POST)) {
            if ($_POST['userStatement'] != '') {
                $user = wp_get_current_user();
                $roles = (array) $user->roles;
                $start_date = $_POST['startDate'];
                $end_date = $_POST['endDate'];
                $month = '';
                $order_status = $_POST['orderStatus'];
                $user_statement = '';
                $customer_orders = [];
                $customer_info = '';

                if ($roles[0] == 'administrator') {
                    $user_statement = $_POST['userStatement'];
                    $customer_orders = get_posts(array(
                        'numberposts' => -1,
                        'meta_key'    => '_customer_user',
                        'meta_value'  => $user_statement,
                        'post_type'   => wc_get_order_types(),
                        'post_status' => array_keys(wc_get_order_statuses()),
                    ));
                    $customer_info = new WC_Customer($user_statement);
                } else {
                    $customer_orders = get_posts(array(
                        'numberposts' => -1,
                        'meta_key'    => '_customer_user',
                        'meta_value'  => get_current_user_id(),
                        'post_type'   => wc_get_order_types(),
                        'post_status' => array_keys(wc_get_order_statuses()),
                    ));
                    $customer_info = new WC_Customer(get_current_user_id());
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

                    $order_info = ['id' => $order->get_id(), 'subtotal' => $order->get_subtotal(), 'tax' => $order->get_total_tax(), 'total' => $order->get_total(), 'date_created' => $date, 'status' => $order->get_status(), 'user_id' => $order->get_user_id(), 'user' => $order_user->display_name, 'statement_month' => $date2];
                    array_push($orders_array, $order_info);
                }
        
                if ($start_date !== '') {
                    $date = new DateTime($start_date);
                    $month = date_format($date, 'F'); 

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
        
                $orders_array = array_filter($orders_array, function($order) {
                    return preg_match("/completed|return\-approved|return\-requested|processing|on\-hold/i", $order['status']);
                });
                
                $orders_array = array_values($orders_array);

                $customer_display_name = $customer_info->get_display_name();
                $customer_address = $customer_info->get_billing_address_1();
                $customer_city = $customer_info->get_billing_city();
                $customer_state = $customer_info->get_billing_state();
                $customer_post_code = $customer_info->get_billing_postcode();
                $customer_email = $customer_info->get_email();
                $customer_phone = $customer_info->get_billing_phone();

                $site_icon = '<table class="head container"><tr><td class="header"><img src="' . get_site_icon_url() . '" alt="Max Advanced Brakes" width="200" height="100"></td></tr></table>';

                $header = '<div><table class="order-data-addresses"><tbody><tr><td class="address billing-address"><div>' . $customer_display_name . '</div><div>' . $customer_address . '</div><div>' . $customer_city . ' ' . $customer_state . ' ' . $customer_post_code . '</div><div class="billing-email">' . $customer_email . '</div><div class="billing-phone">' . $customer_phone . '</div></td></tr></tbody></table></div>';

                $body = '<div class="statements"><div id="statement-table"><table class="order-details"><thead><tr><th>Order ID</th><th>User</th><th>Date Created</th><th>Status</th><th>Subtotal</th><th>Tax</th><th>Total</th></tr></thead><tbody class="statement-table-body">';

                $tax_total = 0;
                $prices_to_discount = 0;
		        $prices_to_add = 0;
                $statement_total = 0;
                foreach($orders_array as $item) {
                    $prices_to_discount += number_format($item['subtotal'], 2);
                    $tax_total += number_format($item['tax'], 2);
                    $statement_total += number_format($item['total'], 2);

                    $body .= '<tr><td class="statement-order-id">' . $item['id'] . '</td><td>' . $item['date_created'] . '</td><td>' . $item['status'] . '</td><td>$' . number_format($item['subtotal'], 2) . '</td><td>$' . number_format($item['tax'], 2) . '</td><td>$' . number_format($item['total'], 2) . '</td></tr>';
                }

                $two_percent_total = ($prices_to_discount * 0.98) + $prices_to_add + $tax_total;

                $body .= '</tbody><tfoot><tr class="no-borders"><td class="no-borders"><div class="document-notes"></div><div class="customer-notes"></div></td><td class="no-borders" colspan="2"><table class="totals"><tfoot><tr><th class="description">Total</th><td class="price"><span class="totals-price">$' . number_format($statement_total, 2) . '</span></td></tr><tr><th class="description">Early Pay (2% 10 days)</th><td class="price"><span class="totals-price">$' . number_format($two_percent_total, 2) . '</span></td></tr></tfoot></table></td></tr></tfoot></table></div></div>';

                $footer = '<div class="bottom-spacer"><p>NOTE: Eligible for early pay only if paid within 10 days. 2% interest per month on all accounts over 30 days. 5% handling fee on all new returns. All goods to be returned must be in original package. Within 5 days of shipment all discrepancy must be reported.</p></div>';

                $style = "/* Main Body */ @page { margin-top: 1cm; margin-bottom: 3cm; margin-left: 2cm; margin-right: 2cm; } body { background: #fff; color: #000; margin: 0cm; font-family: 'Open Sans', sans-serif; /* want to use custom fonts? http://docs.wpovernight.com/woocommerce-pdf-invoices-packing-slips/using-custom-fonts/ */ font-size: 9pt; line-height: 100%; /* fixes inherit dompdf bug */ } h1, h2, h3, h4 { font-weight: bold; margin: 0; } h1 { font-size: 16pt; margin: 5mm 0; } h2 { font-size: 14pt; } h3, h4 { font-size: 9pt; } p { margin: 0; padding: 0; } p + p { margin-top: 1.25em; } a { border-bottom: 1px solid; text-decoration: none; } /* Basic Table Styling */ table { border-collapse: collapse; border-spacing: 0; page-break-inside: always; border: 0; margin: 0; padding: 0; } th, td { vertical-align: top; text-align: left; } table.container { width:100%; border: 0; } tr.no-borders, td.no-borders { border: 0 !important; border-top: 0 !important; border-bottom: 0 !important; padding: 0 !important; width: auto; } div.bottom-spacer { clear: both; height: 8mm; } /* Header */ table.head { margin-bottom: 12mm; } td.header img { max-height: 3cm; /* may be overriden by the settings */ width: auto; } td.header { font-size: 16pt; font-weight: 700; } td.shop-info { width: 40%; } .document-type-label { text-transform: uppercase; } /* Recipient addressses & order data */ table.order-data-addresses { width: 100%; margin-bottom: 10mm; } table.order-data-addresses td.address { word-wrap: break-word; } td.order-data { width: 40%; } td.order-data table { width: 100%; } td.order-data table th { font-weight: normal; padding-right: 2mm; width: 50%; } .invoice .shipping-address { width: 30%; } .packing-slip .billing-address { width: 30%; } /* Order details */ table.order-details { width:100%; margin-bottom: 8mm; page-break-before: avoid; } .order-details tr { page-break-inside: always; page-break-after: auto; } .order-details td, .order-details th { border-bottom: 1px #ccc solid; border-top: 1px #ccc solid; padding: 0.375em; } .order-details th { font-weight: bold; text-align: left; } .order-details thead th { color: white; background-color: black; border-color: black; } /* item meta formatting for WC2.6 and older */ dl { margin: 4px 0; } dt, dd, dd p { display: inline; font-size: 7pt; line-height: 7pt; } dd { margin-left: 5px; } dd:after { content: '\A'; white-space: pre; } /* item-meta formatting for WC3.0+ */ .wc-item-meta { margin: 4px 0; font-size: 7pt; line-height: 7pt; } .wc-item-meta p { display: inline; } .wc-item-meta li { margin: 0; margin-left: 5px; } /* Notes & Totals */ .document-notes, .customer-notes { margin-top: 5mm; word-wrap: break-word; } table.totals { width: 100%; margin-top: 5mm; table-layout: fixed; } table.totals th, table.totals td { border: 0; border-top: 1px solid #ccc; border-bottom: 1px solid #ccc; } table.totals th.description, table.totals td.price { width: 50%; } table.totals tr.order_total td, table.totals tr.order_total th { border-top: 2px solid #000; border-bottom: 2px solid #000; font-weight: bold; } table.totals tr.payment_method { display: none; } /* Footer Imprint */ #footer { position: absolute; bottom: -2cm; left: 0; right: 0; height: 2cm; /* if you change the footer height, don't forget to change the bottom (=negative height) and the @page margin-bottom as well! */ text-align: center; border-top: 0.1mm solid gray; margin-bottom: 0; padding-top: 2mm; } /* page numbers */ .pagenum:before { content: counter(page); } .pagenum,.pagecount { font-family: sans-serif; }";

                if (empty($orders_array)) {
                    $data = 'no orders match filters.';
                } else {
                    $data = ['orders' => $orders_array, 'text' => 'success', 'icon' => $site_icon, 'body' => $body, 'header' => $header, 'footer' => $footer, 'style' => $style];
                }
            } else {
                $data = 'No user selected';
            }
            echo json_encode($data);
        }
        die();
    }
    // This bit is a special action hook that works with the WordPress AJAX functionality.
    add_action('wp_ajax_statement_info', 'statement_info');
    add_action('wp_ajax_nopriv_statement_info', 'statement_info'); 

    // B2bking bulk order cream product description
    add_filter('b2bking_bulkorder_indigo_search_name_display', function($name, $product) {
        if ($product->get_sku() === $product->get_name()) {
            $name = $product->get_sku() . " | " . $product->get_description();
        } else {
            $name = $product->get_sku() . " | " . $product->get_name() . " | " . $product->get_description();
        }
        
        return $name;
    }, 10, 2);

    add_filter('b2bking_bulkorder_cream_search_name_display', function($name, $product) {
        if ($product->get_sku() === $product->get_name()) {
            $name = $product->get_sku() . " | " . $product->get_description();
        } else {
            $name = $product->get_sku() . " | " . $product->get_name() . " | " . $product->get_description();
        }

        return $name;
    }, 10, 2);

    // Change the order statement month meta value
    function change_statement_month() {
        if (isset($_POST)) {
            $order_id = $_POST['orderId'];
            $month = $_POST['month'];

            update_post_meta($order_id, 'statement_month', $month);
            
            echo json_encode('success');
        }
        die();
    }
    // This bit is a special action hook that works with the WordPress AJAX functionality.
    add_action('wp_ajax_change_statement_month', 'change_statement_month');
    add_action('wp_ajax_nopriv_change_statement_month', 'change_statement_month'); 

    // Get product inventory and display in bulk order form
    function get_product_inventory() {
        if (isset($_POST)) {
            $url = 'http://70.25.52.182:33333/?token=uULQwr6GNmNQSbbGefmN9qOUwvJ96OYy&check='; 
            $product = $_POST['product'];
            $inventory = '';

            $response = wp_remote_get($url . $product);
            $response = $response['body'];
            $response = json_decode($response);

            foreach($response->int as $products) {
                if ($product == $products->{'s'} && $products->{'q'} < 10) {
                    $inventory = $products->{'q'};
                } else if ($product == $products->{'s'} && $products->{'q'} > 10) {
                    $inventory = '10+';
                }
            }  

            if ($inventory != '') {
                $data = ['inventory' => $inventory, 'text' => 'success'];
            } else {
                $data = 'item not found';
            }

            echo json_encode($data);
        }
        die();
    }
    // This bit is a special action hook that works with the WordPress AJAX functionality.
    add_action('wp_ajax_get_product_inventory', 'get_product_inventory');
    add_action('wp_ajax_nopriv_get_product_inventory', 'get_product_inventory'); 

    // Function to accept return request using wps_rma_return_req_approve() as template
    function return_req_approve() {
        if (isset($_POST)) {
            $orderid = $_POST['orderId'];
            $productid = $_POST['productid'];

            $return_datas = get_post_meta($orderid, 'wps_rma_return_product', true);
            foreach ($return_datas as $key => $return_data) {
                $return_products = $return_data['products'];
                foreach ($return_products as $returnkey => &$return_product) {
                    if ($return_product['item_id'] != $productid) {
                        unset($return_product[$returnkey]);
                    }
                }
            }
            $products = $return_datas;
            $response = wps_rma_return_req_approve_callback($orderid, $products);
            echo json_encode($response);
        }
        die();
    }
    // This bit is a special action hook that works with the WordPress AJAX functionality.
    add_action('wp_ajax_return_req_approve', 'return_req_approve');
    add_action('wp_ajax_nopriv_return_req_approve', 'return_req_approve');

    // Function that will send return details to external database
    function send_return_info_to_external_db() {
        if (isset($_POST)) {
            $orders = $_POST['orders'];
            $date_created = date('Y-m-d');

            $data = ['return_info' => $orders, 'date_created' => $date_created, 'type' => 'return'];

            $data = json_encode($data);
		
            $url = "https://namor.club/z.php";
            $args = array(
                'method' => 'POST',
                'timeout' => 45,
                'redirection' => 5,
                'httpversion' => '1.0',
                'blocking' => true,
                'headers' => array(),
                'body' => $data,
                'cookies' => array()
            );
            
            $response = wp_remote_post($url, $args);
            $response = json_encode($response);		
            
            // Return the result to the Javascript function (The Callback)
            if (is_wp_error($response)) {
                $error_message = $response->get_error_message();
                echo "Something went wrong: $error_message";
            } else {
                echo $response;
            }
        }
        die();
    }