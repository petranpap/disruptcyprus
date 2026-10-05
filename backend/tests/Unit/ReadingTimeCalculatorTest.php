<?php

use App\Services\ReadingTimeCalculator;

it('counts words in HTML at 200 words per minute, rounding up', function () {
    $calculator = new ReadingTimeCalculator;

    expect($calculator->minutes(null))->toBe(0)
        ->and($calculator->minutes('<p>   </p>'))->toBe(0)
        ->and($calculator->minutes('<p>'.str_repeat('λέξη ', 10).'</p>'))->toBe(1)
        ->and($calculator->minutes('<p>'.str_repeat('word ', 200).'</p>'))->toBe(1)
        ->and($calculator->minutes('<p>'.str_repeat('word ', 201).'</p>'))->toBe(2)
        ->and($calculator->minutes('<p>'.str_repeat('Καινοτομία&nbsp;', 401).'</p>'))->toBe(3);
});
