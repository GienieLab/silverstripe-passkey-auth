# Documentation Index

## Quick Links

- **[📦 Installation & Setup](../README.md)** - Get started quickly
- **[📖 Usage Guide](USAGE.md)** - How to use the module day-to-day
- **[🔒 Security Guide](SECURITY.md)** - Multi-layer security implementation
- **[⚙️ Configuration Guide](HOST_BASED_CONFIG.md)** - Complete configuration reference

## Core Documentation

### Getting Started
- **[📦 README](../README.md)** - Installation, requirements, and quick setup
- **[📖 Usage Guide](USAGE.md)** - Complete user and admin guide
- **[🔒 Security Guide](SECURITY.md)** - Enterprise security features and testing
- **[🌐 Browser Compatibility](USAGE.md#browser-compatibility)** - Supported browsers and versions

### Configuration Guides
- **[🏠 Host-Based Configuration](HOST_BASED_CONFIG.md)** - Recommended approach using SS_ALLOWED_HOSTS
- **[🏷️ RP Name Configuration](RP_NAME_CONFIGURATION.md)** - Flexible naming strategies for different domains
- **[🌍 Dynamic Subsites](DYNAMIC_SUBSITES.md)** - Legacy subsite-specific configuration
- **[🌐 Multiple Domains Guide](MULTIPLE_DOMAINS.md)** - Handling complex multi-domain setups

### Advanced Features
- **[⚡ Optimization Guide](OPTIMIZATION_GUIDE.md)** - Performance, theming, MFA, and subsites
- **[🔐 MFA Integration](OPTIMIZATION_GUIDE.md#mfa-compatibility)** - SilverStripe MFA compatibility
- **[🚀 Performance Optimization](OPTIMIZATION_GUIDE.md#code-efficiency-optimizations)** - Database indexes and caching
- **[🎨 Custom Theming](OPTIMIZATION_GUIDE.md#customizable-styling-system)** - YAML-based color customization

### Security & Best Practices
- **[🔒 Security Implementation](SECURITY.md)** - Complete security guide with 6-layer protection
- **[🧪 Security Testing](SECURITY.md#security-testing--validation)** - Test your security layers
- **[📊 Threat Analysis](SECURITY.md#threat-mitigation-matrix)** - Threat mitigation matrix
- **[🔍 Security Monitoring](SECURITY.md#security-monitoring)** - Log analysis and monitoring

### Security & Best Practices
- **[🔒 Security Implementation](SECURITY.md)** - Complete security guide with 6-layer protection
- **[🧪 Security Testing](SECURITY.md#security-testing--validation)** - Test your security layers
- **[📊 Threat Analysis](SECURITY.md#threat-mitigation-matrix)** - Threat mitigation matrix
- **[🔍 Security Monitoring](SECURITY.md#security-monitoring)** - Log analysis and monitoring
- **[🚨 Incident Response](SECURITY.md#incident-response)** - Security incident procedures
- **[✅ Security Checklist](SECURITY.md#production-security-checklist)** - Pre-deployment security validation

## Configuration Approaches

### Recommended: Host-Based (Simple)
Use `SS_ALLOWED_HOSTS` for domain management - simplest and most reliable approach.

**Best for:**
- New installations
- Simple domain setups
- Production environments
- Teams wanting minimal configuration

**Documentation:** [Host-Based Configuration](HOST_BASED_CONFIG.md)

### Legacy: Subsite-Specific (Complex)
Configure domains through SilverStripe subsites interface.

**Best for:**
- Existing installations with complex subsite setups
- Migration from older versions
- Complex domain mapping requirements

**Documentation:** [Dynamic Subsites](DYNAMIC_SUBSITES.md)

## Common Use Cases

### Single Domain Website
```bash
SS_ALLOWED_HOSTS="mysite.com,www.mysite.com"
```
**See:** [Host-Based Configuration](HOST_BASED_CONFIG.md)

### Multi-Domain Business
```bash
SS_ALLOWED_HOSTS="company.com,shop.company.com,blog.company.com,api.company.com"
```
**See:** [RP Name Configuration](RP_NAME_CONFIGURATION.md)

### Enterprise with MFA
Passkey + Traditional MFA integration for high-security environments.
**See:** [Optimization Guide - MFA Integration](OPTIMIZATION_GUIDE.md#mfa-compatibility)

### Development Environment
Local development setup with proper domain handling.
**See:** [Host-Based Configuration](HOST_BASED_CONFIG.md#development-environment)

## Technical References

### API Documentation
- **[JavaScript API](USAGE.md#javascript-api)** - Client-side passkey integration
- **[PHP API](USAGE.md#php-api)** - Server-side implementation
- **[Template Integration](USAGE.md#templates-and-styling)** - Frontend integration

### Security & Best Practices
- **[Security Architecture](SECURITY.md#security-architecture-overview)** - Multi-layer security design
- **[Security Testing](SECURITY.md#security-testing--validation)** - Validate your implementation
- **[HTTPS Requirements](USAGE.md#https-requirement)** - SSL/TLS configuration
- **[Domain Validation](HOST_BASED_CONFIG.md#security-advantages)** - Origin protection

### Troubleshooting & Support
- **[Common Issues](USAGE.md#troubleshooting)** - Solutions to frequent problems
- **[Debug Tools](USAGE.md#debug-tools)** - Development debugging
- **[Error Messages](USAGE.md#common-issues)** - Understanding error codes
- **[Security Logs](SECURITY.md#log-analysis)** - Monitor security events

## Migration Guides

### From Static Configuration
**From:** Fixed YAML configuration
**To:** Dynamic host-based configuration
**Guide:** [Host-Based Configuration - Migration](HOST_BASED_CONFIG.md#migration-from-previous-approaches)

### From Subsite-Specific
**From:** Per-subsite YAML configuration  
**To:** SS_ALLOWED_HOSTS approach
**Guide:** [Host-Based Configuration - Migration Script](HOST_BASED_CONFIG.md#migration-script)

### Version Upgrades
**From:** Earlier module versions
**To:** Latest optimized version
**Guide:** [Optimization Guide - Installation](OPTIMIZATION_GUIDE.md#installation-and-configuration)

## Development & Contribution

### Development Setup
- **Prerequisites**: [README - Development](../README.md#development)
- **Building Assets**: [README - Building Assets](../README.md#building-assets)
- **Project Structure**: [README - Project Structure](../README.md#project-structure)

### Customization
- **Template Customization**: [Usage Guide - Custom Templates](USAGE.md#custom-login-template)
- **Styling & Theming**: [Optimization Guide - Theming](OPTIMIZATION_GUIDE.md#customizable-styling-system)
- **JavaScript Customization**: [Usage Guide - JavaScript API](USAGE.md#javascript-api)

### Contributing
- **How to Contribute**: [README - Contributing](../README.md#contributing)
- **Code Standards**: Follow SilverStripe coding standards
- **Testing**: Include tests for new functionality

## Quick Reference

### Configuration Files
```
app/_config/
├── passkey-config.yml          # Main configuration
├── passkey-theme.yml           # Theme customization (optional)
├── mfa-config.yml              # MFA integration (optional)
└── subsites-config.yml         # Subsite support (optional)
```

### Environment Variables
```bash
# Required for production
SS_ALLOWED_HOSTS="domain1.com,domain2.com,domain3.com"

# Optional for development
SS_ENVIRONMENT_TYPE="dev"
```

### Key URLs
- `/Security/login` - Login page with passkey option
- `/passkey-management` - User self-service management
- `/admin/passkey-credentials` - Admin credential management
- `/admin/passkey-hosts` - Host configuration overview
- `/passkey-auth/debug-config` - Debug configuration (dev only)

### Support & Community
- **Issues**: Report bugs on GitHub
- **Questions**: Use SilverStripe community forums
- **Feature Requests**: Submit via GitHub issues
- **Security Issues**: Email maintainers directly

---

## Quick Start Checklist

- [ ] Install via Composer: `composer require gienielab/silverstripe-passkey-auth`
- [ ] Run build: `vendor/bin/sake dev/build flush=1`
- [ ] Set `SS_ALLOWED_HOSTS` in `.env` file
- [ ] Configure basic settings in `app/_config/passkey-config.yml`
- [ ] Test on HTTPS domain (required for production)
- [ ] Review [Usage Guide](USAGE.md) for user instructions

For detailed installation instructions, see the main [README](../README.md).
