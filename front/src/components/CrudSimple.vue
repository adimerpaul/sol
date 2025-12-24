<template>
  <q-card flat bordered class="bg-white">

    <!-- HEADER -->
    <q-card-section class="row items-center q-col-gutter-sm">
      <div class="col-12">
        <div class="text-subtitle1 text-weight-bold row items-center">
          <q-icon name="fa-solid fa-list-check" class="q-mr-sm text-primary" />
          {{ title }}
        </div>
        <div class="text-caption text-grey-7">
          Gestión y búsqueda rápida • Paginación • CRUD
        </div>
      </div>

      <div class="col-12">
        <!-- FILTROS (SELECTS) -->
        <div v-if="selects && selects.length" class="row q-col-gutter-sm items-center">
          <div class="col-12 col-md-3" v-for="s in selects" :key="s.key">
            <q-select
              :model-value="localFilters[s.key]"
              :options="getSelectOptions(s)"
              :option-label="s.optionLabel || 'label'"
              :option-value="s.optionValue || 'value'"
              emit-value
              map-options
              dense
              outlined
              :label="s.label"
              :disable="!!s.disable"
              clearable
              use-input
              input-debounce="0"
              @filter="(val, update, abort) => onSelectFilter(s, val, update, abort)"
              @update:model-value="v => onSelectChange(s, v)"
            >
              <template v-slot:prepend>
                <q-icon v-if="s.icon" :name="s.icon" class="text-grey-7" />
              </template>
            </q-select>
          </div>

          <div class="col-12 col-md-3">
            <q-input
              v-model="search"
              dense
              outlined
              debounce="400"
              placeholder="Buscar..."
              @update:model-value="onSearch"
              clearable
            >
              <template v-slot:prepend>
                <q-icon name="search" class="text-grey-7" />
              </template>
            </q-input>
          </div>

          <div class="col-6 col-md-2">
            <q-btn
              outline
              color="primary"
              icon="refresh"
              label="Refrescar"
              no-caps
              :loading="loading"
              @click="load"
              class="full-width"
            />
          </div>

          <div class="col-6 col-md-2">
            <q-btn
              color="positive"
              icon="add"
              label="Nuevo"
              no-caps
              @click="openCreate"
              class="full-width"
            />
          </div>
        </div>
      </div>
    </q-card-section>

    <q-separator />

    <!-- TABLE -->
    <q-markup-table dense bordered class="q-ma-none">
      <thead>
      <tr>
        <th v-for="c in columns" :key="c" class="text-left">{{ c }}</th>
        <th width="140" class="text-right">Acciones</th>
      </tr>
      </thead>

      <tbody>
      <tr v-for="row in paginatedRows" :key="row.id">
        <td v-for="f in fields" :key="f">
          {{ resolve(row, f) }}
        </td>

        <td class="text-right">
          <q-btn flat dense round icon="edit" color="primary" @click="edit(row)" />
          <q-btn flat dense round icon="delete" color="negative" @click="remove(row)" />
        </td>
      </tr>

      <tr v-if="!loading && paginatedRows.length === 0">
        <td :colspan="columns.length + 1" class="text-center text-grey-7 q-pa-md">
          Sin resultados
        </td>
      </tr>
      </tbody>
    </q-markup-table>

    <q-separator />

    <!-- FOOTER -->
    <q-card-section class="row items-center justify-between">
      <div class="row items-center q-gutter-sm">
        <q-select
          v-model="perPage"
          :options="[5,10,15,20,30,50]"
          dense
          outlined
          label="Por página"
          style="width: 140px"
          @update:model-value="onPerPage"
        />
        <div class="text-caption text-grey-7">
          Total: {{ total }}
        </div>
      </div>

      <q-pagination
        v-model="page"
        :max="pages"
        max-pages="8"
        boundary-numbers
        direction-links
        @update:model-value="load"
      />
    </q-card-section>

    <q-inner-loading :showing="loading">
      <q-spinner />
    </q-inner-loading>
  </q-card>
</template>

