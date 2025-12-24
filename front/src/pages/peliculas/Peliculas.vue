<template>
  <q-page class="q-pa-md">
    <q-card flat bordered class="q-mb-md">
      <q-card-section class="row items-center q-col-gutter-sm">
        <div class="col-12 col-md">
          <div class="text-h6 text-weight-bold">Películas · Multicines Plaza</div>
          <div class="text-caption text-grey-7">Buscar en TMDB y guardar en tu base de datos.</div>
        </div>

        <div class="col-12 col-md-4">
          <q-tabs v-model="tab" dense align="right" active-color="primary" indicator-color="primary">
            <q-tab name="buscar" icon="search" label="Buscar" />
            <q-tab name="mias" icon="movie" label="Mis Películas" />
          </q-tabs>
        </div>
      </q-card-section>
    </q-card>

    <!-- ===== TAB BUSCAR ===== -->
    <div v-if="tab === 'buscar'">
      <q-form @submit.prevent="tmdbSearch">
      <q-card flat bordered class="q-mb-md">
        <q-card-section class="row items-center q-col-gutter-sm">
          <div class="col-12 col-md-6">
            <q-input v-model="q" outlined dense debounce="400" label="Buscar película (es-MX)" required>
              <template #prepend><q-icon name="search" /></template>
            </q-input>
          </div>
          <div class="col-auto">
            <q-btn color="primary" no-caps icon="search" label="Buscar" type="submit" :loading="loadingSearch"/>
          </div>
          <q-space />
          <div class="col-auto text-caption text-grey-7">
            Resultados: <b>{{ tmdbResults.length }}</b>
          </div>
        </q-card-section>
      </q-card>
      </q-form>
      <q-table
        flat bordered dense
        :rows="tmdbResults"
        :columns="tmdbColumns"
        row-key="id"
        :rows-per-page-options="[0]"
        @row-click="onPickTmdb"
      >
        <template #body-cell-poster="props">
          <q-td :props="props">
            <q-img
              v-if="props.row.poster_path"
              :src="tmdbImg(props.row.poster_path)"
              style="width:48px; border-radius:8px"
            />
            <q-badge v-else color="grey-5" outline>Sin poster</q-badge>
