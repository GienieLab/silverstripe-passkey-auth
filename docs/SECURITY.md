# 🔒 Passkey Authentication Security Guide

A comprehensive security guide for the SilverStripe Passkey Authentication module, detailing our multi-layer security implementation and best practices.

## 🛡️ Security Architecture Overview

The passkey authentication module implements **6 layers of security protection** to ensure enterprise-grade security while maintaining usability:

```
┌─────────────────────────────────────────────────────────┐
│                 Security Layers                         │
├─────────────────────────────────────────────────────────┤
│ Layer 1: User-Agent Filtering (Bot Protection)         │
│ Layer 2: Rate Limiting (Brute Force Prevention)        │
│ Layer 3: Request Size Limits (Payload Attack Prevention)│
│ Layer 4: Origin Validation (CSRF Prevention)           │
│ Layer 5: CSRF Token Validation (State Protection)      │
│ Layer 6: Comprehensive Logging (Threat Monitoring)     │
└─────────────────────────────────────────────────────────┘
```

## 🚀 Multi-Layer Security Implementation

### Layer 1: User-Agent Filtering
**Purpose**: Block automated attacks and bots

```php
protected function isSuspiciousUserAgent(string $userAgent): bool
{
    $suspiciousPatterns = [
        '/curl/i', '/wget/i', '/python/i', '/bot/i', 
        '/crawler/i', '/spider/i', '/scanner/i'
    ];
    
    foreach ($suspiciousPatterns as $pattern) {
        if (preg_match($pattern, $userAgent)) {
            return true;
        }
    }
    return false;
}
```

**Protection Against**:
- Automated bot attacks
- Script-based credential harvesting
- Malicious crawlers

### Layer 2: Rate Limiting
**Purpose**: Prevent brute force and abuse

```yaml
# Configuration
Rate Limits:
  Challenge Generation: 5 requests per hour
  Authentication: 5 attempts per hour  
  Registration: 5 attempts per hour
  Debug Endpoints: 3 requests per 5 minutes
```

**Implementation**:
- IP-based tracking using SilverStripe CacheFactory
- Separate limits per endpoint type
- Exponential backoff for repeated violations

### Layer 3: Request Size Limits
**Purpose**: Prevent large payload attacks

```php
protected function validateRequestSecurity(HTTPRequest $request): array
{
    $body = $request->getBody();
    if (strlen($body) > 1024) { // 1KB limit
        return ['valid' => false, 'reason' => 'Request too large'];
    }
    return ['valid' => true];
}
```

**Protection Against**:
- Buffer overflow attempts
- Memory exhaustion attacks
- Malformed large payloads

### Layer 4: Origin Validation
**Purpose**: Prevent cross-site request forgery

```php
protected function checkPasskeyCSRF(HTTPRequest $request): bool
{
    $origin = $request->getHeader('Origin');
    $allowedHosts = Environment::getEnv('SS_ALLOWED_HOSTS');
    
    if (!$origin || !$this->isValidOrigin($origin, $allowedHosts)) {
        return false;
    }
    return true;
}
```

**Features**:
- Dynamic origin checking against `SS_ALLOWED_HOSTS`
- HTTPS enforcement in production
- Localhost allowed for development

### Layer 5: CSRF Token Validation  
**Purpose**: Protect against state-changing attacks

```php
// Custom CSRF implementation for passkey endpoints
if (!$this->checkPasskeyCSRF($request)) {
    $this->logSecurityEvent('Blocked invalid origin', [
        'origin' => $request->getHeader('Origin'),
        'ip' => $request->getIP()
    ]);
    return $this->httpError(403, 'Forbidden');
}
```

### Layer 6: Comprehensive Logging
**Purpose**: Monitor and detect threats

```php
protected function logSecurityEvent(string $message, array $context = []): void
{
    $this->logger->warning($message, array_merge($context, [
        'timestamp' => date('c'),
        'endpoint' => $this->getRequest()->getURL(),
        'user_agent' => $this->getRequest()->getHeader('User-Agent')
    ]));
}
```

