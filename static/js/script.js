// ConfigWiz - Consolidated JavaScript
const ConfigWiz = {
    // Initialize all common functionality
    init: function() {
        this.initGlobalSearch();
        this.initCollapsibles();
        this.updateChangeButtons();
        this.setupNavigationWarnings();
        this.setupFileUpload();
        this.initResetButtons();
        
        // Additional page-specific initializations
        if(document.querySelector('.summary-page')) {
            this.initSummaryPage();
        }
        
        if(document.querySelector('.param-deletion')) {
            this.initParamDeletion();
        }
        
        if(document.querySelector('.auto-save')) {
            this.initAutoSave();
        }
    },
    
    // Global search implementation
    initGlobalSearch: function() {
        const globalSearch = document.getElementById('global-search');
        const globalSearchResults = document.getElementById('global-search-results');
        
        if (!globalSearch || !globalSearchResults) return;
        
        let debounceTimer;
        
        globalSearch.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            const query = this.value.trim();
            
            if (query.length < 2) {
                globalSearchResults.classList.remove('visible');
                return;
            }
            
            // Show loading indicator
            globalSearchResults.innerHTML = '<div class="instant-result-item">Searching...</div>';
            globalSearchResults.classList.add('visible');
            
            // Debounce to avoid too many requests
            debounceTimer = setTimeout(function() {
                fetch('./index.php?route=api_search&query=' + encodeURIComponent(query))
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Search request failed: ' + response.statusText);
                        }
                        return response.json().catch(error => {
                            console.error('Invalid JSON response:', error);
                            throw new Error('Invalid JSON response from server');
                        });
                    })
                    .then(data => {
                        globalSearchResults.innerHTML = '';
                        
                        if (Array.isArray(data) && data.length === 0) {
                            globalSearchResults.innerHTML = '<div class="instant-result-item">No results found</div>';
                        } else if (Array.isArray(data)) {
                            data.forEach(item => {
                                const resultItem = document.createElement('div');
                                resultItem.className = 'instant-result-item';
                                resultItem.innerHTML = `
                                    <div class="result-name">${item.Name || 'Unknown'}</div>
                                    <div class="result-description">${item.Description || ''}</div>
                                `;
                                resultItem.addEventListener('click', function() {
                                    // Hide search results before navigating
                                    globalSearchResults.classList.remove('visible');
                                    // Clear the search input
                                    globalSearch.value = '';
                                    // Store the target in localStorage to ensure highlighting works after page loads
                                    const targetParam = item.Name;
                                    localStorage.setItem('highlight_param', targetParam);
                                    // Navigate to the parameter
                                    window.location.href = './index.php?route=configure&category=' + encodeURIComponent(item.Category || 'Imported Configuration') + '#param-' + targetParam;
                                });
                                globalSearchResults.appendChild(resultItem);
                            });
                        } else {
                            globalSearchResults.innerHTML = '<div class="instant-result-item">Invalid search response</div>';
                            console.error('Invalid search response:', data);
                        }
                        
                        globalSearchResults.classList.add('visible');
                    })
                    .catch(error => {
                        console.error('Error fetching search results:', error);
                        globalSearchResults.innerHTML = '<div class="instant-result-item">Error searching. Please try again.</div>';
                        globalSearchResults.classList.add('visible');
                    });
            }, 300);
        });
        
        // Hide search results when clicking outside
        document.addEventListener('click', function(event) {
            if (!globalSearch.contains(event.target) && !globalSearchResults.contains(event.target)) {
                globalSearchResults.classList.remove('visible');
            }
        });
    },
    
    // Navigation with changes warning
    setupNavigationWarnings: function() {
        // Find all elements that need the navigateHome function
        const homeLinks = document.querySelectorAll('[onclick="navigateHome(event)"]');
        
        console.log('Found navigation home links:', homeLinks.length);
        
        if (homeLinks.length === 0) return;
        
        // Replace the inline onclick with our function
        homeLinks.forEach(link => {
            link.removeAttribute('onclick');
            link.addEventListener('click', function(event) {
                event.preventDefault();
                console.log('ConfigWiz logo clicked, checking for changes...');
                
                // Check if there are any changes
                fetch('./index.php?route=get_changes')
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok: ' + response.statusText);
                        }
                        return response.json().catch(error => {
                            console.error('Invalid JSON response:', error);
                            throw new Error('Invalid JSON response from server');
                        });
                    })
                    .then(data => {
                        console.log('Changes check result:', data);
                        
                        // Check if data exists and has items
                        // The API returns the user_changes directly, not in a nested object
                        const hasChanges = data && Object.keys(data).length > 0;
                        console.log('Has changes:', hasChanges);
                        
                        if (hasChanges) {
                            // Show warning dialog if there are changes
                            const confirmNavigation = confirm("Warning: Navigating away will clear all your configuration changes. Are you sure you want to start over?");
                            console.log('User confirmed navigation:', confirmNavigation);
                            
                            if (confirmNavigation) {
                                // Navigate to the versions selection page
                                window.location.href = "./index.php?route=home";
                            }
                        } else {
                            // No changes, navigate directly to the versions selection page
                            console.log('No changes, navigating directly');
                            window.location.href = "./index.php?route=home";
                        }
                    })
                    .catch(error => {
                        console.error('Error checking for changes:', error);
                        // Navigate anyway if there's an error
                        window.location.href = "./index.php?route=home";
                    });
            });
        });
    },
    
    // Add/remove View Changes button
    updateChangeButtons: function() {
        console.log('Updating change buttons...');
        // Check for existing changes and update UI
        fetch('./index.php?route=get_changes')
            .then(response => {
                console.log('Get changes response status:', response.status);
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.statusText);
                }
                return response.text().then(text => {
                    console.log('Get changes raw response:', text);
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('JSON parse error:', e);
                        throw new Error('Invalid JSON response: ' + text);
                    }
                });
            })
            .then(data => {
                console.log('Get changes response data:', data);
                
                // Get the View Changes button
                const viewChangesButton = document.querySelector('.right-buttons .primary');
                const rightButtons = document.querySelector('.right-buttons');
                
                console.log('Current UI elements:', {
                    viewChangesButton: viewChangesButton ? 'exists' : 'not found',
                    rightButtons: rightButtons ? 'exists' : 'not found'
                });
                
                if (!rightButtons) {
                    console.warn('Right buttons container not found');
                    return;
                }
                
                // API returns the user_changes directly
                const hasChanges = data && Object.keys(data).length > 0;
                const changedParams = data ? Object.keys(data) : [];
                
                console.log('Changes state:', {
                    hasChanges,
                    changedParams
                });
                
                // Find all status elements and config items
                const allStatusElements = document.querySelectorAll('.change-status');
                const allConfigItems = document.querySelectorAll('.config-item');
                
                console.log('Found elements:', {
                    statusElements: allStatusElements.length,
                    configItems: allConfigItems.length
                });
                
                // First, reset all status indicators for parameters that are not in the changes list
                allStatusElements.forEach(statusEl => {
                    const paramId = statusEl.id;
                    if (paramId) {
                        const paramName = paramId.replace('status-', '');
                        if (!changedParams.includes(paramName)) {
                            console.log('Resetting status for:', paramName);
                            statusEl.innerHTML = '';
                            statusEl.classList.remove('status-changed');
                            
                            // Also find and remove modified class from the config item
                            const configItem = document.getElementById(`param-${paramName}`);
                            if (configItem) {
                                configItem.classList.remove('modified-parameter');
                                
                                // Remove modified indicator if present
                                const modifiedIndicator = configItem.querySelector('.modified-indicator');
                                if (modifiedIndicator) {
                                    modifiedIndicator.remove();
                                }
                            }
                        }
                    }
                });
                
                if (hasChanges) {
                    console.log('Has changes, updating UI...');
                    // Add the button if it doesn't exist
                    if (!viewChangesButton) {
                        console.log('Creating View Changes button');
                        const newViewChangesButton = document.createElement('a');
                        newViewChangesButton.href = "./index.php?route=summary";
                        newViewChangesButton.className = "search-bar-button primary";
                        newViewChangesButton.textContent = "View Changes";
                        rightButtons.appendChild(newViewChangesButton);
                    }
                    
                    // Update status indicators on configure page for parameters that are in the changes list
                    for (const [name, value] of Object.entries(data)) {
                        console.log('Updating status for changed parameter:', name);
                        const statusElement = document.getElementById(`status-${name}`);
                        if (statusElement) {
                            statusElement.innerHTML = '<span class="status-changed"><i class="fa-solid fa-check"></i> Added to configuration</span>';
                            
                            // Also find and add modified class to the config item
                            const configItem = document.getElementById(`param-${name}`);
                            if (configItem && !configItem.classList.contains('modified-parameter')) {
                                configItem.classList.add('modified-parameter');
                                
                                // Add the modified indicator icon if not present
                                const paramNameElement = configItem.querySelector('.param-name');
                                if (paramNameElement && !paramNameElement.querySelector('.modified-indicator')) {
                                    const modifiedIndicator = document.createElement('span');
                                    modifiedIndicator.className = 'modified-indicator';
                                    modifiedIndicator.title = 'Modified';
                                    modifiedIndicator.innerHTML = '<i class="fa-solid fa-pen"></i>';
                                    paramNameElement.appendChild(modifiedIndicator);
                                }
                            }
                        } else {
                            console.warn('Status element not found for:', name);
                        }
                    }
                } else {
                    console.log('No changes, removing View Changes button if exists');
                    // Remove the button if it exists and there are no changes
                    if (viewChangesButton) {
                        viewChangesButton.remove();
                    }
                }
            })
            .catch(error => {
                console.error('Error updating change buttons:', error);
            });
    },
    
    // Collapsible sections
    initCollapsibles: function() {
        const collapsibles = document.getElementsByClassName("collapsible");
        
        if (collapsibles.length === 0) return;
        
        for (let i = 0; i < collapsibles.length; i++) {
            // Ensure initial state is collapsed
            const content = collapsibles[i].nextElementSibling;
            if (content && content.className.includes('collapsible-content')) {
                content.style.maxHeight = null;
            
                collapsibles[i].addEventListener("click", function() {
                    this.classList.toggle("active");
                    if (content.style.maxHeight) {
                        content.style.maxHeight = null;
                    } else {
                        content.style.maxHeight = content.scrollHeight + "px";
                    }
                });
            }
        }
    },
    
    // File upload handling
    setupFileUpload: function() {
        const fileInput = document.getElementById('file-upload');
        const fileNameDisplay = document.getElementById('file-name');
        
        if (!fileInput || !fileNameDisplay) return;
        
        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                fileNameDisplay.textContent = this.files[0].name;
            } else {
                fileNameDisplay.textContent = '';
            }
        });
    },
    
    // Parameter auto-save functionality
    initAutoSave: function() {
        const autoSaveInputs = document.querySelectorAll('.auto-save');
        
        console.log('InitAutoSave - Found inputs:', autoSaveInputs.length);
        
        if (autoSaveInputs.length === 0) return;
        
        autoSaveInputs.forEach(input => {
            input.addEventListener('change', function() {
                const paramName = this.name;
                const paramValue = this.value;
                const defaultValue = this.getAttribute('data-default');
                const paramCategory = this.getAttribute('data-category') || this.closest('.config-item')?.getAttribute('data-category') || '';
                const paramDescription = this.closest('.config-item')?.getAttribute('data-description') || '';
                const statusElement = this.closest('.config-item')?.querySelector('.change-status');
                const configItem = this.closest('.config-item');
                
                console.log('Change detected:', {
                    paramName,
                    paramValue,
                    defaultValue,
                    paramCategory,
                    paramDescription
                });
                
                // Get the original value from the database (or default if original is empty)
                const originalValue = configItem ? configItem.getAttribute('data-original-value') : '';
                const effectiveOriginalValue = originalValue && originalValue.trim() !== '' ? originalValue : defaultValue;
                
                console.log('Original values:', {
                    originalValue,
                    effectiveOriginalValue
                });
                
                // Show saving indicator
                if (statusElement) {
                    statusElement.innerHTML = '<span class="saving-indicator">Saving change...</span>';
                }
                
                // Handle comparison differently for select elements vs text inputs
                let isDifferentFromOriginal;
                
                if (this.tagName.toLowerCase() === 'select') {
                    // For select elements, if original value contains multiple options,
                    // we need to check if the current value is different from the default
                    if (originalValue && originalValue.includes(',')) {
                        isDifferentFromOriginal = paramValue !== defaultValue;
                    } else {
                        // If original value is a single value, compare with that
                        isDifferentFromOriginal = paramValue !== effectiveOriginalValue;
                    }
                    console.log('Select comparison:', {
                        paramValue,
                        defaultValue,
                        effectiveOriginalValue,
                        isDifferentFromOriginal
                    });
                } else {
                    // For text inputs, first try original value, fallback to default
                    const valueToCompare = originalValue.includes(',') ? defaultValue : effectiveOriginalValue;
                    isDifferentFromOriginal = paramValue !== valueToCompare;
                    console.log('Text input comparison:', {
                        paramValue,
                        valueToCompare,
                        isDifferentFromOriginal
                    });
                }
                
                // Create FormData object
                const formData = new FormData();
                
                if (isDifferentFromOriginal) {
                    // If value is different from original/default, save the change
                    formData.append('name', paramName);
                    formData.append('value', paramValue);
                    formData.append('category', paramCategory);
                    formData.append('description', paramDescription);
                    formData.append('default_value', defaultValue);
                    
                    console.log('Sending save request with data:', {
                        name: paramName,
                        value: paramValue,
                        category: paramCategory,
                        description: paramDescription,
                        default_value: defaultValue
                    });
                    
                    fetch('./index.php?route=save_change', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        console.log('Save response status:', response.status);
                        if (!response.ok) {
                            throw new Error('Network response was not ok: ' + response.statusText);
                        }
                        return response.text().then(text => {
                            console.log('Raw response:', text);
                            try {
                                return JSON.parse(text);
                            } catch (e) {
                                console.error('JSON parse error:', e);
                                throw new Error('Invalid JSON response: ' + text);
                            }
                        });
                    })
                    .then(data => {
                        console.log('Save response data:', data);
                        if (data.status === 'success') {
                            if (statusElement) {
                                statusElement.innerHTML = '<span class="status-changed"><i class="fa-solid fa-check"></i> Added to configuration</span>';
                                
                                // Also find and add modified class to the config item immediately
                                const configItem = input.closest('.config-item');
                                if (configItem) {
                                    configItem.classList.add('modified-parameter');
                                    
                                    // Add modified indicator if not present
                                    const paramNameElement = configItem.querySelector('.param-name');
                                    if (paramNameElement && !paramNameElement.querySelector('.modified-indicator')) {
                                        const modifiedIndicator = document.createElement('span');
                                        modifiedIndicator.className = 'modified-indicator';
                                        modifiedIndicator.title = 'Modified';
                                        modifiedIndicator.innerHTML = '<i class="fa-solid fa-pen"></i>';
                                        paramNameElement.appendChild(modifiedIndicator);
                                    }
                                }
                            }
                            
                            // Add a small delay before checking changes to allow server state to update
                            setTimeout(() => {
                                ConfigWiz.updateChangeButtons();
                            }, 500);
                        } else {
                            console.error('Save failed:', data);
                            if (statusElement) {
                                statusElement.innerHTML = '<span class="status-error">Error saving change</span>';
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error saving changes:', error);
                        if (statusElement) {
                            statusElement.innerHTML = '<span class="status-error">Error saving change: ' + error.message + '</span>';
                        }
                    });
                } else {
                    // Only try to remove if we're resetting to default and the parameter might exist
                    fetch('./index.php?route=get_changes')
                        .then(response => response.json())
                        .then(changes => {
                            // Check if this parameter exists in changes
                            if (changes && changes[paramName]) {
                                console.log('Parameter exists in changes, removing:', paramName);
                                formData.append('name', paramName);
                                
                                return fetch('./index.php?route=remove_change', {
                                    method: 'POST',
                                    body: formData
                                });
                            } else {
                                console.log('Parameter not in changes, skipping remove request');
                                return null;
                            }
                        })
                        .then(response => {
                            if (response) {
                                return response.text().then(text => {
                                    console.log('Raw response:', text);
                                    try {
                                        return JSON.parse(text);
                                    } catch (e) {
                                        console.error('JSON parse error:', e);
                                        throw new Error('Invalid JSON response: ' + text);
                                    }
                                });
                            }
                            return null;
                        })
                        .then(data => {
                            if (data && data.status === 'success') {
                                if (statusElement) {
                                    statusElement.innerHTML = '<span class="status-restored">Value restored to default</span>';
                                    setTimeout(() => {
                                        statusElement.innerHTML = '';
                                    }, 3000);
                                }
                                ConfigWiz.updateChangeButtons();
                            } else if (data && data.status === 'error') {
                                console.error('Remove failed:', data);
                                if (statusElement) {
                                    statusElement.innerHTML = '<span class="status-error">Error restoring value</span>';
                                }
                            } else {
                                // Parameter wasn't in changes, just clear the status
                                if (statusElement) {
                                    statusElement.innerHTML = '';
                                }
                            }
                        })
                        .catch(error => {
                            console.error('Error restoring value:', error);
                            if (statusElement) {
                                statusElement.innerHTML = '<span class="status-error">Error restoring value: ' + error.message + '</span>';
                            }
                        });
                }
            });
        });
    },
    
    // Summary page specific functionality
    initSummaryPage: function() {
        const showCommentsCheckbox = document.getElementById('show-comments');
        const paramDescriptions = document.querySelectorAll('.param-description');
        const deleteParamButtons = document.querySelectorAll('.delete-param-btn');
        
        // Parameter deletion functionality
        if (deleteParamButtons.length > 0) {
            deleteParamButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const paramName = this.getAttribute('data-param-name');
                    if (confirm(`Are you sure you want to remove "${paramName}" from your configuration changes?`)) {
                        // Send request to remove the parameter
                        fetch('./index.php?route=remove_change', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: new URLSearchParams({
                                'name': paramName
                            })
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok: ' + response.statusText);
                            }
                            return response.json().catch(error => {
                                console.error('Invalid JSON response:', error);
                                throw new Error('Invalid JSON response from server');
                            });
                        })
                        .then(data => {
                            if (data.status === 'success') {
                                // Remove the row from the table
                                const row = this.closest('tr');
                                row.style.backgroundColor = '#ffcccc';
                                row.style.opacity = '0';
                                setTimeout(() => {
                                    row.remove();
                                    
                                    // Update the parameter count
                                    const countElement = document.querySelector('.changes-count p');
                                    if (countElement) {
                                        const count = document.querySelectorAll('#summary-table-body tr').length;
                                        countElement.textContent = `${count} parameter${count !== 1 ? 's' : ''} changed from default values`;
                                    }
                                    
                                    // Check if we need to update the View Changes button
                                    ConfigWiz.updateChangeButtons();
                                    
                                    // If no parameters left, refresh the page to show "no changes" message
                                    if (document.querySelectorAll('#summary-table-body tr').length === 0) {
                                        window.location.reload();
                                    }
                                }, 500);
                            } else {
                                alert('Failed to remove parameter. Please try again.');
                            }
                        })
                        .catch(error => {
                            console.error('Error removing parameter:', error);
                            alert('An error occurred while removing the parameter.');
                        });
                    }
                });
            });
        }
        
        // Toggle comments/descriptions
        if (showCommentsCheckbox && paramDescriptions.length > 0) {
            // Set initial state based on localStorage or default to checked
            const showComments = localStorage.getItem('showComments') !== 'false';
            showCommentsCheckbox.checked = showComments;
            
            // Apply initial state
            this.toggleDescriptions(paramDescriptions, showComments);
            
            // Update download link based on comments preference
            this.updateDownloadLink(showComments);
            
            showCommentsCheckbox.addEventListener('change', function() {
                const showComments = this.checked;
                localStorage.setItem('showComments', showComments);
                ConfigWiz.toggleDescriptions(paramDescriptions, showComments);
                ConfigWiz.updateDownloadLink(showComments);
            });
        }
    },
    
    toggleDescriptions: function(descriptions, show) {
        descriptions.forEach(desc => {
            desc.style.display = show ? 'block' : 'none';
        });
    },
    
    updateDownloadLink: function(showComments) {
        const downloadLink = document.getElementById('download-link');
        if (downloadLink) {
            if (showComments) {
                downloadLink.href = "./index.php?route=download";
            } else {
                downloadLink.href = "./index.php?route=download&include_comments=0";
            }
        }
    },
    
    // Highlight parameter in configure view when coming from a link
    highlightParameter: function() {
        // Check URL hash for direct navigation
        if (window.location.hash) {
            const targetElement = document.querySelector(window.location.hash);
            this.performHighlight(targetElement);
        }
        
        // Check localStorage for search navigation (more reliable across page loads)
        const highlightParam = localStorage.getItem('highlight_param');
        if (highlightParam) {
            const targetElement = document.getElementById('param-' + highlightParam);
            this.performHighlight(targetElement);
            // Clear the stored parameter after use
            localStorage.removeItem('highlight_param');
        }
    },
    
    performHighlight: function(element) {
        if (element) {
            // First make sure any previous highlights are removed
            const previousHighlights = document.querySelectorAll('.highlight');
            previousHighlights.forEach(el => el.classList.remove('highlight'));
            
            // Scroll the element into view
            setTimeout(() => {
                // Get the element's position relative to the viewport
                const rect = element.getBoundingClientRect();
                
                // Check if element is already visible in the viewport
                const isVisible = (
                    rect.top >= 0 &&
                    rect.left >= 0 &&
                    rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
                    rect.right <= (window.innerWidth || document.documentElement.clientWidth)
                );
                
                // Only scroll if element is not already visible
                if (!isVisible) {
                    // For multi-column layouts, use a different scroll behavior
                    // First scroll to position the element near the top of the viewport
                    window.scrollBy({
                        top: rect.top - 120, // Position it 120px from the top
                        left: 0,
                        behavior: 'smooth'
                    });
                }
                
                // Apply the highlight class
                element.classList.add('highlight');
                
                // Make highlight effect more visible by adding a temporary border
                element.style.boxShadow = '0 0 20px rgba(255, 165, 0, 0.7)';
                
                // Remove highlight after animation completes
                setTimeout(() => {
                    element.classList.remove('highlight');
                    element.style.boxShadow = '';
                }, 3000);
            }, 300);
        }
    },
    
    // Parameter deletion functionality
    initParamDeletion: function() {
        const deleteButtons = document.querySelectorAll('.btn-remove');
        if (deleteButtons.length === 0) return;
        
        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                const paramName = this.getAttribute('data-param-name');
                if (paramName && confirm(`Are you sure you want to remove "${paramName}" from your configuration?`)) {
                    // Send request to remove the parameter
                    fetch('./index.php?route=remove_change', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams({
                            'name': paramName
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            window.location.reload();
                        } else {
                            alert('Failed to remove parameter. Please try again.');
                        }
                    })
                    .catch(error => {
                        console.error('Error removing parameter:', error);
                        alert('An error occurred while removing the parameter.');
                    });
                }
            });
        });
    },
    
    // Initialize reset button functionality for modified fields
    initResetButtons: function() {
        document.addEventListener('click', function(e) {
            if (e.target.closest('.btn-reset-field')) {
                const resetButton = e.target.closest('.btn-reset-field');
                const paramName = resetButton.getAttribute('data-parameter');
                const configItem = resetButton.closest('.config-item');
                
                if (!paramName || !configItem) return;
                
                // Find the input field or select
                const inputField = configItem.querySelector('input[name="' + paramName + '"]');
                const selectField = configItem.querySelector('select[name="' + paramName + '"]');
                const field = inputField || selectField;
                
                if (!field) {
                    console.error('Could not find field for parameter:', paramName);
                    return;
                }
                
                console.log('Resetting field:', paramName, 'Type:', field.tagName, 'Current value:', field.value);
                
                // Show saving indicator
                const statusElement = configItem.querySelector('.change-status');
                if (statusElement) {
                    statusElement.innerHTML = '<span class="saving-indicator">Resetting to default...</span>';
                }
                
                // Send request to remove this parameter
                fetch('./index.php?route=remove_change', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        'name': paramName
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        // Get original value (current value in the database)
                        const originalValue = configItem.getAttribute('data-original-value') || '';
                        const defaultValue = field.getAttribute('data-default') || '';
                        
                        console.log('Reset - Parameter:', paramName, 'Default:', defaultValue, 'Original:', originalValue);
                        
                        // Update the field value
                        if (field.tagName.toLowerCase() === 'select') {
                            // For select dropdowns, set to default value directly
                            console.log('Reset select to default:', defaultValue);
                            
                            // Find and select the option matching the default value
                            let optionFound = false;
                            Array.from(field.options).forEach(option => {
                                if (option.value === defaultValue) {
                                    option.selected = true;
                                    optionFound = true;
                                } else {
                                    option.selected = false;
                                }
                            });
                            
                            if (!optionFound) {
                                console.warn('Could not find option matching default value:', defaultValue);
                            }
                        } else {
                            // For text inputs
                            const valueToUse = originalValue.trim() !== '' ? originalValue : defaultValue;
                            console.log('Reset text input to:', valueToUse);
                            field.value = valueToUse;
                        }
                        
                        // Remove modified styling
                        configItem.classList.remove('modified-parameter');
                        
                        // Remove reset button by replacing it with a clean version of the input/select
                        resetButton.parentElement.replaceWith(field.cloneNode(true));
                        
                        // Remove modified indicator
                        const modifiedIndicator = configItem.querySelector('.modified-indicator');
                        if (modifiedIndicator) {
                            modifiedIndicator.remove();
                        }
                        
                        // Clear status message and ensure it's not showing "Added to configuration"
                        if (statusElement) {
                            statusElement.innerHTML = '<span class="status-restored">Value restored to default</span>';
                            // Explicitly remove any existing status classes
                            statusElement.className = 'change-status';
                            
                            // Remove the message after a delay
                            setTimeout(() => {
                                statusElement.innerHTML = '';
                            }, 3000);
                        }
                        
                        // Force an immediate UI update
                        ConfigWiz.updateChangeButtons();
                    } else {
                        console.error('Error removing parameter:', data.message);
                        if (statusElement) {
                            statusElement.innerHTML = '<span class="status-error">Error resetting value</span>';
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    if (statusElement) {
                        statusElement.innerHTML = '<span class="status-error">Error resetting value</span>';
                    }
                });
            }
        });
    }
};

// Instant search functionality (for backwards compatibility)
document.addEventListener('DOMContentLoaded', function() {
    // Initialize everything
    ConfigWiz.init();
    
    // Handle hash in URL for parameter highlighting
    ConfigWiz.highlightParameter();
}); 