<?php

/**
 * Get config value by key.
 * @param string $key
 * @return mixed
 */
function value_from_config(string $key)
{
    // ...
    return './counter.txt';
}

/**
 * Thread-safe counter incrementing.
 */
function increment_counter()
{
    $file = fopen(value_from_config(‘increment_counter_file’), 'c+b');
    flock($file, LOCK_EX);
    $count = (int)fgets($file) + 1;
    rewind($file);
    ftruncate($file, 0);
    fwrite($file, $count);
    flock($file, LOCK_UN);
    fclose($file);
}
