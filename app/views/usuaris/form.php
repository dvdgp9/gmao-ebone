<?php
$title = $usuari ? 'Editar Usuari' : 'Nou Usuari';
$action = $usuari ? url('usuaris/update/' . $usuari['id']) : url('usuaris/store');
// Preseleccionar l'assignació actual quan l'usuari en té exactament una
$assignacioActual = (count($assignacions ?? []) === 1) ? $assignacions[0] : null;
$instalacioSeleccionada = $assignacioActual ? (int)$assignacioActual['instalacio_id'] : 0;
$rolSeleccionat = $assignacioActual ? (int)$assignacioActual['rol_id'] : 0;
ob_start();
?>

<div class="mb-6">
    <a href="<?= url('usuaris') ?>" class="text-sm text-gray-500 hover:text-brand transition flex items-center gap-1">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Tornar a usuaris
    </a>
    <h2 class="text-xl sm:text-2xl font-bold text-gray-800 mt-2"><?= e($title) ?></h2>
</div>

<form method="POST" action="<?= $action ?>" class="space-y-6">
    <?= csrf_field() ?>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Dades de l'usuari</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nom <span class="text-red-500">*</span></label>
                <input type="text" name="nom" value="<?= e($usuari['nom'] ?? '') ?>" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Cognoms</label>
                <input type="text" name="cognoms" value="<?= e($usuari['cognoms'] ?? '') ?>"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                <input type="email" name="email" value="<?= e($usuari['email'] ?? '') ?>" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Contrasenya <?= $usuari ? '' : '<span class="text-red-500">*</span>' ?>
                </label>
                <input type="password" name="password" <?= $usuari ? '' : 'required' ?>
                       placeholder="<?= $usuari ? 'Deixar en blanc per no canviar' : '' ?>"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none">
            </div>
        </div>
        <div class="mt-4">
            <label class="flex items-center gap-2">
                <input type="checkbox" name="actiu" value="1" <?= ($usuari['actiu'] ?? 1) ? 'checked' : '' ?>
                       class="w-4 h-4 text-brand border-gray-300 rounded focus:ring-brand">
                <span class="text-sm text-gray-700">Usuari actiu</span>
            </label>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Assignació a instal·lació</h3>

        <?php if (!empty($assignacions)): ?>
        <div class="mb-4">
            <p class="text-sm text-gray-500 mb-2">Assignacions actuals:</p>
            <div class="space-y-1">
                <?php foreach ($assignacions as $a): ?>
                <div class="flex items-center gap-2 text-sm">
                    <span class="font-medium text-gray-700"><?= e($a['instalacio_nom']) ?></span>
                    <span class="text-gray-400">→</span>
                    <span class="inline-block bg-brand-light text-brand-dark text-xs px-2 py-0.5 rounded"><?= e(ucfirst(str_replace('_', ' ', $a['rol_nom']))) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <hr class="my-4 border-gray-200">
        <p class="text-sm text-gray-500 mb-3">Actualitzar o afegir assignació:</p>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Instal·lació</label>
                <select name="instalacio_id" id="instalacio-select" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none">
                    <option value="">— Selecciona —</option>
                    <?php foreach ($instalacions as $inst): ?>
                        <option value="<?= $inst['id'] ?>" <?= (int)$inst['id'] === $instalacioSeleccionada ? 'selected' : '' ?>><?= e($inst['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Rol</label>
                <select name="rol_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none">
                    <option value="">— Selecciona —</option>
                    <?php foreach ($rols as $r): ?>
                        <option value="<?= $r['id'] ?>" <?= (int)$r['id'] === $rolSeleccionat ? 'selected' : '' ?>><?= e(ucfirst(str_replace('_', ' ', $r['nom']))) ?> — <?= e($r['descripcio'] ?? '') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <?php if (!empty($tornsPerInstalacio)): ?>
        <div class="mt-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Torns</label>
            <p class="text-xs text-gray-400 mb-2">Torns de la instal·lació seleccionada en què treballa l'usuari.</p>
            <p id="torns-placeholder" class="text-sm text-gray-400">Selecciona primer una instal·lació.</p>
            <?php foreach ($tornsPerInstalacio as $instId => $tornsInst): ?>
            <div class="torns-group hidden flex-wrap gap-2" data-instalacio="<?= (int)$instId ?>">
                <?php foreach ($tornsInst as $t): ?>
                <label class="flex items-center gap-2 bg-gray-50 rounded-lg px-4 py-2 cursor-pointer hover:bg-gray-100 transition">
                    <input type="checkbox" name="torns[]" value="<?= (int)$t['id'] ?>" disabled
                           <?= in_array((int)$t['id'], $tornsAssignats ?? []) ? 'checked' : '' ?>
                           class="w-4 h-4 text-brand border-gray-300 rounded focus:ring-brand">
                    <span class="text-sm text-gray-700"><?= e($t['nom']) ?></span>
                </label>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
        <button type="submit" class="bg-brand text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-brand-dark transition">
            <?= $usuari ? 'Actualitzar' : 'Crear usuari' ?>
        </button>
        <a href="<?= url('usuaris') ?>" class="px-6 py-2.5 rounded-lg text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 transition">
            Cancel·lar
        </a>
    </div>
</form>

<script>
(function () {
    var select = document.getElementById('instalacio-select');
    var groups = document.querySelectorAll('.torns-group');
    var placeholder = document.getElementById('torns-placeholder');
    if (!select || !groups.length) return;

    function refresh() {
        var current = select.value;
        var anyVisible = false;
        groups.forEach(function (group) {
            var match = group.dataset.instalacio === current;
            group.classList.toggle('hidden', !match);
            group.classList.toggle('flex', match);
            group.querySelectorAll('input[type="checkbox"]').forEach(function (input) {
                input.disabled = !match;
            });
            if (match) anyVisible = true;
        });
        if (placeholder) {
            placeholder.classList.toggle('hidden', anyVisible);
            placeholder.textContent = current && !anyVisible
                ? 'Aquesta instal·lació no té torns actius.'
                : 'Selecciona primer una instal·lació.';
        }
    }

    select.addEventListener('change', refresh);
    refresh();
})();
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
