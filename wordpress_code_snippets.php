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
                                product = product.split(' ')[0] +  product.split(' ')[2];
                                
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
                        }, success: function(data) {
                            console.log(data)
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
                                console.log('Fail: One or more items does not have inventory |', data);
                            }
                        }, error: function(error) {
                            console.log("Something went wrong: ", error.responseText);
                        }
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
                    $args['menu'] = 17;
                    break;
                case "ACCOUNTANT":
                    $args['menu'] = 37;
                    break;
                default:
                    $args['menu'] = 38;
                    break;
            }
        } else {
            // Non-logged-in menu to display
            $args['menu'] = 18;
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