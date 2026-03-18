<?php
$title = 'Importar Excel';
$backUrl = !empty($returnTo ?? '') ? url($returnTo) : url('dashboard');
ob_start();
?>

<div class="mb-6">
    <a href="<?= $backUrl ?>" class="text-sm text-gray-500 hover:text-brand transition flex items-center gap-1 mb-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        <?= !empty($returnTo ?? '') ? 'Tornar a onboarding' : 'Tornar' ?>
    </a>
    <h2 class="text-xl sm:text-2xl font-bold text-gray-800">Importar Excel</h2>
    <p class="text-gray-500 text-sm mt-1">Puja un fitxer Excel per importar dades al sistema</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Pujar fitxer</h3>
        <?php if (!empty($currentInstalacioId ?? null)): ?>
            <div class="mb-4 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                La importació completa s'aplicarà sobre la <span class="font-semibold">instal·lació activa</span> actual.
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
                    <option value="tasques_cataleg">Tasques al Catàleg (global)</option>
                    <option value="tasques_pla">Tasques al Pla de Manteniment (instal·lació actual)</option>
                    <option value="completa_instalacio" <?= empty($currentInstalacioId ?? null) ? 'disabled' : '' ?>>Importació completa de la instal·lació activa</option>
                </select>
                <?php if (empty($currentInstalacioId ?? null)): ?>
                    <p class="text-xs text-amber-600 mt-1">Per usar la importació completa cal tenir una instal·lació activa seleccionada.</p>
                <?php endif; ?>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fitxer Excel (.xlsx / .xls)</label>
                <input type="file" name="excel_file" accept=".xlsx,.xls" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none file:mr-4 file:py-1 file:px-3 file:rounded file:border-0 file:text-sm file:bg-brand-light file:text-brand-dark hover:file:bg-brand-light">
            </div>

            <button type="submit" class="w-full sm:w-auto bg-brand text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-brand-dark transition">
                Pujar i previsualitzar
            </button>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Format esperat</h3>

        <div class="space-y-4 text-sm text-gray-600">
            <div>
                <p class="font-medium text-gray-800 mb-1">Tasques al Catàleg</p>
                <p>La primera fila ha de ser la capçalera. Columnes esperades:</p>
                <div class="mt-2 bg-gray-50 rounded-lg p-3 text-xs font-mono">
                    A: Codi sistema | B: Nom tasca | C: Tipus equip | D: (alternatiu nom) | E: Periodicitat | F: Empresa
                </div>
            </div>

            <div>
                <p class="font-medium text-gray-800 mb-1">Tasques al Pla</p>
                <p>Vincula tasques del catàleg a la instal·lació activa:</p>
                <div class="mt-2 bg-gray-50 rounded-lg p-3 text-xs font-mono">
                    A: Codi | B: Nom tasca | C: Equipament | D: Espai | E: Periodicitat | F: Torn
                </div>
                <p class="text-xs text-gray-400 mt-1">Les tasques s'han de trobar al catàleg pel nom.</p>
            </div>

            <div>
                <p class="font-medium text-gray-800 mb-1">Importació completa de la instal·lació</p>
                <p>Per onboarding o reimportació inicial. El llibre Excel ha de contenir aquestes fulles:</p>
                <div class="mt-2 bg-gray-50 rounded-lg p-3 text-xs font-mono">
                    LLISTES | INVENTARI | BD TASQUES | TASQUES PLA_M | REGISTRE TASQUES
                </div>
                <p class="text-xs text-gray-400 mt-1">Afegeix espais, equips, catàleg, pla i registre sobre la instal·lació activa.</p>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
