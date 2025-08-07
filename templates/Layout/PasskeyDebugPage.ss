<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passkey Debug Information</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .debug-section { 
            background: #f5f5f5; 
            padding: 20px; 
            margin: 20px 0; 
            border-radius: 8px; 
            border-left: 4px solid #007acc;
        }
        .error { border-left-color: #dc3545; }
        .success { border-left-color: #28a745; }
        .warning { border-left-color: #ffc107; }
        pre { background: #fff; padding: 15px; border-radius: 4px; overflow-x: auto; }
        button { padding: 10px 20px; margin: 10px 5px 0 0; cursor: pointer; }
    </style>
</head>
<body>
    <h1>🔐 Passkey Debug Information</h1>
    
    <div class="debug-section">
        <h2>Current Environment</h2>
        <pre id="environment-info">Loading...</pre>
    </div>
    
    <div class="debug-section">
        <h2>WebAuthn Support</h2>
        <pre id="webauthn-support">Loading...</pre>
    </div>
    
    <div class="debug-section">
        <h2>Server Configuration Check</h2>
        <button onclick="checkServerConfig()">Check Server RP Config</button>
        <button onclick="checkDomainDetection()">Check Domain Detection</button>
        <pre id="server-config">Click button to check server configuration...</pre>
    </div>
    
    <div class="debug-section">
        <h2>Test Registration</h2>
        <button onclick="testRegistration()">Test Passkey Registration</button>
        <pre id="registration-test">Click button to test registration...</pre>
    </div>
    
    <script>
        // Base64 conversion functions
        function base64ToUint8Array(base64String) {
            const padding = '='.repeat((4 - base64String.length % 4) % 4);
            const base64 = (base64String + padding)
                .replace(/-/g, '+')
                .replace(/_/g, '/');
            
            const rawData = window.atob(base64);
            const outputArray = new Uint8Array(rawData.length);
            
            for (let i = 0; i < rawData.length; ++i) {
                outputArray[i] = rawData.charCodeAt(i);
            }
            return outputArray;
        }

        function uint8ArrayToBase64(uint8Array) {
            let binary = '';
            for (let i = 0; i < uint8Array.byteLength; i++) {
                binary += String.fromCharCode(uint8Array[i]);
            }
            return window.btoa(binary);
        }
        
        // Load environment info immediately
        document.addEventListener('DOMContentLoaded', function() {
            loadEnvironmentInfo();
            loadWebAuthnSupport();
        });
        
        function loadEnvironmentInfo() {
            const info = {
                'Current URL': window.location.href,
                'Protocol': window.location.protocol,
                'Hostname': window.location.hostname,
                'Port': window.location.port,
                'Origin': window.location.origin,
                'Is HTTPS': window.location.protocol === 'https:',
                'Is Development Domain': isDevelopmentDomain(),
                'User Agent': navigator.userAgent
            };
            
            document.getElementById('environment-info').textContent = JSON.stringify(info, null, 2);
        }
        
        function isDevelopmentDomain() {
            return window.location.hostname === 'localhost' || 
                   window.location.hostname === '127.0.0.1' || 
                   window.location.hostname.endsWith('.local') ||
                   window.location.hostname.endsWith('.test') ||
                   window.location.hostname.endsWith('.dev') ||
                   window.location.hostname.includes('localhost') ||
                   window.location.hostname.startsWith('dev.');
        }
        
        async function loadWebAuthnSupport() {
            const support = {
                'PublicKeyCredential available': !!window.PublicKeyCredential,
                'navigator.credentials available': !!navigator.credentials,
                'navigator.credentials.create available': !!navigator.credentials?.create,
                'navigator.credentials.get available': !!navigator.credentials?.get
            };
            
            if (window.PublicKeyCredential) {
                try {
                    support['Platform authenticator available'] = await PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable();
                } catch (e) {
                    support['Platform authenticator check error'] = e.message;
                }
            }
            
            document.getElementById('webauthn-support').textContent = JSON.stringify(support, null, 2);
        }
        
        async function checkServerConfig() {
            try {
                const response = await fetch('/passkey-auth/debug-config', {
                    method: 'GET',
                    credentials: 'same-origin'
                });
                
                if (response.ok) {
                    const data = await response.json();
                    document.getElementById('server-config').textContent = JSON.stringify(data, null, 2);
                } else {
                    document.getElementById('server-config').textContent = 'Error: ' + response.status + ' ' + response.statusText;
                }
            } catch (error) {
                document.getElementById('server-config').textContent = 'Error: ' + error.message;
            }
        }
        
        async function checkDomainDetection() {
            try {
                const response = await fetch('/passkey-auth/debug-domain', {
                    method: 'GET',
                    credentials: 'same-origin'
                });
                
                if (response.ok) {
                    const data = await response.json();
                    document.getElementById('server-config').textContent = 'Domain Detection:\\n' + JSON.stringify(data, null, 2);
                } else {
                    document.getElementById('server-config').textContent = 'Error: ' + response.status + ' ' + response.statusText;
                }
            } catch (error) {
                document.getElementById('server-config').textContent = 'Error: ' + error.message;
            }
        }
        
        async function testRegistration() {
            const resultEl = document.getElementById('registration-test');
            try {
                resultEl.textContent = 'Starting registration test...\\n';
                
                const response = await fetch('/passkey-auth/register-begin', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    credentials: 'same-origin'
                });
                
                if (!response.ok) {
                    const errorData = await response.json();
                    throw new Error(errorData.error || 'Failed to start registration');
                }
                
                const registrationData = await response.json();
                resultEl.textContent += 'Server registration data received:\\n' + JSON.stringify(registrationData, null, 2) + '\\n\\n';
                
                resultEl.textContent += 'About to call WebAuthn API...\\n';
                
                // Convert the server response to proper WebAuthn format
                const publicKeyOptions = registrationData.publicKey;
                const webAuthnOptions = {
                    challenge: base64ToUint8Array(publicKeyOptions.challenge),
                    rp: publicKeyOptions.rp,
                    user: {
                        id: base64ToUint8Array(publicKeyOptions.user.id),
                        name: publicKeyOptions.user.name,
                        displayName: publicKeyOptions.user.displayName
                    },
                    pubKeyCredParams: publicKeyOptions.pubKeyCredParams,
                    authenticatorSelection: publicKeyOptions.authenticatorSelection,
                    timeout: publicKeyOptions.timeout,
                    excludeCredentials: publicKeyOptions.excludeCredentials?.map(cred => ({
                        id: base64ToUint8Array(cred.id),
                        type: cred.type
                    })) || []
                };
                
                resultEl.textContent += 'Converted WebAuthn options:\\n' + JSON.stringify({
                    ...webAuthnOptions,
                    challenge: '[Uint8Array of length ' + webAuthnOptions.challenge.length + ']',
                    user: {
                        ...webAuthnOptions.user,
                        id: '[Uint8Array of length ' + webAuthnOptions.user.id.length + ']'
                    }
                }, null, 2) + '\\n\\n';
                
                // Additional debugging before WebAuthn call
                resultEl.textContent += '=== WebAuthn Security Check ===\\n';
                resultEl.textContent += 'Current origin: ' + window.location.origin + '\\n';
                resultEl.textContent += 'Current hostname: ' + window.location.hostname + '\\n';
                resultEl.textContent += 'RP ID from server: ' + webAuthnOptions.rp.id + '\\n';
                resultEl.textContent += 'RP ID matches hostname: ' + (webAuthnOptions.rp.id === window.location.hostname) + '\\n';
                resultEl.textContent += 'Is HTTPS: ' + (window.location.protocol === 'https:') + '\\n';
                resultEl.textContent += 'Document domain: ' + document.domain + '\\n';
                resultEl.textContent += 'Browser: ' + navigator.userAgent.split(')')[0] + ')' + '\\n';
                resultEl.textContent += '\\n';
                
                // Check if we need to set document.domain
                if (webAuthnOptions.rp.id !== window.location.hostname) {
                    resultEl.textContent += 'WARNING: RP ID mismatch detected!\\n';
                    resultEl.textContent += 'This will cause SecurityError: The operation is insecure\\n';
                    resultEl.textContent += 'Server should return RP ID: ' + window.location.hostname + '\\n\\n';
                }
                
                // Firefox-specific debugging
                if (navigator.userAgent.includes('Firefox')) {
                    resultEl.textContent += 'Firefox detected - checking for known issues:\\n';
                    resultEl.textContent += '- Firefox requires exact RP ID match\\n';
                    resultEl.textContent += '- Firefox may be stricter about development domains\\n';
                    resultEl.textContent += '- Consider testing in Chrome/Edge to compare\\n\\n';
                }
                
                // Try to log the exact WebAuthn call that will be made
                resultEl.textContent += 'Exact navigator.credentials.create call:\\n';
                resultEl.textContent += 'navigator.credentials.create({\\n';
                resultEl.textContent += '  publicKey: {\\n';
                resultEl.textContent += '    challenge: Uint8Array(' + webAuthnOptions.challenge.length + '),\\n';
                resultEl.textContent += '    rp: { name: "' + webAuthnOptions.rp.name + '", id: "' + webAuthnOptions.rp.id + '" },\\n';
                resultEl.textContent += '    user: { id: Uint8Array(' + webAuthnOptions.user.id.length + '), name: "' + webAuthnOptions.user.name + '", displayName: "' + webAuthnOptions.user.displayName + '" },\\n';
                resultEl.textContent += '    pubKeyCredParams: [...],\\n';
                resultEl.textContent += '    authenticatorSelection: ' + JSON.stringify(webAuthnOptions.authenticatorSelection) + ',\\n';
                resultEl.textContent += '    timeout: ' + webAuthnOptions.timeout + '\\n';
                resultEl.textContent += '  }\\n';
                resultEl.textContent += '})\\n\\n';
                
                // This is where the "operation is insecure" error typically occurs
                const credential = await navigator.credentials.create({
                    publicKey: webAuthnOptions
                });
                
                resultEl.textContent += 'SUCCESS: WebAuthn registration completed!\\n';
                resultEl.textContent += JSON.stringify(credential, null, 2);
                
            } catch (error) {
                resultEl.textContent += 'ERROR: ' + error.name + ': ' + error.message + '\\n';
                resultEl.textContent += 'Stack: ' + error.stack;
            }
        }
    </script>
</body>
</html>
