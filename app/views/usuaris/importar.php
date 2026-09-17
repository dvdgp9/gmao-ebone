<?php
$title = 'Importar usuaris';
$config['tokensDisponibles'] = $tokensDisponibles;
$instalacioFixa = !$potTriarInstalacio ? ($config['instalacions'][0]['nom'] ?? '') : '';
ob_start();
?>

<div class="mb-6">
    <a href="<?= url('usuaris') ?>" class="text-sm text-gray-500 hover:text-brand transition flex items-center gap-1">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Tornar a usuaris
    </a>
    <h2 class="text-xl sm:text-2xl font-bold text-gray-800 mt-2">Importar usuaris</h2>
    <p class="text-gray-500 text-sm mt-1">
        Enganxa una llista en qualsevol format o puja un fitxer, ajusta-ho amb clics i crea els comptes.
        Cada persona nova rep un enllaç per triar la seva contrasenya: ningú n'ha d'inventar cap.
    </p>
</div>

<?php if (!$tokensDisponibles): ?>
    <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
        Falta executar la migració <code class="font-mono">database/migrations/2026-09-17_usuari_tokens.sql</code> al servidor.
        Pots provar el panell, però no es podran crear usuaris fins llavors.
    </div>
<?php endif; ?>

<script type="application/json" id="usuaris-import-config"><?= json_encode($config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?></script>

<div x-data="importUsuaris()" x-cloak class="space-y-4">

    <!-- 1. Dades d'entrada -->
    <section class="usuaris-import-drop bg-white rounded-xl shadow-sm border border-gray-200 p-4 sm:p-5"
             :class="{ 'usuaris-import-drop--actiu': arrossegant }"
             @dragover.prevent="arrossegant = true"
             @dragleave.prevent="arrossegant = false"
             @drop.prevent="deixarFitxer($event)">
        <div class="flex flex-wrap items-baseline justify-between gap-2 mb-3">
            <h3 class="font-semibold text-gray-800">1. Enganxa les dades</h3>
            <span class="text-xs text-gray-400">o arrossega aquí un Excel o CSV</span>
        </div>

        <textarea x-model="text"
                  @paste="enganxat()"
                  @keydown.meta.enter.prevent="analitzar()"
                  @keydown.ctrl.enter.prevent="analitzar()"
                  rows="6"
                  spellcheck="false"
                  class="w-full font-mono text-sm border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand focus:border-brand outline-none"
                  placeholder="Raul Duran.    raulduran@ebone.es&#10;David Pino.     davidpino@ebone.es&#10;&#10;…o columnes copiades d'Excel (Nom, Cognoms, Correu, Puesto, Torns, Instal·lació)"></textarea>

        <div class="mt-3 flex flex-wrap items-center gap-2">
            <button type="button" @click="analitzar()" :disabled="analitzant || !text.trim()"
                    class="bg-brand text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-brand-dark transition disabled:opacity-50 disabled:cursor-not-allowed">
                <span x-text="analitzant ? 'Analitzant…' : 'Analitzar'"></span>
            </button>
            <label class="px-4 py-2 rounded-lg text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 transition cursor-pointer">
                Puja un fitxer
                <input type="file" class="hidden" accept=".xlsx,.xls,.ods,.csv,.txt" @change="triarFitxer($event)">
            </label>
            <button type="button" @click="afegirFila()" class="px-3 py-2 text-sm text-brand hover:text-brand-dark transition font-medium">
                + Afegir una persona a mà
            </button>
        </div>

        <details class="mt-3 text-xs text-gray-500">
            <summary class="cursor-pointer select-none">Quins formats entén?</summary>
            <ul class="mt-2 list-disc pl-5 space-y-1">
                <li>Una persona per línia, amb el nom i l'email com vingui: <code class="font-mono">Raul Duran. raulduran@ebone.es</code></li>
                <li>Columnes copiades d'Excel, amb capçalera o sense (nom, cognoms, correu, puesto, torns, instal·lació; en català o castellà).</li>
                <li>Adreces copiades d'Outlook: <code class="font-mono">Raul Duran &lt;raulduran@ebone.es&gt;; David Pino &lt;davidpino@ebone.es&gt;</code></li>
                <li>Fitxers .xlsx, .xls, .ods o .csv. Si l'Excel té un full «Usuaris», es fa servir aquest.</li>
                <li>El «puesto» es tradueix a rol (tècnic, jefe de mantenimiento, dirección…) i «todos» / «tots» vol dir tots els torns.</li>
            </ul>
        </details>

        <ul x-show="avisosAnalisi.length" class="mt-3 space-y-1 text-xs text-amber-800 bg-amber-50 border border-amber-200 rounded-lg p-3">
            <template x-for="avis in avisosAnalisi">
                <li x-text="avis"></li>
            </template>
        </ul>
        <p x-show="errorAnalisi" class="mt-3 text-sm text-red-700 bg-red-50 border border-red-200 rounded-lg p-3" x-text="errorAnalisi"></p>
    </section>

    <!-- 2. Ajustos ràpids -->
    <section x-show="files.length" class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 sm:p-5">
        <div class="flex flex-wrap items-baseline justify-between gap-2 mb-4">
            <h3 class="font-semibold text-gray-800">2. Ajustos ràpids</h3>
            <p class="text-xs text-gray-500">
                S'apliquen a
                <strong class="text-gray-700" x-text="seleccionades.length ? (seleccionades.length === 1 ? '1 fila seleccionada' : seleccionades.length + ' files seleccionades') : 'totes les files (' + files.length + ')'"></strong>
                <button type="button" x-show="seleccionades.length" @click="seleccionarTotes(false)" class="ml-2 text-brand hover:text-brand-dark">Treure la selecció</button>
            </p>
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Instal·lació</p>
                <?php if ($potTriarInstalacio): ?>
                    <select @change="aplicarInstalacio($event.target.value); $event.target.value = ''"
                            class="w-full border border-gray-300 rounded-lg px-2 py-2 text-sm focus:ring-2 focus:ring-brand outline-none">
                        <option value="">Canvia la instal·lació…</option>
                        <template x-for="inst in cfg.instalacions" :key="inst.id">
                            <option :value="inst.id" x-text="inst.nom"></option>
                        </template>
                    </select>
                <?php else: ?>
                    <p class="text-sm text-gray-700"><?= e($instalacioFixa) ?></p>
                <?php endif; ?>
            </div>

            <div>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Rol</p>
                <div class="flex flex-wrap gap-1.5">
                    <template x-for="rol in cfg.rols" :key="rol.id">
                        <button type="button" class="usuaris-chip" :class="estatChip(objectiu.filter(f => f.rol_id === rol.id).length, objectiu.length)"
                                @click="aplicarRol(rol.id)" x-text="rol.etiqueta"></button>
                    </template>
                </div>
            </div>

            <div>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Torns</p>
                <div class="flex flex-wrap gap-1.5">
                    <button type="button" class="usuaris-chip" @click="aplicarTotsTorns()">Tots els torns</button>
                    <button type="button" class="usuaris-chip" @click="treureTorns()">Cap torn</button>
                    <template x-for="torn in tornsDe(instalacioObjectiu)" :key="torn.id">
                        <button type="button" class="usuaris-chip"
                                :class="estatChip(objectiu.filter(f => f.torn_ids.includes(torn.id)).length, objectiu.length)"
                                @click="alternarTornObjectiu(torn.id)" x-text="torn.nom"></button>
                    </template>
                </div>
                <p x-show="!instalacioObjectiu" class="text-xs text-gray-400 mt-2">Per triar torns concrets, primer posa les files a la mateixa instal·lació.</p>
            </div>
        </div>

        <div x-show="seleccionades.length" class="mt-4 pt-3 border-t border-gray-100 flex justify-end">
            <button type="button" @click="eliminarSeleccionades()" class="text-sm text-red-600 hover:text-red-700">Eliminar les files seleccionades</button>
        </div>
    </section>

    <!-- 3. Revisió fila a fila -->
    <section x-show="files.length" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-4 sm:px-5 pt-4 flex flex-wrap items-baseline justify-between gap-2">
            <h3 class="font-semibold text-gray-800">3. Revisa i corregeix</h3>
            <span class="text-xs text-gray-400">Maj + clic per seleccionar un rang de files</span>
        </div>
        <div class="overflow-x-auto mt-3">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-y border-gray-200 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">
                        <th class="px-3 py-2 w-8">
                            <input type="checkbox" :checked="totesSeleccionades" @change="seleccionarTotes($event.target.checked)"
                                   class="w-4 h-4 text-brand border-gray-300 rounded focus:ring-brand" aria-label="Seleccionar totes">
                        </th>
                        <th class="px-2 py-2 min-w-[100px]">Estat</th>
                        <th class="px-2 py-2 min-w-[100px]">Nom</th>
                        <th class="px-2 py-2 min-w-[130px]">Cognoms</th>
                        <th class="px-2 py-2 min-w-[190px]">Email</th>
                        <th class="px-2 py-2 min-w-[160px]">Instal·lació</th>
                        <th class="px-2 py-2 min-w-[140px]">Rol</th>
                        <th class="px-2 py-2 min-w-[170px]">Torns</th>
                        <th class="px-2 py-2 w-8"></th>
                    </tr>
                </thead>
                <!-- Un tbody per persona: la fila editable i, a sota, els seus errors i avisos a tot l'ample. -->
                <template x-for="(fila, index) in files" :key="fila.key">
                    <tbody class="border-b border-gray-100" :class="{ 'usuaris-import-fila--seleccionada': fila.sel }">
                        <tr class="align-top">
                            <td class="px-3 py-3">
                                <input type="checkbox" :checked="fila.sel" @click="clicSeleccio($event, index)"
                                       class="w-4 h-4 text-brand border-gray-300 rounded focus:ring-brand" aria-label="Seleccionar fila">
                            </td>
                            <td class="px-2 py-3">
                                <span class="import-action-pill" :class="classeEstat(fila)" x-text="etiquetaEstat(fila)"></span>
                            </td>
                            <td class="px-2 py-2">
                                <input type="text" x-model="fila.nom" @input="programarValidacio()" data-camp-nom maxlength="100"
                                       class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none">
                            </td>
                            <td class="px-2 py-2">
                                <input type="text" x-model="fila.cognoms" @input="programarValidacio()" maxlength="200"
                                       class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none">
                            </td>
                            <td class="px-2 py-2">
                                <input type="email" x-model="fila.email" @input="programarValidacio()" maxlength="255"
                                       class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none">
                            </td>
                            <td class="px-2 py-2">
                                <?php if ($potTriarInstalacio): ?>
                                    <select @change="canviarInstalacio(fila, $event.target.value)"
                                            class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:ring-2 focus:ring-brand outline-none">
                                        <option value="" :selected="!fila.instalacio_id">— Tria —</option>
                                        <template x-for="inst in cfg.instalacions" :key="inst.id">
                                            <option :value="inst.id" :selected="inst.id === fila.instalacio_id" x-text="inst.nom"></option>
                                        </template>
                                    </select>
                                <?php else: ?>
                                    <span class="block py-1.5 text-gray-700" x-text="nomInstalacio(fila.instalacio_id)"></span>
                                <?php endif; ?>
                            </td>
                            <td class="px-2 py-2">
                                <select @change="canviarRol(fila, $event.target.value)"
                                        class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:ring-2 focus:ring-brand outline-none">
                                    <option value="" :selected="!fila.rol_id">— Tria —</option>
                                    <template x-for="rol in cfg.rols" :key="rol.id">
                                        <option :value="rol.id" :selected="rol.id === fila.rol_id" x-text="rol.etiqueta"></option>
                                    </template>
                                </select>
                            </td>
                            <td class="px-2 py-2">
                                <div class="flex flex-wrap gap-1 py-1">
                                    <template x-for="torn in tornsDe(fila.instalacio_id)" :key="torn.id">
                                        <button type="button" class="usuaris-chip usuaris-chip--petit"
                                                :class="{ 'usuaris-chip--actiu': fila.torn_ids.includes(torn.id) }"
                                                @click="alternarTornFila(fila, torn.id)" x-text="torn.nom"></button>
                                    </template>
                                    <span x-show="fila.instalacio_id && !tornsDe(fila.instalacio_id).length" class="text-xs text-gray-400">La instal·lació no té torns</span>
                                </div>
                            </td>
                            <td class="px-2 py-3">
                                <button type="button" @click="eliminarFila(fila)" class="text-gray-400 hover:text-red-600 transition" title="Eliminar fila" aria-label="Eliminar fila">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </td>
                        </tr>
                        <tr x-show="missatgesFila(fila).length">
                            <td></td>
                            <td colspan="8" class="px-2 pb-3">
                                <ul class="space-y-0.5 text-xs">
                                    <template x-for="missatge in missatgesFila(fila)">
                                        <li :class="missatge.error ? 'text-red-700' : 'text-amber-700'" x-text="missatge.text"></li>
                                    </template>
                                </ul>
                            </td>
                        </tr>
                    </tbody>
                </template>
            </table>
        </div>
        <div class="p-3 border-t border-gray-100">
            <button type="button" @click="afegirFila()" class="text-sm text-brand hover:text-brand-dark transition font-medium">+ Afegir una persona</button>
        </div>
    </section>

    <!-- Barra fixa de confirmació -->
    <div x-show="files.length" class="fixed bottom-0 left-0 right-0 lg:left-64 z-40 bg-white border-t border-gray-200 shadow-lg px-4 py-3">
        <div class="max-w-6xl mx-auto flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4">
            <div class="text-sm text-gray-600 flex flex-wrap items-center gap-x-4 gap-y-1">
                <span><strong class="text-gray-800" x-text="resum.usuaris_nous"></strong> usuaris nous</span>
                <span x-show="resum.existents"><strong class="text-gray-800" x-text="resum.existents"></strong> ja tenien compte</span>
                <span :class="resum.errors ? 'text-red-700' : ''"><strong x-text="resum.errors"></strong> per revisar</span>
                <span x-show="validant || pendent" class="text-xs text-gray-400">Comprovant…</span>
            </div>
            <p x-show="error" class="text-sm text-red-700 sm:max-w-md" x-text="error"></p>
            <button type="button" @click="confirmar()" :disabled="!potConfirmar"
                    class="sm:ml-auto bg-brand text-white px-5 py-2.5 rounded-lg text-sm font-medium hover:bg-brand-dark transition disabled:opacity-50 disabled:cursor-not-allowed">
                <span x-text="confirmant ? 'Creant usuaris…' : 'Crear usuaris i generar enllaços'"></span>
            </button>
        </div>
    </div>
    <!-- Espai perquè la barra fixa no tapi l'última fila -->
    <div x-show="files.length" class="h-32 sm:h-20"></div>
</div>

<script>
function importUsuaris() {
    const cfg = JSON.parse(document.getElementById('usuaris-import-config').textContent);
    const resumBuit = { usuaris_nous: 0, existents: 0, errors: 0, files: 0 };

    return {
        cfg,
        text: '',
        files: [],
        avisosAnalisi: [],
        errorAnalisi: '',
        error: '',
        resum: { ...resumBuit },
        arrossegant: false,
        analitzant: false,
        validant: false,
        pendent: false,
        confirmant: false,
        modificat: false,
        seguentClau: 1,
        sequencia: 0,
        temporitzador: null,
        ultimaSeleccio: null,

        get seleccionades() {
            return this.files.filter(f => f.sel);
        },
        get objectiu() {
            const seleccionades = this.seleccionades;
            return seleccionades.length ? seleccionades : this.files;
        },
        get totesSeleccionades() {
            return this.files.length > 0 && this.files.every(f => f.sel);
        },
        get instalacioObjectiu() {
            const ids = [...new Set(this.objectiu.map(f => f.instalacio_id))];
            return ids.length === 1 ? ids[0] : null;
        },
        get potConfirmar() {
            return this.cfg.tokensDisponibles
                && this.files.length > 0
                && !this.pendent && !this.validant && !this.confirmant
                && this.resum.errors === 0;
        },

        tornsDe(instalacioId) {
            return (instalacioId && this.cfg.torns[instalacioId]) || [];
        },
        nomInstalacio(instalacioId) {
            const inst = this.cfg.instalacions.find(i => i.id === instalacioId);
            return inst ? inst.nom : '—';
        },
        toId(valor) {
            const id = parseInt(valor, 10);
            return Number.isFinite(id) && id > 0 ? id : null;
        },
        estatChip(coincidents, total) {
            if (total > 0 && coincidents === total) return 'usuaris-chip--actiu';
            return coincidents > 0 ? 'usuaris-chip--parcial' : '';
        },

        novaFila(dades = {}) {
            const avisos = dades.avisos && !Array.isArray(dades.avisos) ? { ...dades.avisos } : {};
            return {
                key: this.seguentClau++,
                nom: dades.nom || '',
                cognoms: dades.cognoms || '',
                email: dades.email || '',
                instalacio_id: dades.instalacio_id ?? this.cfg.defaultInstalacioId,
                rol_id: dades.rol_id ?? this.cfg.defaultRolId,
                torn_ids: Array.isArray(dades.torn_ids) ? [...dades.torn_ids] : [],
                avisos,
                sel: false,
                estat: null,
            };
        },

        // --- Entrada de dades -------------------------------------------------
        enganxat() {
            // L'esdeveniment paste arriba abans que x-model tingui el text nou.
            setTimeout(() => this.analitzar(), 0);
        },
        triarFitxer(event) {
            const fitxer = event.target.files[0];
            event.target.value = '';
            if (fitxer) this.analitzar(fitxer);
        },
        deixarFitxer(event) {
            this.arrossegant = false;
            const fitxer = event.dataTransfer.files[0];
            if (fitxer) this.analitzar(fitxer);
        },
        async analitzar(fitxer = null) {
            if (this.analitzant || (!fitxer && !this.text.trim())) return;
            if (this.files.length && this.modificat && !confirm('Vols substituir les files actuals per les dades noves? Es perdran els canvis que hi has fet.')) return;

            this.analitzant = true;
            this.errorAnalisi = '';
            const dades = new FormData();
            if (fitxer) {
                dades.append('fitxer', fitxer);
            } else {
                dades.append('text', this.text);
            }

            try {
                const resposta = await this.enviar(this.cfg.urls.analitzar, dades);
                this.files = resposta.files.map(f => this.novaFila(f));
                this.avisosAnalisi = resposta.avisos || [];
                this.aplicarValidacio(resposta.validacio, this.files.map(f => f.key));
                this.modificat = false;
                this.ultimaSeleccio = null;
                if (!this.files.length) {
                    this.errorAnalisi = 'No s\'ha trobat cap persona. Comprova que cada línia té un email.';
                }
            } catch (e) {
                this.errorAnalisi = e.message;
            } finally {
                this.analitzant = false;
            }
        },

        // --- Ajustos ràpids ---------------------------------------------------
        aplicarInstalacio(valor) {
            const id = this.toId(valor);
            if (!id) return;
            this.objectiu.forEach(f => this.posarInstalacio(f, id));
            this.programarValidacio();
        },
        aplicarRol(rolId) {
            this.objectiu.forEach(f => {
                f.rol_id = rolId;
                delete f.avisos.rol;
            });
            this.programarValidacio();
        },
        aplicarTotsTorns() {
            this.objectiu.forEach(f => {
                f.torn_ids = this.tornsDe(f.instalacio_id).map(t => t.id);
                delete f.avisos.torns;
            });
            this.programarValidacio();
        },
        treureTorns() {
            this.objectiu.forEach(f => {
                f.torn_ids = [];
                delete f.avisos.torns;
            });
            this.programarValidacio();
        },
        alternarTornObjectiu(tornId) {
            const files = this.objectiu;
            const totes = files.every(f => f.torn_ids.includes(tornId));
            files.forEach(f => {
                if (totes) {
                    f.torn_ids = f.torn_ids.filter(id => id !== tornId);
                } else if (!f.torn_ids.includes(tornId)) {
                    f.torn_ids.push(tornId);
                }
                delete f.avisos.torns;
            });
            this.programarValidacio();
        },
        eliminarSeleccionades() {
            this.files = this.files.filter(f => !f.sel);
            this.ultimaSeleccio = null;
            this.programarValidacio();
        },

        // --- Edició per fila --------------------------------------------------
        posarInstalacio(fila, id) {
            if (fila.instalacio_id !== id) {
                const valids = this.tornsDe(id).map(t => t.id);
                fila.instalacio_id = id;
                fila.torn_ids = fila.torn_ids.filter(t => valids.includes(t));
            }
            delete fila.avisos.instalacio;
            delete fila.avisos.torns;
        },
        canviarInstalacio(fila, valor) {
            const id = this.toId(valor);
            if (id) {
                this.posarInstalacio(fila, id);
            } else {
                fila.instalacio_id = null;
                fila.torn_ids = [];
            }
            this.programarValidacio();
        },
        canviarRol(fila, valor) {
            fila.rol_id = this.toId(valor);
            delete fila.avisos.rol;
            this.programarValidacio();
        },
        alternarTornFila(fila, tornId) {
            fila.torn_ids = fila.torn_ids.includes(tornId)
                ? fila.torn_ids.filter(id => id !== tornId)
                : [...fila.torn_ids, tornId];
            delete fila.avisos.torns;
            this.programarValidacio();
        },
        clicSeleccio(event, index) {
            const marcada = event.target.checked;
            if (event.shiftKey && this.ultimaSeleccio !== null) {
                const inici = Math.min(index, this.ultimaSeleccio);
                const fi = Math.max(index, this.ultimaSeleccio);
                for (let i = inici; i <= fi; i++) this.files[i].sel = marcada;
            } else {
                this.files[index].sel = marcada;
            }
            this.ultimaSeleccio = index;
        },
        seleccionarTotes(marcada) {
            this.files.forEach(f => { f.sel = marcada; });
            this.ultimaSeleccio = null;
        },
        afegirFila() {
            this.files.push(this.novaFila());
            this.programarValidacio();
            this.$nextTick(() => {
                const camps = document.querySelectorAll('[data-camp-nom]');
                if (camps.length) camps[camps.length - 1].focus();
            });
        },
        eliminarFila(fila) {
            this.files = this.files.filter(f => f.key !== fila.key);
            this.ultimaSeleccio = null;
            this.programarValidacio();
        },

        // --- Estat de cada fila -----------------------------------------------
        missatgesFila(fila) {
            const errors = fila.estat ? fila.estat.errors : [];
            const avisos = [...Object.values(fila.avisos), ...(fila.estat ? fila.estat.avisos : [])];
            return [
                ...errors.map(text => ({ text, error: true })),
                ...avisos.map(text => ({ text, error: false })),
            ];
        },
        etiquetaEstat(fila) {
            if (!fila.estat) return 'Comprovant…';
            if (fila.estat.errors.length) return 'Revisar';
            return { crear: 'Nou', assignar: 'Ja té compte', actualitzar: 'Actualitzar' }[fila.estat.accio] || '—';
        },
        classeEstat(fila) {
            if (!fila.estat) return 'import-action-pill--pending';
            if (fila.estat.errors.length) return 'import-action-pill--error';
            return { crear: 'import-action-pill--new', assignar: 'import-action-pill--match', actualitzar: 'import-action-pill--review' }[fila.estat.accio] || '';
        },

        // --- Servidor -----------------------------------------------------------
        payload() {
            return this.files.map(f => ({
                nom: f.nom,
                cognoms: f.cognoms,
                email: f.email,
                instalacio_id: f.instalacio_id,
                rol_id: f.rol_id,
                torn_ids: f.torn_ids,
            }));
        },
        programarValidacio() {
            this.modificat = true;
            this.pendent = true;
            clearTimeout(this.temporitzador);
            this.temporitzador = setTimeout(() => this.validar(), 350);
        },
        async validar() {
            const sequencia = ++this.sequencia;
            if (!this.files.length) {
                this.resum = { ...resumBuit };
                this.pendent = false;
                return;
            }

            const claus = this.files.map(f => f.key);
            const dades = new FormData();
            dades.append('files', JSON.stringify(this.payload()));
            this.validant = true;

            try {
                const resposta = await this.enviar(this.cfg.urls.validar, dades);
                if (sequencia === this.sequencia) {
                    this.aplicarValidacio(resposta.validacio, claus);
                    this.error = '';
                }
            } catch (e) {
                if (sequencia === this.sequencia) this.error = e.message;
            } finally {
                if (sequencia === this.sequencia) {
                    this.validant = false;
                    this.pendent = false;
                }
            }
        },
        aplicarValidacio(validacio, claus) {
            const perClau = new Map(this.files.map(f => [f.key, f]));
            claus.forEach((clau, i) => {
                const fila = perClau.get(clau);
                if (fila && validacio.files[i]) fila.estat = validacio.files[i];
            });
            this.resum = validacio.resum;
        },
        async confirmar() {
            if (!this.potConfirmar) return;

            const { usuaris_nous: nous, existents } = this.resum;
            let missatge = nous === 1 ? 'Es crearà 1 usuari nou amb el seu enllaç d\'accés' : `Es crearan ${nous} usuaris nous amb el seu enllaç d'accés`;
            if (existents) missatge += ` i s'actualitzaran ${existents} assignacions d'usuaris que ja tenien compte`;
            if (!confirm(missatge + '. Continuar?')) return;

            this.confirmant = true;
            this.error = '';
            const claus = this.files.map(f => f.key);
            const dades = new FormData();
            dades.append('files', JSON.stringify(this.payload()));

            try {
                const resposta = await this.enviar(this.cfg.urls.confirmar, dades);
                window.location.href = resposta.redirect;
            } catch (e) {
                this.error = e.message;
                if (e.dades && e.dades.validacio) this.aplicarValidacio(e.dades.validacio, claus);
                this.confirmant = false;
            }
        },
        async enviar(url, dades) {
            dades.append('_token', this.cfg.csrf);
            let resposta;
            try {
                resposta = await fetch(url, { method: 'POST', body: dades, credentials: 'same-origin', headers: { Accept: 'application/json' } });
            } catch (e) {
                throw new Error('No s\'ha pogut connectar amb el servidor.');
            }

            if (!(resposta.headers.get('Content-Type') || '').includes('application/json')) {
                console.error('[usuaris/importar] Resposta no JSON', resposta.status, url);
                throw new Error(`Resposta inesperada del servidor (${resposta.status}). Si la sessió ha caducat, recarrega la pàgina.`);
            }

            const json = await resposta.json();
            if (!json.ok) {
                const error = new Error(json.error || 'Error desconegut.');
                error.dades = json;
                throw error;
            }
            return json;
        },
    };
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
