<?php

use League\Csv\Writer;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;

/**
 * Throws an out of memory exception.
 *
 */
Route::get('eager', function () {
    return Collection::times(1000 * 1000 * 1000)
        ->filter(fn ($number) => $number % 2 == 0)
        ->take(1000);
});

/**
 * Returns the first 1,000 even numbers.
 *
 */
Route::get('lazy', function () {
    return LazyCollection::times(1000 * 1000 * 1000)
        ->filter(fn ($number) => $number % 2 == 0)
        ->take(1000)
        ->values();
});

/**
 * Only dumps "1".
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
 * Dumps a Generator object.
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
 * Dumps:
 *     "Did we get here?"
 *     "1"
 */
Route::get('generator-current', function () {
    $run = function () {
        dump('Did we get here?');
        yield 1;
        yield 2;
    };

    $generator = $run();

    $firstValue = $generator->current();

    dump($firstValue);
});

/**
 * Dumps: "2".
 *
 */
Route::get('generator-next', function () {
    $run = function () {
        yield 1;
        yield 2;
    };

    $generator = $run();

    $firstValue = $generator->current();
    $generator->next();
    $secondValue = $generator->current();

    dump($secondValue);
});

/**
 * Dumps:
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
 * Dumps the numbers 1-20.
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

/**
 * Also dumps the numbers 1-20.
 *
 */
Route::get('composition', function () {
    $generate_numbers = function () {
        $number = 1;

        while (true) yield $number++;
    };

    $take = function ($generator, $limit) {
        foreach ($generator as $index => $value) {
            if ($index == $limit) break;

            yield $value;
        }
    };

    $generator = $generate_numbers();

    foreach ($take($generate_numbers(), 20) as $number) {
        dump($number);
    }
});

/**
 * Returns the numbers 1-10.
 *
 */
Route::get('lazy-collection', function () {
    $collection = LazyCollection::make(function () {
        $number = 1;

        while (true) {
            yield $number++;
        }
    });

    return $collection->take(10);
});

/**
 * Dumps the first 1,000 even numbers.
 *
 */
Route::get('lazy-collection-inifnite', function () {
    LazyCollection::times(INF)
        ->filter(fn ($number) => $number % 2 == 0)
        ->take(1000)
        ->each(fn ($number) => dump($number));
});

/**
 * Streams a 39MB CSV file of fake logins.
 *
 */
Route::get('streamed-download', function () {
    $logins = LazyCollection::times(1000 * 1000, fn () => [
        'user_id' => 24,
        'name' => 'Houdini',
        'logged_in_at' => now()->toIsoString(),
    ]);

    return response()->streamDownload(function () use ($logins) {
        $csvWriter = Writer::createFromFileObject(
            new SplFileObject('php://output', 'w+')
        );

        $csvWriter->insertOne(['User ID', 'Name', 'Login Time']);

        $csvWriter->insertAll($logins);
    }, 'logins.csv');
});

/**
 * Writes a fake login records to the `storage/app/logins.ndjson` file.
 *
 */
Route::get('writing-lazily', function () {
    LazyCollection::times(10 * 1000)
        ->flatMap(fn () => [
            ['user_id' => 1, 'name' => 'Jinfeng'],
            ['user_id' => 2, 'name' => 'Alice'],
        ])
        ->map(fn ($user, $index) => array_merge($user, [
            'timestamp' => now()->addSeconds($index)->toIsoString(),
        ]))
        ->map(fn ($entry) => json_encode($entry))
        ->each(fn ($json) => Storage::append('logins.ndjson', $json));

    return 'Done.';
});

/**
 * Reads the `storage/app/logins.ndjson` file lazily,
 * and dumps the amount of logins from Alice.
 *
 */
Route::get('reading-lazily', function () {
    $logins = LazyCollection::make(function () {
        $handle = fopen(storage_path('app/logins.ndjson'), 'r');

        while (($line = fgets($handle)) !== false) {
            yield $line;
        }
    });

    return $logins
        ->map(fn ($json) => json_decode($json))
        ->filter()
        ->where('name', 'Alice')
        ->count();
});

/**
 * Reads the `storage/app/logins.ndjson` file,
 * and streams each record into a CSV file,
 * all done lazily with virtually no memory.
 *
 */
Route::get('read-and-stream', function () {
    $logins = LazyCollection::make(function () {
        $handle = fopen(storage_path('app/logins.ndjson'), 'r');

        while (($line = fgets($handle)) !== false) {
            yield $line;
        }
    })
    ->map(fn ($json) => json_decode($json, true))
    ->filter();

    return response()->streamDownload(function () use ($logins) {
        $csvWriter = Writer::createFromFileObject(
            new SplFileObject('php://output', 'w+')
        );

        $csvWriter->insertOne(['User ID', 'Name', 'Login Time']);

        $csvWriter->insertAll($logins);
    }, 'logins.csv');
});

/**
 * Converts an eager collection to a lazy collection
 * to count customer in France with a balance over 100 euros.
 *
 */
Route::get('convert-to-lazy', function () {
    return get_all_customers_from_quickbooks()
        ->lazy()
        ->where('country', 'FR')
        ->where('balance', '>', 100)
        ->count();
});






/**
 * A mock function that ostensibely returns
 * all customers in QuickBooks.
 */
function get_all_customers_from_quickbooks() : Collection
{
    return Collection::times(10 * 1000, fn () => [
        'country' => ['CH', 'DE', 'FR', 'MX', 'UK', 'USA'][rand(0, 5)],
        'balance' => rand(50, 150),
    ]);
}
