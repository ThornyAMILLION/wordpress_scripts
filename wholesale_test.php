<?php
    // url: 'http://70.25.52.182:33333/';
    // token: uULQwr6GNmNQSbbGefmN9qOUwvJ96OYy
    // check: ''

    $url = 'http://70.25.52.182:33333/';
    $token = '?token=uULQwr6GNmNQSbbGefmN9qOUwvJ96OYy';
    $products = [array('R3295ONZ', 10), array('R3295OCG', 10)];
    $product_arr = [];
    $products_len = count($products);
    $count = 0;

    var_dump($products);

    foreach($products as $x => $product) {
        $check = '&check=' . $product[0];

        $url_query = $url . $token . $check;

        // create a new cURL resource
        $ch = curl_init();

        // set URL and other appropriate options
        curl_setopt($ch, CURLOPT_URL, $url_query); // setting the authorization key in header.
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // grab URL and pass it to the browser
        $ch_response = curl_exec($ch);

        $response = json_decode($ch_response, true);

        foreach($response['int'] as $y => $part) {
            if ($part['s'] == $product[0] && (int)$product[1] < (int)$part['q']) {
                $count += 1;
            }
        }

        // close cURL resource, and free up system resources
        curl_close($ch);
    }
    echo $count . ' ' . $products_len; 
    if ($count == $products_len) {
        echo 'success';
    } else {
        echo 'fail';
    }