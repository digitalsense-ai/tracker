<?php

return [
    'scanner' => [
        'candidate_limit' => env('SCANNER_CANDIDATE_LIMIT', 25),
        'allow_symbols'   => env('SCANNER_ALLOW_SYMBOLS', ''),
	    'deny_symbols'    => env('SCANNER_DENY_SYMBOLS', ''),
	    'min_price'       => env('SCANNER_MIN_PRICE', null),
	    'max_price'       => env('SCANNER_MAX_PRICE', null),
	    'require_price'   => env('SCANNER_REQUIRE_PRICE', true),

	    'weights' => [
	        'atr'   => 1.0,
	        'mom'   => 1.0,
	        'range' => 1.0,
	    ],
    ],
];
