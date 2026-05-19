<?php
$title = 'Registre de Tasques';
$filters = $filters ?? [];
$filterOptions = $filterOptions ?? ['tasques' => [], 'espais' => [], 'torns' => []];
$activeFilters = array_filter($filters, fn($value) => $value !== null && $value !== '');
$baseQuery = array_filter([
    'date_from' => $filters['date_from'] ?? null,
    'date_to' => $filters['date_to'] ?? null,
    'tasca' => $filters['tasca_pla_id'] ?? null,
    'espai' => $filters['espai_id'] ?? null,
    'torn' => $filters['torn_id'] ?? null,
    'q' => $filters['q'] ?? null,
], fn($value) => $value !== null && $value !== '');
$pageUrl = function (int $page) use ($baseQuery): string {
    return url('registre?' . http_build_query(array_merge($baseQuery, ['page' => $page])));
};
ob_start();
?>

<div class="mb-5">
    <div class="flex flex-col xl:flex-row xl:items-end xl:justify-between gap-3">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-gray-800">Registre de Tasques</h2>
            <p class="text-gray-500 text-sm mt-1">Historial d'execucions de tasques de manteniment</p>
        </div>
        <div class="text-sm text-gray-500">
            <?= $pagination['total'] ?> registres<?= !empty($activeFilters) ? ' filtrats' : '' ?>
        </div>
    </div>
</div>

