# Passkey Authentication Module - Optimization Guide

## Overview

This guide covers the comprehensive optimizations implemented in the GienieLab Passkey Authentication module, including code efficiency improvements, customizable styling, MFA compatibility, and subsites integration.

## 1. Code Efficiency Optimizations

### Performance Improvements (OptimizedPasskeyService)

The module includes an optimized version of `PasskeyService` with the following enhancements:

- **Memory Management**: Efficient handling of large credential datasets
- **Caching Strategy**: Smart caching of frequently accessed data
- **Database Optimization**: Reduced database queries through efficient filtering
- **Error Handling**: Improved error recovery and logging

#### Usage Example:
```yaml
# Use optimized service
GienieLab\PasskeyAuth\Service\PasskeyService:
  class: 'GienieLab\PasskeyAuth\Service\OptimizedPasskeyService'
```

### Database Performance

Execute the provided SQL indexes for optimal database performance:

```sql
-- Performance indexes for passkey credentials
CREATE INDEX IF NOT EXISTS idx_passkey_user_id ON PasskeyCredential(UserID);
CREATE INDEX IF NOT EXISTS idx_passkey_credential_id ON PasskeyCredential(CredentialID);
CREATE INDEX IF NOT EXISTS idx_passkey_last_used ON PasskeyCredential(LastUsedAt);
CREATE INDEX IF NOT EXISTS idx_passkey_created ON PasskeyCredential(Created);
```

## 2. Customizable Styling System

### YAML-Based Theme Configuration

The module supports YAML-based color customization through `theme.yml`:

```yaml
# app/_config/passkey-theme.yml
---
Name: custom-passkey-theme
After: 
  - '#gienielab-passkey-theme'
---

GienieLab\PasskeyAuth\Service\ThemeService:
  theme_config:
    primary_color: '#0066cc'
    secondary_color: '#f8f9fa'
    accent_color: '#28a745'
    danger_color: '#dc3545'
    text_color: '#333333'
    background_color: '#ffffff'
    border_radius: '8px'
    font_family: 'Inter, -apple-system, BlinkMacSystemFont, sans-serif'
```

### Modern CSS Integration

The `ThemeService` generates CSS custom properties that can be used in templates:

```html
<!-- Automatic CSS variables injection -->
<style type="text/css">
:root {
  --passkey-primary: #0066cc;
  --passkey-secondary: #f8f9fa;
  --passkey-accent: #28a745;
  /* ... more variables */
}
</style>
```

### Template Integration

The Security_login.ss template includes modern CSS classes and responsive design:

- **Method Switching**: Tabbed interface for login methods
- **Loading States**: Visual feedback during authentication
- **Dark Mode Support**: CSS variables adapt to system preferences
- **Mobile Responsive**: Optimized for all screen sizes

## 3. MFA (Multi-Factor Authentication) Compatibility

### SilverStripe MFA Integration

The module is fully compatible with `silverstripe/mfa` through the `PasskeyMFAMethod` class:

#### Features:
- **Method Registration**: Passkeys can be registered as MFA methods
- **Verification Handler**: Secure passkey verification within MFA flow
- **Backup Method Support**: Optional backup method configuration
- **Priority System**: Configurable priority among MFA methods

#### Configuration:
```yaml
# Enable MFA integration
SilverStripe\MFA\Service\RegisteredMethodManager:
  methods:
    passkey: 'GienieLab\PasskeyAuth\MFA\PasskeyMFAMethod'

GienieLab\PasskeyAuth\MFA\PasskeyMFAMethod:
  is_backup_method: false
  priority: 100
```

### Implementation Details

The MFA integration includes:
- `PasskeyMFAMethod`: Main MFA method implementation
- `PasskeyMFARegisterHandler`: Registration flow handler
- `PasskeyMFAVerifyHandler`: Verification flow handler

## 4. Domain & Subsites Integration

### Host-Based Domain Management (Recommended)

The module uses `SS_ALLOWED_HOSTS` for domain management, which is simpler and more reliable:

#### Features:
- **Single Source of Truth**: Uses `SS_ALLOWED_HOSTS` that you need anyway
- **Zero Configuration**: No YAML needed for multiple domains  
- **Automatic Detection**: Uses currently accessed domain as RP ID
- **Security Validated**: Checks against SilverStripe's allowed hosts
- **Works Everywhere**: Main site, subsites, development, production

#### Simple Configuration:
```bash
# .env file - handles all RP IDs automatically
SS_ALLOWED_HOSTS="example.com,www.example.com,shop.example.com,api.example.com"
```

```yaml
# Single YAML config with flexible RP name options
GienieLab\PasskeyAuth\Service\PasskeyService:
  extensions:
    - GienieLab\PasskeyAuth\Extension\PasskeyHostExtension
  # Option 1: Global RP name for all domains
  rp_name: 'My Company'
  # Option 2: Domain-specific RP names  
  domain_names:
    'example.com': 'Main Website'
    'shop.example.com': 'Online Store'
    'api.example.com': 'API Service'
```

#### How It Works:
1. **Set SS_ALLOWED_HOSTS**: Add all your domains to the environment variable
2. **Configure RP Names**: Choose from global, domain-specific, subsite titles, or auto-generated
3. **Enable Extension**: Add `PasskeyHostExtension` to your config
4. **Done**: Passkey authentication works automatically on all domains!

