# Dynamic Subsite Configuration for Passkey Authentication

## Overview

This approach automatically configures passkey authentication settings based on the subsites and domains configured in the SilverStripe CMS, eliminating the need for manual YAML configuration for each subsite.

## How It Works

### Automatic RP ID Detection
The system automatically determines the **Relying Party ID (RP ID)** with smart multiple domain support:
1. **Current Domain Match**: Finds which configured domain is currently being accessed
2. **Primary Domain**: Uses the subsite's primary domain as fallback
3. **Subsite Domain**: Falls back to the subsite's domain field (legacy)
4. **Current Request**: Uses the current HTTP request domain as ultimate fallback

### Multiple Domain Handling
When a subsite has multiple domains (e.g., `example.com`, `www.example.com`, `shop.example.com`):
- **Smart Detection**: System identifies which domain is currently being accessed
- **Consistent RP ID**: Uses the accessed domain as RP ID for that session
- **Credential Isolation**: Passkeys work across all domains for the same subsite
- **Admin Visibility**: All domains are shown in the configuration overview

### Automatic RP Name Detection  
The system automatically determines the **Relying Party Name (RP Name)** from:
1. **Subsite Title**: Uses the subsite's title field as primary choice
2. **Domain-based Name**: Converts domain to human-readable name (e.g., "example.com" → "Example Site")
3. **Config Fallback**: Uses configured default name

## Benefits

### ✅ Zero Configuration
- **No YAML needed**: Just add subsites in the CMS with domains
- **Automatic setup**: New subsites work immediately
- **Self-maintaining**: Configuration updates when domains change

### ✅ User-Friendly
- **CMS-based**: All configuration happens in the familiar admin interface
- **Visual feedback**: See all configurations in the admin
- **Domain validation**: Clear indication of which domains are being used

### ✅ Secure Isolation
- **Credential isolation**: Each subsite maintains separate passkey credentials
- **Domain binding**: Passkeys are bound to the correct domain automatically
- **Context awareness**: System knows which subsite context it's operating in

## Admin Interface

### Viewing Current Configuration
Navigate to **Admin > Passkey Subsite Config** to see:
- Current RP ID and RP Name for each subsite
- Domain sources being used
- Real-time configuration preview

### Setting Up New Subsites
1. **Create Subsite**: Go to Admin > Subsites > Add New
2. **Set Title**: Enter a descriptive title (becomes RP Name)
3. **Configure Domain**: Add the domain under Domains tab
4. **Done**: Passkey authentication automatically works!

## Configuration Examples

### Example 1: Simple Subsite
```
Subsite Title: "Customer Portal"
Primary Domain: "portal.example.com"

Result:
- RP ID: "portal.example.com"
- RP Name: "Customer Portal"
```

### Example 2: Multiple Domains (Improved)
```
Subsite Title: "E-commerce Site"
Domains:
  - shop.example.com (PRIMARY)
  - store.example.com
  - www.shop.example.com

When accessed via shop.example.com:
- RP ID: "shop.example.com"
- RP Name: "E-commerce Site"

When accessed via store.example.com:
- RP ID: "store.example.com" 
- RP Name: "E-commerce Site"

Note: Passkeys registered on one domain work on all domains of the same subsite
```

### Example 3: Complex Multi-Domain Setup
```
Subsite Title: "Marketing Platform"
Domains:
  - marketing.example.com (PRIMARY)
  - promo.example.com
  - campaigns.example.com
  - www.marketing.example.com

Result: System automatically uses the domain being accessed as RP ID,
ensuring WebAuthn works correctly regardless of which domain users access.
```

### Example 4: Domain-only Configuration
```
Subsite Title: ""
Domain Field: "shop.example.com"

Result:
- RP ID: "shop.example.com"
- RP Name: "Shop Site" (auto-generated from domain)
```

## Technical Implementation

### Key Classes
- **`SubsitePasskeyExtension`**: Handles dynamic RP detection
- **`PasskeyCredentialSubsiteExtension`**: Manages credential isolation
- **`PasskeySubsiteConfigAdmin`**: Provides admin interface

### Dynamic Methods
```php
// Automatically get RP ID for current subsite
$rpId = $passkeyService->getDynamicRpId();

// Automatically get RP Name for current subsite  
$rpName = $passkeyService->getDynamicRpName();

// Clear cache when switching subsites
$passkeyService->clearRpEntityCache();
```

### Hooks and Events
- **Subsite Changes**: RP entity cache clears automatically
- **Domain Updates**: Configuration updates in real-time
- **Context Switching**: Proper isolation maintained

## Migration from Static YAML

### Old Approach (Manual YAML)
```yaml
---
Name: passkey-subsites-subsite1
Only:
  subsiteid: 1
---
GienieLab\PasskeyAuth\Service\PasskeyService:
  rp_name: 'Subsite One'
  rp_id: 'sub1.example.com'
```

### New Approach (Dynamic CMS)
1. Remove static YAML configurations
2. Ensure subsites have proper domains configured
3. System automatically detects settings
4. No code changes needed!

## Troubleshooting

### Common Issues

**Issue**: Passkey not working on subsite
**Solution**: Check that subsite has a domain configured in Admin > Subsites

**Issue**: Wrong RP ID being used
**Solution**: Verify primary domain is set correctly, not just additional domains

**Issue**: Generic RP Name showing
**Solution**: Set a descriptive title for the subsite in the admin

### Debug Information
View current configuration in **Admin > Passkey Subsite Config** to see:
- What RP ID is being detected
- What RP Name is being used
- Which domain source is being used

## Best Practices

### Domain Configuration
1. **Always set primary domain**: This becomes the RP ID
2. **Use descriptive titles**: These become user-visible RP Names
3. **Avoid www prefixes**: System automatically cleans these
4. **Use HTTPS domains**: Required for WebAuthn

### Maintenance
1. **Monitor admin interface**: Regular check of configuration
2. **Update titles as needed**: Changes reflect immediately
3. **Test new subsites**: Verify passkey functionality after setup
4. **Document domain changes**: Keep track of domain updates

This dynamic approach makes passkey authentication much more maintainable and user-friendly across multiple subsites!
