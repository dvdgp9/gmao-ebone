<?php
use App\Models\MesuraPiscina;

$title = 'Paràmetres piscina';
$parametres = MesuraPiscina::PARAMETRES;
$moments = MesuraPiscina::MOMENTS;
$campsMoment = MesuraPiscina::campsCadaMoment();
$campsDiaris = MesuraPiscina::campsDiaris();
$classeEstat = [
    MesuraPiscina::TANCAR => 'bg-red-100 text-red-700 font-semibold',
    MesuraPiscina::FORA => 'bg-yellow-100 text-yellow-800 font-medium',
];
$format = static function ($valor, string $camp): string {
    if ($valor === null || $valor === '') {
        return '–';
    }
    $decimals = in_array($camp, ['co2_interior', 'co2_exterior', 'humitat', 'recirculacio', 'aigua_nova'], true) ? 0 : 2;
    $text = number_format((float)$valor, $decimals, ',', '.');
    return $decimals ? rtrim(rtrim($text, '0'), ',') : $text;
};

// Com el full de paràmetres: una fila per dia i vas, amb les mesures de cada moment.
$files = [];
foreach ($mesures as $m) {
    $clau = $m['data'] . '|' . $m['tasca_pla_id'];
    if (!isset($files[$clau])) {
        $files[$clau] = [
            'data' => $m['data'],
            'vas' => $m['espai_nom'] ?: $m['tasca_nom'],
            'moments' => [],
            'diaris' => array_fill_keys($campsDiaris, null),
            'observacions' => [],
            'tecnics' => [],
        ];
    }
    $f = &$files[$clau];
    $f['moments'][$m['moment']] = $m;
    foreach ($campsDiaris as $camp) {
        if ($m[$camp] !== null && $f['diaris'][$camp] === null) {
            $f['diaris'][$camp] = $m[$camp];
        }
    }
    if (!empty($m['observacions'])) {
        $f['observacions'][] = $moments[$m['moment']] . ': ' . $m['observacions'];
    }
    $tecnic = trim(($m['usuari_nom'] ?? '') . ' ' . ($m['usuari_cognoms'] ?? ''));
    if ($tecnic !== '' && !in_array($tecnic, $f['tecnics'], true)) {
        $f['tecnics'][] = $tecnic;
    }
    unset($f);
}
$perDia = [];
foreach ($files as $f) {
    $f['estatsDiaris'] = MesuraPiscina::avaluar($f['diaris']);
    $perDia[$f['data']][] = $f;
}
$estatMoment = static fn(array $f, string $moment, string $camp): string
    => isset($f['moments'][$moment]) ? ($classeEstat[MesuraPiscina::avaluar($f['moments'][$moment])[$camp] ?? ''] ?? '') : '';
ob_start();
?>

<div class="mb-4">
    <h2 class="text-xl sm:text-2xl font-bold text-gray-800">Paràmetres piscina</h2>
    <p class="text-gray-500 text-sm mt-1">Una fila per dia i vas, com el full de paràmetres: clor i pH a l'inici de la jornada (1), a les 14 h (2) i al final (3); la resta, un cop al dia. En groc, fora de norma; en vermell, cal tancar el vas.</p>
</div>

<form method="GET" action="<?= url('parametres') ?>" class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-4 grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
    <label class="block">
        <span class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Vas</span>
        <select name="vas" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand outline-none">
            <option value="">Tots els vasos</option>
            <?php foreach ($vasos as $v): ?>
                <option value="<?= (int)$v['id'] ?>" <?= (int)$vasActual === (int)$v['id'] ? 'selected' : '' ?>><?= e(($v['espai_nom'] ?: $v['tasca_nom']) . ($v['tasca_codi'] ? ' · ' . $v['tasca_codi'] : '')) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label class="block">
        <span class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Des de</span>
        <input type="date" name="desde" value="<?= e($desde) ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand outline-none">
    </label>
    <label class="block">
        <span class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Fins a</span>
        <input type="date" name="fins" value="<?= e($fins) ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand outline-none">
    </label>
    <button type="submit" class="bg-brand text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-brand-dark transition">Aplicar filtres</button>
</form>

<?php if (empty($mesures)): ?>
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 px-4 py-10 text-center text-gray-400">
        No hi ha mesures en aquestes dates. Es registren des de la Vista Diària, amb el botó «Paràmetres».
    </div>
<?php else: ?>

