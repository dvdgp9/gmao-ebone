<?php
$esEnllacos = ($resultat['origen'] ?? '') === 'enllacos';
$title = $esEnllacos ? 'Enllaços d\'accés generats' : 'Usuaris importats';
$files = $resultat['files'];
$ambEnllac = array_values(array_filter($files, static fn($f) => !empty($f['enllac'])));
$existents = count($files) - count($ambEnllac);
$caduca = !empty($resultat['expires_at']) ? date('d/m/Y \a \l\e\s H:i', strtotime($resultat['expires_at'])) : null;

// Taula per enganxar a Excel o a un correu (separada per tabuladors).
$tsv = "Nom\tEmail\tInstal·lació\tRol\tTorns\tEnllaç d'accés\n";
foreach ($files as $f) {
    $tsv .= implode("\t", [$f['nom'], $f['email'], $f['instalacio'], $f['rol'], $f['torns'], $f['enllac'] ?? 'Ja tenia compte']) . "\n";
}
ob_start();
?>

<div class="mb-6">
    <a href="<?= url('usuaris') ?>" class="text-sm text-gray-500 hover:text-brand transition flex items-center gap-1">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Tornar a usuaris
    </a>
    <h2 class="text-xl sm:text-2xl font-bold text-gray-800 mt-2"><?= e($title) ?></h2>
    <p class="text-gray-500 text-sm mt-1">
        <?php if ($esEnllacos): ?>
            <?= count($ambEnllac) === 1 ? '1 enllaç nou' : count($ambEnllac) . ' enllaços nous' ?>. Els anteriors d'aquests usuaris ja no funcionen; les contrasenyes actuals no canvien.
        <?php else: ?>
            <?= count($ambEnllac) === 1 ? '1 usuari nou' : count($ambEnllac) . ' usuaris nous' ?> amb enllaç d'accés<?= $existents ? ' · ' . $existents . ($existents === 1 ? ' assignació' : ' assignacions') . ' a usuaris que ja tenien compte' : '' ?>.
        <?php endif; ?>
    </p>
</div>

<script type="application/json" id="usuaris-import-tsv"><?= json_encode($tsv, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?></script>

<div x-data="resultatImportUsuaris()" class="space-y-4">
    <?php if ($ambEnllac): ?>
        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
            <p class="font-medium">Guarda aquests enllaços ara: no es poden tornar a consultar després d'una altra importació o en tancar la sessió.</p>
            <p class="mt-1">
                Cada enllaç serveix una sola vegada<?= $caduca ? ' i caduca el ' . e($caduca) : '' ?>.
                Si caduca o es perd, genera'n un de nou des de la llista d'usuaris.
            </p>
        </div>
    <?php endif; ?>

    <div class="flex flex-wrap items-center gap-2">
        <a href="<?= url('usuaris/importar/resultat/excel') ?>" class="bg-brand text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-brand-dark transition">
            Descarregar Excel
        </a>
        <button type="button" @click="copiar(tsv, 'taula')" class="px-4 py-2 rounded-lg text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 transition">
            <span x-text="copiat === 'taula' ? 'Copiada!' : 'Copiar la taula'"></span>
        </button>
        <?php if (!$esEnllacos): ?>
            <a href="<?= url('usuaris/importar') ?>" class="px-4 py-2 rounded-lg text-sm font-medium text-brand hover:text-brand-dark transition">Importar-ne més</a>
        <?php endif; ?>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">
                        <th class="px-4 py-3">Usuari</th>
                        <th class="px-4 py-3">Instal·lació</th>
                        <th class="px-4 py-3">Rol</th>
                        <th class="px-4 py-3">Torns</th>
                        <th class="px-4 py-3 min-w-[260px]">Enllaç d'accés</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($files as $i => $f): ?>
                        <tr class="align-top">
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-800"><?= e($f['nom']) ?></p>
                                <p class="text-xs text-gray-400"><?= e($f['email']) ?></p>
                            </td>
                            <td class="px-4 py-3 text-gray-600"><?= e($f['instalacio']) ?></td>
                            <td class="px-4 py-3 text-gray-600"><?= e($f['rol']) ?></td>
                            <td class="px-4 py-3 text-gray-600 text-xs"><?= e($f['torns'] ?: '—') ?></td>
                            <td class="px-4 py-3">
                                <?php if (!empty($f['enllac'])): ?>
                                    <div class="flex items-center gap-2">
                                        <input type="text" readonly value="<?= e($f['enllac']) ?>" @focus="$event.target.select()"
                                               class="w-full min-w-0 font-mono text-xs border border-gray-200 bg-gray-50 rounded-lg px-2 py-1.5 text-gray-600">
                                        <button type="button" @click="copiar(<?= e(json_encode($f['enllac'])) ?>, <?= (int)$i ?>)"
                                                class="shrink-0 text-xs font-medium text-brand hover:text-brand-dark transition">
                                            <span x-text="copiat === <?= (int)$i ?> ? 'Copiat!' : 'Copiar'"></span>
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <span class="text-xs text-gray-500">Ja tenia compte: entra amb la seva contrasenya</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function resultatImportUsuaris() {
    return {
        tsv: JSON.parse(document.getElementById('usuaris-import-tsv').textContent),
        copiat: null,

        async copiar(text, clau) {
            await window.copiarText(text);
            this.copiat = clau;
            setTimeout(() => { if (this.copiat === clau) this.copiat = null; }, 2000);
        },
    };
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
