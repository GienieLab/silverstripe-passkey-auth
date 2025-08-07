/**
 * Passkey Management JavaScript
 * Handles user interactions for passkey credential management
 */

// Initialize passkey management when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    initializePasskeyManagement();
});

/**
 * Initialize passkey management functionality
 */
function initializePasskeyManagement() {
    // Only initialize if we're on the passkey management page
    if (!document.querySelector('.passkey-management')) {
        return;
    }

    console.log('Initializing passkey management...');
    
    // Setup delete button handlers
    setupDeleteButtons();
    
    // Setup registration button if present
    setupRegistrationButton();
}

/**
 * Setup event handlers for delete buttons
 */
function setupDeleteButtons() {
    const deleteButtons = document.querySelectorAll('.delete-passkey');
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const credentialId = this.getAttribute('data-credential-id');
            const credentialName = this.getAttribute('data-credential-name');
            
            if (!credentialId) {
                console.error('No credential ID found for delete button');
                return;
            }
            
            // Show confirmation dialog
            const confirmed = confirm(
                `Are you sure you want to delete "${credentialName}"?\n\n` +
                'This action cannot be undone. You will no longer be able to use this passkey to sign in.'
            );
            
            if (confirmed) {
                deletePasskey(credentialId, button);
            }
        });
    });
    
    console.log(`Setup ${deleteButtons.length} delete button handlers`);
}

/**
 * Setup registration button handler
 */
function setupRegistrationButton() {
    const registerButton = document.querySelector('.passkey-management__register .btn-primary');
    
    if (registerButton && !registerButton.hasAttribute('onclick')) {
        registerButton.addEventListener('click', function() {
            startPasskeyRegistration();
        });
    }
}

/**
 * Delete a passkey credential
 * @param {string} credentialId - The credential ID to delete
 * @param {HTMLElement} button - The delete button element
 */
