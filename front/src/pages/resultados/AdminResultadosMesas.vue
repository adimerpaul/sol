<template>
  <q-page class="q-pa-md bg-grey-2">
    <q-card flat bordered class="bg-white">

      <!-- HEADER + FILTROS -->
      <q-card-section class="row items-center q-col-gutter-sm">
        <div class="col-12 col-md-4">
          <div class="text-h6 text-weight-bold">Resultados · Mesas</div>
          <div class="text-caption text-grey-7">Asignación y carga de resultados por mesa.</div>
        </div>

        <div class="col-12 col-md-8">
          <div class="row q-col-gutter-sm justify-end items-center">

            <div class="col-12 col-sm-5">
              <q-select
                v-model="filters.recinto_id"
                :options="recintosOpt"
                option-label="nombre"
                option-value="id"
                emit-value map-options
                use-input input-debounce="200"
                dense outlined clearable
                label="Recinto"
                @filter="filterRecintos"
                @update:model-value="onPickRecinto"
              />
            </div>

            <div class="col-6 col-sm-2">
              <q-select
                v-model="filters.mesa_id"
                :options="mesasOpt"
                option-label="label"
                option-value="id"
                emit-value map-options
                dense outlined clearable
                label="Mesa"
                :disable="!filters.recinto_id || loadingMesas"
                :loading="loadingMesas"
              />
            </div>

            <div class="col-6 col-sm-2">
              <q-select
                v-model="filters.asignado"
                dense outlined
                label="Delegado"
                :options="asignadoOptions"
                emit-value map-options
              />
            </div>

            <div class="col-6 col-sm-2">
              <q-select
                v-model="filters.estado"
                dense outlined clearable
                label="Estado"
                :options="estadoOptions"
              />
            </div>

            <div class="col-6 col-sm-2">
              <q-select
                v-model="filters.con_resultado"
                dense outlined
                label="Resultado"
                :options="resultadoOptions"
                emit-value map-options
              />
            </div>

            <div class="col-12 col-sm-3">
              <q-btn
                color="primary"
                icon="search"
                label="Buscar"
                no-caps
                class="full-width"
                :loading="loading"
                @click="refresh"
              />
            </div>
            <div class="col-12 col-sm-3">
              <q-btn
                color="teal"
                icon="download"
                label="Traer TODO"
                no-caps
                class="full-width"
                :loading="loadingAll"
                @click="fetchAll"
              />
            </div>

          </div>
        </div>
      </q-card-section>

      <q-separator />

      <!-- CHIPS -->
      <q-card-section class="q-pt-sm q-pb-none">
        <div class="row items-center q-col-gutter-sm">
          <div class="col-auto"><q-chip outline color="primary">Total: {{ totalReal }}</q-chip></div>
          <div class="col-auto"><q-chip outline color="positive">Asignadas: {{ countAsignadas }}</q-chip></div>
          <div class="col-auto"><q-chip outline color="negative">Sin delegado: {{ countSinDelegado }}</q-chip></div>
          <div class="col-auto"><q-chip outline color="teal">Con resultado: {{ countConResultado }}</q-chip></div>

          <q-space />

          <div class="col-auto">
            <q-select
              v-model="rowsPerPage"
              dense outlined
              label="Filas"
              :options="rowsPerPageOptions"
              emit-value map-options
              style="width: 140px"
              @update:model-value="onChangeRowsPerPage"
            />
<!--            <pre>{{rowsPerPage}}</pre>-->
          </div>
        </div>

