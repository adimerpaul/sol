<template>
  <q-page class="q-pa-md bg-grey-2">
    <q-card flat bordered class="bg-white">
      <q-card-section>
        <div class="text-h6 text-weight-bold">
          Asignacion de Jefes por Recinto (Mapa)
          <q-btn icon="refresh" round dense flat class="q-ml-sm" @click="load" />
        </div>
        <div class="text-caption text-grey-7">Selecciona un recinto en el mapa y asigna sus jefes</div>

        <div class="row items-center q-col-gutter-sm q-mt-sm">
          <div class="col-auto">
            <q-chip color="primary" text-color="white" outline>
              Total de recintos: {{ recintos.length }}
            </q-chip>
          </div>

          <div class="col-auto">
            <q-chip color="primary" text-color="white" outline>
              Total de jefes: {{ jefes.length }}
            </q-chip>
          </div>

          <div class="col-auto">
            <q-chip color="negative" text-color="white" outline>
              Recintos sin jefe: {{ recintos.filter(r => !r.jefe?.length).length }}
            </q-chip>
          </div>
          <div class="col-auto">
            <q-chip color="orange" text-color="white" outline>
              Recintos con mesas faltantes: {{ recintos.filter(r => r.mesas_faltan > 0).length }}
            </q-chip>
          </div>
        </div>

        <div class="row q-col-gutter-sm q-mt-sm">
          <div class="col-12 col-md-6">
            <q-select
              v-model="recintoPick"
              :options="recintosOptions"
              use-input
              fill-input
              hide-selected
              input-debounce="250"
              dense
              outlined
              label="Buscar recinto rapido..."
              option-label="label"
              option-value="value"
              emit-value
              map-options
              clearable
              @filter="filterRecintos"
              @update:model-value="onPickRecinto"
            >
              <template v-slot:prepend>
                <q-icon name="search" />
              </template>

              <template v-slot:option="scope">
                <q-item v-bind="scope.itemProps">
                  <q-item-section>
                    <q-item-label class="text-weight-medium">
                      {{ scope.opt.nombre }}
                    </q-item-label>
                    <q-item-label caption>
                      {{ scope.opt.jefeNombre }}
                    </q-item-label>
                    <q-item-label caption>
                      Mesas: {{ scope.opt.mesas_total }} · Asignadas: {{ scope.opt.mesas_asignadas }}
                    </q-item-label>
                  </q-item-section>
                  <q-item-section side>
                    <q-badge outline :color="scope.opt.okDelegados ? 'positive' : 'negative'">
                      {{ scope.opt.okDelegados ? 'completo' : 'falta' }}
                    </q-badge>
                  </q-item-section>
                </q-item>
              </template>
            </q-select>
          </div>
        </div>
      </q-card-section>

      <q-separator />

      <q-card-section>
        <div class="row q-col-gutter-sm">
          <div class="col-12 col-md-8">
            <RecintosMapaMarkers
              ref="mapMarkersRef"
              :markers="recintos"
              :focus-recinto="focusRecinto"
              @select="onSelectRecinto"
            />
          </div>

          <div class="col-12 col-md-4">
            <div v-if="!selected">
              <q-banner dense class="bg-grey-2">Selecciona un recinto en el mapa</q-banner>
            </div>

            <div v-else>
              <div class="text-subtitle1 text-weight-bold">{{ selected.nombre }}</div>

              <q-badge class="q-mt-xs" outline :color="selected?.jefe?.length ? 'positive' : 'negative'">
                {{ selected?.jefe?.length ? `${selected.jefe.length} jefe(s) asignado(s)` : 'Sin jefe asignado' }}
              </q-badge>
              <div class="row q-gutter-xs q-mt-xs">
                <q-badge outline color="grey-8">Mesas: {{ selected.mesas_total }}</q-badge>
                <q-badge outline color="primary">Asignadas: {{ selected.mesas_asignadas }}</q-badge>
                <q-badge outline :color="selected.mesas_faltan > 0 ? 'negative' : 'positive'">
                  {{ selected.mesas_faltan > 0 ? `Faltan ${selected.mesas_faltan}` : 'Delegados OK' }}
                </q-badge>
              </div>

              <q-card flat bordered class="q-mt-md bg-grey-1">
                <q-card-section class="q-pb-sm">
                  <div class="text-subtitle2 text-weight-medium">Jefes asignados</div>
                </q-card-section>

                <q-card-section class="q-pt-none">
                  <q-markup-table dense flat bordered separator="horizontal">
                    <thead>
                    <tr>
                      <th class="text-left">Jefe</th>
                      <th class="text-left">Celular</th>
                      <th class="text-right" style="width: 90px;">Quitar</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="jefe in (selected.jefe || [])" :key="jefe.id">
                      <td>
                        <div class="text-weight-medium">{{ jefe.name }}</div>
                        <div class="text-caption text-grey-7">{{ jefe.username }}</div>
                      </td>
                      <td>{{ jefe.celular || 'Sin celular' }}</td>
                      <td class="text-right">
                        <q-btn
                          flat
                          dense
                          color="negative"
                          icon="delete"
                          label="Quitar"
                          no-caps
                          :loading="savingJefe && pendingJefeActionId === jefe.id"
                          @click="confirmRemoveJefe(jefe)"
                        />
                      </td>
                    </tr>
                    <tr v-if="!(selected.jefe || []).length">
                      <td colspan="3" class="text-center text-grey-7 q-pa-md">
                        Sin jefes asignados
                      </td>
                    </tr>
                    </tbody>
                  </q-markup-table>
                </q-card-section>
              </q-card>

              <div class="row q-col-gutter-sm q-mt-md">
                <div class="col-12 col-sm-8">
                  <q-select
                    v-model="jefeIdToAdd"
                    :options="jefesOptions"
                    option-label="label"
                    option-value="value"
                    emit-value
                    map-options
                    use-input
                    input-debounce="0"
                    clearable
                    dense
                    outlined
                    label="Jefe de Recinto"
                    @filter="filterJefes"
                  />
                </div>
                <div class="col-12 col-sm-4">
                  <q-btn
                    color="primary"
                    icon="add"
                    label="Agregar"
                    class="full-width"
                    no-caps
                    :disable="!jefeIdToAdd"
                    :loading="savingJefe && pendingJefeActionId === jefeIdToAdd"
                    @click="addJefe"
                  />
                </div>
              </div>

              <q-separator class="q-my-md" />

              <div class="row items-center q-mb-sm">
                <div class="text-subtitle2 text-weight-medium">Mesas</div>
                <q-space />
                <q-btn icon="refresh" round dense flat :loading="loadingMesas" @click="loadMesas" />
              </div>

              <q-banner v-if="loadingMesas" dense class="bg-grey-2 q-mb-sm">
                Cargando mesas...
              </q-banner>

              <q-banner v-else-if="mesas.length === 0" dense class="bg-grey-2 q-mb-sm">
                No hay mesas para este recinto.
              </q-banner>

              <q-list v-else bordered separator class="rounded-borders">
                <q-item v-for="mesa in mesas" :key="mesa.id">
                  <q-item-section>
                    <q-item-label class="text-weight-medium">
                      Mesa {{ mesa.numero_mesa }}
                    </q-item-label>
                    <q-item-label caption>
                      {{ mesa.delegado ? `${mesa.delegado.name} (${mesa.delegado.username})` : 'Sin delegado asignado' }}
                    </q-item-label>
                    <q-item-label caption>
                      Estado: {{ mesa.estado || (mesa.delegado_id ? 'ASIGNADA' : 'PENDIENTE') }}
                    </q-item-label>

                    <div class="row q-col-gutter-sm q-mt-sm">
                      <div class="col-12">
                        <q-select
                          v-model="mesa.delegadoDraftId"
                          :options="delegadosOptions"
                          option-label="label"
                          option-value="value"
                          emit-value
                          map-options
                          use-input
                          input-debounce="0"
                          clearable
                          dense
                          outlined
                          label="Delegado de Mesa"
                          @filter="filterDelegados"
                        />
                      </div>
                      <div class="col-6">
                        <q-btn
                          color="primary"
                          label="Guardar delegado"
                          no-caps
                          class="full-width"
                          :loading="savingMesaId === mesa.id"
                          @click="saveMesa(mesa)"
                        />
                      </div>
                      <div class="col-6">
                        <q-btn
                          flat
                          color="negative"
                          label="Quitar"
                          no-caps
                          class="full-width"
                          :disable="!mesa.delegado_id && !mesa.delegadoDraftId"
                          @click="clearMesa(mesa)"
                        />
                      </div>
                    </div>
                  </q-item-section>
                </q-item>
              </q-list>
            </div>
          </div>
        </div>
      </q-card-section>
    </q-card>
  </q-page>
