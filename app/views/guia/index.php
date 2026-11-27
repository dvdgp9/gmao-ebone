<?php
$title = "Guia d'ús";

$perfils = [
    'tots' => 'Tots els apartats',
    'tecnic' => 'Tècnic',
    'cap' => 'Cap de manteniment',
    'admin' => 'Administració',
];
$etiquetes = [
    'tecnic' => ['Tècnic', 'bg-purple-50 text-purple-700'],
    'cap' => ['Cap de manteniment', 'bg-amber-50 text-amber-700'],
    'admin' => ['Administració', 'bg-brand-light text-brand-dark'],
    'super' => ['Només superadmin', 'bg-gray-100 text-gray-700'],
];

/** Etiquetes de rol d'un apartat. */
$rols = function (array $llista) use ($etiquetes): string {
    $html = '';
    foreach ($llista as $r) {
        [$text, $classes] = $etiquetes[$r];
        $html .= '<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ' . $classes . '">' . e($text) . '</span>';
    }
    return $html;
};

/** Captura amb peu; es pot ampliar amb un clic. */
$fig = function (string $fitxer, string $alt, string $peu = '', bool $estreta = false): string {
    $dir = __DIR__ . '/../../../public/img/guia/';
    // Captures d'escriptori a 1,5x i de mòbil («-m») a 2x: es mostren com a màxim a la seva mida real.
    $dim = function (string $f, float $escala) use ($dir): string {
        $mida = @getimagesize($dir . $f);
        return $mida ? ' width="' . (int)round($mida[0] / $escala) . '" height="' . (int)round($mida[1] / $escala) . '"' : '';
    };
    $mobil = preg_replace('/\.jpg$/', '-m.jpg', $fitxer);
    $font = is_file($dir . $mobil)
        ? '<source media="(max-width: 767px)" srcset="' . e(url('img/guia/' . $mobil)) . '"' . $dim($mobil, 2) . '>'
        : '';
    return '<figure class="my-4 ' . ($estreta ? 'max-w-sm' : '') . '">'
        . '<button type="button" class="block max-w-full text-left group" @click="obrir($el.querySelector(\'img\'))">'
        . '<picture>' . $font
        . '<img src="' . e(url('img/guia/' . $fitxer)) . '" alt="' . e($alt) . '"' . $dim($fitxer, 1.5) . ' loading="lazy" class="block max-w-full h-auto rounded-lg border border-gray-200 shadow-sm group-hover:border-brand transition">'
        . '</picture>'
        . '</button>'
        . ($peu !== '' ? '<figcaption class="mt-1.5 text-xs text-gray-500">' . $peu . '</figcaption>' : '')
        . '</figure>';
};

/** On és l'apartat al menú. */
$on = fn (string $cami): string => '<p class="mt-2 text-xs text-gray-500"><span class="font-semibold text-gray-600">On és:</span> menú lateral → <span class="font-medium text-gray-700">' . $cami . '</span></p>';

$apartats = [
    ['abans', '00', 'Abans de començar', ['tecnic', 'cap', 'admin']],
    ['jornada', '01', 'La meva jornada', ['tecnic']],
    ['dia', '02', 'Vista Diària: marcar les tasques', ['tecnic', 'cap', 'admin']],
    ['parametres', '03', 'Paràmetres de piscina', ['tecnic', 'cap', 'admin']],
    ['setmana', '04', 'Vista Setmanal', ['tecnic', 'cap', 'admin']],
    ['registre', '05', 'Registre', ['tecnic', 'cap', 'admin']],
    ['resum', '06', 'Resum general', ['cap', 'admin']],
    ['incidencies', '07', 'Incidències', ['cap', 'admin']],
    ['pla', '08', 'Pla de manteniment', ['cap', 'admin']],
    ['equips', '09', 'Equips', ['cap', 'admin']],
    ['espais', '10', 'Espais', ['cap', 'admin']],
    ['repositori', '11', 'Repositori de tasques', ['admin']],
    ['sistemes', '12', 'Sistemes', ['admin']],
    ['usuaris', '13', 'Usuaris', ['admin']],
    ['torns', '14', 'Torns', ['admin']],
    ['configuracio', '15', 'Configuració (primers passos)', ['admin']],
    ['importar', '16', 'Importar Excel', ['admin']],
    ['instalacions', '17', 'Instal·lacions', ['admin', 'super']],
];
$visible = fn (array $r): string => "perfil === 'tots' || " . json_encode(array_values(array_diff($r, ['super']))) . ".includes(perfil)";
$cap = function (string $id) use ($apartats, $rols, $visible): string {
    foreach ($apartats as [$aid, $num, $titol, $r]) {
        if ($aid !== $id) continue;
        return '<section id="' . $aid . '" x-show="' . e($visible($r)) . '" class="guia-apartat bg-white rounded-xl border border-gray-200 shadow-sm p-5 sm:p-7 scroll-mt-6">'
            . '<header class="flex items-start gap-4 mb-4">'
            . '<span class="shrink-0 font-mono text-2xl font-semibold text-brand leading-none pt-1">' . $num . '</span>'
            . '<div class="min-w-0"><h3 class="text-lg sm:text-xl font-bold text-gray-800">' . e($titol) . '</h3>'
            . '<div class="mt-1.5 flex flex-wrap gap-1.5">' . $rols($r) . '</div></div></header>';
    }
    return '';
};
$pas = fn (int $n, string $html): string => '<li class="flex gap-3"><span class="shrink-0 mt-0.5 flex h-6 w-6 items-center justify-center rounded-full bg-brand text-white text-xs font-semibold">' . $n . '</span><div class="min-w-0 flex-1 text-sm text-gray-700 leading-relaxed">' . $html . '</div></li>';
$nota = fn (string $html, string $tipus = 'info'): string => '<div class="my-4 rounded-lg border px-4 py-3 text-sm leading-relaxed '
    . ($tipus === 'avis' ? 'border-amber-200 bg-amber-50 text-amber-900' : 'border-brand/20 bg-brand-light text-gray-700') . '">' . $html . '</div>';
