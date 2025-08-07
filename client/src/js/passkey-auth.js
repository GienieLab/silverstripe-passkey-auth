/**
 * Passkey Authentication Module
 * Modern ES6+ implementation for WebAuthn passkey authentication
 */

// Utility functions for base64 conversion
export const base64Utils = {
  /**
   * Convert base64 string to Uint8Array
   * @param {string} base64String - Base64 encoded string
   * @returns {Uint8Array} - Decoded byte array
   */
  toUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding)
      .replace(/-/g, '+')
      .replace(/_/g, '/');
    
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);
    
    for (let i = 0; i < rawData.length; i += 1) {
      outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
  },

  /**
   * Convert Uint8Array to base64 string
   * @param {Uint8Array} uint8Array - Byte array to encode
   * @returns {string} - Base64 encoded string
   */
  fromUint8Array(uint8Array) {
    let binary = '';
    for (let i = 0; i < uint8Array.byteLength; i += 1) {
      binary += String.fromCharCode(uint8Array[i]);
    }
    return window.btoa(binary);
  }
};

/**
 * Check if HTTPS is available (required for WebAuthn)
 * @returns {boolean} - True if HTTPS or localhost
 */
export function checkHttpsRequirement() {
  // Allow localhost, 127.0.0.1, and common development domains for testing
  const isLocalhost = location.hostname === 'localhost' 
                   || location.hostname === '127.0.0.1' 
                       location.hostname.endsWith('.local') ||
                       location.hostname.endsWith('.test') ||
                       location.hostname.endsWith('.dev') ||
                       location.hostname.includes('localhost') ||
                       location.hostname.startsWith('dev.');
    
    if (!isLocalhost && location.protocol !== 'https:') {
        throw new Error('Passkeys require HTTPS. Please use a secure connection.');
    }
}

async function startPasskeyLogin() {
    const statusEl = document.getElementById('passkey-status');
    const passkeyDataField = document.querySelector('input[name="PasskeyData"]');
    
    try {
        if (statusEl) {
            statusEl.textContent = 'Preparing authentication...';
            statusEl.className = 'passkey-login__status passkey-login__status--loading';
        }
        
        // Check HTTPS requirement
        checkHttpsRequirement();
        
        // Check if WebAuthn is supported
        if (!window.PublicKeyCredential) {
            throw new Error('WebAuthn is not supported in this browser');
        }
        
        // Request authentication challenge from server
        const challengeResponse = await fetch('/passkey-auth/challenge', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'same-origin'
        });
        
        if (!challengeResponse.ok) {
            const errorData = await challengeResponse.json();
            
            // Check if the error is due to no registered passkeys
            if (challengeResponse.status === 400 && errorData.error && errorData.error.includes('No passkeys are registered')) {
                if (statusEl) {
                    statusEl.textContent = 'No passkeys found. Would you like to register one?';
                    statusEl.className = 'passkey-login__status passkey-login__status--warning';
                }
                
                // Show registration option
                showPasskeyRegistrationOption();
                return;
            }
            
            throw new Error(errorData.error || 'Failed to get authentication challenge');
        }
        
        const challengeData = await challengeResponse.json();
        
        if (statusEl) {
            statusEl.textContent = 'Touch your authenticator...';
        }
        
        // Start WebAuthn authentication
        const credential = await navigator.credentials.get({
            publicKey: {
                challenge: base64Utils.toUint8Array(challengeData.challenge),
                allowCredentials: challengeData.allowCredentials.map(cred => ({
                    id: base64Utils.toUint8Array(cred.id),
                    type: cred.type
                })),
                userVerification: 'preferred',
                timeout: 60000
            }
        });
        
        if (!credential) {
            throw new Error('Authentication was cancelled');
        }
        
        // Prepare the authentication data
        const authData = {
            id: credential.id,
            rawId: base64Utils.fromUint8Array(new Uint8Array(credential.rawId)),
            response: {
                authenticatorData: base64Utils.fromUint8Array(new Uint8Array(credential.response.authenticatorData)),
                clientDataJSON: base64Utils.fromUint8Array(new Uint8Array(credential.response.clientDataJSON)),
                signature: base64Utils.fromUint8Array(new Uint8Array(credential.response.signature)),
                userHandle: credential.response.userHandle ? base64Utils.fromUint8Array(new Uint8Array(credential.response.userHandle)) : null
            },
            type: credential.type
        };
        
        // Set the passkey data and submit the form
        if (passkeyDataField) {
            passkeyDataField.value = JSON.stringify(authData);
        }
        
        if (statusEl) {
            statusEl.textContent = 'Verifying authentication...';
        }
        
        // Send authentication data to server
        const finishResponse = await fetch('/passkey-auth/login-finish', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'same-origin',
            body: JSON.stringify(authData)
        });
        
        if (!finishResponse.ok) {
            const errorData = await finishResponse.json();
            throw new Error(errorData.error || 'Authentication verification failed');
        }
        
        const result = await finishResponse.json();
        
        if (statusEl) {
            statusEl.textContent = 'Authentication successful! Redirecting...';
            statusEl.className = 'passkey-login__status passkey-login__status--success';
        }
        
        // Redirect to the specified URL or reload the page
        if (result.redirectURL) {
            window.location.href = result.redirectURL;
        } else {
            window.location.reload();
        }
        
        // // Submit the form
        // const form = document.querySelector('form');
        // if (form) {
        //     form.submit();
        // }
        
    } catch (error) {
        console.error('Passkey authentication failed:', error);
        
        let userFriendlyMessage = 'Authentication failed: ';
        
        // Handle specific WebAuthn errors with user-friendly messages
        if (error.name === 'NotAllowedError') {
            userFriendlyMessage += 'You cancelled the authentication or the operation timed out. Please try again.';
        } else if (error.name === 'AbortError') {
            userFriendlyMessage += 'Authentication was cancelled. Please try again.';
        } else if (error.name === 'TimeoutError') {
            userFriendlyMessage += 'Authentication timed out. Please try again.';
        } else if (error.name === 'SecurityError') {
            userFriendlyMessage += 'Security error. Please make sure you\'re using a secure connection.';
        } else if (error.name === 'NotSupportedError') {
            userFriendlyMessage += 'Your device or browser doesn\'t support this type of authentication.';
        } else if (error.message.includes('operation is insecure')) {
            userFriendlyMessage += 'Authentication is not available on this connection. Please use HTTPS.';
        } else if (error.message.includes('timed out') || error.message.includes('not allowed')) {
            userFriendlyMessage += 'You cancelled the authentication or it timed out. Please try again.';
        } else {
            userFriendlyMessage += error.message;
        }
        
        if (statusEl) {
            statusEl.textContent = userFriendlyMessage;
            statusEl.className = 'passkey-login__status passkey-login__status--error';
        }
    }
}

