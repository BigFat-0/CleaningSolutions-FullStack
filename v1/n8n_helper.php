<?php
// v1/n8n_helper.php

// Define the N8N Base URL
define('N8N_BASE_URL', 'https://n8n.timscleaning.co.uk');

/**
 * Sends a webhook to n8n.
 * 
 * @param string $endpoint The webhook endpoint name (e.g., 'new-user'). 
 *                         It will be appended to N8N_BASE_URL . '/webhook/'.
 * @param array $data The data to send as JSON.
 */
function sendWebhook($endpoint, $data) {
    $url = N8N_BASE_URL . '/webhook/' . $endpoint;
    $jsonData = json_encode($data);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    
    // Fire and forget: Timeout after 1 second
    curl_setopt($ch, CURLOPT_TIMEOUT, 1); 
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    curl_exec($ch);
    curl_close($ch);
}
?>
