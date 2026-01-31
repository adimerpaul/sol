<template>
  <q-page class="q-pa-md bg-grey-2">
    <q-card flat bordered class="bg-white">

      <q-card-section>
        <div class="text-h6 text-weight-bold">
          Asignación de Jefes por Recinto (Mapa)
        </div>
        <div class="text-caption text-grey-7">
          Selecciona un recinto en el mapa y asigna su jefe
        </div>
      </q-card-section>

      <q-separator />

      <q-card-section>
        <div class="row">
          <div class="col-12 col-md-8">
<!--            <RecintoLeafletPicker-->
<!--              :markers="recintos"-->
<!--              @select="onSelectRecinto"-->
<!--            />-->
            <RecintoLeafletPicker
              :markers="recintos"
              marker-color="blue"
              @select="onSelectRecinto"
            />
          </div>

          <div class="col-12 col-md-4">
            <div v-if="!selected">
              <q-banner dense class="bg-grey-2">
                Selecciona un recinto en el mapa
              </q-banner>
            </div>

            <div v-else>
              <div class="text-subtitle1 text-weight-bold">
                {{ selected.nombre }}
              </div>

              <q-select
                v-model="jefeId"
                :options="jefes"
                option-label="name"
                option-value="id"
                emit-value
                map-options
                label="Jefe de Recinto"
                dense outlined
              />

              <q-btn
                color="primary"
                icon="save"
                label="Asignar"
                class="q-mt-sm full-width"
                @click="save"
              />
            </div>
          </div>
        </div>
      </q-card-section>

    </q-card>
  </q-page>
</template>
<script>
import RecintoLeafletPicker from "components/RecintoLeafletPicker.vue";

export default {
  name: 'AdminRecintoJefeMapa',
  components: {RecintoLeafletPicker},

  data () {
    return {
      recintos: [],
      jefes: [],
      selected: null,
      jefeId: null
    }
  },

  mounted () {
    this.load()
  },

  methods: {
    async load () {
      const [r,j] = await Promise.all([
        this.$axios.get('admin/mapa-recintos/recintos'),
        this.$axios.get('admin/mapa-recintos/jefes')
      ])
      this.recintos = r.data
      this.jefes = j.data
    },

    onSelectRecinto (recinto) {
      this.selected = recinto
      this.jefeId = recinto.jefe?.[0]?.id ?? null
    },

    async save () {
      await this.$axios.put(
        `admin/mapa-recintos/recintos/${this.selected.id}/jefe`,
        { jefe_id: this.jefeId }
      )
      this.$alert.success('Jefe asignado')
      await this.load()
    }
  }
}

</script>
