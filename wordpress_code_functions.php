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
                            'orderNum' : orderNum,
                            'orderStatus': orderStatus,
                            'discountCheck': orderDiscountCheck
                        },
                        success: function(data) {
                            // This outputs the result of the ajax request (The Callback)
                            console.log(data);
                        },
                        error: function(errorThrown) {
                            window.alert(errorThrown);
                        }
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
                'method'      => 'POST',
                'timeout'     => 45,
                'redirection' => 5,
                'httpversion' => '1.0',
                'blocking'    => true,
                'headers'     => array(),
                'body'        => $order,
                'cookies'     => array()
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
            $no_inventory = '';
            
            foreach($products as $product) {
                $response = wp_remote_get($url . $product[1]);
                $response = $response['body'];
                $response = json_decode($response);
                
                $count_previous_value = $count;			
                foreach($response->int as $part) {
                    if ($part->{'s'} == $product[1] && $product[2] < $part->{'q'}) {
                        $count += 1;
                    }
                }
                
                if ($count_previous_value == $count) {
                    $no_inventory .= $product[1] . '|';
                }
                
            }
            
            if ($count == $products_len) {
                echo "success";
            } else {
                echo "fail: count - " . $count . ' products - ' . $products_len . ' | ' . $no_inventory;
            }
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
    function new_order_2_percent_meta_data($order_id) {
        $order = wc_get_order($order_id);
        $tax_total = $order->get_total_tax();
        
        // Calculate subtotal excluding items that do not have 2% discount
        $prices_to_discount = 0;
        $prices_to_add = 0;
        foreach ($order->get_items() as $item) {
            $product = wc_get_product($item->get_product_id());
            $temp_item = $product->get_sku();
            if (!stristr($temp_item, 'AFN')) {
                $prices_to_discount += number_format($item->get_total());
            } else {
                $prices_to_add += number_format($item->get_total());
            }
        }
        
        $two_percent_total = ($prices_to_discount * 0.98) + $prices_to_add + $tax_total;
        update_post_meta($order_id, 'two_percent_early_pay', $two_percent_total);
    }
    add_action('woocommerce_checkout_update_order_meta', 'new_order_2_percent_meta_data');

    // Remove cart and mini cart product links
    add_filter( 'woocommerce_cart_item_permalink', '__return_null' );
    add_filter( 'woocommerce_mini_cart_item_name_permalink', '__return_null' );

    function statement_filter_customer_orders() {
        if (isset($_POST)) {
            $start_date = $_POST['startDate'];
            $end_date = $_POST['endDate'];
            $order_status = $_POST['orderStatus'];
            
            $customer_orders = get_posts(array(
                'numberposts' => -1,
                'meta_key'    => '_customer_user',
                'meta_value'  => get_current_user_id(),
                'post_type'   => wc_get_order_types(),
                'post_status' => array_keys(wc_get_order_statuses()),
            ));
            
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

            if ($order_status !== '') {
                if ($order_status == 'Paid') {
                    $orders_array = array_filter($orders_array, function($order) {
                        return $order['status'] == 'completed';
                    });
                } else {
                    $orders_array = array_filter($orders_array, function($order) {
                        return $order['status'] != 'completed';
                    });
                }
            }

            $orders_array = array_values($orders_array);
            
            if (empty($orders_array)) {
                $data = 'fail: no orders match filters.';
            } else {
                $data = ['orders' => $orders_array, 'text' => 'success'];
            }
        }
        echo json_encode($data);
        die();
    }
    // This bit is a special action hook that works with the WordPress AJAX functionality.
    add_action('wp_ajax_statement_filter_customer_orders', 'statement_filter_customer_orders');
    add_action('wp_ajax_nopriv_statement_filter_customer_orders', 'statement_filter_customer_orders'); 