<?php

function oris_school_year_range(int $year, int $month): array
{
    if ($month <= 6) {
        return [($year - 1) . '-09-01', $year . '-06-30'];
    }
    return [$year . '-09-01', ($year + 1) . '-06-30'];
}

function oris_filter_school_events(array $events): array
{
    $matches = [];
    foreach ($events as $event) {
        if (($event['Cancelled'] ?? '0') === '1') {
            continue;
        }
        if (($event['Level']['ID'] ?? '') !== '20') {
            continue;
        }
        $matches[] = $event;
    }

    usort($matches, static function (array $a, array $b): int {
        return strcmp($a['Date'] ?? '', $b['Date'] ?? '');
    });

    return $matches;
}

function oris_cache_is_fresh(string $cacheFile, int $ttlSeconds): bool
{
    clearstatcache(true, $cacheFile);
    if (!is_file($cacheFile)) {
        return false;
    }
    return (time() - filemtime($cacheFile)) < $ttlSeconds;
}

function oris_fetch_raw(string $apiUrl): ?array
{
    $context = stream_context_create(['http' => ['timeout' => 5]]);
    $json = @file_get_contents($apiUrl, false, $context);
    if ($json === false) {
        return null;
    }

    $decoded = json_decode($json, true);
    if (!is_array($decoded) || ($decoded['Status'] ?? '') !== 'OK') {
        return null;
    }

    return $decoded['Data'] ?? [];
}

function oris_get_school_events(string $cacheFile, int $ttlSeconds, callable $fetcher): array
{
    if (oris_cache_is_fresh($cacheFile, $ttlSeconds)) {
        $cached = json_decode((string) file_get_contents($cacheFile), true);
        if (is_array($cached)) {
            return $cached;
        }
    }

    $raw = $fetcher();

    if ($raw === null) {
        if (is_file($cacheFile)) {
            $stale = json_decode((string) file_get_contents($cacheFile), true);
            return is_array($stale) ? $stale : [];
        }
        return [];
    }

    $filtered = oris_filter_school_events($raw);

    $dir = dirname($cacheFile);
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    file_put_contents($cacheFile, json_encode($filtered, JSON_UNESCAPED_UNICODE));

    return $filtered;
}
