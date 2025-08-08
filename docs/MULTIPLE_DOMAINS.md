# Multiple Domain Configuration Guide

## Overview

This guide explains how the GienieLab Passkey Authentication module handles subsites with multiple domains, ensuring WebAuthn works correctly across all domain variations.

## How Multiple Domains Work

### Domain Detection Logic

The system uses intelligent domain detection:

1. **Current Request Analysis**: Determines which domain the user is currently accessing
2. **Domain Matching**: Finds the matching configured domain in the subsite
3. **RP ID Selection**: Uses the accessed domain as the Relying Party ID
4. **Credential Isolation**: Maintains proper security isolation per subsite

### Example Scenarios

#### Scenario A: E-commerce with Multiple Domains
```yaml
Subsite: "Online Store"
Domains:
  - shop.example.com (PRIMARY)
  - store.example.com  
  - www.shop.example.com
  - mobile.shop.example.com
```

**User Experience:**
- User visits `shop.example.com` → RP ID becomes `shop.example.com`
- User visits `store.example.com` → RP ID becomes `store.example.com`
- Passkeys registered on any domain work across all domains of the same subsite
- Each domain maintains WebAuthn compliance

#### Scenario B: Marketing with Country Domains
```yaml
Subsite: "Global Marketing"
Domains:
  - marketing.example.com (PRIMARY)
  - marketing.example.co.uk
  - marketing.example.au
  - promo.example.com
```

**Benefits:**
- Geographical domain access works seamlessly
- Single subsite management for global campaigns
- Credential sharing across all marketing domains
- Consistent user experience

## Configuration in SilverStripe Admin

### Setting Up Multiple Domains

1. **Navigate to Admin > Subsites**
2. **Select or Create Subsite**
3. **Go to Domains Tab**
4. **Add Multiple Domains:**
   - Set one as PRIMARY domain
   - Add additional domains as needed
   - Configure protocols (HTTPS recommended)

### Domain Configuration Best Practices

#### ✅ Recommended Setup
```
Primary Domain: shop.example.com
Additional Domains:
  - www.shop.example.com
  - mobile.shop.example.com
  - m.shop.example.com
```

#### ❌ Avoid These Configurations
```
# DON'T mix different business domains in same subsite
Primary Domain: shop.example.com
Additional Domains:
  - blog.example.com  # Different business function
  - api.example.com   # Different service type
```

## Technical Implementation

### Domain Matching Algorithm

```php
// Pseudo-code for domain matching
function getSubsiteRpId() {
    $currentDomain = getCurrentRequestDomain();
    $subsite = getCurrentSubsite();
    
    // 1. Find exact match in configured domains
    foreach ($subsite->getAllDomains() as $domain) {
        if (cleanDomain($domain) === cleanDomain($currentDomain)) {
            return cleanDomain($domain);
        }
    }
    
    // 2. Fallback to primary domain
    return cleanDomain($subsite->getPrimaryDomain());
}
```

### Domain Cleaning Process

The system automatically cleans domains:
- Removes `http://` and `https://` protocols
- Removes port numbers (`:8080`)
- Removes paths (`/admin`, `/shop`)
- Converts to lowercase
- Trims whitespace

Example:
```
Input: "HTTPS://Shop.Example.Com:443/admin"
Output: "shop.example.com"
```

## WebAuthn Compliance

### RP ID Requirements

WebAuthn requires:
- **Exact Domain Match**: RP ID must match the current domain exactly
- **No Subdomains**: Cannot use parent domain for subdomain access
- **HTTPS Only**: WebAuthn only works over HTTPS (except localhost)

### How We Handle This

✅ **Compliant Approach:**
```
User accesses: shop.example.com
RP ID used: shop.example.com
Result: ✅ WebAuthn works

User accesses: www.shop.example.com  
RP ID used: www.shop.example.com
Result: ✅ WebAuthn works
```

❌ **Non-Compliant Approach:**
```
User accesses: shop.example.com
RP ID used: example.com  # Parent domain
Result: ❌ WebAuthn fails
```

## Credential Management

### Cross-Domain Credential Sharing

Within the same subsite:
- Credentials are isolated by **subsite**, not by domain
- User can register passkey on `shop.example.com`
- Same passkey works on `www.shop.example.com` 
- Database stores subsite association, not domain association

### Security Considerations

- **Subsite Isolation**: Credentials cannot cross subsite boundaries
- **Domain Validation**: Each domain validates WebAuthn independently
- **Session Context**: System tracks which domain user is accessing
- **Cache Management**: RP entity cache clears on domain switches

## Admin Interface

### Viewing Domain Configuration

Navigate to **Admin > Passkey Subsite Config** to see:

```
Subsite: E-commerce Store (ID: 2)
RP ID: shop.example.com (dynamically determined)
RP Name: E-commerce Store
Domains: shop.example.com (PRIMARY), www.shop.example.com, mobile.shop.example.com
```

### Troubleshooting Information

The admin interface shows:
- **Current RP ID**: What's being used right now
- **All Domains**: Complete list of configured domains
- **Primary Domain**: Which domain is marked as primary
- **Domain Source**: Where the RP ID is coming from

## Migration Strategies

### From Single Domain to Multiple Domains

1. **Add Additional Domains**: Configure new domains in subsite
2. **Test Access**: Verify passkey works on all domains
3. **Update DNS**: Point new domains to same server
4. **No Code Changes**: System automatically handles new domains

### From Static YAML to Dynamic

1. **Remove Static Config**: Delete domain-specific YAML files
2. **Configure in CMS**: Set up domains in SilverStripe admin
3. **Verify Function**: Test passkey authentication
4. **Clean Up**: Remove old configuration files

## Best Practices

### Domain Planning
1. **Group Related Domains**: Keep business-related domains in same subsite
2. **Use Descriptive Titles**: Subsite titles become user-visible RP names
3. **Plan Primary Domain**: Choose most important domain as primary
4. **Consider SEO**: Align with SEO domain strategy

### Security
1. **HTTPS Only**: Always use HTTPS for WebAuthn domains
2. **Wildcard Certificates**: Use SSL certificates covering all domains
3. **Test Thoroughly**: Verify passkey functionality on each domain
4. **Monitor Access**: Track which domains users access most

### Maintenance
1. **Regular Audits**: Review domain configurations periodically
2. **Update Documentation**: Keep domain lists current
3. **Test New Domains**: Verify passkey function before going live
4. **Monitor Performance**: Watch for domain-related issues

This approach ensures reliable passkey authentication across all your subsite domains while maintaining WebAuthn compliance and security standards.
