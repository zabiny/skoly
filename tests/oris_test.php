<?php
require __DIR__ . '/../lib/oris.php';

$failures = 0;

function assertEqual($expected, $actual, string $message): void
{
    global $failures;
    if ($expected !== $actual) {
        $failures++;
        echo "FAIL: $message\n  expected: " . var_export($expected, true) . "\n  actual:   " . var_export($actual, true) . "\n";
    } else {
        echo "PASS: $message\n";
    }
}

function fixtureEvents(): array
{
    return [
        'Event_1' => ['ID' => '1', 'Name' => 'Kitl Český pohár 2026', 'Date' => '2026-03-01', 'Place' => '', 'Cancelled' => '0'],
        'Event_2' => ['ID' => '2', 'Name' => 'Liga škol - podzimní okresní kolo', 'Date' => '2026-10-05', 'Place' => 'Brno', 'Cancelled' => '0'],
        'Event_3' => ['ID' => '3', 'Name' => 'LIGA ŠKOL - jarní krajské kolo', 'Date' => '2026-04-20', 'Place' => 'Brno', 'Cancelled' => '0'],
        'Event_4' => ['ID' => '4', 'Name' => 'Liga škol - zrušené kolo', 'Date' => '2026-05-01', 'Place' => 'Brno', 'Cancelled' => '1'],
    ];
}

// --- oris_filter_liga_skol_events ---

$filtered = oris_filter_liga_skol_events(fixtureEvents());
assertEqual(2, count($filtered), 'filter: keeps only non-cancelled Liga škol events');
assertEqual('3', $filtered[0]['ID'] ?? null, 'filter: sorts by Date ascending (spring round first)');
assertEqual('2', $filtered[1]['ID'] ?? null, 'filter: second entry is the autumn round');

$filteredEmpty = oris_filter_liga_skol_events([
    'Event_1' => ['ID' => '1', 'Name' => 'Kitl Český pohár 2026', 'Date' => '2026-03-01', 'Cancelled' => '0'],
]);
assertEqual([], $filteredEmpty, 'filter: returns empty array when nothing matches');

// --- oris_cache_is_fresh ---

$tmpFile = sys_get_temp_dir() . '/oris_test_cache_' . uniqid() . '.json';
assertEqual(false, oris_cache_is_fresh($tmpFile, 900), 'cache: missing file is never fresh');

file_put_contents($tmpFile, '[]');
assertEqual(true, oris_cache_is_fresh($tmpFile, 900), 'cache: just-written file is fresh');

touch($tmpFile, time() - 1000);
assertEqual(false, oris_cache_is_fresh($tmpFile, 900), 'cache: file older than TTL is stale');
unlink($tmpFile);

// --- oris_get_liga_skol_events ---

$cacheDir = sys_get_temp_dir() . '/oris_test_' . uniqid();
$cacheFile = $cacheDir . '/cache.json';

// Fresh fetch, no cache yet
$fetchCalls = 0;
$fetcher = function () use (&$fetchCalls, $cacheFile) {
    global $fetchCalls;
    $fetchCalls++;
    return fixtureEvents();
};
$result = oris_get_liga_skol_events($cacheFile, 900, $fetcher);
assertEqual(2, count($result), 'get_events: first call fetches and filters');
assertEqual(1, $fetchCalls, 'get_events: fetcher called once on cold cache');
assertEqual(true, is_file($cacheFile), 'get_events: writes cache file');

// Second call within TTL should NOT call fetcher again
$result2 = oris_get_liga_skol_events($cacheFile, 900, $fetcher);
assertEqual(2, count($result2), 'get_events: second call still returns filtered events');
assertEqual(1, $fetchCalls, 'get_events: fetcher NOT called again while cache is fresh');

// Force cache stale, fetcher now fails -> falls back to stale cache
touch($cacheFile, time() - 1000);
$failingFetcher = function () { return null; };
$result3 = oris_get_liga_skol_events($cacheFile, 900, $failingFetcher);
assertEqual(2, count($result3), 'get_events: on fetch failure, serves stale cache instead of erroring');

// No cache at all, fetcher fails -> empty list, never null/error
unlink($cacheFile);
rmdir($cacheDir);
$result4 = oris_get_liga_skol_events($cacheFile, 900, $failingFetcher);
assertEqual([], $result4, 'get_events: no cache + failed fetch => empty array, not an error');

if ($failures > 0) {
    echo "\n$failures test(s) failed.\n";
    exit(1);
}
echo "\nAll tests passed.\n";
exit(0);
