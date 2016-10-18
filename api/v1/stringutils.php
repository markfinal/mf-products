<?php
function startsWith($string, $prefix)
{
    $length = strlen($prefix);
    return (substr($string, 0, $length) === $prefix);
}

function endsWith($string, $suffix)
{
    $length = strlen($suffix);
    if (0 == $length)
    {
        return true;
    }
    return (substr($string, -$length) === $suffix);
}
?>
