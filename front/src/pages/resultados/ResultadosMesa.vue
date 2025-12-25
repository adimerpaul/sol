<template>
  <q-page class="q-pa-md bg-grey-2">
    <q-card flat bordered class="bg-white">

      <!-- HEADER -->
      <q-card-section class="row items-center ">
        <q-avatar rounded size="42px" class="bg-blue-1">
          <q-icon name="how_to_vote" class="text-primary" />
        </q-avatar>

        <div class="col">
          <div class="text-h6 text-weight-bold">Carga de Resultados por Mesa</div>
          <div class="text-caption text-grey-7">
            Selecciona una mesa asignada • Registra votos por partido • Adjunta fotos del acta
          </div>
        </div>

        <div class="col-auto row items-center q-gutter-sm">
          <q-badge v-if="selectedEstado" outline :color="selectedEstadoColor">
            {{ selectedEstado }}
          </q-badge>

          <q-btn
            outline
            color="primary"
            icon="refresh"
            label="Actualizar"
            no-caps
            :loading="loading"
            @click="loadAll"
          />
        </div>
      </q-card-section>

      <q-separator />

      <!-- BODY -->
      <q-card-section class="q-pa-md">

        <!-- MESA SELECT -->
        <div class="row q-col-gutter-md items-end q-mb-md">
          <div class="col-12 col-md-8">
            <q-select
              v-model="mesaId"
              :options="mesaOptions"
              option-label="label"
              option-value="value"
              emit-value
              map-options
              use-input
              input-debounce="0"
              @filter="filterMesas"
              dense outlined
              label="Seleccione Mesa"
              :disable="loading"
              clearable
            >
              <template v-slot:prepend>
                <q-icon name="table_restaurant" class="text-grey-7" />
              </template>
            </q-select>
          </div>

          <div class="col-12 col-md-4">
            <q-card flat bordered class="bg-grey-1 q-pa-sm">
              <div class="row items-center">
                <div class="col">
                  <div class="text-caption text-grey-7">Total votos</div>
                  <div class="text-h6 text-weight-bold">{{ totalVotos }}</div>
                </div>
                <div class="col-auto text-right" v-if="selectedFecha">
                  <div class="text-caption text-grey-7">Fecha</div>
                  <div class="text-caption">{{ selectedFecha }}</div>
                </div>
              </div>
            </q-card>
          </div>
        </div>

        <q-banner v-if="!mesaId" rounded class="bg-blue-1 text-blue-9 q-mb-md">
          <template v-slot:avatar><q-icon name="info" /></template>
          Selecciona una mesa para habilitar el formulario.
        </q-banner>

        <q-banner v-else-if="isReadOnly" rounded class="bg-grey-3 text-grey-9 q-mb-md">
          <template v-slot:avatar><q-icon name="lock" /></template>
          Esta mesa está <b>REALIZADA</b>. Solo puedes ver los datos.
          <div class="text-caption text-grey-7 q-mt-xs">
            Si necesitas re-cargar, el administrador debe cambiar el estado a <b>PENDIENTE</b>.
          </div>
        </q-banner>

        <q-banner v-else-if="selectedEstado === 'PENDIENTE'" rounded class="bg-orange-2 text-orange-10 q-mb-md">
          <template v-slot:avatar><q-icon name="warning" /></template>
          Esta mesa está en <b>PENDIENTE</b>. Puedes re-cargar y al guardar volverá a <b>REALIZADO</b>.
        </q-banner>

        <!-- PARTIDOS GRID -->
        <div class="row q-col-gutter-md">
          <div
            v-for="p in partidos"
            :key="p.id"
            class="col-12 col-md-6 col-lg-4"
          >
            <q-card flat bordered class="q-pa-sm" :class="mesaId ? '' : 'opacity-50'">
              <div class="row items-center q-col-gutter-sm">
                <div class="col-auto">
                  <q-avatar rounded size="44px" class="bg-grey-2">
                    <q-img
                      v-if="p.icono"
                      :src="partyLogoUrl(p.icono)"
                      spinner-color="primary"
                    />
                    <q-icon v-else name="flag" class="text-grey-7" />
                  </q-avatar>
                </div>

                <div class="col">
                  <div class="text-subtitle2 text-weight-bold">{{ p.sigla }}</div>
                  <div class="text-caption text-grey-7 ellipsis">{{ p.nombre }}</div>
                  <q-badge v-if="p.tipo" outline color="primary" class="q-mt-xs">
                    {{ p.tipo }}
                  </q-badge>
                </div>

                <div class="col-12 q-mt-sm">
                  <q-input
                    v-model.number="resultados[p.id]"
                    dense
                    outlined
                    type="number"
                    min="0"
                    label="Votos"
                    :disable="!mesaId || isReadOnly"
                  >
                    <template v-slot:prepend>
                      <q-icon name="how_to_reg" class="text-grey-7" />
                    </template>
                  </q-input>
                </div>
              </div>
            </q-card>
          </div>
        </div>

        <!-- FOTOS -->
        <q-separator class="q-my-md" />

        <div class="row q-col-gutter-md">
          <div class="col-12 col-md-6">
            <q-file
              v-model="foto1"
              dense
              outlined
              label="Foto Acta 1"
              accept="image/*"
              :disable="!mesaId || isReadOnly"
              clearable
            >
              <template v-slot:prepend><q-icon name="photo_camera" /></template>
            </q-file>

            <q-card v-if="foto1Preview" flat bordered class="q-mt-sm">
              <q-img :src="foto1Preview" style="height: 180px" />
            </q-card>
          </div>

          <div class="col-12 col-md-6">
            <q-file
              v-model="foto2"
              dense
              outlined
              label="Foto Acta 2"
              accept="image/*"
              :disable="!mesaId || isReadOnly"
              clearable
            >
              <template v-slot:prepend><q-icon name="photo_camera" /></template>
            </q-file>

            <q-card v-if="foto2Preview" flat bordered class="q-mt-sm">
              <q-img :src="foto2Preview" style="height: 180px" />
            </q-card>
          </div>
        </div>

        <!-- OBS -->
        <div class="q-mt-md">
          <q-input
            v-model="observacion"
            dense
            outlined
            type="textarea"
            label="Observación (opcional)"
            :disable="!mesaId || isReadOnly"
            autogrow
          />
        </div>

      </q-card-section>

      <q-separator />

      <!-- ACTIONS -->
      <q-card-actions align="right" class="q-pa-md">
        <q-btn
          flat
          color="grey-8"
          label="Limpiar"
          no-caps
          :disable="loading || isReadOnly"
          @click="resetForm"
        />
        <q-btn
          color="primary"
          icon="send"
          label="Enviar resultados"
          no-caps
          :loading="saving"
          :disable="!canSubmit"
          @click="guardar"
        />
      </q-card-actions>

      <q-inner-loading :showing="loading">
        <q-spinner />
      </q-inner-loading>

    </q-card>
  </q-page>
