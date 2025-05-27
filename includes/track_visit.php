<?php
function track_store_visit($store_id) {
    global $conn;
    
    // Get visitor IP
    $visitor_ip = $_SERVER['REMOTE_ADDR'];
    
    // Get user agent
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    
    // Get current page URL
    $page_url = $_SERVER['REQUEST_URI'];
    
    // Insert visit record
    $stmt = $conn->prepare("INSERT INTO store_visits (store_id, visitor_ip, user_agent, page_url) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $store_id, $visitor_ip, $user_agent, $page_url);
    $stmt->execute();
}
?>
