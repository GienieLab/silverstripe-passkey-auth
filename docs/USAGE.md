# Usage Guide

## Overview

This guide covers how to use the GienieLab Passkey Authentication module in your SilverStripe application, from basic setup to advanced customization.

## Quick Start

### 1. Basic Integration

After installation, the module automatically integrates with SilverStripe's login system. Users will see both password and passkey login options.

### 2. User Registration Flow

**For Users:**
1. Log in with your existing password
2. Visit `/passkey-management` or find "Manage Passkeys" in your account
3. Click "Register New Passkey"
4. Follow the browser prompts (fingerprint, face recognition, or security key)
5. Your passkey is now registered and ready to use

**For Future Logins:**
1. Visit the login page
2. Click "Sign in with Passkey" tab
3. Authenticate with your biometric or security key
4. You're logged in automatically

### 3. Admin Management

Administrators can manage all passkeys via `/admin/passkey-credentials`:

- View all registered passkeys across users
- Delete compromised or old credentials
- Monitor passkey usage statistics
- View authentication history

## Configuration

### Basic Configuration

```yaml
# app/_config/passkey-config.yml
---
Name: passkey-config
---
GienieLab\PasskeyAuth\Service\PasskeyService:
  extensions:
    - GienieLab\PasskeyAuth\Extension\PasskeyHostExtension
  rp_name: 'Your Application Name'
```

### Environment Configuration

```bash
# .env file
SS_ALLOWED_HOSTS="yourdomain.com,www.yourdomain.com,app.yourdomain.com"
```

### Advanced Configuration Options

For detailed configuration including domain-specific names, subsite integration, and MFA compatibility, see:
- [Host-Based Configuration Guide](HOST_BASED_CONFIG.md)
- [RP Name Configuration Guide](RP_NAME_CONFIGURATION.md)  
- [Optimization Guide](OPTIMIZATION_GUIDE.md)

### Security Configuration

