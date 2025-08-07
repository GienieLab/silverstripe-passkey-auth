# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.1] - 2025-01-07

### Fixed
- **LastUsed Tracking**: Fixed `LastUsed` field not being updated when passkeys are used for authentication
- **User Agent Updates**: Enhanced authentication tracking to update user agent information on each use
- **Usage Logging**: Added debug logging for credential usage tracking

### Enhanced
- **PasskeyCredential Model**: Enhanced `updateUsage()` method to handle sign count, timestamp, and user agent updates
- **Better Tracking**: Improved tracking of when and how passkeys are used for security auditing

## [1.0.0] - 2025-01-07

### Added
- Initial release of SilverStripe Passkey Authentication module
- WebAuthn-based passwordless authentication using web-auth/webauthn-lib
- Dual authentication system (passkeys + traditional passwords)
- Comprehensive admin interface for credential management
- User self-service passkey management interface
- Cross-platform support for desktop and mobile devices
- Support for multiple authentication methods (biometrics, security keys, device PIN)
- Comprehensive error handling and user-friendly messages
- Production-ready security implementations
- Responsive UI components with modern styling
- Full documentation and configuration examples
- PHPUnit test suite for core functionality
- HTTPS requirement enforcement for security
- Base64 encoding for secure credential storage
- BackURL parameter support for seamless redirects
- AJAX-based authentication flow
- **Modern Build System**: Webpack-based asset compilation with Yarn
- **ES6+ JavaScript**: Modern JavaScript with Babel transpilation
- **Advanced SCSS**: PostCSS optimization and autoprefixing
- **Development Tools**: ESLint, Stylelint, Jest testing, and hot module replacement
- **Asset Optimization**: Code splitting, minification, and source maps

### Build System Features
- Webpack 5 with modern optimization
- Babel transpilation for browser compatibility
- SCSS compilation with PostCSS
- Automated vendor prefixing
- Development server with hot reload
- Code linting and formatting
- Bundle analysis tools
- Production-ready minification

### Security
- Implemented WebAuthn standard for robust authentication
- Required HTTPS for all passkey operations
- Secure random challenge generation
- Binary credential data protection
- User verification requirements
- Domain validation for relying party identity

### Technical Details
- Compatible with SilverStripe ^5.0 || ^6.0
- Requires PHP ^8.1
- Uses web-auth/webauthn-lib ^4.0 and web-auth/cose-lib ^4.0
- PSR-4 autoloading compliance
- Full test coverage for critical components
- Comprehensive error logging and debugging tools
