<?php
$title = 'Usuaris';
$rolsAssignables = array_values(array_filter($rols, static fn($r) => $r['nom'] !== 'superadmin'));
ob_start();
?>

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <h2 class="text-xl sm:text-2xl font-bold text-gray-800">Usuaris</h2>
        <p class="text-gray-500 text-sm mt-1">Gestió d'usuaris i permisos</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <a href="<?= url('usuaris/importar') ?>" class="px-4 py-2 rounded-lg text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 transition flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/></svg>
            Importar usuaris
        </a>
        <a href="<?= url('usuaris/create') ?>" class="bg-brand text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-brand-dark transition flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Nou Usuari
        </a>
    </div>
</div>

<?php if (!empty($flash['enllac'])): ?>
<?php $enllac = $flash['enllac']; ?>
<div x-data="{ copiat: false }" class="mb-6 rounded-xl border border-brand-200 bg-brand-light p-4">
    <p class="text-sm font-medium text-gray-800">
        Enllaç d'accés per a <?= e($enllac['nom']) ?>
        <span class="font-normal text-gray-500">(<?= e($enllac['email']) ?>)</span>
    </p>
    <div class="mt-2 flex flex-col sm:flex-row gap-2">
        <input type="text" readonly value="<?= e($enllac['url']) ?>" @focus="$event.target.select()"
               class="flex-1 min-w-0 font-mono text-xs border border-gray-200 bg-white rounded-lg px-3 py-2 text-gray-700">
        <button type="button" @click="copiarText(<?= e(json_encode($enllac['url'])) ?>).then(() => { copiat = true; setTimeout(() => copiat = false, 2000); })"
                class="bg-brand text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-brand-dark transition">
            <span x-text="copiat ? 'Copiat!' : 'Copiar enllaç'"></span>
        </button>
    </div>
    <p class="mt-2 text-xs text-gray-500">Serveix una sola vegada i caduca el <?= e($enllac['caduca']) ?>. Copia'l ara: no es podrà tornar a consultar.</p>
</div>
<?php endif; ?>