#### RP Name Configuration Options:
- **Global Name**: One name for all domains (`rp_name: 'My Company'`)
- **Domain-Specific**: Different names per domain (`domain_names` mapping)
- **Subsite Integration**: Uses subsite titles automatically
- **Auto-Generated**: Smart domain-to-name conversion as fallback

For detailed RP name configuration, see [RP Name Configuration Guide](RP_NAME_CONFIGURATION.md).

#### Multiple Domain Benefits:
- **Smart RP ID Detection**: Uses the currently accessed domain as RP ID
- **WebAuthn Compliance**: Ensures proper WebAuthn domain binding  
- **No Duplication**: No need to configure domains in multiple places
- **Environment Aware**: Different domains for dev/staging/production

For detailed information, see [Host-Based Configuration Guide](HOST_BASED_CONFIG.md).

#### Admin Interface:
Navigate to **Admin > Passkey Host Config** to view current domain configuration.

### Legacy Subsite Support

The module still supports the older subsite-specific approach for backward compatibility:

#### Dynamic Subsite Configuration:
```yaml
# Legacy approach - only use if you can't use host-based
SilverStripe\Subsites\Model\Subsite:
  extensions:
    - GienieLab\PasskeyAuth\Extensions\SubsitePasskeyExtension
```

For legacy subsite configuration, see [Dynamic Subsites Documentation](DYNAMIC_SUBSITES.md).

### Implementation Components

- `PasskeyHostExtension`: Domain detection from `SS_ALLOWED_HOSTS` (recommended)
- `PasskeyCredentialSubsiteExtension`: Credential isolation for subsites
- `PasskeyHostConfigAdmin`: Visual configuration overview
- `SubsitePasskeyExtension`: Legacy subsite-specific configuration

### Example Configuration Results

```bash
# Environment Configuration
SS_ALLOWED_HOSTS="example.com,portal.example.com,shop.example.com"

# Automatic Results:
User visits example.com → RP ID: "example.com" | RP Name: "Main Site"
User visits portal.example.com → RP ID: "portal.example.com" | RP Name: "Portal Site"  
User visits shop.example.com → RP ID: "shop.example.com" | RP Name: "Shop Site"
```

## 5. Installation and Configuration

### Step 1: Install Dependencies

```bash
composer require gienielab/silverstripe-passkey-auth
```

### Step 2: Run Database Build

```bash
vendor/bin/sake dev/build flush=1
```

### Step 3: Configure Theme (Optional)

Create `app/_config/passkey-theme.yml` with your custom colors.

### Step 4: Enable Optimizations

```yaml
# app/_config/passkey-optimizations.yml
GienieLab\PasskeyAuth\Service\PasskeyService:
  class: 'GienieLab\PasskeyAuth\Service\OptimizedPasskeyService'
```

### Step 5: Configure MFA (If Using silverstripe/mfa)

```yaml
# app/_config/mfa-config.yml
SilverStripe\MFA\Service\RegisteredMethodManager:
  methods:
    passkey: 'GienieLab\PasskeyAuth\MFA\PasskeyMFAMethod'
```

### Step 6: Configure Domains (Recommended: Host-Based)

```bash
# .env file
SS_ALLOWED_HOSTS="example.com,www.example.com,shop.example.com"
```

```yaml
# app/_config/domain-config.yml
GienieLab\PasskeyAuth\Service\PasskeyService:
  extensions:
    - GienieLab\PasskeyAuth\Extension\PasskeyHostExtension
```

### Step 7: Configure Subsites (If Using silverstripe/subsites)

```yaml
# app/_config/subsites-config.yml (optional)
GienieLab\PasskeyAuth\Model\PasskeyCredential:
  extensions:
    - GienieLab\PasskeyAuth\Extensions\PasskeyCredentialSubsiteExtension
```

## 6. Troubleshooting

### Common Issues

1. **Styling Not Applied**: Ensure `ThemeService` is properly configured and templates are using vendor asset paths
2. **MFA Not Working**: Verify `silverstripe/mfa` module is installed and configured
3. **Subsites Issues**: Check that `silverstripe/subsites` module is properly set up
4. **Performance Issues**: Apply the database indexes from `sql/performance_indexes.sql`

### Debug Mode

Enable debug logging in your environment:

```yaml
GienieLab\PasskeyAuth\Service\PasskeyService:
  debug_mode: true
```

## 7. Best Practices

1. **Theme Customization**: Use YAML configuration for colors rather than CSS overrides
2. **Performance**: Apply database indexes in production environments
3. **Security**: Regularly update passkey credentials and monitor usage
4. **MFA Setup**: Use passkeys as primary method with traditional backup methods
5. **Subsites**: Test credential isolation thoroughly in multi-site environments

## 8. API Reference

### ThemeService Methods
- `getThemeConfig()`: Returns current theme configuration
- `generateCSSProperties()`: Generates CSS custom properties
- `getModernStyles()`: Returns modern CSS styles

### OptimizedPasskeyService Methods
- All standard PasskeyService methods with performance optimizations
- `clearCache()`: Manual cache clearing
- `getPerformanceStats()`: Performance monitoring

### MFA Integration
- `PasskeyMFAMethod::register()`: Register passkey as MFA method
- `PasskeyMFAMethod::verify()`: Verify passkey in MFA flow

### Subsites Integration
- `SubsitePasskeyExtension::getRPIDForSubsite()`: Get RP ID for specific subsite
- `PasskeyCredentialSubsiteExtension::filterBySubsite()`: Filter credentials by subsite

This comprehensive optimization guide ensures your passkey authentication module is production-ready with modern features, excellent performance, and extensive compatibility.
