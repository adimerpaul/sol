<template>
  <q-page class="q-pa-md bg-grey-2">
    <q-card flat bordered class="bg-white">
      <q-card-section class="row items-center">
        <div class="col">
          <div class="text-h6 text-weight-bold">Avance de Mesas</div>
          <div class="text-caption text-grey-7">Seguimiento manual de mesas realizadas por categoria</div>
        </div>
        <div class="col-auto">
          <q-btn color="primary" icon="refresh" label="Actualizar" no-caps :loading="loading" @click="loadData" />
        </div>
      </q-card-section>

      <q-separator />

      <q-card-section v-if="!isAdministrator">
        <q-banner rounded class="bg-negative text-white">
          Esta vista esta disponible solo para administradores.
        </q-banner>
      </q-card-section>

      <template v-else>
        <q-card-section class="q-pt-md">
          <div class="row q-col-gutter-md">
            <div class="col-12 col-md-4">
              <q-card flat bordered class="q-pa-md summary-card">
                <div class="text-caption text-grey-7">Mesas totales</div>
                <div class="text-h4 text-weight-bold">{{ summary.total }}</div>
              </q-card>
            </div>
            <div class="col-12 col-md-4">
              <q-card flat bordered class="q-pa-md summary-card">
                <div class="text-caption text-grey-7">Mesas realizadas</div>
                <div class="text-h4 text-weight-bold text-positive">{{ summary.realizadas }}</div>
              </q-card>
            </div>
            <div class="col-12 col-md-4">
              <q-card flat bordered class="q-pa-md summary-card">
                <div class="text-caption text-grey-7">Mesas faltantes</div>
                <div class="text-h4 text-weight-bold text-orange">{{ summary.faltantes }}</div>
              </q-card>
            </div>
          </div>
        </q-card-section>

        <q-card-section class="q-pt-none">
          <q-card flat bordered class="q-pa-md overall-card">
            <div class="row items-center justify-between q-mb-sm">
              <div>
                <div class="text-subtitle1 text-weight-bold">Porcentaje general de llenado</div>
                <div class="text-caption text-grey-7">Mesas con cualquier resultado registrado</div>
              </div>
              <div class="text-h6 text-weight-bold">{{ summary.porcentaje.toFixed(2) }}%</div>
            </div>
            <q-linear-progress rounded size="18px" :value="summary.progress" color="positive" track-color="grey-4" />
          </q-card>
        </q-card-section>

        <q-card-section class="q-pt-none">
          <div class="row q-col-gutter-md">
            <div v-for="item in categorias" :key="item.key" class="col-12 col-md-6">
              <q-card flat bordered class="q-pa-md category-card">
                <div class="row items-center justify-between q-mb-xs">
                  <div class="text-subtitle2 text-weight-bold">{{ item.label }}</div>
                  <q-chip dense outline color="primary">{{ item.mesas_realizadas }} / {{ item.mesas_total }}</q-chip>
                </div>
                <div class="text-caption text-grey-7 q-mb-sm">
                  Realizadas: {{ item.mesas_realizadas }} | Faltantes: {{ item.mesas_faltantes }}
                </div>
                <q-linear-progress rounded size="14px" :value="item.progress" color="secondary" track-color="grey-4" />
                <div class="text-caption text-grey-8 q-mt-sm">{{ item.porcentaje.toFixed(2) }}%</div>
              </q-card>
            </div>
          </div>
        </q-card-section>

        <q-card-section class="q-pt-none">
          <div class="text-caption text-grey-6">
            Actualizado: {{ generatedAtLabel }}
          </div>
        </q-card-section>
      </template>

      <q-inner-loading :showing="loading">
        <q-spinner />
      </q-inner-loading>
    </q-card>
  </q-page>
</template>

<script>
export default {
  name: 'DashboardAvanceMesasPage',
  data () {
    return {
      loading: false,
      payload: {
        mesas: {
          total: 0,
          realizadas: 0,
          faltantes: 0,
          porcentaje: 0
        },
        categorias: [],
        generated_at: null
      }
    }
  },
  computed: {
    isAdministrator () {
      return (this.$store?.user?.role || '') === 'Administrador'
    },
    summary () {
      const mesas = this.payload?.mesas || {}
      const porcentaje = Number(mesas.porcentaje || 0)

      return {
        total: Number(mesas.total || 0),
        realizadas: Number(mesas.realizadas || 0),
        faltantes: Number(mesas.faltantes || 0),
        porcentaje,
        progress: Math.max(0, Math.min(1, porcentaje / 100))
      }
    },
    categorias () {
      return (Array.isArray(this.payload?.categorias) ? this.payload.categorias : []).map(item => {
        const porcentaje = Number(item?.porcentaje || 0)
        return {
          key: item?.key || '',
          label: item?.label || '-',
          mesas_total: Number(item?.mesas_total || 0),
          mesas_realizadas: Number(item?.mesas_realizadas || 0),
          mesas_faltantes: Number(item?.mesas_faltantes || 0),
          porcentaje,
          progress: Math.max(0, Math.min(1, porcentaje / 100))
        }
      })
    },
    generatedAtLabel () {
      if (!this.payload?.generated_at) return 'Sin fecha'
      const value = new Date(this.payload.generated_at)
      if (Number.isNaN(value.getTime())) return 'Sin fecha'
      return value.toLocaleString('es-BO', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
    }
  },
  async mounted () {
    if (!this.isAdministrator) return
    await this.loadData()
  },
  methods: {
    async loadData () {
      this.loading = true
      try {
        const { data } = await this.$axios.get('dashboard/bootstrap/avance-mesas')
        this.payload = data || this.payload
      } catch (e) {
        this.$q.notify({
          type: 'negative',
          message: e?.response?.data?.message || 'No se pudo cargar el avance de mesas'
        })
      } finally {
        this.loading = false
      }
    }
  }
}
</script>

<style scoped>
.summary-card,
.overall-card,
.category-card {
  border-radius: 14px;
}
</style>
