<?php
/**
 * Database Structure Import Script
 * This script will import the structure (without data) from the original marketplace database
 * to the new marketplace_en database
 */

// Set error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Define database connection parameters for both databases
$source_db = [
    'host' => 'localhost',
    'user' => 'root',
    'pass' => '',
    'name' => 'marketplace'
];

$target_db = [
    'host' => 'localhost',
    'user' => 'root',
    'pass' => '',
    'name' => 'marketplace_en'
];

// Function to execute SQL query with error handling
function executeQuery($conn, $sql, $message) {
    if ($conn->query($sql) === TRUE) {
        return ['success' => true, 'message' => "<p style='color:green;'>✓ $message</p>"];
    } else {
        return ['success' => false, 'message' => "<p style='color:red;'>✗ Error: " . $conn->error . "</p>"];
    }
}

// Start HTML output
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Database Structure - English Marketplace</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        h1, h2 {
            color: #333;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        .container {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .success {
            color: green;
            font-weight: bold;
        }
        .error {
            color: red;
            font-weight: bold;
        }
        .progress {
            margin: 20px 0;
            padding: 10px;
            background: #f0f0f0;
            border-left: 4px solid #333;
        }
        .btn {
            display: inline-block;
            background: #333;
            color: #fff;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .btn:hover {
            background: #555;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Import Database Structure from Arabic to English Marketplace</h1>
        
        <?php
        // Process form submission
        $results = [];
        $tables_imported = 0;
        $total_tables = 0;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import'])) {
            // Connect to source database
            $source_conn = new mysqli($source_db['host'], $source_db['user'], $source_db['pass'], $source_db['name']);
            if ($source_conn->connect_error) {
                echo "<p class='error'>Connection to source database failed: " . $source_conn->connect_error . "</p>";
                exit;
            }
            
            // Connect to target database
            $target_conn = new mysqli($target_db['host'], $target_db['user'], $target_db['pass'], $target_db['name']);
            if ($target_conn->connect_error) {
                echo "<p class='error'>Connection to target database failed: " . $target_conn->connect_error . "</p>";
                exit;
            }
            
            // Get all tables from source database
            $tables_result = $source_conn->query("SHOW TABLES");
            if (!$tables_result) {
                echo "<p class='error'>Error fetching tables: " . $source_conn->error . "</p>";
                exit;
            }
            
            $tables = [];
            while ($row = $tables_result->fetch_row()) {
                $tables[] = $row[0];
            }
            
            $total_tables = count($tables);
            
            // Process each table
            foreach ($tables as $table) {
                // Get table creation SQL
                $create_table_result = $source_conn->query("SHOW CREATE TABLE `$table`");
                if (!$create_table_result) {
                    $results[] = [
                        'table' => $table,
                        'success' => false,
                        'message' => "Error getting CREATE TABLE statement: " . $source_conn->error
                    ];
                    continue;
                }
                
                $create_table_row = $create_table_result->fetch_row();
                $create_table_sql = $create_table_row[1];
                
                // Drop table if it exists in target database
                $drop_result = executeQuery($target_conn, "DROP TABLE IF EXISTS `$table`", "Dropped existing table: $table");
                
                // Create table in target database
                $create_result = executeQuery($target_conn, $create_table_sql, "Created table: $table");
                
                if ($create_result['success']) {
                    $tables_imported++;
                }
                
                $results[] = [
                    'table' => $table,
                    'success' => $create_result['success'],
                    'message' => $create_result['message']
                ];
            }
            
            // Close connections
            $source_conn->close();
            $target_conn->close();
            
            echo "<div class='progress'>";
            echo "<p class='success'>Import process completed!</p>";
            echo "<p>Successfully imported $tables_imported out of $total_tables tables.</p>";
            echo "</div>";
        }
        ?>
        
        <h2>Import Database Structure</h2>
        <p>This tool will import the database structure from the original Arabic marketplace database to the new English marketplace database.</p>
        <p><strong>Important:</strong> This will only import the table structure, not the data. Any existing tables in the target database will be dropped.</p>
        
        <h3>Source Database</h3>
        <ul>
            <li><strong>Database:</strong> <?php echo $source_db['name']; ?></li>
            <li><strong>Host:</strong> <?php echo $source_db['host']; ?></li>
        </ul>
        
        <h3>Target Database</h3>
        <ul>
            <li><strong>Database:</strong> <?php echo $target_db['name']; ?></li>
            <li><strong>Host:</strong> <?php echo $target_db['host']; ?></li>
        </ul>
        
        <form method="post" action="">
            <input type="hidden" name="import" value="1">
            <button type="submit" class="btn">Start Import Process</button>
        </form>
        
        <?php if (!empty($results)): ?>
            <h2>Import Results</h2>
            <table>
                <thead>
                    <tr>
                        <th>Table</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $result): ?>
                        <tr>
                            <td><?php echo $result['table']; ?></td>
                            <td><?php echo $result['message']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <p>
                <a href="setup_database.php" class="btn">Go to Database Setup</a>
                <a href="index.php" class="btn">Go to Homepage</a>
            </p>
        <?php endif; ?>
    </div>
</body>
</html>