<script type="application/json" id="usuaris-panell-config"><?= json_encode($config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?></script>

<div x-data="panellUsuaris()" x-cloak>

    <!-- Comptadors: també són filtres -->
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-4 <?= $isSuperadmin ? 'lg:grid-cols-5' : 'lg:grid-cols-4' ?>">
        <template x-for="comptador in comptadors" :key="comptador.clau">
            <button type="button" @click="triarComptador(comptador)"
                    class="usuaris-comptador" :class="{ 'usuaris-comptador--actiu': comptadorActiu(comptador) }">
                <span class="block text-2xl font-bold text-gray-800" x-text="comptador.total"></span>
                <span class="block text-xs text-gray-500" x-text="comptador.etiqueta"></span>
            </button>
        </template>
    </div>

    <!-- Cerca i filtres -->
    <div class="bg-white rounded-xl border border-gray-200 p-3 sm:p-4 mb-3 flex flex-col lg:flex-row gap-3 lg:items-center">
        <div class="relative flex-1 min-w-0">
            <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/></svg>
            <input type="search" x-model="filtres.q" x-ref="cerca" @keydown.escape="filtres.q = ''"
                   placeholder="Cerca per nom, cognoms o email"
                   class="w-full border border-gray-300 rounded-lg pl-9 pr-3 py-2 text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none">
        </div>
        <?php if ($isSuperadmin): ?>
            <select x-model="filtres.inst" aria-label="Filtrar per instal·lació"
                    class="lg:w-56 border border-gray-300 rounded-lg px-2 py-2 text-sm focus:ring-2 focus:ring-brand outline-none">
                <option value="">Totes les instal·lacions</option>
                <option value="sense">Sense instal·lació</option>
                <?php foreach ($instalacions as $inst): ?>
                    <option value="<?= (int)$inst['id'] ?>"><?= e($inst['nom']) ?></option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>
        <select x-model="filtres.rol" aria-label="Filtrar per rol"
                class="lg:w-48 border border-gray-300 rounded-lg px-2 py-2 text-sm focus:ring-2 focus:ring-brand outline-none">
            <option value="">Tots els rols</option>
            <?php foreach ($rols as $rol): ?>
                <option value="<?= e($rol['nom']) ?>"><?= e($rol['etiqueta']) ?></option>
            <?php endforeach; ?>
        </select>
        <select x-model="filtres.ordre" aria-label="Ordenar"
                class="lg:w-44 border border-gray-300 rounded-lg px-2 py-2 text-sm focus:ring-2 focus:ring-brand outline-none">
            <option value="nom">Nom (A-Z)</option>
            <option value="recents">Alta més recent</option>
            <option value="email">Email</option>
        </select>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-2 mb-3 text-sm text-gray-500">
        <p>
            <span x-text="textRecompte"></span>
            <button type="button" x-show="hiHaFiltres" @click="netejarFiltres()" class="ml-2 text-brand hover:text-brand-dark">Treure filtres</button>
        </p>
        <?php if ($isSuperadmin): ?>
            <div class="flex flex-wrap items-center gap-3" x-show="filtrats.length">
                <button type="button" @click="seleccionarFiltrats()" class="text-brand hover:text-brand-dark"
                        x-text="filtrats.length === 1 ? 'Seleccionar-lo' : 'Seleccionar els ' + filtrats.length"></button>
                <button type="button" @click="exportar(filtrats.map(u => u.id))" class="text-brand hover:text-brand-dark">Exportar a Excel</button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Mòbil: targetes -->
    <div class="space-y-3 md:hidden">
        <template x-for="(u, index) in visibles" :key="u.id">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4"
                 :class="{ 'opacity-70': !u.actiu, 'usuaris-fila--seleccionada': seleccionat(u) }">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <?php if ($isSuperadmin): ?>
                            <input type="checkbox" :checked="seleccionat(u)" @click="clicSeleccio($event, index)"
                                   class="w-4 h-4 text-brand border-gray-300 rounded focus:ring-brand shrink-0" aria-label="Seleccionar usuari">
                        <?php endif; ?>
                        <div class="w-8 h-8 bg-brand/10 rounded-full flex items-center justify-center text-brand text-sm font-bold flex-shrink-0" x-text="inicial(u)"></div>
                        <div class="min-w-0">
                            <p class="font-medium text-gray-800 truncate" x-text="u.nomComplet"></p>
                            <p class="text-xs text-gray-400 truncate" x-text="u.email"></p>
                        </div>
                    </div>
                    <div class="flex flex-col items-end gap-1" x-html="badgesEstat(u)"></div>
                </div>
                <div class="mt-3 flex flex-col gap-1.5" x-html="assignacionsHtml(u)"></div>
                <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-2 mt-3 pt-3 border-t border-gray-100">
                    <span class="text-xs text-gray-400 whitespace-nowrap" x-text="'Alta ' + dataCurta(u.creat)"></span>
                    <div class="flex items-center gap-3 ml-auto">
                        <template x-if="potEnllac(u)">
                            <form method="POST" :action="cfg.urls.enllac + u.id" @submit="confirmarEnllac($event)">
                                <input type="hidden" name="_token" :value="cfg.csrf">
                                <button type="submit" class="text-sm text-gray-600 hover:text-brand transition whitespace-nowrap">Enllaç d'accés</button>
                            </form>
                        </template>
                        <a :href="cfg.urls.edit + u.id" class="text-sm text-brand hover:text-brand-dark transition">Editar</a>
                        <template x-if="potCanviarEstat(u)">
                            <form method="POST" :action="cfg.urls.toggle + u.id" @submit="confirmarEstat($event, u)">
                                <input type="hidden" name="_token" :value="cfg.csrf">
                                <button type="submit" class="text-sm transition" :class="u.actiu ? 'text-red-600 hover:text-red-700' : 'text-green-600 hover:text-green-700'" x-text="u.actiu ? 'Desactivar' : 'Activar'"></button>
                            </form>
                        </template>
                    </div>
                </div>
            </div>
        </template>
        <div x-show="!filtrats.length" class="bg-white rounded-xl border border-gray-200 px-4 py-8 text-center text-gray-400">Cap usuari coincideix amb els filtres.</div>
    </div>

    <!-- Escriptori: taula -->
    <div class="hidden md:block bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <?php if ($isSuperadmin): ?>
                            <th class="w-10 pl-4 py-3 text-left">
                                <input type="checkbox" :checked="totsVisiblesSeleccionats" @change="seleccionarVisibles($event.target.checked)"
                                       class="w-4 h-4 text-brand border-gray-300 rounded focus:ring-brand" aria-label="Seleccionar els usuaris de la pàgina">
                            </th>
                        <?php endif; ?>
                        <th class="text-left px-4 py-3 font-medium text-gray-600">Usuari</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-600">Instal·lacions, rols i torns</th>
                        <th class="text-center px-4 py-3 font-medium text-gray-600">Estat</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-600">Alta</th>
                        <th class="text-right px-4 py-3 font-medium text-gray-600">Accions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <template x-for="(u, index) in visibles" :key="u.id">
                        <tr class="hover:bg-gray-50 transition align-top" :class="{ 'opacity-60': !u.actiu, 'usuaris-fila--seleccionada': seleccionat(u) }">
                            <?php if ($isSuperadmin): ?>
                                <td class="pl-4 py-3">
                                    <input type="checkbox" :checked="seleccionat(u)" @click="clicSeleccio($event, index)"
                                           class="w-4 h-4 text-brand border-gray-300 rounded focus:ring-brand" aria-label="Seleccionar usuari">
                                </td>
                            <?php endif; ?>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-brand/10 rounded-full flex items-center justify-center text-brand text-sm font-bold flex-shrink-0" x-text="inicial(u)"></div>
                                    <div class="min-w-0">
                                        <p class="font-medium text-gray-800" x-text="u.nomComplet"></p>
                                        <p class="text-xs text-gray-400" x-text="u.email"></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-col gap-1.5" x-html="assignacionsHtml(u)"></div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-col items-center gap-1" x-html="badgesEstat(u)"></div>
                            </td>
                            <td class="px-4 py-3 text-gray-400 text-xs whitespace-nowrap" x-text="dataCurta(u.creat)"></td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <template x-if="potEnllac(u)">
                                        <form method="POST" :action="cfg.urls.enllac + u.id" class="inline" @submit="confirmarEnllac($event)">
                                            <input type="hidden" name="_token" :value="cfg.csrf">
                                            <button type="submit" class="text-gray-400 hover:text-brand transition" title="Generar enllaç d'accés">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                            </button>
                                        </form>
                                    </template>
                                    <a :href="cfg.urls.edit + u.id" class="text-gray-400 hover:text-brand transition" title="Editar">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </a>
                                    <template x-if="potCanviarEstat(u)">
                                        <form method="POST" :action="cfg.urls.toggle + u.id" class="inline" @submit="confirmarEstat($event, u)">
                                            <input type="hidden" name="_token" :value="cfg.csrf">
                                            <button type="submit" class="text-gray-400 transition" :class="u.actiu ? 'hover:text-red-500' : 'hover:text-green-500'" :title="u.actiu ? 'Desactivar' : 'Activar'">
                                                <svg x-show="u.actiu" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                                <svg x-show="!u.actiu" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            </button>
                                        </form>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="!filtrats.length">
                        <td colspan="6" class="px-4 py-8 text-center text-gray-400">Cap usuari coincideix amb els filtres.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div x-show="filtrats.length > limit" class="mt-4 text-center">
        <button type="button" @click="limit += 100" class="px-4 py-2 rounded-lg text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 transition"
                x-text="'Mostrar-ne més (' + (filtrats.length - limit) + ' pendents)'"></button>
    </div>

    <?php if ($isSuperadmin): ?>
        <!-- Barra d'accions en bloc -->
        <div x-show="nSeleccionats" class="fixed bottom-0 left-0 right-0 lg:left-64 z-40 bg-white border-t border-gray-200 shadow-lg px-4 py-3">
            <div class="max-w-6xl mx-auto flex flex-col xl:flex-row xl:items-center gap-2 xl:gap-4">
                <p class="text-sm text-gray-600 shrink-0">
                    <strong class="text-brand" x-text="nSeleccionats"></strong>
                    <span x-text="nSeleccionats === 1 ? 'usuari seleccionat' : 'usuaris seleccionats'"></span>
                    <span x-show="seleccionatsOcults" class="text-xs text-gray-400" x-text="'(' + seleccionatsOcults + ' fora dels filtres)'"></span>
                    <button type="button" @click="netejarSeleccio()" class="ml-2 text-xs text-gray-500 underline hover:text-gray-700">Treure la selecció</button>
                </p>
                <div class="flex gap-2 overflow-x-auto pb-1 -mx-4 px-4 sm:mx-0 sm:px-0 sm:pb-0 sm:flex-wrap sm:overflow-visible xl:ml-auto">
                    <button type="button" class="usuaris-accio" @click="executar('activar')">Activar</button>
                    <button type="button" class="usuaris-accio" @click="executar('desactivar')">Desactivar</button>
                    <button type="button" class="usuaris-accio" @click="obrirModal('assignar')">Assignar a instal·lació…</button>
                    <button type="button" class="usuaris-accio" @click="obrirModal('treure')">Treure d'instal·lació…</button>
                    <button type="button" class="usuaris-accio" x-show="cfg.potGenerarEnllac" @click="executar('enllacos')">Enllaços d'accés</button>
                    <button type="button" class="usuaris-accio" @click="exportar(idsSeleccionats)">Exportar</button>
                </div>
            </div>
        </div>
        <div x-show="nSeleccionats" class="h-24 sm:h-36 xl:h-20"></div>

        <!-- Modal: assignar o treure d'una instal·lació -->
        <div x-show="modal" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-gray-900/40 p-4"
             @keydown.escape.window="modal = null" @click.self="modal = null">
            <div class="w-full max-w-lg bg-white rounded-2xl shadow-xl p-5" role="dialog" aria-modal="true">
                <h3 class="text-lg font-semibold text-gray-800" x-text="modal === 'assignar' ? 'Assignar a una instal·lació' : 'Treure d\'una instal·lació'"></h3>
                <p class="text-sm text-gray-500 mt-1" x-text="nSeleccionats === 1 ? '1 usuari seleccionat' : nSeleccionats + ' usuaris seleccionats'"></p>

                <label class="block text-sm font-medium text-gray-700 mt-4 mb-1">Instal·lació</label>
                <select x-model="massiu.instalacio_id" @change="massiu.torn_ids = []"
                        class="w-full border border-gray-300 rounded-lg px-2 py-2 text-sm focus:ring-2 focus:ring-brand outline-none">
                    <option value="">— Tria —</option>
                    <?php foreach ($instalacions as $inst): ?>
                        <option value="<?= (int)$inst['id'] ?>"><?= e($inst['nom']) ?></option>
                    <?php endforeach; ?>
                </select>

                <div x-show="modal === 'assignar'">
                    <p class="text-sm font-medium text-gray-700 mt-4 mb-2">Rol</p>
                    <div class="flex flex-wrap gap-1.5">
                        <?php foreach ($rolsAssignables as $rol): ?>
                            <button type="button" class="usuaris-chip" :class="{ 'usuaris-chip--actiu': massiu.rol_id === '<?= (int)$rol['id'] ?>' }"
                                    @click="massiu.rol_id = '<?= (int)$rol['id'] ?>'"><?= e($rol['etiqueta']) ?></button>
                        <?php endforeach; ?>
                    </div>

                    <p class="text-sm font-medium text-gray-700 mt-4 mb-2">Torns</p>
                    <p x-show="!massiu.instalacio_id" class="text-xs text-gray-400">Tria primer la instal·lació.</p>
                    <p x-show="massiu.instalacio_id && !tornsModal.length" class="text-xs text-gray-400">Aquesta instal·lació no té torns.</p>
                    <div x-show="tornsModal.length" class="flex flex-wrap gap-1.5">
                        <button type="button" class="usuaris-chip" @click="massiu.torn_ids = tornsModal.map(t => t.id)">Tots</button>
                        <button type="button" class="usuaris-chip" @click="massiu.torn_ids = []">Cap</button>
                        <template x-for="torn in tornsModal" :key="torn.id">
                            <button type="button" class="usuaris-chip" :class="{ 'usuaris-chip--actiu': massiu.torn_ids.includes(torn.id) }"
                                    @click="alternarTornModal(torn.id)" x-text="torn.nom"></button>
                        </template>
                    </div>
                    <p class="text-xs text-gray-500 mt-4">Els que ja hi són conserven el compte, però se'ls canvia el rol i els torns en aquesta instal·lació. Els superadmins s'ometen.</p>
                </div>

                <p x-show="modal === 'treure' && massiu.instalacio_id" class="text-sm text-gray-600 mt-4" x-text="textTreure"></p>

                <div class="flex justify-end gap-2 mt-6">
                    <button type="button" @click="modal = null" class="px-4 py-2 rounded-lg text-sm text-gray-600 hover:bg-gray-100 transition">Cancel·lar</button>
                    <button type="button" @click="confirmarModal()" :disabled="!modalValid"
                            class="px-4 py-2 rounded-lg text-sm font-medium text-white transition disabled:opacity-50 disabled:cursor-not-allowed"
                            :class="modal === 'treure' ? 'bg-red-600 hover:bg-red-700' : 'bg-brand hover:bg-brand-dark'"
                            x-text="modal === 'treure' ? 'Treure' : 'Assignar'"></button>
                </div>
            </div>
        </div>

        <form method="POST" action="<?= url('usuaris/massiu') ?>" x-ref="formMassiu" class="hidden">
            <?= csrf_field() ?>
            <input type="hidden" name="accio" :value="massiu.accio">
            <input type="hidden" name="ids" :value="massiu.ids">
            <input type="hidden" name="instalacio_id" :value="massiu.instalacio_id">
            <input type="hidden" name="rol_id" :value="massiu.rol_id">
            <input type="hidden" name="torn_ids" :value="massiu.torn_ids.join(',')">
        </form>
        <form method="POST" action="<?= url('usuaris/exportar') ?>" x-ref="formExportar" class="hidden">
            <?= csrf_field() ?>
            <input type="hidden" name="ids" :value="exportIds">
        </form>
    <?php endif; ?>
</div>

<script>
function panellUsuaris() {
    const cfg = JSON.parse(document.getElementById('usuaris-panell-config').textContent);
    const CLAU_FILTRES = 'gmao-usuaris-filtres';
    const normalitzar = (text) => (text || '').toString().normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
    const escapar = (text) => (text || '').toString().replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const filtresBuits = () => ({ q: '', inst: '', rol: '', estat: '', ordre: 'nom' });

    // Dades estàtiques fora de l'estat reactiu: es recalcula només el resultat dels filtres.
    const usuaris = cfg.usuaris;
    usuaris.forEach(u => {
        u.nomComplet = `${u.nom} ${u.cognoms}`.trim();
        u.cerca = normalitzar(`${u.nom} ${u.cognoms} ${u.email}`);
    });
    const perId = new Map(usuaris.map(u => [u.id, u]));

    return {
        cfg: { superadmin: cfg.superadmin, urls: cfg.urls, csrf: cfg.csrf, potGenerarEnllac: cfg.potGenerarEnllac },
        filtres: { ...filtresBuits(), inst: cfg.instalacioSidebar ? String(cfg.instalacioSidebar) : '' },
        filtrats: [],
        comptadors: [],
        limit: 100,
        seleccio: {},
        ultimIndex: null,
        modal: null,
        massiu: { accio: '', ids: '', instalacio_id: '', rol_id: '', torn_ids: [] },
        exportIds: '',

        init() {
            try {
                const desat = JSON.parse(sessionStorage.getItem(CLAU_FILTRES) || 'null');
                // Si has canviat d'instal·lació al menú lateral, manen els filtres per defecte.
                if (desat && desat.sidebar === cfg.instalacioSidebar) Object.assign(this.filtres, desat.filtres);
            } catch (e) {}

            this.recalcular();
            this.$watch('filtres', () => {
                this.limit = 100;
                this.ultimIndex = null;
                this.recalcular();
                try {
                    sessionStorage.setItem(CLAU_FILTRES, JSON.stringify({ sidebar: cfg.instalacioSidebar, filtres: this.filtres }));
                } catch (e) {}
            });

            window.addEventListener('keydown', (event) => {
                const escrivint = ['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName);
                if (event.key === '/' && !escrivint) {
                    event.preventDefault();
                    this.$refs.cerca.focus();
                }
            });
        },

        // --- Filtres ------------------------------------------------------------
        passaCerca(u) {
            const paraules = normalitzar(this.filtres.q).split(/\s+/).filter(Boolean);
            return paraules.every(p => u.cerca.includes(p));
        },
        passaInstalacio(u) {
            const inst = this.filtres.inst;
            if (!inst) return true;
            if (inst === 'sense') return !u.superadmin && !u.assignacions.length;
            return u.assignacions.some(a => String(a.instalacio_id) === inst);
        },
        passaRol(u) {
            const rol = this.filtres.rol;
            if (!rol) return true;
            if (rol === 'superadmin') return u.superadmin;
            const inst = this.filtres.inst;
            return u.assignacions.some(a => a.rol === rol && (!inst || inst === 'sense' || String(a.instalacio_id) === inst));
        },
        passaEstat(u, estat) {
            if (estat === 'actius') return u.actiu;
            if (estat === 'inactius') return !u.actiu;
            if (estat === 'pendents') return u.actiu && !!u.activacio;
            return true;
        },
        recalcular() {
            const base = usuaris.filter(u => this.passaCerca(u) && this.passaInstalacio(u) && this.passaRol(u));
            const ordre = this.filtres.ordre;
            const perNom = (a, b) => a.nomComplet.localeCompare(b.nomComplet, 'ca', { sensitivity: 'base' });

            this.filtrats = base
                .filter(u => this.passaEstat(u, this.filtres.estat))
                .sort((a, b) => ordre === 'recents' ? (b.creat.localeCompare(a.creat) || perNom(a, b))
                    : ordre === 'email' ? a.email.localeCompare(b.email)
                    : perNom(a, b));

            this.comptadors = [
                { clau: 'tots', etiqueta: 'Total usuaris', total: base.length },
                { clau: 'actius', etiqueta: 'Actius', total: base.filter(u => u.actiu).length },
                { clau: 'inactius', etiqueta: 'Inactius', total: base.filter(u => !u.actiu).length },
                { clau: 'pendents', etiqueta: "Pendents d'activar", total: base.filter(u => this.passaEstat(u, 'pendents')).length },
            ];
            if (cfg.superadmin) {
                this.comptadors.push({
                    clau: 'sense',
                    etiqueta: 'Sense instal·lació',
                    total: usuaris.filter(u => this.passaCerca(u) && !u.superadmin && !u.assignacions.length).length,
                });
            }
        },
        get visibles() {
            return this.filtrats.slice(0, this.limit);
        },
        get hiHaFiltres() {
            const f = this.filtres;
            return !!(f.q || f.inst || f.rol || f.estat);
        },
        get textRecompte() {
            const n = this.filtrats.length;
            if (!n) return 'Cap usuari';
            const text = n === usuaris.length ? `${n} usuaris` : `${n} de ${usuaris.length} usuaris`;
            return n > this.limit ? `${text} · es mostren els primers ${this.limit}` : text;
        },
        triarComptador(comptador) {
            if (comptador.clau === 'sense') {
                this.filtres.inst = this.filtres.inst === 'sense' ? '' : 'sense';
                this.filtres.rol = '';
                return;
            }
            this.filtres.estat = comptador.clau === 'tots' ? '' : comptador.clau;
        },
        comptadorActiu(comptador) {
            if (comptador.clau === 'sense') return this.filtres.inst === 'sense';
            return (this.filtres.estat || 'tots') === comptador.clau;
        },
        netejarFiltres() {
            Object.assign(this.filtres, filtresBuits());
        },

        // --- Presentació ----------------------------------------------------------
        inicial(u) {
            return (u.nom || u.email || '?').charAt(0).toUpperCase();
        },
        dataCurta(iso) {
            if (!iso) return '—';
            const [any, mes, dia] = iso.split('-');
            return `${dia}/${mes}/${any}`;
        },
        assignacionsHtml(u) {
            if (u.superadmin) {
                return '<span class="usuaris-assignacio"><span class="usuaris-assignacio__rol usuaris-rol--superadmin">Superadmin · totes les instal·lacions</span></span>';
            }
            if (!u.assignacions.length) {
                return '<span class="text-xs italic text-gray-400">Sense instal·lació</span>';
            }
            return u.assignacions.map(a => `
                <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5">
                    <span class="usuaris-assignacio">
                        <span class="usuaris-assignacio__instalacio">${escapar(a.instalacio)}</span>
                        <span class="usuaris-assignacio__rol usuaris-rol--${escapar(a.rol)}">${escapar(a.rol_etiqueta)}</span>
                    </span>
                    ${a.torns.length ? `<span class="text-xs text-gray-400">${escapar(a.torns.join(', '))}</span>` : ''}
                </div>`).join('');
        },
        badgesEstat(u) {
            const badges = [u.actiu
                ? '<span class="inline-flex items-center gap-1 text-xs text-green-700 bg-green-50 px-2 py-0.5 rounded-full whitespace-nowrap"><span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span> Actiu</span>'
                : '<span class="inline-flex items-center gap-1 text-xs text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full whitespace-nowrap"><span class="w-1.5 h-1.5 bg-gray-400 rounded-full"></span> Inactiu</span>'];
            if (u.activacio === 'pendent') {
                badges.push(`<span class="text-xs text-amber-700 bg-amber-50 px-2 py-0.5 rounded-full whitespace-nowrap">Pendent d'activar</span>`);
            } else if (u.activacio === 'caducat') {
                badges.push('<span class="text-xs text-red-700 bg-red-50 px-2 py-0.5 rounded-full whitespace-nowrap">Enllaç caducat</span>');
            }
            if (u.bloquejat) {
                badges.push('<span class="text-xs text-gray-600 bg-gray-100 px-2 py-0.5 rounded-full whitespace-nowrap" title="També treballa en altres instal·lacions: només en pots canviar el rol i els torns">Compartit</span>');
            }
            return badges.join('');
        },
        potEnllac(u) {
            return this.cfg.potGenerarEnllac && u.actiu && !u.bloquejat && (!u.superadmin || this.cfg.superadmin);
        },
        potCanviarEstat(u) {
            return !u.propi && !u.bloquejat;
        },
        confirmarEnllac(event) {
            if (!confirm('Generar un enllaç d\'accés nou? Els enllaços anteriors d\'aquest usuari deixaran de funcionar.')) event.preventDefault();
        },
        confirmarEstat(event, u) {
            if (!confirm(`${u.actiu ? 'Desactivar' : 'Activar'} aquest usuari?`)) event.preventDefault();
        },

        // --- Selecció ---------------------------------------------------------------
        seleccionat(u) {
            return !!this.seleccio[u.id];
        },
        get idsSeleccionats() {
            return Object.keys(this.seleccio).map(Number);
        },
        get nSeleccionats() {
            return Object.keys(this.seleccio).length;
        },
        get seleccionatsOcults() {
            const visibles = new Set(this.filtrats.map(u => u.id));
            return this.idsSeleccionats.filter(id => !visibles.has(id)).length;
        },
        get totsVisiblesSeleccionats() {
            return this.visibles.length > 0 && this.visibles.every(u => this.seleccio[u.id]);
        },
        commutar(u, marcat) {
            if (marcat) {
                this.seleccio[u.id] = true;
            } else {
                delete this.seleccio[u.id];
            }
        },
        clicSeleccio(event, index) {
            const marcat = event.target.checked;
            if (event.shiftKey && this.ultimIndex !== null) {
                const inici = Math.min(index, this.ultimIndex);
                const fi = Math.max(index, this.ultimIndex);
                this.filtrats.slice(inici, fi + 1).forEach(u => this.commutar(u, marcat));
            } else {
                this.commutar(this.filtrats[index], marcat);
            }
            this.ultimIndex = index;
        },
        seleccionarVisibles(marcat) {
            this.visibles.forEach(u => this.commutar(u, marcat));
        },
        seleccionarFiltrats() {
            this.filtrats.forEach(u => this.commutar(u, true));
        },
        netejarSeleccio() {
            this.seleccio = {};
            this.ultimIndex = null;
        },

        // --- Accions en bloc ---------------------------------------------------------
        executar(accio) {
            const n = this.nSeleccionats;
            const usuarisText = n === 1 ? '1 usuari' : `${n} usuaris`;
            const preguntes = {
                activar: `Activar ${usuarisText}?`,
                desactivar: `Desactivar ${usuarisText}? No podran entrar fins que els tornis a activar.`,
                enllacos: `Generar un enllaç d'accés nou per a ${usuarisText}? Els enllaços anteriors deixaran de funcionar; les contrasenyes actuals no canvien.`,
            };
            if (preguntes[accio] && !confirm(preguntes[accio])) return;
            this.enviarMassiu(accio);
        },
        obrirModal(tipus) {
            const inst = this.filtres.inst && this.filtres.inst !== 'sense' ? this.filtres.inst : '';
            this.massiu.instalacio_id = inst || (cfg.instalacioSidebar ? String(cfg.instalacioSidebar) : '');
            this.massiu.rol_id = cfg.rolPerDefecte ? String(cfg.rolPerDefecte) : '';
            this.massiu.torn_ids = [];
            this.modal = tipus;
        },
        get tornsModal() {
            return cfg.torns[this.massiu.instalacio_id] || [];
        },
        alternarTornModal(tornId) {
            this.massiu.torn_ids = this.massiu.torn_ids.includes(tornId)
                ? this.massiu.torn_ids.filter(id => id !== tornId)
                : [...this.massiu.torn_ids, tornId];
        },
        get modalValid() {
            return !!this.massiu.instalacio_id && (this.modal !== 'assignar' || !!this.massiu.rol_id);
        },
        get textTreure() {
            const inst = Number(this.massiu.instalacio_id);
            const hiSon = this.idsSeleccionats.filter(id => (perId.get(id)?.assignacions || []).some(a => a.instalacio_id === inst)).length;
            if (!hiSon) return 'Cap dels usuaris seleccionats és en aquesta instal·lació.';
            return `${hiSon === 1 ? '1 dels seleccionats hi és i en perdrà' : hiSon + ' dels seleccionats hi són i en perdran'} l'accés i els torns. El compte no s'esborra.`;
        },
        confirmarModal() {
            if (!this.modalValid) return;
            this.enviarMassiu(this.modal);
        },
        enviarMassiu(accio) {
            this.massiu.accio = accio;
            this.massiu.ids = this.idsSeleccionats.join(',');
            this.$nextTick(() => this.$refs.formMassiu.submit());
        },
        exportar(ids) {
            this.exportIds = ids.join(',');
            this.$nextTick(() => this.$refs.formExportar.submit());
        },
    };
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
