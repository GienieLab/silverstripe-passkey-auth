# SilverStripe Passkey Authentication Module

[![Latest Stable Version](https://poser.pugx.org/gienielab/silverstripe-passkey-auth/v/stable)](https://packagist.org/packages/gienielab/silverstripe-passkey-auth)
[![Total Downloads](https://poser.pugx.org/gienielab/silverstripe-passkey-auth/downloads)](https://packagist.org/packages/gienielab/silverstripe-passkey-auth)
[![License](https://poser.pugx.org/gienielab/silverstripe-passkey-auth/license)](https://packagist.org/packages/gienielab/silverstripe-passkey-auth)

A comprehensive passkey (WebAuthn) authentication module for SilverStripe that provides secure, passwordless authentication using biometrics, security keys, or device authentication.

## Features

- 🔐 **Secure Authentication**: Uses WebAuthn standard for robust security
- 👆 **Multiple Authentication Methods**: Fingerprint, face recognition, security keys
- 🔄 **Dual Login System**: Works alongside traditional password authentication  
- 📱 **Cross-Platform**: Works on desktop and mobile devices
- 🛠️ **Admin Management**: Full admin interface for credential management
- 👤 **User Self-Service**: Users can manage their own passkeys
- 🎨 **Customizable Templates**: Easy to integrate with existing themes
- 🔒 **Enterprise Ready**: Production-tested with comprehensive error handling

## Requirements

- SilverStripe ^5.0 || ^6.0
- PHP ^8.3
- HTTPS enabled (required for WebAuthn)
- Modern browser with WebAuthn support

## Installation

### 1. Install via Composer

```bash
composer require gienielab/silverstripe-passkey-auth
```

### 2. Run Database Build

```bash
vendor/bin/sake dev/build flush=1
```

### 3. Configure Your Application

Create `app/_config/passkey.yml`:

```yaml
---
Name: passkey-config
---
GienieLab\PasskeyAuth\Service\PasskeyService:
  rp_name: 'Your Application Name'  # The name shown to users
  rp_id: 'yourdomain.com'          # Your domain (without protocol)
  timeout: 60000                   # Timeout in milliseconds (60 seconds)
  require_user_verification: true  # Require biometric/PIN verification
  require_user_presence: true      # Require user interaction
```

### 4. Add Extensions

Create `app/_config/extensions.yml`:

```yaml
---
Name: passkey-extensions
---
SilverStripe\Security\Member:
  extensions:
    - GienieLab\PasskeyAuth\Extension\MemberPasskeyExtension

SilverStripe\Security\Security:
  extensions:
    - GienieLab\PasskeyAuth\Extension\SecurityExtension
```

### 5. Configure Routes

Add to your `app/_config/routes.yml`:

```yaml
---
Name: passkey-routes
After: 'framework/routes#coreroutes'
---
SilverStripe\Control\Director:
  rules:
    'passkey-auth': 'GienieLab\PasskeyAuth\Controller\PasskeyAuthController'
    'passkey-management': 'GienieLab\PasskeyAuth\Controller\PasskeyManagementController'
```

## Configuration Options

### Basic Configuration

```yaml
GienieLab\PasskeyAuth\Service\PasskeyService:
  # Required: Your application name (shown in browser prompts)
  rp_name: 'My Application'
  
  # Required: Your domain (must match the domain serving your app)
  rp_id: 'myapp.com'
  
  # Optional: Authentication timeout (default: 60000ms)
  timeout: 60000
  
  # Optional: Require user verification like PIN/biometric (default: true)
  require_user_verification: true
  
  # Optional: Require user presence/interaction (default: true)
  require_user_presence: true
```

### Environment-Specific Configuration

For different environments, you can use:

```yaml
---
Name: passkey-config-dev
Only:
  environment: 'dev'
---
GienieLab\PasskeyAuth\Service\PasskeyService:
  rp_id: 'localhost'
  rp_name: 'My App (Development)'

---
Name: passkey-config-live
Only:
  environment: 'live'
---
GienieLab\PasskeyAuth\Service\PasskeyService:
  rp_id: 'myapp.com'
  rp_name: 'My Application'
```

## Usage

### 1. Basic Integration

The module automatically integrates with SilverStripe's login system. Users will see both password and passkey login options.

### 2. Custom Login Template

To customize the login interface, copy the provided template and modify it:

```bash
# Copy the template to your theme
cp vendor/gienielab/silverstripe-passkey-auth/templates/Layout/Security_login.ss themes/your-theme/templates/Layout/
```

Then customize the template as needed. The key elements are:

```html
<!-- Login method tabs -->
<div class="login-methods">
    <button type="button" class="login-methods__tab active" data-method="password">
        Password Login
    </button>
    <button type="button" class="login-methods__tab" data-method="passkey">
        Passkey Login
    </button>
</div>

<!-- Passkey login section -->
<div class="login-method login-method--passkey">
    <button type="button" class="passkey-login__button" onclick="startPasskeyLogin()">
        Sign in with Passkey
    </button>
    <div id="passkey-status"></div>
</div>

<!-- Required JavaScript -->
<% require javascript('gienielab/silverstripe-passkey-auth:client/dist/js/passkey-auth.js') %>
```

### 3. Custom Styling

The module provides separate CSS files for different components:

```html
<!-- For login pages -->
<% require css('gienielab/silverstripe-passkey-auth:client/dist/css/passkey-login.css') %>

<!-- For management pages -->
<% require css('gienielab/silverstripe-passkey-auth:client/dist/css/passkey-management.css') %>
```

You can override these styles in your own CSS files or customize the SCSS source files:

```scss
// Override login styles
.passkey-login__button {
    background: your-brand-color;
    border-radius: your-border-radius;
}

// Override management styles  
.passkey-management {
    max-width: your-preferred-width;
}
```

### 4. User Management

Users can manage their passkeys at `/passkey-management`:

- View registered passkeys
- Delete old or unwanted passkeys  
- Register new passkeys
- See usage history

### 5. Admin Management

Access via `/admin/passkey-credentials` to:

- View all registered passkeys across users
- Delete compromised or old credentials
- Monitor passkey usage statistics

## Authentication Flow

### Registration Flow

1. User must be logged in with password first
2. User clicks "Register Passkey" 
3. Browser prompts for biometric/security key
4. Credential is stored securely in database
5. User can now use passkey for future logins

### Login Flow

1. User visits login page
2. User selects "Passkey Login"
3. Browser prompts for registered authenticator
4. User authenticates with biometric/security key
5. User is logged in automatically

## Security Considerations

### HTTPS Requirement

**Passkeys require HTTPS in production.** The module allows HTTP only for:
- `localhost`
- `127.0.0.1`
- `*.local`
- `*.test`
- `*.dev`

### Domain Configuration

The `rp_id` must exactly match your domain:

```yaml
# Correct
GienieLab\PasskeyAuth\Service\PasskeyService:
  rp_id: 'myapp.com'  # If serving from https://myapp.com

# Incorrect  
GienieLab\PasskeyAuth\Service\PasskeyService:
  rp_id: 'https://myapp.com'  # Don't include protocol
  rp_id: 'www.myapp.com'      # Don't include subdomain unless specific
```

## Troubleshooting

### Common Issues

#### "Operation is insecure" Error

**Cause**: Domain mismatch between `rp_id` configuration and actual domain.

**Solution**: 
1. Check your `rp_id` configuration matches exactly
2. Ensure you're using HTTPS (except localhost)
3. Use debug endpoints: `/passkey-auth/debug-config`

#### Passkeys Not Working on Mobile

**Cause**: Usually HTTPS or domain configuration issues.

**Solution**:
1. Verify HTTPS is properly configured
2. Test the debug endpoints
3. Check browser compatibility

### Debug Endpoints

In development mode, access these debug URLs:

- `/passkey-auth/debug-config` - Configuration and environment info
- `/passkey-auth/debug-domain` - Domain detection information

## Development

### Prerequisites

- Node.js (v18 or higher)
- Yarn package manager
- PHP ^8.1

### Building Assets

The module uses Webpack for modern asset compilation with comprehensive optimization:

```bash
# Install dependencies
cd passkey-auth/
yarn install

# Production build (minified, optimized)
yarn build

# Development build (with source maps)
yarn build:dev

# Watch for changes and auto-rebuild
yarn watch

# Development server with hot reload
yarn dev

# Clean build directory
yarn clean

# Lint JavaScript and SCSS
yarn lint

# Run tests
yarn test

# Analyze bundle size
yarn analyze
```

### Build Features

- **Modern ES6+ Support**: Babel transpilation for browser compatibility
- **SCSS Compilation**: Advanced Sass features with PostCSS optimization
- **Code Splitting**: Automatic optimization for better performance
- **Source Maps**: Full debugging support in development
- **Asset Optimization**: Minification, tree-shaking, and compression
- **Hot Module Replacement**: Instant development feedback
- **Browser Prefixing**: Automatic vendor prefixes via Autoprefixer

### Project Structure

```
passkey-auth/
├── _config/
│   ├── config.yml              # Module configuration
│   └── routes.yml              # URL routing
├── client/
│   ├── src/
│   │   ├── js/
│   │   │   ├── passkey-auth.js          # Main authentication JavaScript
│   │   │   └── passkey-management.js    # User management functionality
│   │   └── scss/
│   │       ├── passkey-login.scss       # Login page styles
│   │       └── passkey-management.scss  # Management page styles
│   └── dist/                   # Built assets (auto-generated)
│       ├── css/
│       │   ├── passkey-login.css
│       │   └── passkey-management.css
│       └── js/
│           ├── passkey-auth.js
│           └── passkey-management.js
├── src/
│   ├── Admin/
│   │   └── PasskeyCredentialAdmin.php
│   ├── Controller/
│   │   ├── PasskeyAuthController.php
│   │   └── PasskeyManagementController.php
│   ├── Extension/
│   │   ├── MemberPasskeyExtension.php
│   │   └── SecurityExtension.php
│   ├── Model/
│   │   └── PasskeyCredential.php
│   └── Service/
│       └── PasskeyService.php
└── templates/
    ├── Layout/
    │   └── Security_login.ss
    └── PasskeyManagement.ss
```

## API Reference

### PasskeyService

Main service class for WebAuthn operations:

```php
// Generate registration challenge
$challenge = $passkeyService->generateRegistrationChallenge($member);

// Process registration
$credential = $passkeyService->processRegistration($data, $challenge, $member);

// Generate authentication challenge  
$challenge = $passkeyService->generateAuthenticationChallenge();

// Process authentication
$member = $passkeyService->processAuthentication($data, $challenge);
```

### PasskeyCredential Model

Database model for storing passkey credentials:

```php
// Properties
$credential->CredentialID;  // Unique credential identifier
$credential->PublicKey;     // Base64-encoded public key
$credential->AAGUID;        // Authenticator AAGUID
$credential->SignCount;     // Signature counter for replay protection
$credential->LastUsed;      // Last authentication timestamp
$credential->Member();      // Associated Member record

// Methods
$credential->updateUsage($signCount);  // Update usage statistics
$credential->getTitle();               // Human-readable description
```

### Member Extension

```php
// Check if a user has passkeys
$member = Member::currentUser();
if ($member && $member->hasPasskeyCredentials()) {
    // User has passkeys registered
}

// Get passkey credentials for a user
$credentials = $member->getPasskeyCredentials();

// Remove a specific passkey
$member->removePasskeyCredential($credentialId);
```

## Customization

### Custom Login Templates

To override the login template, copy it to your theme:

```bash
cp vendor/gienielab/silverstripe-passkey-auth/templates/Layout/Security_login.ss themes/your-theme/templates/Layout/
```

Key template elements to maintain:

```html
<!-- Method switching tabs -->
<div class="login-methods">
    <button class="login-methods__tab" data-method="password">Password</button>
    <button class="login-methods__tab" data-method="passkey">Passkey</button>
</div>

<!-- Passkey authentication -->
<div class="login-method login-method--passkey">
    <button onclick="startPasskeyLogin()">Sign in with Passkey</button>
    <div id="passkey-status"></div>
</div>

<!-- Required assets -->
<% require javascript('gienielab/silverstripe-passkey-auth:client/dist/js/passkey-auth.js') %>
<% require css('gienielab/silverstripe-passkey-auth:client/dist/css/passkey-login.css') %>
```

### JavaScript Files

The module provides two main JavaScript files:

```html
<!-- Core authentication functionality (required for all passkey features) -->
<% require javascript('gienielab/silverstripe-passkey-auth:client/dist/js/passkey-auth.js') %>

<!-- User management functionality (only needed for management pages) -->
<% require javascript('gienielab/silverstripe-passkey-auth:client/dist/js/passkey-management.js') %>
```

### Custom Error Messages

You can customize error messages by overriding the JavaScript error handling:

```javascript
// Add this after including passkey-auth.js
window.passkeyErrorHandler = function(error) {
    // Your custom error handling
    if (error.name === 'NotAllowedError') {
        return 'Please try again or use your password to sign in.';
    }
    return error.message;
};
```

## Browser Compatibility

| Browser | Support | Notes |
|---------|---------|-------|
| Chrome 67+ | ✅ Full | Best support |
| Firefox 60+ | ✅ Full | Good support |
| Safari 14+ | ✅ Full | iOS 14+ required |
| Edge 18+ | ✅ Full | Chromium-based |

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests for new functionality
5. Submit a pull request

## License

This module is released under the MIT license. See LICENSE file for details.

## Credits

Built using:
- [web-auth/webauthn-lib](https://github.com/web-auth/webauthn-lib) - WebAuthn server library
- [SilverStripe Framework](https://silverstripe.org) - CMS/Framework
- WebAuthn specification by [W3C](https://w3c.github.io/webauthn/)
│   ├── src/                    # Source files
│   │   ├── js/
│   │   │   └── passkey-auth.js # WebAuthn JavaScript logic
│   │   └── scss/
│   │       └── passkey-login.scss # Passkey login styles
│   └── dist/                   # Compiled files
│       ├── js/
│       └── css/
├── src/                        # PHP source files
├── _config/                    # SilverStripe configuration
├── templates/                  # SilverStripe templates
├── package.json               # NPM configuration
└── composer.json             # Composer configuration
```

### Asset Deployment

Built assets are automatically copied to the theme directory:
- CSS: `themes/marmalade/dist/styles/passkey-login.css`
- JS: `themes/marmalade/dist/js/passkey-auth.js`

This ensures they're available via the `_resources` URL structure.

## Configuration

Configure the passkey service in your `_config.yml`:

```yaml
GienieLab\PasskeyAuth\Service\PasskeyService:
  rp_name: 'Your Site Name'
  rp_id: 'yoursite.com'
  timeout: 60
  require_user_verification: true
  require_user_presence: true
```

## Usage

1. Navigate to `/Security/login` to see the dual authentication form
2. Users can switch between password and passkey login methods
3. Passkey registration can be handled through the member management interface

## Browser Support

- Chrome 67+
- Firefox 60+
- Safari 14+
- Edge 18+

Requires HTTPS in production environments.