</template>

<script>
export default {
  name: 'ResultadosMesa',

  data () {
    return {
      loading: false,
      saving: false,

      mesas: [],
      mesaId: null,
      mesaOptions: [],
      mesaOptionsAll: [],

      partidos: [],
      resultados: {},

      // resultado actual (si existe)
      currentResult: null,

      foto1: null,
      foto2: null,
      observacion: ''
    }
  },

  computed: {
    totalVotos () {
      const vals = Object.values(this.resultados || {})
      return vals.reduce((a, b) => a + (Number(b) || 0), 0)
    },

    selectedEstado () {
      return this.currentResult?.estado || null
    },

    selectedEstadoColor () {
      if (this.selectedEstado === 'REALIZADO') return 'positive'
      if (this.selectedEstado === 'PENDIENTE') return 'warning'
      return 'grey-7'
    },

    selectedFecha () {
      const f = this.currentResult?.fecha_hora
      if (!f) return null
      return String(f).replace('T', ' ').substring(0, 16)
    },

    isReadOnly () {
      return this.currentResult?.estado === 'REALIZADO'
    },

    canSubmit () {
      if (!this.mesaId) return false
      if (this.isReadOnly) return false
      return !this.saving
    },

    foto1Preview () {
      if (this.foto1) return URL.createObjectURL(this.foto1)
      if (this.currentResult?.foto_1) return this.actaUrl(this.currentResult.foto_1)
      return null
    },

    foto2Preview () {
      if (this.foto2) return URL.createObjectURL(this.foto2)
      if (this.currentResult?.foto_2) return this.actaUrl(this.currentResult.foto_2)
      return null
    }
  },

  watch: {
    async mesaId (val) {
      if (!val) {
        this.currentResult = null
        this.resetForm()
        return
      }
      await this.loadMesaDetail(val)
    }
  },

  async mounted () {
    await this.loadAll()
  },

  methods: {
    partyLogoUrl (filename) {
      // logos en /public/images/partidos/
      return `${this.$url}/../images/partidos/${filename}`
    },

    actaUrl (filename) {
      // actas en /public/actas/
      return `${this.$url}/../actas/${filename}`
    },

    async loadAll () {
      this.loading = true
      try {
        const [mRes, pRes] = await Promise.all([
          this.$axios.get('resultados/mesas-asignadas'),
          this.$axios.get('partidos?per_page=200')
        ])

        this.mesas = Array.isArray(mRes.data) ? mRes.data : []
        const pData = pRes.data?.data ? pRes.data.data : (Array.isArray(pRes.data) ? pRes.data : [])
        this.partidos = pData

        // opciones mesas (con estado)
        this.mesaOptionsAll = this.mesas.map(m => {
          const estado = m.resultado_mesa?.estado || (m.resultadoMesa?.estado) || null
          const total = m.resultado_mesa?.total_votos || (m.resultadoMesa?.total_votos) || null

          let extra = 'SIN CARGAR'
          if (estado === 'REALIZADO') extra = `REALIZADO • Total ${total ?? 0}`
          if (estado === 'PENDIENTE') extra = `PENDIENTE • Re-carga`

          return {
            value: m.id,
            label: `Mesa ${m.numero_mesa} — ${m.recinto?.nombre || ''} • ${extra}`
          }
        })
        this.mesaOptions = this.mesaOptionsAll

        // init resultados
        const obj = {}
        this.partidos.forEach(p => { obj[p.id] = 0 })
        this.resultados = obj
      } catch (e) {
        this.$q.notify({ type: 'negative', message: e?.response?.data?.message || 'No se pudo cargar' })
      } finally {
        this.loading = false
      }
    },

    async loadMesaDetail (mesaId) {
      this.loading = true
      try {
        const mesa = await this.$axios.get(`resultados/mesa/${mesaId}`).then(r => r.data)
        const r = mesa?.resultado_mesa || mesa?.resultadoMesa || null
        this.currentResult = r

        // si hay resultado => cargar sus votos al formulario (solo lectura si REALIZADO)
        const obj = {}
        this.partidos.forEach(p => { obj[p.id] = 0 })

        if (r?.resultados) {
          Object.keys(obj).forEach(pid => {
            const v = r.resultados[pid]
            obj[pid] = Number(v || 0)
          })
          // por si viene con keys numéricas reales
          Object.entries(r.resultados).forEach(([k, v]) => {
            obj[k] = Number(v || 0)
          })
        }

        this.resultados = obj
        this.observacion = r?.observacion || ''

        // no tocar archivos si solo es ver
        this.foto1 = null
        this.foto2 = null
      } catch (e) {
        this.$q.notify({ type: 'negative', message: e?.response?.data?.message || 'No se pudo cargar la mesa' })
      } finally {
        this.loading = false
      }
    },

    filterMesas (val, update) {
      update(() => {
        const needle = String(val || '').toLowerCase().trim()
        if (!needle) {
          this.mesaOptions = this.mesaOptionsAll
          return
        }
        this.mesaOptions = this.mesaOptionsAll.filter(o =>
          String(o.label || '').toLowerCase().includes(needle)
        )
      })
    },

    resetForm () {
      const obj = {}
      this.partidos.forEach(p => { obj[p.id] = 0 })
      this.resultados = obj
      this.foto1 = null
      this.foto2 = null
      this.observacion = ''
    },

    async guardar () {
      this.saving = true
      try {
        const fd = new FormData()
        fd.append('mesa_id', this.mesaId)
        fd.append('resultados', JSON.stringify(this.resultados))
        if (this.foto1) fd.append('foto_1', this.foto1)
        if (this.foto2) fd.append('foto_2', this.foto2)
        if (this.observacion) fd.append('observacion', this.observacion)

        await this.$axios.post('resultados', fd, {
          headers: { 'Content-Type': 'multipart/form-data' }
        })

        this.$q.notify({ type: 'positive', message: '✅ Resultado guardado (REALIZADO)' })

        await this.loadAll()
        // refrescar detalle para que se ponga en readonly
        await this.loadMesaDetail(this.mesaId)
      } catch (e) {
        this.$q.notify({ type: 'negative', message: e?.response?.data?.message || 'No se pudo enviar' })
      } finally {
        this.saving = false
      }
    }
  }
}
</script>

<style scoped>
.opacity-50 { opacity: 0.5; }
</style>
