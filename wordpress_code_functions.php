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
                $('#wpforms-submit-12023').click(function() {
                    let orderNum = $('#wpforms-12023-field_1').val();
                    let orderStatus = $('#wpforms-12023-field_2').val();
                    
                    $.ajax({
                        url: ajaxurl, // Since WP 2.8 ajaxurl is always defined and points to admin-ajax.php
                        type: 'POST',
                        data: {
                            'action': 'change_order_status_ajax', // This is our PHP function below
                            'orderNum' : orderNum,
                            'orderStatus': orderStatus
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
            $order = wc_get_order($order_number);
            
            $text_response = "Order status changed to ";

            if ($order_status == 'On Hold') {
                $order->update_status('wc-on-hold');
                echo $text_response . $order_status;
            } else if ($order_status == 'Processing') {
                $order->update_status('wc-processing');
                echo $text_response . $order_status;
            } else if ($order_status == 'Completed') {
                $order->update_status('wc-completed');
                echo $text_response . $order_status;
            } else if ($order_status == 'Pending Payment') {
                $order->update_status('wc-pending-payment');
                echo $text_response . $order_status;
            } else {
                echo "Order status does not match options";
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
        $subtotal = $order->get_subtotal();
        $two_percent_total = $subtotal * 0.98;
        update_post_meta($order_id, 'two_percent_early_pay', $two_percent_total);
    }
    add_action('woocommerce_checkout_update_order_meta', 'new_order_2_percent_meta_data');