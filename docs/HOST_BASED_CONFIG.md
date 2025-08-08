# Host-Based Domain Configuration

## Overview

This improved approach uses `SS_ALLOWED_HOSTS` for domain management instead of subsite-specific configuration. This is more reliable, simpler to configure, and works for both main site and subsites.

## Why SS_ALLOWED_HOSTS is Better

### ✅ **Single Source of Truth**
- All domains must be in `SS_ALLOWED_HOSTS` anyway for security
- No need to duplicate domain configuration in multiple places
- Consistent with SilverStripe security best practices

### ✅ **Simpler Configuration**
- One environment variable controls all domains
- Works for main site without requiring subsites module
- No per-subsite YAML configuration needed

### ✅ **More Reliable**
- Uses SilverStripe's built-in security validation
- Automatically validates against allowed domains
- Prevents security issues from misconfiguration

### ✅ **Better Performance**
- No complex subsite domain lookups
- Direct environment variable access
- Faster domain validation

## Configuration

### Step 1: Set SS_ALLOWED_HOSTS

Add to your `.env` file:

```bash
# Single domain
SS_ALLOWED_HOSTS="example.com"

# Multiple domains (comma-separated)
SS_ALLOWED_HOSTS="example.com,www.example.com,shop.example.com,api.example.com"

# Development setup
SS_ALLOWED_HOSTS="localhost,127.0.0.1,mysite.local"
```

### Step 2: Enable Host-Based Extension

```yaml
# app/_config/passkey-host.yml
---
Name: passkey-host-config
After:
  - 'passkey-auth'
---

GienieLab\PasskeyAuth\Service\PasskeyService:
  extensions:
    - GienieLab\PasskeyAuth\Extension\PasskeyHostExtension
```

### Step 3: Optional Subsite Support

If using subsites, credentials are still isolated:

```yaml
# app/_config/passkey-subsites.yml (optional)
---
Name: passkey-subsite-support
Only:
  classexists: 'SilverStripe\Subsites\Model\Subsite'
After:
  - 'passkey-host-config'
---

GienieLab\PasskeyAuth\Model\PasskeyCredential:
  extensions:
    - GienieLab\PasskeyAuth\Extension\PasskeyCredentialSubsiteExtension
```

## How It Works

### Automatic RP ID Detection

1. **Current Domain**: Uses the domain being accessed right now
2. **Validation**: Checks against `SS_ALLOWED_HOSTS` for security
3. **Fallback**: Uses first allowed host if current domain not found
4. **Development**: Allows localhost/127.0.0.1 for development

### RP Name Generation

1. **Subsite Title**: If subsites available, uses subsite title
2. **Domain-Based**: Converts domain to human-readable name
3. **Configuration**: Falls back to configured name

### Example Scenarios

#### Scenario A: Simple Website
```bash
SS_ALLOWED_HOSTS="mysite.com,www.mysite.com"
```

**Results:**
- User visits `mysite.com` → RP ID: `mysite.com`
- User visits `www.mysite.com` → RP ID: `www.mysite.com`
- RP Name: "Mysite Site" (auto-generated)

#### Scenario B: Multi-Domain with Subsites
```bash
SS_ALLOWED_HOSTS="main.com,shop.com,blog.com,api.com"
```

**With Subsites:**
- Main Site → RP ID: `main.com`, RP Name: "Main Site"
- Shop Subsite → RP ID: `shop.com`, RP Name: "Shop" (from subsite title)
- Blog Subsite → RP ID: `blog.com`, RP Name: "Blog" (from subsite title)

#### Scenario C: Development Environment
```bash
SS_ALLOWED_HOSTS="localhost,127.0.0.1,mysite.local"
```

**Results:**
- All domains work for local development
- WebAuthn functions correctly on localhost
- Easy testing across different local domains

## Benefits Over Subsite-Specific Configuration

### Before (Subsite-Specific)
```yaml
# Required separate config for each subsite
---
Name: passkey-subsite-1
Only:
  subsiteid: 1
---
GienieLab\PasskeyAuth\Service\PasskeyService:
  rp_id: 'shop.example.com'
  rp_name: 'Shop Site'

---
Name: passkey-subsite-2  
Only:
  subsiteid: 2
---
GienieLab\PasskeyAuth\Service\PasskeyService:
  rp_id: 'blog.example.com'
  rp_name: 'Blog Site'
```