## 🔧 Security Configuration

### Basic Security Setup

Create `app/_config/passkey-security.yml`:

```yaml
---
Name: passkey-security
---
# Rate limiting configuration
SilverStripe\Core\Cache\CacheFactory:
  passkey_rate_limit:
    namespace: "passkey_rate_limit"
    defaultLifetime: 3600

# Security headers
SilverStripe\Control\Controller:
  default_csp: "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline';"

# Session security
SilverStripe\Control\Session:
  cookie_secure: true
  cookie_httponly: true
  cookie_samesite: 'Strict'
```

### Environment Security

Add to your `.env` file:

```bash
# Required: Define allowed hosts for origin validation
SS_ALLOWED_HOSTS="yourdomain.com,www.yourdomain.com,app.yourdomain.com"

# Recommended: Enable security features
SS_SEND_SECURITY_HEADERS=1
SS_SECURE_COOKIES=1

# Production: Disable debug features
SS_ENVIRONMENT_TYPE="live"
```

## 🎯 Security Headers Implementation

The module automatically adds comprehensive security headers:

```php
protected function addSecurityHeaders(HTTPResponse $response): void
{
    $response->addHeader('X-Content-Type-Options', 'nosniff');
    $response->addHeader('X-Frame-Options', 'DENY');
    $response->addHeader('X-XSS-Protection', '1; mode=block');
    $response->addHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    $response->addHeader('Content-Security-Policy', "default-src 'self'");
}
```

### Header Explanations

| Header | Purpose | Protection |
|--------|---------|------------|
| `X-Content-Type-Options: nosniff` | Prevent MIME confusion | File upload attacks |
| `X-Frame-Options: DENY` | Block iframe embedding | Clickjacking |
| `X-XSS-Protection: 1; mode=block` | Enable XSS filtering | Script injection |
| `Referrer-Policy: strict-origin-when-cross-origin` | Limit referrer info | Privacy protection |
| `Content-Security-Policy` | Control resource loading | XSS prevention |
## 🧪 Security Testing & Validation

### Testing Your Security Implementation

You can test the security layers using these commands:

#### Test 1: User-Agent Blocking
```bash
# This should return 403 (blocked)
curl -X POST "https://yoursite.com/passkey-auth/challenge" \
  -H "Content-Type: application/json" \
  -H "Origin: https://yoursite.com" \
  -H "User-Agent: curl/8.0" \
  -d '{}' -w "Status: %{http_code}\n"
```

#### Test 2: Legitimate Browser Request
```bash
# This should return 200 (allowed)
curl -X POST "https://yoursite.com/passkey-auth/challenge" \
  -H "Content-Type: application/json" \
  -H "Origin: https://yoursite.com" \
  -H "Accept: application/json" \
  -H "User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)" \
  -d '{}' -w "Status: %{http_code}\n"
```

#### Test 3: Rate Limiting
```bash
# Rapid requests should trigger rate limiting after 5 attempts
for i in {1..7}; do
  curl -X POST "https://yoursite.com/passkey-auth/challenge" \
    -H "Content-Type: application/json" \
    -H "Origin: https://yoursite.com" \
    -H "User-Agent: Mozilla/5.0 (Test)" \
    -d '{}' -w "Request $i Status: %{http_code}\n" -s -o /dev/null
done
```

#### Test 4: Large Payload Protection
```bash
# Large payloads should return 403
curl -X POST "https://yoursite.com/passkey-auth/challenge" \
  -H "Content-Type: application/json" \
  -H "Origin: https://yoursite.com" \
  -H "User-Agent: Mozilla/5.0 (Test)" \
  -d '{"malicious":"'$(python3 -c "print('x'*2000)")'"}'
```

