<?php
/**
 * English Translations for Marketplace
 * This file contains translations for common text used throughout the site
 */

// Define translations array
$translations = [
    // General
    'site_name' => 'E-Marketplace',
    'welcome' => 'Welcome to E-Marketplace',
    'home' => 'Home',
    'products' => 'Products',
    'stores' => 'Stores',
    'categories' => 'Categories',
    'deals' => 'Deals',
    'offers' => 'Offers',
    'top_rated' => 'Top Rated',
    'most_liked' => 'Most Liked',
    'search' => 'Search',
    'search_placeholder' => 'Search for products or stores...',
    
    // User Account
    'account' => 'My Account',
    'profile' => 'Profile',
    'orders' => 'Orders',
    'wishlist' => 'Wishlist',
    'addresses' => 'Addresses',
    'settings' => 'Settings',
    'login' => 'Login',
    'register' => 'Register',
    'logout' => 'Logout',
    'welcome_user' => 'Welcome, %s',
    
    // Product
    'product_details' => 'Product Details',
    'price' => 'Price',
    'quantity' => 'Quantity',
    'add_to_cart' => 'Add to Cart',
    'add_to_wishlist' => 'Add to Wishlist',
    'remove_from_wishlist' => 'Remove from Wishlist',
    'out_of_stock' => 'Out of Stock',
    'description' => 'Description',
    'specifications' => 'Specifications',
    'reviews' => 'Reviews',
    'related_products' => 'Related Products',
    'view_details' => 'View Details',
    
    // Cart
    'cart' => 'Cart',
    'your_cart' => 'Your Cart',
    'cart_empty' => 'Your cart is empty',
    'continue_shopping' => 'Continue Shopping',
    'proceed_to_checkout' => 'Proceed to Checkout',
    'remove' => 'Remove',
    'update' => 'Update',
    'subtotal' => 'Subtotal',
    'total' => 'Total',
    
    // Checkout
    'checkout' => 'Checkout',
    'shipping_info' => 'Shipping Information',
    'payment_info' => 'Payment Information',
    'order_summary' => 'Order Summary',
    'shipping_address' => 'Shipping Address',
    'payment_method' => 'Payment Method',
    'cash_on_delivery' => 'Cash on Delivery',
    'credit_card' => 'Credit Card',
    'bank_transfer' => 'Bank Transfer',
    'place_order' => 'Place Order',
    
    // Address
    'add_new_address' => 'Add New Address',
    'edit_address' => 'Edit Address',
    'delete_address' => 'Delete Address',
    'address_title' => 'Address Title',
    'recipient_name' => 'Recipient Name',
    'phone' => 'Phone',
    'city' => 'City',
    'area' => 'Area',
    'street' => 'Street',
    'building' => 'Building',
    'apartment' => 'Apartment',
    'notes' => 'Notes',
    'set_as_default' => 'Set as Default',
    
    // Orders
    'order_id' => 'Order ID',
    'order_date' => 'Order Date',
    'order_status' => 'Order Status',
    'order_total' => 'Order Total',
    'order_details' => 'Order Details',
    'track_order' => 'Track Order',
    'cancel_order' => 'Cancel Order',
    'pending' => 'Pending',
    'processing' => 'Processing',
    'shipped' => 'Shipped',
    'delivered' => 'Delivered',
    'cancelled' => 'Cancelled',
    
    // Reviews
    'write_review' => 'Write a Review',
    'your_rating' => 'Your Rating',
    'your_review' => 'Your Review',
    'submit_review' => 'Submit Review',
    'no_reviews' => 'No reviews yet',
    
    // Buttons
    'submit' => 'Submit',
    'save' => 'Save',
    'cancel' => 'Cancel',
    'edit' => 'Edit',
    'delete' => 'Delete',
    'back' => 'Back',
    'next' => 'Next',
    'previous' => 'Previous',
    
    // Messages
    'success' => 'Success!',
    'error' => 'Error!',
    'warning' => 'Warning!',
    'info' => 'Information',
    'confirm_delete' => 'Are you sure you want to delete this?',
    'item_added' => 'Item added successfully',
    'item_removed' => 'Item removed successfully',
    'item_updated' => 'Item updated successfully',
    'login_required' => 'Please login to continue',
    'registration_success' => 'Registration successful! You can now login.',
    
    // Footer
    'about_us' => 'About Us',
    'contact_us' => 'Contact Us',
    'faq' => 'FAQ',
    'terms' => 'Terms & Conditions',
    'privacy' => 'Privacy Policy',
    'copyright' => '© 2023 E-Marketplace. All rights reserved.',
    
    // Store
    'store_details' => 'Store Details',
    'store_products' => 'Store Products',
    'contact_store' => 'Contact Store',
    'follow_store' => 'Follow Store',
    'store_info' => 'Store Information',
    'store_location' => 'Store Location',
    'store_hours' => 'Store Hours',
    'store_contact' => 'Store Contact',
    'store_social' => 'Social Media',
];

// Function to get translation
function __($key, $params = []) {
    global $translations;
    
    if (isset($translations[$key])) {
        $text = $translations[$key];
        
        // Replace parameters if any
        if (!empty($params)) {
            foreach ($params as $param) {
                $text = preg_replace('/%s/', $param, $text, 1);
            }
        }
        
        return $text;
    }
    
    // Return the key if translation not found
    return $key;
}
?>
