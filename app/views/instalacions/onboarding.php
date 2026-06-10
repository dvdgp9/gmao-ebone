<?php
$title = 'Configurar Instal·lació';
$returnTo = 'instalacions/onboarding/' . (int)$instalacio['id'];
$supportsModuls = !empty($supportsModuls);
$modulsActius = $modulsActius ?? ['espais', 'torns', 'equips'];
$haTriatModuls = !$supportsModuls || ($modulsTriats ?? null) !== null;
$isSuperadmin = !empty($_SESSION['is_superadmin']);

$plantillaUrl = url('import/plantilla');
$importUrl = url('import?return_to=' . urlencode($returnTo) . '&recommended=plantilla');

$modulsInfo = [
    'espais' => [
        'titol' => 'Espais',
        'pregunta' => 'Les tasques es fan en zones diferents que vols distingir?',
        'explicacio' => 'Les zones de la instal·lació: sales, plantes, piscines, vestuaris... Permeten saber ON es fa cada tasca i filtrar per zona.',
        'quan_si' => 'Un poliesportiu amb piscina, gimnàs i vestuaris.',
        'quan_no' => 'Un local petit on tot passa "al mateix lloc".',
    ],
    'torns' => [
        'titol' => 'Torns',
        'pregunta' => 'Hi ha més d\'una persona o horari de treball?',
        'explicacio' => 'Els horaris de treball del personal (Matí, Tarda, Cap de Setmana...). Permeten assignar tasques i usuaris a cada torn, i que cada tècnic vegi només les seves.',
        'quan_si' => 'Equips de manteniment amb torns rotatius.',
        'quan_no' => 'Una sola persona que ho fa tot.',
    ],
    'equips' => [
        'titol' => 'Equips',
        'pregunta' => 'Vols inventariar la maquinària?',
        'explicacio' => 'L\'inventari de maquinària: calderes, bombes, climatitzadors... Permet vincular tasques a equips concrets i tenir l\'historial de cada màquina.',
        'quan_si' => 'Sales tècniques amb maquinària que rep manteniment regular.',
        'quan_no' => 'Només necessites una llista de tasques periòdiques.',
    ],
];