<!-- Mòbil: una targeta per dia i vas -->
<div class="md:hidden space-y-4">
    <?php foreach ($perDia as $dia => $filesDia): ?>
    <div>
        <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2"><?= e(format_date($dia)) ?></h3>
        <div class="space-y-2">
        <?php foreach ($filesDia as $f): ?>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 px-3 py-2.5">
                <p class="text-sm font-medium text-gray-800 break-words"><?= e($f['vas']) ?></p>
                <table class="w-full mt-2 text-xs">
                    <thead>
                        <tr class="text-[10px] text-gray-400">
                            <th class="text-left font-normal py-0.5"></th>
                            <?php foreach ($campsMoment as $camp): ?><th class="text-right font-normal py-0.5"><?= e($parametres[$camp]['nom']) ?></th><?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($moments as $moment => $nomMoment): ?>
                        <tr>
                            <td class="py-0.5 text-[11px] text-gray-500 whitespace-nowrap"><?= e($nomMoment) ?></td>
                            <?php foreach ($campsMoment as $camp): ?>
                            <td class="py-0.5 text-right tabular-nums"><span class="rounded px-1 <?= $estatMoment($f, $moment, $camp) ?>"><?= e($format($f['moments'][$moment][$camp] ?? null, $camp)) ?></span></td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if (array_filter($f['diaris'], static fn($v) => $v !== null)): ?>
                <dl class="grid grid-cols-3 gap-x-3 gap-y-1.5 mt-2 pt-2 border-t border-gray-100 text-xs">
                    <?php foreach ($campsDiaris as $camp): if ($f['diaris'][$camp] === null) continue; ?>
                    <div class="rounded px-1 <?= $classeEstat[$f['estatsDiaris'][$camp] ?? ''] ?? '' ?>">
                        <dt class="text-[10px] text-gray-400"><?= e($parametres[$camp]['nom']) ?></dt>
                        <dd><?= e($format($f['diaris'][$camp], $camp)) ?></dd>
                    </div>
                    <?php endforeach; ?>
                </dl>
                <?php endif; ?>
                <?php foreach ($f['observacions'] as $obs): ?><p class="mt-2 text-xs text-gray-600 break-words"><?= e($obs) ?></p><?php endforeach; ?>
                <?php if (!$isTecnic && $f['tecnics']): ?><p class="mt-1 text-[11px] text-gray-400"><?= e(implode(', ', $f['tecnics'])) ?></p><?php endif; ?>
            </div>
        <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Escriptori: taula com el full de paràmetres -->
<div class="hidden md:block bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-xs">
            <thead>
                <tr class="bg-gray-50 text-gray-600">
                    <th rowspan="2" class="text-left px-3 py-2 font-medium align-bottom border-b border-gray-200">Vas</th>
                    <?php foreach ($campsMoment as $camp): $p = $parametres[$camp]; ?>
                    <th colspan="3" class="text-center px-2 pt-2 font-medium whitespace-nowrap border-l border-gray-200">
                        <?= e($p['nom']) ?>
                        <?php if ($p['norma']): ?><div class="text-[10px] font-normal text-gray-400"><?= e($p['norma']) ?></div><?php endif; ?>
                    </th>
                    <?php endforeach; ?>
                    <?php foreach ($campsDiaris as $i => $camp): $p = $parametres[$camp]; ?>
                    <th rowspan="2" class="text-right px-2 py-2 font-medium align-bottom border-b border-gray-200 <?= $i === 0 ? 'border-l' : '' ?>" title="Un cop al dia, abans d'obrir al públic">
                        <?= e($p['nom']) ?>
                        <?php if ($p['norma']): ?><div class="text-[10px] font-normal text-gray-400 whitespace-nowrap"><?= e($p['norma']) ?></div><?php endif; ?>
                    </th>
                    <?php endforeach; ?>
                    <?php if (!$isTecnic): ?><th rowspan="2" class="text-left px-3 py-2 font-medium align-bottom border-b border-gray-200">Tècnic</th><?php endif; ?>
                    <th rowspan="2" class="text-left px-3 py-2 font-medium align-bottom border-b border-gray-200">Observacions</th>
                </tr>
                <tr class="bg-gray-50 text-[10px] text-gray-400 border-b border-gray-200">
                    <?php foreach ($campsMoment as $camp): ?>
                        <?php $n = 0; foreach ($moments as $nomMoment): $n++; ?>
                        <th class="text-right px-2 pb-1.5 font-normal <?= $n === 1 ? 'border-l border-gray-200' : '' ?>" title="<?= e($nomMoment) ?>"><?= $n ?></th>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php $columnes = 1 + count($campsMoment) * count($moments) + count($campsDiaris) + ($isTecnic ? 1 : 2); ?>
                <?php foreach ($perDia as $dia => $filesDia): ?>
                <tr class="bg-gray-50">
                    <th colspan="<?= $columnes ?>" scope="colgroup" class="text-left px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-gray-500"><?= e(format_date($dia)) ?></th>
                </tr>
                <?php foreach ($filesDia as $f): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-3 py-2 text-gray-800 whitespace-nowrap"><?= e($f['vas']) ?></td>
                    <?php foreach ($campsMoment as $camp): ?>
                        <?php $n = 0; foreach ($moments as $moment => $nomMoment): $n++; ?>
                        <td class="px-2 py-2 text-right tabular-nums whitespace-nowrap <?= $n === 1 ? 'border-l border-gray-100' : '' ?>"><span class="rounded px-1 <?= $estatMoment($f, $moment, $camp) ?>"><?= e($format($f['moments'][$moment][$camp] ?? null, $camp)) ?></span></td>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                    <?php foreach ($campsDiaris as $i => $camp): ?>
                    <td class="px-2 py-2 text-right tabular-nums whitespace-nowrap <?= $i === 0 ? 'border-l border-gray-100' : '' ?>"><span class="rounded px-1 <?= $classeEstat[$f['estatsDiaris'][$camp] ?? ''] ?? '' ?>"><?= e($format($f['diaris'][$camp], $camp)) ?></span></td>
                    <?php endforeach; ?>
                    <?php if (!$isTecnic): ?><td class="px-3 py-2 text-gray-500 whitespace-nowrap"><?= e(implode(', ', $f['tecnics']) ?: '–') ?></td><?php endif; ?>
                    <td class="px-3 py-2 text-gray-600 min-w-48"><?= nl2br(e(implode("\n", $f['observacions']))) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="px-4 py-3 border-t border-gray-200 text-sm text-gray-500">1 = inici de jornada · 2 = manteniment 14 h · 3 = final de jornada · <?= count($files) ?> files</div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
