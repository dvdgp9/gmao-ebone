<?php
$title = 'Dashboard';
$s = $stats ?? [];
ob_start();
?>

<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-800">Dashboard</h2>
    <p class="text-gray-500 text-sm mt-1">Resum general de manteniment — <?= e(date('F Y')) ?></p>
</div>

<?php if (!$instalacioId): ?>
<div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6 text-yellow-800">
    <p class="font-medium">No tens cap instal·lació seleccionada.</p>
    <p class="text-sm mt-1">Demana a un administrador que t'assigni a una instal·lació.</p>
</div>
<?php else: ?>

<!-- KPI Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 mb-8">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
        <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">Tasques al pla</p>
        <p class="text-2xl font-bold text-gray-800 mt-2"><?= $s['tasques_pla'] ?></p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
        <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">Pendents (avui)</p>
        <p class="text-2xl font-bold text-yellow-600 mt-2"><?= $s['tasques_pendents'] ?></p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 <?= $s['tasques_vencudes'] > 0 ? 'border-red-300 bg-red-50' : '' ?>">
        <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">Vençudes</p>
        <p class="text-2xl font-bold <?= $s['tasques_vencudes'] > 0 ? 'text-red-600' : 'text-gray-800' ?> mt-2"><?= $s['tasques_vencudes'] ?></p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
        <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">Equips actius</p>
        <p class="text-2xl font-bold text-blue-600 mt-2"><?= $s['equips_actius'] ?></p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
        <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">Execucions (mes)</p>
        <p class="text-2xl font-bold text-gray-800 mt-2"><?= $s['registres_mes'] ?></p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 <?= $s['grau_acompliment'] >= 80 ? 'border-green-300 bg-green-50' : ($s['grau_acompliment'] >= 50 ? '' : 'border-red-300 bg-red-50') ?>">
        <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">% Acompliment</p>
        <p class="text-2xl font-bold <?= $s['grau_acompliment'] >= 80 ? 'text-green-600' : ($s['grau_acompliment'] >= 50 ? 'text-yellow-600' : 'text-red-600') ?> mt-2">
            <?= $s['grau_acompliment'] ?>%
        </p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <!-- Properes tasques -->
    <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-200">
        <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="font-semibold text-gray-800">Properes tasques</h3>
            <a href="<?= url('pla') ?>" class="text-xs text-blue-600 hover:text-blue-800 transition">Veure tot</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="text-left px-4 py-2 font-medium text-gray-500 text-xs">Data</th>
                        <th class="text-left px-4 py-2 font-medium text-gray-500 text-xs">Codi</th>
                        <th class="text-left px-4 py-2 font-medium text-gray-500 text-xs">Tasca</th>
                        <th class="text-left px-4 py-2 font-medium text-gray-500 text-xs">Espai</th>
                        <th class="text-left px-4 py-2 font-medium text-gray-500 text-xs">Torn</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php if (empty($s['properes_tasques'])): ?>
                        <tr><td colspan="5" class="px-4 py-6 text-center text-gray-400 text-xs">No hi ha tasques programades</td></tr>
                    <?php else: ?>
                        <?php foreach ($s['properes_tasques'] as $t): ?>
                        <?php
                            $vencuda = $t['data_propera_realitzacio'] < date('Y-m-d');
                            $avui = $t['data_propera_realitzacio'] === date('Y-m-d');
                        ?>
                        <tr class="<?= $vencuda ? 'bg-red-50' : ($avui ? 'bg-yellow-50' : '') ?>">
                            <td class="px-4 py-2 text-xs <?= $vencuda ? 'text-red-600 font-medium' : ($avui ? 'text-yellow-600 font-medium' : 'text-gray-500') ?>">
                                <?= format_date($t['data_propera_realitzacio']) ?>
                            </td>
                            <td class="px-4 py-2 font-mono text-xs text-blue-600"><?= e($t['tasca_codi'] ?? '') ?></td>
                            <td class="px-4 py-2 text-xs max-w-xs truncate"><?= e($t['tasca_nom']) ?></td>
                            <td class="px-4 py-2 text-xs text-gray-500"><?= e($t['espai_nom'] ?? '-') ?></td>
                            <td class="px-4 py-2">
                                <?php if ($t['torn_nom']): ?>
                                    <span class="inline-block bg-purple-50 text-purple-700 text-xs px-1.5 py-0.5 rounded"><?= e($t['torn_nom']) ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Tasques per torn + per sistema -->
    <div class="space-y-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-5 py-4 border-b border-gray-200">
                <h3 class="font-semibold text-gray-800">Per torn</h3>
            </div>
            <div class="p-5 space-y-3">
                <?php if (empty($s['tasques_per_torn'])): ?>
                    <p class="text-xs text-gray-400">Sense dades</p>
                <?php else: ?>
                    <?php foreach ($s['tasques_per_torn'] as $tt): ?>
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm text-gray-700"><?= e($tt['torn_nom'] ?? 'Sense torn') ?></span>
                            <span class="text-xs text-gray-500"><?= $tt['total'] ?> tasques
                                <?php if ($tt['vencudes'] > 0): ?>
                                    <span class="text-red-600">(<?= $tt['vencudes'] ?> vençudes)</span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <?php $pct = $tt['total'] > 0 ? round((($tt['total'] - $tt['vencudes']) / $tt['total']) * 100) : 0; ?>
                        <div class="w-full bg-gray-100 rounded-full h-2">
                            <div class="h-2 rounded-full <?= $pct >= 80 ? 'bg-green-500' : ($pct >= 50 ? 'bg-yellow-500' : 'bg-red-500') ?>"
                                 style="width: <?= $pct ?>%"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-5 py-4 border-b border-gray-200">
                <h3 class="font-semibold text-gray-800">Per sistema</h3>
            </div>
            <div class="p-5 space-y-2">
                <?php if (empty($s['tasques_per_sistema'])): ?>
                    <p class="text-xs text-gray-400">Sense dades</p>
                <?php else: ?>
                    <?php foreach ($s['tasques_per_sistema'] as $ts): ?>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="inline-block bg-blue-50 text-blue-700 text-xs px-1.5 py-0.5 rounded font-mono"><?= e($ts['sistema_codi'] ?? '?') ?></span>
                            <span class="text-sm text-gray-600 truncate max-w-[140px]"><?= e($ts['sistema_nom'] ?? 'Altres') ?></span>
                        </div>
                        <span class="text-sm font-medium text-gray-800"><?= $ts['total'] ?></span>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
