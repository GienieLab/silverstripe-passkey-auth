# Passkey Authentication Template Includes

## Available Template Includes

The passkey authentication module provides several reusable template includes that developers can use in their custom login templates for maximum flexibility and consistency.

### 1. Complete Login Tabs
```silverstripe
<% include LoginTabs %>
```
Provides the tab navigation between Password and Passkey authentication methods with icons.

### 2. Password Login Section
```silverstripe
<% include PasswordLogin %>
```
Provides the standard SilverStripe password login form wrapped in appropriate styling.

### 3. Passkey Login Section
```silverstripe
<% include PasskeyLogin %>
```
Provides the complete passkey authentication UI with modern CTA button.

### 4. Complete Login System
For a full login system, use all three includes:
```silverstripe
<% include LoginTabs %>
<% include PasswordLogin %>
<% include PasskeyLogin %>
```

## Template Locations

Each include is available in two locations for maximum flexibility:

### Module Templates (Primary)
- `/vendor/gienielab/silverstripe-passkey-auth/templates/Includes/LoginTabs.ss`
- `/vendor/gienielab/silverstripe-passkey-auth/templates/Includes/PasswordLogin.ss`
- `/vendor/gienielab/silverstripe-passkey-auth/templates/Includes/PasskeyLogin.ss`

### App Templates (Override)
- `/app/templates/Includes/LoginTabs.ss`
- `/app/templates/Includes/PasswordLogin.ss`  
- `/app/templates/Includes/PasskeyLogin.ss`

## What Each Include Provides

### LoginTabs Include
- Tab navigation buttons with icons
- Password tab with lock icon
- Passkey tab with shield/fingerprint icon
- JavaScript tab switching functionality
- Responsive design
- Proper accessibility attributes

### PasswordLogin Include
- Standard SilverStripe `$Form` variable
- Proper wrapper classes for styling
- Active state management

### PasskeyLogin Include
- Modern CTA-style button with loading states
- Professional header with fingerprint icon
- Description text explaining passkey authentication
- Status messages for authentication feedback
- Responsive design
- Accessibility features

## Customization Options

### Option 1: Override Individual Includes
Copy any include from the module to your app templates folder and modify:

```bash
cp vendor/gienielab/silverstripe-passkey-auth/templates/Includes/LoginTabs.ss app/templates/Includes/
```

### Option 2: Use Custom Template Structure
Create your own login template and include only what you need:

```silverstripe
<div class="custom-login">
    <h1>Sign In</h1>
    
    <!-- Only include passkey login -->
    <% include PasskeyLogin %>
    
    <!-- Or only password login -->
    <% include PasswordLogin %>
    
    <!-- Or include tabs for both -->
    <% include LoginTabs %>
    <% include PasswordLogin %>
    <% include PasskeyLogin %>
</div>
```

### CSS Classes Available

The templates use these main CSS classes that you can style:

#### LoginTabs Classes
- `.login-methods` - Tab container
- `.login-methods__tab` - Individual tab button
- `.login-methods__tab.active` - Active tab state
- `.login-methods__icon` - Tab icon
- `.login-methods__text` - Tab text

#### PasswordLogin Classes  
- `.login-method--password` - Password form container
- `.login-method.active` - Active method state

#### PasskeyLogin Classes
- `.login-method--passkey` - Passkey container
- `.passkey-section` - Main passkey area
- `.passkey-header` - Title and icon area
- `.passkey-description` - Description text
- `.passkey-cta` - Main CTA button
- `.passkey-login__status` - Status message area

### Complete Example

```silverstripe
<div class="login-container">
    <div class="login-header">
        <h1>Welcome Back</h1>
        <p>Choose your preferred sign-in method</p>
    </div>
    
    <!-- Login method tabs -->
    <% include LoginTabs %>
    
    <!-- Password login form -->
    <% include PasswordLogin %>
    
    <!-- Passkey authentication -->
    <% include PasskeyLogin %>
    
    <div class="login-footer">
        <a href="/Security/lostpassword">Forgot your password?</a>
    </div>
</div>
```

### Required Assets

Don't forget to include the required JavaScript and CSS:

```silverstripe
<% require javascript('gienielab/silverstripe-passkey-auth:client/dist/js/passkey-auth.js') %>
<% require css('gienielab/silverstripe-passkey-auth:client/dist/css/styles.css') %>
```

### Benefits of Using Includes

1. **Modularity**: Use only the components you need
2. **Consistency**: Same UI across different templates
3. **Maintainability**: Updates to the module automatically improve all implementations
4. **Customization**: Easy to override individual components
5. **Reusability**: Include in multiple templates without duplication
