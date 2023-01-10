<?php
    $return = include \Marinar\Marinar\Marinar::getPackageMainDir()
        .DIRECTORY_SEPARATOR.'config'
        .DIRECTORY_SEPARATOR.'marinar.php';
    return array_merge($return, [

    ]);