// Passos de configuració segons mòduls actius
$steps = [];
if (in_array('espais', $modulsActius, true)) {
    $steps[] = [
        'titol' => 'Espais',
        'count' => (int)($stats['espais'] ?? 0),
        'explicacio' => 'Defineix les zones de la instal·lació. Després, cada tasca podrà indicar on es fa.',
        'recollir' => [
            'Fes una llista de totes les zones on es fan tasques de manteniment.',
            'Per cada zona: el nom (obligatori), un codi curt si en feu servir, i la planta on és.',
            'Font típica: el plànol de la instal·lació o simplement una volta caminant.',
        ],
        'exemple' => 'Piscina gran (codi PG, planta -1) · Sala fitness (codi SF, planta 1) · Vestuaris (planta 0)',
        'manual_url' => url('espais/create?return_to=' . urlencode($returnTo)),
        'manual_cta' => 'Afegir espais a mà',
    ];
}
if (in_array('torns', $modulsActius, true)) {
    $steps[] = [
        'titol' => 'Torns',
        'count' => (int)($stats['torns'] ?? 0),
        'explicacio' => 'Defineix els horaris de treball. Després podràs assignar usuaris i tasques a cada torn.',
        'recollir' => [
            'Quins torns té el personal de manteniment (p.ex. Matí, Tarda, Cap de Setmana).',
            'Per cada torn: quins dies de la setmana treballa i, si vols, l\'horari.',
        ],
        'exemple' => 'Matí (dilluns a divendres, 06:00–14:00) · Cap de Setmana (dissabte i diumenge)',
        'manual_url' => url('torns/create?return_to=' . urlencode($returnTo)),
        'manual_cta' => 'Afegir torns a mà',
    ];
}
if (in_array('equips', $modulsActius, true)) {
    $steps[] = [
        'titol' => 'Equips',
        'count' => (int)($stats['equips'] ?? 0),
        'explicacio' => 'Inventaria la maquinària que rep manteniment. Després podràs vincular tasques a equips concrets.',
        'recollir' => [
            'Llista de màquines i instal·lacions tècniques: calderes, bombes, climatitzadors, quadres elèctrics...',
            'Per cada equip: nom descriptiu (obligatori), codi intern si en feu servir, model, ubicació i empresa mantenidora.',
            'Consell: fes una volta per les sales tècniques amb el mòbil i fotografia les plaques de característiques.',
        ],
        'exemple' => 'Caldera ACS principal (ACS-CAL-1, Viessmann Vitoplex 200, planta -1, Tècnics Calor SL)',
        'manual_url' => url('equips/create?return_to=' . urlencode($returnTo)),
        'manual_cta' => 'Afegir equips a mà',
    ];
}
$steps[] = [
    'titol' => 'Pla de tasques',
    'count' => (int)($stats['tasques_pla'] ?? 0),
    'explicacio' => 'El cor del GMAO: la llista de tasques periòdiques amb la seva freqüència. Amb això ja tindràs les vistes de dia i setmana funcionant.',
    'recollir' => [
        'Totes les tasques de manteniment que es fan (o s\'haurien de fer) periòdicament.',
        'Per cada tasca: descripció clara (obligatori) i cada quan es fa (obligatori).' . (count($modulsActius) > 0 ? ' Opcionalment: ' . implode(', ', array_map(fn($m) => mb_strtolower($modulsInfo[$m]['titol']), $modulsActius)) . '.' : ''),
        'Bones fonts: contractes de manteniment, llibre de manteniment de l\'edifici, normativa aplicable, i el que el personal ja fa de memòria.',
    ],
    'exemple' => 'Revisió temperatura i clor de l\'aigua (diària) · Neteja filtres climatització (mensual) · Revisió extintors (anual)',
    'manual_url' => url('pla/alta-rapida?return_to=' . urlencode($returnTo)),
    'manual_cta' => 'Afegir tasques a mà (alta ràpida)',
];

$completed = count(array_filter($steps, fn($s) => $s['count'] > 0));
ob_start();
?>

<div class="mb-6">
    <?php if ($isSuperadmin): ?>
    <a href="<?= url('instalacions') ?>" class="text-sm text-gray-500 hover:text-brand transition flex items-center gap-1">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Tornar a instal·lacions
    </a>
    <?php endif; ?>
    <div class="mt-3 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <h2 class="text-2xl sm:text-3xl font-bold text-gray-800">Posem en marxa <?= e($instalacio['nom']) ?></h2>
            <p class="text-gray-500 text-sm mt-1">Aquesta pantalla et guia pas a pas. Pots tornar-hi sempre que vulguis.</p>
        </div>
        <?php if ($haTriatModuls): ?>
        <div class="bg-brand-light text-brand-dark rounded-xl px-4 py-3 text-sm">
            <div class="font-semibold"><?= $completed ?> de <?= count($steps) ?> blocs amb dades</div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($supportsModuls): ?>
