<?php
/**
 * ConfigWiz - PHP Version
 * Utility functions for ConfigWiz application
 */

// Category to icon mapping
$category_icons = [
    // Engineering/CAD focused icons
    'Appearance' => 'fa-fill-drip',  // Better represents appearance/surface properties
    'Drawing' => 'fa-drafting-compass',  // Perfect for CAD drawing
    'Plotting' => 'fa-print',
    'System' => 'fa-microchip',
    'Environment' => 'fa-puzzle-piece',  // Represents system components fitting together
    'Save' => 'fa-save',
    'Files' => 'fa-folder-open',  // Shows files being accessed
    'Display' => 'fa-desktop',
    'Features' => 'fa-vector-square',  // Better represents CAD features/geometry
    'Assembly' => 'fa-object-group',
    'Manufacturing' => 'fa-industry',
    'Modeling' => 'fa-cubes',  // Multiple 3D objects for modeling
    'Import' => 'fa-file-import',
    'Export' => 'fa-file-export',
    'Licensing' => 'fa-key',
    'Performance' => 'fa-tachometer-alt',
    'Search' => 'fa-search',
    'Units' => 'fa-ruler-combined',  // More detailed measuring tool
    
    // CAD/CAM specific categories
    'Additive Manufacturing' => 'fa-layer-group',  // Layered manufacturing
    'Application Programming Interfaces' => 'fa-code',
    'Assembly Process' => 'fa-tools',  // Tools for assembly
    'Casting & Mold Design' => 'fa-cube',  // 3D object representing a casting
    'Colors' => 'fa-palette',
    'Combination States' => 'fa-object-ungroup',  // Exploded view concept
    'Composite' => 'fa-layer-group',
    'Creo Simulate' => 'fa-chart-line',  // Engineering analysis
    'Data Exchange' => 'fa-exchange-alt',
    'Data Management' => 'fa-database',
    'Design Manager' => 'fa-sitemap',
    'Dimensions & Tolerances' => 'fa-ruler',
    'Education & Social Tools' => 'fa-graduation-cap',
    'Electromechanical' => 'fa-bolt',
    'File Storage & Retrieval' => 'fa-folder-open',
    'Freestyle Feature' => 'fa-draw-polygon',  // Free-form geometry
    'Generative' => 'fa-robot',  // Automated design generation
    'Hatch Patterns' => 'fa-grip',  // Pattern of dots/grips
    'Layers' => 'fa-clone',
    'Layout' => 'fa-table',  // Grid layout
    'Mechanism' => 'fa-cogs',
    'Miscellaneous' => 'fa-tools',  // Tools representing utilities
    'Model Display' => 'fa-cube',  // 3D model view
    'Notification Manager' => 'fa-bell',
    'Piping' => 'fa-project-diagram',
    'Printing & Plotting' => 'fa-print',
    'Reference control' => 'fa-crosshairs',  // Precision targeting/alignment
    'Sheetmetal' => 'fa-th-large',  // Grid pattern for compatibility
    'Sketcher' => 'fa-pencil-alt',  // Sketching tool
    'Style Feature' => 'fa-paint-roller',  // Applying style
    'Update Control' => 'fa-sync',
    'User Interface' => 'fa-sliders-h',  // Control panel concept
    'Weld' => 'fa-bolt',  // Changed to standard bolt icon
    'Without Category' => 'fa-question',
    
    // Default icon for any category without a specific mapping
    'default' => 'fa-cog'
];

/**
 * Get available Creo versions from CSV files
 * 
 * @return array Available Creo versions
 */
