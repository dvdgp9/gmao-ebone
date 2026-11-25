<?php
/**
 * Formulari de paràmetres de l'aigua (Vista Diària). S'obre amb l'esdeveniment
 * «obrir-mesura» des del botó «Paràmetres» de cada tasca de mesura.
 * Variables: $tasques, $mesures, $redirectDia.
 */
$dadesMesura = [];
foreach ($tasques as $t) {
    if (!isset($mesures[$t['id']])) {
        continue;
    }
    $dadesMesura[(int)$t['id']] = $mesures[$t['id']] + [
        'codi' => (string)($t['tasca_codi'] ?? ''),
        'nom' => (string)$t['tasca_nom'],
        'espai' => (string)($t['espai_nom'] ?? ''),
    ];
}
$parametres = \App\Models\MesuraPiscina::PARAMETRES;
$moments = \App\Models\MesuraPiscina::MOMENTS;
?>
<div x-data="mesuraPiscina(<?= e(json_encode(['tasques' => $dadesMesura, 'parametres' => $parametres, 'moments' => $moments], JSON_UNESCAPED_UNICODE)) ?>)"
     @obrir-mesura.window="obrir($event.detail.id)"
     @keydown.escape.window="obert = false"
     x-show="obert" x-cloak
     class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-gray-900/40 p-0 sm:p-4"
     role="dialog" aria-modal="true" aria-labelledby="mesura-titol">
    <div @click.outside="obert = false" class="w-full sm:max-w-2xl max-h-[92vh] overflow-y-auto rounded-t-2xl sm:rounded-2xl bg-white shadow-xl">
        <template x-if="tasca">
        <div>
            <div class="sticky top-0 z-10 flex items-start justify-between gap-3 border-b border-gray-100 bg-white px-4 sm:px-6 py-4">
                <div class="min-w-0">
                    <p class="text-xs font-medium uppercase tracking-wide text-brand">Paràmetres de l'aigua · avui</p>
                    <h3 id="mesura-titol" class="text-base sm:text-lg font-semibold text-gray-800 break-words" x-text="tasca.espai || tasca.nom"></h3>
                    <p class="text-xs text-gray-500 break-words"><span class="font-mono" x-text="tasca.codi"></span> <span x-text="tasca.espai ? tasca.nom : ''"></span></p>
                </div>
                <button type="button" @click="obert = false" class="shrink-0 rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600" aria-label="Tancar">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form method="POST" action="<?= url('parametres/store') ?>" class="px-4 sm:px-6 py-4 space-y-4"
                  @submit="if (hiHaInvalids()) { $event.preventDefault(); avisInvalids = true; }">
                <?= csrf_field() ?>
                <input type="hidden" name="tasca_pla_id" :value="tascaId">
                <input type="hidden" name="moment" :value="moment">
                <input type="hidden" name="redirect" value="<?= e($redirectDia) ?>">

                <div>
                    <p class="text-sm font-medium text-gray-700 mb-2">Moment de la mesura</p>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="(nom, clau) in moments" :key="clau">
                            <button type="button" @click="triarMoment(clau)"
                                    :class="moment === clau ? 'bg-brand text-white border-brand' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50'"
                                    class="inline-flex items-center gap-1.5 rounded-2xl border px-3 py-1.5 text-sm transition">
                                <span x-text="nom"></span>
                                <svg x-show="tasca.fetes[clau]" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-label="Ja mesurat"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </button>
                        </template>
                    </div>
                    <p class="text-xs text-gray-400 mt-1.5">L'hora és orientativa. La tasca queda feta amb la primera mesura del dia; les altres es poden afegir fins al final de la jornada.</p>
                </div>

                <p x-show="jaMesurat" class="rounded-lg bg-blue-50 px-3 py-2 text-xs text-blue-700">Aquest moment ja està mesurat. Si canvies algun valor, es corregeix.</p>

                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">A cada mesura</p>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <template x-for="camp in campsMoment" :key="camp">
                        <label class="block">
                            <span class="block text-xs font-medium text-gray-600">
                                <span x-text="parametres[camp].nom"></span><span x-show="parametres[camp].unitat" class="text-gray-400" x-text="' (' + parametres[camp].unitat + ')'"></span>
                            </span>
                            <input type="text" inputmode="decimal" autocomplete="off" :name="camp" x-model="valors[camp]"
                                   :placeholder="camp === 'clor_combinat' && combinatCalculat() !== '' ? combinatCalculat() : ''"
                                   :aria-invalid="invalid(camp) ? 'true' : 'false'"
                                   :class="{ 'border-red-500 bg-white text-red-700 ring-1 ring-red-400': invalid(camp), 'border-red-400 bg-red-50 text-red-700': !invalid(camp) && estat(camp) === 'tancar', 'border-yellow-400 bg-yellow-50 text-yellow-800': !invalid(camp) && estat(camp) === 'fora', 'border-gray-300': !invalid(camp) && (!estat(camp) || estat(camp) === 'ok') }"
                                   class="mt-1 w-full rounded-lg border px-3 py-2 text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none">
                            <span class="block text-[11px] mt-0.5"
                                  :class="invalid(camp) || estat(camp) === 'tancar' ? 'text-red-600 font-medium' : (estat(camp) === 'fora' ? 'text-yellow-700' : 'text-gray-400')"
                                  x-text="invalid(camp) ? 'Valor no vàlid' : (estat(camp) === 'tancar' ? 'Tancar el vas' : (parametres[camp].norma ? 'Normal: ' + parametres[camp].norma : (camp === 'clor_combinat' ? 'Total − lliure si el deixes buit' : '')))"></span>
                        </label>
                    </template>
                </div>

                <div class="space-y-2 border-t border-gray-100 pt-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Un cop al dia · abans d'obrir al públic</p>
                    <p x-show="diarisAltre" class="rounded-lg bg-gray-50 px-3 py-2 text-xs text-gray-600">
                        Ja s'han registrat a la mesura de <strong x-text="moments[diarisAltre]"></strong>. Per corregir-los, tria aquell moment.
                    </p>
                    <template x-if="!diarisAltre">
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        <template x-for="camp in campsDiaris" :key="camp">
                            <label class="block">
                                <span class="block text-xs font-medium text-gray-600">
                                    <span x-text="parametres[camp].nom"></span><span x-show="parametres[camp].unitat" class="text-gray-400" x-text="' (' + parametres[camp].unitat + ')'"></span>
                                </span>
                                <input type="text" inputmode="decimal" autocomplete="off" :name="camp" x-model="valors[camp]"
                                       :placeholder="camp === 'clor_combinat' && combinatCalculat() !== '' ? combinatCalculat() : ''"
                                       :aria-invalid="invalid(camp) ? 'true' : 'false'"
                                       :class="{ 'border-red-500 bg-white text-red-700 ring-1 ring-red-400': invalid(camp), 'border-red-400 bg-red-50 text-red-700': !invalid(camp) && estat(camp) === 'tancar', 'border-yellow-400 bg-yellow-50 text-yellow-800': !invalid(camp) && estat(camp) === 'fora', 'border-gray-300': !invalid(camp) && (!estat(camp) || estat(camp) === 'ok') }"
                                       class="mt-1 w-full rounded-lg border px-3 py-2 text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none">
                                <span class="block text-[11px] mt-0.5"
                                      :class="invalid(camp) || estat(camp) === 'tancar' ? 'text-red-600 font-medium' : (estat(camp) === 'fora' ? 'text-yellow-700' : 'text-gray-400')"
                                      x-text="invalid(camp) ? 'Valor no vàlid' : (estat(camp) === 'tancar' ? 'Tancar el vas' : (parametres[camp].norma ? 'Normal: ' + parametres[camp].norma : (camp === 'clor_combinat' ? 'Total − lliure si el deixes buit' : '')))"></span>
                            </label>
                        </template>
                    </div>
                    </template>
                </div>

                <div x-show="avisInvalids && hiHaInvalids()" class="rounded-lg border border-red-300 bg-white px-3 py-2 text-sm text-red-700" role="alert">
                    Hi ha valors marcats com a <strong>no vàlids</strong> (no són un número o no són possibles). Corregeix-los per desar.
                </div>

                <div x-show="hiHaTancar()" class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
                    Algun valor obliga a <strong>tancar el vas</strong>. Desa la mesura i anota l'actuació a les observacions.
                </div>

                <label class="block">
                    <span class="block text-sm font-medium text-gray-700">Observacions</span>
                    <span class="block text-xs text-gray-400">Si algun valor surt fora de norma, anota l'actuació i el resultat.</span>
                    <textarea name="observacions" rows="2" x-model="valors.observacions" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none"></textarea>
                </label>

                <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-3 pt-1">
                    <button type="button" x-show="!tasca.feta" @click="incidencia = !incidencia" class="text-sm text-red-600 hover:text-red-700 text-left">No s'ha pogut mesurar</button>
                    <span x-show="tasca.feta" class="text-xs text-green-700">Tasca feta avui</span>
                    <div class="flex flex-col sm:flex-row gap-2">
                        <button type="button" @click="obert = false" class="px-4 py-2.5 rounded-lg text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 transition">Cancel·lar</button>
                        <button type="submit" class="px-5 py-2.5 rounded-lg text-sm font-medium text-white bg-brand hover:bg-brand-dark active:scale-[0.98] transition" x-text="jaMesurat ? 'Corregir mesura' : 'Desar mesura'"></button>
                    </div>
                </div>
            </form>

            <form x-show="incidencia && !tasca.feta" method="POST" action="<?= url('registre/store') ?>" class="mx-4 sm:mx-6 mb-4 space-y-2 rounded-lg border border-gray-100 bg-gray-50 p-3">
                <?= csrf_field() ?>
                <input type="hidden" name="tasca_pla_id" :value="tascaId">
                <input type="hidden" name="data_execucio" value="<?= date('Y-m-d') ?>">
                <input type="hidden" name="realitzada" value="0">
                <input type="hidden" name="tipus_incidencia" value="no_feta_per_incidencia">
                <input type="hidden" name="redirect" value="<?= e($redirectDia) ?>">
                <label class="block text-xs font-medium text-gray-500">Per què no s'ha pogut mesurar?</label>
                <textarea name="comentaris" required rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none" placeholder="Descriu la incidència..."></textarea>
                <button type="submit" class="w-full rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white hover:bg-gray-800 transition">Registrar incidència</button>
            </form>
        </div>
        </template>
    </div>