<!--            <pre>{{tmdbImg(props.row.poster_path)}}</pre>-->
          </q-td>
        </template>

        <template #body-cell-title="props">
          <q-td :props="props">
            <div class="text-weight-medium">{{ props.row.title }}</div>
            <div class="text-caption text-grey-7 ellipsis">
              {{ props.row.release_date || 'Sin fecha' }}
            </div>
          </q-td>
        </template>
      </q-table>
    </div>

    <!-- ===== TAB MIS PELÍCULAS ===== -->
    <div v-else>
      <q-card flat bordered class="q-mb-md">
        <q-card-section class="row items-center q-col-gutter-sm">
          <div class="col-12 col-md-4">
            <q-input v-model="filters.search" outlined dense debounce="400" label="Buscar (título / tmdb_id)"
                     @update:model-value="peliculasGet" clearable>
              <template #prepend><q-icon name="search" /></template>
            </q-input>
          </div>
          <div class="col-12 col-md-3">
            <q-select
              v-model="filters.status"
              outlined dense
              label="Estado"
              :options="['', 'Publicado', 'No Publicado']"
              emit-value map-options
              @update:model-value="peliculasGet"
            />
          </div>
          <q-space />
          <div class="col-auto">
            <q-btn color="primary" no-caps icon="refresh" label="Actualizar" @click="peliculasGet" :loading="loadingDb"/>
          </div>
        </q-card-section>
      </q-card>

      <q-table
        title="Mis Películas"
        flat bordered dense
        :rows="peliculas"
        :columns="dbColumns"
        row-key="id"
        :filter="filters.search"
        :rows-per-page-options="[0]"
      >
        <template #body-cell-poster_path="props">
          <q-td :props="props">
            <q-img
              v-if="props.row.poster_path"
              :src="tmdbImg(props.row.poster_path)"
              style="width:48px; border-radius:8px"
            />
            <q-badge v-else color="grey-5" outline>Sin poster</q-badge>
          </q-td>
        </template>

        <template #body-cell-status="props">
          <q-td :props="props">
            <q-chip
              dense
              :color="props.row.status === 'PUBLICADO' ? 'positive' : 'grey-6'"
              text-color="white"
            >
              {{ props.row.status }}
            </q-chip>
          </q-td>
        </template>

        <template #body-cell-actions="props">
          <q-td :props="props">
            <q-btn-dropdown dense no-caps color="primary" label="Opciones" size="10px">
              <q-list>
                <q-item clickable v-close-popup @click="editOpen(props.row)">
                  <q-item-section avatar><q-icon name="edit" /></q-item-section>
                  <q-item-section><q-item-label>Editar</q-item-label></q-item-section>
                </q-item>
                <q-item clickable v-close-popup @click="del(props.row.id)">
                  <q-item-section avatar><q-icon name="delete" /></q-item-section>
                  <q-item-section><q-item-label>Eliminar</q-item-label></q-item-section>
                </q-item>
              </q-list>
            </q-btn-dropdown>
          </q-td>
        </template>
      </q-table>
    </div>

    <!-- ===== DIALOG DETALLE TMDB ===== -->
    <!-- ===== DIALOG DETALLE TMDB ===== -->
    <q-dialog v-model="tmdbDialog" persistent>
      <q-card style="width: 920px; max-width: 95vw">
        <q-card-section class="row items-center">
          <div class="text-weight-bold">
            {{ picked?.title || 'Detalle' }}
            <span class="text-caption text-grey-7">· TMDB {{ picked?.id }}</span>
          </div>
          <q-space />
          <q-btn icon="close" flat round dense @click="tmdbDialog=false" />
        </q-card-section>

        <q-separator />

        <q-card-section class="row q-col-gutter-md">
          <!-- ===== LEFT: EDITABLE ===== -->
          <div class="col-12 col-md-4">
            <div class="text-subtitle2 text-weight-bold q-mb-sm">
              Editar antes de guardar
            </div>

            <q-input
              v-model="draft.title"
              dense
              outlined
              label="Título"
            />

            <q-input
              v-model="draft.release_date"
              dense
              outlined
              type="date"
              label="Estreno"
              class="q-mt-sm"
            />

            <q-select
              v-model="draft.status"
              dense
              outlined
              label="Estado en mi sistema"
              :options="['Publicado', 'No Publicado']"
              class="q-mt-sm"
            />

            <!-- Poster editable -->
            <div class="q-mt-md text-caption text-grey-7">Poster</div>
            <div class="row items-center q-col-gutter-sm">
              <div class="col-auto">
                <q-img
                  v-if="draft.poster_path"
                  :src="tmdbImg(draft.poster_path)"
                  style="width:84px; height:126px; border-radius:12px"
                />
                <q-badge v-else color="grey-5" outline>Sin poster</q-badge>
              </div>
              <div class="col">
                <q-input
                  v-model="draft.poster_path"
                  dense
                  outlined
                  label="poster_path (TMDB)"
                  hint="Ej: /abc123.jpg"
                />
              </div>
            </div>

            <!-- Backdrop editable -->
            <div class="q-mt-md text-caption text-grey-7">Backdrop</div>
            <q-img
              v-if="draft.backdrop_path"
              :src="tmdbImg(draft.backdrop_path)"
              style="width:100%; height:120px; border-radius:12px"
            />
            <q-badge v-else color="grey-5" outline>Sin backdrop</q-badge>

            <q-input
              v-model="draft.backdrop_path"
              dense
              outlined
              label="backdrop_path (TMDB)"
              hint="Ej: /xyz987.jpg"
              class="q-mt-sm"
            />

            <q-btn
              class="full-width q-mt-md"
              color="primary"
              no-caps
              icon="save"
              label="Guardar en mi BD"
              :loading="loadingSave"
              @click="saveFromTmdb"
            />
          </div>

          <!-- ===== RIGHT: INFO + TRAILERS ===== -->
          <div class="col-12 col-md-8">
            <div class="text-subtitle1 text-weight-medium q-mb-xs">Descripción</div>
            <div class="text-body2 text-grey-8">
              {{ pickedDetail?.overview || 'Sin descripción' }}
              <!-- si quieres seguir viendo el JSON -->