function showPasskeyRegistrationOption() {
    const passkeySection = document.querySelector('.login-method--passkey');
    if (!passkeySection) return;
    
    // Create registration interface
    const registrationHTML = `
        <div class="passkey-registration">
            <div class="passkey-icon">🔐</div>
            <h3>Register Your First Passkey</h3>
            <p class="passkey-description">
                Set up a passkey to sign in quickly and securely using your fingerprint, face, or security key.
            </p>
            <div class="passkey-registration-steps">
                <p><strong>First, sign in with your password to register a passkey:</strong></p>
                <button type="button" class="passkey-switch-to-password" onclick="switchToPasswordMethod()">
                    Sign in with Password First
                </button>
            </div>
            <div id="passkey-status" class="passkey-login__status"></div>
        </div>
    `;
    
    passkeySection.innerHTML = registrationHTML;
}

async function startPasskeyRegistration() {
    const statusEl = document.getElementById('passkey-status');
    let publicKeyOptions = null; // Define in broader scope for error handling
    
    try {
        if (statusEl) {
            statusEl.textContent = 'Preparing registration...';
            statusEl.className = 'passkey-login__status passkey-login__status--loading';
        }
        
        // Check HTTPS requirement with detailed logging
        console.log('=== HTTPS and WebAuthn Support Check ===');
        console.log('Current URL:', window.location.href);
        console.log('Protocol:', window.location.protocol);
        console.log('Hostname:', window.location.hostname);
        console.log('Is HTTPS:', window.location.protocol === 'https:');
        console.log('Is localhost/dev domain:', 
            window.location.hostname === 'localhost' || 
            window.location.hostname === '127.0.0.1' || 
            window.location.hostname.endsWith('.local') ||
            window.location.hostname.endsWith('.test') ||
            window.location.hostname.endsWith('.dev') ||
            window.location.hostname.includes('localhost')
        );
        
        checkHttpsRequirement();
        
        // Check if WebAuthn is supported with detailed info
        console.log('WebAuthn Support Check:');
        console.log('- window.PublicKeyCredential exists:', !!window.PublicKeyCredential);
        console.log('- navigator.credentials exists:', !!navigator.credentials);
        console.log('- navigator.credentials.create exists:', !!navigator.credentials.create);
        
        if (!window.PublicKeyCredential) {
            throw new Error('WebAuthn is not supported in this browser');
        }
        
        // Additional WebAuthn availability check
        try {
            const available = await PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable();
            console.log('Platform authenticator available:', available);
        } catch (e) {
            console.log('Could not check platform authenticator availability:', e);
        }
        
        // Request registration challenge from server
        const registrationResponse = await fetch('/passkey-auth/register-begin', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'same-origin'
        });
        
        if (!registrationResponse.ok) {
            const errorData = await registrationResponse.json();
            throw new Error(errorData.error || 'Failed to start registration');
        }
        
        const registrationData = await registrationResponse.json();
        
        // Start WebAuthn registration
        // The registrationData contains a publicKey object with the actual WebAuthn parameters
        publicKeyOptions = registrationData.publicKey || registrationData;
        
        // Debug log the registration data structure
        console.log('Registration data received:', registrationData);
        console.log('Current location:', location.href);
        console.log('Current hostname:', location.hostname);
        console.log('Current protocol:', location.protocol);
        console.log('PublicKey options:', publicKeyOptions);
        
        if (statusEl) {
            statusEl.textContent = 'Touch your authenticator to register...';
        }
        
        // Add detailed logging before WebAuthn create call
        console.log('=== About to call navigator.credentials.create ===');
        console.log('RP info:', publicKeyOptions.rp);
        console.log('Current origin:', window.location.origin);
        console.log('Does RP ID match current domain?', 
            publicKeyOptions.rp.id === window.location.hostname ||
            window.location.hostname.includes(publicKeyOptions.rp.id)
        );
        
        const webAuthnOptions = {
            challenge: base64Utils.toUint8Array(publicKeyOptions.challenge),
            rp: publicKeyOptions.rp,
            user: {
                id: base64Utils.toUint8Array(publicKeyOptions.user.id),
                name: publicKeyOptions.user.name,
                displayName: publicKeyOptions.user.displayName
            },
            pubKeyCredParams: publicKeyOptions.pubKeyCredParams,
            authenticatorSelection: publicKeyOptions.authenticatorSelection,
            timeout: publicKeyOptions.timeout,
            excludeCredentials: publicKeyOptions.excludeCredentials?.map(cred => ({
                id: base64Utils.toUint8Array(cred.id),
                type: cred.type
            })) || []
        };
        
        console.log('Final WebAuthn options:', webAuthnOptions);
        
        try {
            console.log('Calling navigator.credentials.create...');
            const credential = await navigator.credentials.create({
                publicKey: webAuthnOptions
            });
            console.log('WebAuthn create successful!', credential);
            
            if (!credential) {
                throw new Error('Registration was cancelled');
            }
            
            // Prepare the registration data
            const regData = {
                id: credential.id,
                rawId: base64Utils.fromUint8Array(new Uint8Array(credential.rawId)),
                response: {
                    attestationObject: base64Utils.fromUint8Array(new Uint8Array(credential.response.attestationObject)),
                    clientDataJSON: base64Utils.fromUint8Array(new Uint8Array(credential.response.clientDataJSON))
                },
                type: credential.type
            };
            
            // Send registration data to server
            const finishResponse = await fetch('/passkey-auth/register-finish', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify(regData)
            });
            
            if (!finishResponse.ok) {
                const errorData = await finishResponse.json();
                throw new Error(errorData.error || 'Failed to complete registration');
            }
            
            if (statusEl) {
                statusEl.textContent = 'Passkey registered successfully! You can now use it to sign in.';
                statusEl.className = 'passkey-login__status passkey-login__status--success';
            }
            
            // Optionally redirect or refresh the form
            setTimeout(() => {
                window.location.reload();
            }, 2000);
            
        } catch (createError) {
            console.error('=== WebAuthn Create Error ===');
            console.error('Error name:', createError.name);
            console.error('Error message:', createError.message);
            console.error('Error stack:', createError.stack);
            console.error('Current origin:', window.location.origin);
            console.error('RP ID from server:', publicKeyOptions.rp?.id);
            console.error('RP name from server:', publicKeyOptions.rp?.name);
            throw createError;
        }
        
    } catch (error) {
        console.error('Passkey registration failed:', error);
        console.error('Error name:', error.name);
        console.error('Error message:', error.message);
        console.error('Error stack:', error.stack);
        console.error('Current origin:', window.location.origin);
        console.error('RP info from server:', publicKeyOptions?.rp);
        
        let userFriendlyMessage = 'Registration failed: ';
        
        // Handle specific WebAuthn errors with user-friendly messages
        if (error.name === 'NotAllowedError') {
            userFriendlyMessage += 'You cancelled the registration or the operation timed out. Please try again.';
        } else if (error.name === 'AbortError') {
            userFriendlyMessage += 'Registration was cancelled. Please try again.';
        } else if (error.name === 'TimeoutError') {
            userFriendlyMessage += 'Registration timed out. Please try again.';
        } else if (error.name === 'SecurityError') {
            userFriendlyMessage += 'Security error. Please make sure you\'re using a secure connection.';
        } else if (error.name === 'NotSupportedError') {
            userFriendlyMessage += 'Your device or browser doesn\'t support passkey registration.';
        } else if (error.name === 'InvalidStateError') {
            userFriendlyMessage += 'A passkey for this account already exists on this device.';
        } else if (error.message.includes('operation is insecure')) {
            userFriendlyMessage += 'Registration is not available on this connection. Please use HTTPS.';
        } else if (error.message.includes('timed out') || error.message.includes('not allowed')) {
            userFriendlyMessage += 'You cancelled the registration or it timed out. Please try again.';
        } else if (error.message.includes('domain/origin')) {
            userFriendlyMessage += 'Domain configuration error. Please contact support.';
        } else {
            userFriendlyMessage += error.message;
        }
        
        if (statusEl) {
            statusEl.textContent = userFriendlyMessage;
            statusEl.className = 'passkey-login__status passkey-login__status--error';
        }
    }
}

