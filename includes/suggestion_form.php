<?php
// Display flash message if it exists
if (isset($_SESSION['flash_message'])) {
    $flash_type = $_SESSION['flash_message']['type'];
    $flash_message = $_SESSION['flash_message']['message'];
    
    // Display the flash message
    echo "<div class='alert alert-{$flash_type} alert-dismissible fade show' role='alert' style='position: fixed; top: 20px; right: 50%; transform: translateX(50%); z-index: 9999; width: 80%; max-width: 500px;'>";
    echo $flash_message;
    echo "<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>";
    echo "</div>";
    
    // Remove the flash message after displaying it
    unset($_SESSION['flash_message']);
}

// Check if user has submitted a suggestion
$suggestion_submitted = false;
$suggestion_error = '';

// Process suggestion form when submitted
if (isset($_POST['submit_suggestion'])) {
    $name = trim($_POST['suggestion_name']);
    $email = trim($_POST['suggestion_email']);
    $suggestion_text = trim($_POST['suggestion_text']);
    $customer_id = isset($_SESSION['customer_id']) ? $_SESSION['customer_id'] : null;
    
    // Validate data
    if (empty($name)) {
        $suggestion_error = 'Please enter your name';
    } elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $suggestion_error = 'Please enter a valid email address';
    } elseif (empty($suggestion_text)) {
        $suggestion_error = 'Please enter your suggestion';
    } else {
        // Insert suggestion into database
        $stmt = $conn->prepare("INSERT INTO suggestions (customer_id, name, email, suggestion_text) VALUES (?, ?, ?, ?)");
        
        // Check if preparation was successful
        if ($stmt) {
            $stmt->bind_param("isss", $customer_id, $name, $email, $suggestion_text);
            
            if ($stmt->execute()) {
                $suggestion_submitted = true;
                
                // Add flash message
                $_SESSION['flash_message'] = array(
                    'type' => 'success',
                    'message' => 'Your suggestion has been submitted successfully! Thank you for contributing to improve our services.'
                );
                
                // Redirect user to the same page
                header('Location: ' . $_SERVER['REQUEST_URI']);
                exit();
            } else {
                $suggestion_error = 'An error occurred while submitting your suggestion: ' . $stmt->error;
            }
        } else {
            // In case preparation fails
            $suggestion_error = 'A system error occurred: ' . $conn->error;
        }
    }
}
?>

<!-- Small Suggestion Popup -->
<div class="suggestion-popup" id="suggestionPopup">
    <div class="suggestion-popup-header">
        <h5>Have a Suggestion?</h5>
        <button type="button" class="suggestion-popup-close" onclick="closeSuggestionPopup()">&times;</button>
    </div>
    <div class="suggestion-popup-body">
        <p>We welcome your suggestions to improve our services</p>
        <button type="button" class="btn btn-orange btn-sm" data-bs-toggle="modal" data-bs-target="#suggestionModal" onclick="closeSuggestionPopup()">
            Submit Suggestion
        </button>
    </div>
</div>

<!-- Suggestion form modal -->
<div class="modal fade" id="suggestionModal" tabindex="-1" aria-labelledby="suggestionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="suggestionModalLabel">Submit a Suggestion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php if ($suggestion_submitted): ?>
                    <div class="alert alert-success">
                        Your suggestion has been submitted successfully! Thank you for contributing to improve our services.
                    </div>
                    <script>
                        // Reset form after 3 seconds
                        setTimeout(function() {
                            document.getElementById('suggestionForm').reset();
                            document.querySelector('.alert-success').style.display = 'none';
                        }, 3000);
                    </script>
                <?php elseif (!empty($suggestion_error)): ?>
                    <div class="alert alert-danger">
                        <?php echo $suggestion_error; ?>
                    </div>
                <?php endif; ?>
                
                <form id="suggestionForm" method="POST" action="">
                    <div class="mb-3">
                        <label for="suggestion_name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="suggestion_name" name="suggestion_name" required
                            value="<?php echo isset($_SESSION['customer_name']) ? $_SESSION['customer_name'] : ''; ?>">
                    </div>
                    <div class="mb-3">
                        <label for="suggestion_email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="suggestion_email" name="suggestion_email" required
                            value="<?php echo isset($_SESSION['customer_email']) ? $_SESSION['customer_email'] : ''; ?>">
                    </div>
                    <div class="mb-3">
                        <label for="suggestion_text" class="form-label">Suggestion</label>
                        <textarea class="form-control" id="suggestion_text" name="suggestion_text" rows="4" required></textarea>
                        <div class="form-text">Please provide your suggestion clearly. It will be reviewed by our team.</div>
                    </div>
                    <button type="submit" name="submit_suggestion" class="btn btn-orange">Submit Suggestion</button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.btn-orange {
    background-color: #ff7a00;
    border-color: #ff7a00;
    color: white;
}

.btn-orange:hover, .btn-orange:focus {
    background-color: #e56e00;
    border-color: #e56e00;
    color: white;
}

.suggestion-popup {
    position: fixed;
    bottom: 20px;
    right: 20px;
    width: 280px;
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    z-index: 1000;
    display: none;
    direction: ltr;
    text-align: left;
    overflow: hidden;
    animation: slideIn 0.3s ease-out;
}

.suggestion-popup-header {
    background-color: #ff7a00;
    color: white;
    padding: 10px 15px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.suggestion-popup-header h5 {
    margin: 0;
    font-size: 16px;
}

.suggestion-popup-close {
    background: none;
    border: none;
    color: white;
    font-size: 20px;
    cursor: pointer;
    padding: 0;
    line-height: 1;
}

.suggestion-popup-body {
    padding: 15px;
}

.suggestion-popup-body p {
    margin-bottom: 15px;
    font-size: 14px;
}

@keyframes slideIn {
    from {
        transform: translateY(100px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

@media (max-width: 768px) {
    .suggestion-popup {
        width: 250px;
        bottom: 15px;
        right: 15px;
    }
}
</style>

<script>
// Reset form when modal is closed
document.getElementById('suggestionModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('suggestionForm').reset();
    const alertElements = document.querySelectorAll('.alert');
    alertElements.forEach(element => {
        element.style.display = 'none';
    });
});

// Show suggestion popup after a delay
let suggestionPopupShown = localStorage.getItem('suggestionPopupShown');
let lastShownTime = localStorage.getItem('suggestionPopupLastShown');
const currentTime = new Date().getTime();
const oneDay = 24 * 60 * 60 * 1000; // 24 hours in milliseconds

function showSuggestionPopup() {
    const suggestionPopup = document.getElementById('suggestionPopup');
    if (suggestionPopup) {
        suggestionPopup.style.display = 'block';
    }
}

function closeSuggestionPopup() {
    const suggestionPopup = document.getElementById('suggestionPopup');
    if (suggestionPopup) {
        suggestionPopup.style.display = 'none';
        localStorage.setItem('suggestionPopupShown', 'true');
        localStorage.setItem('suggestionPopupLastShown', currentTime);
    }
}

// Show the popup after 10 seconds of page load on every page refresh
setTimeout(showSuggestionPopup, 10000); // 10 seconds
</script>