<!--        <q-banner v-if="truncated" dense class="bg-orange-2 text-black q-mt-sm">-->
<!--          OJO: existen <b>{{ totalReal }}</b> registros, pero por rendimiento solo se cargan <b>{{ maxCap }}</b>.-->
<!--          Ajusta filtros para reducir.-->
<!--        </q-banner>-->
      </q-card-section>

      <!-- TABLA (QMarkupTable) -->
      <q-card-section class="q-pt-sm">
        <q-markup-table dense flat bordered separator="horizontal" class="bg-white">
          <thead>
          <tr>
            <th class="text-center" style="width: 110px;">Acciones</th>
            <th class="text-left">Mesa</th>
            <th class="text-left">Delegado</th>
            <th class="text-left">Estado</th>
            <th class="text-left">Resultado</th>
            <th class="text-left">Control de Mesa</th>
          </tr>
          </thead>

          <tbody v-if="pagedRows.length">
          <tr v-for="(r,i) in pagedRows" :key="r.id">
            <td class="text-center">
              <q-btn-dropdown dense color="primary" :label="'Acciones ' + (r.id)" no-caps>
                <q-list>
                  <q-item clickable v-close-popup @click="openAsignar(r)">
                    <q-item-section avatar><q-icon name="person_add" /></q-item-section>
                    <q-item-section><q-item-label>Asignar delegado</q-item-label></q-item-section>
                  </q-item>
                  <q-item clickable v-close-popup @click="openResultado(r)">
                    <q-item-section avatar><q-icon name="how_to_vote" /></q-item-section>
                    <q-item-section><q-item-label>Registrar resultado</q-item-label></q-item-section>
                  </q-item>
                </q-list>
              </q-btn-dropdown>
            </td>
            <td class="text-left">
              <div class="text-weight-bold">Mesa {{ r.numero_mesa }}</div>
              <div class="text-caption text-grey-7">{{ r.recinto_nombre }}</div>
            </td>

            <td class="text-left">
              <q-badge outline :color="r.delegado ? 'positive' : 'negative'">
                {{ r.delegado ? (r.delegado.name + ' (' + r.delegado.username + ')') : 'SIN ASIGNAR' }}
              </q-badge>
            </td>

            <td class="text-left">
              <q-chip dense text-color="white" :color="colorEstado(r.estado)">
                {{ r.estado }}
              </q-chip>
            </td>

            <td class="text-left">
              <div class="row items-center q-gutter-xs">
                <q-badge outline :color="r.tiene_resultado ? 'teal' : 'grey-6'">
                  {{ r.tiene_resultado ? 'CON RESULTADO' : 'SIN RESULTADO' }}
                </q-badge>
                <q-chip v-if="r.tiene_resultado" dense color="primary" text-color="white">
                  Total: {{ r.total_votos }}
                </q-chip>
              </div>

              <div v-if="r.tiene_resultado" class="text-caption text-grey-7">
                Válidos: {{ r.total_validos }} · B: {{ r.total_blancos }} · N: {{ r.total_nulos }}
              </div>
            </td>

            <td class="text-left">
              <div class="row items-center q-gutter-xs">
                <q-chip dense size="11px" :color="b(r.aviso_antes)" text-color="white">Estoy presente</q-chip>
                <q-chip dense size="11px" :color="b(r.aviso_manana)" text-color="white">Mesa abierta</q-chip>
                <q-chip dense size="11px" :color="b(r.aviso_mediodia)" text-color="white">Tengo el acta</q-chip>
                <q-chip v-if="r.hora_apertura_mesa" dense size="11px" color="indigo" text-color="white">
                  {{ r.hora_apertura_mesa }}
                </q-chip>
              </div>
            </td>

          </tr>
          </tbody>

          <tbody v-else>
          <tr>
            <td colspan="6" class="text-center text-grey-7 q-pa-md">
              <q-icon name="info" class="q-mr-sm" />
              No hay datos para los filtros.
            </td>
          </tr>
          </tbody>
        </q-markup-table>

        <!-- PAGINACIÓN -->
        <div v-if="!showAll" class="row items-center q-mt-md">
          <div class="text-caption text-grey-7">
            Mostrando {{ fromRow }}-{{ toRow }} de {{ displayTotal }}
          </div>
          <q-space />
          <q-pagination
            v-model="page"
            :max="maxPage"
            boundary-numbers
            direction-links
            size="sm"
            @update:model-value="refresh"
          />
        </div>
      </q-card-section>
    </q-card>

    <!-- DIALOG: ASIGNAR -->
    <q-dialog v-model="dlgAsignar" persistent>
      <q-card style="width: 520px; max-width: 95vw;">
        <q-card-section class="row items-center">
          <div class="text-weight-bold">Asignar delegado</div>
          <q-space />
          <q-btn icon="close" flat round dense @click="dlgAsignar=false" />
        </q-card-section>

        <q-card-section class="q-pt-none">
          <div class="text-caption text-grey-7 q-mb-sm">
            {{ curMesa?.recinto_nombre }} · Mesa {{ curMesa?.numero_mesa }}
          </div>

          <q-select
            v-model="delegadoPick"
            :options="delegadosOpt"
            option-label="name"
            option-value="id"
            emit-value map-options
            use-input input-debounce="200"
            dense outlined
            label="Delegado de Mesa"
            clearable
          >
            <template v-slot:option="scope">
              <q-item v-bind="scope.itemProps">
                <q-item-section>
                  <q-item-label>{{ scope.opt.name }}</q-item-label>
                  <q-item-label caption>{{ scope.opt.username }}</q-item-label>
                </q-item-section>
              </q-item>
            </template>
          </q-select>

          <q-select
            v-model="estadoPick"
            class="q-mt-sm"
            dense outlined
            label="Estado"
            :options="estadoOptions"
          />
        </q-card-section>

        <q-card-actions align="right">
          <q-btn color="grey-7" label="Cancelar" no-caps @click="dlgAsignar=false" />
          <q-btn color="primary" label="Guardar" no-caps :disable="!delegadoPick" :loading="saving" @click="saveAsignar" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- DIALOG: RESULTADO -->
    <q-dialog v-model="dlgResultado" persistent>
      <q-card style="width: 980px; max-width: 98vw;">
        <q-card-section class="row items-center">
          <div class="text-weight-bold">Resultado de Mesa</div>
          <q-space />
          <q-btn icon="close" flat round dense @click="dlgResultado=false" />
        </q-card-section>

        <q-card-section class="q-pt-none">
          <div class="text-caption text-grey-7">
            {{ resMesa?.recinto_nombre }} · Mesa {{ resMesa?.numero_mesa }} ·
            Delegado: <span class="text-weight-medium">{{ resMesa?.delegado?.name || 'SIN ASIGNAR' }}</span>
          </div>

          <q-banner v-if="!resMesa?.delegado_id" dense class="bg-orange-2 text-black q-mt-sm">
            No puedes registrar resultados si la mesa no tiene delegado asignado.
          </q-banner>

          <div class="row q-col-gutter-sm q-mt-sm">
            <!-- PANEL IZQ -->
            <div class="col-12 col-md-4">
              <q-card flat bordered>
                <q-card-section class="text-weight-bold">Control de Mesa</q-card-section>
                <q-separator />
                <q-card-section class="q-gutter-sm">
                  <q-toggle v-model="resForm.aviso_antes" label="Estoy presente en mi mesa" />
                  <q-toggle v-model="resForm.aviso_manana" label="Abrí la mesa" />
                  <q-input
                    v-model="resForm.hora_apertura_mesa"
                    type="time"
                    dense
                    outlined
                    label="Hora de apertura (08:00 a 04:00)"
                    :disable="!resForm.aviso_manana"
                  />
                  <q-toggle v-model="resForm.aviso_mediodia" label="Tengo el acta en mi poder" />
                </q-card-section>
              </q-card>

              <q-card flat bordered class="q-mt-sm">
                <q-card-section class="text-weight-bold">Totales</q-card-section>
                <q-separator />
                <q-card-section class="row q-col-gutter-sm">