<!--              <pre class="q-mt-sm">{{ pickedDetail }}</pre>-->
            </div>

            <div class="q-mt-md">
              <div class="text-subtitle1 text-weight-medium q-mb-xs">Tráilers (es-MX)</div>

              <q-list bordered separator dense>
                <q-item v-for="v in pickedVideos" :key="v.id">
                  <q-item-section>
                    <q-item-label class="text-weight-medium">{{ v.name }}</q-item-label>
                    <q-item-label caption>
                      {{ v.type }} · {{ v.iso_3166_1 }} · {{ v.site }} · {{ v.published_at?.slice(0,10) }}
                    </q-item-label>
                  </q-item-section>

                  <q-item-section side>
                    <q-btn
                      v-if="v.site === 'YouTube'"
                      flat
                      color="primary"
                      no-caps
                      icon="open_in_new"
                      label="Ver"
                      @click="openYoutube(v.key)"
                    />
                    <q-radio v-model="selectedVideo" :val="v.key" label="Seleccionar trailer" />
                  </q-item-section>
                </q-item>

                <q-item v-if="!pickedVideos.length">
                  <q-item-section>
                    <q-item-label class="text-grey-7">No hay videos es-MX.</q-item-label>
                  </q-item-section>
                </q-item>
              </q-list>
            </div>
          </div>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- ===== DIALOG EDITAR BD ===== -->
    <q-dialog v-model="editDialog" persistent>
      <q-card style="width: 520px; max-width: 95vw">
        <q-card-section class="row items-center">
          <div class="text-weight-bold">Editar película</div>
          <q-space />
          <q-btn icon="close" flat round dense @click="editDialog=false" />
        </q-card-section>

        <q-card-section class="q-pt-none">
          <q-form @submit="editSave">
            <q-input v-model="editRow.title" dense outlined label="Título" />
            <q-select
              v-model="editRow.status"
              dense outlined
              label="Estado"
              :options="['Publicado', 'No Publicado']"
              class="q-mt-sm"
            />
            <q-input v-model="editRow.trailer_url" dense outlined label="Trailer URL (opcional)" class="q-mt-sm" />

            <div class="text-right q-mt-md">
              <q-btn color="negative" no-caps label="Cancelar" @click="editDialog=false" />
              <q-btn color="primary" no-caps label="Guardar" type="submit" class="q-ml-sm" :loading="loadingEdit" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script>
