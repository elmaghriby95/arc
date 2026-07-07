<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Maximum upload file size (kilobytes)
    |--------------------------------------------------------------------------
    |
    | Must be <= PHP upload_max_filesize and web server body size limits.
    | Default 65536 KB = 64 MB (matches typical production PHP settings).
    |
    */
    'max_file_kb' => (int) env('UPLOAD_MAX_FILE_KB', 65536),

];