<script>
export default {
  name: 'CrudSimple',

  props: {
    title: { type: String, default: '' },
    endpoint: { type: String, required: true },

    columns: { type: Array, default: () => [] },
    fields: { type: Array, default: () => [] },

    filters: { type: Object, default: () => ({}) },

    // [{ key,label,icon,options,optionLabel,optionValue,disable,resets:[] }]
    selects: { type: Array, default: () => [] }
  },

  emits: ['update:filters', 'filters-changed'],

  data () {
    return {
      loading: false,

      rows: [],
      search: '',

      // backend pagination
      page: 1,
      perPage: 10,
      meta: {
        total: 0,
        current_page: 1,
        last_page: 1,
        per_page: 10
      },

      localFilters: {},
      filteredSelectOptions: {}
    }
  },

  computed: {
    total () {
      return Number(this.meta?.total || 0)
    },

    pages () {
      return Number(this.meta?.last_page || 1) || 1
    },

    // ahora ya viene paginado del backend (NO slice)
    paginatedRows () {
      return Array.isArray(this.rows) ? this.rows : []
    }
  },

  watch: {
    filters: {
      deep: true,
      immediate: true,
      handler (v) {
        this.localFilters = { ...(v || {}) }
      }
    },

    selects: {
      deep: true,
      handler () {
        this.filteredSelectOptions = {}
      }
    }
  },

  mounted () {
    this.load()
  },

  methods: {
    resolve (obj, path) {
      return (path || '').split('.').reduce((o, i) => o?.[i], obj) ?? '-'
    },

    onSearch () {
      this.page = 1
      this.load()
    },

    onPerPage () {
      this.page = 1
      this.load()
    },

    getSelectOptions (s) {
      const key = s?.key
      if (!key) return s.options || []
      return this.filteredSelectOptions[key] || (s.options || [])
    },

    onSelectFilter (s, val, update, abort) {
      const key = s?.key
      if (!key) return abort()

      const options = Array.isArray(s.options) ? s.options : []
      const labelKey = s.optionLabel || 'label'

      update(() => {
        const needle = String(val || '').toLowerCase().trim()

        if (!needle) {
          this.filteredSelectOptions = { ...this.filteredSelectOptions, [key]: options }
          return
        }

        const filtered = options.filter(o => {
          const text = String(o?.[labelKey] ?? '').toLowerCase()
          return text.includes(needle)
        })

        this.filteredSelectOptions = { ...this.filteredSelectOptions, [key]: filtered }
      })
    },

    onSelectChange (selectDef, value) {
      const next = { ...(this.localFilters || {}), [selectDef.key]: value }

      // resets cascada
      const resets = selectDef.resets || []
      resets.forEach(k => { next[k] = null })

      this.localFilters = next
      this.page = 1

      this.$emit('update:filters', next)
      this.$emit('filters-changed', next)

      this.load()
    },

    async load () {
      this.loading = true
      try {
        const params = {
          search: this.search || '',
          ...(this.localFilters || {}),
          page: this.page,
          per_page: this.perPage
        }

        const r = await this.$axios.get(this.endpoint, { params })

        // Laravel paginate() => data + meta fields
        this.rows = Array.isArray(r.data?.data) ? r.data.data : []

        this.meta = {
          total: Number(r.data?.total || 0),
          current_page: Number(r.data?.current_page || this.page || 1),
          last_page: Number(r.data?.last_page || 1),
          per_page: Number(r.data?.per_page || this.perPage)
        }

        // sincroniza por si el backend devuelve diferente
        this.page = this.meta.current_page
        this.perPage = this.meta.per_page
      } catch (e) {
        this.rows = []
        this.meta = { total: 0, current_page: 1, last_page: 1, per_page: this.perPage }
        this.$q.notify({
          type: 'negative',
          message: e?.response?.data?.message || 'No se pudo cargar datos'
        })
      } finally {
        this.loading = false
      }
    },

    openCreate () {
      // exige filtros seleccionados (pais->recinto, etc.)
      if (this.selects && this.selects.length) {
        for (let i = 0; i < this.selects.length; i++) {
          const s = this.selects[i]
          if (s.key && (!this.localFilters || !this.localFilters[s.key])) {
            this.$q.notify({
              type: 'warning',
              message: `Seleccione ${s.label} antes de crear un nuevo ${this.title}`
            })
            return
          }
        }
      }

      this.$q.dialog({
        title: `Nuevo ${this.title}`.trim(),
        prompt: { model: '', label: 'Nombre', isValid: v => !!String(v || '').trim() },
        cancel: true,
        persistent: true
      }).onOk(async name => {
        try {
          await this.$axios.post(this.endpoint, { nombre: String(name).trim(), ...(this.localFilters || {}) })
          this.$q.notify({ type: 'positive', message: 'Creado' })
          this.load()
        } catch (e) {
          this.$q.notify({ type: 'negative', message: e?.response?.data?.message || 'No se pudo crear' })
        }
      })
    },

    edit (row) {
      const current = row?.nombre ?? row?.numero_mesa ?? ''
      const label = row?.numero_mesa !== undefined ? 'Número de mesa' : 'Nombre'

      this.$q.dialog({
        title: `Editar ${this.title}`.trim(),
        prompt: { model: String(current), label, isValid: v => !!String(v || '').trim() },
        cancel: true,
        persistent: true
      }).onOk(async value => {
        try {
          const payload = {}
          if (row?.numero_mesa !== undefined) payload.numero_mesa = String(value).trim()
          else payload.nombre = String(value).trim()

          await this.$axios.put(`${this.endpoint}/${row.id}`, payload)
          this.$q.notify({ type: 'positive', message: 'Actualizado' })
          this.load()
        } catch (e) {
          this.$q.notify({ type: 'negative', message: e?.response?.data?.message || 'No se pudo actualizar' })
        }
      })
    },

    remove (row) {
      const label = row?.nombre ?? row?.numero_mesa ?? ''
      this.$q.dialog({
        title: 'Eliminar',
        message: `¿Eliminar "${label}"?`,
        cancel: true,
        persistent: true
      }).onOk(async () => {
        try {
          await this.$axios.delete(`${this.endpoint}/${row.id}`)
          this.$q.notify({ type: 'positive', message: 'Eliminado' })
          this.load()
        } catch (e) {
          this.$q.notify({ type: 'negative', message: e?.response?.data?.message || 'No se pudo eliminar' })
        }
      })
    }
  }
}
</script>