function get_available_versions() {
    $versions = [];
    
    if ($handle = opendir('configs')) {
        while (false !== ($file = readdir($handle))) {
            if (preg_match('/^creo(\d+)_configs\.csv$/', $file, $matches)) {
                $version_num = $matches[1];
                
                // Read the first line of the file to get the column headers
                $f = fopen('configs/' . $file, 'r');
                if ($f) {
                    $header = fgets($f);
                    fclose($f);
                    
                    // Check if the file has the expected structure by looking for common column names
                    if (strpos($header, 'Category') !== false && 
                        strpos($header, 'Name') !== false && 
                        strpos($header, 'Description') !== false) {
                        
                        // Get line count as a rough estimation of parameter count
                        $line_count = 0;
                        $f = fopen('configs/' . $file, 'r');
                        if ($f) {
                            while (!feof($f)) {
                                fgets($f);
                                $line_count++;
                            }
                            fclose($f);
                        }
                        
                        // Format version name (e.g., "Creo 11.0")
                        $versions[] = [
                            'version' => $version_num,
                            'name' => "Creo " . $version_num . ".0",
                            'file' => $file,
                            'param_count' => max(0, $line_count - 1) // Subtract header row
                        ];
                    }
                }
            }
        }
        closedir($handle);
    }
    
    // Sort versions in descending order
    usort($versions, function($a, $b) {
        return intval($b['version']) - intval($a['version']);
    });
    
    return $versions;
}

/**
 * Load parameter metadata from CSV file
 * 
 * @return array Parameter metadata
 */
function load_parameter_metadata() {
    $metadata_file = "configs/parameter_metadata.csv";
    
    // Check if file exists
    if (!file_exists($metadata_file)) {
        return [];
    }
    
    // Read CSV data
    $metadata = [];
    $header = null;
    
    // Helper function to clean descriptions
    $clean_description = function($str) {
        $str = trim($str, '"');  // Remove quotes
        $str = trim($str);       // Remove leading/trailing spaces
        $str = preg_replace('/[.!?]+\s*$/', '', $str);  // Remove trailing punctuation
        return $str;
    };
    
    if (($handle = fopen($metadata_file, "r")) !== false) {
        while (($row = fgetcsv($handle, 1000, ",")) !== false) {
            if ($header === null) {
                // Trim whitespace from header names
                $header = array_map('trim', $row);
                continue;
            }
            
            // Create associative array for each row
            $rowData = [];
            foreach ($header as $i => $key) {
                $value = $row[$i] ?? '';
                if ($key === 'AdditionalDescription') {
                    $value = $clean_description($value);
                }
                $rowData[$key] = $value;
            }
            
            // Store data by parameter name for quick lookup
            // Only store the first occurrence of each parameter
            if (isset($rowData['Name']) && !isset($metadata[$rowData['Name']])) {
                $metadata[$rowData['Name']] = $rowData;
            }
        }
        fclose($handle);
    }
    
    return $metadata;
}

/**
 * Load configuration data from CSV file
 * 
 * @param string|null $version Version number
 * @return array Configuration data
 */
function load_config_data($version = null) {
    // Use the version from session if not provided
    if ($version === null) {
        $version = $_SESSION['version'] ?? null;
        
        // If still no version, use the latest one
        if ($version === null) {
            $versions = get_available_versions();
            if (!empty($versions)) {
                $version = $versions[0]['version'];
            } else {
                return [];
            }
        }
    }
    
    // Determine the config file path
    $config_file = "configs/creo{$version}_configs.csv";
    
    // Check if file exists
    if (!file_exists($config_file)) {
        return [];
    }
    
    // Helper function to clean descriptions
    $clean_description = function($str) {
        $str = trim($str);
        $str = preg_replace('/[.!?]+\s*$/', '', $str);
        return $str;
    };
    
    // Read CSV data
    $data = [];
    $header = null;
    
    if (($handle = fopen($config_file, "r")) !== false) {
        while (($row = fgetcsv($handle, 1000, ",")) !== false) {
            if ($header === null) {
                // Trim whitespace from header names
                $header = array_map('trim', $row);
                continue;
            }
            
            // Create associative array for each row
            $rowData = [];
            foreach ($header as $i => $key) {
                $value = $row[$i] ?? '';
                if ($key === 'Description') {
                    $value = $clean_description($value);
                }
                $rowData[$key] = $value;
            }
            
            // Map the Value column to Options if it contains comma-separated values
            if (isset($rowData['Value']) && strpos($rowData['Value'], ',') !== false) {
                $rowData['Options'] = $rowData['Value'];
            }
            
            // Store data by parameter name for quick lookup
            $data[$rowData['Name']] = $rowData;
        }
        fclose($handle);
    }
    
    // Load and merge additional descriptions from parameter_metadata.csv
    $metadata = load_parameter_metadata();
    
    // Enhance descriptions with metadata
    foreach ($data as $param_name => &$param) {
        if (isset($metadata[$param_name]) && !empty($metadata[$param_name]['AdditionalDescription'])) {
            // Create an enhanced description by combining the original description with the additional description
            $original_desc = $param['Description'] ?? '';
            $additional_desc = $metadata[$param_name]['AdditionalDescription'];
            
            // Only add the additional description if it's not already part of the original description
            if (!empty($additional_desc) && strpos($original_desc, $additional_desc) === false) {
                $param['EnhancedDescription'] = $original_desc;
                if (!empty($original_desc)) {
                    $param['EnhancedDescription'] .= '. ' . $additional_desc;
                } else {
                    $param['EnhancedDescription'] = $additional_desc;
                }
            }
        }
    }
    
    return $data;
}

