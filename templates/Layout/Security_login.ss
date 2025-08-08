<section id="Login" class="login passkey-auth-wrapper">
    <!-- Inject CSS Custom Properties for Passkey Theme -->
    <style>
        :root {
            --_login-background-colour: #f6f4f0;
            --_login-background-colour-contrast: #333;
            --_login-box-colour: #fff;
            --_login-box-colour-contrast: #333;
            --_login-text-colour: #333;
            --_login-text-colour-contrast: #fff;
        }
    </style>

    <div class="login__wrap">
        <div class="login__body">
            <div class="login__content">
                <div class="login__header">
                    <div class="login__logo">
                        <!-- Add your logo here if needed -->
                        <h2>Your Site</h2>
                    </div>
                </div>

                <div class="login__heading">
                    <p>Login to your account</p>
                    <hr>
                </div>

                <div class="login__form">
                    <% if $Message %>
                        <div class="message {$MessageType}">
                            {$Message}
                        </div>
                    <% end_if %>

                    <!-- Enhanced Login method tabs with icons -->
                    <% include LoginTabs %>
                    
                    <!-- Password Login Method -->
                    <% include PasswordLogin %>  
                    
                    <!-- Enhanced Passkey login section using include template -->
                    <% include PasskeyLogin %>

                    <!-- Hidden fields -->
                    <% loop $Fields %>
                        <% if $Name == "PasskeyData" %>
                            $FieldHolder
                        <% end_if %>
                    <% end_loop %>
                </div>
            </div>
        </div>
    </div>
</section>


