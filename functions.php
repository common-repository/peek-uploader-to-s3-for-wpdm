<?php

function str_replace_first($from, $to, $subject)
{
    $from = '/'.preg_quote($from, '/').'/';
    return preg_replace($from, $to, $subject, 1);
}

function del_nil($arr)
{
    return array_filter($arr, 'remove_null');
}

function remove_null($val)
{
    return !is_null($val);
}
