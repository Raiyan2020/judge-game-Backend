<?php

/**
 * Setting Name
 *
 * @param $name
 * @return mixed
 */
/**
 * Upload Path
 *
 * @return string
 */
function uploadpath()
{
    return 'photos';
}

/**
 * Get Image
 *
 * @param $filename
 * @return string
 */
function getimg($filename)
{
    return asset($filename);
}

/**
 * Upload an image
 *
 * @param $img
 */
function uploader($value)
{
    $path = '/storage/' . \Storage::disk('public')->putFile(uploadpath(), $value);

    return $path;
}
function uploadeFile($folders, $value)
{
    $path = \Storage::disk('public')->putFile($folders, $value);

    return $path;
}

/**
 * Dashboard logo URL (sidebar + login). Falls back to the bundled icon.
 */
function dashboard_logo_url(): string
{
    $setting = \App\Models\Setting::where('name', 'dashboard_logo')->first();
    $path = $setting?->getTranslation('value', 'en') ?: '_dashboard/sidebar-logo.png';

    if (filter_var($path, FILTER_VALIDATE_URL)) {
        return $path;
    }

    return asset(ltrim($path, '/'));
}

/**
 * Cache-bust version for the dashboard logo file.
 */
function dashboard_logo_version(): string
{
    $setting = \App\Models\Setting::where('name', 'dashboard_logo')->first();
    $path = $setting?->getTranslation('value', 'en') ?: '_dashboard/sidebar-logo.png';

    if (filter_var($path, FILTER_VALIDATE_URL)) {
        return '1';
    }

    $fullPath = public_path(ltrim($path, '/'));

    return file_exists($fullPath) ? (string) filemtime($fullPath) : '1';
}



class responder
{
    public static function success($data, $extra = [])
    {
        return response()->json(['status' => true, 'data' => $data] + $extra, 200);
    }

    public static function error($data)
    {
        return response()->json(['status' => false, 'msg' => $data], 422);
    }
}

function whatsapp($phone, $body)
{

    $params = [
        'token' => '93qarq16bsjuynln',
        'to' => $phone,
        'body' => $body,
    ];

    return Http::post('https://api.ultramsg.com/instance63271/messages/chat', $params);
}
function added()
{
    alert()->success(__('Added successfully !'));
}

function updated()
{
    alert()->success(__('Updated successfully !'));
}

function deleted()
{
    alert()->success(__('Deleted successfully !'));
}

function statusChange()
{
    alert()->success(__('Status changed successfully !'));
}

function getClass($status)
{
    return $status == 1 ? 'success' : 'danger';
}

function getStatusName($status)
{
    return $status == 1 ? __('active') : __('inactive');
}

/**
 * Format a percentage value for display (e.g. 40%).
 */
function format_discount_percent($value, bool $withSymbol = true): string
{
    if ($value === null || $value === '') {
        return $withSymbol ? '-' : '';
    }

    $formatted = (string) (int) round((float) $value);

    return $withSymbol ? $formatted . '%' : $formatted;
}

/**
 * Format a package price without decimal places.
 */
function format_package_price($value): string
{
    if ($value === null || $value === '') {
        return '';
    }

    return (string) (int) round((float) $value);
}

/**
 * Application currency code (e.g. KWD).
 */
function app_currency(): string
{
    return (string) config('payment.currency', 'KWD');
}

/**
 * Format a monetary amount with currency and no decimal places.
 */
function format_money($value): string
{
    if ($value === null || $value === '') {
        return '-';
    }

    return format_package_price($value) . ' ' . __(app_currency());
}

/**
 * Format a phone number with its country code for display.
 */
function format_phone_with_code(?string $countryCode, ?string $phone): string
{
    if ($phone === null || $phone === '') {
        return '-';
    }

    $code = $countryCode !== null ? ltrim($countryCode, '+') : '';

    if ($code === '') {
        return $phone;
    }

    return '+' . $code . ' ' . $phone;
}

