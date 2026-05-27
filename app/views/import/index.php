<?php
$title = 'Importar Excel';
$backUrl = !empty($returnTo ?? '') ? url($returnTo) : url('dashboard');
$recommendedType = $recommendedType ?? (!empty($returnTo ?? '') ? 'completa_instalacio' : 'pla_rapid');
$hasActiveInstalacio = !empty($currentInstalacioId ?? null);
if (!$hasActiveInstalacio && in_array($recommendedType, ['pla_rapid', 'tasques_pla', 'completa_instalacio'], true)) {
    $recommendedType = 'tasques_cataleg';
}
ob_start();
?>

<div class="mb-6">
    <a href="<?= $backUrl ?>" class="text-sm text-gray-500 hover:text-brand transition flex items-center gap-1 mb-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        <?= !empty($returnTo ?? '') ? 'Tornar a onboarding' : 'Tornar' ?>
    </a>
    <h2 class="text-xl sm:text-2xl font-bold text-gray-800">Importar Excel</h2>
    <p class="text-gray-500 text-sm mt-1">Puja el fitxer, revisa la previsualització i confirma només quan tot quadri.</p>
</div>

<div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1.2fr)_minmax(320px,0.8fr)] gap-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 sm:p-6">
        <div class="flex items-start gap-3 mb-5">
            <div class="h-10 w-10 rounded-xl bg-brand-light text-brand-dark flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 16V4m0 12l-4-4m4 4l4-4M4 20h16"/></svg>
            </div>
            <div class="min-w-0">
                <h3 class="text-lg font-semibold text-gray-800">Pujar fitxer</h3>
                <p class="text-sm text-gray-500 mt-1">La càrrega no s'aplica fins que confirmes el pas següent.</p>
            </div>
        </div>
        <?php if ($hasActiveInstalacio): ?>
            <div class="mb-4 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                <?php if ($recommendedType === 'completa_instalacio'): ?>
                    Estàs en el camí recomanat per crear una instal·lació nova: pujar el llibre Excel complet i revisar-lo abans de confirmar.
                <?php else: ?>
                    Aquesta importació s'aplicarà sobre la <span class="font-semibold">instal·lació activa</span> actual.
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= url('import/upload') ?>" enctype="multipart/form-data" class="space-y-4">
            <?= csrf_field() ?>
            <?php if (!empty($returnTo ?? '')): ?>
                <input type="hidden" name="return_to" value="<?= e($returnTo) ?>">
            <?php endif; ?>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipus d'importació</label>
                <select name="import_type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none">
                    <option value="completa_instalacio" <?= !$hasActiveInstalacio ? 'disabled' : '' ?> <?= $recommendedType === 'completa_instalacio' ? 'selected' : '' ?>>Excel complet de la instal·lació activa</option>
                    <option value="pla_rapid" <?= !$hasActiveInstalacio ? 'disabled' : '' ?> <?= $recommendedType === 'pla_rapid' ? 'selected' : '' ?>>Pla ràpid: tasca + periodicitat</option>
                    <option value="tasques_pla" <?= !$hasActiveInstalacio ? 'disabled' : '' ?> <?= $recommendedType === 'tasques_pla' ? 'selected' : '' ?>>Només tasques al Pla de Manteniment</option>
                    <option value="tasques_cataleg" <?= $recommendedType === 'tasques_cataleg' ? 'selected' : '' ?>>Només tasques al Catàleg global</option>
                </select>
                <?php if (!$hasActiveInstalacio): ?>
                    <p class="text-xs text-amber-600 mt-1">Per importar un pla cal tenir una instal·lació activa seleccionada.</p>
                <?php elseif ($recommendedType === 'completa_instalacio'): ?>
                    <p class="text-xs text-gray-500 mt-1">Espera les fulles LLISTES, INVENTARI, BD TASQUES, TASQUES PLA_M i REGISTRE TASQUES.</p>
                <?php else: ?>
                    <p class="text-xs text-gray-500 mt-1">El pla ràpid crearà tasques mínimes al catàleg global només quan no trobi una coincidència segura.</p>
                <?php endif; ?>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fitxer Excel (.xlsx / .xls)</label>
                <input type="file" name="excel_file" accept=".xlsx,.xls" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none file:mr-4 file:py-1 file:px-3 file:rounded file:border-0 file:text-sm file:bg-brand-light file:text-brand-dark hover:file:bg-brand-light">
            </div>

            <button type="submit" class="w-full sm:w-auto bg-brand text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-brand-dark active:scale-[0.98] transition">
                Pujar i previsualitzar
            </button>
        </form>
    </div>

    <div class="space-y-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 sm:p-6">
            <h3 class="text-lg font-semibold text-gray-800">Quin format he d'usar?</h3>
            <p class="text-sm text-gray-500 mt-1">Escull segons el que vols carregar ara.</p>

            <div class="mt-5 space-y-4 text-sm text-gray-600">
                <div class="rounded-xl border border-brand/20 bg-brand-light/60 p-4">
                    <p class="font-semibold text-gray-800 mb-1">Excel complet</p>
                    <p>És el camí recomanat quan acabes de crear una instal·lació.</p>
                    <div class="mt-2 bg-white rounded-lg border border-brand/10 p-3 text-xs font-mono text-gray-700">
                        LLISTES | INVENTARI | BD TASQUES | TASQUES PLA_M | REGISTRE TASQUES
                    </div>
                    <p class="text-xs text-gray-500 mt-2">Afegeix espais, torns, equips, catàleg, pla i registres sobre la instal·lació activa.</p>
                </div>

                <div>
                    <p class="font-semibold text-gray-800 mb-1">Pla ràpid</p>
                    <p>Primera fila amb capçaleres. Camps mínims:</p>
                    <div class="mt-2 bg-gray-50 rounded-lg p-3 text-xs font-mono">
                        tasca | periodicitat
                    </div>
                    <p class="text-xs text-gray-500 mt-2">Opcionals: normativa, sistema, tipus, espai, torn, equip.</p>
                </div>

                <div>
                    <p class="font-medium text-gray-800 mb-1">Només Catàleg</p>
                    <p>Actualitza el catàleg global sense crear pla per una instal·lació.</p>
                    <div class="mt-2 bg-gray-50 rounded-lg p-3 text-xs font-mono">
                        A: Codi sistema | B: Nom tasca | C: Tipus equip | D: (alternatiu nom) | E: Periodicitat | F: Empresa
                    </div>
                </div>

                <div>
                    <p class="font-medium text-gray-800 mb-1">Només Pla</p>
                    <p>Vincula tasques ja existents del catàleg a la instal·lació activa.</p>
                    <div class="mt-2 bg-gray-50 rounded-lg p-3 text-xs font-mono">
                        A: Codi | B: Nom tasca | C: Equipament | D: Espai | E: Periodicitat | F: Torn
                    </div>
                    <p class="text-xs text-gray-400 mt-1">Les tasques s'han de trobar al catàleg pel nom.</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 sm:p-6">
            <h3 class="text-lg font-semibold text-gray-800">Què revisa el pla ràpid</h3>
            <div class="mt-4 space-y-4 text-sm text-gray-600">
                <div class="import-step flex gap-3">
                    <span class="relative z-10 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-green-100 text-xs font-bold text-green-700">1</span>
                    <div>
                        <p class="font-medium text-gray-800">Busca coincidències segures</p>
                        <p class="text-xs text-gray-500 mt-1">Compara nom, normativa, periodicitat, sistema i tipus.</p>
                    </div>
                </div>
                <div class="import-step flex gap-3">
                    <span class="relative z-10 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand-light text-xs font-bold text-brand-dark">2</span>
                    <div>
                        <p class="font-medium text-gray-800">Crea només si no existeix</p>
                        <p class="text-xs text-gray-500 mt-1">Les tasques noves es guarden al catàleg global amb dades mínimes.</p>
                    </div>
                </div>
                <div class="import-step flex gap-3">
                    <span class="relative z-10 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-amber-100 text-xs font-bold text-amber-700">3</span>
                    <div>
                        <p class="font-medium text-gray-800">Bloqueja dubtes</p>
                        <p class="text-xs text-gray-500 mt-1">Si hi ha una coincidència ambigua, no importa fins que es revisi.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
