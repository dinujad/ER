<?php

use App\Support\NoopActivityLogger;
use Illuminate\Support\Collection;

if (!function_exists('activity')) {
    /**
     * @param  string|null  $logName  Ignored; API compatibility only.
     */
    function activity(?string $logName = null): NoopActivityLogger
    {
        return app(NoopActivityLogger::class);
    }
}

/**
 * Minimal HTML form helpers (replaces laravelcollective/html Form facade for Accounting views).
 */
if (!function_exists('acct_form_attrs')) {
    function acct_form_attrs(array $attrs): string
    {
        $out = '';
        foreach ($attrs as $key => $val) {
            if (is_int($key)) {
                if ($val === false || $val === null) {
                    continue;
                }
                if (is_string($val)) {
                    $out .= ' '.e($val);
                }

                continue;
            }
            if ($val === false || $val === null) {
                continue;
            }
            if ($val === true) {
                $out .= ' '.e($key);
            } else {
                $out .= ' '.e($key).'="'.e((string) $val).'"';
            }
        }

        return $out;
    }
}

if (!function_exists('acct_form_label')) {
    function acct_form_label(string $for, string $text, array $attributes = []): string
    {
        $attributes['for'] = $for;

        return '<label'.acct_form_attrs($attributes).'>'.e($text).'</label>';
    }
}

if (!function_exists('acct_normalize_select_options')) {
    /**
     * @return array<int|string, mixed>
     */
    function acct_normalize_select_options($options): array
    {
        if ($options instanceof Collection) {
            return $options->all();
        }
        if ($options instanceof \Traversable && !is_array($options)) {
            return iterator_to_array($options);
        }

        return is_array($options) ? $options : [];
    }
}

if (!function_exists('acct_form_select')) {
    function acct_form_select(string $name, $options, $selected = null, array $attributes = []): string
    {
        $options = acct_normalize_select_options($options);
        $placeholder = $attributes['placeholder'] ?? null;
        unset($attributes['placeholder']);

        if (!isset($attributes['id'])) {
            $attributes['id'] = $name;
        }
        if (!isset($attributes['name'])) {
            $attributes['name'] = $name;
        }

        $html = '<select'.acct_form_attrs($attributes).'>';
        $hasEmptyKey = array_key_exists('', $options);
        if ($placeholder !== null && !$hasEmptyKey) {
            $phSelected = ($selected === null || $selected === '') ? ' selected' : '';
            $html .= '<option value=""'.$phSelected.'>'.e($placeholder).'</option>';
        }
        foreach ($options as $value => $label) {
            $isSelected = ((string) $selected === (string) $value) ? ' selected' : '';
            $labelStr = is_scalar($label) ? (string) $label : '';
            $html .= '<option value="'.e((string) $value).'"'.$isSelected.'>'.e($labelStr).'</option>';
        }
        $html .= '</select>';

        return $html;
    }
}

if (!function_exists('acct_form_text')) {
    function acct_form_text(string $name, $value = null, array $attributes = []): string
    {
        $merged = $attributes;
        if (!isset($merged['type'])) {
            $merged['type'] = 'text';
        }
        if (!isset($merged['name'])) {
            $merged['name'] = $name;
        }
        if (!isset($merged['id'])) {
            $merged['id'] = $name;
        }
        if ($value !== null && $value !== '') {
            $merged['value'] = $value;
        }

        return '<input'.acct_form_attrs($merged).' />';
    }
}

if (!function_exists('acct_form_checkbox')) {
    function acct_form_checkbox(string $name, $value = 1, bool $checked = false, array $attributes = []): string
    {
        $a = $attributes;
        $a['type'] = 'checkbox';
        $a['name'] = $name;
        $a['value'] = (string) $value;
        if ($checked) {
            $a['checked'] = true;
        }

        return '<input'.acct_form_attrs($a).' />';
    }
}

if (!function_exists('acct_form_file')) {
    function acct_form_file(string $name, array $attributes = []): string
    {
        $a = $attributes;
        $a['type'] = 'file';
        $a['name'] = $name;

        return '<input'.acct_form_attrs($a).' />';
    }
}

if (!function_exists('acct_safe_action')) {
    /**
     * Like action() but returns "#" when the controller action is not registered (e.g. legacy POS routes).
     */
    function acct_safe_action(array|string $name, mixed $parameters = [], bool $absolute = true): string
    {
        try {
            return action($name, $parameters, $absolute);
        } catch (\Throwable $e) {
            return '#';
        }
    }
}

if (!function_exists('acct_safe_route')) {
    function acct_safe_route(string $name, mixed $parameters = [], bool $absolute = true): string
    {
        try {
            return route($name, $parameters, $absolute);
        } catch (\Throwable $e) {
            return '#';
        }
    }
}
