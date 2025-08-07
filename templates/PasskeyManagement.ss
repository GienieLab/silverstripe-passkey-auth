<div class="passkey-management">
    <div class="container">
        <h1>Manage Your Passkeys</h1>
        
        <% if $Message %>
            <div class="alert alert-{$MessageType}">
                {$Message}
            </div>
        <% end_if %>
        
        <div class="passkey-management__intro">
            <p>Passkeys are a secure way to sign in using your fingerprint, face, or security key. 
            You can manage your registered passkeys below.</p>
        </div>

        <div class="passkey-management__actions">
            <h2>Your Passkeys</h2>
            
            <% if $PasskeyCredentials %>
                <div class="passkey-credentials-list">
                    <% loop $PasskeyCredentials %>
                        <div class="passkey-credential" data-credential-id="{$ID}">
                            <div class="passkey-credential__info">
                                <h3>Passkey {$Pos}</h3>
                                <div class="passkey-credential__details">
                                    <p><strong>Created:</strong> {$Created.Nice}</p>
                                    <% if $LastUsed %>
                                        <p><strong>Last Used:</strong> {$LastUsed.Nice}</p>
                                    <% else %>
                                        <p><strong>Last Used:</strong> Never</p>
                                    <% end_if %>
                                    <% if $UserAgent %>
                                        <p><strong>Device:</strong> {$UserAgent}</p>
                                    <% end_if %>
                                </div>
                            </div>
                            <div class="passkey-credential__actions">
                                <button type="button" 
                                        class="btn btn-danger btn-sm delete-passkey" 
                                        data-credential-id="{$ID}"
                                        data-credential-name="Passkey {$Pos}">
                                    Delete
                                </button>
                            </div>
                        </div>
                    <% end_loop %>
                </div>
            <% else %>
                <div class="alert alert-info">
                    <h4>No Passkeys Registered</h4>
                    <p>You haven't registered any passkeys yet. You can register a new passkey below.</p>
                </div>
            <% end_if %>
        </div>

        <div class="passkey-management__register">
            <h2>Register New Passkey</h2>
            <p>Add a new passkey to your account for secure sign-in.</p>
            
            <button type="button" class="btn btn-primary" onclick="startPasskeyRegistration()">
                <span class="passkey-icon">🔐</span>
                Register New Passkey
            </button>
            
            <div id="passkey-status" class="passkey-status mt-3"></div>
        </div>

        <div class="passkey-management__help">
            <h2>Need Help?</h2>
            <div class="help-sections">
                <div class="help-section">
                    <h3>What are Passkeys?</h3>
                    <p>Passkeys are a more secure replacement for passwords. They use your device's built-in security 
                    (like fingerprint or face recognition) to authenticate you.</p>
                </div>
                
                <div class="help-section">
                    <h3>When should I delete a passkey?</h3>
                    <ul>
                        <li>When you no longer have access to the device</li>
                        <li>If you think the passkey might be compromised</li>
                        <li>When replacing an old device</li>
                    </ul>
                </div>
                
                <div class="help-section">
                    <h3>What happens if I delete all my passkeys?</h3>
                    <p>You can still sign in using your regular password. You can then register new passkeys 
                    if you want to continue using passkey authentication.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<%-- Include webpack-compiled assets --%>
<% require javascript('gienielab/silverstripe-passkey-auth:client/dist/js/passkey-auth.js') %>
<% require javascript('gienielab/silverstripe-passkey-auth:client/dist/js/passkey-management.js') %>
<% require css('gienielab/silverstripe-passkey-auth:client/dist/css/styles.css') %>
