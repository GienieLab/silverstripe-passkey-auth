// Mock WebAuthn API for testing
Object.defineProperty(window, 'PublicKeyCredential', {
  writable: true,
  value: {
    isUserVerifyingPlatformAuthenticatorAvailable: jest.fn().mockResolvedValue(true),
    isConditionalMediationAvailable: jest.fn().mockResolvedValue(true)
  }
});

Object.defineProperty(navigator, 'credentials', {
  writable: true,
  value: {
    create: jest.fn(),
    get: jest.fn()
  }
});

// Mock fetch
global.fetch = jest.fn();
