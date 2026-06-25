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

    <?php
    // Mapa instalacio_id => rol_id de les assignacions actuals (per preseleccionar).
    $rolPerInst = [];
    foreach ($assignacions ?? [] as $a) {
        $rolPerInst[(int)$a['instalacio_id']] = (int)$a['rol_id'];
    }
    ?>

    <?php if (!empty($isSuperadmin)): ?>
    <!-- Superadmin: assignació a múltiples instal·lacions, cada una amb el seu rol -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-start justify-between gap-4 mb-1">
            <h3 class="text-lg font-semibold text-gray-800">Instal·lacions i rols</h3>
            <span class="assign-count text-xs font-medium text-gray-500 bg-gray-100 px-2.5 py-1 rounded-full whitespace-nowrap">0 assignades</span>
        </div>
        <p class="text-sm text-gray-500 mb-4">Tria un rol per a cada instal·lació on treballi aquesta persona. Les que deixis sense rol no hi tindran accés.</p>

        <div class="space-y-3">
            <?php foreach ($instalacions as $inst): ?>
            <?php
                $instId = (int)$inst['id'];
                $rolSel = $rolPerInst[$instId] ?? 0;
                $tornsInst = $tornsPerInstalacio[$instId] ?? [];
                $tornsSel = $tornsAssignatsPerInst[$instId] ?? [];
                $assignada = $rolSel > 0;
            ?>
            <div class="assign-inst rounded-xl border-2 transition-all overflow-hidden <?= $assignada ? 'border-brand bg-brand/5' : 'border-gray-200 bg-white' ?>" data-instalacio="<?= $instId ?>">
                <div class="flex items-center gap-3 p-4">
                    <div class="assign-icon w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0 transition <?= $assignada ? 'bg-brand/15 text-brand' : 'bg-gray-100 text-gray-400' ?>">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0H5m14 0h2M5 21H3m4-13h2m-2 4h2m6-4h2m-2 4h2"/></svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="font-medium text-gray-800 truncate"><?= e($inst['nom']) ?></p>
                        <p class="assign-status text-xs <?= $assignada ? 'text-brand-dark' : 'text-gray-400' ?>"><?= $assignada ? 'Assignada' : 'Sense assignar' ?></p>
                    </div>
                    <div class="w-40 sm:w-48 flex-shrink-0">
                        <select name="assign[<?= $instId ?>][rol]" class="assign-rol w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-brand focus:border-brand outline-none">
                            <option value="">Sense rol</option>
                            <?php foreach ($rols as $r): ?>
                                <option value="<?= $r['id'] ?>" <?= (int)$r['id'] === $rolSel ? 'selected' : '' ?>><?= e(ucfirst(str_replace('_', ' ', $r['nom']))) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <?php if (!empty($tornsInst)): ?>
                <div class="assign-torns border-t border-brand/10 bg-white/60 px-4 py-3 <?= $assignada ? '' : 'hidden' ?>">
                    <p class="text-xs font-medium text-gray-500 mb-2">Torns en aquesta instal·lació</p>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($tornsInst as $t): ?>
                        <label class="cursor-pointer">
                            <input type="checkbox" name="assign[<?= $instId ?>][torns][]" value="<?= (int)$t['id'] ?>"
                                   <?= in_array((int)$t['id'], $tornsSel, true) ? 'checked' : '' ?>
                                   <?= $assignada ? '' : 'disabled' ?>
                                   class="peer sr-only">
                            <span class="inline-flex items-center gap-1.5 rounded-full border border-gray-300 bg-white px-3 py-1 text-sm text-gray-600 transition peer-checked:border-brand peer-checked:bg-brand peer-checked:text-white peer-disabled:opacity-40"><?= e($t['nom']) ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php else: ?>
    <!-- Admin d'instal·lació: només la seva instal·lació activa -->
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
    <?php endif; ?>

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
// Superadmin: estat visual de cada instal·lació segons si té rol assignat.
(function () {
    var blocks = document.querySelectorAll('.assign-inst');
    var counter = document.querySelector('.assign-count');
    if (!blocks.length) return;

    function updateCounter() {
        var n = 0;
        blocks.forEach(function (b) {
            if (b.querySelector('.assign-rol').value !== '') n++;
        });
        if (counter) counter.textContent = n + (n === 1 ? ' assignada' : ' assignades');
    }

    blocks.forEach(function (block) {
        var rolSelect = block.querySelector('.assign-rol');
        var icon = block.querySelector('.assign-icon');
        var status = block.querySelector('.assign-status');
        var tornsBlock = block.querySelector('.assign-torns');
        var checks = block.querySelectorAll('input[type="checkbox"]');
        if (!rolSelect) return;

        function sync() {
            var hasRol = rolSelect.value !== '';

            block.classList.toggle('border-brand', hasRol);
            block.classList.toggle('bg-brand/5', hasRol);
            block.classList.toggle('border-gray-200', !hasRol);
            block.classList.toggle('bg-white', !hasRol);

            if (icon) {
                icon.classList.toggle('bg-brand/15', hasRol);
                icon.classList.toggle('text-brand', hasRol);
                icon.classList.toggle('bg-gray-100', !hasRol);
                icon.classList.toggle('text-gray-400', !hasRol);
            }
            if (status) {
                status.textContent = hasRol ? 'Assignada' : 'Sense assignar';
                status.classList.toggle('text-brand-dark', hasRol);
                status.classList.toggle('text-gray-400', !hasRol);
            }
            if (tornsBlock) tornsBlock.classList.toggle('hidden', !hasRol);

            checks.forEach(function (input) {
                input.disabled = !hasRol;
                if (!hasRol) input.checked = false;
            });
            updateCounter();
        }
        rolSelect.addEventListener('change', sync);
    });

    updateCounter();
})();

// Admin d'instal·lació: torns de la instal·lació única seleccionada.
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
