<?php
$title = 'Vista Diària';
$dataActual = date('Y-m-d');
$dataIso = $dataSeleccionada->format('Y-m-d');
$dataAnterior = (clone $dataSeleccionada)->modify('-1 day')->format('Y-m-d');
$dataSeguent = (clone $dataSeleccionada)->modify('+1 day')->format('Y-m-d');
$queryBase = ($tornActual ? '&torn=' . $tornActual : '') . ($search ? '&q=' . urlencode($search) : '');
$nVencudes = count(array_filter($tasques, fn($t) => ($t['data_propera_realitzacio'] ?? null) < $dataIso));
$nDia = count($tasques) - $nVencudes;
$limitMobile = 15;
ob_start();
?>

<div class="mb-4 max-w-full overflow-x-hidden">
    <h2 class="text-xl sm:text-2xl font-bold text-gray-800">Vista Diària</h2>
    <p class="text-gray-500 text-sm mt-1">Tasques del dia seleccionat i vencides pendents</p>
</div>

<!-- Buscador -->
<div class="mb-4 max-w-full overflow-x-hidden">
    <form method="GET" action="<?= url('dia') ?>" class="flex flex-col sm:flex-row gap-2">
        <input type="hidden" name="data" value="<?= e($dataIso) ?>">
        <?php if ($tornActual): ?>
            <input type="hidden" name="torn" value="<?= e((string)$tornActual) ?>">
        <?php endif; ?>
        <input type="text" name="q" value="<?= e($search ?? '') ?>" placeholder="Cercar tasca, codi, espai, equip..."
               class="w-full min-w-0 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none">
        <button type="submit" class="bg-gray-100 text-gray-600 px-4 py-2 rounded-lg text-sm hover:bg-gray-200 transition">Cercar</button>
        <?php if ($search): ?>
            <a href="<?= url('dia?data=' . $dataIso . ($tornActual ? '&torn=' . $tornActual : '')) ?>" class="bg-gray-100 text-gray-500 px-4 py-2 rounded-lg text-sm hover:bg-gray-200 transition text-center">Netejar</a>
        <?php endif; ?>
    </form>
</div>

<!-- Navegació + filtres -->
<div class="flex flex-col xl:flex-row items-start xl:items-center justify-between gap-3 mb-3 max-w-full overflow-x-hidden">
    <div class="flex flex-wrap items-stretch gap-2 w-full sm:w-auto min-w-0 max-w-full">
        <a href="<?= url('dia?data=' . $dataAnterior . $queryBase) ?>"
           class="bg-white border border-gray-300 rounded-lg px-3 py-2 text-sm hover:bg-gray-50 transition">
            &larr;
        </a>
        <div class="min-w-0 flex-1 bg-white border border-gray-200 rounded-lg px-3 py-2 text-center">
            <div class="text-sm font-semibold text-gray-800"><?= e($dataSeleccionada->format('d/m/Y')) ?></div>
            <div class="text-[11px] sm:text-xs text-gray-500 leading-tight"><?= e($dataSeleccionada->format('l')) ?></div>
        </div>
        <a href="<?= url('dia?data=' . $dataSeguent . $queryBase) ?>"
           class="bg-white border border-gray-300 rounded-lg px-3 py-2 text-sm hover:bg-gray-50 transition">
            &rarr;
        </a>
        <?php if ($dataIso !== $dataActual): ?>
        <a href="<?= url('dia' . ($tornActual ? '?torn=' . $tornActual : '') . ($search ? ($tornActual ? '&' : '?') . 'q=' . urlencode($search) : '')) ?>"
           class="text-sm text-brand hover:text-brand-dark transition ml-1">Avui</a>
        <?php endif; ?>
        <form method="GET" action="<?= url('dia') ?>" class="flex items-center gap-1 w-full sm:w-auto sm:ml-2">
            <?php if ($tornActual): ?><input type="hidden" name="torn" value="<?= e((string)$tornActual) ?>"><?php endif; ?>
            <?php if ($search): ?><input type="hidden" name="q" value="<?= e($search) ?>"><?php endif; ?>
            <input type="date" name="data" value="<?= e($dataIso) ?>"
                   class="border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:ring-2 focus:ring-brand focus:border-brand outline-none min-w-0 flex-1 sm:w-32">
            <button type="submit" class="bg-gray-100 text-gray-600 px-2 py-1.5 rounded-lg text-xs hover:bg-gray-200 transition shrink-0">Anar</button>
        </form>
    </div>

    <div class="flex flex-wrap items-center gap-2 w-full min-w-0 max-w-full">
        <span class="text-sm text-gray-500 shrink-0">Torn:</span>
        <a href="<?= url('dia?data=' . $dataIso . ($search ? '&q=' . urlencode($search) : '')) ?>"
           class="px-3 py-1.5 text-sm rounded-2xl <?= !$tornActual ? 'bg-brand text-white' : 'bg-white border border-gray-300 text-gray-600 hover:bg-gray-50' ?> transition">
            Tots
        </a>
        <?php foreach ($torns as $t): ?>
        <a href="<?= url('dia?data=' . $dataIso . '&torn=' . $t['id'] . ($search ? '&q=' . urlencode($search) : '')) ?>"
           class="px-3 py-1.5 text-sm rounded-2xl <?= $tornActual == $t['id'] ? 'bg-brand text-white' : 'bg-white border border-gray-300 text-gray-600 hover:bg-gray-50' ?> transition">
            <?= e($t['nom']) ?>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<!-- Resum ràpid -->
