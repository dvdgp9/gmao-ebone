<?php
$title = 'Alta ràpida de tasques';
$backUrl = !empty($returnTo ?? '') ? url($returnTo) : url('pla');
$teEspais = !empty($espais);
$teTorns = !empty($torns);
ob_start();
?>

<div class="mb-6">
    <a href="<?= $backUrl ?>" class="text-sm text-gray-500 hover:text-brand transition flex items-center gap-1">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        <?= !empty($returnTo ?? '') ? 'Tornar a la configuració' : 'Tornar al pla' ?>
    </a>
    <h2 class="text-xl sm:text-2xl font-bold text-gray-800 mt-2">Alta ràpida de tasques</h2>
    <p class="text-gray-500 text-sm mt-1">
        Afegeix diverses tasques de cop: escriu què s'ha de fer i cada quan. Les files buides s'ignoren.
    </p>
</div>

<form method="POST" action="<?= url('pla/alta-rapida/store') ?>" x-data="{ files: 5 }">
    <?= csrf_field() ?>
    <?php if (!empty($returnTo ?? '')): ?>
        <input type="hidden" name="return_to" value="<?= e($returnTo) ?>">
    <?php endif; ?>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">
                    <th class="px-4 py-3 min-w-[260px]">Tasca <span class="text-red-500">*</span></th>
                    <th class="px-4 py-3 min-w-[150px]">Periodicitat <span class="text-red-500">*</span></th>
                    <th class="px-4 py-3 min-w-[150px]">Primera execució</th>
                    <?php if ($teEspais): ?><th class="px-4 py-3 min-w-[150px]">Espai</th><?php endif; ?>
                    <?php if ($teTorns): ?><th class="px-4 py-3 min-w-[150px]">Torn</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <template x-for="i in files" :key="i">
                    <tr class="border-b border-gray-100">
                        <td class="px-4 py-2">
                            <input type="text" name="nom[]" placeholder="p.ex. Revisió de la temperatura de l'aigua"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none">
                        </td>
                        <td class="px-4 py-2">
                            <select name="periodicitat_id[]" class="w-full border border-gray-300 rounded-lg px-2 py-2 text-sm focus:ring-2 focus:ring-brand outline-none">
                                <option value="">— Tria —</option>
                                <?php foreach ($periodicitats as $p): ?>
                                    <option value="<?= (int)$p['id'] ?>"><?= e($p['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td class="px-4 py-2">
                            <input type="date" name="data_primera[]" value="<?= date('Y-m-d') ?>"
                                   class="w-full border border-gray-300 rounded-lg px-2 py-2 text-sm focus:ring-2 focus:ring-brand outline-none">
                        </td>
                        <?php if ($teEspais): ?>
                        <td class="px-4 py-2">
                            <select name="espai_id[]" class="w-full border border-gray-300 rounded-lg px-2 py-2 text-sm focus:ring-2 focus:ring-brand outline-none">
                                <option value="">—</option>
                                <?php foreach ($espais as $esp): ?>
                                    <option value="<?= (int)$esp['id'] ?>"><?= e($esp['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <?php endif; ?>
                        <?php if ($teTorns): ?>
                        <td class="px-4 py-2">
                            <select name="torn_id[]" class="w-full border border-gray-300 rounded-lg px-2 py-2 text-sm focus:ring-2 focus:ring-brand outline-none">
                                <option value="">—</option>
                                <?php foreach ($torns as $t): ?>
                                    <option value="<?= (int)$t['id'] ?>"><?= e($t['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <?php endif; ?>
                    </tr>
                </template>
            </tbody>
        </table>
        <div class="p-3 border-t border-gray-100">
            <button type="button" @click="files += 5" class="text-sm text-brand hover:text-brand-dark transition font-medium">
                + Afegir 5 files més
            </button>
        </div>
    </div>

    <div class="mt-4 flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
        <button type="submit" class="bg-brand text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-brand-dark transition">
            Desar tasques
        </button>
        <a href="<?= $backUrl ?>" class="px-6 py-2.5 rounded-lg text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 transition text-center">
            Cancel·lar
        </a>
        <span class="text-xs text-gray-400 sm:ml-auto">La data de primera execució indica quan ha d'aparèixer la tasca per primer cop a les vistes de dia i setmana.</span>
    </div>
</form>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
