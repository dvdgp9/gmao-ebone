<?php
$title = 'La meva jornada';
$s = $stats ?? [];
$tornsAssignats = $tornsAssignats ?? [];
$instalacioId = $instalacioId ?? null;
$senseTornsAssignats = $senseTornsAssignats ?? false;
ob_start();
?>

<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <p class="text-xs font-semibold uppercase tracking-wider text-brand">Inicio</p>
        <h2 class="mt-1 text-2xl font-bold text-gray-900">La meva jornada</h2>
        <p class="mt-1 text-sm text-gray-500">El treball assignat als teus torns — <?= e(date('d/m/Y')) ?></p>
    </div>
    <?php if ($instalacioId && !$senseTornsAssignats): ?>
    <div class="flex flex-wrap gap-2">
        <a href="<?= url('dia') ?>" class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand px-4 py-2 text-sm font-medium text-white transition hover:bg-brand-dark active:scale-[0.98]">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            Obrir vista diària
        </a>
        <a href="<?= url('setmana') ?>" class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 active:scale-[0.98]">
            Veure la setmana
        </a>
    </div>
    <?php endif; ?>
</div>

<?php if (!$instalacioId): ?>
<div class="rounded-xl border border-yellow-200 bg-yellow-50 p-6 text-yellow-900">
    <p class="font-medium">No tens cap instal·lació seleccionada.</p>
    <p class="mt-1 text-sm">Demana a un administrador que t'assigni a una instal·lació.</p>
</div>
<?php elseif ($senseTornsAssignats): ?>
<div class="rounded-xl border border-amber-200 bg-amber-50 p-6 text-amber-900">
    <div class="flex items-start gap-3">
        <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86a2 2 0 001.74-3L13.74 4a2 2 0 00-3.48 0L3.33 16a2 2 0 001.74 3z"/></svg>
        <div>
            <p class="font-medium">Encara no tens cap torn assignat</p>
            <p class="mt-1 text-sm text-amber-800">Contacta amb l'administrador perquè t'assigni el torn corresponent. Fins llavors no es mostraran tasques d'altres equips.</p>
        </div>
    </div>
</div>
<?php else: ?>

<div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
    <a href="<?= url('dia') ?>" class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition hover:border-brand/40 hover:shadow-md active:scale-[0.98]">
        <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Per fer avui</p>
        <p class="mt-2 text-2xl font-bold text-gray-900"><?= (int)($s['pendents_avui'] ?? 0) ?></p>
        <p class="mt-1 text-xs text-gray-500">Inclou les vençudes</p>
    </a>
    <a href="<?= url('dia') ?>" class="rounded-xl border bg-white p-4 shadow-sm transition hover:shadow-md active:scale-[0.98] <?= ($s['vencudes'] ?? 0) > 0 ? 'border-red-200' : 'border-gray-200' ?>">
        <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Vençudes</p>
        <p class="mt-2 text-2xl font-bold <?= ($s['vencudes'] ?? 0) > 0 ? 'text-red-600' : 'text-gray-900' ?>"><?= (int)($s['vencudes'] ?? 0) ?></p>
        <p class="mt-1 text-xs text-gray-500">Prioritat del torn</p>
    </a>
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Fetes avui</p>
        <p class="mt-2 text-2xl font-bold text-green-600"><?= (int)($s['fetes_avui'] ?? 0) ?></p>
        <p class="mt-1 text-xs text-gray-500">Registrades per tu</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Fetes aquest mes</p>
        <p class="mt-2 text-2xl font-bold text-gray-900"><?= (int)($s['fetes_mes'] ?? 0) ?></p>
        <p class="mt-1 text-xs text-gray-500">Registrades per tu</p>
    </div>
</div>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm lg:col-span-2">
        <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4">
            <div>
                <h3 class="font-semibold text-gray-900">La meva cua de treball</h3>
                <p class="mt-0.5 text-xs text-gray-500">Primer les tasques vençudes i les d'avui</p>
            </div>
            <a href="<?= url('pla') ?>" class="text-sm font-medium text-brand transition hover:text-brand-dark">Veure el meu pla</a>
        </div>

        <?php if (empty($s['properes_tasques'])): ?>
            <div class="px-5 py-10 text-center">
                <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-green-50 text-green-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </div>
                <p class="mt-3 text-sm font-medium text-gray-700">No tens tasques programades</p>
                <p class="mt-1 text-xs text-gray-400">La cua s'actualitzarà quan s'assignin tasques als teus torns.</p>
            </div>
        <?php else: ?>
            <div class="divide-y divide-gray-100">
                <?php foreach ($s['properes_tasques'] as $tasca): ?>
                    <?php
                    $vencuda = $tasca['data_propera_realitzacio'] < date('Y-m-d');
                    $avui = $tasca['data_propera_realitzacio'] === date('Y-m-d');
                    ?>
                    <div class="flex items-start gap-3 px-4 py-3 sm:px-5">
                        <div class="w-14 shrink-0 text-center">
                            <span class="block text-xs font-semibold <?= $vencuda ? 'text-red-600' : ($avui ? 'text-amber-600' : 'text-gray-700') ?>">
                                <?= e(date('d/m', strtotime($tasca['data_propera_realitzacio']))) ?>
                            </span>
                            <span class="mt-0.5 block text-[10px] uppercase tracking-wide <?= $vencuda ? 'text-red-500' : ($avui ? 'text-amber-500' : 'text-gray-400') ?>">
                                <?= $vencuda ? 'Vençuda' : ($avui ? 'Avui' : 'Pròxima') ?>
                            </span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex min-w-0 items-start gap-2">
                                <?php if (!empty($tasca['tasca_codi'])): ?>
                                    <span class="shrink-0 font-mono text-[11px] text-brand"><?= e($tasca['tasca_codi']) ?></span>
                                <?php endif; ?>
                                <p class="min-w-0 text-sm font-medium text-gray-800"><?= e($tasca['tasca_nom']) ?></p>
                            </div>
                            <p class="mt-1 text-xs text-gray-500">
                                <?= e($tasca['espai_nom'] ?? 'Sense espai') ?>
                                <?php if (!empty($tasca['torn_nom'])): ?>
                                    <span class="text-gray-300"> · </span><?= e($tasca['torn_nom']) ?>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <aside class="space-y-4">
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h3 class="font-semibold text-gray-900">Els meus torns</h3>
            <div class="mt-3 space-y-2">
                <?php foreach ($tornsAssignats as $torn): ?>
                    <div class="flex items-center gap-2 text-sm text-gray-700">
                        <span class="h-2 w-2 rounded-full bg-brand" aria-hidden="true"></span>
                        <?= e($torn['nom']) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="rounded-xl border border-brand/20 bg-brand-light p-5">
            <h3 class="font-semibold text-gray-900">Per registrar una tasca</h3>
            <p class="mt-2 text-sm leading-5 text-gray-600">Obre la vista diària, revisa la informació i marca el resultat de l'execució.</p>
            <a href="<?= url('dia') ?>" class="mt-4 inline-flex text-sm font-medium text-brand-dark hover:text-brand-700">Anar a les tasques d'avui →</a>
        </div>
    </aside>
</div>

<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