### After (Host-Based)
```bash
# Single environment variable
SS_ALLOWED_HOSTS="example.com,shop.example.com,blog.example.com"
```

**That's it!** No YAML configuration needed.

## Security Advantages

### Domain Validation
- Automatically validates against `SS_ALLOWED_HOSTS`
- Prevents passkey registration on unauthorized domains
- Consistent with SilverStripe security model

### Development vs Production
```bash
# Development
SS_ALLOWED_HOSTS="localhost,127.0.0.1,dev.mysite.com"

# Production  
SS_ALLOWED_HOSTS="mysite.com,www.mysite.com,api.mysite.com"
```

### Prevents Configuration Drift
- Single source of truth for all domains
- No risk of subsite config being out of sync
- Environment-specific configuration

## Admin Interface

### Viewing Configuration

Navigate to **Admin > Passkey Host Config** to see:

- **Current RP ID and RP Name**: What's being used right now
- **All Allowed Hosts**: Complete list from `SS_ALLOWED_HOSTS`
- **Current Domain Status**: Which domain is being accessed
- **Subsite Integration**: How subsites integrate with host config

### Example Admin Display
```
Current Configuration:
- Current RP ID: shop.example.com
- Current RP Name: Shop Site

SS_ALLOWED_HOSTS Configuration:
- Domain 1: example.com (ALLOWED)
- Domain 2: www.example.com (ALLOWED)  
- Domain 3: shop.example.com (CURRENT, ALLOWED)
- Domain 4: api.example.com (ALLOWED)

Subsite Integration:
- Current Subsite: Shop Site (ID: 1)
- Main Site: RP ID: example.com | RP Name: Main Site
- Shop Subsite: RP ID: shop.example.com | RP Name: Shop Site
```

## Migration Guide

### From Subsite-Specific to Host-Based

1. **List Current Domains**: Gather all domains from subsite configurations
2. **Set SS_ALLOWED_HOSTS**: Add all domains to environment variable
3. **Remove Old Config**: Delete subsite-specific YAML files
4. **Enable Host Extension**: Add `PasskeyHostExtension` to configuration
5. **Test**: Verify passkey functionality on all domains

### Migration Script
```bash
# 1. Backup existing configuration
cp app/_config/passkey-*.yml backup/

# 2. Set environment variable
echo 'SS_ALLOWED_HOSTS="domain1.com,domain2.com,domain3.com"' >> .env

# 3. Remove old config files
rm app/_config/passkey-subsite-*.yml

# 4. Add new configuration
cat > app/_config/passkey-host.yml << EOF
---
Name: passkey-host-config
---
GienieLab\PasskeyAuth\Service\PasskeyService:
  extensions:
    - GienieLab\PasskeyAuth\Extension\PasskeyHostExtension
EOF

# 5. Rebuild and test
vendor/bin/sake dev/build flush=1
```

## Troubleshooting

### Common Issues

**Issue**: Passkey not working on some domains
**Solution**: Check that domain is in `SS_ALLOWED_HOSTS`

**Issue**: RP ID changing unexpectedly  
**Solution**: Verify current domain is in allowed hosts list

**Issue**: Development environment issues
**Solution**: Add `localhost,127.0.0.1` to `SS_ALLOWED_HOSTS`

### Debug Information

Use the admin interface at **Admin > Passkey Host Config** to see:
- Which domains are allowed
- What RP ID is being used
- Whether current domain is recognized
- How subsites integrate with host configuration

## Best Practices

### Environment Configuration
1. **Production**: Use only production domains in `SS_ALLOWED_HOSTS`
2. **Staging**: Include staging domains
3. **Development**: Include localhost and local domains
4. **Security**: Never include wildcard domains

### Domain Management
1. **HTTPS Only**: Ensure all domains use HTTPS (except localhost)
2. **Consistent Naming**: Use clear, descriptive domain names
3. **Documentation**: Keep a list of all domains and their purposes
4. **Monitoring**: Regularly review allowed hosts list

### Performance
1. **Minimal Domains**: Only include domains you actually use
2. **Clear Naming**: Use descriptive domain names for easier management
3. **Regular Cleanup**: Remove unused domains from configuration

This host-based approach significantly simplifies passkey domain management while improving security and reliability!
