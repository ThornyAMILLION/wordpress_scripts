from woocommerce import API

wcapi = API(
    url="https://wptest.maxbrakes.com/", # Your store URL
    consumer_key="ck_fa863a0cc9895dd98c4c0859b71d5d8b18bf689d", # Your consumer key
    consumer_secret="ck_fa863a0cc9895dd98c4c0859b71d5d8b18bf689d", # Your consumer secret
    wp_api=True, # Enable the WP REST API integration
    version="wc/v3", # WooCommerce WP REST API version
    query_string_auth=True # Force Basic Authentication as query string true and using under HTTPS
)

r = wcapi.get('products')

print(r.status_code)