<!-- Pas 0: selecció de mòduls -->
<div x-data="{ obert: <?= $haTriatModuls ? 'false' : 'true' ?> }" class="bg-white rounded-xl shadow-sm border <?= $haTriatModuls ? 'border-gray-200' : 'border-brand/40' ?> p-5 sm:p-6 mb-6">
    <div class="flex items-start justify-between gap-4">
        <div class="min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full text-sm font-semibold <?= $haTriatModuls ? 'bg-green-100 text-green-700' : 'bg-brand text-white' ?>">0</span>
                <h3 class="text-lg font-semibold text-gray-800">Què necessita aquesta instal·lació?</h3>
            </div>
            <p class="text-sm text-gray-500 mt-2">
                Cada instal·lació és diferent. Tria només els blocs que necessites: el menú i la plantilla s'adaptaran.
                El <span class="font-medium text-gray-700">pla de tasques sempre està inclòs</span>.
                Podràs canviar-ho més endavant sense perdre res.
            </p>
            <?php if ($haTriatModuls): ?>
            <p class="text-sm text-gray-600 mt-2">
                Blocs actius:
                <span class="font-medium text-gray-800"><?= empty($modulsActius) ? 'cap (només pla de tasques)' : e(implode(', ', array_map(fn($m) => $modulsInfo[$m]['titol'], $modulsActius))) ?></span>
            </p>
            <?php endif; ?>
        </div>
        <?php if ($haTriatModuls): ?>
        <button type="button" @click="obert = !obert" class="shrink-0 text-sm text-brand hover:text-brand-dark transition" x-text="obert ? 'Tancar' : 'Canviar'"></button>
        <?php endif; ?>
    </div>

    <form x-show="obert" x-collapse method="POST" action="<?= url('instalacions/moduls/' . (int)$instalacio['id']) ?>" class="mt-5">
        <?= csrf_field() ?>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <?php foreach ($modulsInfo as $clau => $info): ?>
            <label class="flex flex-col gap-2 rounded-xl border border-gray-200 p-4 cursor-pointer hover:border-brand/50 transition has-[:checked]:border-brand has-[:checked]:bg-brand-light/40">
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="moduls[]" value="<?= $clau ?>" <?= in_array($clau, $modulsActius, true) && $haTriatModuls ? 'checked' : '' ?>
                           class="w-4 h-4 text-brand border-gray-300 rounded focus:ring-brand">
                    <span class="font-semibold text-gray-800"><?= e($info['titol']) ?></span>
                </div>
                <p class="text-xs font-medium text-gray-700"><?= e($info['pregunta']) ?></p>
                <p class="text-xs text-gray-500"><?= e($info['explicacio']) ?></p>
                <p class="text-xs text-green-700"><span class="font-medium">Sí, si:</span> <?= e($info['quan_si']) ?></p>
                <p class="text-xs text-gray-400"><span class="font-medium">No cal, si:</span> <?= e($info['quan_no']) ?></p>
            </label>
            <?php endforeach; ?>
        </div>
        <button type="submit" class="mt-4 bg-brand text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-brand-dark transition">
            Desar i continuar
        </button>
    </form>
</div>
<?php endif; ?>

