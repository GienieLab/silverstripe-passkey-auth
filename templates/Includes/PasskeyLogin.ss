<!-- Passkey Login Method -->
<div class="login-method login-method--passkey">
    <div class="passkey-section">
        <div class="passkey-header">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="passkey-header__icon">
                <path d="M2 12a10 10 0 0 1 18-6"/>
                <path d="M2 16h.01"/>
                <path d="M21.8 16c.2-2 .131-5.354 0-6"/>
                <path d="M5 19.5C5.5 18 6 15 6 12a6 6 0 0 1 .34-2"/>
                <path d="M8.65 22c.21-.66.45-1.32.57-2"/>
                <path d="M9 6.8a6 6 0 0 1 9 5.2v2"/>
                <path d="M12 10a2 2 0 0 0-2 2c0 1.02-.1 2.51-.26 4"/>
                <path d="M14 13.12c0 2.38 0 6.38-1 8.88"/>
                <path d="M17.29 21.02c.12-.6.43-2.3.5-3.02"/>
            </svg>
            <h3 class="passkey-header__title">Sign in with Passkey</h3>
        </div>
        
        <p class="passkey-description">
            Use your fingerprint, face, or security key to sign in securely and instantly.
        </p>
        
        <button type="button" class="passkey-cta" onclick="startPasskeyLogin()">
            <div class="passkey-cta__content">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="passkey-cta__icon">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/>
                    <path d="m9 12 2 2 4-4"/>
                    <circle cx="12" cy="8" r="1"/>
                </svg>
                <span class="passkey-cta__text">Continue with Passkey</span>
            </div>
            <div class="passkey-cta__loading" style="display: none;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" class="passkey-cta__spinner">
                    <path d="M12,1A11,11,0,1,0,23,12,11,11,0,0,0,12,1Zm0,19a8,8,0,1,1,8-8A8,8,0,0,1,12,20Z" opacity=".25"/>
                    <path d="M10.14,1.16a11,11,0,0,0-9,8.92A1.59,1.59,0,0,0,2.46,12,1.52,1.52,0,0,0,4.11,10.7a8,8,0,0,1,6.66-6.61A1.42,1.42,0,0,0,12,2.69h0A1.57,1.57,0,0,0,10.14,1.16Z"/>
                </svg>
                <span class="passkey-cta__text">Authenticating...</span>
            </div>
        </button>
        
        <div class="passkey-login__status" id="passkey-status"></div>
    </div>
</div>
