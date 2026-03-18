<?php
$title = "Instal·lacions";
ob_start();
?>

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <h2 class="text-xl sm:text-2xl font-bold text-gray-800">Instal·lacions</h2>
        <p class="text-gray-500 text-sm mt-1">Gestió de centres i instal·lacions</p>
    </div>
    <a href="<?= url('instalacions/create') ?>" class="bg-brand text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-brand-dark transition flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Nova Instal·lació
    </a>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php if (empty($instalacions)): ?>
        <div class="col-span-full text-center py-12 text-gray-400">No hi ha instal·lacions registrades.</div>
    <?php else: ?>
        <?php foreach ($instalacions as $inst): ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition">
            <div class="flex items-start justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800"><?= e($inst['nom']) ?></h3>
                    <?php if ($inst['adreca']): ?>
                        <p class="text-sm text-gray-500 mt-1"><?= e($inst['adreca']) ?></p>
                    <?php endif; ?>
                </div>
                <span class="inline-block px-2 py-0.5 rounded text-xs <?= $inst['activa'] ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500' ?>">
                    <?= $inst['activa'] ? 'Activa' : 'Inactiva' ?>
                </span>
            </div>
            <div class="mt-4 text-sm text-gray-500 space-y-1">
                <?php if ($inst['telefon']): ?>
                    <p><?= e($inst['telefon']) ?></p>
                <?php endif; ?>
                <?php if ($inst['email']): ?>
                    <p><?= e($inst['email']) ?></p>
                <?php endif; ?>
            </div>
            <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                Neteja dades operatives per permetre una reimportació, però conserva la instal·lació i els usuaris assignats.
            </div>
            <div class="mt-4 pt-4 border-t border-gray-100 flex gap-3">
                <a href="<?= url('instalacions/edit/' . $inst['id']) ?>" class="text-sm text-brand hover:text-brand-dark transition">Editar</a>
                <form method="POST" action="<?= url('instalacions/clear-data/' . $inst['id']) ?>" onsubmit="return confirm('Segur que vols netejar totes les dades operatives d\'aquesta instal·lació? Es conservarà la instal·lació, però s\'eliminaran torns, espais, equips, tasques del pla i registres.')">
                    <?= csrf_field() ?>
                    <button type="submit" class="text-sm text-amber-600 hover:text-amber-700 transition">Netejar dades</button>
                </form>
                <form method="POST" action="<?= url('instalacions/delete/' . $inst['id']) ?>" onsubmit="return confirm('Segur que vols eliminar aquesta instal·lació?')">
                    <?= csrf_field() ?>
                    <button type="submit" class="text-sm text-red-600 hover:text-red-700 transition">Eliminar</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