$ui = fn (string $t): string => '<span class="inline-block rounded border border-gray-200 bg-gray-50 px-1.5 text-[0.8em] font-medium text-gray-800 whitespace-nowrap">' . $t . '</span>';

ob_start();
?>

<div x-data="{
        perfil: <?= e(json_encode($perfilInicial)) ?>,
        zoom: null,
        avall: false,
        obrir(img) {
            // Al mòbil, una captura d'ordinador s'obre a mida real i es pot desplaçar; la resta s'ajusta a la pantalla.
            const src = img.currentSrc || img.src;
            const ample = window.innerWidth < 768 && !src.includes('-m.jpg') ? Math.round(img.naturalWidth / 1.5) : null;
            this.zoom = { src, alt: img.alt, ample };
        },
    }" @keydown.escape.window="zoom = null" @scroll.window.throttle.150ms="avall = window.scrollY > 700">

    <div class="mb-6 max-w-3xl">
        <p class="text-xs font-semibold uppercase tracking-wider text-brand">Guia d'ús</p>
        <h2 class="mt-1 text-xl sm:text-2xl font-bold text-gray-800">Com fer servir el GMAO, apartat per apartat</h2>
        <p class="mt-2 text-sm text-gray-500 leading-relaxed">Què hi ha a cada pantalla i com es fa cada cosa, amb captures de cada pas. Per defecte veus els apartats del teu rol; pots canviar-ho aquí sota. Toca o fes clic a qualsevol captura per veure-la més gran.</p>
    </div>

    <div class="mb-6 flex flex-wrap items-center gap-2">
        <span class="text-sm text-gray-500 mr-1">Mostra:</span>
        <?php foreach ($perfils as $clau => $nom): ?>
        <button type="button" @click="perfil = '<?= $clau ?>'"
                :class="perfil === '<?= $clau ?>' ? 'bg-brand text-white border-brand' : 'bg-white text-gray-700 border-gray-300 hover:border-brand'"
                class="rounded-full border px-3.5 py-1.5 text-sm transition"><?= e($nom) ?></button>
        <?php endforeach; ?>
    </div>

    <div class="max-w-4xl">
        <nav id="index-guia" class="mb-6 rounded-xl border border-gray-200 bg-white p-4 sm:p-5 shadow-sm scroll-mt-6" aria-label="Índex de la guia">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-2">En aquesta guia</p>
            <ol class="grid gap-x-4 gap-y-0.5 text-sm sm:grid-cols-2 lg:grid-cols-3">
                <?php foreach ($apartats as [$aid, $num, $titol, $r]): ?>
                <li x-show="<?= e($visible($r)) ?>">
                    <a href="#<?= $aid ?>" class="flex gap-2 rounded-md px-2 py-1.5 text-gray-700 hover:bg-brand-light hover:text-brand-dark transition">
                        <span class="font-mono text-xs text-gray-400 pt-0.5"><?= $num ?></span><span><?= e($titol) ?></span>
                    </a>
                </li>
                <?php endforeach; ?>
            </ol>
        </nav>

        <div class="min-w-0 space-y-6">