function switchToPasswordMethod() {
    const passwordTab = document.querySelector('.login-methods__tab[data-method="password"]');
    if (passwordTab) {
        passwordTab.click();
    }
}

// Login method switching
document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('.login-methods__tab');
    const methods = document.querySelectorAll('.login-method');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const method = this.getAttribute('data-method');
            
            // Update active tab
            tabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
            // Update active method
            methods.forEach(m => m.classList.remove('active'));
            const targetMethod = document.querySelector(`.login-method--${method}`);
            if (targetMethod) {
                targetMethod.classList.add('active');
            }
            
            // Update form field requirements
            const emailField = document.querySelector('input[name="Email"]');
            const passwordField = document.querySelector('input[name="Password"]');
            
            if (method === 'password') {
                if (emailField && passwordField) {
                    emailField.required = true;
                    passwordField.required = true;
                }
            } else if (method === 'passkey') {
                if (emailField && passwordField) {
                    emailField.required = false;
                    passwordField.required = false;
                }
            }
        });
    });
    
    // Check if user is on the member profile page and add passkey registration option
    if (window.location.pathname.includes('/admin/myprofile') || window.location.pathname.includes('/member/profile')) {
        addPasskeyRegistrationToProfile();
    }
    
    // Check if we should show post-login passkey registration prompt
    checkForPostLoginPasskeyPrompt();
    
    // Setup form submission handler for all forms on the page
    setupFormSubmissionHandler();
});