/**
 * Get user changes from session
 * 
 * @return array User changes
 */
function get_user_changes() {
    // Check if user has a session ID
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['user_id'] = uniqid();
    }
    
    $user_id = $_SESSION['user_id'];
    $session_file = "sessions/{$user_id}.json";
    
    // Check if session file exists
    if (file_exists($session_file)) {
        $content = file_get_contents($session_file);
        return json_decode($content, true) ?: [];
    }
    
    return [];
}

/**
 * Save user changes to session file
 * 
 * @param array $changes User changes
 * @return void
 */
function save_user_changes($changes) {
    // Check if user has a session ID
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['user_id'] = uniqid();
    }
    
    $user_id = $_SESSION['user_id'];
    $session_file = "sessions/{$user_id}.json";
    
    // Create sessions directory if it doesn't exist
    if (!file_exists('sessions')) {
        mkdir('sessions', 0755, true);
    }
    
    // Save changes to file
    file_put_contents($session_file, json_encode($changes, JSON_PRETTY_PRINT));
}

/**
 * Get all categories from configuration data
 * 
 * @return array Categories
 */
function get_categories() {
    $config_data = load_config_data();
    $categories = [];
    
    foreach ($config_data as $param) {
        if (!empty($param['Category']) && !in_array($param['Category'], $categories)) {
            $categories[] = $param['Category'];
        }
    }
    
    // Sort categories
    usort($categories, 'category_sort_key');
    
    return $categories;
}

/**
 * Sort key function for categories
 * 
 * @param string $category Category name
 * @return mixed Sort key
 */
function category_sort_key($category) {
    // Try to convert the category to an integer if it's a numeric string
    if (is_numeric($category)) {
        return intval($category);
    }
    
    // Special cases for categories that should appear at the beginning
    $special_prefixes = [
        'Assembly' => 'A',
        'Display' => 'B',
        'Drawing' => 'C',
        'Environment' => 'D',
        'Features' => 'E',
        'Files' => 'F',
        'Import' => 'G',
        'Export' => 'H',
        'Modeling' => 'I',
        'Plotting' => 'J',
        'Save' => 'K',
        'System' => 'L'
    ];
    
    foreach ($special_prefixes as $prefix => $order) {
        if (strpos($category, $prefix) === 0) {
            return $order . $category;
        }
    }
    
    // For all other categories, use the original string
    return 'Z' . $category;
}

/**
 * Get parameters by category
 * 
 * @param string $category Category name
 * @return array Parameters in the category
 */
function get_parameters_by_category($category) {
    $config_data = load_config_data();
    $parameters = [];
    
    foreach ($config_data as $param) {
        if ($param['Category'] === $category) {
            $parameters[] = $param;
        }
    }
    
    // Sort parameters by name
    usort($parameters, function($a, $b) {
        return strcmp($a['Name'], $b['Name']);
    });
    
    return $parameters;
}

/**
 * Search parameters by query
 * 
 * @param string $query Search query
 * @return array Search results
 */