<!-- 00 ─────────────────────────────────────────── -->
<?= $cap('abans') ?>
    <p class="text-sm text-gray-700 leading-relaxed">El GMAO organitza el manteniment de la instal·lació: <strong>quines tasques</strong> s'han de fer, <strong>cada quan</strong>, <strong>on</strong> i <strong>qui</strong> les fa. Cada vegada que algú marca una tasca, queda guardat a l'historial i l'aplicació calcula sola la propera data.</p>

    <h4 class="mt-5 mb-2 text-sm font-semibold text-gray-800">Què pot fer cada rol</h4>
    <div class="divide-y divide-gray-100 text-sm">
        <?php foreach ([
            [$rols(['tecnic']), 'Veu les tasques dels seus torns, les marca com a fetes o amb incidència, i registra els paràmetres de la piscina.'],
            [$rols(['cap']), 'Tot el del tècnic, per a tots els torns, i a més planifica el pla de manteniment, gestiona equips i espais i revisa les incidències.'],
            [$rols(['admin']), "L'administrador de la instal·lació: tot l'anterior i, a més, usuaris, torns, repositori de tasques, sistemes i importació d'Excel. El <strong>superadmin</strong> ho pot fer a totes les instal·lacions i és l'únic que en crea de noves."],
            ['<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-gray-100 text-gray-700">Lectura</span>', 'Només consulta (direcció, auditoria).'],
        ] as [$etiqueta, $text]): ?>
        <div class="flex flex-col gap-1 py-2.5 sm:flex-row sm:gap-4">
            <div class="shrink-0 sm:w-44"><?= $etiqueta ?></div>
            <p class="text-gray-700 leading-relaxed"><?= $text ?></p>
        </div>
        <?php endforeach; ?>
    </div>

    <h4 class="mt-6 mb-2 text-sm font-semibold text-gray-800">Si treballes a més d'una instal·lació</h4>
    <p class="text-sm text-gray-700 leading-relaxed">A dalt del menú lateral hi ha el selector <?= $ui('Instal·lació') ?>. Tria-hi la instal·lació i totes les pantalles passen a mostrar les dades d'aquella.</p>
    <?= $fig('00-selector.jpg', "Selector d'instal·lació a dalt del menú", '', true) ?>

    <h4 class="mt-6 mb-2 text-sm font-semibold text-gray-800">Paraules que surten a tot arreu</h4>
    <dl class="grid sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
        <div><dt class="font-semibold text-red-600">Vençuda</dt><dd class="text-gray-700">La data ja ha passat i la tasca no s'ha fet. Surt en vermell, i el número vermell del menú les compta.</dd></div>
        <div><dt class="font-semibold text-amber-600">Del dia</dt><dd class="text-gray-700">Toca fer-la avui (o el dia que estàs mirant). Surt en groc.</dd></div>
        <div><dt class="font-semibold text-gray-800">Pla de manteniment</dt><dd class="text-gray-700">Les tasques que estan programades en aquesta instal·lació, amb la seva data.</dd></div>
        <div><dt class="font-semibold text-gray-800">Repositori</dt><dd class="text-gray-700">El catàleg de totes les tasques, també les que ara no es fan. D'aquí es recuperen per tornar-les al pla.</dd></div>
        <div><dt class="font-semibold text-gray-800">Torn</dt><dd class="text-gray-700">Un horari de treball (Matí, Tarda…). Cada tècnic veu les tasques dels seus torns.</dd></div>
        <div><dt class="font-semibold text-gray-800">Bloc</dt><dd class="text-gray-700">L'ordre de la jornada a la Vista Diària: Obertura, Normatives, Preventiu i Tancament.</dd></div>
    </dl>
</section>

<!-- 01 ─────────────────────────────────────────── -->
<?= $cap('jornada') ?>
    <p class="text-sm text-gray-700 leading-relaxed">És el que veu el tècnic en entrar. Resumeix la feina dels seus torns.</p>
    <?= $on('Inicio') ?>
    <?= $fig('01-jornada.jpg', 'Pantalla La meva jornada del tècnic') ?>
    <ol class="space-y-3">
        <?= $pas(1, 'A dalt tens quatre xifres: <strong>Per fer avui</strong> (inclou les vençudes), <strong>Vençudes</strong>, <strong>Fetes avui</strong> i <strong>Fetes aquest mes</strong> (les que has registrat tu).') ?>
        <?= $pas(2, '<strong>La meva cua de treball</strong> llista primer les vençudes i després les d\'avui. Al costat (a sota, al mòbil) veus <strong>els teus torns</strong>.') ?>
        <?= $pas(3, 'Per començar a marcar tasques, prem ' . $ui('Obrir vista diària') . ' (apartat 02). Amb ' . $ui('Veure la setmana') . ' vas a la Vista Setmanal.') ?>
    </ol>
    <?= $nota('Si no veus cap tasca i surt «No tens cap torn assignat», demana a l\'administrador que t\'afegeixi al teu torn.', 'avis') ?>
</section>

