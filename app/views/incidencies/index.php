<?php
$title = 'Incidències';
ob_start();
?>

<div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-6">
    <div>
        <h2 class="text-xl sm:text-2xl font-bold text-gray-800">Incidències</h2>
        <p class="text-gray-500 text-sm mt-1">Tasques reportades amb incidències pendents de revisió</p>
    </div>
    <div class="text-sm text-gray-500">
        <?= count($incidencies) ?> obertes
    </div>
</div>

<?php if (empty($incidencies)): ?>
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 px-4 py-10 text-center">
        <div class="mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-green-50 text-green-700">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <p class="font-medium text-gray-700">No hi ha incidències obertes.</p>
        <p class="text-sm text-gray-400 mt-1">Quan un tècnic reporti una incidència apareixerà aquí.</p>
    </div>
<?php else: ?>
<div class="space-y-3">
    <?php foreach ($incidencies as $incidencia): ?>
    <?php $esFeta = $incidencia['tipus'] === 'feta_amb_incidencia'; ?>
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="font-mono text-xs text-brand"><?= e($incidencia['tasca_codi'] ?? '-') ?></span>
                    <span class="inline-block rounded px-2 py-0.5 text-xs font-medium <?= $esFeta ? 'bg-yellow-50 text-yellow-700' : 'bg-red-50 text-red-700' ?>">
                        <?= $esFeta ? 'Fet amb incidència' : 'No fet per incidència' ?>
                    </span>
                </div>
                <h3 class="text-sm font-semibold text-gray-800 mt-1"><?= e($incidencia['tasca_nom'] ?? '-') ?></h3>
                <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500">
                    <span>Data: <?= format_date($incidencia['data_programada']) ?></span>
                    <span>Espai: <?= e($incidencia['espai_nom'] ?? '-') ?></span>
                    <span>Torn: <?= e($incidencia['torn_nom'] ?? '-') ?></span>
                    <span>Reportat per: <?= e($incidencia['usuari_nom'] ?? '-') ?></span>
                </div>
                <div class="mt-3 rounded-lg bg-gray-50 px-3 py-2 text-sm text-gray-700">
                    <?= nl2br(e($incidencia['comentari'])) ?>
                </div>
            </div>
            <form method="POST" action="<?= url('incidencies/vista/' . $incidencia['id']) ?>" class="shrink-0">
                <?= csrf_field() ?>
                <button type="submit" class="inline-flex w-full lg:w-auto items-center justify-center rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-gray-800 active:scale-[0.98]">
                    Marcar com vista
                </button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
