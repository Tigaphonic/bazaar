<?php

namespace Tigaphonic\Bazaar\Shell\Support;

/**
 * Static mirror of DESIGN.md's token set (Story 1.2, AD-33). This is the
 * single PHP-side source of truth for token *values* — the actual visual
 * rendering comes from the compiled `resources/dist/shell.css` Tailwind v4
 * build (its `@theme` block is hand-kept in sync with this class), but any
 * PHP code that needs a token value (e.g. panel color overrides) reads it
 * from here rather than duplicating literals.
 *
 * Values are transcribed verbatim from
 * `_artifacts/planning-artifacts/ux-designs/ux-Tigaphonic/bazaar-2026-09-11/DESIGN.md`.
 * Dark-mode pairs are DESIGN.md's own net-new dark palette — never a literal
 * inversion of the light value.
 */
final class DesignTokens
{
    /**
     * @return array<string, string>
     */
    public static function colors(): array
    {
        return [
            // Light
            'primary' => '#00609e',
            'primary-700' => '#00527f',
            'primary-50' => '#eef6fb',
            'primary-100' => '#d9ecf7',
            'bluegray' => '#5a6a82',
            'charcoal' => '#333333',
            'soft' => '#f7f7f7',
            'border' => '#e3e6ea',
            'white' => '#ffffff',
            'ink' => '#22262b',
            'muted' => '#5c6875',
            'success-bg' => '#e8f5ec',
            'success-text' => '#1e7d3c',
            'success-ring' => '#bfe3cb',
            'warn-bg' => '#fdf3e2',
            'warn-text' => '#a15c00',
            'warn-ring' => '#f3d9a3',
            'info-bg' => '#e8f1fb',
            'info-text' => '#00609e',
            'info-ring' => '#bcd9ef',
            'danger-bg' => '#fbeaea',
            'danger-text' => '#b0292b',
            'danger-ring' => '#f0c2c3',
            'neutral-bg' => '#eef0f2',
            'neutral-text' => '#5b6472',
            'neutral-ring' => '#dbdfe4',
            'done-bg' => '#eef1f5',
            'done-text' => '#3d4655',
            'done-ring' => '#d7dce3',
            'live-dot' => '#2fae5c',
            'notification-dot' => '#e6544f',
            'quota-warn' => '#d98a1f',
            'hairline' => '#f1f2f4',
            'border-emphasis' => '#d7dbdf',
            'surface-hover' => '#f4f6f7',
            'border-dashed' => '#c6ccd1',
            'dot-pending' => '#cfd5da',

            // Dark — net-new, hue-preserving/luminance-inverted (never a literal inversion)
            'primary-dark' => '#3d94c9',
            'primary-700-dark' => '#7ec2e8',
            'primary-50-dark' => '#0d2436',
            'primary-100-dark' => '#123049',
            'bluegray-dark' => '#93a1b1',
            'charcoal-dark' => '#eef1f4',
            'soft-dark' => '#10151b',
            'border-dark' => '#262f39',
            'white-dark' => '#181f27',
            'ink-dark' => '#e7ebef',
            'muted-dark' => '#7e8b99',
            'success-bg-dark' => '#123423',
            'success-text-dark' => '#4fcf82',
            'success-ring-dark' => '#1f5c3b',
            'warn-bg-dark' => '#3a2c10',
            'warn-text-dark' => '#f0b459',
            'warn-ring-dark' => '#5c4318',
            'info-bg-dark' => '#12283b',
            'info-text-dark' => '#5fb3e8',
            'info-ring-dark' => '#1f4460',
            'danger-bg-dark' => '#3a1616',
            'danger-text-dark' => '#f0797b',
            'danger-ring-dark' => '#5c2426',
            'neutral-bg-dark' => '#232a32',
            'neutral-text-dark' => '#a7b0ba',
            'neutral-ring-dark' => '#333c46',
            'done-bg-dark' => '#232933',
            'done-text-dark' => '#aab3c0',
            'done-ring-dark' => '#333c48',
            'live-dot-dark' => '#3ecb74',
            'notification-dot-dark' => '#f0797b',
            'quota-warn-dark' => '#e3a94a',
            'hairline-dark' => '#20262d',
            'border-emphasis-dark' => '#333d47',
            'surface-hover-dark' => '#1c232b',
            'border-dashed-dark' => '#3a4650',
            'dot-pending-dark' => '#3d4750',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function typography(): array
    {
        return [
            'heading-family' => "'Poppins','Segoe UI',system-ui,-apple-system,sans-serif",
            'body-family' => "'Mulish','Segoe UI',system-ui,-apple-system,sans-serif",
            'heading-display' => ['fontFamily' => 'Poppins', 'fontSize' => '21px', 'fontWeight' => '700', 'lineHeight' => '1.3'],
            'heading-section' => ['fontFamily' => 'Poppins', 'fontSize' => '14.5px', 'fontWeight' => '700', 'lineHeight' => '1.3'],
            'heading-stat' => ['fontFamily' => 'Poppins', 'fontSize' => '26px', 'fontWeight' => '700', 'lineHeight' => '1.2'],
            'label' => ['fontFamily' => 'Poppins', 'fontSize' => '12px', 'fontWeight' => '700', 'lineHeight' => '1.4'],
            'nav-item' => ['fontFamily' => 'Poppins', 'fontSize' => '13.5px', 'fontWeight' => '500', 'lineHeight' => '1.4'],
            'body' => ['fontFamily' => 'Mulish', 'fontSize' => '14px', 'fontWeight' => '400', 'lineHeight' => '1.5'],
            'body-sm' => ['fontFamily' => 'Mulish', 'fontSize' => '13px', 'fontWeight' => '400', 'lineHeight' => '1.5'],
            'caption' => ['fontFamily' => 'Mulish', 'fontSize' => '12px', 'fontWeight' => '400', 'lineHeight' => '1.4'],
            'overline' => ['fontFamily' => 'Mulish', 'fontSize' => '11px', 'fontWeight' => '700', 'lineHeight' => '1.3', 'letterSpacing' => '0.02em'],
            'overline-group' => ['fontFamily' => 'Poppins', 'fontSize' => '12px', 'fontWeight' => '700', 'lineHeight' => '1.3', 'letterSpacing' => '0.04em'],
            'pill-text' => ['fontFamily' => 'Mulish', 'fontSize' => '11px', 'fontWeight' => '700', 'lineHeight' => '1.2'],
        ];
    }

    /**
     * @return array<array-key, mixed>
     */
    public static function spacing(): array
    {
        return [
            '1' => '2px',
            '2' => '4px',
            '3' => '6px',
            '4' => '8px',
            '5' => '10px',
            '6' => '12px',
            '7' => '14px',
            '8' => '16px',
            '9' => '18px',
            '10' => '20px',
            '11' => '22px',
            '12' => '28px',
            'gutter' => '16px',
            'card-padding' => '18px',
            'row-padding-x' => '14px',
            'row-padding-y' => '11px',
            'content-padding' => '26px 28px 40px',
            'sidebar-width' => '250px',
            'topbar-height' => '62px',
            'radius' => [
                'sm' => '7px',
                'md' => '8px',
                'lg' => '12px',
                'full' => '9999px',
            ],
        ];
    }
}