export default {
  name: 'PeliculasPage',
  data () {
    return {
      tab: 'buscar',

      // buscar TMDB
      q: '',
      loadingSearch: false,
      tmdbResults: [],
      selectedVideo: null,
      tmdbColumns: [
        { name: 'poster', label: '', align: 'left' },
        { name: 'title', label: 'Título', align: 'left' },
        { name: 'id', label: 'TMDB ID', field: 'id', align: 'left' },
        { name: 'release_date', label: 'Estreno', field: 'release_date', align: 'left' },
        { name: 'vote_average', label: 'Rating', field: 'vote_average', align: 'left' },
      ],
      draft: {
        title: '',
        poster_path: '',
        backdrop_path: '',
        release_date: '',
        status: 'No Publicado'
      },

      // detalle seleccionado
      tmdbDialog: false,
      picked: null,
      pickedDetail: null,
      pickedVideos: [],
      saveStatus: 'No Publicado',
      loadingSave: false,

      // mis películas
      peliculas: [],
      loadingDb: false,
      filters: { search: '', status: '' },
      dbColumns: [
        { name: 'actions', label: 'Acciones', align: 'center' },
        { name: 'poster_path', label: '', field: 'poster_path', align: 'left' },
        { name: 'title', label: 'Título', field: 'title', align: 'left' },
        { name: 'tmdb_id', label: 'TMDB', field: 'tmdb_id', align: 'left' },
        { name: 'release_date', label: 'Estreno', field: 'release_date', align: 'left' },
        { name: 'status', label: 'Estado', field: 'status', align: 'left' },
      ],

      // editar
      editDialog: false,
      editRow: {},
      loadingEdit: false,
    }
  },

  mounted () {
    this.peliculasGet()
    this.q = 'Avatar'  // ejemplo inicial
    this.tmdbSearch()
  },

  methods: {
    tmdbImg (path) {
      return `https://image.tmdb.org/t/p/w500${path}`
    },

    async tmdbSearch () {
      if (!this.q) return
      this.loadingSearch = true
      try {
        const res = await this.$axios.get('tmdb/search', { params: { query: this.q } })
        this.tmdbResults = res.data?.results || []
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'Error buscando en TMDB')
      } finally {
        this.loadingSearch = false
      }
    },

    async onPickTmdb (evt, row) {
      this.picked = row
      this.saveStatus = 'No Publicado'
      this.tmdbDialog = true
      await this.loadPickedDetail(row.id)
    },

    async loadPickedDetail (tmdbId) {
      this.pickedDetail = null
      this.selectedVideo = null
      this.pickedVideos = []
      try {
        this.loadingSave = true
        const detail = await this.$axios.get(`tmdb/movie/${tmdbId}`).then(r => r.data)
        const videos = await this.$axios.get(`tmdb/movie/${tmdbId}/videos`).then(r => r.data)

        this.pickedDetail = detail

        // ✅ llenar borrador editable con valores por defecto
        this.draft = {
          title: detail?.title || '',
          poster_path: detail?.poster_path || '',
          backdrop_path: detail?.backdrop_path || '',
          release_date: detail?.release_date || '',
          status: this.saveStatus || 'No Publicado'
        }

        this.pickedVideos = (videos?.results || [])
          .filter(v => v.iso_3166_1 === 'MX')

        this.loadingSave = false
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'Error cargando detalle/videos')
        this.loadingSave = false
      }
    },

    openYoutube (key) {
      window.open(`https://www.youtube.com/watch?v=${key}`, '_blank')
    },

    async saveFromTmdb () {
      if (!this.picked?.id) return
      this.loadingSave = true
      try {
        await this.$axios.post('peliculas/from-tmdb', {
          tmdb_id: this.picked.id,
          status: this.draft.status,
          trailer_key: this.selectedVideo,

          // ✅ overrides editables
          title: this.draft.title,
          poster_path: this.draft.poster_path,
          backdrop_path: this.draft.backdrop_path,
          release_date: this.draft.release_date
        })

        this.$alert.success('Película guardada/actualizada')
        this.tmdbDialog = false
        this.tab = 'mias'
        this.peliculasGet()
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'No se pudo guardar')
      } finally {
        this.loadingSave = false
      }
    },

    async peliculasGet () {
      this.loadingDb = true
      try {
        const res = await this.$axios.get('peliculas', { params: this.filters })
        this.peliculas = res.data || []
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'Error cargando películas')
      } finally {
        this.loadingDb = false
      }
    },

    editOpen (row) {
      this.editRow = { ...row }
      this.editDialog = true
    },

    async editSave () {
      this.loadingEdit = true
      try {
        await this.$axios.put(`peliculas/${this.editRow.id}`, {
          title: this.editRow.title,
          status: this.editRow.status,
          trailer_url: this.editRow.trailer_url,
        })
        this.$alert.success('Película actualizada')
        this.editDialog = false
        this.peliculasGet()
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'No se pudo actualizar')
      } finally {
        this.loadingEdit = false
      }
    },

    del (id) {
      this.$alert.dialog('¿Eliminar esta película?')
        .onOk(async () => {
          try {
            await this.$axios.delete(`peliculas/${id}`)
            this.$alert.success('Eliminada')
            this.peliculasGet()
          } catch (e) {
            this.$alert.error(e.response?.data?.message || 'No se pudo eliminar')
          }
        })
    },
  }
}
</script>