function search_parameters($query) {
    if (empty($query)) {
        return [];
    }
    
    $config_data = load_config_data();
    $results = [];
    
    foreach ($config_data as $param) {
        // Skip empty or incomplete parameters
        if (empty($param['Name'])) {
            continue;
        }
        
        // Search in name and description
        if (safe_search($param, $query)) {
            $results[] = $param;
        }
    }
    
    // Sort results by relevance
    usort($results, function($a, $b) use ($query) {
        // Exact name match gets highest priority
        $a_exact = strtolower($a['Name']) === strtolower($query);
        $b_exact = strtolower($b['Name']) === strtolower($query);
        
        if ($a_exact && !$b_exact) return -1;
        if (!$a_exact && $b_exact) return 1;
        
        // Name contains query gets second priority
        $a_contains = stripos($a['Name'], $query) !== false;
        $b_contains = stripos($b['Name'], $query) !== false;
        
        if ($a_contains && !$b_contains) return -1;
        if (!$a_contains && $b_contains) return 1;
        
        // Fall back to alphabetical order
        return strcmp($a['Name'], $b['Name']);
    });
    
    return $results;
}

/**
 * Safe search function that handles special characters and regex patterns
 * 
 * @param array $row Data row
 * @param string $query Search query
 * @return bool Whether the row matches the query
 */
function safe_search($row, $query) {
    // Escape special regex characters in the query
    $escaped_query = preg_quote($query, '/');
    
    // Create the regex pattern
    $pattern = "/($escaped_query)/i";
    
    // Check name field
    if (!empty($row['Name']) && preg_match($pattern, $row['Name'])) {
        return true;
    }
    
    // Check description field
    if (!empty($row['Description']) && preg_match($pattern, $row['Description'])) {
        return true;
    }
    
    // No match found
    return false;
}

/**
 * Handle file upload and process the configuration file
 * 
 * @return array Result status and message
 */
function handle_file_upload() {
    // Check if a file was uploaded
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        return [
            'status' => 'error',
            'message' => 'No file uploaded or upload failed.'
        ];
    }
    
    // Check file type
    $file_info = pathinfo($_FILES['file']['name']);
    $extension = strtolower($file_info['extension'] ?? '');
    
    if (!in_array($extension, ['pro', 'txt', 'csv'])) {
        return [
            'status' => 'error',
            'message' => 'Invalid file type. Please upload a .pro, .txt, or .csv file.'
        ];
    }
    
    // Create uploads directory if it doesn't exist
    if (!file_exists('uploads')) {
        mkdir('uploads', 0755, true);
    }
    
    // Generate a unique filename
    $timestamp = date('YmdHis');
    $upload_file = "uploads/config_{$timestamp}.{$extension}";
    
    // Move the uploaded file
    if (!move_uploaded_file($_FILES['file']['tmp_name'], $upload_file)) {
        return [
            'status' => 'error',
            'message' => 'Failed to save the uploaded file.'
        ];
    }
    
    // Process the file
    $result = process_config_file($upload_file);
    $parameters = $result['parameters'];
    $stats = $result['stats'];
    
    // Get existing user changes
    $user_changes = get_user_changes();
    
    // Add uploaded parameters to user changes
    foreach ($parameters as $param) {
        $name = $param['name'];
        $user_changes[$name] = [
            'name' => $name,
            'value' => $param['value'],
            'category' => 'System',
            'description' => $param['description'] ?? '',
            'default_value' => $param['default_value'] ?? ''
        ];
    }
    
    // Save changes back to session
    save_user_changes($user_changes);
    
    // Generate a detailed import report
    $message = "Successfully imported {$stats['valid']} valid parameter(s). ";
    
    if ($stats['defaults_removed'] > 0) {
        $message .= "{$stats['defaults_removed']} parameter(s) that match default values were removed as they are unnecessary. ";
    }
    
    if ($stats['comments_skipped'] > 0) {
        $message .= "Skipped {$stats['comments_skipped']} comment lines.";
    }
    
    // Create flash messages for additional details
    $_SESSION['import_stats'] = [
        'invalid_parameters' => $stats['invalid'],
        'invalid_count' => $stats['invalid_count'],
        'mapkeys' => $stats['mapkeys'],
        'debug_info' => $stats['debug_info']
    ];
    
    // Return success with redirect to summary page
    return [
        'status' => 'success',
        'message' => $message,
        'redirect' => 'summary' // Indicate to redirect to summary page
    ];
}

/**
 * Process a config.pro file and extract parameters
 * 
 * @param string $file_path Path to the config file
 * @return array Processed results including parameters and statistics
 */