<!-- 02 ─────────────────────────────────────────── -->
<?= $cap('dia') ?>
    <p class="text-sm text-gray-700 leading-relaxed">La pantalla de treball de cada dia: les tasques que toquen avui i les vençudes que han quedat pendents. Des d'aquí es marquen com a fetes.</p>
    <?= $on('Vista Diària') ?>
    <?= $fig('02-dia.jpg', 'Vista Diària amb les tasques per blocs') ?>
    <ol class="space-y-3">
        <?= $pas(1, 'Tria el dia amb les fletxes ' . $ui('←') . ' ' . $ui('→') . ' o amb el calendari i ' . $ui('Anar') . '. Amb el cercador trobes una tasca pel codi, el nom, l\'espai o l\'equip.') ?>
        <?= $pas(2, 'Amb ' . $ui('Torn') . ' pots veure només un torn. El tècnic només veu els seus.') ?>
        <?= $pas(3, 'Si la instal·lació fa servir blocs, les tasques surten en l\'ordre de la jornada: ' . $ui('1. Obertura') . ' ' . $ui('2. Normatives') . ' ' . $ui('3. Preventiu') . ' ' . $ui('4. Tancament') . '. ' . $ui('Totes') . ' les mostra totes juntes amb un títol per bloc; si en tries un, en marcar l\'última tasca passes sol al bloc següent.') ?>
        <?= $pas(4, 'Colors: en <span class="text-red-600 font-medium">vermell</span> les vençudes i en <span class="text-amber-600 font-medium">groc</span> les del dia.') ?>
    </ol>

    <h4 class="mt-6 mb-2 text-sm font-semibold text-gray-800">Marcar una tasca com a feta</h4>
    <ol class="space-y-3">
        <?= $pas(1, 'A la fila de la tasca, prem ' . $ui('Acció') . ' i després ' . $ui('Fet') . '. Al mòbil, prem el botó rodó ✓ de la targeta.') ?>
    </ol>
    <?= $fig('02-dia-accio.jpg', 'Menú Acció amb Fet, Fet amb incidència i No fet per incidència') ?>
    <ol class="space-y-3" start="2">
        <?= $pas(2, 'Surt un missatge de confirmació i la tasca desapareix de la llista: ja està registrada i té la propera data calculada. Si t\'has equivocat, prem ' . $ui('Desfer') . ' al mateix missatge.') ?>
    </ol>
    <?= $fig('02-dia-fet.jpg', 'Missatge després de marcar una tasca com a feta') ?>

    <h4 class="mt-6 mb-2 text-sm font-semibold text-gray-800">Si hi ha hagut algun problema</h4>
    <ol class="space-y-3">
        <?= $pas(1, 'Al menú ' . $ui('Acció') . ' (al mòbil, obre ' . $ui('+ Detalls') . ' a la targeta) tria:<ul class="mt-1.5 ml-4 list-disc space-y-1"><li>' . $ui('Fet amb incidència') . ' si l\'has pogut fer però hi ha alguna cosa a revisar. La tasca queda feta.</li><li>' . $ui('No fet per incidència') . ' si no s\'ha pogut fer. La tasca <strong>continua pendent</strong>.</li></ul>') ?>
        <?= $pas(2, 'Escriu què ha passat (és obligatori) i prem ' . $ui('Registrar incidència') . '. El cap de manteniment la veurà a <strong>Incidències</strong> (apartat 07).') ?>
    </ol>
    <?= $fig('02-dia-incidencia.jpg', "Formulari d'incidència amb el comentari escrit") ?>
</section>

<!-- 03 ─────────────────────────────────────────── -->
<?= $cap('parametres') ?>
    <p class="text-sm text-gray-700 leading-relaxed">Les tasques de mesura de l'aigua (clor, pH, CO₂, temperatures…) no es marquen amb «Fet»: s'omple un formulari. Cada vas té la seva tasca.</p>
    <?= $on('Vista Diària') . ' ' ?>
    <?= $nota('Aquest apartat només surt si la instal·lació té alguna tasca marcada com a «Mesura de paràmetres de l\'aigua» (es marca a la fitxa de la tasca, apartat 08).') ?>
    <ol class="space-y-3">
        <?= $pas(1, 'A la Vista Diària, la tasca del vas té el botó ' . $ui('Paràmetres 0/3') . ' en lloc d\'«Acció» (o del ✓ al mòbil). El número diu quantes de les tres mesures del dia s\'han fet.') ?>
    </ol>
    <?= $fig('03-param-boto.jpg', 'Botó Paràmetres a la fila de la tasca') ?>
    <ol class="space-y-3" start="2">
        <?= $pas(2, 'Comprova el <strong>moment de la mesura</strong>: ' . $ui('Inici jornada') . ', ' . $ui('Mant. 14 h') . ' o ' . $ui('Final jornada') . '. L\'aplicació proposa el que toca segons l\'hora, però el pots canviar.') ?>
        <?= $pas(3, 'Omple els valors. El clor i el pH es mesuren a cada moment; la resta (CO₂, temperatures, humitat, terbolesa i comptadors), un cop al dia. Pots escriure amb coma decimal i amb punts de milers als comptadors.') ?>
        <?= $pas(4, 'Si un valor surt en <span class="text-amber-600 font-medium">groc</span>, és fora de norma; en <span class="text-red-600 font-medium">vermell</span>, cal tancar el vas. Explica-ho a <strong>Observacions</strong>.') ?>
        <?= $pas(5, 'Prem ' . $ui('Desar mesura') . '. Si no has pogut mesurar, prem <span class="text-red-600">No s\'ha pogut mesurar</span> i explica el motiu: queda com a incidència.') ?>
    </ol>
    <?= $fig('03-param-formulari.jpg', 'Formulari de paràmetres de l\'aigua omplert') ?>
    <ol class="space-y-3" start="6">
        <?= $pas(6, 'Amb la primera mesura del dia la tasca ja queda feta, però continua a la llista fins a final del dia perquè hi afegeixis les altres mesures (o en corregeixis una).') ?>
    </ol>
    <?= $fig('03-param-desat.jpg', 'Missatge de mesura desada i tasca feta avui') ?>

    <h4 class="mt-6 mb-2 text-sm font-semibold text-gray-800">Consultar l'historial i descarregar l'Excel</h4>
    <?= $on('Paràmetres piscina') ?>
    <ol class="mt-3 space-y-3">
        <?= $pas(1, 'Una fila per dia i vas, com el full de paper: el clor i el pH a les columnes 1, 2 i 3 (inici, 14 h i final).') ?>
        <?= $pas(2, 'Filtra per ' . $ui('Vas') . ' i per dates, i prem ' . $ui('Aplicar filtres') . '.') ?>
        <?= $pas(3, $ui('Descarregar Excel') . ' baixa el que estàs veient, amb un full per vas i els colors de fora de norma, a punt per a una inspecció.') ?>
    </ol>
    <?= $fig('03-param-historial.jpg', 'Historial de paràmetres de piscina') ?>
