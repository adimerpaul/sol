<template>
  <q-page class="q-pa-md">
    <q-table
      :rows="partidos"
      :columns="columns"
      row-key="id"
      dense
      wrap-cells
      flat
      bordered
      :rows-per-page-options="[0]"
      title="Partidos"
      :filter="filter"
    >
      <template v-slot:top-right>
        <q-btn
          color="positive"
          label="Nuevo"
          @click="partidoNew"
          no-caps
          icon="add_circle_outline"
          :loading="loading"
          class="q-mr-sm"
        />
        <q-btn
          color="primary"
          label="Actualizar"
          @click="partidosGet"
          no-caps
          icon="refresh"
          :loading="loading"
          class="q-mr-sm"
        />
        <q-input v-model="filter" label="Buscar" dense outlined>
          <template v-slot:append>
            <q-icon name="search" />
          </template>
        </q-input>
      </template>

      <!-- ICONO -->
      <template v-slot:body-cell-icono="props">
        <q-td :props="props">
          <q-avatar rounded size="40px">
            <q-img
              v-if="props.row.icono"
              :src="`${$url}/../images/partidos/${props.row.icono}`"
              width="40px"
              height="40px"
            />
            <q-icon name="image" size="24px" v-else />
          </q-avatar>
        </q-td>
      </template>

      <!-- TIPO -->
      <template v-slot:body-cell-tipo="props">
        <q-td :props="props">
          <q-chip
            dense
            text-color="white"
            :label="props.row.tipo"
            :color="colorTipo(props.row.tipo)"
          />
        </q-td>
      </template>

      <!-- ACCIONES -->
      <template v-slot:body-cell-actions="props">
        <q-td :props="props">
          <q-btn-dropdown label="Opciones" no-caps size="10px" dense color="primary">
            <q-list>
              <q-item clickable @click="partidoEdit(props.row)" v-close-popup>
                <q-item-section avatar><q-icon name="edit" /></q-item-section>
                <q-item-section><q-item-label>Editar</q-item-label></q-item-section>
              </q-item>

              <q-item clickable @click="partidoDelete(props.row.id)" v-close-popup>
                <q-item-section avatar><q-icon name="delete" /></q-item-section>
                <q-item-section><q-item-label>Eliminar</q-item-label></q-item-section>
              </q-item>
            </q-list>
          </q-btn-dropdown>
        </q-td>
      </template>
    </q-table>

    <!-- DIALOG CREATE/EDIT -->
    <q-dialog v-model="dialog" persistent>
      <q-card style="width: 520px; max-width: 95vw">
        <q-card-section class="q-pb-none row items-center">
          <div class="text-bold">
            {{ partido.id ? 'Editar Partido' : 'Nuevo Partido' }}
          </div>
          <q-space />
          <q-btn icon="close" flat round dense @click="closeDialog" />
        </q-card-section>

        <q-card-section class="q-pt-none">
          <q-form @submit.prevent="partido.id ? partidoPut() : partidoPost()">
            <div class="row q-col-gutter-md">

              <div class="col-12 col-md-4">
                <q-input
                  v-model="partido.sigla"
                  label="Sigla"
                  dense
                  outlined
                  :rules="[v => !!v || 'Requerido']"
                />
              </div>

              <div class="col-12 col-md-8">
                <q-input
                  v-model="partido.nombre"
                  label="Nombre"
                  dense
                  outlined
                  :rules="[v => !!v || 'Requerido']"
                />
              </div>

              <div class="col-12 col-md-6">
                <q-select
                  v-model="partido.tipo"
                  label="Tipo"
                  dense
                  outlined
                  :options="tipos"
                  :rules="[v => !!v || 'Requerido']"
                />
              </div>

              <div class="col-12 col-md-6">
                <q-input
                  v-model="partido.alcalde"
                  label="Alcalde (opcional)"
                  dense
                  outlined
                />
              </div>

              <!-- ICONO -->
              <div class="col-12">
                <q-card flat bordered class="q-pa-sm">
                  <div class="row items-center q-col-gutter-md">
                    <div class="col-auto">
                      <q-avatar rounded size="90px">
                        <q-img v-if="iconPreview" :src="iconPreview" />
                        <q-icon v-else name="image" size="42px" />
                      </q-avatar>
                    </div>

                    <div class="col">
                      <div class="text-subtitle2 text-weight-medium">Ícono del partido</div>
                      <div class="text-caption text-grey-7">
                        JPG/PNG/WebP • se guarda en 300x300
                      </div>

                      <div class="row q-gutter-sm q-mt-sm">
                        <q-btn
                          outline
                          color="primary"
                          icon="image"
                          label="Elegir imagen"
                          no-caps
                          @click="$refs.iconInput.click()"
                        />
                        <q-btn
                          v-if="iconPreview"
                          outline
                          color="negative"
                          icon="close"
                          label="Quitar"
                          no-caps
                          @click="clearIcon"
                        />
                      </div>

                      <input
                        ref="iconInput"
                        type="file"
                        accept="image/*"
                        style="display:none"
                        @change="onIconChange"
                      />
                    </div>
                  </div>
                </q-card>
              </div>

            </div>

            <div class="text-right q-mt-md">
              <q-btn color="negative" label="Cancelar" no-caps :loading="loading" @click="closeDialog" />
              <q-btn color="primary" label="Guardar" type="submit" no-caps :loading="loading" class="q-ml-sm" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script>