<!--                  <div class="col-12 text-caption text-grey-7">Gobernador</div>-->
<!--                  <div class="col-6">-->
<!--                    <q-input v-model.number="resForm.blancos_gobernador" type="number" dense outlined label="Blancos" min="0" />-->
<!--                  </div>-->
<!--                  <div class="col-6">-->
<!--                    <q-input v-model.number="resForm.nulos_gobernador" type="number" dense outlined label="Nulos" min="0" />-->
<!--                  </div>-->

<!--                  <div class="col-12 text-caption text-grey-7">Asambleísta Distrito  </div>-->
<!--                  <div class="col-6">-->
<!--                    <q-input v-model.number="resForm.blancos_asambleista_distrito" type="number" dense outlined label="Blancos" min="0" />-->
<!--                  </div>-->
<!--                  <div class="col-6">-->
<!--                    <q-input v-model.number="resForm.nulos_asambleista_distrito" type="number" dense outlined label="Nulos" min="0" />-->
<!--                  </div>-->

<!--                  <div class="col-12 text-caption text-grey-7">Asambleísta Población</div>-->
<!--                  <div class="col-6">-->
<!--                    <q-input v-model.number="resForm.blancos_asambleista_poblacion" type="number" dense outlined label="Blancos" min="0" />-->
<!--                  </div>-->
<!--                  <div class="col-6">-->
<!--                    <q-input v-model.number="resForm.nulos_asambleista_poblacion" type="number" dense outlined label="Nulos" min="0" />-->
<!--                  </div>-->



                  <div class="col-12 text-caption text-grey-7">Alcalde</div>
                  <div class="col-6">
                    <q-input v-model.number="resForm.blancos_alcalde" type="number" dense outlined label="Blancos" min="0" />
                  </div>
                  <div class="col-6">
                    <q-input v-model.number="resForm.nulos_alcalde" type="number" dense outlined label="Nulos" min="0" />
                  </div>
                  <div class="col-12 text-caption text-grey-7">Concejal</div>
                  <div class="col-6">
                    <q-input v-model.number="resForm.blancos_concejal" type="number" dense outlined label="Blancos" min="0" />
                  </div>
                  <div class="col-6">
                    <q-input v-model.number="resForm.nulos_concejal" type="number" dense outlined label="Nulos" min="0" />
                  </div>

                  <div class="col-12">
                    <q-input dense outlined label="Total válidos (auto)" :model-value="sumVotos" disable />
                  </div>
                  <div class="col-12">
                    <q-input dense outlined label="Total general (válidos + blancos + nulos)" :model-value="sumTotal" disable />
                  </div>
                </q-card-section>
              </q-card>

              <!-- FOTOS (compacto tipo Usuarios) -->
              <q-card flat bordered class="q-mt-sm">
                <q-card-section class="text-weight-bold">Fotos (10)</q-card-section>
                <q-separator />
                <q-card-section class="q-pt-sm">
                  <div class="row q-col-gutter-sm">
                    <div v-for="n in 10" :key="n" class="col-6">
                      <q-card flat bordered class="q-pa-xs">
<!--                        <pre>{{fotoPreview(n)}}</pre>-->
                        <q-img
                          v-if="fotoPreview(n)"
                          :src="fotoPreview(n)"
                          style="height: 110px"
                          spinner-color="primary"
                        />
                        <div v-else class="flex flex-center text-grey-6" style="height: 110px;">
                          <q-icon name="image" size="28px" />
                        </div>

                        <div class="q-mt-xs">
                          <q-file
                            v-model="fotos[`foto${n}`]"
                            dense outlined
                            accept="image/*"
                            label="Subir"
                            clearable
                          />
                        </div>
                      </q-card>
                    </div>
                  </div>
                </q-card-section>
              </q-card>
            </div>

            <!-- PANEL DER -->
            <div class="col-12 col-md-8">
              <q-card flat bordered>
                <q-card-section class="row items-center">
                  <div class="text-weight-bold">Votos por Partido</div>
                  <q-space />
                  <q-chip outline color="primary">Total: {{ sumVotos }}</q-chip>
                </q-card-section>
                <q-separator />

<!--                <q-card-section class="q-pt-sm q-pb-none">-->
<!--                  <q-banner v-if="totalMismatchAny" dense class="bg-orange-2 text-black">-->
<!--                    Hay categorías que no suman 250. Revisa: {{ mismatchLabels.join(', ') }}.-->
<!--                  </q-banner>-->
<!--                </q-card-section>-->

                <q-card-section style="max-height: 55vh; overflow:auto;">
                  <div class="row q-col-gutter-sm">
<!--                    <div class="col-12 col-md-6">-->
<!--                      <q-card flat bordered class="q-pa-sm">-->
<!--                        <div class="text-weight-bold q-mb-xs">Gobernador</div>-->
<!--                        <div v-for="p in partidosDepartamental" :key="'gob_'+p.id" class="row items-center q-col-gutter-sm q-mb-xs">-->
<!--                          <div class="col-12 col-md-7 row items-center">-->
<!--                            <div v-if="p.icono" class="q-mr-sm">-->
<!--                              <q-img :src="$url + '/../images/partidos/' + p.icono" style="width:26px; height:26px;" />-->
<!--                            </div>-->
<!--                            <q-badge outline :style="{ borderColor: p.color || '#999', color: p.color || '#111' }">-->
<!--                              {{ p.sigla }}-->
<!--                            </q-badge>-->
<!--                            <span class="q-ml-sm">{{ p.nombre }}</span>-->
<!--                          </div>-->
<!--                          <div class="col-12 col-md-5">-->
<!--                            <q-input v-model.number="votosMap[p.id].votos_gobernador" type="number" dense outlined label="Votos" min="0" />-->
<!--                          </div>-->
<!--                        </div>-->
<!--                        <q-separator />-->
<!--                        <div class="text-caption text-grey-7 q-mt-xs">-->
<!--                          Total: {{ sumGobernador }} · Blancos: {{ resForm.blancos_gobernador }} · Nulos: {{ resForm.nulos_gobernador }}-->
<!--                        </div>-->
<!--                      </q-card>-->
<!--                    </div>-->

