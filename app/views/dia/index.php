<?php
$title = 'Vista Diària';
$dataActual = date('Y-m-d');
$dataIso = $dataSeleccionada->format('Y-m-d');
$dataAnterior = (clone $dataSeleccionada)->modify('-1 day')->format('Y-m-d');
$dataSeguent = (clone $dataSeleccionada)->modify('+1 day')->format('Y-m-d');
$queryBase = $tornActual ? '&torn=' . $tornActual : '';
$nVencudes = count(array_filter($tasques, fn($t) => ($t['data_propera_realitzacio'] ?? null) < $dataIso));
$nDia = count($tasques) - $nVencudes;
ob_start();
?>

<div class="mb-6">
    <h2 class="text-xl sm:text-2xl font-bold text-gray-800">Vista Diària</h2>
    <p class="text-gray-500 text-sm mt-1">Tasques del dia seleccionat i vencides pendents</p>
</div>

<div class="flex flex-col xl:flex-row items-start xl:items-center justify-between gap-4 mb-6">
    <div class="flex flex-wrap items-center gap-2">
        <a href="<?= url('dia?data=' . $dataAnterior . $queryBase) ?>"
           class="bg-white border border-gray-300 rounded-lg px-3 py-2 text-sm hover:bg-gray-50 transition">
            &larr; Anterior
        </a>
        <div class="bg-white border border-gray-200 rounded-lg px-4 py-2 text-center">
            <div class="text-sm font-semibold text-gray-800"><?= e($dataSeleccionada->format('d/m/Y')) ?></div>
            <div class="text-xs text-gray-500"><?= e($dataSeleccionada->format('l')) ?></div>
        </div>
        <a href="<?= url('dia?data=' . $dataSeguent . $queryBase) ?>"
           class="bg-white border border-gray-300 rounded-lg px-3 py-2 text-sm hover:bg-gray-50 transition">
            Següent &rarr;
        </a>
        <?php if ($dataIso !== $dataActual): ?>
        <a href="<?= url('dia' . ($tornActual ? '?torn=' . $tornActual : '')) ?>"
           class="text-sm text-brand hover:text-brand-dark transition ml-2">Avui</a>
        <?php endif; ?>
    </div>

    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 w-full xl:w-auto">
        <form method="GET" action="<?= url('dia') ?>" class="flex items-center gap-2">
            <?php if ($tornActual): ?>
                <input type="hidden" name="torn" value="<?= e((string)$tornActual) ?>">
            <?php endif; ?>
            <input type="date" name="data" value="<?= e($dataIso) ?>"
                   class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none">
            <button type="submit" class="bg-gray-100 text-gray-600 px-4 py-2 rounded-lg text-sm hover:bg-gray-200 transition">
                Anar
            </button>
        </form>

        <div class="flex flex-wrap items-center gap-2">
            <span class="text-sm text-gray-500">Torn:</span>
            <a href="<?= url('dia?data=' . $dataIso) ?>"
               class="px-3 py-1.5 text-sm rounded-lg <?= !$tornActual ? 'bg-brand text-white' : 'bg-white border border-gray-300 text-gray-600 hover:bg-gray-50' ?> transition">
                Tots
            </a>
            <?php foreach ($torns as $t): ?>
            <a href="<?= url('dia?data=' . $dataIso . '&torn=' . $t['id']) ?>"
               class="px-3 py-1.5 text-sm rounded-lg <?= $tornActual == $t['id'] ? 'bg-brand text-white' : 'bg-white border border-gray-300 text-gray-600 hover:bg-gray-50' ?> transition">
                <?= e($t['nom']) ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="space-y-4 md:hidden">
    <?php if (empty($tasques)): ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 px-4 py-8 text-center text-gray-400">No hi ha tasques pendents per a aquest dia.</div>
    <?php else: ?>
        <?php foreach ($tasques as $t): ?>
        <?php
            $vencuda = ($t['data_propera_realitzacio'] ?? null) < $dataIso;
            $esDia = ($t['data_propera_realitzacio'] ?? null) === $dataIso;
        ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 <?= $vencuda ? 'border-red-200 bg-red-50/60' : ($esDia ? 'border-yellow-200 bg-yellow-50/70' : '') ?>">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <div class="font-mono text-xs text-brand"><?= e($t['tasca_codi'] ?? '-') ?></div>
                    <h3 class="text-sm font-semibold text-gray-800 mt-1"><?= e($t['tasca_nom']) ?></h3>
                    <?php if ($t['equip_nom']): ?>
                        <div class="text-xs text-gray-400 mt-0.5"><?= e($t['equip_nom']) ?></div>
                    <?php endif; ?>
                </div>
                <?php if ($t['torn_nom']): ?>
                    <span class="inline-block bg-purple-50 text-purple-700 text-xs px-2 py-0.5 rounded whitespace-nowrap"><?= e($t['torn_nom']) ?></span>
                <?php endif; ?>
            </div>

            <div class="grid grid-cols-2 gap-3 mt-4 text-xs">
                <div>
                    <div class="text-gray-400">Espai</div>
                    <div class="text-gray-700 mt-0.5"><?= e($t['espai_nom'] ?? '-') ?></div>
                </div>
                <div>
                    <div class="text-gray-400">Periodicitat</div>
                    <div class="text-gray-700 mt-0.5"><?= e($t['periodicitat_nom'] ?? '-') ?></div>
                </div>
                <div class="col-span-2">
                    <div class="text-gray-400">Data propera</div>
                    <div class="mt-0.5 <?= $vencuda ? 'text-red-600 font-medium' : ($esDia ? 'text-yellow-700 font-medium' : 'text-gray-700') ?>">
                        <?= $t['data_propera_realitzacio'] ? format_date($t['data_propera_realitzacio']) : '-' ?>
                        <?php if ($vencuda): ?>
                            <span class="text-[11px] text-red-500 ml-1">Vençuda</span>
                        <?php elseif ($esDia): ?>
                            <span class="text-[11px] text-yellow-600 ml-1">Del dia</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if (in_array($_SESSION['current_role'] ?? '', ['superadmin', 'admin_instalacio', 'cap_manteniment', 'tecnic'])): ?>
            <div class="mt-4 pt-3 border-t border-gray-100">
                <form method="POST" action="<?= url('registre/store') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="tasca_pla_id" value="<?= $t['id'] ?>">
                    <input type="hidden" name="data_execucio" value="<?= date('Y-m-d') ?>">
                    <input type="hidden" name="realitzada" value="1">
                    <input type="hidden" name="redirect" value="dia?data=<?= e($dataIso) ?><?= $tornActual ? '&torn=' . $tornActual : '' ?>">
                    <button type="submit" class="w-full bg-green-50 text-green-700 hover:bg-green-100 px-3 py-2 rounded-lg text-sm font-medium transition"
                            onclick="return confirm('Marcar com a realitzada?')">
                        Fet
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div class="hidden md:block bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200">
                    <th class="text-left px-4 py-3 font-medium text-gray-600 w-16">Codi</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Espai</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Tasca</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600 w-20">Periodicitat</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600 w-24">Torn</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600 w-24">Data propera</th>
                    <th class="text-center px-4 py-3 font-medium text-gray-600 w-20">Acció</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($tasques)): ?>
                    <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">No hi ha tasques pendents per a aquest dia.</td></tr>
                <?php else: ?>
                    <?php foreach ($tasques as $t): ?>
                    <?php
                        $vencuda = ($t['data_propera_realitzacio'] ?? null) < $dataIso;
                        $esDia = ($t['data_propera_realitzacio'] ?? null) === $dataIso;
                    ?>
                    <tr class="hover:bg-gray-50 transition <?= $vencuda ? 'bg-red-50' : ($esDia ? 'bg-yellow-50' : '') ?>">
                        <td class="px-4 py-3 font-mono text-xs text-brand"><?= e($t['tasca_codi'] ?? '-') ?></td>
                        <td class="px-4 py-3 text-gray-500 text-xs"><?= e($t['espai_nom'] ?? '-') ?></td>
                        <td class="px-4 py-3">
                            <div class="max-w-sm truncate" title="<?= e($t['tasca_nom']) ?>"><?= e($t['tasca_nom']) ?></div>
                            <?php if ($t['equip_nom']): ?>
                                <div class="text-xs text-gray-400 mt-0.5"><?= e($t['equip_nom']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-gray-500 text-xs"><?= e($t['periodicitat_nom'] ?? '-') ?></td>
                        <td class="px-4 py-3">
                            <?php if ($t['torn_nom']): ?>
                                <span class="inline-block bg-purple-50 text-purple-700 text-xs px-2 py-0.5 rounded"><?= e($t['torn_nom']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-xs <?= $vencuda ? 'text-red-600 font-medium' : ($esDia ? 'text-yellow-700 font-medium' : 'text-gray-500') ?>">
                            <?= $t['data_propera_realitzacio'] ? format_date($t['data_propera_realitzacio']) : '-' ?>
                            <?php if ($vencuda): ?>
                                <div class="text-[11px] text-red-500">Vençuda</div>
                            <?php elseif ($esDia): ?>
                                <div class="text-[11px] text-yellow-600">Del dia</div>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <?php if (in_array($_SESSION['current_role'] ?? '', ['superadmin', 'admin_instalacio', 'cap_manteniment', 'tecnic'])): ?>
                            <form method="POST" action="<?= url('registre/store') ?>" class="inline-flex items-center gap-1">
                                <?= csrf_field() ?>
                                <input type="hidden" name="tasca_pla_id" value="<?= $t['id'] ?>">
                                <input type="hidden" name="data_execucio" value="<?= date('Y-m-d') ?>">
                                <input type="hidden" name="realitzada" value="1">
                                <input type="hidden" name="redirect" value="dia?data=<?= e($dataIso) ?><?= $tornActual ? '&torn=' . $tornActual : '' ?>">
                                <button type="submit" class="bg-green-50 text-green-700 hover:bg-green-100 px-2.5 py-1 rounded text-xs font-medium transition"
                                        onclick="return confirm('Marcar com a realitzada?')">
                                    Fet
                                </button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="px-4 py-3 border-t border-gray-200 text-sm text-gray-500">
        <?= count($tasques) ?> tasques
        <?php if ($nVencudes > 0): ?>
            <span class="text-red-500 font-medium">(<?= $nVencudes ?> vençudes)</span>
        <?php endif; ?>
        <?php if ($nDia > 0): ?>
            <span>(<?= $nDia ?> del dia)</span>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