</section>

<!-- 04 ─────────────────────────────────────────── -->
<?= $cap('setmana') ?>
    <p class="text-sm text-gray-700 leading-relaxed">Les tasques de tota la setmana, per avançar feina o veure què vindrà.</p>
    <?= $on('Vista Setmanal') ?>
    <?= $fig('04-setmana.jpg', 'Vista Setmanal') ?>
    <ol class="space-y-3">
        <?= $pas(1, 'Canvia de setmana amb les fletxes i filtra per ' . $ui('Torn') . ' si cal.') ?>
        <?= $pas(2, 'El botó ' . $ui('Fet') . ' (✓ al mòbil) marca la tasca directament. Les de paràmetres de piscina porten el botó ' . $ui('Paràmetres') . ', que t\'envia a la Vista Diària.') ?>
    </ol>
</section>

<!-- 05 ─────────────────────────────────────────── -->
<?= $cap('registre') ?>
    <p class="text-sm text-gray-700 leading-relaxed">L'historial de tot el que s'ha fet. El tècnic hi veu <strong>el seu registre</strong>; el cap i l'administrador veuen el de tothom, amb la columna del tècnic.</p>
    <?= $on('Registre') ?>
    <?= $fig('05-registre.jpg', 'Registre de tasques') ?>
    <ol class="space-y-3">
        <?= $pas(1, 'Cerca per text o tria un interval de dates, i prem ' . $ui('Aplicar filtres') . '.') ?>
        <?= $pas(2, 'També pots filtrar per ' . $ui('Tasca') . ', ' . $ui('Espai') . ' i ' . $ui('Torn') . '. La columna <strong>Estat</strong> diu si es va fer o no, i <strong>Comentaris</strong> recull el que es va escriure.') ?>
    </ol>
</section>

<!-- 06 ─────────────────────────────────────────── -->
<?= $cap('resum') ?>
    <p class="text-sm text-gray-700 leading-relaxed">La pantalla d'inici del cap de manteniment i de l'administrador: com va el manteniment de la instal·lació.</p>
    <?= $on('Inicio') ?>
    <?= $fig('06-resum.jpg', 'Resum general de manteniment') ?>
    <ol class="space-y-3">
        <?= $pas(1, 'Les xifres de dalt: tasques al pla, pendents d\'avui, vençudes, equips actius, execucions del mes i <strong>% d\'acompliment</strong>.') ?>
        <?= $pas(2, '<strong>Properes tasques</strong> llista les que vénen; a la dreta tens el repartiment <strong>per torn</strong> (amb les vençudes) i <strong>per sistema</strong>.') ?>
    </ol>
</section>

<!-- 07 ─────────────────────────────────────────── -->
<?= $cap('incidencies') ?>
    <p class="text-sm text-gray-700 leading-relaxed">Aquí arriben les incidències que registren els tècnics. El número vermell del menú diu quantes n'hi ha per revisar.</p>
    <?= $on('Incidències') ?>
    <?= $fig('07-incidencies.jpg', "Llista d'incidències obertes") ?>
    <ol class="space-y-3">
        <?= $pas(1, 'Cada targeta diu la tasca, el tipus (' . $ui('Fet amb incidència') . ' o ' . $ui('No fet per incidència') . '), la data, l\'espai, el torn, qui l\'ha reportat i el seu comentari.') ?>
        <?= $pas(2, 'Quan l\'hagis revisada, prem ' . $ui('Marcar com vista') . ': surt de la llista però continua al Registre.') ?>
    </ol>
    <?= $nota('Recorda que una tasca «No fet per incidència» continua pendent a la Vista Diària fins que algú la faci.', 'avis') ?>
</section>

