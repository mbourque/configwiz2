<?php
// Start session management
session_start();

// Include utility functions
require_once 'includes/functions.php';

// Create directories if they don't exist
if (!file_exists('sessions')) {
    mkdir('sessions', 0755, true);
}

// Route handling - Simple front controller pattern
$route = $_GET['route'] ?? 'home';

// Define routes
switch ($route) {
    case 'home':
        include 'templates/versions.php';
        break;

    case 'version':
        $version = $_GET['version'] ?? '';
        if (!empty($version)) {
            // Store the selected version in the session
            $_SESSION['version'] = $version;
            
            // Clear any existing user changes when selecting a new version
            if (isset($_SESSION['user_id'])) {
                $user_id = $_SESSION['user_id'];
                $session_file = "sessions/{$user_id}.json";
                
                // Delete the session file if it exists
                if (file_exists($session_file)) {
                    unlink($session_file);
                }
            }
            
            // Generate a new session ID to ensure a fresh start
            $_SESSION['user_id'] = uniqid();
            
            // Redirect to the index page
            header('Location: index.php?route=index');
            exit;
        } else {
            // Redirect back to the home page if no version was selected
            header('Location: index.php');
            exit;
        }
        break;

    case 'index':
        // Get available categories
        $categories = get_categories();
        $custom_categories = get_available_custom_categories();
        $user_changes = get_user_changes();
        $version = $_SESSION['version'] ?? '';
        
        // Include the index template
        include 'templates/index.php';
        break;

    case 'configure':
        $category = $_GET['category'] ?? '';
        if (!empty($category)) {
            // Get parameters for the selected category
            $user_changes = get_user_changes();
            $parameters = get_parameters_by_category($category);
            
            // Include the configure template
            include 'templates/configure.php';
        } else {
            // Redirect back to the index page if no category was selected
            header('Location: index.php?route=index');
            exit;
        }
        break;

    case 'custom_category':
        $category = $_GET['category'] ?? '';
        if (!empty($category)) {
            // Get parameters for the selected custom category
            $user_changes = get_user_changes();
            $parameters = get_parameters_by_custom_category($category);
            
            // Check if the category has any parameters for this version
            if (empty($parameters)) {
                // Set an error flash message
                $_SESSION['flash_message'] = 'This custom category has no parameters available for the current Creo version.';
                $_SESSION['flash_category'] = 'error';
                
                // Redirect back to the index page
                header('Location: index.php?route=index');
                exit;
            }
            
            // Include the custom category template
            include 'templates/custom_category.php';
        } else {
            // Redirect back to the index page if no category was selected
            header('Location: index.php?route=index');
            exit;
        }
        break;

    case 'summary':
        // Get user's changes from session
        $user_changes = get_user_changes();
        
        // Get all categories for grouping changes
        $all_parameters = load_config_data();
        
        // Include the summary template
        include 'templates/summary.php';
        break;

    case 'view_config':
        // Get user's changes from session
        $user_changes = get_user_changes();
        
        // Check if there are any changes to view
        if (empty($user_changes)) {
            header('Content-Type: text/plain');
            echo "No changes to view.";
            exit;
        }
        
        // Generate the config.pro content
        $include_comments = isset($_GET['include_comments']) ? true : false;
        $config_content = generate_config_file($user_changes, $include_comments);
        
        // Return the content as plain text without any processing
        header('Content-Type: text/plain');
        echo $config_content;
        exit;
        break;

    case 'search':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $query = $_POST['query'] ?? '';
            $results = search_parameters($query);
            // Include the search results template
            include 'templates/search_results.php';
        } else {
            // Redirect back to the index page if not a POST request
            header('Location: index.php?route=index');
            exit;
        }
        break;

    case 'api_search':
        $query = $_GET['query'] ?? '';
        $results = search_parameters($query);
        
        // Return JSON response
        header('Content-Type: application/json');
        echo json_encode($results);
        break;

    case 'save_change':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $param_name = $_POST['name'] ?? '';
            $param_value = $_POST['value'] ?? '';
            $param_category = $_POST['category'] ?? '';
            $param_description = $_POST['description'] ?? '';
            $param_default_value = $_POST['default_value'] ?? '';
            
            // Validate input
            if (empty($param_name) || empty($param_category)) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Missing required parameters']);
                exit;
            }
            
            // Get user's changes from session
            $user_changes = get_user_changes();
            
            // Update or add the parameter
            $user_changes[$param_name] = [
                'name' => $param_name,
                'value' => $param_value,
                'category' => $param_category,
                'description' => $param_description,
                'default_value' => $param_default_value
            ];
            
            // Save changes back to session
            save_user_changes($user_changes);
            
            // Return success response
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success']);
        } else {
            // Return error response for non-POST requests
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
        }
        break;

    case 'remove_change':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $param_name = $_POST['name'] ?? '';
            
            // Validate input
            if (empty($param_name)) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Missing parameter name']);
                exit;
            }
            
            // Get user's changes from session
            $user_changes = get_user_changes();
            
            // Remove the parameter if it exists
            if (isset($user_changes[$param_name])) {
                unset($user_changes[$param_name]);
                
                // Save changes back to session
                save_user_changes($user_changes);
                
                // Return success response
                header('Content-Type: application/json');
                echo json_encode(['status' => 'success']);
            } else {
                // Return error if parameter doesn't exist
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Parameter not found']);
            }
        } else {
            // Return error response for non-POST requests
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
        }
        break;

    case 'upload':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = handle_file_upload();
            
            // Redirect based on result
            if ($result['status'] === 'success') {
                // Set a success flash message
                $_SESSION['flash_message'] = $result['message'];
                $_SESSION['flash_category'] = 'success';
                
                // Redirect to summary page if specified
                if (isset($result['redirect']) && $result['redirect'] === 'summary') {
                    header('Location: index.php?route=summary');
                } else {
                    header('Location: index.php?route=index');
                }
                exit;
            } else {
                // Set an error flash message
                $_SESSION['flash_message'] = $result['message'];
                $_SESSION['flash_category'] = 'error';
                
                // Redirect back to the index page
                header('Location: index.php?route=index');
                exit;
            }
        } else {
            // Redirect back to the index page if not a POST request
            header('Location: index.php?route=index');
            exit;
        }
        break;

    case 'download':
        // Get user's changes from session
        $user_changes = get_user_changes();
        
        // Check if there are any changes to download
        if (empty($user_changes)) {
            // Set an error flash message
            $_SESSION['flash_message'] = 'No changes to download.';
            $_SESSION['flash_category'] = 'error';
            
            // Redirect back to the index page
            header('Location: index.php?route=index');
            exit;
        }
        
        // Generate the config.pro file
        $include_comments = isset($_GET['include_comments']) ? true : false;
        $config_content = generate_config_file($user_changes, $include_comments);
        
        // Set headers for file download
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="config.pro"');
        header('Content-Length: ' . strlen($config_content));
        
        // Output the file content
        echo $config_content;
        break;

    case 'get_changes':
        // Get user's changes from session
        $user_changes = get_user_changes();
        
        // Return JSON response
        header('Content-Type: application/json');
        echo json_encode($user_changes);
        break;

    case 'clear_changes':
        // Clear user's changes from session
        $user_id = $_SESSION['user_id'] ?? '';
        
        if (!empty($user_id)) {
            $session_file = "sessions/{$user_id}.json";
            
            // Delete the session file if it exists
            if (file_exists($session_file)) {
                unlink($session_file);
            }
        }
        
        // Redirect back to the index page
        header('Location: index.php?route=index');
        exit;
        break;

    default:
        // Handle 404 - page not found
        echo "<h1>404 - Page Not Found</h1>";
        echo "<p>The requested page does not exist.</p>";
        echo "<p><a href='index.php'>Return to Home</a></p>";
        break;
} 