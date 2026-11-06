<?php
/** 头像挂件清单：key => 名称。素材为 300x300 透明动图，套在圆形头像外圈。 */
function user_avatar_frames()
{
    return array(
        'frame_01' => 'S3定制',
        'frame_02' => 'SSS明星',
        'frame_03' => 'SS明星',
        'frame_04' => 'S明星',
        'frame_05' => '两情相悦（女）',
        'frame_06' => '两情相悦（男）',
        'frame_07' => '冰晶之核',
        'frame_08' => '初心不负',
        'frame_09' => '灿烂银河',
        'frame_10' => '纯真天蝎',
    );
}

/** 挂件白名单校验：只认清单里的 key，杜绝任意路径 / 目录穿越 */
if (!function_exists('user_avatar_frame_key')) {
    function user_avatar_frame_key($key)
    {
        $key = (string)$key;
        if ($key === '') {
            return '';
        }
        $frames = user_avatar_frames();
        return isset($frames[$key]) ? $key : '';
    }
}

/** 挂件动图 URL（透明 GIF，叠在头像外圈） */
if (!function_exists('user_avatar_frame_url')) {
    function user_avatar_frame_url($key)
    {
        $key = user_avatar_frame_key($key);
        return $key === '' ? '' : '/assets/avatar-frames/' . $key . '.gif';
    }
}

/** 选择网格里的静态预览图（首帧 PNG，比动图轻，10 个一起加载不卡） */
if (!function_exists('user_avatar_frame_preview')) {
    function user_avatar_frame_preview($key)
    {
        $key = user_avatar_frame_key($key);
        return $key === '' ? '' : '/assets/avatar-frames/' . $key . '.png';
    }
}

if (!function_exists('user_avatar_frame_name')) {
    function user_avatar_frame_name($key)
    {
        $key = user_avatar_frame_key($key);
        if ($key === '') {
            return '无挂件';
        }
        $frames = user_avatar_frames();
        return $frames[$key];
    }
}
