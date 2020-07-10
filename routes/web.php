<?php

use League\Csv\Writer;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;

Route::get('create-log', function () {
    LazyCollection::times(1000)
        ->flatMap(fn () => [
            ['user_id' => 1, 'name' => 'Jinfeng'],
            ['user_id' => 2, 'name' => 'Alice'],
        ])
        ->map(fn ($user, $index) => array_merge($user, [
            'timestamp' => now()->addSeconds($index)->toIsoString(),
        ]))
        ->map(fn ($entry) => json_encode($entry))
        ->each(fn ($json) => Storage::append('logins.ndjson', $json));
});

Route::get('/', function () {
    return response()->streamDownload(function () {

        $csv = Writer::createFromFileObject(
            new SplFileObject('php://output', 'w+')
        );

        $csv->insertOne(['User ID', 'Name', 'Login Time']);

        $csv->insertAll(LazyCollection::times(100 * 1000 * 1000)->map(function () {
            return [
                86758, 'Houdini', now()->toIsoString(),
            ];
        }));

    }, 'login-log.csv');
});


Route::get('stream', function () {

    return response()->streamDownload(function () {
        $csvWriter = Writer::createFromFileObject(
            new SplFileObject('php://output', 'w+')
        );

        $csvWriter->insertOne(['User ID', 'Name', 'Login Time']);

        $logins = LazyCollection::times(100000, fn () => [
            'user_id' => 24,
            'name' => 'Houdini',
            'logged_in_at' => now()->toIsoString(),
        ]);

        $csvWriter->insertAll($logins);
    }, 'logins.csv');
});




















Route::get('mock', function () {

    return response()->streamDownload(function () {

        $csv = Writer::createFromFileObject(
            new SplFileObject('php://output', 'w+')
        );

        $csv->insertOne(['User ID', 'Name', 'Login Time']);

        $csv->insertAll(LazyCollection::times(100 * 1000 * 1000)->map(function () {
            return [
                86758, 'Houdini', now()->toIsoString(),
            ];
        }));

    }, 'login-log.csv');

});


















Route::get('real', function () {

    $collection = LazyCollection::make(function () {
        $handle = fopen(
            storage_path('app/login-log.ndjson'), 'r'
        );

        while (($line = fgets($handle)) !== false) {
            yield $line;
        }
    });

    return response()->streamDownload(function () use ($collection) {

        $csv = Writer::createFromFileObject(
            new SplFileObject('php://output', 'w+')
        );

        $csv->insertOne(['User ID', 'Login Time']);

        $csv->insertAll($collection->map(function ($line) {
            return json_decode($line, true);
        })->filter());

    }, 'login-log.csv');

});