<!-- 08 ─────────────────────────────────────────── -->
<?= $cap('pla') ?>
    <p class="text-sm text-gray-700 leading-relaxed">Totes les tasques programades a la instal·lació, amb la darrera i la propera data.</p>
    <?= $on('Pla de Manteniment') ?>
    <?= $fig('08-pla.jpg', 'Pla de manteniment') ?>
    <ol class="space-y-3">
        <?= $pas(1, 'Cerca i ' . $ui('Filtrar') . ' per trobar una tasca. Amb la icona del llapis l\'edites; l\'administrador també la pot desactivar (es pot recuperar des del Repositori).') ?>
    </ol>

    <h4 class="mt-6 mb-2 text-sm font-semibold text-gray-800">Afegir una tasca</h4>
    <ol class="space-y-3">
        <?= $pas(1, 'Prem ' . $ui('Nova tasca') . '. A <strong>Tasca existent del repositori</strong> pots recuperar-ne una que ja existeixi, o deixar-ho a «Crear una tasca nova».') ?>
        <?= $pas(2, 'Omple el <strong>nom</strong> (obligatori) i, si cal, sistema, normativa i <strong>Tipus de tasca</strong> (el bloc on sortirà a la Vista Diària). Marca <strong>Mesura de paràmetres de l\'aigua</strong> si és una mesura de piscina: llavors sortirà el formulari de l\'apartat 03.') ?>
        <?= $pas(3, 'Amb <strong>Afegir al pla de manteniment</strong> marcat, tria l\'equip, l\'espai, els torns, la periodicitat i la data propera. <strong>Tasca fixa</strong> fa que es continuï fent encara que l\'espai estigui desactivat (p. ex. la piscina exterior a l\'hivern).') ?>
    </ol>
    <?= $fig('08-pla-nova.jpg', 'Formulari de nova tasca') ?>

    <h4 class="mt-6 mb-2 text-sm font-semibold text-gray-800">Afegir-ne moltes de cop</h4>
    <ol class="space-y-3">
        <?= $pas(1, 'Obre l\'<a href="' . e(url('pla/alta-rapida')) . '" class="text-brand hover:text-brand-dark underline">Alta ràpida de tasques</a> (també hi arribes des de <strong>Configuració</strong>, apartat 15, amb «Afegir tasques a mà»). Escriu una fila per tasca: què s\'ha de fer i cada quan. La resta de columnes són opcionals i les files buides s\'ignoren.') ?>
        <?= $pas(2, '<strong>Primera execució</strong> és el dia que la tasca sortirà per primer cop a les vistes de dia i setmana.') ?>
    </ol>
    <?= $fig('08-pla-alta-rapida.jpg', 'Alta ràpida de tasques') ?>

    <h4 class="mt-6 mb-2 text-sm font-semibold text-gray-800">Canviar la data de diverses tasques</h4>
    <ol class="space-y-3">
        <?= $pas(1, 'Prem ' . $ui('Programar dates') . ' i marca les tasques. Consell: filtra primer (per periodicitat o per espai) i després marca «Totes les filtrades».') ?>
        <?= $pas(2, 'A baix, tria la <strong>Propera realització</strong> i prem ' . $ui('Aplicar data') . '.') ?>
    </ol>
    <?= $fig('08-pla-programar.jpg', 'Programar dates de diverses tasques') ?>
</section>

<!-- 09 ─────────────────────────────────────────── -->
<?= $cap('equips') ?>
    <p class="text-sm text-gray-700 leading-relaxed">L'inventari de màquines i instal·lacions tècniques: bombes, calderes, filtres, extintors… Els tècnics el poden consultar.</p>
    <?= $on('Equips') ?>
    <?= $fig('09-equips.jpg', "Llista d'equips") ?>
    <ol class="space-y-3">
        <?= $pas(1, 'Filtra per sistema o cerca un equip. Prem ' . $ui('Nou Equip') . ' per donar-ne un d\'alta.') ?>
        <?= $pas(2, 'Són obligatoris el <strong>Codi MN</strong> i el <strong>Nom equip</strong>. Si pots, omple el sistema, el tipus, el model, l\'empresa mantenedora, l\'espai, l\'estat i les dates d\'instal·lació i de fi de garantia.') ?>
    </ol>
    <?= $fig('09-equips-nou.jpg', "Formulari de nou equip") ?>
</section>

<!-- 10 ─────────────────────────────────────────── -->
<?= $cap('espais') ?>
    <p class="text-sm text-gray-700 leading-relaxed">Les zones de la instal·lació on es fan tasques: vasos, sales, vestidors…</p>
    <?= $on('Espais') ?>
    <?= $fig('10-espais.jpg', "Llista d'espais") ?>
    <ol class="space-y-3">
        <?= $pas(1, 'Prem ' . $ui('Nou Espai') . ' i omple el nom (obligatori) i, si vols, el codi, la planta i la zona.') ?>
        <?= $pas(2, 'Si desmarques <strong>Espai actiu</strong> (p. ex. la piscina exterior a l\'hivern), les seves tasques deixen de sortir com a pendents, menys les marcades com a <strong>Tasca fixa</strong>.') ?>
    </ol>
    <?= $fig('10-espais-nou.jpg', "Formulari de nou espai") ?>
</section>