async function deletePasskey(credentialId, button) {
    const statusEl = document.getElementById('passkey-status');
    const originalButtonText = button.textContent;
    
    try {
        // Update UI to show loading state
        button.disabled = true;
        button.textContent = 'Deleting...';
        
        if (statusEl) {
            statusEl.textContent = 'Deleting passkey...';
            statusEl.className = 'passkey-status';
        }
        
        // Send delete request to server
        const response = await fetch(`/passkey-management/delete/${credentialId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            credentials: 'same-origin'
        });
        
        if (!response.ok) {
            let errorMessage = 'Failed to delete passkey';
            
            try {
                const errorData = await response.json();
                errorMessage = errorData.error || errorMessage;
            } catch (e) {
                // If response isn't JSON, use status text
                errorMessage = response.statusText || errorMessage;
            }
            
            throw new Error(errorMessage);
        }
        
        // Parse success response
        const result = await response.json();
        
        // Remove the credential from the page
        const credentialElement = document.querySelector(`[data-credential-id="${credentialId}"]`);
        if (credentialElement) {
            // Animate removal
            credentialElement.style.transition = 'all 0.3s ease';
            credentialElement.style.opacity = '0';
            credentialElement.style.transform = 'translateX(-20px)';
            
            setTimeout(() => {
                credentialElement.remove();
                
                // Check if no passkeys left
                const remainingCredentials = document.querySelectorAll('.passkey-credential');
                if (remainingCredentials.length === 0) {
                    showNoPasskeysMessage();
                }
            }, 300);
        }
        
        // Show success message
        showStatusMessage('Passkey deleted successfully', 'success');
        
    } catch (error) {
        console.error('Error deleting passkey:', error);
        
        // Reset button state
        button.disabled = false;
        button.textContent = originalButtonText;
        
        // Show error message
        showStatusMessage('Failed to delete passkey: ' + error.message, 'error');
    }
}

/**
 * Show status message to user
 * @param {string} message - The message to show
 * @param {string} type - The type of message (success, error, info)
 */
function showStatusMessage(message, type = 'info') {
    const statusEl = document.getElementById('passkey-status');
    if (!statusEl) return;
    
    // Set message and styling
    statusEl.textContent = message;
    statusEl.className = `passkey-status alert alert-${type}`;
    
    // Auto-hide success and info messages after 5 seconds
    if (type === 'success' || type === 'info') {
        setTimeout(() => {
            if (statusEl.textContent === message) {
                statusEl.textContent = '';
                statusEl.className = 'passkey-status';
            }
        }, 5000);
    }
}

/**
 * Show message when no passkeys remain
 */
function showNoPasskeysMessage() {
    const credentialsList = document.querySelector('.passkey-credentials-list');
    if (!credentialsList) return;
    
    credentialsList.innerHTML = `
        <div class="alert alert-info">
            <h4>No Passkeys Registered</h4>
            <p>You have successfully removed all your passkeys. You can register a new passkey below if you'd like to continue using passkey authentication.</p>
        </div>
    `;
}

/**
 * Refresh the passkey list from server
 * Useful after operations that might change the list
 */
async function refreshPasskeyList() {
    try {
        const response = await fetch('/passkey-management/list', {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            },
            credentials: 'same-origin'
        });
        
        if (!response.ok) {
            throw new Error('Failed to fetch passkey list');
        }
        
        const data = await response.json();
        updatePasskeyList(data.credentials);
        
    } catch (error) {
        console.error('Error refreshing passkey list:', error);
        showStatusMessage('Failed to refresh passkey list', 'error');
    }
}

/**
 * Update the passkey list in the UI
 * @param {Array} credentials - Array of credential objects
 */
function updatePasskeyList(credentials) {
    const credentialsList = document.querySelector('.passkey-credentials-list');
    if (!credentialsList) return;
    
    if (credentials.length === 0) {
        showNoPasskeysMessage();
        return;
    }
    
    // Generate HTML for credentials
    const credentialsHTML = credentials.map((credential, index) => `
        <div class="passkey-credential" data-credential-id="${credential.ID}">
            <div class="passkey-credential__info">
                <h3>Passkey ${index + 1}</h3>
                <div class="passkey-credential__details">
                    <p><strong>Created:</strong> ${credential.Created}</p>
                    <p><strong>Last Used:</strong> ${credential.LastUsed}</p>
                    ${credential.UserAgent ? `<p><strong>Device:</strong> ${credential.UserAgent}</p>` : ''}
                </div>
            </div>
            <div class="passkey-credential__actions">
                <button type="button" 
                        class="btn btn-danger btn-sm delete-passkey" 
                        data-credential-id="${credential.ID}"
                        data-credential-name="Passkey ${index + 1}">
                    Delete
                </button>
            </div>
        </div>
    `).join('');
    
    credentialsList.innerHTML = credentialsHTML;
    
    // Re-setup delete button handlers
    setupDeleteButtons();
}

/**
 * Enhanced startPasskeyRegistration function for management page
 */
async function startPasskeyRegistrationManagement() {
    const statusEl = document.getElementById('passkey-status');
    const registerButton = document.querySelector('.passkey-management__register .btn-primary');
    
    try {
        // Update UI
        if (registerButton) {
            registerButton.disabled = true;
            registerButton.textContent = 'Setting up...';
        }
        
        showStatusMessage('Preparing registration...', 'info');
        
        // Use the global registration function if available
        if (typeof window.startPasskeyRegistration === 'function') {
            await window.startPasskeyRegistration();
            
            // If successful, refresh the list after a short delay
            setTimeout(() => {
                window.location.reload(); // Refresh to show new passkey
            }, 2000);
            
        } else {
            throw new Error('Passkey registration function not available');
        }
        
    } catch (error) {
        console.error('Registration failed:', error);
        showStatusMessage('Registration failed: ' + error.message, 'error');
        
        // Reset button
        if (registerButton) {
            registerButton.disabled = false;
            registerButton.textContent = 'Register New Passkey';
        }
    }
}

// Export functions for global access
window.deletePasskey = deletePasskey;
window.refreshPasskeyList = refreshPasskeyList;
window.startPasskeyRegistrationManagement = startPasskeyRegistrationManagement;
