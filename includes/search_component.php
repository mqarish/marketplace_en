<?php
/**
 * Unified search bar component
 * Used across all site pages to ensure consistent appearance and behavior
 * 
 * Parameters:
 * $form_action - Path to the page where the form will be submitted
 * $placeholder - Default text in the search field
 * $search_value - Current search value (if any)
 * $hidden_fields - Additional hidden fields (array name => value)
 * $show_type_selector - Show search type selector (products/stores)
 * $show_location - Show location button
 */

// Set default values
$form_action = $form_action ?? '';
$placeholder = $placeholder ?? 'Search for products or stores...';
$search_value = $search_value ?? '';
$hidden_fields = $hidden_fields ?? [];
$show_type_selector = $show_type_selector ?? false;
$show_location = $show_location ?? false;
$search_class = $search_class ?? 'search-form';
$input_class = $input_class ?? 'search-input';
$button_class = $button_class ?? 'round-btn';
?>

<div class="search-container">
    <form action="<?php echo htmlspecialchars($form_action); ?>" method="GET" class="<?php echo $search_class; ?>" id="search-form">
        <?php if ($show_type_selector): ?>
        <div class="search-type">
            <select name="view" class="search-select">
                <option value="products" <?php echo (isset($_GET['view']) && $_GET['view'] == 'stores') ? '' : 'selected'; ?>>Products</option>
                <option value="stores" <?php echo (isset($_GET['view']) && $_GET['view'] == 'stores') ? 'selected' : ''; ?>>Stores</option>
            </select>
            <i class="bi bi-chevron-down"></i>
        </div>
        <?php endif; ?>
        
        <div class="search-input-wrap">
            <input type="text" name="search" class="<?php echo $input_class; ?>" 
                   placeholder="<?php echo htmlspecialchars($placeholder); ?>" 
                   value="<?php echo htmlspecialchars($search_value); ?>" 
                   autocomplete="off">
        </div>
        
        <?php foreach ($hidden_fields as $name => $value): ?>
            <input type="hidden" name="<?php echo htmlspecialchars($name); ?>" value="<?php echo htmlspecialchars($value); ?>">
        <?php endforeach; ?>
        
        <?php if ($show_location): ?>
        <button type="button" class="<?php echo $button_class; ?> location-btn" onclick="getLocation()">
            <i class="bi bi-geo-alt"></i>
        </button>
        <?php else: ?>
        <button type="submit" class="<?php echo $button_class; ?>">
            <i class="bi bi-search"></i>
        </button>
        <?php endif; ?>
    </form>
</div>
