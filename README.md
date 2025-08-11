# SilverStripe Passkey Authentication Module

[![Latest Stable Version](https://poser.pugx.org/gienielab/silverstripe-passkey-auth/v/stable)](https://packagist.org/packages/gienielab/silverstripe-passkey-auth)
[![Total Downloads](https://poser.pugx.org/gienielab/silverstripe-passkey-auth/downloads)](https://packagist.org/packages/gienielab/silverstripe-passkey-auth)
[![License](https://poser.pugx.org/gienielab/silverstripe-passkey-auth/license)](https://packagist.org/packages/gienielab/silverstripe-passkey-auth)

A comprehensive passkey (WebAuthn) authentication module for SilverStripe that provides secure, passwordless authentication using biometrics, security keys, or device authentication.

## ✨ Features

- 🔐 **Enterprise Security**: 6-layer security protection with comprehensive threat mitigation
- 👆 **Multiple Authentication Methods**: Fingerprint, face recognition, security keys
- 🔄 **Dual Login System**: Works alongside traditional password authentication  
- 📱 **Cross-Platform**: Works on desktop and mobile devices
- 🛡️ **Advanced Protection**: Rate limiting, CSRF protection, bot filtering, comprehensive logging
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
- **🔒 [Security Guide](docs/SECURITY.md)** - Multi-layer security implementation
- **⚙️ [Configuration](docs/HOST_BASED_CONFIG.md)** - Detailed configuration options
- **🎨 [Theming & Customization](docs/RP_NAME_CONFIGURATION.md)** - Styling and branding
- **📑 [Full Documentation Index](docs/README.md)** - All available documentation

### Quick Links
- **[🔧 Advanced Configuration](docs/OPTIMIZATION_GUIDE.md)** - Performance, MFA, subsites
- **🏗️ [Multiple Domains Setup](docs/MULTIPLE_DOMAINS.md)** - Complex domain configurations
- **🔍 [Troubleshooting](docs/USAGE.md#troubleshooting)** - Common issues and solutions

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

### Enterprise-Grade Security
The module implements **6 layers of security protection**:

1. **🛡️ User-Agent Filtering** - Blocks bots and automated attacks
2. **⚡ Rate Limiting** - Prevents brute force attacks (5 req/hour default)
3. **📏 Request Size Limits** - Stops payload attacks (1KB limit)
4. **🌐 Origin Validation** - Prevents CSRF attacks
5. **🔑 CSRF Token Protection** - Secures state-changing operations
6. **📊 Comprehensive Logging** - Monitors all security events

**See [Security Guide](docs/SECURITY.md) for complete implementation details and testing.**

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
