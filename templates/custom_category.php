<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/configwiz/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="/configwiz/favicon.ico" type="image/x-icon">
    <title>ConfigWiz - <?= $category ?></title>
    <?php include_once 'includes/analytics.php'; ?>
    <link rel="stylesheet" href="static/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <?php 
    // Make sure the category_icons array is available
    global $category_icons;
    if (!isset($category_icons)) {
        include_once 'includes/functions.php';
    }
    ?>
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
            <h1><i class="fa-solid <?= get_category_icon($category) ?>"></i> <?= $category ?></h1>
        </div>

        <div class="card">
            <div class="version-info">
                <p>Showing custom category for Creo <?= $_SESSION['version'] ?></p>
            </div>
            
            <div class="config-items">
                <?php foreach ($parameters as $param): ?>
                    <?php
                    $param_name = $param['Name'] ?? '';
                    $param_value = $param['Value'] ?? '';
                    $param_default = $param['Default Value'] ?? '';
                    $param_desc = isset($param['EnhancedDescription']) ? $param['EnhancedDescription'] : ($param['Description'] ?? '');
                    $param_options = $param['Options'] ?? '';
                    
                    // Check if this parameter has been modified by the user
                    $is_modified = false;
                    $modified_value = '';
                    if (!empty($user_changes) && isset($user_changes[$param_name])) {
                        $is_modified = true;
                        $modified_value = $user_changes[$param_name]['value'] ?? '';
                    }
                    ?>
                    <div class="config-item<?= $is_modified ? ' modified-parameter' : '' ?>" id="param-<?= $param_name ?>" data-name="<?= htmlspecialchars($param_name) ?>" data-category="<?= htmlspecialchars($category) ?>" data-default="<?= htmlspecialchars($param_default) ?>" data-description="<?= htmlspecialchars($param_desc) ?>" data-original-value="<?= htmlspecialchars($param_value) ?>">
                        <h3 class="param-name">
                            <?= htmlspecialchars($param_name) ?>
                        </h3>
                        <p class="param-description"><?= htmlspecialchars($param_desc) ?></p>
                        
                        <div class="input-group">
                            <?php if (!empty($param['Options'])): ?>
                                <div class="text-input-wrapper">
                                    <select name="<?= $param_name ?>" class="auto-save" data-default="<?= $param_default ?>" data-parameter="<?= $param_name ?>" data-category="<?= $category ?>">
                                        <?php 
                                        $options = explode(',', $param['Options']);
                                        foreach ($options as $option): 
                                            $option = trim($option);
                                            $selected = '';
                                            if ($is_modified && $option === $modified_value) {
                                                $selected = 'selected';
                                            } elseif (!$is_modified && $option === $param_value) {
                                                $selected = 'selected';
                                            } elseif (!$is_modified && $option === $param_default) {
                                                $selected = 'selected';
                                            }
                                        ?>
                                        <option value="<?= $option ?>" <?= $selected ?>><?= $option ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if ($is_modified): ?>
                                    <button type="button" class="btn-reset-field" title="Reset to original value" data-parameter="<?= $param_name ?>">
                                        <i class="fa-solid fa-rotate-left"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-input-wrapper">
                                    <input type="text" name="<?= $param_name ?>" class="auto-save" value="<?= $is_modified ? $modified_value : (empty(trim($param_value)) ? $param_default : $param_value) ?>" data-default="<?= $param_default ?>" data-parameter="<?= $param_name ?>" data-category="<?= $category ?>">
                                    <?php if ($is_modified): ?>
                                    <button type="button" class="btn-reset-field" title="Reset to original value" data-parameter="<?= $param_name ?>">
                                        <i class="fa-solid fa-rotate-left"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="default-value"><strong>Default:</strong> <?= empty($param_default) || strtolower($param_default) === 'nan' ? '(No Value)' : htmlspecialchars($param_default) ?></div>
                        
                        <?php if ($is_modified): ?>
                        <div class="change-status" id="status-<?= $param_name ?>">
                            <span class="status-changed"><i class="fa-solid fa-check"></i> Added to configuration</span>
                        </div>
                        <?php else: ?>
                        <div class="change-status" id="status-<?= $param_name ?>"></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div style="text-align: center; margin-top: 20px;">
                <a href="index.php?route=index" class="btn btn-secondary">Back to Categories</a>
            </div>
        </div>
    </div>

    <!-- Floating back button -->
    <a href="index.php?route=index" class="floating-back-button" title="Back to Categories">
        <i class="fa-solid fa-table-cells-large"></i>
        <span class="tooltip">Back to Categories</span>
    </a>

    <?php include_once 'includes/footer.php'; ?>

    <script src="static/js/script.js?v=<?= time() . rand(1000, 9999) ?>"></script>
</body>
</html> 