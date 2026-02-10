<?php
$title = 'Importar Excel';
ob_start();
?>

<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-800">Importar Excel</h2>
    <p class="text-gray-500 text-sm mt-1">Puja un fitxer Excel per importar dades al sistema</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Pujar fitxer</h3>

        <form method="POST" action="<?= url('import/upload') ?>" enctype="multipart/form-data" class="space-y-4">
            <?= csrf_field() ?>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipus d'importació</label>
                <select name="import_type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    <option value="tasques_cataleg">Tasques al Catàleg (global)</option>
                    <option value="tasques_pla">Tasques al Pla de Manteniment (instal·lació actual)</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fitxer Excel (.xlsx / .xls)</label>
                <input type="file" name="excel_file" accept=".xlsx,.xls" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none file:mr-4 file:py-1 file:px-3 file:rounded file:border-0 file:text-sm file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
            </div>

            <button type="submit" class="bg-blue-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-blue-700 transition">
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
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