<!--                    <div class="col-12 col-md-6">-->
<!--                      <q-card flat bordered class="q-pa-sm">-->
<!--                        <div class="text-weight-bold q-mb-xs">Asambleísta Distrito xxx</div>-->
<!--                        <div v-for="p in partidosDepartamental" :key="'asd_'+p.id" class="row items-center q-col-gutter-sm q-mb-xs">-->
<!--                          <div class="col-12 col-md-7 row items-center">-->
<!--                            <div v-if="p.icono" class="q-mr-sm">-->
<!--                              <q-img :src="$url + '/../images/partidos/' + p.icono" style="width:26px; height:26px;" />-->
<!--                            </div>-->
<!--                            <q-badge outline :style="{ borderColor: p.color || '#999', color: p.color || '#111' }">-->
<!--                              {{ p.sigla }}-->
<!--                            </q-badge>-->
<!--                            <span class="q-ml-sm">{{ p.nombre }}</span>-->
<!--                          </div>-->
<!--                          <div class="col-12 col-md-5">-->
<!--                            <q-input v-model.number="votosMap[p.id].votos_asambleista_distrito" type="number" dense outlined label="Votos" min="0" />-->
<!--                          </div>-->
<!--                        </div>-->
<!--                        <q-separator />-->
<!--                        <div class="text-caption text-grey-7 q-mt-xs">-->
<!--                          Total: {{ sumAsd }} · Blancos: {{ resForm.blancos_asambleista_distrito }} · Nulos: {{ resForm.nulos_asambleista_distrito }}-->
<!--                        </div>-->
<!--                      </q-card>-->
<!--                    </div>-->

<!--                    <div class="col-12 col-md-6">-->
<!--                      <q-card flat bordered class="q-pa-sm">-->
<!--                        <div class="text-weight-bold q-mb-xs">Asambleísta Población</div>-->
<!--                        <div v-for="p in partidosDepartamental" :key="'asp_'+p.id" class="row items-center q-col-gutter-sm q-mb-xs">-->
<!--                          <div class="col-12 col-md-7 row items-center">-->
<!--                            <div v-if="p.icono" class="q-mr-sm">-->
<!--                              <q-img :src="$url + '/../images/partidos/' + p.icono" style="width:26px; height:26px;" />-->
<!--                            </div>-->
<!--                            <q-badge outline :style="{ borderColor: p.color || '#999', color: p.color || '#111' }">-->
<!--                              {{ p.sigla }}-->
<!--                            </q-badge>-->
<!--                            <span class="q-ml-sm">{{ p.nombre }}</span>-->
<!--                          </div>-->
<!--                          <div class="col-12 col-md-5">-->
<!--                            <q-input v-model.number="votosMap[p.id].votos_asambleista_poblacion" type="number" dense outlined label="Votos" min="0" />-->
<!--                          </div>-->
<!--                        </div>-->
<!--                        <q-separator />-->
<!--                        <div class="text-caption text-grey-7 q-mt-xs">-->
<!--                          Total: {{ sumAsp }} · Blancos: {{ resForm.blancos_asambleista_poblacion }} · Nulos: {{ resForm.nulos_asambleista_poblacion }}-->
<!--                        </div>-->
<!--                      </q-card>-->
<!--                    </div>-->
                    <div class="col-12 col-md-6">
                      <q-card flat bordered class="q-pa-sm">
                        <div class="text-weight-bold q-mb-xs">Alcalde</div>
                        <div v-for="p in partidosMunicipal" :key="'alc_'+p.id" class="row items-center q-col-gutter-sm q-mb-xs">
                          <div class="col-12 col-md-7 row items-center">
                            <div v-if="p.icono" class="q-mr-sm">
                              <q-img :src="$url + '/../images/partidos/' + p.icono" style="width:26px; height:26px;" />
                            </div>
                            <q-badge outline :style="{ borderColor: p.color || '#999', color: p.color || '#111' }">
                              {{ p.sigla }}
                            </q-badge>
                            <span class="q-ml-sm">{{ p.nombre }}</span>
                          </div>
                          <div class="col-12 col-md-5">
                            <q-input v-model.number="votosMap[p.id].votos_alcalde" type="number" dense outlined label="Votos" min="0" />
                          </div>
                        </div>
                        <q-separator />
                        <div class="text-caption text-grey-7 q-mt-xs">
                          Total: {{ sumAlc }} · Blancos: {{ resForm.blancos_alcalde }} · Nulos: {{ resForm.nulos_alcalde }}
                        </div>
                      </q-card>
                    </div>
                    <div class="col-12 col-md-6">
                      <q-card flat bordered class="q-pa-sm">
                        <div class="text-weight-bold q-mb-xs">Concejal</div>
                        <div v-for="p in partidosMunicipal" :key="'con_'+p.id" class="row items-center q-col-gutter-sm q-mb-xs">
                          <div class="col-12 col-md-7 row items-center">
                            <div v-if="p.icono" class="q-mr-sm">
                              <q-img :src="$url + '/../images/partidos/' + p.icono" style="width:26px; height:26px;" />
                            </div>
                            <q-badge outline :style="{ borderColor: p.color || '#999', color: p.color || '#111' }">
                              {{ p.sigla }}
                            </q-badge>
                            <span class="q-ml-sm">{{ p.nombre }}</span>
                          </div>
                          <div class="col-12 col-md-5">
                            <q-input v-model.number="votosMap[p.id].votos_concejal" type="number" dense outlined label="Votos" min="0" />
                          </div>
                        </div>
                        <q-separator />
                        <div class="text-caption text-grey-7 q-mt-xs">
                          Total: {{ sumCon }} · Blancos: {{ resForm.blancos_concejal }} · Nulos: {{ resForm.nulos_concejal }}
                        </div>
                      </q-card>
                    </div>
                  </div>
                </q-card-section>
              </q-card>

              <q-card flat bordered class="q-mt-sm">
                <q-card-section>
                  <q-input v-model="resForm.observacion" type="textarea" outlined label="Observación" />
                </q-card-section>
              </q-card>
            </div>
          </div>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn color="grey-7" label="Cerrar" no-caps @click="dlgResultado=false" />
          <q-btn
            color="primary"
            label="Guardar resultado"
            no-caps
            :disable="!resMesa?.delegado_id"
            :loading="saving"
            @click="saveResultado"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script>
