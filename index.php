<?php

require __DIR__ . '/lib/oris.php';

$cacheFile = __DIR__ . '/cache/oris-liga-skol.json';
$apiUrl = sprintf(
    'https://oris.ceskyorientak.cz/API/?format=json&method=getEventList&region=JHM&datefrom=%s&dateto=%s',
    date('Y-m-d'),
    date('Y-m-d', strtotime('+18 months'))
);

$races = oris_get_liga_skol_events($cacheFile, 900, static function () use ($apiUrl) {
    return oris_fetch_raw($apiUrl);
});

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
    <title>Liga škol | skoly.zabiny.club</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="page-wrapper">

    <div class="page-header">
        <div class="header-kicker">Orientační běh pro školy</div>
        <div class="header-title">Liga škol</div>
        <div class="header-lead">Zábavné závody v orientačním běhu pro základní a střední školy Jihomoravského kraje.</div>
    </div>

    <div class="page-body">

        <p class="intro-text">
            Chceš, aby vaše škola zažila kus dobrodružství přímo ve městě? Liga škol je příležitost,
            jak vzít žáky ven z lavic a ukázat jim, že orientovat se v terénu umí být zábava.
            Každý závodník běží sám s mapou v ruce a hledá kontrolní body v ulicích a parcích —
            a nejlepší dvojice z každé kategorie pak přidává body své škole. Nejde jen o jednotlivce —
            každý závodník tak pomáhá i své škole v celkovém pořadí.
        </p>
        <p class="intro-text">
            Do společného hodnocení počítáme kategorie D5+H5 (DH5), D7+H7+D9+H9 (DH79) a DS+HS (DHS).
            O pořadí škol rozhoduje součet bodů, při shodě pak nižší součet časů. V DH79 a DHS postupují
            dvě nejúspěšnější školy do republikového finále.
        </p>
        <p class="intro-text">
            Přihlašuje škola za celé družstvo, každý závodník startuje ve své věkové kategorii.
        </p>

        <div class="section">
            <div class="section-title">Organizace</div>
            <p class="intro-text" style="margin-bottom: 12px;">
                Závody organizuje náš oddíl orientačního běhu. Počítáme se dvěma závody v sezóně —
                podzimní okresní kolo a jarní krajské kolo, ze kterého se postupuje dál.
            </p>

            <div class="race-list">
<?php if (empty($races)): ?>
                <div class="race-empty">Zatím nejsou závody Ligy škol vypsané, jakmile budou, objeví se tady.</div>
<?php else: ?>
<?php foreach ($races as $index => $race): ?>
                <a class="race-card" href="<?= h('https://oris.ceskyorientak.cz/Zavod?id=' . ($race['ID'] ?? '')) ?>">
                    <div class="race-num"><?= $index + 1 ?></div>
                    <div class="race-info">
                        <div class="race-name"><?= h($race['Name'] ?? '') ?></div>
                        <div class="race-meta"><?= h(formatRaceDate($race['Date'] ?? '')) ?><?= !empty($race['Place']) ? ' · ' . h($race['Place']) : '' ?></div>
                    </div>
                    <div class="race-link">ORIS →</div>
                </a>
<?php endforeach; ?>
<?php endif; ?>
            </div>
        </div>

        <div class="section">
            <div class="section-title">Kategorie</div>
            <div class="cat-list">
                <div class="cat-item"><b>D3/H3</b> — dívky/chlapci 1.–3. třída</div>
                <div class="cat-item"><b>D5/H5</b> — dívky/chlapci 4.–5. třída</div>
                <div class="cat-item"><b>D7/H7</b> — dívky/chlapci 6.–7. třída, prima/sekunda osmiletých gymnázií</div>
                <div class="cat-item"><b>D9/H9</b> — dívky/chlapci 8.–9. třída, prima/sekunda šestiletých gymnázií, tercie/kvarta osmiletých gymnázií</div>
                <div class="cat-item"><b>DS/HS</b> — 1.–4. ročník střední školy, kvinta až oktáva osmiletých gymnázií, tercie až sexta šestiletých gymnázií</div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">Hodnocení</div>
            <p class="intro-text" style="margin-bottom: 0;">
                Do soutěže družstev ve všech kategoriích bodují vždy dva nejlepší závodníci. Body se přidělují
                dle počtu zúčastněných družstev pro každou kategorii zvlášť a sčítají se: v kategorii DH3 = D3 + H3, DH5 = D5 + H5,
                v kategorii DH79 = D7 + H7 + D9 + H9, v kategorii DHS = DS + HS. Pořadí škol je určeno počtem
                získaných bodů, při rovnosti rozhoduje nižší součet časů bodujících členů družstva. Vítězové
                jednotlivců se vyhlašují samostatně v jednotlivých kategoriích. Nejlepší dvě družstva v kategoriích
                DH79 a DHS postupují do republikového finále.
            </p>
        </div>

        <div class="section">
            <div class="section-title">Pravidla a tratě</div>
            <p class="intro-text" style="margin-bottom: 0;">
                Běžíme podle Pravidel orientačního běhu, upravených pro naše podmínky
                (<a href="https://obrozvoj.cz/Pages/PreborSkol/Pravidla.aspx">plné znění zde</a>). Tratě jsou
                orientačně nenáročné a zvládne je i úplný začátečník — buzola není nutná, ale hodí se. Podle
                kategorie měří 1 až 3,5 km, kontroly se sbírají v pořadí podle mapy a popisu kontrol. Na trati
                budou dohlížet pořadatelé.
            </p>
        </div>

        <div class="section">
            <div class="section-title">Info</div>
            <div class="info-grid">
                <div class="info-card">
                    <div class="info-card-label">Mapy</div>
                    <div class="info-card-value">Nejsou voděodolně upravené — když bude pršet, dáme vám na ně plastový obal.</div>
                </div>
                <div class="info-card">
                    <div class="info-card-label">Start</div>
                    <div class="info-card-value">Do koridoru vstupujete 2 minuty před startem, minutu před startem si vezmete mapu a zorientujete ji.</div>
                </div>
                <div class="info-card">
                    <div class="info-card-label">Ražení</div>
                    <div class="info-card-value">Elektronické čipy SportIdent. Nemáš vlastní? Půjčíme ti kartu na místě.</div>
                </div>
                <div class="info-card">
                    <div class="info-card-label">Startovné</div>
                    <div class="info-card-value">Pro školy zdarma.</div>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">Časový průběh závodů</div>
            <div class="info-grid">
                <div class="info-card">
                    <div class="info-card-label">Závodní kancelář</div>
                    <div class="info-card-value">V centru závodů od 9:30 do 10:30.</div>
                </div>
                <div class="info-card">
                    <div class="info-card-label">Start</div>
                    <div class="info-card-value">Čas 00 = 11:00, intervalový dle startovní listiny.</div>
                </div>
            </div>
        </div>

    </div>

    <div class="page-footer">
        Pořádá SK Brno Žabovřesky &middot; <a href="ochrana-udaju.php">Ochrana osobních údajů a fotografování</a>
    </div>

</div>
</body>
</html>
