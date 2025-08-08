-- Performance optimization indexes for PasskeyCredential table
-- Run these in your database for better query performance

-- Index for user credential lookups (most common query)
CREATE INDEX idx_passkey_user_active ON PasskeyCredential (UserID, IsActive, LastUsed DESC);

-- Index for credential ID lookups during authentication
CREATE INDEX idx_passkey_credential_id ON PasskeyCredential (CredentialID);

-- Index for cleanup operations (finding old/unused credentials)
CREATE INDEX idx_passkey_cleanup ON PasskeyCredential (IsActive, LastUsed, Created);

-- Index for admin reporting (subsite-aware if using subsites)
CREATE INDEX idx_passkey_admin ON PasskeyCredential (SubsiteID, IsActive, Created DESC);

-- Composite index for authentication flow
CREATE INDEX idx_passkey_auth_flow ON PasskeyCredential (UserID, CredentialID, IsActive);