function process_config_file($file_path) {
    // Read the file content
    $content = file_get_contents($file_path);
    $lines = explode("\n", $content);
    
    // Get the current version's valid parameters
    $version = $_SESSION['version'] ?? null;
    if (!$version) {
        return [
            'parameters' => [],
            'stats' => [
                'valid' => 0,
                'defaults_removed' => 0,
                'comments_skipped' => 0,
                'invalid' => [],
                'invalid_count' => 0,
                'mapkeys' => 0,
                'debug_info' => []
            ]
        ];
    }
    
    // Load valid parameters for this version
    $valid_parameters = load_config_data($version);
    
    $parameters = [];
    $invalid_parameters = [];
    $default_value_matches = [];
    $comments_count = 0;
    $mapkeys_count = 0;
    $debug_info = [
        'attempted_params' => [],
        'version' => $version,
        'valid_param_count' => count($valid_parameters),
        'equals_format_count' => 0,
        'space_format_count' => 0
    ];
    
    // Cache a lowercase version of parameter names for case-insensitive matching
    $valid_parameter_keys_lower = array_map('strtolower', array_keys($valid_parameters));
    $valid_parameters_by_lowercase = [];
    foreach ($valid_parameters as $key => $value) {
        $valid_parameters_by_lowercase[strtolower($key)] = [
            'original_key' => $key,
            'data' => $value
        ];
    }
    
    foreach ($lines as $line_number => $line) {
        $line = trim($line);
        
        // Skip empty lines
        if (empty($line)) {
            continue;
        }
        
        // Skip comment lines and count them
        if (strpos($line, '!') === 0 || strpos($line, '#') === 0) {
            // Check if it's a mapkey
            if (strpos($line, '!mapkey') === 0 || preg_match('/^!.*\s+mapkey\s+/i', $line)) {
                $mapkeys_count++;
            } else {
                $comments_count++;
            }
            continue;
        }
        
        // Skip multi-line comments
        if (strpos($line, '/*') === 0 || strpos($line, '*/') !== false) {
            $comments_count++;
            continue;
        }
        
        // Process parameter line - support both "name = value" and "name value" formats
        // Primary pattern for space-separated format, falls back to equals sign format
        if (preg_match('/^([a-zA-Z0-9_\-\.]+)(?:\s+([^=].+)|(?:\s*=\s*)(.+))$/', $line, $matches)) {
            $name = trim($matches[1]);
            $value = trim($matches[2] ?? $matches[3]); // Use space-separated value if available, otherwise use equals format
            
            // Track format type for debugging
            if (isset($matches[3])) {
                $debug_info['equals_format_count']++;
            } else {
                $debug_info['space_format_count']++;
            }
            
            // Clean up the value by removing trailing comments
            if (($pos = strpos($value, '!')) !== false) {
                $value = trim(substr($value, 0, $pos));
            }
            
            if (($pos = strpos($value, '#')) !== false) {
                $value = trim(substr($value, 0, $pos));
            }
            
            // Use case-insensitive parameter name matching
            $name_lower = strtolower($name);
            $is_valid = in_array($name_lower, $valid_parameter_keys_lower);
            
            // Add to debug info
            $debug_info['attempted_params'][] = [
                'line' => $line_number + 1,
                'name' => $name,
                'value' => $value,
                'is_valid' => $is_valid
            ];
            
            if (!$is_valid) {
                $invalid_parameters[] = $name;
                continue;
            }
            
            // Get the original parameter name and data
            $original_param = $valid_parameters_by_lowercase[$name_lower];
            $original_name = $original_param['original_key'];
            $param_data = $original_param['data'];
            
            // Check if this matches the default value
            $default_value = $param_data['Default Value'] ?? '';
            
            // Convert to lowercase for case-insensitive comparison when the value is yes/no
            $normalized_value = strtolower($value);
            $normalized_default = strtolower($default_value);
            
            // Compare values (case-insensitive for yes/no)
            if (($normalized_value === 'yes' || $normalized_value === 'no') && 
                ($normalized_value === $normalized_default)) {
                // Skip parameters that match their default value
                $default_value_matches[] = $name;
                continue;
            }
            
            // Parameter is valid - add to parameters array
            $parameters[] = [
                'name' => $original_name, // Use the original case from the config file
                'value' => $value,
                'description' => $param_data['Description'] ?? '',
                'default_value' => $default_value
            ];
        }
    }
    
    // Return processed parameters and statistics
    return [
        'parameters' => $parameters,
        'stats' => [
            'valid' => count($parameters),
            'defaults_removed' => count($default_value_matches),
            'comments_skipped' => $comments_count,
            'invalid' => array_unique($invalid_parameters),
            'invalid_count' => count($invalid_parameters),
            'mapkeys' => $mapkeys_count,
            'debug_info' => $debug_info
        ]
    ];
}

