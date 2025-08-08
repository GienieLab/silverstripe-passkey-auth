# SilverStripe Passkey Authentication Module

[![Latest Stable Version](https://poser.pugx.org/gienielab/silverstripe-passkey-auth/v/stable)](https://packagist.org/packages/gienielab/silverstripe-passkey-auth)
[![Total Downloads](https://poser.pugx.org/gienielab/silverstripe-passkey-auth/downloads)](https://packagist.org/packages/gienielab/silverstripe-passkey-auth)
[![License](https://poser.pugx.org/gienielab/silverstripe-passkey-auth/license)](https://packagist.org/packages/gienielab/silverstripe-passkey-auth)

A comprehensive passkey (WebAuthn) authentication module for SilverStripe that provides secure, passwordless authentication using biometrics, security keys, or device authentication.

## ✨ Features

- 🔐 **Secure Authentication**: Uses WebAuthn standard for robust security
- 👆 **Multiple Authentication Methods**: Fingerprint, face recognition, security keys
- 🔄 **Dual Login System**: Works alongside traditional password authentication  
- 📱 **Cross-Platform**: Works on desktop and mobile devices
- 🛠️ **Admin Management**: Full admin interface for credential management
- 👤 **User Self-Service**: Users can manage their own passkeys
- 🎨 **Customizable Styling**: YAML-based theming and modern CSS
- 🏢 **Enterprise Ready**: MFA compatibility, subsites support, performance optimized

## 📋 Requirements

- SilverStripe ^5.0 || ^6.0
- PHP ^8.3
- HTTPS enabled (required for WebAuthn)
- Modern browser with WebAuthn support

## 🚀 Quick Installation

### 1. Install via Composer

```bash
composer require gienielab/silverstripe-passkey-auth
```

### 2. Run Database Build

```bash
vendor/bin/sake dev/build flush=1
```

### 3. Configure Domains

Add to your `.env` file:

```bash
SS_ALLOWED_HOSTS="yourdomain.com,www.yourdomain.com"
```

### 4. Basic Configuration

Create `app/_config/passkey-config.yml`:

```yaml
---
Name: passkey-config
---
GienieLab\PasskeyAuth\Service\PasskeyService:
  extensions:
    - GienieLab\PasskeyAuth\Extension\PasskeyHostExtension
  rp_name: 'Your Application Name'
```

That's it! 🎉 Your passkey authentication is ready to use.

## 📚 Documentation

### Essential Guides
- **[📖 Usage Guide](docs/USAGE.md)** - Complete user and admin guide
- **⚙️ Configuration](docs/HOST_BASED_CONFIG.md)** - Detailed configuration options
- **🎨 Theming & Customization](docs/RP_NAME_CONFIGURATION.md)** - Styling and branding
- **📑 Full Documentation Index](docs/README.md)** - All available documentation

### Quick Links
- **[🔧 Advanced Configuration](docs/OPTIMIZATION_GUIDE.md)** - Performance, MFA, subsites
- **🏗️ Multiple Domains Setup](docs/MULTIPLE_DOMAINS.md)** - Complex domain configurations
- **🔍 Troubleshooting](docs/USAGE.md#troubleshooting)** - Common issues and solutions

## 🎯 How It Works

### For Users
1. **Register**: Log in with password, then register your passkey (fingerprint/face/security key)
2. **Login**: Next time, just click "Sign in with Passkey" and authenticate
3. **Manage**: Visit `/passkey-management` to add/remove passkeys

### For Admins
- **Overview**: Visit `/admin/passkey-credentials` to manage all passkeys
- **Configuration**: Check `/admin/passkey-hosts` for domain setup
- **Monitoring**: Track usage and security across your organization

## 🔒 Security & Browser Support

### HTTPS Required
Passkeys require HTTPS in production. Localhost and development domains are automatically allowed.

### Browser Compatibility
| Browser | Support | Notes |
|---------|---------|-------|
| Chrome 67+ | ✅ Full | Best support |
| Firefox 60+ | ✅ Full | Good support |
| Safari 14+ | ✅ Full | iOS 14+ required |
| Edge 18+ | ✅ Full | Chromium-based |

## 🛠️ Development

### Prerequisites
- Node.js (v18+)
- Yarn
- PHP ^8.3

### Building Assets

```bash
# Install dependencies
yarn install

# Production build
yarn build

# Development with watch
yarn watch

# Development server
yarn dev
```

### Build Features
- **Modern ES6+ Support**: Babel transpilation
- **SCSS Compilation**: PostCSS optimization
- **Code Splitting**: Performance optimization
- **Source Maps**: Development debugging
- **Hot Module Replacement**: Instant feedback

### Project Structure

```
├── _config/           # SilverStripe configuration
├── client/            # Frontend assets
│   ├── src/          # Source files (JS/SCSS)
│   └── dist/         # Built assets
├── docs/             # Documentation
├── src/              # PHP source files
│   ├── Admin/        # Admin interfaces
│   ├── Controller/   # Controllers
│   ├── Extension/    # Extensions
│   ├── Model/        # Data models
│   └── Service/      # Core services
└── templates/        # SilverStripe templates
```

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests for new functionality
5. Submit a pull request

See [Development Documentation](docs/USAGE.md#development--contribution) for detailed guidelines.

## 📄 License

This module is released under the MIT license. See [LICENSE](LICENSE) file for details.

## 🙏 Credits

Built with:
- [web-auth/webauthn-lib](https://github.com/web-auth/webauthn-lib) - WebAuthn server library
- [SilverStripe Framework](https://silverstripe.org) - CMS/Framework
- WebAuthn specification by [W3C](https://w3c.github.io/webauthn/)

## 🆘 Support

- **📖 Documentation**: [Complete documentation](docs/README.md)
- **🐛 Bug Reports**: [GitHub Issues](https://github.com/gienielab/silverstripe-passkey-auth/issues)
- **💬 Questions**: [SilverStripe Community](https://forum.silverstripe.org)
- **🔒 Security Issues**: Email maintainers directly

---

**Ready to get started?** Check out the [Usage Guide](docs/USAGE.md) for detailed instructions! 🚀
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