<!-- 11 ─────────────────────────────────────────── -->
<?= $cap('repositori') ?>
    <p class="text-sm text-gray-700 leading-relaxed">El catàleg de totes les tasques, tant les que estan al pla com les que no. La columna <strong>Estat</strong> diu si la tasca està activa al pla.</p>
    <?= $on('Repositori Tasques') ?>
    <?= $fig('11-repositori.jpg', 'Repositori de tasques') ?>
    <ol class="space-y-3">
        <?= $pas(1, 'Des de cada tasca pots ' . $ui('Afegir al pla') . ' o ' . $ui('Reactivar') . ' (tornar-la a programar), ' . $ui('Editar') . ' i ' . $ui('Arxivar') . '. Les arxivades es poden ' . $ui('Recuperar') . '.') ?>
        <?= $pas(2, $ui('Nova tasca') . ' obre la mateixa fitxa que al Pla de manteniment (apartat 08).') ?>
    </ol>
</section>

<!-- 12 ─────────────────────────────────────────── -->
<?= $cap('sistemes') ?>
    <p class="text-sm text-gray-700 leading-relaxed">Les sigles que agrupen equips i tasques (ACS, HP, CL…). És un catàleg comú a totes les instal·lacions i diu quants equips i tasques té cadascun.</p>
    <?= $on('Sistemes') ?>
    <?= $fig('12-sistemes.jpg', 'Catàleg de sistemes') ?>
    <ol class="space-y-3">
        <?= $pas(1, 'Prem ' . $ui('Nou Sistema') . ' i omple la <strong>sigla</strong> i el <strong>nom</strong>.') ?>
    </ol>
</section>

<!-- 13 ─────────────────────────────────────────── -->
<?= $cap('usuaris') ?>
    <p class="text-sm text-gray-700 leading-relaxed">Les persones que fan servir el GMAO, amb el seu rol i els seus torns a cada instal·lació.</p>
    <?= $on('Usuaris') ?>
    <?= $fig('13-usuaris.jpg', "Llista d'usuaris") ?>
    <ol class="space-y-3">
        <?= $pas(1, 'Filtra per instal·lació, rol o estat. Si marques diverses persones, pots activar-les, desactivar-les, assignar-les a una instal·lació, generar-ne els enllaços d\'accés o exportar-les a Excel de cop.') ?>
    </ol>

    <h4 class="mt-6 mb-2 text-sm font-semibold text-gray-800">Donar d'alta una persona</h4>
    <ol class="space-y-3">
        <?= $pas(1, 'Prem ' . $ui('Nou Usuari') . ' i omple el nom i l\'email.') ?>
        <?= $pas(2, 'Deixa la <strong>contrasenya</strong> en blanc: en desar sortirà un <strong>enllaç d\'un sol ús</strong> perquè la persona triï la seva. Copia\'l i envia-l\'hi; caduca al cap de 7 dies i no es pot tornar a consultar (però en pots generar un de nou amb la icona d\'enllaç).') ?>
        <?= $pas(3, 'A <strong>Instal·lacions i rols</strong> tria un rol per a cada instal·lació on treballi i marca els seus torns. On no li posis rol, no hi tindrà accés.') ?>
    </ol>
    <?= $fig('13-usuaris-nou.jpg', "Formulari de nou usuari amb rol i torns") ?>

    <h4 class="mt-6 mb-2 text-sm font-semibold text-gray-800">Importar-ne molts de cop</h4>
    <ol class="space-y-3">
        <?= $pas(1, 'Prem ' . $ui('Importar usuaris') . '. Enganxa una llista copiada d\'un Excel (nom, cognoms, correu, rol, torns…) o puja un fitxer Excel o CSV, i prem ' . $ui('Analitzar') . '.') ?>
        <?= $pas(2, 'Revisa i ajusta el que calgui amb clics, i crea els comptes. Cada persona nova rep el seu enllaç per triar la contrasenya.') ?>
    </ol>
    <?= $fig('13-usuaris-importar.jpg', "Importar usuaris") ?>
</section>

<!-- 14 ─────────────────────────────────────────── -->
<?= $cap('torns') ?>
    <p class="text-sm text-gray-700 leading-relaxed">Els horaris de treball de la instal·lació. Les tasques i els tècnics s'assignen a torns, i cada tècnic veu només les tasques dels seus.</p>
    <?= $on('Torns') ?>
    <?= $fig('14-torns.jpg', 'Llista de torns') ?>
    <ol class="space-y-3">
        <?= $pas(1, 'Prem ' . $ui('Nou Torn') . ': posa-hi el nom, l\'horari si vols, i marca els <strong>dies de la setmana</strong> que treballa.') ?>
        <?= $pas(2, 'A <strong>Usuaris assignats</strong> marca qui hi treballa. També es pot fer des de la fitxa de cada usuari.') ?>
    </ol>
    <?= $fig('14-torns-nou.jpg', 'Formulari de nou torn') ?>
</section>