/**
 * Generate a config.pro file from user changes
 * 
 * @param array $changes User changes
 * @param bool $include_comments Whether to include comments
 * @return string Generated config.pro content
 */
function generate_config_file($changes, $include_comments = false) {
    $content = "! ConfigWiz Configuration File\n";
    $content .= "! DISCLAIMER: Use at your own risk. Review configurations carefully.\n";
    $content .= "! The creators of ConfigWiz take no responsibility for issues arising from these settings.\n";
    $content .= "! Created for Creo " . ($_SESSION['version'] ?? 'Unknown') . "\n";
    $content .= "! Generated on " . date('Y-m-d H:i:s') . "\n";
    $content .= "!\n";
    
    // Group parameters by category
    $categories = [];
    foreach ($changes as $param) {
        $category = $param['category'] ?? 'Uncategorized';
        if (!isset($categories[$category])) {
            $categories[$category] = [];
        }
        $categories[$category][] = $param;
    }
    
    // Sort categories
    ksort($categories);
    
    // Generate content for each category
    foreach ($categories as $category => $params) {
        $content .= "! ===== {$category} =====\n";
        
        // Sort parameters by name
        usort($params, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });
        
        foreach ($params as $param) {
            // Add description as comment if requested
            if ($include_comments && !empty($param['description'])) {
                // Wrap description to ~80 chars per line
                $desc_lines = wordwrap($param['description'], 78, "\n", true);
                $desc_lines = explode("\n", $desc_lines);
                
                foreach ($desc_lines as $desc_line) {
                    $content .= "! {$desc_line}\n";
                }
            }
            
            // Add parameter line
            $content .= "{$param['name']} = {$param['value']}\n";
            
            // Add a newline for readability
            $content .= "\n";
        }
    }
    
    return $content;
}

/**
 * Get parameters by custom category
 * 
 * @param string $category_name Custom category name
 * @param string|null $version Version number
 * @return array Parameters in the custom category
 */
function get_parameters_by_custom_category($category_name, $version = null) {
    // Load metadata and config data
    $metadata = load_parameter_metadata();
    $config_data = load_config_data($version);
    
    // Set of parameters in this custom category
    $parameters = [];
    
    // Helper function to clean and join descriptions
    $join_descriptions = function($original, $additional) {
        // Clean up descriptions by removing trailing punctuation and spaces
        $clean = function($str) {
            return trim(preg_replace('/[.!?]+\s*$/', '', trim($str ?? '')));
        };
        
        // Clean both descriptions
        $original = $clean($original);
        $additional = $clean($additional);
        
        // If either part is empty, just return the non-empty one with a period
        if (empty($original)) return $additional . '.';
        if (empty($additional)) return $original . '.';
        
        // Join the descriptions with proper punctuation
        // Remove any trailing periods from the first part to avoid double periods
        $original = rtrim($original, '. ');
        return $original . '. ' . $additional . '.';
    };
    
    // First check metadata for parameters in this category
    foreach ($metadata as $param_name => $meta) {
        if (!empty($meta['Categories']) && strcasecmp($meta['Categories'], $category_name) === 0) {
            // Only include if parameter exists in config data
            if (isset($config_data[$param_name])) {
                $param_data = $config_data[$param_name];
                $param_data['Category'] = $category_name;
                
                // Append the metadata description if available
                if (!empty($meta['AdditionalDescription'])) {
                    $param_data['Description'] = $join_descriptions(
                        $param_data['Description'],
                        $meta['AdditionalDescription']
                    );
                } else {
                    // Ensure the description ends with a period
                    $desc = trim($param_data['Description'] ?? '');
                    if (!empty($desc) && !preg_match('/[.!?]$/', $desc)) {
                        $param_data['Description'] = $desc . '.';
                    }
                }
                
                $parameters[$param_name] = $param_data;
            }
        }
    }
    
    // Then check config data for any additional parameters in this category
    foreach ($config_data as $param_name => $param_data) {
        if (strcasecmp($param_data['Category'] ?? '', $category_name) === 0) {
            if (!isset($parameters[$param_name])) {
                $param_data['Category'] = $category_name;
                
                // Append the metadata description if available
                if (isset($metadata[$param_name]) && !empty($metadata[$param_name]['AdditionalDescription'])) {
                    $param_data['Description'] = $join_descriptions(
                        $param_data['Description'],
                        $metadata[$param_name]['AdditionalDescription']
                    );
                } else {
                    // Ensure the description ends with a period
                    $desc = trim($param_data['Description'] ?? '');
                    if (!empty($desc) && !preg_match('/[.!?]$/', $desc)) {
                        $param_data['Description'] = $desc . '.';
                    }
                }
                
                $parameters[$param_name] = $param_data;
            }
        }
    }
    
    // Sort parameters by name
    $parameters = array_values($parameters);
    usort($parameters, function($a, $b) {
        return strcmp($a['Name'], $b['Name']);
    });
    
    return $parameters;
}

