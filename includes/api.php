<?php 
/*
 * TODO Does something
 */
function fh_seo_generate($request) {
    $requested_id = $request->get_param('id') ?? false;
    $offset = $request->get_param('offset') ?? 0;

    $output = [];
    
    // Get one requested attachment? Or all?
    if($requested_id) {
        $attachment_ids = [$requested_id];
    } else {
        $args = [
            'post_type' => 'attachment',
            'post_mime_type' => ['image/jpeg', 'image/gif', 'image/png'],
            'posts_per_page' => 2,
            'offset' => $offset,
            'meta_query' => [
                [
                 'key' => 'fh_seo_timestamp',
                 'compare' => 'NOT EXISTS'
                ],
            ],
            'fields' => 'ids'
        ];
        $attachment_ids = get_posts($args);        
    }    
    
    // Regenerate each 
    foreach($attachment_ids as $attachment_id) {
        $output['data'][] = fh_seo_rename_and_discribe($attachment_id);        
    }

    // Include query meta always
    $output['meta']['nonce'] = wp_create_nonce('wp_rest');

    return new WP_REST_Response($output, 200); 
}

/*
 * This customizes the CORS headers the server will accept, allows use of token header
 */
function fh_seo_cors_headers()
{
    add_filter("rest_pre_serve_request", function ($value) {
        header("Access-Control-Allow-Headers: Content-Type");
        return $value;
    });
}
add_action("rest_api_init", "fh_seo_cors_headers", 15);


/**
 * Checks if the user is logged into WordPress and can edit uses (so is an admin). Used as permission_callback of WP JSON.
 *
 * @return Boolean|WP_Error true if valid token, otherwise error
 */
function fh_seo_permissions($request)
{
    // For this to work, generate a nonce using: `wp_create_nonce('wp_rest')` and set it as a HTTP header `X-WP-Nonce`.
    // Must also include valid WP logged in cookies in request headers too (credentials: include and CORS on)
    return current_user_can('edit_users');
    //return true;
}