</div>

<script>
function mesuraPiscina(dades) {
    const buits = () => Object.fromEntries([...Object.keys(dades.parametres), 'observacions'].map(c => [c, '']));
    // Mateix criteri que MesuraPiscina::numero() (PHP): null = buit, NaN = no és un número.
    const comptadors = <?= json_encode(\App\Models\MesuraPiscina::COMPTADORS) ?>;
    const llegir = (v, comptador = false) => {
        let t = String(v ?? '').replace(/\s+/g, '').replace(/(ºc|°c|º|°|%|ppm|mg\/l|unf)$/i, '');
        if (t === '') return null;
        const comes = (t.match(/,/g) || []).length, punts = (t.match(/\./g) || []).length;
        if (comes && punts) t = t.lastIndexOf(',') > t.lastIndexOf('.') ? t.replace(/\./g, '').replace(',', '.') : t.replace(/,/g, '');
        else if (comes > 1) t = t.replace(/,/g, '');
        else if (punts > 1) t = t.replace(/\./g, '');
        else if (comptador && /^\d{1,3}\.\d{3}$/.test(t)) t = t.replace('.', '');
        else t = t.replace(',', '.');
        return /^-?\d+(\.\d+)?$/.test(t) ? Number(t) : NaN;
    };
    const num = (v) => { const n = llegir(v); return Number.isNaN(n) ? null : n; };
    const campsMoment = Object.keys(dades.parametres).filter(c => dades.parametres[c].cada_moment);
    const campsDiaris = Object.keys(dades.parametres).filter(c => !dades.parametres[c].cada_moment);
    return {
        tasques: dades.tasques,
        parametres: dades.parametres,
        moments: dades.moments,
        campsMoment,
        campsDiaris,
        obert: false,
        incidencia: false,
        avisInvalids: false,
        tascaId: null,
        moment: 'inici',
        valors: buits(),
        get tasca() { return this.tascaId ? this.tasques[this.tascaId] : null; },
        get jaMesurat() { return !!(this.tasca && this.tasca.fetes[this.moment]); },
        // Moment on ja hi ha els paràmetres d'un cop al dia, si no és el triat.
        get diarisAltre() { return this.tasca && this.tasca.diaris && this.tasca.diaris !== this.moment ? this.tasca.diaris : null; },
        obrir(id) {
            if (!this.tasques[id]) return;
            this.tascaId = id;
            this.incidencia = false;
            this.avisInvalids = false;
            this.triarMoment(this.tasques[id].proposat || 'inici');
            this.obert = true;
        },
        triarMoment(clau) {
            this.moment = clau;
            const fet = this.tasca.fetes[clau];
            const v = buits();
            if (fet) {
                for (const c in v) {
                    if (fet[c] === null || fet[c] === undefined) continue;
                    v[c] = c === 'observacions' ? fet[c] : String(Number(fet[c])).replace('.', ',');
                }
            }
            this.valors = v;
        },
        combinatCalculat() {
            const t = num(this.valors.clor_total), l = num(this.valors.clor_lliure);
            return t !== null && l !== null ? String(Math.max(0, Math.round((t - l) * 100) / 100)).replace('.', ',') : '';
        },
        // Mateixos criteris que MesuraPiscina::avaluar() (PHP).
        estat(camp) {
            let x = num(this.valors[camp]);
            if (camp === 'clor_combinat' && x === null) x = num(this.combinatCalculat());
            if (x === null) return null;
            switch (camp) {
                case 'clor_lliure': return (x <= 0 || x > 5) ? 'tancar' : ((x < 0.5 || x > 2) ? 'fora' : 'ok');
                case 'clor_combinat': return x > 3 ? 'tancar' : (x >= 0.6 ? 'fora' : 'ok');
                case 'ph': return (x < 6 || x > 9) ? 'tancar' : ((x < 7.2 || x > 8) ? 'fora' : 'ok');
                case 'co2_interior': { const e = num(this.valors.co2_exterior); return e === null ? null : (x >= e + 500 ? 'fora' : 'ok'); }
                case 'humitat': return x >= 65 ? 'fora' : 'ok';
                case 'terbolesa': return x > 20 ? 'tancar' : (x >= 5 ? 'fora' : 'ok');
                default: return null;
            }
        },
        hiHaTancar() { return this.campsVisibles().some(c => this.estat(c) === 'tancar'); },
        campsVisibles() { return this.diarisAltre ? campsMoment : Object.keys(this.parametres); },
        // Valor que no es pot desar: no és un número o està fora del que és possible.
        invalid(camp) {
            const n = llegir(this.valors[camp], comptadors.includes(camp));
            if (n === null) return false;
            const p = this.parametres[camp];
            return Number.isNaN(n) || n < p.min || n > p.max;
        },
        hiHaInvalids() { return this.campsVisibles().some(c => this.invalid(c)); },
    };
}
</script>