<!-- 15 ─────────────────────────────────────────── -->
<?= $cap('configuracio') ?>
    <p class="text-sm text-gray-700 leading-relaxed">Una pantalla que guia la posada en marxa d'una instal·lació: espais, torns, equips i pla de tasques. Diu quins blocs ja tenen dades.</p>
    <?= $on('Configuració') ?>
    <?= $fig('15-primers-passos.jpg', 'Pantalla de posada en marxa') ?>
    <ol class="space-y-3">
        <?= $pas(1, 'El camí més fàcil: ' . $ui('1. Descarregar plantilla') . ', omplir-la amb calma i ' . $ui('3. Pujar plantilla omplerta') . '. Abans de desar res veuràs una previsualització.') ?>
        <?= $pas(2, 'També pots anar bloc a bloc: cada bloc diu què has de recollir, porta un exemple i un botó per afegir-ho a mà.') ?>
    </ol>
</section>

<!-- 16 ─────────────────────────────────────────── -->
<?= $cap('importar') ?>
    <p class="text-sm text-gray-700 leading-relaxed">Carrega dades d'un Excel a la instal·lació activa: la plantilla de configuració, un Excel complet del pla o una simple llista de tasques.</p>
    <?= $on('Importar Excel') ?>
    <?= $fig('16-importar.jpg', 'Importar Excel') ?>
    <ol class="space-y-3">
        <?= $pas(1, 'Comprova que el selector d\'instal·lació és a la instal·lació correcta: la importació s\'hi aplica.') ?>
        <?= $pas(2, 'Tria el fitxer i prem ' . $ui('Analitzar fitxer') . '. No cal dir quin format és: es detecta sol.') ?>
        <?= $pas(3, 'Revisa la vista prèvia: quants espais, equips i tasques s\'importaran, i les files que demanen decisió. Els errors bloquegen la importació; els avisos es poden acceptar.') ?>
        <?= $pas(4, 'Confirma només quan tot quadri. Fins llavors no es desa res.') ?>
    </ol>
    <?= $nota('Per a una llista senzilla només cal una primera fila amb <strong>tasca | periodicitat</strong>; la resta de columnes són opcionals.') ?>
</section>

<!-- 17 ─────────────────────────────────────────── -->
<?= $cap('instalacions') ?>
    <p class="text-sm text-gray-700 leading-relaxed">Els centres que gestiona el GMAO. Només el superadmin hi entra.</p>
    <?= $on('Instal·lacions') ?>
    <?= $fig('17-instalacions.jpg', "Llista d'instal·lacions") ?>
    <ol class="space-y-3">
        <?= $pas(1, $ui('Nova Instal·lació') . ' en crea una (nom, adreça, telèfon, email). Després, ' . $ui('Configurar') . ' obre la posada en marxa (apartat 15).') ?>
        <?= $pas(2, $ui('Netejar dades') . ' esborra les dades operatives per poder tornar a importar, però conserva la instal·lació i els usuaris. ' . $ui('Eliminar') . ' l\'esborra del tot.') ?>
    </ol>
    <?= $nota('«Netejar dades» i «Eliminar» no es poden desfer. Fes-los servir només si n\'estàs segur.', 'avis') ?>
</section>

            <p class="pb-6 text-center text-xs text-gray-400">Les captures són d'una instal·lació de prova: els noms i les dades no són reals.</p>
        </div>
    </div>

    <!-- Tornar a l'índex -->
    <a href="#index-guia" x-show="avall" x-cloak x-transition.opacity
       class="fixed bottom-5 right-5 z-40 inline-flex items-center gap-1.5 rounded-full bg-brand px-4 py-2.5 text-sm font-medium text-white shadow-lg hover:bg-brand-dark transition">
        <span aria-hidden="true">↑</span> Índex
    </a>

    <!-- Captura ampliada -->
    <div x-show="zoom" x-cloak x-transition.opacity @click="zoom = null"
         class="fixed inset-0 z-50 overflow-auto bg-gray-900/85 cursor-zoom-out" role="dialog" aria-modal="true" aria-label="Captura ampliada">
        <button type="button" @click.stop="zoom = null"
                class="fixed top-3 right-3 z-10 inline-flex items-center gap-1.5 rounded-full bg-white px-3.5 py-2 text-sm font-medium text-gray-800 shadow-lg">
            Tancar <span aria-hidden="true">✕</span>
        </button>
        <div class="flex min-h-full items-center justify-center p-4 pt-16 md:pt-4" :class="zoom && zoom.ample ? 'w-max' : ''">
            <img :src="zoom ? zoom.src : ''" :alt="zoom ? zoom.alt : ''"
                 :style="zoom && zoom.ample ? 'width:' + zoom.ample + 'px;max-width:none' : ''"
                 :class="zoom && zoom.ample ? '' : 'max-h-[90vh] max-w-full'"
                 class="rounded-lg bg-white shadow-2xl">
        </div>
        <p x-show="zoom && zoom.ample" class="fixed bottom-3 inset-x-0 text-center text-xs text-white/80 pointer-events-none">Llisca per moure't per la captura</p>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
