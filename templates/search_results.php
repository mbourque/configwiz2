<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/configwiz/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="/configwiz/favicon.ico" type="image/x-icon">
    <title>ConfigWiz - Search Results</title>
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
                <?php if (!empty($user_changes)): ?>
                <a href="index.php?route=summary" class="search-bar-button primary">View Changes</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if (isset($_SESSION['flash_message']) && isset($_SESSION['flash_category'])): ?>
            <div class="flash-messages">
                <div class="flash-message <?= $_SESSION['flash_category'] ?>">
                    <?= $_SESSION['flash_message'] ?>
                </div>
            </div>
            <?php
            // Clear flash messages after displaying them
            unset($_SESSION['flash_message']);
            unset($_SESSION['flash_category']);
            ?>
        <?php endif; ?>

        <div class="page-header">
            <h1>Search Results: "<?= htmlspecialchars($query) ?>"</h1>
        </div>

        <?php if (empty($results)): ?>
        <div class="card">
            <div class="empty-state">
                <i class="fa-solid fa-search empty-icon"></i>
                <h2>No Results Found</h2>
                <p>Your search did not match any parameters. Try a different search term.</p>
                <a href="index.php?route=index" class="btn primary">Return to Categories</a>
            </div>
        </div>
        <?php else: ?>
        <div class="card parameter-list">
            <div class="search-summary">
                <p>Found <?= count($results) ?> results for "<?= htmlspecialchars($query) ?>"</p>
            </div>
            
            <table class="parameters-table">
                <thead>
                    <tr>
                        <th class="param-name">Parameter</th>
                        <th class="param-value">Value</th>
                        <th class="param-category">Category</th>
                        <th class="param-desc">Description</th>
                        <th class="param-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    // Get user's changes
                    $user_changes = get_user_changes();
                    
                    foreach ($results as $param): 
                    ?>
                    <?php
                    $param_name = $param['Name'] ?? '';
                    $param_value = $param['Value'] ?? '';
                    $param_default = $param['Default Value'] ?? '';
                    $param_desc = isset($param['EnhancedDescription']) ? $param['EnhancedDescription'] : ($param['Description'] ?? '');
                    $param_category = $param['Category'] ?? '';
                    
                    // Check if this parameter has been modified by the user
                    $is_modified = false;
                    $modified_value = '';
                    if (!empty($user_changes) && isset($user_changes[$param_name])) {
                        $is_modified = true;
                        $modified_value = $user_changes[$param_name]['value'] ?? '';
                    }
                    
                    // Determine row class
                    $row_class = $is_modified ? 'modified-parameter' : '';
                    ?>
                    <tr class="<?= $row_class ?>" data-name="<?= htmlspecialchars($param_name) ?>" data-category="<?= htmlspecialchars($param_category) ?>" data-default="<?= htmlspecialchars($param_default) ?>" data-description="<?= htmlspecialchars($param_desc) ?>">
                        <td class="param-name">
                            <?= htmlspecialchars($param_name) ?>
                            <?php if ($is_modified): ?>
                            <span class="modified-indicator" title="Modified"><i class="fa-solid fa-pen"></i></span>
                            <?php endif; ?>
                        </td>
                        <td class="param-value">
                            <?php if ($is_modified): ?>
                            <span class="value-display"><?= htmlspecialchars($modified_value) ?></span>
                            <div class="value-original">
                                <span class="original-label">Original:</span> <?= htmlspecialchars(empty(trim($param_value)) ? $param_default : $param_value) ?>
                            </div>
                            <?php else: ?>
                            <span class="value-display"><?= htmlspecialchars(empty(trim($param_value)) ? $param_default : $param_value) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="param-category">
                            <a href="index.php?route=configure&category=<?= urlencode($param_category) ?>">
                                <?= htmlspecialchars($param_category) ?>
                            </a>
                        </td>
                        <td class="param-desc">
                            <?= htmlspecialchars($param_desc) ?>
                        </td>
                        <td class="param-actions">
                            <button class="btn-edit" title="Edit parameter" onclick="editParameter(this)">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Floating back button -->
    <a href="index.php?route=index" class="floating-back-button" title="Back to Categories">
        <i class="fa-solid fa-table-cells-large"></i>
        <span class="tooltip">Back to Categories</span>
    </a>

    <footer class="site-footer">
        <div class="footer-content">
            <p>&copy; 2025 Michael P. Bourque | <a href="https://github.com/mbourque/configwiz2" target="_blank"><i class="fa-brands fa-github"></i> GitHub</a></p>
        </div>
    </footer>

    <script src="static/js/script.js?v=<?= time() ?>"></script>
</body>
</html> 