</template>

<script>
import RecintosMapaMarkers from 'components/RecintosMapaMarkers.vue'

export default {
  name: 'AdminRecintoJefeMapa',
  components: { RecintosMapaMarkers },

  data () {
    return {
      recintos: [],
      jefes: [],
      jefesOptions: [],
      jefesOptionsAll: [],
      delegadosOptions: [],
      delegadosOptionsAll: [],
      selected: null,
      jefeIdToAdd: null,
      savingJefe: false,
      pendingJefeActionId: null,
      mesas: [],
      loadingMesas: false,
      savingMesaId: null,
      recintoPick: null,
      recintosOptions: [],
      focusRecinto: null
    }
  },

  mounted () {
    this.load()
  },

  methods: {
    async load () {
      const [r, j, d] = await Promise.all([
        this.$axios.get('admin/mapa-recintos/recintos'),
        this.$axios.get('admin/mapa-recintos/jefes'),
        this.$axios.get('admin/mesas/options/delegados')
      ])

      this.recintos = (Array.isArray(r.data) ? r.data : []).map(this.normalizeRecinto)
      this.jefes = Array.isArray(j.data) ? j.data : []
      this.jefesOptionsAll = this.buildJefesOptions(this.jefes)
      this.jefesOptions = this.jefesOptionsAll
      this.delegadosOptionsAll = (Array.isArray(d.data) ? d.data : []).map(item => ({
        value: item.id,
        label: `${item.name} (${item.username})`
      }))
      this.delegadosOptions = this.delegadosOptionsAll
      this.recintosOptions = this.buildOptions(this.recintos)

      if (this.selected?.id) {
        const again = (this.recintos || []).find(x => x.id === this.selected.id)
        if (again) {
          await this.onSelectRecinto(again)
        }
      }
    },

    normalizeRecinto (recinto) {
      return {
        ...recinto,
        jefe: Array.isArray(recinto?.jefe) ? recinto.jefe : []
      }
    },

    buildJefesOptions (list) {
      return (list || []).map(j => ({
        value: j.id,
        label: `${j.name || '-'} · ${j.celular || 'Sin celular'}`,
        search: `${j.name || ''} ${j.username || ''} ${j.celular || ''}`.toLowerCase()
      }))
    },

    buildOptions (list) {
      return (list || []).map(x => {
        const jefes = Array.isArray(x?.jefe) ? x.jefe : []
        const jefeNombre = jefes.length
          ? jefes.map(j => `${j.name} (${j.celular || j.username || 'sin dato'})`).join(', ')
          : 'Sin jefe asignado'
        const tieneJefe = jefes.length > 0
        const mesasTotal = Number(x?.mesas_total || 0)
        const mesasAsignadas = Number(x?.mesas_asignadas || 0)
        const okDelegados = tieneJefe && (mesasTotal === 0 || mesasAsignadas >= mesasTotal)

        return {
          label: `${x.nombre} - ${jefeNombre}`,
          value: x.id,
          nombre: x.nombre,
          jefeNombre,
          tieneJefe,
          mesas_total: mesasTotal,
          mesas_asignadas: mesasAsignadas,
          okDelegados
        }
      })
    },

    filterRecintos (val, update) {
      update(() => {
        const needle = (val || '').toLowerCase().trim()
        const base = this.buildOptions(this.recintos)

        if (!needle) {
          this.recintosOptions = base
          return
        }

        this.recintosOptions = base.filter(o =>
          o.nombre.toLowerCase().includes(needle) ||
          o.jefeNombre.toLowerCase().includes(needle)
        )
      })
    },

    filterJefes (val, update) {
      update(() => {
        const needle = String(val || '').toLowerCase().trim()
        if (!needle) {
          this.jefesOptions = this.availableJefesOptions()
          return
        }

        this.jefesOptions = this.availableJefesOptions().filter(j =>
          String(j.search || '').includes(needle)
        )
      })
    },

    availableJefesOptions () {
      const assignedIds = new Set((this.selected?.jefe || []).map(j => j.id))
      return (this.jefesOptionsAll || []).filter(j => !assignedIds.has(j.value))
    },

    filterDelegados (val, update) {
      update(() => {
        const needle = String(val || '').toLowerCase().trim()
        if (!needle) {
          this.delegadosOptions = this.delegadosOptionsAll
          return
        }

        this.delegadosOptions = (this.delegadosOptionsAll || []).filter(d =>
          String(d.label || '').toLowerCase().includes(needle)
        )
      })
    },

    async onPickRecinto (id) {
      if (!id) {
        this.focusRecinto = null
        return
      }

      const r = (this.recintos || []).find(x => x.id === id)
      if (!r) return

      await this.onSelectRecinto(r)

      this.focusRecinto = {
        id: r.id,
        latitud: r.latitud,
        longitud: r.longitud
      }
    },

    async onSelectRecinto (recinto) {
      this.selected = this.normalizeRecinto(recinto)
      this.jefeIdToAdd = null
      this.jefesOptions = this.availableJefesOptions()
      this.recintoPick = recinto.id
      await this.loadMesas()
    },

    async persistJefes (ids, successMessage) {
      this.savingJefe = true
      try {
        await this.$axios.put(
          `admin/mapa-recintos/recintos/${this.selected.id}/jefe`,
          { jefe_ids: ids }
        )
        this.$alert.success(successMessage)
        await this.load()
      } catch (e) {
        this.$alert?.error(e.response?.data?.message || 'No se pudo guardar jefes')
      } finally {
        this.savingJefe = false
        this.pendingJefeActionId = null
      }
    },

    async addJefe () {
      if (!this.jefeIdToAdd || !this.selected?.id) return
      const ids = [...new Set([...(this.selected?.jefe || []).map(j => j.id), this.jefeIdToAdd])]
      this.pendingJefeActionId = this.jefeIdToAdd
      this.jefeIdToAdd = null
      await this.persistJefes(ids, 'Jefe agregado')
    },

    confirmRemoveJefe (jefe) {
      this.$q.dialog({
        title: 'Confirmar',
        message: `Quitar a ${jefe.name} de este recinto?`,
        cancel: true,
        persistent: true
      }).onOk(() => {
        this.removeJefe(jefe)
      })
    },

    async removeJefe (jefe) {
      if (!this.selected?.id) return
      this.pendingJefeActionId = jefe.id
      const ids = (this.selected?.jefe || []).map(j => j.id).filter(id => id !== jefe.id)
      await this.persistJefes(ids, 'Jefe quitado')
    },

    async loadMesas () {
      if (!this.selected?.id) {
        this.mesas = []
        return
      }

      this.loadingMesas = true
      try {
        const response = await this.$axios.get('admin/mesas', {
          params: {
            recinto_id: this.selected.id,
            all: 1,
            per_page: 500
          }
        })

        this.mesas = (Array.isArray(response.data?.data) ? response.data.data : []).map(m => ({
          ...m,
          delegadoDraftId: m.delegado_id ?? null
        }))
        this.delegadosOptions = this.delegadosOptionsAll
      } catch (e) {
        this.mesas = []
        this.$alert?.error(e.response?.data?.message || 'No se pudo cargar mesas del recinto')
      } finally {
        this.loadingMesas = false
      }
    },

    async saveMesa (mesa) {
      this.savingMesaId = mesa.id
      try {
        await this.$axios.put(`admin/mesas/${mesa.id}/delegado`, {
          delegado_id: mesa.delegadoDraftId || null
        })
        this.$alert.success(mesa.delegadoDraftId ? 'Delegado asignado' : 'Mesa liberada')
        await this.load()
      } catch (e) {
        this.$alert?.error(e.response?.data?.message || 'No se pudo guardar delegado')
      } finally {
        this.savingMesaId = null
      }
    },

    async clearMesa (mesa) {
      mesa.delegadoDraftId = null
      await this.saveMesa(mesa)
    }
  }
}
</script>
