# English Marketplace

This is the English version of the Marketplace e-commerce platform. This version maintains the same functionality as the Arabic version but with an English interface and Left-to-Right (LTR) layout.

## Project Structure

The project follows a standard PHP web application structure:

- `/customer` - Customer-facing pages and functionality
- `/admin` - Admin dashboard and management tools
- `/vendor` - Vendor/store management interface
- `/includes` - Core PHP includes (configuration, functions, etc.)
- `/uploads` - Directory for uploaded files (products, store logos, etc.)
- `/assets` - Static assets (CSS, JS, images)

## Setup Instructions

### Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- WAMP/XAMPP/MAMP for local development

### Database Setup

1. Create a new database named `marketplace_en` in your MySQL server
2. Import the database structure using the `setup_database.php` script:
   - Navigate to `http://localhost/marketplace_en/setup_database.php` in your browser
   - The script will create all necessary tables and an admin user

### Configuration

The main configuration files are:

- `includes/config.php` - Database connection and site configuration
- `includes/init.php` - Application initialization
- `includes/translations.php` - English translations for the site

## Key Features

- Responsive design for mobile and desktop
- Customer account management
- Product browsing and searching
- Shopping cart and checkout
- Order tracking
- Wishlist functionality
- Store management for vendors
- Admin dashboard for site management

## Differences from Arabic Version

- Left-to-Right (LTR) layout instead of Right-to-Left (RTL)
- English translations for all interface elements
- Separate database for independent content management
- English-specific CSS adjustments in `customer/styles/english-style.css`

## Default Admin Login

- Email: admin@example.com
- Password: admin123

## Development Notes

- The site uses Bootstrap 5 for responsive layout
- jQuery is used for client-side functionality
- Custom CSS is organized by feature in the `customer/styles` directory
- The translation system uses the `__()` function defined in `includes/translations.php`

## Maintenance

To update the site:

1. Make changes to the appropriate files
2. Test thoroughly in a local environment
3. Deploy changes to the production server

## License

This project is proprietary and confidential. Unauthorized copying, distribution, or use is strictly prohibited.
