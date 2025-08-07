<% require javascript('gienielab/silverstripe-passkey-auth:client/dist/js/passkey-auth.js') %>
<% require css('gienielab/silverstripe-passkey-auth:client/dist/css/passkey-register-styles.css') %>

<div class="security-page-wrapper">
    <div class="passkey-registration-container">
        <div class="passkey-registration-card">
            <div class="passkey-icon-large">🔐</div>
            
            <h1>Secure Your Account</h1>
            <p class="subtitle">Set up a passkey for faster, more secure sign-ins</p>
            
            <% if $Message %>
                <div class="message {$MessageType}">
                    {$Message}
                </div>
            <% end_if %>

            <div class="passkey-registration-content">
                <p>Welcome! You've successfully logged in with your password.</p>
                <p><strong>Would you like to set up a passkey for faster, more secure sign-ins?</strong></p>
                
                <div class="passkey-benefits">
                    <h3>With a passkey, you can sign in using:</h3>
                    <ul>
                        <li>🔒 Your fingerprint</li>
                        <li>👤 Face recognition</li>
                        <li>🔑 Security key</li>
                        <li>📱 Device PIN</li>
                    </ul>
                </div>
                
                <% if $CurrentUser %>
                    <p class="passkey-email">Setting up passkey for: <strong>$CurrentUser.Email</strong></p>
                <% end_if %>
            </div>

            <div class="passkey-registration-actions">
                <button type="button" class="passkey-button passkey-button--primary" onclick="startPasskeyRegistration()">
                    <span class="passkey-icon">🔐</span>
                    Set Up Passkey Now
                </button>
                
                <button type="button" class="passkey-button passkey-button--secondary" onclick="skipPasskeyRegistration()">
                    Maybe Later
                </button>
            </div>
            
            <div id="passkey-status" class="passkey-registration-status"></div>
        </div>
    </div>
</div>

<script>
// Function to skip passkey registration and go to original destination
function skipPasskeyRegistration() {
    // Get the stored BackURL or default to admin
    window.location.href = '$PostPasskeyRegistrationURL';
}

// Override the registration success to redirect properly
function onPasskeyRegistrationSuccess() {
    // Redirect to the original destination after successful registration
    setTimeout(() => {
        window.location.href = '$PostPasskeyRegistrationURL';
    }, 2000);
}

// Make the functions available globally
window.skipPasskeyRegistration = skipPasskeyRegistration;
window.onPasskeyRegistrationSuccess = onPasskeyRegistrationSuccess;
</script>
