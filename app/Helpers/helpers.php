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