export default {
  name: 'AdminResultadosMesas',
  data () {
    return {
      loadingAll: false,
      loading: false,
      saving: false,
      loadingMesas: false,

      // datos
      allRows: [],
      totalReal: 0,
      truncated: false,
      maxCap: 250,

      // paginación local
      page: 1,
      rowsPerPage: 250,
      rowsPerPageOptions: [
        { label: '15', value: 15 },
        { label: '30', value: 30 },
        { label: '50', value: 50 },
        { label: '100', value: 100 },
        { label: '250', value: 250 },
        { label: 'Todas', value: 0 }
      ],
      backendTotal: 0,
      backendLast: 1,
      showAll: false,

      filters: {
        recinto_id: null,
        mesa_id: null,
        asignado: 'ALL',
        estado: null,
        con_resultado: 'ALL'
      },

      estadoOptions: ['PENDIENTE','ASIGNADA','EN_PROCESO','FINALIZADA','OBSERVADA'],
      asignadoOptions: [
        {label:'Todos', value:'ALL'},
        {label:'Asignado', value:'YES'},
        {label:'Sin asignar', value:'NO'}
      ],
      resultadoOptions: [
        {label:'Todos', value:'ALL'},
        {label:'Con resultado', value:'YES'},
        {label:'Sin resultado', value:'NO'}
      ],

      recintosOpt: [],
      recintosBase: [],
      mesasOpt: [],

      // asignar
      dlgAsignar: false,
      curMesa: null,
      delegadosOpt: [],
      delegadoPick: null,
      estadoPick: 'ASIGNADA',

      // resultado
      dlgResultado: false,
      resMesa: null,
      partidos: [],
      votosMap: {},
      voteTypes: [
        { key: 'votos_gobernador', label: 'Gobernador' },
        { key: 'votos_asambleista_distrito', label: 'Asam. Distrito' },
        { key: 'votos_asambleista_poblacion', label: 'Asam. Poblacion' },
        { key: 'votos_concejal', label: 'Concejal' },
        { key: 'votos_alcalde', label: 'Alcalde' }
      ],
      resForm: {
        aviso_antes: false,
        aviso_manana: false,
        aviso_mediodia: false,
        hora_apertura_mesa: '',
        aviso_tarde: false,
        etapa_1: false,
        etapa_2: false,
        blancos_gobernador: 0,
        nulos_gobernador: 0,
        blancos_asambleista_distrito: 0,
        nulos_asambleista_distrito: 0,
        blancos_asambleista_poblacion: 0,
        nulos_asambleista_poblacion: 0,
        blancos_concejal: 0,
        nulos_concejal: 0,
        blancos_alcalde: 0,
        nulos_alcalde: 0,
        observacion: ''
      },

      // fotos
      fotos: {
        foto1: null, foto2: null, foto3: null, foto4: null, foto5: null,
        foto6: null, foto7: null, foto8: null, foto9: null, foto10: null
      },
      fotosServer: {
        foto1_url: null, foto2_url: null, foto3_url: null, foto4_url: null, foto5_url: null,
        foto6_url: null, foto7_url: null, foto8_url: null, foto9_url: null, foto10_url: null
      }
    }
  },

  computed: {
    // filas actuales (ya vienen filtradas del backend, por eso "filteredRows" = allRows)
    filteredRows () {
      const rows = this.allRows || []
      return rows.slice().sort((a, b) => {
        const na = Number(a.numero_mesa || 0)
        const nb = Number(b.numero_mesa || 0)
        if (na !== nb) return na - nb
        return Number(a.id || 0) - Number(b.id || 0)
      })
    },

    maxPage () {
      if (this.showAll) return 1
      return Math.max(1, Number(this.backendLast || 1))
    },

    pagedRows () {
      if (this.showAll) return this.filteredRows
      return this.filteredRows
    },

    fromRow () {
      if (this.showAll) return this.filteredRows.length ? 1 : 0
      if (!this.filteredRows.length) return 0
      const rp = Number(this.rowsPerPage || 250)
      return (this.page - 1) * rp + 1
    },
    toRow () {
      if (this.showAll) return this.filteredRows.length
      if (!this.filteredRows.length) return 0
      const rp = Number(this.rowsPerPage || 250)
      return Math.min(Number(this.backendTotal || 0), (this.page - 1) * rp + this.filteredRows.length)
    },

    displayTotal () {
      return this.showAll ? this.filteredRows.length : Number(this.backendTotal || 0)
    },

    countSinDelegado () {
      // this.allRows.length
      // return (this.filteredRows || []).filter(x => !x.delegado_id).length
      return this.totalReal - this.countAsignadas
    },
    countAsignadas () { return (this.filteredRows || []).filter(x => !!x.delegado_id).length },
    countConResultado () { return (this.filteredRows || []).filter(x => !!x.tiene_resultado).length },

    partidosMunicipal () {
      return (this.partidos || []).slice().sort((a, b) => {
        const oa = Number(a.orden_municipal || 0)
        const ob = Number(b.orden_municipal || 0)
        if (oa !== ob) return oa - ob
        return String(a.sigla || '').localeCompare(String(b.sigla || ''))
      })
    },

    partidosDepartamental () {
      return (this.partidos || [])
        .filter(p => Number(p.orden_departamental || 0) > 0)
        .slice()
        .sort((a, b) => {
          const oa = Number(a.orden_departamental || 0)
          const ob = Number(b.orden_departamental || 0)
          if (oa !== ob) return oa - ob
          return String(a.sigla || '').localeCompare(String(b.sigla || ''))
        })
    },

    sumVotos () {
      let s = 0
      for (const k of Object.keys(this.votosMap || {})) {
        const row = this.votosMap[k] || {}
        for (const t of this.voteTypes) {
          const v = Number(row[t.key] || 0)
          if (!Number.isNaN(v)) s += v
        }
      }
      return s
    },

    sumGobernador () { return this.sumByKey('votos_gobernador') },
    sumAsd () { return this.sumByKey('votos_asambleista_distrito') },
    sumAsp () { return this.sumByKey('votos_asambleista_poblacion') },
    sumCon () { return this.sumByKey('votos_concejal') },
    sumAlc () { return this.sumByKey('votos_alcalde') },

    sumTotal () {
      const b =
        Number(this.resForm.blancos_gobernador || 0) +
        Number(this.resForm.blancos_asambleista_distrito || 0) +
        Number(this.resForm.blancos_asambleista_poblacion || 0) +
        Number(this.resForm.blancos_concejal || 0) +
        Number(this.resForm.blancos_alcalde || 0)
      const n =
        Number(this.resForm.nulos_gobernador || 0) +
        Number(this.resForm.nulos_asambleista_distrito || 0) +
        Number(this.resForm.nulos_asambleista_poblacion || 0) +
        Number(this.resForm.nulos_concejal || 0) +
        Number(this.resForm.nulos_alcalde || 0)
      return this.sumVotos + b + n
    },

    mismatchLabels () {
      const labels = []
      if (this.sumGobernador + Number(this.resForm.blancos_gobernador || 0) + Number(this.resForm.nulos_gobernador || 0) !== 250) labels.push('Gobernador')
      if (this.sumAsd + Number(this.resForm.blancos_asambleista_distrito || 0) + Number(this.resForm.nulos_asambleista_distrito || 0) !== 250) labels.push('Asambleísta Distrito')
      if (this.sumAsp + Number(this.resForm.blancos_asambleista_poblacion || 0) + Number(this.resForm.nulos_asambleista_poblacion || 0) !== 250) labels.push('Asambleísta Población')
      if (this.sumCon + Number(this.resForm.blancos_concejal || 0) + Number(this.resForm.nulos_concejal || 0) !== 250) labels.push('Concejal')
      if (this.sumAlc + Number(this.resForm.blancos_alcalde || 0) + Number(this.resForm.nulos_alcalde || 0) !== 250) labels.push('Alcalde')
      return labels
    },

    totalMismatchAny () {
      return this.mismatchLabels.length > 0
    }
  },

  async mounted () {
    await this.loadOptions()
    this.refresh()
  },

  methods: {
    sumByKey (key) {
      let s = 0
      for (const k of Object.keys(this.votosMap || {})) {
        const row = this.votosMap[k] || {}
        const v = Number(row[key] || 0)
        if (!Number.isNaN(v)) s += v
      }
      return s
    },

    colorEstado (e) {
      if (e === 'PENDIENTE') return 'grey-7'
      if (e === 'ASIGNADA') return 'primary'
      if (e === 'EN_PROCESO') return 'orange'
      if (e === 'FINALIZADA') return 'positive'
      if (e === 'OBSERVADA') return 'negative'
      return 'grey-7'
    },
    b (val) { return val ? 'positive' : 'grey-6' },

    onChangeRowsPerPage () {
      this.page = 1
      if (!this.showAll) this.refresh()
    },

    async loadOptions () {
      this.recintosBase = await this.$axios.get('admin/mesas/options/recintos').then(r => r.data)
      this.recintosOpt = this.recintosBase
      this.delegadosOpt = await this.$axios.get('admin/mesas/options/delegados').then(r => r.data)
    },

    filterRecintos (val, update) {
      update(() => {
        const needle = (val || '').toLowerCase().trim()
        if (!needle) { this.recintosOpt = this.recintosBase; return }
        this.recintosOpt = (this.recintosBase || []).filter(r => (r.nombre || '').toLowerCase().includes(needle))
      })
    },

    async onPickRecinto (recintoId) {
      this.filters.mesa_id = null
      this.mesasOpt = []
      if (!recintoId) return

      this.loadingMesas = true
      try {
        const data = await this.$axios.get('admin/mesas/options/mesas', { params: { recinto_id: recintoId } }).then(r => r.data)
        this.mesasOpt = (data || []).map(x => ({
          id: x.id,
          label: `Mesa ${x.numero_mesa} (${x.estado})`
        }))
      } finally {
        this.loadingMesas = false
      }
    },
    async fetchAll () {
      this.loadingAll = true
      try {
        const baseParams = {
          recinto_id: this.filters.recinto_id || undefined,
          mesa_id: this.filters.mesa_id || undefined,
          asignado: this.filters.asignado,
          estado: this.filters.estado || undefined,
          con_resultado: this.filters.con_resultado,
          all: 1,              // 🔥 activa paginate del backend
          per_page: 200        // lote (sube/baja)
        }

        let page = 1
        let all = []
        let total = 0
        let last = 1

        do {
          const res = await this.$axios.get('admin/mesas', {
            params: { ...baseParams, page }
          }).then(r => r.data)

          total = res.total
          last = res.last_page

          all = all.concat(res.data || [])
          page++

          // opcional: feedback
          this.totalReal = total
          this.allRows = all
        } while (page <= last)

        this.allRows = all
        this.totalReal = total
        this.truncated = false
        this.maxCap = total

        this.showAll = true
        this.rowsPerPage = 0
        this.page = 1
        this.$alert.success(`Cargadas ${all.length} filas`)
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'No se pudo traer todo')
      } finally {
        this.loadingAll = false
      }
    },

    async refresh () {
      this.loading = true
      try {
        this.showAll = false
        const perPage = this.rowsPerPage === 0 ? 250 : this.rowsPerPage
        const params = {
          recinto_id: this.filters.recinto_id || undefined,
          mesa_id: this.filters.mesa_id || undefined,
          asignado: this.filters.asignado,
          estado: this.filters.estado || undefined,
          con_resultado: this.filters.con_resultado,
          all: 1,
          per_page: perPage,
          page: this.page
        }

        const res = await this.$axios.get('admin/mesas', { params }).then(r => r.data)

        this.allRows = res.data || []
        this.totalReal = res.total || this.allRows.length
        this.backendTotal = res.total || 0
        this.backendLast = res.last_page || 1
        this.truncated = false
        this.maxCap = this.rowsPerPage === 0 ? 250 : this.rowsPerPage
      } finally {
        this.loading = false
      }
    },

    openAsignar (row) {
      this.curMesa = row
      this.delegadoPick = row.delegado_id || null
      this.estadoPick = row.estado || 'ASIGNADA'
      this.dlgAsignar = true
    },

    async saveAsignar () {
      if (!this.curMesa?.id || !this.delegadoPick) return
      this.saving = true
      try {
        await this.$axios.put(`admin/mesas/${this.curMesa.id}/delegado`, {
          delegado_id: this.delegadoPick,
          estado: this.estadoPick
        })
        this.$alert.success('Delegado asignado')
        this.dlgAsignar = false
        this.refresh()
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'No se pudo asignar')
      } finally {
        this.saving = false
      }
    },

    // preview: primero archivo local, si no hay -> foto server
    fotoPreview (n) {
      const key = `foto${n}`
      // console.log('fotoPreview', key, this.fotos[key], this.fotosServer[`${key}_url`])
      const f =  this.fotos[key]
      // console.log('fotoPreview file', f)
      if (f) return URL.createObjectURL(f)
      // console.log('fotoPreview server', this.fotosServer[`${key}_url`])
      const serverUrl = this.fotosServer[`${key}_url`]
      return serverUrl ? (this.$url + '/..' + serverUrl) : null
    },

    async openResultado (row) {
      this.saving = true
      try {
        const data = await this.$axios.get(`admin/mesas/${row.id}/resultado`).then(r => r.data)

        this.resMesa = data.mesa
        this.partidos = data.partidos || []

        // reset form + votos + fotos
        this.votosMap = {}
        this.resForm = {
          aviso_antes: false,
          aviso_manana: false,
          aviso_mediodia: false,
          hora_apertura_mesa: '',
          aviso_tarde: false,
          etapa_1: false,
          etapa_2: false,
          blancos_gobernador: 0,
          nulos_gobernador: 0,
          blancos_asambleista_distrito: 0,
          nulos_asambleista_distrito: 0,
          blancos_asambleista_poblacion: 0,
          nulos_asambleista_poblacion: 0,
          blancos_concejal: 0,
          nulos_concejal: 0,
          blancos_alcalde: 0,
          nulos_alcalde: 0,
          observacion: ''
        }

        this.fotos = {
          foto1: null, foto2: null, foto3: null, foto4: null, foto5: null,
          foto6: null, foto7: null, foto8: null, foto9: null, foto10: null
        }
        this.fotosServer = {
          foto1_url: null, foto2_url: null, foto3_url: null, foto4_url: null, foto5_url: null,
          foto6_url: null, foto7_url: null, foto8_url: null, foto9_url: null, foto10_url: null
        }

        if (data.resultado) {
          const r = data.resultado

          this.resForm.aviso_antes = !!r.aviso_antes
          this.resForm.aviso_manana = !!r.aviso_manana
          this.resForm.aviso_mediodia = !!r.aviso_mediodia
          this.resForm.hora_apertura_mesa = r.hora_apertura_mesa || ''
          this.resForm.aviso_tarde = !!r.aviso_tarde
          this.resForm.etapa_1 = !!r.etapa_1
          this.resForm.etapa_2 = !!r.etapa_2

          this.resForm.blancos_gobernador = Number(r.blancos_gobernador || 0)
          this.resForm.nulos_gobernador = Number(r.nulos_gobernador || 0)
          this.resForm.blancos_asambleista_distrito = Number(r.blancos_asambleista_distrito || 0)
          this.resForm.nulos_asambleista_distrito = Number(r.nulos_asambleista_distrito || 0)
          this.resForm.blancos_asambleista_poblacion = Number(r.blancos_asambleista_poblacion || 0)
          this.resForm.nulos_asambleista_poblacion = Number(r.nulos_asambleista_poblacion || 0)
          this.resForm.blancos_concejal = Number(r.blancos_concejal || 0)
          this.resForm.nulos_concejal = Number(r.nulos_concejal || 0)
          this.resForm.blancos_alcalde = Number(r.blancos_alcalde || 0)
          this.resForm.nulos_alcalde = Number(r.nulos_alcalde || 0)
          this.resForm.observacion = r.observacion || ''

          // fotos existentes
          this.fotosServer.foto1_url = r.foto1_url || null
          this.fotosServer.foto2_url = r.foto2_url || null
          this.fotosServer.foto3_url = r.foto3_url || null
          this.fotosServer.foto4_url = r.foto4_url || null
          this.fotosServer.foto5_url = r.foto5_url || null
          this.fotosServer.foto6_url = r.foto6_url || null
          this.fotosServer.foto7_url = r.foto7_url || null
          this.fotosServer.foto8_url = r.foto8_url || null
          this.fotosServer.foto9_url = r.foto9_url || null
          this.fotosServer.foto10_url = r.foto10_url || null

          const det = r.detalles || []
          det.forEach(d => {
            this.votosMap[d.partido_id] = {
              votos_gobernador: Number(d.votos_gobernador || 0),
              votos_asambleista_distrito: Number(d.votos_asambleista_distrito || 0),
              votos_asambleista_poblacion: Number(d.votos_asambleista_poblacion || 0),
              votos_concejal: Number(d.votos_concejal || 0),
              votos_alcalde: Number(d.votos_alcalde || 0)
            }
          })
        }

        // asegurar todos los partidos
        this.partidos.forEach(p => {
          if (this.votosMap[p.id] == null) {
            this.votosMap[p.id] = {
              votos_gobernador: 0,
              votos_asambleista_distrito: 0,
              votos_asambleista_poblacion: 0,
              votos_concejal: 0,
              votos_alcalde: 0
            }
          }
        })

        this.dlgResultado = true
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'No se pudo cargar resultado')
      } finally {
        this.saving = false
      }
    },

    async saveResultado () {
      if (!this.resMesa?.id) return
      this.saving = true
      try {
        if (this.resForm.aviso_manana) {
          const hhmm = this.resForm.hora_apertura_mesa || ''
          const okFmt = /^\d{2}:\d{2}$/.test(hhmm)
          const hh = okFmt ? Number(hhmm.slice(0, 2)) : -1
          if (!okFmt || !(hh >= 8 || hh <= 4)) {
            this.$alert.error('La hora de apertura debe estar entre 08:00 y 04:00')
            return
          }
        } else {
          this.resForm.hora_apertura_mesa = ''
        }

        const votos = (this.partidos || []).map(p => ({
          partido_id: p.id,
          votos_gobernador: Number(this.votosMap[p.id]?.votos_gobernador || 0),
          votos_asambleista_distrito: Number(this.votosMap[p.id]?.votos_asambleista_distrito || 0),
          votos_asambleista_poblacion: Number(this.votosMap[p.id]?.votos_asambleista_poblacion || 0),
          votos_concejal: Number(this.votosMap[p.id]?.votos_concejal || 0),
          votos_alcalde: Number(this.votosMap[p.id]?.votos_alcalde || 0)
        }))

        // multipart
        const fd = new FormData()
        if (this.totalMismatchAny) {
          this.$alert.warning('Hay categorías que no suman 250, se guardará igual')
        }
        fd.append('aviso_antes', this.resForm.aviso_antes ? '1' : '0')
        fd.append('aviso_manana', this.resForm.aviso_manana ? '1' : '0')
        fd.append('aviso_mediodia', this.resForm.aviso_mediodia ? '1' : '0')
        fd.append('hora_apertura_mesa', this.resForm.hora_apertura_mesa || '')

        const totalBlancos =
          Number(this.resForm.blancos_gobernador || 0) +
          Number(this.resForm.blancos_asambleista_distrito || 0) +
          Number(this.resForm.blancos_asambleista_poblacion || 0) +
          Number(this.resForm.blancos_concejal || 0) +
          Number(this.resForm.blancos_alcalde || 0)
        const totalNulos =
          Number(this.resForm.nulos_gobernador || 0) +
          Number(this.resForm.nulos_asambleista_distrito || 0) +
          Number(this.resForm.nulos_asambleista_poblacion || 0) +
          Number(this.resForm.nulos_concejal || 0) +
          Number(this.resForm.nulos_alcalde || 0)

        fd.append('total_blancos', String(totalBlancos))
        fd.append('total_nulos', String(totalNulos))
        fd.append('total_validos', String(this.sumVotos || 0))
        fd.append('observacion', this.resForm.observacion || '')

        fd.append('blancos_gobernador', String(this.resForm.blancos_gobernador || 0))
        fd.append('nulos_gobernador', String(this.resForm.nulos_gobernador || 0))
        fd.append('blancos_asambleista_distrito', String(this.resForm.blancos_asambleista_distrito || 0))
        fd.append('nulos_asambleista_distrito', String(this.resForm.nulos_asambleista_distrito || 0))
        fd.append('blancos_asambleista_poblacion', String(this.resForm.blancos_asambleista_poblacion || 0))
        fd.append('nulos_asambleista_poblacion', String(this.resForm.nulos_asambleista_poblacion || 0))
        fd.append('blancos_concejal', String(this.resForm.blancos_concejal || 0))
        fd.append('nulos_concejal', String(this.resForm.nulos_concejal || 0))
        fd.append('blancos_alcalde', String(this.resForm.blancos_alcalde || 0))
        fd.append('nulos_alcalde', String(this.resForm.nulos_alcalde || 0))

        fd.append('votos', JSON.stringify(votos))

        // fotos (si selecciona, reemplaza)
        if (this.fotos.foto1) fd.append('foto1', this.fotos.foto1)
        if (this.fotos.foto2) fd.append('foto2', this.fotos.foto2)
        if (this.fotos.foto3) fd.append('foto3', this.fotos.foto3)
        if (this.fotos.foto4) fd.append('foto4', this.fotos.foto4)
        if (this.fotos.foto5) fd.append('foto5', this.fotos.foto5)
        if (this.fotos.foto6) fd.append('foto6', this.fotos.foto6)
        if (this.fotos.foto7) fd.append('foto7', this.fotos.foto7)
        if (this.fotos.foto8) fd.append('foto8', this.fotos.foto8)
        if (this.fotos.foto9) fd.append('foto9', this.fotos.foto9)
        if (this.fotos.foto10) fd.append('foto10', this.fotos.foto10)

        await this.$axios.post(
          `admin/mesas/${this.resMesa.id}/resultado?_method=PUT`,
          fd,
          { headers: { 'Content-Type': 'multipart/form-data' } }
        )

        this.$alert.success('Resultado guardado')
        this.dlgResultado = false
        this.refresh()
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'No se pudo guardar')
      } finally {
        this.saving = false
      }
    }
  }
}
</script>
