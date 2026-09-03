<?php
$title = 'Importar Excel';
$backUrl = !empty($returnTo ?? '') ? url($returnTo) : url('dashboard');
$hasActiveInstalacio = !empty($currentInstalacioId ?? null);
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
                La importació s'aplicarà sobre la <span class="font-semibold">instal·lació activa</span>. El format es detectarà automàticament.
            </div>
        <?php else: ?>
            <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                Selecciona una instal·lació abans de pujar el fitxer.
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= url('import/upload') ?>" enctype="multipart/form-data" class="space-y-4">
            <?= csrf_field() ?>
            <?php if (!empty($returnTo ?? '')): ?>
                <input type="hidden" name="return_to" value="<?= e($returnTo) ?>">
            <?php endif; ?>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fitxer Excel (.xlsx / .xls)</label>
                <input type="file" name="excel_file" accept=".xlsx,.xls" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none file:mr-4 file:py-1 file:px-3 file:rounded file:border-0 file:text-sm file:bg-brand-light file:text-brand-dark hover:file:bg-brand-light">
            </div>

            <button type="submit" <?= !$hasActiveInstalacio ? 'disabled' : '' ?> class="w-full sm:w-auto bg-brand text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-brand-dark active:scale-[0.98] transition disabled:cursor-not-allowed disabled:bg-gray-300">
                Analitzar fitxer
            </button>
        </form>
    </div>

    <div class="space-y-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 sm:p-6">
            <h3 class="text-lg font-semibold text-gray-800">No cal escollir cap format</h3>
            <p class="text-sm text-gray-500 mt-1">La mateixa pujada reconeix automàticament:</p>

            <div class="mt-5 space-y-4 text-sm text-gray-600">
                <?php if ($hasActiveInstalacio): ?>
                <div class="rounded-xl border border-brand/20 bg-brand-light/60 p-4">
                    <p class="font-semibold text-gray-800 mb-1">Plantilla de configuració actual</p>
                    <p>El camí més fàcil: descarrega la plantilla adaptada a la teva instal·lació, omple-la seguint la fulla INSTRUCCIONS i puja-la aquí.</p>
                    <a href="<?= url('import/plantilla') ?>" class="mt-3 inline-flex items-center gap-2 bg-brand text-white px-4 py-2 rounded-lg text-xs font-semibold hover:bg-brand-dark transition">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 16V4m0 12l-4-4m4 4l4-4M4 20h16"/></svg>
                        Descarregar plantilla
                    </a>
                    <p class="text-xs text-gray-500 mt-2">Inclou exemples ja omplerts i instruccions de què recollir per cada bloc.</p>
                </div>
                <?php endif; ?>
                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                    <p class="font-semibold text-gray-800 mb-1">Excel complet</p>
                    <p>És el camí recomanat quan acabes de crear una instal·lació.</p>
                    <div class="mt-2 bg-white rounded-lg border border-brand/10 p-3 text-xs font-mono text-gray-700">
                        LLISTES | INVENTARI | BD TASQUES | TASQUES PLA_M | REGISTRE TASQUES
                    </div>
                    <p class="text-xs text-gray-500 mt-2">Afegeix espais, torns, equips, catàleg, pla i registres sobre la instal·lació activa.</p>
                </div>

                <div class="rounded-xl border border-gray-200 p-4">
                    <p class="font-semibold text-gray-800 mb-1">Llista senzilla de tasques</p>
                    <p>Només necessita una primera fila amb:</p>
                    <div class="mt-2 bg-gray-50 rounded-lg p-3 text-xs font-mono">
                        tasca | periodicitat
                    </div>
                    <p class="text-xs text-gray-500 mt-2">Normativa, sistema, tipus, espai, torn i equip continuen sent opcionals.</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 sm:p-6">
            <h3 class="text-lg font-semibold text-gray-800">Què passarà després</h3>
            <div class="mt-4 space-y-4 text-sm text-gray-600">
                <div class="import-step flex gap-3">
                    <span class="relative z-10 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-green-100 text-xs font-bold text-green-700">1</span>
                    <div>
                        <p class="font-medium text-gray-800">Detecta el contingut</p>
                        <p class="text-xs text-gray-500 mt-1">Revisa fulles, capçaleres i files abans de desar.</p>
                    </div>
                </div>
                <div class="import-step flex gap-3">
                    <span class="relative z-10 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand-light text-xs font-bold text-brand-dark">2</span>
                    <div>
                        <p class="font-medium text-gray-800">Mostra un resum clar</p>
                        <p class="text-xs text-gray-500 mt-1">Veuràs quants espais, equips, sistemes i tasques s'importaran.</p>
                    </div>
                </div>
                <div class="import-step flex gap-3">
                    <span class="relative z-10 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-amber-100 text-xs font-bold text-amber-700">3</span>
                    <div>
                        <p class="font-medium text-gray-800">Demana confirmació</p>
                        <p class="text-xs text-gray-500 mt-1">Els errors bloquegen la importació; els avisos queden visibles abans de confirmar.</p>
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
