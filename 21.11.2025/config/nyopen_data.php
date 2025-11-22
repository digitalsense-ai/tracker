<?php
return [
    'tables' => [
        'daily'    => env('PRICE_DAILY_TABLE', 'prices_daily'),
        'intraday' => env('PRICE_INTRADAY_TABLE', 'prices_intraday'),
    ],
    'cols' => [
        'ticker' => env('PRICE_COL_TICKER', 'ticker'),
        'date'   => env('PRICE_COL_DATE', 'date'),
        'close'  => env('PRICE_COL_CLOSE', 'close'),

        'ts'     => env('PRICE_COL_TS', 'ts'),
        'open'   => env('PRICE_COL_OPEN', 'open'),
        'high'   => env('PRICE_COL_HIGH', 'high'),
        'low'    => env('PRICE_COL_LOW', 'low'),
        'last'   => env('PRICE_COL_LAST', 'last'),
        'volume' => env('PRICE_COL_VOLUME', 'volume'),
    ],
    'tz' => [
        'market' => env('TZ_MARKET', 'America/New_York'),
        'db'     => env('TZ_DB', 'UTC'),
    ],
];
