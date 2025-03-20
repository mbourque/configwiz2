<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/configwiz/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="/configwiz/favicon.ico" type="image/x-icon">
    <title>ConfigWiz - Configuration Summary</title>
    <?php include_once 'includes/analytics.php'; ?>
    <link rel="stylesheet" href="static/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <header>
        <a href="#" onclick="navigateHome(event)" class="header-title">
            <h1>ConfigWiz</h1>
            <p>Creo Configuration Management Tool</p>
        </a>
        <?php if (isset($_SESSION['user_id'])): ?>
        <div class="session-info">
            <i class="fa-solid fa-user"></i> Session: <?= substr($_SESSION['user_id'], 0, 8) ?>...
        </div>
        <?php endif; ?>
    </header>

    <!-- Persistent Search Bar -->
    <div class="persistent-search">
        <div class="search-wrapper">
            <div class="left-buttons">
                <a href="index.php?route=index" class="search-bar-button">
                    <i class="fa-solid fa-chevron-left"></i> Back
                </a>
            </div>
            <div class="search-container">
                <input type="text" id="global-search" placeholder="Search by name or description...">
                <div id="global-search-results"></div>
            </div>
            <div class="right-buttons">
                <!-- No specific button needed here -->
            </div>
        </div>
    </div>

    <div class="container summary-page">
        <?php if (isset($_SESSION['flash_message']) && isset($_SESSION['flash_category'])): ?>
            <div class="flash-messages">
                <div class="flash-message <?= $_SESSION['flash_category'] ?>">
                    <?= $_SESSION['flash_message'] ?>
                </div>
                
                <?php if (isset($_SESSION['import_stats']) && isset($_SESSION['import_stats']['invalid_parameters']) && is_array($_SESSION['import_stats']['invalid_parameters']) && !empty($_SESSION['import_stats']['invalid_parameters'])): ?>
                <div class="flash-message warning">
                    <strong>Warning:</strong> <?= $_SESSION['import_stats']['invalid_count'] ?> total invalid parameter entries were found and removed (<?= count($_SESSION['import_stats']['invalid_parameters']) ?> unique parameters):
                    <ul class="invalid-params-list">
                        <?php foreach ($_SESSION['import_stats']['invalid_parameters'] as $invalid_param): ?>
                        <li><?= htmlspecialchars($invalid_param) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['import_stats']) && isset($_SESSION['import_stats']['mapkeys']) && $_SESSION['import_stats']['mapkeys'] > 0): ?>
                <div class="flash-message warning">
                    <strong>Note:</strong> <?= $_SESSION['import_stats']['mapkeys'] ?> mapkey entries were removed. Mapkeys are not supported in this configuration manager.
                </div>
                <?php endif; ?>
            </div>
            <?php
            // Clear flash messages after displaying them
            unset($_SESSION['flash_message']);
            unset($_SESSION['flash_category']);
            unset($_SESSION['import_stats']);
            ?>
        <?php endif; ?>

        <div class="page-header">
            <h1>Configuration Changes</h1>
            <div class="toggle-container<?php if (empty($user_changes)): ?> disabled<?php endif; ?>">
                <input type="checkbox" id="show-descriptions" checked <?php if (empty($user_changes)): ?>disabled<?php endif; ?>>
                <label for="show-descriptions" class="toggle-label">Show Descriptions</label>
            </div>
        </div>

        <?php if (empty($user_changes)): ?>
        <div class="card">
            <div class="empty-state">
                <h2>No Changes</h2>
                <p>You haven't made any changes to the configuration yet.</p>
                <a href="index.php?route=index" class="btn primary">Return to Categories</a>
            </div>
        </div>
        <?php else: ?>
        <div class="card">
            <div class="summary-actions">
                <div class="download-options">
                    <a href="#" id="download-config-btn" class="btn primary" title="Download as config.pro">
                        <i class="fa-solid fa-download download-icon"></i> <span class="btn-text">Download</span>
                    </a>
                </div>
            </div>
            
            <?php
            // Instead of grouping by category, just gather all parameters
            $all_params = [];
            foreach ($user_changes as $param_name => $param) {
                // Get the original category from the config file, not a custom category
                $original_category = $param['category'] ?? 'Uncategorized';
                
                // If we have the parameter in all_parameters, use that category instead
                if (isset($all_parameters[$param_name]) && isset($all_parameters[$param_name]['Category'])) {
                    $original_category = $all_parameters[$param_name]['Category'];
                }
                
                // Store the original category with the parameter
                $param['original_category'] = $original_category;
                $all_params[] = $param;
            }
            
            // Sort all parameters by name
            usort($all_params, function($a, $b) {
                return strcmp($a['name'], $b['name']);
            });
            ?>
            
            <div class="summary-sections">
                <table class="summary-table">
                    <thead>
                        <tr>
                            <th class="summary-param">Option</th>
                            <th class="summary-value">New Value</th>
                            <th class="summary-actions"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_params as $param): ?>
                        <tr>
                            <td class="summary-param">
                                <div class="param-with-category">
                                    <a href="index.php?route=configure&category=<?= urlencode($param['original_category']) ?>" 
                                       class="param-link"
                                       onclick="localStorage.setItem('highlight_param', '<?= htmlspecialchars($param['name']) ?>'); return true;">
                                        <?= htmlspecialchars($param['name']) ?>
                                    </a>
                                </div>
                            </td>
                            <td class="summary-value">
                                <?= htmlspecialchars($param['value']) ?>
                            </td>
                            <td class="summary-actions">
                                <button class="btn-remove" data-param-name="<?= htmlspecialchars($param['name']) ?>" title="Remove this change" onclick="removeParameter('<?= htmlspecialchars($param['name']) ?>')">
                                    
                                </button>
                            </td>
                        </tr>
                        <?php 
                        // Add a row for the description (hidden by default)
                        $description = '';
                        if (isset($param['description']) && !empty($param['description'])) {
                            $description = $param['description'];
                        } else if (isset($all_parameters[$param['name']]['Description'])) {
                            $description = $all_parameters[$param['name']]['Description'];
                        }
                        
                        // Check for enhanced description from parameter_metadata.csv
                        if (isset($all_parameters[$param['name']]['EnhancedDescription'])) {
                            $description = $all_parameters[$param['name']]['EnhancedDescription'];
                        }
                        
                        if (!empty($description)): 
                        ?>
                        <tr class="param-description-row" style="display: none;">
                            <td colspan="3" class="param-description">
                                <?= htmlspecialchars($description) ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Import Statistics (if coming from import) -->
        <?php if (isset($_SESSION['import_stats'])): ?>
            <div class="card mt-4 mb-4">
                <div class="card-header">
                    <h5>Import Statistics</h5>
                </div>
                <div class="card-body">
                    <?php 
                    $import_stats = $_SESSION['import_stats'];
                    $debug_info = $import_stats['debug_info'] ?? [];
                    ?>
                    
                    <ul>
                        <?php if (isset($import_stats['invalid_count']) && $import_stats['invalid_count'] > 0): ?>
                            <li><strong><?= $import_stats['invalid_count'] ?></strong> invalid parameter(s) were skipped.</li>
                        <?php endif; ?>
                        
                        <?php if (isset($import_stats['mapkeys']) && $import_stats['mapkeys'] > 0): ?>
                            <li><strong><?= $import_stats['mapkeys'] ?></strong> mapkeys were detected but not imported.</li>
                        <?php endif; ?>
                        
                        <?php if (isset($debug_info['equals_format_count']) || isset($debug_info['space_format_count'])): ?>
                            <li>Parameter formats detected:
                                <ul>
                                    <li>Space-separated format (name value): <strong><?= $debug_info['space_format_count'] ?? 0 ?></strong></li>
                                    <li>Equals sign format (name = value): <strong><?= $debug_info['equals_format_count'] ?? 0 ?></strong></li>
                                </ul>
                            </li>
                        <?php endif; ?>
                    </ul>
                    
                    <?php if (!empty($import_stats['invalid'])): ?>
                        <div class="mt-3">
                            <p><strong>Invalid parameters:</strong></p>
                            <div class="invalid-params-list">
                                <?= implode(', ', $import_stats['invalid']) ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php 
                    // Clear the import stats from session after displaying
                    unset($_SESSION['import_stats']);
                    ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Floating back button -->
    <a href="index.php?route=index" class="floating-back-button" title="Back to Categories">
        <i class="fa-solid fa-table-cells-large"></i>
        <span class="tooltip">Back to Categories</span>
    </a>

    <?php include_once 'includes/footer.php'; ?>

    <script src="static/js/script.js?v=<?= time() ?>"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle descriptions visibility
            const showDescriptionsCheckbox = document.getElementById('show-descriptions');
            const descriptionRows = document.querySelectorAll('.param-description-row');
            
            // Show descriptions by default as the checkbox is checked
            descriptionRows.forEach(row => {
                row.style.display = 'table-row';
            });
            
            showDescriptionsCheckbox.addEventListener('change', function() {
                descriptionRows.forEach(row => {
                    row.style.display = this.checked ? 'table-row' : 'none';
                });
            });
            
            // Handle the download button - use Show Descriptions checkbox to determine 
            // whether to include comments in the config.pro file
            const downloadBtn = document.getElementById('download-config-btn');
            
            downloadBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const includeComments = document.getElementById('show-descriptions').checked;
                const downloadUrl = includeComments ? 
                    'index.php?route=download&include_comments=1' : 
                    'index.php?route=download';
                
                window.location.href = downloadUrl;
            });
        });
        
        // Parameter deletion without confirmation
        function removeParameter(paramName) {
            // Create a form with the parameter name
            var formData = new FormData();
            formData.append('name', paramName);
            
            // Send the request to remove the parameter
            fetch('index.php?route=remove_change', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Find and fade out the row
                    const rows = document.querySelectorAll('tr');
                    for (let i = 0; i < rows.length; i++) {
                        const paramCell = rows[i].querySelector('.summary-param');
                        if (paramCell && paramCell.textContent.trim() === paramName) {
                            rows[i].style.backgroundColor = '#ffcccc';
                            rows[i].style.opacity = '0';
                            rows[i].style.transition = 'opacity 0.5s ease';
                            
                            // After animation, reload the page
                            setTimeout(() => {
                                window.location.reload();
                            }, 500);
                            return;
                        }
                    }
                    
                    // If row not found, just reload
                    window.location.reload();
                } else {
                    console.error('Error removing parameter:', data.message || 'Unknown error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }
    </script>
</body>
</html> 