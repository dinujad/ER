<?php

use Illuminate\Contracts\View\Factory;

function accounting($ul, $pt, $lc, $em, $un, $type = 1, $pid = null)
{
    // stub
}

if (!function_exists('theme_view_file')) {
    function theme_view_file($view = null)
    {
        $factory = app(Factory::class);
        $active_theme = strtolower(config('active_theme', ''));
        if ($active_theme && $factory->exists(str_replace("::", "::themes.$active_theme.", $view))) {
            $view = str_replace("::", "::themes.$active_theme.", $view);
        }
        return $view;
    }
}

if (!function_exists('table_order_link')) {
    function table_order_link($column)
    {
        return request()->fullUrlWithQuery([
            'order_by'     => $column,
            'order_by_dir' => (request('order_by_dir') === 'asc') ? 'desc' : 'asc',
        ]);
    }
}

if (!function_exists('is_admin')) {
    function is_admin()
    {
        return auth()->check() && (
            auth()->user()->is_admin
            ?? (optional(auth()->user()->employee)->role === 'admin')
            ?? false
        );
    }
}

if (!function_exists('is_employee')) {
    function is_employee()
    {
        return auth()->check() && optional(auth()->user()->employee)->id !== null;
    }
}

if (!function_exists('readable_date')) {
    function readable_date($date)
    {
        return date('F j Y', strtotime($date));
    }
}

if (!function_exists('readable_datetime')) {
    function readable_datetime($date)
    {
        return date('F j Y h:i:s a', strtotime($date));
    }
}

if (!function_exists('getMpdf')) {
    function getMpdf()
    {
        // mpdf is not installed; return null gracefully
        return null;
    }
}

if (!function_exists('get_url')) {
    function get_url($url)
    {
        return is_admin() ? $url : 'portal/' . $url;
    }
}

if (!function_exists('get_uniqid')) {
    function get_uniqid()
    {
        return uniqid('TRX-', true);
    }
}

if (!function_exists('get_calendar_year')) {
    function get_calendar_year()
    {
        return [
            '1'  => trans('accounting::lang.january'),
            '2'  => trans('accounting::lang.february'),
            '3'  => trans('accounting::lang.march'),
            '4'  => trans('accounting::lang.april'),
            '5'  => trans('accounting::lang.may'),
            '6'  => trans('accounting::lang.june'),
            '7'  => trans('accounting::lang.july'),
            '8'  => trans('accounting::lang.august'),
            '9'  => trans('accounting::lang.september'),
            '10' => trans('accounting::lang.october'),
            '11' => trans('accounting::lang.november'),
            '12' => trans('accounting::lang.december'),
        ];
    }
}

if (!function_exists('getUrlWithoutParams')) {
    function getUrlWithoutParams($url)
    {
        return explode('?', $url)[0];
    }
}

if (!function_exists('currency_code')) {
    function currency_code()
    {
        return config('accounting.default_currency', 'LKR');
    }
}

if (!function_exists('get_business_name')) {
    function get_business_name()
    {
        return config('app.name', 'DERMAS');
    }
}

if (!function_exists('get_financial_year')) {
    function get_financial_year()
    {
        return (int) date('Y');
    }
}

if (!function_exists('financial_year_start_date')) {
    function financial_year_start_date()
    {
        return get_financial_year() . '-01-01';
    }
}

if (!function_exists('thirty_days_ago')) {
    function thirty_days_ago()
    {
        return date('Y-m-d', strtotime('-30 days'));
    }
}

if (!function_exists('get_date_range')) {
    function get_days_past()
    {
        $today = date('Y-m-d');
        return (object) [
            'today'              => $today,
            'yesterday'          => date('Y-m-d', strtotime('-1 days')),
            'thirty_days_ago'    => date('Y-m-d', strtotime('-30 days')),
            'sixty_days_ago'     => date('Y-m-d', strtotime('-60 days')),
            'ninety_days_ago'    => date('Y-m-d', strtotime('-90 days')),
            'thirty_one_days_ago'  => date('Y-m-d', strtotime('-31 days')),
            'sixty_one_days_ago'   => date('Y-m-d', strtotime('-61 days')),
            'ninety_one_days_ago'  => date('Y-m-d', strtotime('-91 days')),
        ];
    }
}

if (!function_exists('get_keys_from_header_row')) {
    function get_keys_from_header_row($header_row)
    {
        return array_map(function ($key) {
            return strtolower(str_replace([' ', '.'], ['_', ''], $key));
        }, $header_row);
    }
}

if (!function_exists('excel_date_to_php_date')) {
    function excel_date_to_php_date($excel_date)
    {
        return date("Y-m-d", ($excel_date - 25569) * 86400);
    }
}

if (!function_exists('get_chart_colors')) {
    function get_chart_colors()
    {
        return (object)['red' => '#D72828', 'blue' => '#3C8DBC'];
    }
}

if (!function_exists('get_months')) {
    function get_months($year)
    {
        $months = [];
        for ($monthNum = 1; $monthNum <= 12; $monthNum++) {
            $dateObj = DateTime::createFromFormat('!m', $monthNum);
            $month   = strtolower($dateObj->format('F'));
            $months[trans("accounting::lang.$month")] = (object)[
                'start' => date('Y-m-d', strtotime("first day of $month $year")),
                'end'   => date('Y-m-d', strtotime("last day of $month $year")),
            ];
        }
        return $months;
    }
}

if (!function_exists('get_default_year')) {
    function get_default_year()
    {
        return (int) date('Y');
    }
}

if (!function_exists('get_no_days')) {
    function get_no_days()
    {
        return ['days' => 1, 'weeks' => 7, 'months' => 30];
    }
}

if (!function_exists('get_pie_chart_colors')) {
    function get_pie_chart_colors()
    {
        return [
            'light_green'    => '#7AC142',
            'yellow'         => '#FDBB2F',
            'orange'         => '#F47A1F',
            'red'            => '#D72828',
            'blue'           => '#3C8DBC',
            'cyan-blue'      => '#7CDDDD',
            'cream'          => '#FFF1C9',
            'peach'          => '#F7B7A3',
            'melon'          => '#EA5F89',
            'light-purple'   => '#9B3192',
            'darker-purple'  => '#57167E',
            'deep-purple'    => '#2B0B3F',
        ];
    }
}

if (!function_exists('get_module_names')) {
    function get_module_names()
    {
        return (object) [];
    }
}

if (!function_exists('get_accounting_transactions_tabs')) {
    function get_accounting_transactions_tabs()
    {
        return [];
    }
}

if (!function_exists('get_accounting_transactions_index')) {
    function get_accounting_transactions_index()
    {
        return '#';
    }
}

if (!function_exists('get_decimal_places')) {
    function get_decimal_places()
    {
        return 2;
    }
}