// Setup form submission handler
function setupFormSubmissionHandler() {
    // Look for any form that might be the login form
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        // Check if this form has email and password fields (likely a login form)
        const emailField = form.querySelector('input[name="Email"]');
        const passwordField = form.querySelector('input[name="Password"]');
        
        if (emailField && passwordField) {
            console.log('Found login form, setting up handler');
            form.addEventListener('submit', handlePasswordLogin);
        }
    });
}

// Handle password login to set up post-login passkey registration
function handlePasswordLogin(event) {
    console.log('Password login form submitted');
    
    const emailField = event.target.querySelector('input[name="Email"]');
    const passwordField = event.target.querySelector('input[name="Password"]');
    const passkeyDataField = event.target.querySelector('input[name="PasskeyData"]');
    
    // Check if password method is active (not passkey method)
    const activeTab = document.querySelector('.login-methods__tab.active');
    const isPasswordLogin = !activeTab || activeTab.getAttribute('data-method') === 'password';
    
    // Only proceed if this is a password login (not passkey)
    if (isPasswordLogin && (!passkeyDataField || !passkeyDataField.value)) {
        if (emailField && passwordField && emailField.value && passwordField.value) {
            console.log('Setting post-login flag for:', emailField.value);
            // Store flag for post-login passkey prompt
            sessionStorage.setItem('showPasskeyRegistrationPrompt', 'true');
            sessionStorage.setItem('loginEmail', emailField.value);
        }
    }
}

