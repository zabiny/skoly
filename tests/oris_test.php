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
        'Event_1' => ['ID' => '1', 'Name' => 'Kitl Český pohár 2026', 'Date' => '2026-03-01', 'Place' => '', 'Level' => ['ID' => '8', 'ShortName' => 'ČP'], 'Cancelled' => '0'],
        'Event_2' => ['ID' => '2', 'Name' => 'Přebor škol - podzimní okresní kolo', 'Date' => '2026-10-05', 'Place' => 'Brno', 'Level' => ['ID' => '20', 'ShortName' => 'PS'], 'Cancelled' => '0'],
        'Event_3' => ['ID' => '3', 'Name' => 'Krajské kolo přeboru škol - Jihomoravský kraj', 'Date' => '2026-04-20', 'Place' => 'Brno', 'Level' => ['ID' => '20', 'ShortName' => 'PS'], 'Cancelled' => '0'],
        'Event_4' => ['ID' => '4', 'Name' => 'Přebor škol - zrušené kolo', 'Date' => '2026-05-01', 'Place' => 'Brno', 'Level' => ['ID' => '20', 'ShortName' => 'PS'], 'Cancelled' => '1'],
        'Event_5' => ['ID' => '5', 'Name' => 'Školení rozhodčích 3. třídy', 'Date' => '2026-06-15', 'Place' => 'Brno', 'Level' => ['ID' => '15', 'ShortName' => 'SEM'], 'Cancelled' => '0'],
    ];
}

// --- oris_school_year_range ---

assertEqual(['2025-09-01', '2026-06-30'], oris_school_year_range(2026, 3), 'school_year_range: March 2026 is inside the 2025/2026 season (started last September)');
assertEqual(['2025-09-01', '2026-06-30'], oris_school_year_range(2026, 6), 'school_year_range: June (last month of the season) still resolves to the same season');
assertEqual(['2026-09-01', '2027-06-30'], oris_school_year_range(2026, 7), 'school_year_range: July rolls over to the next season (2026/2027)');
assertEqual(['2026-09-01', '2027-06-30'], oris_school_year_range(2026, 9), 'school_year_range: September (season start) resolves to the season starting that month');

// --- oris_filter_school_events ---

$filtered = oris_filter_school_events(fixtureEvents());
assertEqual(2, count($filtered), 'filter: keeps only non-cancelled Level=20 (Přebor škol) events');
assertEqual('3', $filtered[0]['ID'] ?? null, 'filter: sorts by Date ascending (spring round first)');
assertEqual('2', $filtered[1]['ID'] ?? null, 'filter: second entry is the autumn round');
assertEqual(false, in_array('5', array_column($filtered, 'ID'), true), 'filter: excludes non-school-league events (Level != 20) regardless of name');
assertEqual(false, in_array('4', array_column($filtered, 'ID'), true), 'filter: excludes cancelled Level=20 events');

$filteredEmpty = oris_filter_school_events([
    'Event_1' => ['ID' => '1', 'Name' => 'Kitl Český pohár 2026', 'Date' => '2026-03-01', 'Level' => ['ID' => '8'], 'Cancelled' => '0'],
]);
assertEqual([], $filteredEmpty, 'filter: returns empty array when nothing matches');

$filteredMissingLevel = oris_filter_school_events([
    'Event_1' => ['ID' => '1', 'Name' => 'No level field', 'Date' => '2026-03-01', 'Cancelled' => '0'],
]);
assertEqual([], $filteredMissingLevel, 'filter: treats a missing Level field as non-matching, not a fatal error');

// --- oris_cache_is_fresh ---

$tmpFile = sys_get_temp_dir() . '/oris_test_cache_' . uniqid() . '.json';
assertEqual(false, oris_cache_is_fresh($tmpFile, 900), 'cache: missing file is never fresh');

file_put_contents($tmpFile, '[]');
assertEqual(true, oris_cache_is_fresh($tmpFile, 900), 'cache: just-written file is fresh');

touch($tmpFile, time() - 1000);
assertEqual(false, oris_cache_is_fresh($tmpFile, 900), 'cache: file older than TTL is stale');
unlink($tmpFile);

// --- oris_fetch_raw ---

$goodFile = sys_get_temp_dir() . '/oris_test_good_' . uniqid() . '.json';
file_put_contents($goodFile, json_encode(['Status' => 'OK', 'Data' => ['Event_1' => ['ID' => '1', 'Name' => 'Test']]]));
$goodResult = oris_fetch_raw($goodFile);
assertEqual(['Event_1' => ['ID' => '1', 'Name' => 'Test']], $goodResult, 'fetch_raw: parses Data on Status OK');
unlink($goodFile);

$badStatusFile = sys_get_temp_dir() . '/oris_test_bad_status_' . uniqid() . '.json';
file_put_contents($badStatusFile, json_encode(['Status' => 'ERROR']));
assertEqual(null, oris_fetch_raw($badStatusFile), 'fetch_raw: returns null when Status is not OK');
unlink($badStatusFile);

$malformedFile = sys_get_temp_dir() . '/oris_test_malformed_' . uniqid() . '.json';
file_put_contents($malformedFile, 'not valid json{{{');
assertEqual(null, oris_fetch_raw($malformedFile), 'fetch_raw: returns null on malformed JSON');
unlink($malformedFile);

assertEqual(null, oris_fetch_raw(sys_get_temp_dir() . '/oris_test_does_not_exist_' . uniqid() . '.json'), 'fetch_raw: returns null when the source is unreadable');

// --- oris_get_school_events ---

$cacheDir = sys_get_temp_dir() . '/oris_test_' . uniqid();
$cacheFile = $cacheDir . '/cache.json';

// Fresh fetch, no cache yet
$fetchCalls = 0;
$fetcher = function () use (&$fetchCalls, $cacheFile) {
    global $fetchCalls;
    $fetchCalls++;
    return fixtureEvents();
};
$result = oris_get_school_events($cacheFile, 900, $fetcher);
assertEqual(2, count($result), 'get_events: first call fetches and filters');
assertEqual(1, $fetchCalls, 'get_events: fetcher called once on cold cache');
assertEqual(true, is_file($cacheFile), 'get_events: writes cache file');

// Second call within TTL should NOT call fetcher again
$result2 = oris_get_school_events($cacheFile, 900, $fetcher);
assertEqual(2, count($result2), 'get_events: second call still returns filtered events');
assertEqual(1, $fetchCalls, 'get_events: fetcher NOT called again while cache is fresh');

// Force cache stale, fetcher now fails -> falls back to stale cache
touch($cacheFile, time() - 1000);
$failingFetcher = function () { return null; };
$result3 = oris_get_school_events($cacheFile, 900, $failingFetcher);
assertEqual(2, count($result3), 'get_events: on fetch failure, serves stale cache instead of erroring');

// No cache at all, fetcher fails -> empty list, never null/error
unlink($cacheFile);
rmdir($cacheDir);
$result4 = oris_get_school_events($cacheFile, 900, $failingFetcher);
assertEqual([], $result4, 'get_events: no cache + failed fetch => empty array, not an error');

if ($failures > 0) {
    echo "\n$failures test(s) failed.\n";
    exit(1);
}
echo "\nAll tests passed.\n";
exit(0);
