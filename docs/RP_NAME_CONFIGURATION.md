# Passkey RP Name Configuration Examples

## Overview

With the host-based approach, you have multiple flexible options for configuring RP names while automatically handling RP IDs from `SS_ALLOWED_HOSTS`.

## Configuration Approaches

### Approach 1: Global RP Name (Simplest)

Use one name for all domains:

```yaml
# app/_config/passkey-config.yml
GienieLab\PasskeyAuth\Service\PasskeyService:
  extensions:
    - GienieLab\PasskeyAuth\Extension\PasskeyHostExtension
  rp_name: 'My Company'
```

```bash
# .env
SS_ALLOWED_HOSTS="example.com,www.example.com,shop.example.com"
```

**Result:**
- `example.com` → RP ID: `example.com`, RP Name: `My Company`
- `shop.example.com` → RP ID: `shop.example.com`, RP Name: `My Company`

### Approach 2: Domain-Specific Names (Recommended)

Different names per domain:

```yaml
# app/_config/passkey-config.yml
GienieLab\PasskeyAuth\Service\PasskeyService:
  extensions:
    - GienieLab\PasskeyAuth\Extension\PasskeyHostExtension
  rp_name: 'Default Site'  # Fallback name
  domain_names:
    'example.com': 'Main Website'
    'www.example.com': 'Main Website'
    'shop.example.com': 'Online Store'
    'api.example.com': 'API Service'
    'admin.example.com': 'Admin Portal'
    'blog.example.com': 'Company Blog'
```

**Result:**
- `example.com` → RP ID: `example.com`, RP Name: `Main Website`
- `shop.example.com` → RP ID: `shop.example.com`, RP Name: `Online Store`
- `api.example.com` → RP ID: `api.example.com`, RP Name: `API Service`

### Approach 3: Subsite Integration (Best of Both)

Use subsite titles with domain fallbacks:

```yaml
# app/_config/passkey-config.yml
GienieLab\PasskeyAuth\Service\PasskeyService:
  extensions:
    - GienieLab\PasskeyAuth\Extension\PasskeyHostExtension
  rp_name: 'Default Site'
  domain_names:
    'example.com': 'Main Website'
    'www.example.com': 'Main Website'
```

**With Subsites Configured:**
- Main Site (example.com) → Uses `domain_names` → `Main Website`
- Shop Subsite (shop.example.com) → Uses subsite title → `E-commerce Portal`
- Blog Subsite (blog.example.com) → Uses subsite title → `Company Blog`

### Approach 4: Auto-Generated Names (Zero Config)

Let the system generate names from domains:

```yaml
# app/_config/passkey-config.yml
GienieLab\PasskeyAuth\Service\PasskeyService:
  extensions:
    - GienieLab\PasskeyAuth\Extension\PasskeyHostExtension
  # No rp_name or domain_names configured
```

**Result (Auto-Generated):**
- `example.com` → RP ID: `example.com`, RP Name: `Example Site`
- `shop.example.com` → RP ID: `shop.example.com`, RP Name: `Shop`
- `blog.example.com` → RP ID: `blog.example.com`, RP Name: `Blog`
- `api.example.com` → RP ID: `api.example.com`, RP Name: `API`

## Real-World Examples

### Example 1: E-commerce Business

```bash
# .env
SS_ALLOWED_HOSTS="mystore.com,www.mystore.com,shop.mystore.com,admin.mystore.com"
```

```yaml
# app/_config/passkey-config.yml
GienieLab\PasskeyAuth\Service\PasskeyService:
  extensions:
    - GienieLab\PasskeyAuth\Extension\PasskeyHostExtension
  rp_name: 'MyStore'
  domain_names:
    'mystore.com': 'MyStore'
    'www.mystore.com': 'MyStore'
    'shop.mystore.com': 'MyStore Shop'
    'admin.mystore.com': 'MyStore Admin'
```

### Example 2: Multi-Brand Company

```bash
# .env
SS_ALLOWED_HOSTS="company.com,brand1.com,brand2.com,api.company.com"
```

```yaml
# app/_config/passkey-config.yml
GienieLab\PasskeyAuth\Service\PasskeyService:
  extensions:
    - GienieLab\PasskeyAuth\Extension\PasskeyHostExtension
  domain_names:
    'company.com': 'Company Corp'
    'brand1.com': 'Brand One'
    'brand2.com': 'Brand Two'
    'api.company.com': 'Company API'
```

### Example 3: Development/Staging/Production

```yaml
# app/_config/passkey-config.yml
GienieLab\PasskeyAuth\Service\PasskeyService:
  extensions:
    - GienieLab\PasskeyAuth\Extension\PasskeyHostExtension
  rp_name: 'My App'
  domain_names:
    'localhost': 'My App (Local)'
    'dev.myapp.com': 'My App (Development)'
    'staging.myapp.com': 'My App (Staging)'
    'myapp.com': 'My App'
    'www.myapp.com': 'My App'
```

## Name Selection Priority

The system selects RP names in this order:

1. **Domain-Specific Configuration**: `domain_names[current_domain]`
2. **Subsite Title**: If subsites enabled and subsite has title
3. **Global Configuration**: `rp_name` setting
4. **Auto-Generated**: Smart domain-to-name conversion

## Best Practices

### Naming Guidelines
1. **Keep it Short**: Passkey UIs have limited space
2. **Be Descriptive**: Users should recognize the service
3. **Be Consistent**: Use similar naming patterns across domains
4. **Avoid Technical Terms**: Use business names, not technical ones

### Examples of Good Names
✅ **Good:**
- "MyCompany"
- "Online Store"
- "Customer Portal"
- "Admin Dashboard"

❌ **Avoid:**
- "api.mycompany.com"
- "Production Environment"
- "SilverStripe CMS"
- "WebAuthn Service"

### Domain Mapping Strategy
1. **Main Domains**: Use your brand/company name
2. **Functional Domains**: Use descriptive function names
3. **Environment Domains**: Include environment in parentheses
4. **API Domains**: Keep simple like "API" or "Service"

## Migration from Previous Approaches

### From Fixed Configuration

**Before:**
```yaml
GienieLab\PasskeyAuth\Service\PasskeyService:
  rp_name: 'Fixed Name'
  rp_id: 'example.com'
```

**After:**
```bash
SS_ALLOWED_HOSTS="example.com,www.example.com"
```
```yaml
GienieLab\PasskeyAuth\Service\PasskeyService:
  extensions:
    - GienieLab\PasskeyAuth\Extension\PasskeyHostExtension
  rp_name: 'Fixed Name'  # Same name, now works for all domains
```

### From Subsite-Specific Configuration

**Before:**
```yaml
---
Name: subsite-1-config
Only: { subsiteid: 1 }
---
GienieLab\PasskeyAuth\Service\PasskeyService:
  rp_name: 'Shop Site'
  rp_id: 'shop.example.com'
```

**After:**
```bash
SS_ALLOWED_HOSTS="example.com,shop.example.com"
```
```yaml
GienieLab\PasskeyAuth\Service\PasskeyService:
  extensions:
    - GienieLab\PasskeyAuth\Extension\PasskeyHostExtension
  domain_names:
    'shop.example.com': 'Shop Site'
```

This approach gives you complete flexibility in naming while maintaining the simplicity of host-based domain management!