// Check for post-login passkey registration prompt
function checkForPostLoginPasskeyPrompt() {
    console.log('Checking for post-login passkey prompt...');
    const shouldShowPrompt = sessionStorage.getItem('showPasskeyRegistrationPrompt');
    const loginEmail = sessionStorage.getItem('loginEmail');
    
    console.log('shouldShowPrompt:', shouldShowPrompt, 'loginEmail:', loginEmail);
    
    if (shouldShowPrompt === 'true' && loginEmail) {
        console.log('Showing post-login passkey prompt for:', loginEmail);
        // Clear the flags
        sessionStorage.removeItem('showPasskeyRegistrationPrompt');
        sessionStorage.removeItem('loginEmail');
        
        // Wait a bit for the page to fully load, then show prompt
        setTimeout(() => {
            showPostLoginPasskeyPrompt(loginEmail);
        }, 1000);
    }
}

// Show post-login passkey registration prompt
function showPostLoginPasskeyPrompt(email) {
    // Check if user already has passkeys registered
    fetch('/passkey-auth/challenge', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        credentials: 'same-origin'
    })
    .then(response => {
        if (response.status === 400) {
            // No passkeys registered, show prompt
            showPasskeyRegistrationModal(email);
        }
        // If response is OK, user already has passkeys, don't prompt
    })
    .catch(error => {
        console.log('Could not check passkey status:', error);
    });
}

// Show passkey registration modal
function showPasskeyRegistrationModal(email) {
    // Create modal overlay
    const modalOverlay = document.createElement('div');
    modalOverlay.className = 'passkey-modal-overlay';
    modalOverlay.innerHTML = `
        <div class="passkey-modal">
            <div class="passkey-modal-header">
                <h2>🔐 Secure Your Account</h2>
                <button class="passkey-modal-close" onclick="closePasskeyModal()">&times;</button>
            </div>
            <div class="passkey-modal-content">
                <p>Welcome! You've successfully logged in with your password.</p>
                <p><strong>Would you like to set up a passkey for faster, more secure sign-ins?</strong></p>
                
                <div class="passkey-setup-options">
                    <h3>Setup Options:</h3>
                    <div class="passkey-option">
                        <span class="passkey-option-icon">📱</span>
                        <div>
                            <strong>This Device</strong>
                            <p>Use fingerprint, face recognition, or PIN on this device</p>
                        </div>
                    </div>
                    <div class="passkey-option">
                        <span class="passkey-option-icon">🔑</span>
                        <div>
                            <strong>Security Key</strong>
                            <p>Use a physical security key (USB/NFC)</p>
                        </div>
                    </div>
                    <div class="passkey-option">
                        <span class="passkey-option-icon">�</span>
                        <div>
                            <strong>Cross-Device</strong>
                            <p>Some browsers may show a QR code to set up on another device</p>
                        </div>
                    </div>
                </div>
                
                <div class="passkey-info">
                    <p><strong>Note:</strong> The exact setup process depends on your browser and device. You might see:</p>
                    <ul>
                        <li>A QR code to scan with your phone</li>
                        <li>A prompt to use your device's built-in authentication</li>
                        <li>An option to use a security key</li>
                    </ul>
                </div>
                
                <p class="passkey-modal-email">Setting up passkey for: <strong>${email}</strong></p>
            </div>
            <div class="passkey-modal-actions">
                <button class="passkey-modal-button passkey-modal-button--primary" onclick="startModalPasskeyRegistration()">
                    Set Up Passkey
                </button>
                <button class="passkey-modal-button passkey-modal-button--secondary" onclick="closePasskeyModal()">
                    Maybe Later
                </button>
            </div>
            <div id="passkey-modal-status" class="passkey-modal-status"></div>
        </div>
    `;
    
    document.body.appendChild(modalOverlay);
    
    // Animate in
    setTimeout(() => {
        modalOverlay.classList.add('active');
    }, 10);
}

// Close passkey modal
function closePasskeyModal() {
    const modal = document.querySelector('.passkey-modal-overlay');
    if (modal) {
        modal.classList.remove('active');
        setTimeout(() => {
            modal.remove();
        }, 300);
    }
}

