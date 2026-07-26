<?php

require __DIR__ . '/lib/oris.php';

$cacheFile = __DIR__ . '/cache/oris-prebor-skol-historie.json';
$apiUrl = sprintf(
    'https://oris.ceskyorientak.cz/API/?format=json&method=getEventList&all=1&level=20&rg=JM&datefrom=%s&dateto=%s',
    '2015-01-01',
    date('Y-m-d')
);

$races = oris_get_school_events($cacheFile, 86400, static function () use ($apiUrl) {
    return oris_fetch_raw($apiUrl);
});
$races = array_reverse($races);

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function formatRaceDate(string $isoDate): string
{
    $timestamp = strtotime($isoDate);
    if ($timestamp === false) {
        return $isoDate;
    }
    return date('j. n. Y', $timestamp);
}
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Výsledky | skoly.zabiny.club</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="page-wrapper">

    <div class="page-header">
        <div class="header-kicker">Přebor škol</div>
        <div class="header-title">Výsledky</div>
        <div class="header-lead">Odkazy na výsledky všech uskutečněných ročníků krajského přeboru škol v orientačním běhu.</div>
    </div>

    <div class="page-body">

        <div class="section">
            <div class="race-list">
<?php if (empty($races)): ?>
                <div class="race-empty">Zatím žádné proběhlé závody, výsledky se objeví po prvním ročníku.</div>
<?php else: ?>
<?php foreach ($races as $index => $race): ?>
                <a class="race-card" href="<?= h('https://oris.ceskyorientak.cz/Vysledky?id=' . ($race['ID'] ?? '')) ?>">
                    <div class="race-num"><?= $index + 1 ?></div>
                    <div class="race-info">
                        <div class="race-name"><?= h($race['Name'] ?? '') ?></div>
                        <div class="race-meta"><?= h(formatRaceDate($race['Date'] ?? '')) ?><?= !empty($race['Place']) ? ' · ' . h($race['Place']) : '' ?></div>
                    </div>
                    <div class="race-link">Výsledky →</div>
                </a>
<?php endforeach; ?>
<?php endif; ?>
            </div>
        </div>

    </div>

    <div class="page-footer">
        <a href="https://zabiny.club"><img class="footer-logo" src="assets/zbm-logo.svg" alt="SK Brno Žabovřesky"></a>
        Pořádá <a href="https://zabiny.club">SK Brno Žabovřesky</a> &middot; <a href="index.php">Zpět na Přebor škol</a>
    </div>

</div>
</body>
</html>