/**
 * Get enhanced description for a parameter
 * 
 * @param string $param_name Parameter name
 * @param string $original_description Original description
 * @return string Enhanced description
 */
function get_enhanced_description($param_name, $original_description) {
    $metadata = load_parameter_metadata();
    
    if (isset($metadata[$param_name]) && !empty($metadata[$param_name]['EnhancedDescription'])) {
        return $metadata[$param_name]['EnhancedDescription'];
    }
    
    return $original_description;
}

/**
 * Get available custom categories
 * 
 * @param string|null $version Version number
 * @return array Available custom categories
 */
function get_available_custom_categories($version = null) {
    // Load metadata
    $metadata = load_parameter_metadata();
    
    // Load config data for the selected version
    $config_data = load_config_data($version);
    
    // Set of custom categories
    $categories = [];
    $valid_categories = [];
    
    // Extract custom categories from metadata
    foreach ($metadata as $param_name => $param) {
        if (!empty($param['Categories']) && !in_array($param['Categories'], $categories)) {
            $categories[] = $param['Categories'];
        }
    }
    
    // Filter categories to only include those with parameters valid in this version
    foreach ($categories as $category) {
        $has_valid_param = false;
        
        // Check if this category has at least one parameter available in the config data
        foreach ($metadata as $param_name => $param) {
            if (!empty($param['Categories']) && $param['Categories'] === $category) {
                // If this parameter exists in the config data for this version, the category is valid
                if (isset($config_data[$param_name])) {
                    $has_valid_param = true;
                    break;
                }
            }
        }
        
        // Only add categories with at least one valid parameter
        if ($has_valid_param) {
            $valid_categories[] = $category;
        }
    }
    
    // Sort categories
    usort($valid_categories, 'category_sort_key');
    
    return $valid_categories;
}

/**
 * Get appropriate icon for a category
 * 
 * This function handles variations in category names to find the correct icon.
 * For example, "3D Printing" should use the icon for "Additive Manufacturing"
 * 
 * @param string $category The category name to find an icon for
 * @return string The FontAwesome icon class
 */
function get_category_icon($category) {
    global $category_icons;
    
    // Direct match first
    if (isset($category_icons[$category])) {
        return $category_icons[$category];
    }
    
    // Known translations for categories
    $category_translations = [
        '3D Printing' => 'Additive Manufacturing',
        // Add other translations as needed
    ];
    
    // Check if we have a translation for this category
    if (isset($category_translations[$category]) && 
        isset($category_icons[$category_translations[$category]])) {
        return $category_icons[$category_translations[$category]];
    }
    
    // Use the default icon from the array if it exists, otherwise fallback to fa-cog
    return $category_icons['default'] ?? 'fa-cog';
} 