The module includes enterprise-grade security with 6 layers of protection. For comprehensive security setup and testing:
- **[🔒 Security Guide](SECURITY.md)** - Complete security implementation
- **[🧪 Security Testing](SECURITY.md#security-testing--validation)** - Test your security layers
- **[📊 Threat Analysis](SECURITY.md#threat-mitigation-matrix)** - Understanding threats and mitigations

## Templates and Styling

### Custom Login Template

The module provides a complete login template that integrates with your existing theme. To customize:

1. **Copy the template:**
```bash
cp vendor/gienielab/silverstripe-passkey-auth/templates/Layout/Security_login.ss themes/your-theme/templates/Layout/
```

2. **Customize as needed:**
```html
<!-- Essential elements to maintain -->
<div class="login-methods">
    <button type="button" class="login-methods__tab active" data-method="password">
        Password Login
    </button>
    <button type="button" class="login-methods__tab" data-method="passkey">
        Passkey Login
    </button>
</div>

<div class="login-method login-method--passkey">
    <button type="button" class="passkey-login__button" onclick="startPasskeyLogin()">
        Sign in with Passkey
    </button>
    <div id="passkey-status"></div>
</div>

<!-- Required assets -->
<% require javascript('gienielab/silverstripe-passkey-auth:client/dist/js/passkey-auth.js') %>
<% require css('gienielab/silverstripe-passkey-auth:client/dist/css/styles.css') %>
```

### Styling Options

The module includes modern, responsive CSS with customizable themes:

```html
<!-- Include the main stylesheet -->
<% require css('gienielab/silverstripe-passkey-auth:client/dist/css/styles.css') %>
```

For advanced styling customization, see [RP Name Configuration Guide](RP_NAME_CONFIGURATION.md) which covers theme configuration.

## User Management

### Self-Service Management

Users can manage their own passkeys at `/passkey-management`:

- **View Registered Passkeys**: See all their registered devices/authenticators
- **Register New Passkeys**: Add additional devices (phone, laptop, security key)
- **Delete Old Passkeys**: Remove lost or unwanted devices
- **View Usage History**: See when and where they last used their passkeys

### Admin Management Interface

Administrators have full oversight via the admin interface:

- **Global Overview**: View all passkeys across the system
- **User-Specific Management**: Manage passkeys for specific users
- **Security Monitoring**: Track usage patterns and identify issues
- **Bulk Operations**: Remove multiple credentials if needed

## Authentication Flow

### Registration Process

1. **User Authentication**: User must be logged in with existing credentials first
2. **Passkey Registration**: User initiates passkey registration
3. **Browser Prompt**: WebAuthn API prompts for biometric/security key
4. **Credential Storage**: Passkey is securely stored in the database
5. **Confirmation**: User receives confirmation of successful registration

### Login Process

1. **Method Selection**: User chooses passkey login option
2. **Challenge Generation**: Server generates a cryptographic challenge
3. **User Authentication**: Browser prompts for registered authenticator
4. **Verification**: Server verifies the response
5. **Session Creation**: User is logged in and session is established

## Security Considerations

### HTTPS Requirement

**Passkeys require HTTPS in production.** The module allows HTTP only for development domains:
- `localhost`
- `127.0.0.1`
- `*.local`
- `*.test`
- `*.dev`

### Domain Configuration

The domain configuration must match exactly where your application is served:

```yaml
# Correct configuration
GienieLab\PasskeyAuth\Service\PasskeyService:
  domain_names:
    'myapp.com': 'My Application'
    'www.myapp.com': 'My Application'
```

### Best Practices

1. **Use Descriptive Names**: Choose clear RP names that users will recognize
2. **Regular Cleanup**: Remove old or unused passkeys periodically
3. **Monitor Usage**: Keep track of authentication patterns
4. **Backup Authentication**: Always provide alternative login methods
5. **User Education**: Help users understand how to use passkeys effectively

## Troubleshooting

### Common Issues

#### "Operation is insecure" Error
**Cause**: Domain mismatch or non-HTTPS connection
**Solution**: 
1. Check your domain configuration in `SS_ALLOWED_HOSTS`
2. Ensure HTTPS is properly configured
3. Use debug endpoints: `/passkey-auth/debug-config`

#### Passkeys Not Working on Mobile
**Cause**: Usually HTTPS, domain, or browser compatibility issues
**Solution**:
1. Verify HTTPS certificate is valid
2. Test on different browsers
3. Check that domains are properly configured

#### Registration Fails
**Cause**: Browser compatibility or configuration issues
**Solution**:
1. Test in a supported browser (Chrome, Firefox, Safari, Edge)
2. Check that WebAuthn is supported on the device
3. Verify the user isn't already registered with the same authenticator

### Debug Tools

The module provides helpful debug endpoints for development:

- `/passkey-auth/debug-config` - View current configuration and environment
- `/passkey-auth/debug-domain` - Check domain detection and validation
- **Admin Interface**: View detailed configuration in Admin > Passkey Host Config

### Browser Compatibility

| Browser | Support | Notes |
|---------|---------|-------|
| Chrome 67+ | ✅ Full | Best support, most tested |
| Firefox 60+ | ✅ Full | Good support |
| Safari 14+ | ✅ Full | iOS 14+ required for mobile |
| Edge 18+ | ✅ Full | Chromium-based versions |

## Advanced Features

### Multiple Domain Support

The module intelligently handles multiple domains using `SS_ALLOWED_HOSTS`. For detailed configuration, see [Host-Based Configuration Guide](HOST_BASED_CONFIG.md).

### Subsite Integration

For multi-site SilverStripe installations, the module provides automatic subsite awareness. See [Dynamic Subsites Documentation](DYNAMIC_SUBSITES.md).

### MFA Integration

The module is compatible with SilverStripe MFA for enterprise security requirements. See [Optimization Guide](OPTIMIZATION_GUIDE.md) for MFA setup.

### Performance Optimization

For high-traffic sites, the module includes performance optimizations and caching strategies. See [Optimization Guide](OPTIMIZATION_GUIDE.md) for details.

## API Reference

### JavaScript API

The module exposes several JavaScript functions for custom integration:

```javascript
// Start passkey login process
startPasskeyLogin()

// Register a new passkey
startPasskeyRegistration()

// Check if WebAuthn is supported
isWebAuthnSupported()

// Custom error handling
window.passkeyErrorHandler = function(error) {
    // Your custom error handling
    return 'Custom error message';
};
```

### PHP API

Key classes and methods for server-side integration:

```php
// PasskeyService - Main service class
$service = PasskeyService::create();
$challenge = $service->generateRegistrationChallenge($member);
$credential = $service->processRegistration($data, $challenge, $member);

// PasskeyCredential - Model class
$credentials = PasskeyCredential::get()->filter('MemberID', $member->ID);
$credential->updateUsage($signCount);

// Member Extension
$member = Member::currentUser();
$hasPasskeys = $member->hasPasskeyCredentials();
$credentials = $member->getPasskeyCredentials();
```

## Next Steps

- Review the [Optimization Guide](OPTIMIZATION_GUIDE.md) for production deployment
- Check [Host-Based Configuration](HOST_BASED_CONFIG.md) for advanced domain setup
- See [RP Name Configuration](RP_NAME_CONFIGURATION.md) for branding customization
- Explore [Multiple Domains Guide](MULTIPLE_DOMAINS.md) for complex setups

For development and contribution information, see the main [README.md](../README.md).
