<?php

namespace GienieLab\PasskeyAuth\Service;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\View\ViewableData;

/**
 * Service for managing theme configuration and CSS generation
 */
class ThemeService extends ViewableData
{
    use Injectable, Configurable;

    /**
     * Get theme configuration
     */
    public function getThemeConfig(): array
    {
        return $this->config()->get('theme') ?: [];
    }

    /**
     * Generate CSS custom properties from theme config
     */
    public function generateCSSProperties(): string
    {
        $config = $this->getThemeConfig();
        $properties = [];

        foreach ($config as $key => $value) {
            $cssVar = '--passkey-' . str_replace('_', '-', $key);
            $properties[] = "{$cssVar}: {$value};";
        }

        return implode(' ', $properties);
    }

    /**
     * Get a specific theme value
     */
    public function getThemeValue(string $key, string $default = ''): string
    {
        $config = $this->getThemeConfig();
        return $config[$key] ?? $default;
    }

    /**
     * Generate modern CSS styles with variables
     */
    public function getModernStyles(): string
    {
        return "
        /* Passkey Authentication Styles */
        .passkey-auth-wrapper {
            --primary: var(--passkey-primary-color, #667eea);
            --primary-hover: var(--passkey-primary-hover, #5a6fd8);
            --secondary: var(--passkey-secondary-color, #764ba2);
            --bg: var(--passkey-background-color, #ffffff);
            --card-bg: var(--passkey-card-background, #ffffff);
            --text: var(--passkey-text-color, #2d3748);
            --text-muted: var(--passkey-text-muted, #718096);
            --border: var(--passkey-border-color, #e2e8f0);
            --success: var(--passkey-success-color, #48bb78);
            --error: var(--passkey-error-color, #f56565);
            --radius: var(--passkey-button-radius, 8px);
            --card-radius: var(--passkey-card-radius, 12px);
            --shadow: var(--passkey-shadow, 0 4px 6px -1px rgba(0, 0, 0, 0.1));
            --shadow-hover: var(--passkey-shadow-hover, 0 10px 15px -3px rgba(0, 0, 0, 0.1));
            --font-family: var(--passkey-font-family, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif);
        }

        .passkey-auth-wrapper .login-methods {
            display: flex;
            gap: var(--passkey-spacing-sm, 0.5rem);
            margin-bottom: var(--passkey-spacing-lg, 1.5rem);
            border-radius: var(--radius);
            padding: var(--passkey-spacing-xs, 0.25rem);
            background: rgba(0, 0, 0, 0.02);
            border: 1px solid var(--border);
        }

        .passkey-auth-wrapper .method-tab {
            flex: 1;
            padding: var(--passkey-spacing-sm, 0.5rem) var(--passkey-spacing-md, 1rem);
            border: none;
            background: transparent;
            color: var(--text-muted);
            border-radius: calc(var(--radius) - 2px);
            font-family: var(--font-family);
            font-size: var(--passkey-font-size-sm, 0.875rem);
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            text-align: center;
        }

        .passkey-auth-wrapper .method-tab:hover {
            background: rgba(var(--primary), 0.05);
            color: var(--primary);
        }

        .passkey-auth-wrapper .method-tab.active {
            background: var(--primary);
            color: white;
            box-shadow: var(--shadow);
        }

        .passkey-auth-wrapper .login-form {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: var(--card-radius);
            padding: var(--passkey-spacing-xl, 2rem);
            box-shadow: var(--shadow);
            transition: box-shadow 0.2s ease;
        }

        .passkey-auth-wrapper .login-form:hover {
            box-shadow: var(--shadow-hover);
        }

        .passkey-auth-wrapper .passkey-button {
            width: 100%;
            padding: 0;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            border-radius: var(--radius);
            font-family: var(--font-family);
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            min-height: 70px;
            box-shadow: var(--shadow);
        }

        .passkey-auth-wrapper .passkey-button:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .passkey-auth-wrapper .passkey-button:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-hover);
            filter: brightness(1.1);
        }

        .passkey-auth-wrapper .passkey-button:hover:before {
            left: 100%;
        }

        .passkey-auth-wrapper .passkey-button:active {
            transform: translateY(0);
        }

        .passkey-auth-wrapper .passkey-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .passkey-auth-wrapper .passkey-button__content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: var(--passkey-spacing-lg, 1.5rem) var(--passkey-spacing-xl, 2rem);
            position: relative;
            z-index: 1;
        }

        .passkey-auth-wrapper .passkey-icon {
            flex-shrink: 0;
            filter: drop-shadow(0 1px 2px rgba(0,0,0,0.1));
        }

        .passkey-auth-wrapper .passkey-button__text {
            flex: 1;
            text-align: left;
            margin-left: var(--passkey-spacing-lg, 1.5rem);
        }

        .passkey-auth-wrapper .passkey-button__title {
            display: block;
            font-size: var(--passkey-font-size-lg, 1.125rem);
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: 2px;
        }

        .passkey-auth-wrapper .passkey-button__subtitle {
            display: block;
            font-size: var(--passkey-font-size-sm, 0.875rem);
            font-weight: 400;
            opacity: 0.9;
            line-height: 1.2;
        }

        .passkey-auth-wrapper .passkey-arrow {
            flex-shrink: 0;
            transition: transform 0.2s ease;
            opacity: 0.8;
        }

        .passkey-auth-wrapper .passkey-button:hover .passkey-arrow {
            transform: translateX(4px);
            opacity: 1;
        }

        .passkey-auth-wrapper .passkey-loading {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            gap: var(--passkey-spacing-sm, 0.5rem);
            font-weight: 600;
            z-index: 2;
        }

        .passkey-auth-wrapper .passkey-loading .animate-spin {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .passkey-auth-wrapper .form-group {
            margin-bottom: var(--passkey-spacing-lg, 1.5rem);
        }

        .passkey-auth-wrapper .form-label {
            display: block;
            color: var(--text);
            font-weight: 500;
            margin-bottom: var(--passkey-spacing-sm, 0.5rem);
            font-family: var(--font-family);
            font-size: var(--passkey-font-size-sm, 0.875rem);
        }

        .passkey-auth-wrapper .form-control {
            width: 100%;
            padding: var(--passkey-spacing-md, 1rem);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            font-family: var(--font-family);
            font-size: var(--passkey-font-size-base, 1rem);
            background: var(--bg);
            color: var(--text);
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .passkey-auth-wrapper .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(var(--primary), 0.1);
        }

        .passkey-auth-wrapper .alert {
            padding: var(--passkey-spacing-md, 1rem);
            border-radius: var(--radius);
            margin-bottom: var(--passkey-spacing-lg, 1.5rem);
            font-family: var(--font-family);
            font-size: var(--passkey-font-size-sm, 0.875rem);
        }

        .passkey-auth-wrapper .alert-success {
            background: rgba(var(--success), 0.1);
            color: var(--success);
            border: 1px solid rgba(var(--success), 0.2);
        }

        .passkey-auth-wrapper .alert-error {
            background: rgba(var(--error), 0.1);
            color: var(--error);
            border: 1px solid rgba(var(--error), 0.2);
        }

        @media (prefers-color-scheme: dark) {
            .passkey-auth-wrapper {
                --bg: #1a202c;
                --card-bg: #2d3748;
                --text: #f7fafc;
                --text-muted: #a0aec0;
                --border: #4a5568;
            }
        }

        /* Responsive design */
        @media (max-width: 640px) {
            .passkey-auth-wrapper .login-form {
                padding: var(--passkey-spacing-lg, 1.5rem);
            }
            
            .passkey-auth-wrapper .login-methods {
                flex-direction: column;
            }
        }
        ";
    }
}
