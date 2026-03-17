<?php

namespace PicowindDeps;

// DEFINE A CONFIGURATION FOR THE LIVECANVAS EDITOR
function lc_define_editor_config($key)
{
    $data = ['config_file_slug' => 'daisyui-5.js'];
    return $data[$key];
}