<form method="GET" action="<?= url('registre') ?>" class="mb-5 rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
    <div class="grid grid-cols-1 lg:grid-cols-[1.2fr_1fr_1fr] gap-0 divide-y lg:divide-y-0 lg:divide-x divide-gray-100">
        <div class="p-4">
            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Buscador</label>
            <div class="relative">
                <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35m1.1-5.15a6.25 6.25 0 11-12.5 0 6.25 6.25 0 0112.5 0z"/>
                </svg>
                <input type="text" name="q" value="<?= e($filters['q'] ?? '') ?>" placeholder="Cercar per tasca, codi, espai, torn, tècnic o comentari"
                       class="w-full border border-gray-300 rounded-lg pl-9 pr-3 py-2 text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none">
            </div>
        </div>

        <div class="p-4">
            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Data</label>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <input type="date" name="date_from" value="<?= e($filters['date_from'] ?? '') ?>"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none">
                <input type="date" name="date_to" value="<?= e($filters['date_to'] ?? '') ?>"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none">
            </div>
        </div>

        <div class="p-4 flex flex-col justify-end gap-2">
            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand px-4 py-2 text-sm font-medium text-white transition hover:bg-brand-dark active:scale-[0.98]">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h18M6 12h12M10 19h4"/>
                </svg>
                Aplicar filtres
            </button>
            <?php if (!empty($activeFilters)): ?>
                <a href="<?= url('registre') ?>" class="inline-flex items-center justify-center rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-200 active:scale-[0.98]">Netejar</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 border-t border-gray-100 p-4">
        <div>
            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Tasca</label>
            <select name="tasca" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none">
                <option value="">Totes les tasques</option>
                <?php foreach ($filterOptions['tasques'] as $tasca): ?>
                    <option value="<?= $tasca['id'] ?>" <?= (string)($filters['tasca_pla_id'] ?? '') === (string)$tasca['id'] ? 'selected' : '' ?>>
                        <?= e(($tasca['tasca_codi'] ?? '-') . ' — ' . ($tasca['tasca_nom'] ?? '-')) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Espai</label>
            <select name="espai" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none">
                <option value="">Tots els espais</option>
                <?php foreach ($filterOptions['espais'] as $espai): ?>
                    <option value="<?= $espai['id'] ?>" <?= (string)($filters['espai_id'] ?? '') === (string)$espai['id'] ? 'selected' : '' ?>>
                        <?= e($espai['nom']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Torn</label>
            <select name="torn" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none">
                <option value="">Tots els torns</option>
                <?php foreach ($filterOptions['torns'] as $torn): ?>
                    <option value="<?= $torn['id'] ?>" <?= (string)($filters['torn_id'] ?? '') === (string)$torn['id'] ? 'selected' : '' ?>>
                        <?= e($torn['nom']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</form>

<?php if (!empty($activeFilters)): ?>
<div class="mb-4 flex flex-wrap items-center gap-2 text-xs">
    <span class="text-gray-400">Filtres actius:</span>
    <?php if (!empty($filters['date_from']) || !empty($filters['date_to'])): ?>
        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-gray-600">Data <?= e($filters['date_from'] ?? '...') ?> - <?= e($filters['date_to'] ?? '...') ?></span>
    <?php endif; ?>
    <?php if (!empty($filters['q'])): ?>
        <span class="rounded-full bg-brand-light px-2.5 py-1 text-brand-dark">"<?= e($filters['q']) ?>"</span>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if (empty($registres) && !empty($activeFilters)): ?>
<div class="bg-white rounded-xl shadow-sm border border-gray-200 px-4 py-10 text-center">
    <div class="mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-gray-100 text-gray-400">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h18M6 12h12M10 19h4"/>
        </svg>
    </div>
    <p class="font-medium text-gray-700">No hi ha registres amb aquests filtres.</p>
    <p class="text-sm text-gray-400 mt-1">Prova a ampliar dates o netejar algun criteri.</p>
</div>
<?php else: ?>

<div class="space-y-4 md:hidden">
    <?php if (empty($registres)): ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 px-4 py-8 text-center text-gray-400">No hi ha registres d'execucions.</div>
    <?php else: ?>
        <?php foreach ($registres as $r): ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <div class="font-mono text-xs text-brand"><?= e($r['tasca_codi'] ?? '-') ?></div>
                    <h3 class="text-sm font-semibold text-gray-800 mt-1"><?= e($r['tasca_nom'] ?? '-') ?></h3>
                </div>
                <?php if ($r['realitzada']): ?>
                    <span class="inline-block bg-green-50 text-green-700 text-xs px-2 py-0.5 rounded">Fet</span>
                <?php else: ?>
                    <span class="inline-block bg-red-50 text-red-700 text-xs px-2 py-0.5 rounded">No fet</span>
                <?php endif; ?>
            </div>
            <div class="grid grid-cols-2 gap-3 mt-4 text-xs">
                <div>
                    <div class="text-gray-400">Data</div>
                    <div class="text-gray-700 mt-0.5"><?= format_date($r['data_execucio']) ?></div>
                </div>
                <div>
                    <div class="text-gray-400">Tècnic</div>
                    <div class="text-gray-700 mt-0.5"><?= e($r['usuari_nom'] ?? '-') ?></div>
                </div>
                <div>
                    <div class="text-gray-400">Espai</div>
                    <div class="text-gray-700 mt-0.5"><?= e($r['espai_nom'] ?? '-') ?></div>
                </div>
                <div>
                    <div class="text-gray-400">Torn</div>
                    <div class="mt-0.5">
                        <?php if (!empty($r['torn_nom'])): ?>
                            <span class="inline-block bg-purple-50 text-purple-700 text-xs px-2 py-0.5 rounded"><?= e($r['torn_nom']) ?></span>
                        <?php else: ?>
                            <span class="text-gray-500">-</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-span-2">
                    <div class="text-gray-400">Comentaris</div>
                    <div class="text-gray-700 mt-0.5"><?= e($r['comentaris'] ?? '-') ?></div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div class="hidden md:block bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200">
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Data</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Codi</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Tasca</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Espai</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Torn</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Tècnic</th>
                    <th class="text-center px-4 py-3 font-medium text-gray-600">Estat</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Comentaris</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($registres)): ?>
                    <tr><td colspan="8" class="px-4 py-8 text-center text-gray-400">No hi ha registres d'execucions.</td></tr>
                <?php else: ?>
                    <?php foreach ($registres as $r): ?>
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-4 py-3 text-xs text-gray-500 whitespace-nowrap"><?= format_date($r['data_execucio']) ?></td>
                        <td class="px-4 py-3 font-mono text-xs text-brand"><?= e($r['tasca_codi'] ?? '-') ?></td>
                        <td class="px-4 py-3">
                            <div class="max-w-xs truncate" title="<?= e($r['tasca_nom'] ?? '') ?>"><?= e($r['tasca_nom'] ?? '-') ?></div>
                        </td>
                        <td class="px-4 py-3 text-gray-500 text-xs"><?= e($r['espai_nom'] ?? '-') ?></td>
                        <td class="px-4 py-3">
                            <?php if (!empty($r['torn_nom'])): ?>
                                <span class="inline-block bg-purple-50 text-purple-700 text-xs px-2 py-0.5 rounded"><?= e($r['torn_nom']) ?></span>
                            <?php else: ?>
                                <span class="text-gray-400">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-gray-500 text-xs"><?= e($r['usuari_nom'] ?? '-') ?></td>
                        <td class="px-4 py-3 text-center">
                            <?php if ($r['realitzada']): ?>
                                <span class="inline-block bg-green-50 text-green-700 text-xs px-2 py-0.5 rounded">Fet</span>
                            <?php else: ?>
                                <span class="inline-block bg-red-50 text-red-700 text-xs px-2 py-0.5 rounded">No fet</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-gray-500 text-xs max-w-xs truncate"><?= e($r['comentaris'] ?? '-') ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($pagination['total_pages'] > 1): ?>
    <div class="px-4 py-3 border-t border-gray-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2">
        <p class="text-sm text-gray-500">
            Mostrant <?= count($registres) ?> de <?= $pagination['total'] ?> registres
        </p>
        <div class="flex gap-1">
            <?php if ($pagination['current_page'] > 1): ?>
                <a href="<?= $pageUrl($pagination['current_page'] - 1) ?>"
                   class="px-3 py-1 text-sm rounded bg-gray-100 text-gray-600 hover:bg-gray-200 transition">&larr;</a>
            <?php endif; ?>
            <?php
                $start = max(1, $pagination['current_page'] - 3);
                $end = min($pagination['total_pages'], $pagination['current_page'] + 3);
            ?>
            <?php for ($i = $start; $i <= $end; $i++): ?>
                <a href="<?= $pageUrl($i) ?>"
                   class="px-3 py-1 text-sm rounded <?= $i === $pagination['current_page'] ? 'bg-brand text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?> transition">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
            <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                <a href="<?= $pageUrl($pagination['current_page'] + 1) ?>"
                   class="px-3 py-1 text-sm rounded bg-gray-100 text-gray-600 hover:bg-gray-200 transition">&rarr;</a>
            <?php endif; ?>
        </div>
    </div>
    <?php else: ?>
    <div class="px-4 py-3 border-t border-gray-200 text-sm text-gray-500">
        <?= $pagination['total'] ?> registres
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
