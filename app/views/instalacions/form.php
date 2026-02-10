<?php
$title = $instalacio ? "Editar Instal·lació" : "Nova Instal·lació";
$action = $instalacio ? url('instalacions/update/' . $instalacio['id']) : url('instalacions/store');
ob_start();
?>

<div class="mb-6">
    <a href="<?= url('instalacions') ?>" class="text-sm text-gray-500 hover:text-brand transition flex items-center gap-1">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Tornar a instal·lacions
    </a>
    <h2 class="text-2xl font-bold text-gray-800 mt-2"><?= e($title) ?></h2>
</div>

<form method="POST" action="<?= $action ?>" class="space-y-6">
    <?= csrf_field() ?>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Nom <span class="text-red-500">*</span></label>
                <input type="text" name="nom" value="<?= e($instalacio['nom'] ?? '') ?>" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Adreça</label>
                <input type="text" name="adreca" value="<?= e($instalacio['adreca'] ?? '') ?>"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Telèfon</label>
                <input type="text" name="telefon" value="<?= e($instalacio['telefon'] ?? '') ?>"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" value="<?= e($instalacio['email'] ?? '') ?>"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none">
            </div>
            <div>
                <label class="flex items-center gap-2 mt-4">
                    <input type="checkbox" name="activa" value="1" <?= ($instalacio['activa'] ?? 1) ? 'checked' : '' ?>
                           class="w-4 h-4 text-brand border-gray-300 rounded focus:ring-brand">
                    <span class="text-sm text-gray-700">Instal·lació activa</span>
                </label>
            </div>
        </div>
    </div>

    <div class="flex items-center gap-3">
        <button type="submit" class="bg-brand text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-brand-dark transition">
            <?= $instalacio ? 'Actualitzar' : "Crear instal·lació" ?>
        </button>
        <a href="<?= url('instalacions') ?>" class="px-6 py-2.5 rounded-lg text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 transition">
            Cancel·lar
        </a>
    </div>
</form>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