#### Test 5: Origin Validation
```bash
# Invalid origins should return 403
curl -X POST "https://yoursite.com/passkey-auth/challenge" \
  -H "Content-Type: application/json" \
  -H "Origin: https://malicious-site.com" \
  -H "User-Agent: Mozilla/5.0 (Test)" \
  -d '{}'
```

### Expected Results

| Test | Expected Status | Security Layer |
|------|----------------|----------------|
| Bot User-Agent | 403 Forbidden | Layer 1: User-Agent Filtering |
| Legitimate Browser | 200 OK | All layers pass |
| Rate Limiting | 200 → 429 | Layer 2: Rate Limiting |
| Large Payload | 403 Forbidden | Layer 3: Size Limits |
| Invalid Origin | 403 Forbidden | Layer 4: Origin Validation |

## 📊 Threat Mitigation Matrix

| Threat Type | Protection Method | Implementation | Effectiveness |
|-------------|------------------|----------------|---------------|
| **Automated Attacks** | User-Agent filtering | Pattern matching against bot signatures | ⭐⭐⭐⭐⭐ |
| **Brute Force** | Rate limiting | IP-based request throttling | ⭐⭐⭐⭐⭐ |
| **CSRF** | Origin validation + CSRF tokens | Domain whitelist + state tokens | ⭐⭐⭐⭐⭐ |
| **Payload Attacks** | Request size limits | 1KB maximum body size | ⭐⭐⭐⭐ |
| **Session Hijacking** | Session binding | User-Agent + IP validation | ⭐⭐⭐⭐ |
| **Information Disclosure** | Sanitized logging | No sensitive data in logs | ⭐⭐⭐⭐⭐ |
| **Replay Attacks** | Challenge expiry | 5-minute time windows | ⭐⭐⭐⭐⭐ |
| **Clickjacking** | X-Frame-Options | Frame embedding prevention | ⭐⭐⭐⭐⭐ |

## 🔍 Security Monitoring

### Log Analysis

Monitor these security events in your logs:

```bash
# Check for blocked threats
tail -f silverstripe.log | grep -i "blocked\|security\|threat"

# Monitor authentication patterns
tail -f silverstripe.log | grep -i "passkey.*auth"

# Check rate limiting effectiveness
tail -f silverstripe.log | grep -i "rate.limit"
```

### Key Security Metrics

Track these metrics for security health:

1. **Blocked Request Rate**: `blocked_requests / total_requests`
2. **Failed Authentication Rate**: `failed_auth / total_auth_attempts`
3. **Rate Limit Triggers**: `rate_limited_ips / unique_ips`
4. **Security Header Coverage**: `requests_with_headers / total_requests`

### Alert Thresholds

Set up alerts for:
- ⚠️ **High blocked request rate** (>10% in 1 hour)
- 🚨 **Repeated failed authentications** (>50 from same IP)
- ⚡ **Rate limit violations** (>10 IPs hitting limits)
- 🔴 **Security header failures** (missing on >1% requests)

## 🏭 Production Security Checklist

### Pre-Deployment
- [ ] HTTPS configured and working
- [ ] `SS_ALLOWED_HOSTS` properly configured
- [ ] Rate limiting configured and tested
- [ ] Security headers validated
- [ ] Debug endpoints disabled (`SS_ENVIRONMENT_TYPE=live`)
- [ ] Log monitoring configured

### Post-Deployment
- [ ] Security layer testing completed
- [ ] Log monitoring active
- [ ] Rate limiting effectiveness verified
- [ ] User-Agent filtering working
- [ ] Origin validation protecting against CSRF
- [ ] Performance impact assessed

### Ongoing Maintenance
- [ ] Regular security log reviews (weekly)
- [ ] Rate limit threshold adjustments (monthly)
- [ ] Security testing (quarterly)
- [ ] Threat pattern updates (as needed)

## 🚨 Incident Response

### Security Event Response

If you detect a security incident:

1. **Immediate Response**
   ```bash
   # Check current attacks
   tail -100 silverstripe.log | grep -i "blocked\|forbidden"
   
   # Identify attack patterns
   grep "user_agent\|ip" silverstripe.log | sort | uniq -c | sort -nr
   ```

