<?php

use League\Csv\Writer;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;

/**
 * This route will throw an out of memory exception.
 *
 */
Route::get('eager', function () {
    return Collection::times(1000 * 1000 * 1000 * 1000)
        ->filter(fn ($number) => $number % 2 == 0)
        ->take(1000);
});

/**
 * This route will return the first 1,000 even numbers.
 *
 */
Route::get('lazy', function () {
    return LazyCollection::times(1000 * 1000 * 1000 * 1000)
        ->filter(fn ($number) => $number % 2 == 0)
        ->take(1000);
});

/**
 * This route will only dump "1".
 *
 */
Route::get('multiple-returns', function () {
    $run = function () {
        return 1;
        return 2;
    };

    dump($run());
});

/**
 * This route will dump a Generator object.
 *
 */
Route::get('generator', function () {
    $run = function () {
        dump('Did we get here?');
        yield 1;
        yield 2;
    };

    dump($run());
});

/**
 * This route will dump:
 *     "Did we get here?"
 *     "1"
 */
Route::get('generator-current', function () {
    $run = function () {
        dump('Did we get here?');
        yield 1;
        yield 2;
    };

    $generator = run();

    $firstValue = $generator->current();

    dump($firstValue);
});

/**
 * This route will dump: "2".
 *
 */
Route::get('generator-next', function () {
    $run = function () {
        yield 1;
        yield 2;
    };

    $generator = run();

    $firstValue = $generator->current();
    $generator->next();
    $secondValue = $generator->current();

    dump($secondValue);
});

/**
 * This route will dump:
 *     "1"
 *     "2"
 *     "3"
 */
Route::get('inifinite-loop', function () {
    $generate_numbers = function () {
        $number = 1;

        while (true) yield $number++;
    };

    $generator = $generate_numbers();

    dump($generator->current());
    $generator->next();
    dump($generator->current());
    $generator->next();
    dump($generator->current());
});

/**
 * This route will dump the numbers 1-20.
 *
 */
Route::get('foreach', function () {
    $generate_numbers = function () {
        $number = 1;

        while (true) yield $number++;
    };

    $generator = $generate_numbers();

    foreach ($generator as $number) {
        dump($number);

        if ($number == 20) break;
    }
});