<?php if ($haTriatModuls): ?>
<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    <div class="xl:col-span-2 space-y-4">

        <!-- Camí recomanat: plantilla -->
        <div class="bg-white rounded-xl shadow-sm border border-brand/25 p-5 sm:p-6">
            <div class="inline-flex items-center rounded-full bg-brand-light px-3 py-1 text-xs font-semibold text-brand-dark">
                El camí més fàcil
            </div>
            <h3 class="mt-3 text-xl font-bold text-gray-800">Omple la plantilla Excel i puja-la</h3>
            <div class="mt-3 space-y-2 text-sm text-gray-600">
                <p><span class="font-semibold text-gray-800">1.</span> Descarrega la plantilla: està adaptada als blocs que has triat i porta una fulla d'instruccions amb exemples ja omplerts.</p>
                <p><span class="font-semibold text-gray-800">2.</span> Omple-la amb calma (pots fer-ho en diverses sessions, és un Excel normal).</p>
                <p><span class="font-semibold text-gray-800">3.</span> Puja-la aquí. Veuràs una previsualització abans que es desi res.</p>
            </div>
            <div class="mt-4 flex flex-col sm:flex-row gap-3">
                <a href="<?= $plantillaUrl ?>" class="inline-flex items-center justify-center gap-2 bg-brand text-white px-4 py-2.5 rounded-lg text-sm font-semibold hover:bg-brand-dark active:scale-[0.98] transition">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 16V4m0 12l-4-4m4 4l4-4M4 20h16"/></svg>
                    1. Descarregar plantilla
                </a>
                <a href="<?= $importUrl ?>" class="inline-flex items-center justify-center gap-2 border border-brand/40 bg-white text-brand-dark px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-brand-light/50 transition">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                    3. Pujar plantilla omplerta
                </a>
            </div>
            <p class="mt-3 text-xs text-gray-500">Prefereixes anar bloc a bloc dins l'aplicació? Cada pas de sota també es pot fer a mà.</p>
        </div>

        <!-- Passos explicats -->
        <?php foreach ($steps as $index => $step): ?>
        <?php $done = $step['count'] > 0; ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-center gap-3 flex-wrap">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full text-sm font-semibold <?= $done ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' ?>"><?= $index + 1 ?></span>
                    <h3 class="text-lg font-semibold text-gray-800"><?= e($step['titol']) ?></h3>
                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium <?= $done ? 'bg-green-50 text-green-700' : 'bg-yellow-50 text-yellow-700' ?>">
                        <?= $done ? 'Té dades' : 'Pendent' ?>
                    </span>
                </div>
                <div class="text-right shrink-0">
                    <div class="text-2xl font-bold text-gray-800"><?= $step['count'] ?></div>
                    <div class="text-xs text-gray-400">registres</div>
                </div>
            </div>

            <p class="text-sm text-gray-600 mt-3"><?= e($step['explicacio']) ?></p>

            <div class="mt-3 rounded-lg bg-gray-50 border border-gray-200 p-3">
                <p class="text-xs font-semibold text-gray-700 uppercase tracking-wide mb-1.5">Què has de recollir</p>
                <ul class="space-y-1 text-sm text-gray-600 list-disc list-inside">
                    <?php foreach ($step['recollir'] as $punt): ?>
                    <li><?= e($punt) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="mt-2 rounded-lg bg-brand-light/40 border border-brand/15 p-3">
                <p class="text-xs font-semibold text-brand-dark uppercase tracking-wide mb-1">Exemple</p>
                <p class="text-sm text-gray-700"><?= e($step['exemple']) ?></p>
            </div>

            <div class="mt-4 flex flex-col sm:flex-row sm:items-center gap-3">
                <a href="<?= $step['manual_url'] ?>" class="inline-flex items-center justify-center gap-2 bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-50 transition">
                    <?= e($step['manual_cta']) ?>
                </a>
                <span class="text-xs text-gray-400">O inclou-ho a la plantilla Excel i puja-ho tot de cop.</span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="space-y-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <h3 class="text-lg font-semibold text-gray-800">Progrés</h3>
            <div class="mt-4 space-y-3 text-sm">
                <?php foreach ($steps as $step): ?>
                <div class="flex items-center justify-between gap-3">
                    <span class="text-gray-500"><?= e($step['titol']) ?></span>
                    <span class="font-semibold <?= $step['count'] > 0 ? 'text-green-700' : 'text-gray-800' ?>"><?= $step['count'] ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <h3 class="text-lg font-semibold text-gray-800">Quan acabis</h3>
            <p class="text-sm text-gray-500 mt-2">Amb el pla de tasques carregat, les vistes de dia i setmana ja mostraran la feina pendent.</p>
            <div class="mt-4 flex flex-col gap-3">
                <a href="<?= url('dashboard') ?>" class="inline-flex items-center justify-center gap-2 bg-gray-100 text-gray-700 px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-gray-200 transition">
                    Anar al dashboard
                </a>
                <a href="<?= url('dia') ?>" class="inline-flex items-center justify-center gap-2 bg-white border border-gray-300 text-gray-700 px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-gray-50 transition">
                    Veure la vista diària
                </a>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <h3 class="text-lg font-semibold text-gray-800">Dubtes freqüents</h3>
            <div class="mt-3 space-y-3 text-sm text-gray-600">
                <p><span class="font-medium text-gray-800">Puc fer-ho en diverses sessions?</span> Sí. Tot el que desis es queda, i aquesta pantalla mostra el progrés.</p>
                <p><span class="font-medium text-gray-800">M'he equivocat en un bloc?</span> Pots editar o esborrar qualsevol element des del seu apartat del menú.</p>
                <p><span class="font-medium text-gray-800">Puc activar un bloc més endavant?</span> Sí, des del pas 0 d'aquesta pantalla, sense perdre res.</p>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