// Start passkey registration from modal
async function startModalPasskeyRegistration() {
    const statusEl = document.getElementById('passkey-modal-status');
    const primaryButton = document.querySelector('.passkey-modal-button--primary');
    
    if (primaryButton) {
        primaryButton.disabled = true;
        primaryButton.textContent = 'Setting up...';
    }
    
    try {
        if (statusEl) {
            statusEl.textContent = 'Preparing registration...';
            statusEl.className = 'passkey-modal-status passkey-modal-status--loading';
        }
        
        // Check HTTPS requirement
        checkHttpsRequirement();
        
        // Check if WebAuthn is supported
        if (!window.PublicKeyCredential) {
            throw new Error('WebAuthn is not supported in this browser');
        }
        
        // Request registration challenge from server
        const registrationResponse = await fetch('/passkey-auth/register-begin', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'same-origin'
        });
        
        if (!registrationResponse.ok) {
            const errorData = await registrationResponse.json();
            throw new Error(errorData.error || 'Failed to start registration');
        }
        
        const registrationData = await registrationResponse.json();
        
        if (statusEl) {
            statusEl.textContent = 'Touch your authenticator to register...';
        }
        
        // Start WebAuthn registration
        const credential = await navigator.credentials.create({
            publicKey: {
                challenge: new Uint8Array(registrationData.challenge),
                rp: registrationData.rp,
                user: {
                    id: new Uint8Array(registrationData.user.id),
                    name: registrationData.user.name,
                    displayName: registrationData.user.displayName
                },
                pubKeyCredParams: registrationData.pubKeyCredParams,
                authenticatorSelection: registrationData.authenticatorSelection,
                timeout: registrationData.timeout,
                excludeCredentials: registrationData.excludeCredentials?.map(cred => ({
                    id: new Uint8Array(cred.id),
                    type: cred.type
                })) || []
            }
        });
        
        if (!credential) {
            throw new Error('Registration was cancelled');
        }
        
        // Prepare the registration data
        const regData = {
            id: credential.id,
            rawId: base64Utils.fromUint8Array(new Uint8Array(credential.rawId)),
            response: {
                attestationObject: base64Utils.fromUint8Array(new Uint8Array(credential.response.attestationObject)),
                clientDataJSON: base64Utils.fromUint8Array(new Uint8Array(credential.response.clientDataJSON))
            },
            type: credential.type
        };
        
        // Send registration data to server
        const finishResponse = await fetch('/passkey-auth/register-finish', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'same-origin',
            body: JSON.stringify(regData)
        });
        
        if (!finishResponse.ok) {
            const errorData = await finishResponse.json();
            throw new Error(errorData.error || 'Failed to complete registration');
        }
        
        if (statusEl) {
            statusEl.textContent = '✅ Passkey registered successfully! You can now use it to sign in.';
            statusEl.className = 'passkey-modal-status passkey-modal-status--success';
        }
        
        // Auto-close modal after success
        setTimeout(() => {
            closePasskeyModal();
        }, 3000);
        
    } catch (error) {
        console.error('Passkey registration failed:', error);
        if (statusEl) {
            statusEl.textContent = 'Registration failed: ' + error.message;
            statusEl.className = 'passkey-modal-status passkey-modal-status--error';
        }
        
        if (primaryButton) {
            primaryButton.disabled = false;
            primaryButton.textContent = 'Try Again';
        }
    }
}

// Add passkey registration option to member profile pages
function addPasskeyRegistrationToProfile() {
    const profileForm = document.querySelector('.cms-edit-form') || document.querySelector('form');
    if (!profileForm) return;
    
    const passkeySection = document.createElement('div');
    passkeySection.className = 'passkey-profile-section';
    passkeySection.innerHTML = `
        <h3>Passkey Security</h3>
        <p>Set up a passkey to sign in quickly and securely using your fingerprint, face, or security key.</p>
        <button type="button" class="passkey-register-button" onclick="startPasskeyRegistration()">
            <span>🔐</span>
            Register New Passkey
        </button>
        <div id="passkey-profile-status" class="passkey-login__status"></div>
    `;
    
    profileForm.appendChild(passkeySection);
}

// Global functions for backward compatibility
window.startPasskeyLogin = startPasskeyLogin;
window.startPasskeyRegistration = startPasskeyRegistration;
window.switchToPasswordMethod = switchToPasswordMethod;

// Legacy function names for backward compatibility
window.base64ToUint8Array = base64Utils.toUint8Array;
window.uint8ArrayToBase64 = base64Utils.fromUint8Array;