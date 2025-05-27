<?php
function log_search($search_query, $results_count, $category_id = null) {
    global $conn;
    
    // Get user ID if logged in
    $user_id = isset($_SESSION['customer_id']) ? $_SESSION['customer_id'] : null;
    
    // Insert search record
    $stmt = $conn->prepare("INSERT INTO search_logs (search_query, user_id, results_count, category_id) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("siii", $search_query, $user_id, $results_count, $category_id);
    $stmt->execute();
}
?>