export default {
  name: 'PartidosPage',
  data () {
    return {
      partidos: [],
      partido: {},
      dialog: false,
      loading: false,
      filter: '',

      tipos: ['PARTIDO', 'AGRUPACION', 'INDIGENA'],

      // imagen
      iconFile: null,
      iconPreview: null,

      columns: [
        { name: 'actions', label: 'Acciones', align: 'center' },
        { name: 'icono', label: 'Ícono', align: 'left', field: row => row.icono },
        { name: 'sigla', label: 'Sigla', align: 'left', field: 'sigla' },
        { name: 'nombre', label: 'Nombre', align: 'left', field: 'nombre' },
        { name: 'tipo', label: 'Tipo', align: 'left', field: 'tipo' },
        { name: 'alcalde', label: 'Alcalde', align: 'left', field: 'alcalde' }
      ]
    }
  },

  mounted () {
    this.partidosGet()
  },

  methods: {
    colorTipo (t) {
      if (t === 'PARTIDO') return 'primary'
      if (t === 'AGRUPACION') return 'orange'
      if (t === 'INDIGENA') return 'green'
      return 'grey'
    },

    partidosGet () {
      this.loading = true
      this.partidos = []
      this.$axios.get('partidos', { params: { per_page: 9999 } })
        .then(res => {
          // paginator: { data: [] }
          this.partidos = res.data?.data || []
        })
        .catch(err => {
          this.$alert?.error(err.response?.data?.message || 'Error cargando partidos')
        })
        .finally(() => {
          this.loading = false
        })
    },

    partidoNew () {
      this.partido = {
        sigla: '',
        nombre: '',
        tipo: 'PARTIDO',
        alcalde: ''
      }
      this.iconFile = null
      this.iconPreview = null
      this.dialog = true
    },

    partidoEdit (row) {
      this.partido = { ...row }
      this.iconFile = null
      this.iconPreview = row?.icono
        ? `${this.$url}/../images/partidos/${row.icono}`
        : null
      this.dialog = true
    },

    closeDialog () {
      this.dialog = false
      this.iconFile = null
      this.iconPreview = null
      if (this.$refs.iconInput) this.$refs.iconInput.value = ''
    },

    onIconChange (e) {
      const file = e.target.files?.[0]
      if (!file) return
      this.iconFile = file
      this.iconPreview = URL.createObjectURL(file)
    },

    clearIcon () {
      this.iconFile = null
      this.iconPreview = null
      if (this.$refs.iconInput) this.$refs.iconInput.value = ''
    },

    // CREATE con multipart
    partidoPost () {
      this.loading = true

      const fd = new FormData()
      fd.append('sigla', this.partido.sigla || '')
      fd.append('nombre', this.partido.nombre || '')
      fd.append('tipo', this.partido.tipo || 'PARTIDO')
      fd.append('alcalde', this.partido.alcalde || '')
      if (this.iconFile) fd.append('icono', this.iconFile)

      this.$axios.post('partidos', fd, {
        headers: { 'Content-Type': 'multipart/form-data' }
      }).then(() => {
        this.$alert?.success('Partido creado')
        this.closeDialog()
        this.partidosGet()
      }).catch(err => {
        this.$alert?.error(err.response?.data?.message || 'No se pudo crear')
      }).finally(() => {
        this.loading = false
      })
    },

    // UPDATE con multipart (Laravel: mejor POST + _method=PUT)
    partidoPut () {
      this.loading = true

      const fd = new FormData()
      fd.append('_method', 'PUT')
      fd.append('sigla', this.partido.sigla || '')
      fd.append('nombre', this.partido.nombre || '')
      fd.append('tipo', this.partido.tipo || 'PARTIDO')
      fd.append('alcalde', this.partido.alcalde || '')
      if (this.iconFile) fd.append('icono', this.iconFile)

      this.$axios.post(`partidos/${this.partido.id}`, fd, {
        headers: { 'Content-Type': 'multipart/form-data' }
      }).then(() => {
        this.$alert?.success('Partido actualizado')
        this.closeDialog()
        this.partidosGet()
      }).catch(err => {
        this.$alert?.error(err.response?.data?.message || 'No se pudo actualizar')
      }).finally(() => {
        this.loading = false
      })
    },

    partidoDelete (id) {
      this.$alert?.dialog('¿Desea eliminar el partido?')
        .onOk(() => {
          this.loading = true
          this.$axios.delete(`partidos/${id}`)
            .then(() => {
              this.$alert?.success('Partido eliminado')
              this.partidosGet()
            })
            .catch(err => {
              this.$alert?.error(err.response?.data?.message || 'No se pudo eliminar')
            })
            .finally(() => {
              this.loading = false
            })
        })
    }
  }
}
</script>