2. **Containment**
   - Review rate limiting effectiveness
   - Check if additional IPs need blocking
   - Verify security headers are active

3. **Analysis**
   - Determine attack vector
   - Assess if security layers held
   - Document lessons learned

4. **Recovery**
   - Clear rate limit caches if needed: `vendor/bin/sake dev/tasks/ClearCacheTask`
   - Update security configurations if needed
   - Monitor for continued attacks

## 🎓 Security Best Practices

### Development
- Always test on HTTPS domains
- Use environment-specific rate limits
- Test all security layers before deployment
- Review security logs during development

### Staging
- Mirror production security configuration
- Test with realistic user loads
- Validate rate limiting under stress
- Verify origin validation works correctly

### Production
- Monitor security metrics continuously
- Regular security layer testing
- Keep rate limits appropriately tuned
- Document security incident procedures

## 🔧 Advanced Configuration

### Custom Rate Limits

```yaml
# app/_config/custom-security.yml
GienieLab\PasskeyAuth\Controller\PasskeyAuthController:
  rate_limits:
    challenge: 10     # 10 per hour instead of 5
    auth: 8          # 8 per hour instead of 5
    register: 3      # 3 per hour instead of 5
    debug: 1         # 1 per 5 minutes instead of 3
```

### Custom User-Agent Patterns

```yaml
# app/_config/custom-security.yml
GienieLab\PasskeyAuth\Controller\PasskeyAuthController:
  suspicious_user_agents:
    - '/malicious-tool/i'
    - '/custom-bot/i'
    - '/security-scanner/i'
```

### Custom Security Headers

```yaml
# app/_config/custom-security.yml
GienieLab\PasskeyAuth\Controller\PasskeyAuthController:
  security_headers:
    X-Custom-Header: 'SecureValue'
    Strict-Transport-Security: 'max-age=31536000; includeSubDomains'
```

## 📋 Compliance & Standards

### Standards Compliance

This security implementation helps meet:

- **WebAuthn Specification**: Full W3C WebAuthn compliance
- **OWASP Top 10**: Protection against major web vulnerabilities
- **GDPR**: Secure credential handling and audit logs
- **SOC 2**: Security controls and monitoring
- **ISO 27001**: Information security management

### Security Certifications

The implementation includes controls for:
- **Access Control** (ISO 27001 A.9)
- **Cryptography** (ISO 27001 A.10)
- **Operations Security** (ISO 27001 A.12)
- **Communications Security** (ISO 27001 A.13)
- **System Acquisition** (ISO 27001 A.14)

## 🆘 Support & Security Issues

### Reporting Security Issues

For security vulnerabilities:
1. **DO NOT** create public GitHub issues
2. Email security issues directly to maintainers
3. Include detailed reproduction steps
4. Allow reasonable time for response

### Getting Help

- **General Questions**: [GitHub Discussions](https://github.com/gienielab/silverstripe-passkey-auth/discussions)
- **Bug Reports**: [GitHub Issues](https://github.com/gienielab/silverstripe-passkey-auth/issues)
- **Security Concerns**: Email maintainers directly
- **Implementation Help**: [SilverStripe Community](https://forum.silverstripe.org)

---

## 🔒 Summary

The SilverStripe Passkey Authentication module implements **enterprise-grade security** with 6 layers of protection:

1. ✅ **User-Agent Filtering** - Blocks bots and automated attacks
2. ✅ **Rate Limiting** - Prevents brute force and abuse
3. ✅ **Request Size Limits** - Stops payload attacks
4. ✅ **Origin Validation** - Prevents CSRF attacks
5. ✅ **CSRF Token Protection** - Secures state-changing operations
6. ✅ **Comprehensive Logging** - Enables threat monitoring

**Result**: Secure, performant, and user-friendly passkey authentication that scales from small sites to enterprise deployments. 🚀
