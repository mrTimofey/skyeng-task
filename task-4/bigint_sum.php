<?php

/**
 * @param string ...$numbers any length numbers as strings
 * @return string sum
 */
function bigint_sum(string ...$numbers): string
{
    // keep each digit here (from end to start)
    $res = [];
    $pos = 0;
    // keep further positions addition here
    $sumBuffer = 0;
    // flip strings for convenience and remove left zeroes and space chars
    $numbers = array_map(function(string $num): string {
        return strrev(ltrim($num, "0 \t\n\r\0\x0B"));
    }, $numbers);
    // go through number positions from end to start until we have something to add
    while (count($numbers) > 0 || $sumBuffer > 0) {
        // remove exhausted numbers (where current position is bigger then string length)
        if (count($numbers) > 0) {
            $numbers = array_filter($numbers, function (string $str) use ($pos): bool {
                return strlen($str) > $pos;
            });
        }
        $posDigit = $sumBuffer % 10;
        $sumBuffer = (int)($sumBuffer / 10);
        foreach ($numbers as $number) {
            // parse last char as int and add it to current position sum
            $posDigit += (int)$number[$pos];
            if ($posDigit > 9) {
                ++$sumBuffer;
                $posDigit %= 10;
            }
        }
        ++$pos;
        $res[] = $posDigit;
    }

    return implode('', array_reverse($res));
}