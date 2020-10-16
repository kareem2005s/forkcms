<?php

const LIMIT = 100000000;
const MY_VAR = true;

$tests = [
    'filter_var' => static function (): string {
        return (string) filter_var(MY_VAR, FILTER_VALIDATE_BOOLEAN);
    },
    'conditional' => static function (): string {
        return MY_VAR ? '1' : '0';
    },
    'plus' => static function (): string {
        return (string) +MY_VAR;
    },
    'double cast' => static function (): string {
        return (string) (int) MY_VAR;
    },
];

foreach ($tests as $name => $test) {
    // Actual test
    $start = microtime(true);
    for ($i = 1; $i <= LIMIT; $i++) {
        $test();
    }
    $end = microtime(true);

    // Output
    echo number_format(LIMIT) . ' iterations completed for test "' . $name . '" in ' . ($end - $start) . ' seconds';
    echo PHP_EOL;
}
