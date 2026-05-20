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