<?php if (!empty($tasques)): ?>
<div class="flex flex-wrap items-center gap-2 mb-3 text-sm">
    <span class="text-gray-500"><?= count($tasques) ?> tasques</span>
    <?php if ($nVencudes > 0): ?>
        <span class="bg-red-100 text-red-700 px-2 py-0.5 rounded text-xs font-medium"><?= $nVencudes ?> vençudes</span>
    <?php endif; ?>
    <?php if ($nDia > 0): ?>
        <span class="bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded text-xs font-medium"><?= $nDia ?> del dia</span>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Mobile: cards compactes amb expand + mostrar més -->
<div class="md:hidden w-full max-w-full overflow-x-hidden" x-data="{ showAll: false, expanded: {} }">
    <?php if (empty($tasques)): ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 px-4 py-8 text-center text-gray-400">No hi ha tasques pendents per a aquest dia.</div>
    <?php else: ?>
        <div class="space-y-2 w-full max-w-full">
        <?php foreach ($tasques as $idx => $t): ?>
        <?php
            $vencuda = ($t['data_propera_realitzacio'] ?? null) < $dataIso;
            $esDia = ($t['data_propera_realitzacio'] ?? null) === $dataIso;
        ?>
        <div x-show="showAll || <?= $idx ?> < <?= $limitMobile ?>" 
             class="w-full max-w-full overflow-hidden bg-white rounded-lg shadow-sm border px-3 py-2.5 <?= $vencuda ? 'border-red-200 bg-red-50/60' : ($esDia ? 'border-yellow-200 bg-yellow-50/70' : 'border-gray-200') ?>">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <div class="flex items-start gap-2 min-w-0">
                        <span class="font-mono text-[11px] text-brand shrink-0 pt-0.5"><?= e($t['tasca_codi'] ?? '-') ?></span>
                        <span class="text-sm leading-5 text-gray-800 break-words min-w-0"><?= e($t['tasca_nom']) ?></span>
                    </div>
                    <div class="flex flex-wrap items-center gap-1 mt-2">
                        <?php if ($vencuda): ?>
                            <span class="bg-red-100 text-red-600 text-[10px] px-1.5 py-0.5 rounded font-medium">Vençuda</span>
                        <?php elseif ($esDia): ?>
                            <span class="bg-yellow-100 text-yellow-700 text-[10px] px-1.5 py-0.5 rounded font-medium">Avui</span>
                        <?php endif; ?>
                        <span class="bg-blue-50 text-blue-700 text-[10px] px-1.5 py-0.5 rounded">
                            <?= e($t['periodicitat_nom'] ?? '-') ?>
                        </span>
                        <?php if ($t['torn_nom']): ?>
                            <span class="bg-purple-50 text-purple-700 text-[10px] px-1.5 py-0.5 rounded"><?= e($t['torn_nom']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if (in_array($_SESSION['current_role'] ?? '', ['superadmin', 'admin_instalacio', 'cap_manteniment', 'tecnic'])): ?>
                <form method="POST" action="<?= url('registre/store') ?>" class="shrink-0">
                    <?= csrf_field() ?>
                    <input type="hidden" name="tasca_pla_id" value="<?= $t['id'] ?>">
                    <input type="hidden" name="data_execucio" value="<?= date('Y-m-d') ?>">
                    <input type="hidden" name="realitzada" value="1">
                    <input type="hidden" name="redirect" value="dia?data=<?= e($dataIso) ?><?= $tornActual ? '&torn=' . $tornActual : '' ?><?= $search ? '&q=' . urlencode($search) : '' ?>">
                    <button type="submit"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-green-200 bg-green-50 text-green-700 hover:bg-green-100 transition"
                            onclick="return confirm('Marcar com a realitzada?')"
                            title="Marcar com a realitzada"
                            aria-label="Marcar com a realitzada">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </button>
                </form>
                <?php endif; ?>
            </div>
            <div class="flex items-start justify-between gap-2 mt-2 text-xs text-gray-500">
                <span class="min-w-0 break-words leading-4"><?= e($t['espai_nom'] ?? '-') ?><?= $t['equip_nom'] ? ' · ' . e($t['equip_nom']) : '' ?></span>
                <button type="button" @click="expanded[<?= $t['id'] ?>] = !expanded[<?= $t['id'] ?>]" class="text-brand hover:text-brand-dark text-[11px] ml-2 shrink-0 whitespace-nowrap">
                    <span x-show="!expanded[<?= $t['id'] ?>]">+ Detalls</span>
                    <span x-show="expanded[<?= $t['id'] ?>]">- Tancar</span>
                </button>
            </div>
            <div x-show="expanded[<?= $t['id'] ?>]" x-collapse class="mt-3 pt-2 border-t border-gray-100 text-xs space-y-2">
                <div class="flex items-start justify-between gap-3">
                    <span class="text-gray-400">Data propera</span>
                    <span class="text-right <?= $vencuda ? 'text-red-600 font-medium' : 'text-gray-700' ?>"><?= $t['data_propera_realitzacio'] ? format_date($t['data_propera_realitzacio']) : '-' ?></span>
                </div>
                <?php if (in_array($_SESSION['current_role'] ?? '', ['superadmin', 'admin_instalacio', 'cap_manteniment', 'tecnic'])): ?>
                <form method="POST" action="<?= url('registre/store') ?>" class="pt-2">
                    <?= csrf_field() ?>
                    <input type="hidden" name="tasca_pla_id" value="<?= $t['id'] ?>">
                    <input type="hidden" name="data_execucio" value="<?= date('Y-m-d') ?>">
                    <input type="hidden" name="realitzada" value="1">
                    <input type="hidden" name="redirect" value="dia?data=<?= e($dataIso) ?><?= $tornActual ? '&torn=' . $tornActual : '' ?><?= $search ? '&q=' . urlencode($search) : '' ?>">
                    <button type="submit" class="w-full bg-green-50 text-green-700 hover:bg-green-100 px-3 py-2 rounded-lg text-sm font-medium transition"
                            onclick="return confirm('Marcar com a realitzada?')">
                        Fet
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
        <?php if (count($tasques) > $limitMobile): ?>
        <button x-show="!showAll" @click="showAll = true" type="button"
                class="w-full mt-3 bg-white border border-gray-300 text-gray-600 px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-gray-50 transition">
            Mostrar <?= count($tasques) - $limitMobile ?> tasques més
        </button>
        <?php endif; ?>
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
