<?php
    function fh_seo_azure_endpoint() {
        return "https://auto-seo.cognitiveservices.azure.com";
    }

// Get image discription and tags from Azure    
    function fh_seo_azure_post_describe($attachment_id)
    {
        $options = get_option('fh_seo_settings');
        
        $body = [
            "url" => wp_get_attachment_url($attachment_id)
        ];
        
        $response = wp_remote_post( fh_seo_azure_endpoint() . '/vision/v3.1/describe?maxCandidates=2', [
            'body'    => wp_json_encode($body),
            'headers' => [
                'Content-Type' => 'application/json',
                'Ocp-Apim-Subscription-Key' => $options['api_key'],
            ],
        ]);
        
        $body = wp_remote_retrieve_body($response);

        return json_decode($body);
    }   
    

// Get focal point from Azure    
    function fh_seo_azure_post_area_of_interest($attachment_id)
    {
        $options = get_option('fh_seo_settings');
        
        $body = [
            "url" => wp_get_attachment_url($attachment_id)
        ];
        
        $response = wp_remote_post( fh_seo_azure_endpoint() . '/vision/v3.1/areaOfInterest', [
            'body'    => wp_json_encode($body),
            'headers' => [
                'Content-Type' => 'application/json',
                'Ocp-Apim-Subscription-Key' => $options['api_key'],
            ],
        ]);
        
        $body = wp_remote_retrieve_body($response);

        return json_decode($body);
    }