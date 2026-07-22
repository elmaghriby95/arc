<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Maximum upload file size (kilobytes)
    |--------------------------------------------------------------------------
    |
    | Must be <= PHP upload_max_filesize and web server body size limits.
    | Default 409600 KB = 400 MB.
    |
    */
    'max_file_kb' => (int) env('UPLOAD_MAX_FILE_KB', 409600),

];
