<?php

function dump_memory_usage($executor)
{
    $before = memory_get_peak_usage();

    return tap($executor(), function () use ($before) {
        $after = memory_get_peak_usage();

        dump(format_bytes($after - $before));
    });
}

function format_bytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');

    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);

    $bytes /= pow(1024, $pow);

    return round($bytes, $precision) . ' ' . $units[$pow];
}
