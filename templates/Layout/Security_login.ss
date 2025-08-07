<% require javascript('gienielab/silverstripe-passkey-auth:client/dist/js/passkey-auth.js') %>
<% require css('gienielab/silverstripe-passkey-auth:client/dist/css/styles.css') %>
<div class="security-page-wrapper">
    <div class="passkey-login-container">
        <div class="passkey-login-card">
            <div class="logo">
                <!-- Add your logo here if needed -->
            </div>
            
            <h1>Welcome Back</h1>
            <p class="subtitle">Choose your preferred sign-in method</p>
            
            <% if $Message %>
                <div class="message {$MessageType}">
                    {$Message}
                </div>
            <% end_if %>

            <div class="login-methods">
                    <button type="button" class="login-methods__tab active" data-method="password">
                        Password Login
                    </button>
                    <button type="button" class="login-methods__tab" data-method="passkey">
                        Passkey Login
                    </button>
                </div>

                <!-- Password Login Method -->
                <div class="login-method login-method--password active">
                    $Form
                </div>

                <!-- Passkey Login Method -->
                <div class="login-method login-method--passkey">
                    <div class="passkey-icon">🔐</div>
                    <p class="passkey-description">
                        Use your fingerprint, face, or security key to sign in securely.
                    </p>
                    <button type="button" class="passkey-login__button" onclick="startPasskeyLogin()">
                        <span class="passkey-login__icon">👆</span>
                        Sign in with Passkey
                    </button>
                    <div class="passkey-login__status" id="passkey-status"></div>
                </div>

                <!-- Hidden fields -->
                <% loop $Fields %>
                    <% if $Name == "PasskeyData" %>
                        $FieldHolder
                    <% end_if %>
                <% end_loop %>


            <div class="form-actions">
                <a href="/Security/lostpassword">Forgot your password?</a>
            </div>
        </div>
    </div>
</div>


