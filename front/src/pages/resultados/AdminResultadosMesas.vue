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

            <div class="col-12 col-sm-3 col-md-2">
              <q-select
                v-model="filters.departamento_id"
                :options="departamentosOpt"
                option-label="label"
                option-value="value"
                emit-value
                map-options
                dense outlined
                label="Departamento"
                @update:model-value="onDepartamentoChange"
              />
            </div>

            <div class="col-12 col-sm-3 col-md-2">
              <q-select
                v-model="filters.provincia_id"
                :options="provinciasOpt"
                option-label="label"
                option-value="value"
                emit-value
                map-options
                dense outlined clearable
                label="Provincia"
                :disable="!filters.departamento_id"
                @update:model-value="onProvinciaChange"
              />
            </div>

            <div class="col-12 col-sm-3 col-md-2">
              <q-select
                v-model="filters.municipio_id"
                :options="municipiosOpt"
                option-label="label"
                option-value="value"
                emit-value
                map-options
                dense outlined clearable
                label="Municipio"
                :disable="!filters.provincia_id"
                @update:model-value="onMunicipioChange"
              />
            </div>

            <div class="col-12 col-sm-3 col-md-2">
              <q-select
                v-model="filters.localidad_id"
                :options="localidadesOpt"
                option-label="label"
                option-value="value"
                emit-value
                map-options
                dense outlined clearable
                label="Localidad"
                :disable="!filters.municipio_id"
                @update:model-value="onLocalidadChange"
              />
            </div>

            <div class="col-12 col-sm-6 col-md-3">
              <q-select
                v-model="filters.municipio_full_id"
                :options="municipiosFullOpt"
                option-label="label"
                option-value="value"
                emit-value
                map-options
                use-input input-debounce="200"
                dense outlined clearable
                label="Buscar provincia y municipio"
                @filter="filterMunicipiosFull"
                @update:model-value="onPickMunicipioFull"
              />
            </div>

            <div class="col-12 col-sm-6 col-md-3">
              <q-select
                v-model="filters.recinto_id"
                :options="recintosOpt"
                option-label="label"
                option-value="value"
                emit-value map-options
                use-input input-debounce="200"
                dense outlined clearable
                label="Buscar recinto completo"
                @filter="filterRecintos"
                @update:model-value="onPickRecinto"
              />
            </div>

            <div class="col-6 col-sm-3 col-md-2">
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

            <div class="col-6 col-sm-3 col-md-2">
              <q-select
                v-model="filters.asignado"
                dense outlined
                label="Delegado"
                :options="asignadoOptions"
                emit-value map-options
                @update:model-value="onPickAsignado"
              />
            </div>

            <div class="col-12 col-sm-6 col-md-4">
              <q-select
                v-model="filters.delegado_id"
                :options="delegadosOptFiltered"
                option-label="label"
                option-value="id"
                emit-value
                map-options
                use-input
                input-debounce="200"
                dense
                outlined
                clearable
                label="Delegado de mesa"
                @filter="filterDelegados"
                @update:model-value="onPickDelegadoFiltro"
              />
            </div>

            <div class="col-12 col-sm-6 col-md-4">
              <q-select
                v-model="filters.jefe_recinto_id"
                :options="jefesRecintoOptFiltered"
                option-label="label"
                option-value="id"
                emit-value
                map-options
                use-input
                input-debounce="200"
                dense
                outlined
                clearable
                label="Jefe de recinto"
                @filter="filterJefesRecinto"
              />
            </div>

            <div class="col-12 col-sm-6 col-md-4">
              <q-select
                v-model="filters.supervisor_id"
                :options="supervisoresOptFiltered"
                option-label="label"
                option-value="id"
                emit-value
                map-options
                use-input
                input-debounce="200"
                dense
                outlined
                clearable
                label="Supervisor"
                @filter="filterSupervisores"
              />
            </div>

            <div class="col-6 col-sm-3 col-md-2">
              <q-select
                v-model="filters.estado"
                dense outlined clearable
                label="Estado"
                :options="estadoOptions"
              />
            </div>

            <div class="col-6 col-sm-3 col-md-2">
              <q-select
                v-model="filters.con_resultado"
                dense outlined
                label="Resultado"
                :options="resultadoOptions"
                emit-value map-options
              />
            </div>

            <div class="col-6 col-sm-3 col-md-2">
              <q-select
                v-model="filters.en_mesa"
                dense outlined
                label="En mesa"
                :options="enMesaOptions"
                emit-value map-options
              />
            </div>

            <div class="col-6 col-sm-3 col-md-2">
              <q-select
                v-model="filters.acta_alcaldia"
                dense outlined
                label="Acta alcaldía"
                :options="resultadoOptions"
                emit-value map-options
              />
            </div>

            <div class="col-6 col-sm-3 col-md-2">
              <q-select
                v-model="filters.acta_gobernacion"
                dense outlined
                label="Acta gobernación"
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
              <q-btn-dropdown
                color="teal"
                label="Imprimir"
                no-caps
                class="full-width"
                :loading="loadingAll || loadingPrint"
              >
                <q-list>
                  <q-item clickable v-close-popup @click="fetchAll">
                    <q-item-section avatar><q-icon name="download" /></q-item-section>
                    <q-item-section><q-item-label>Extraer todos</q-item-label></q-item-section>
                  </q-item>
                  <q-separator />
                  <q-item clickable v-close-popup @click="printEnMesa">
                    <q-item-section avatar><q-icon name="print" /></q-item-section>
                    <q-item-section><q-item-label>Imprimir en mesa</q-item-label></q-item-section>
                  </q-item>
                  <q-item clickable v-close-popup @click="printNoEnMesa">
                    <q-item-section avatar><q-icon name="print" /></q-item-section>
                    <q-item-section><q-item-label>Imprimir no en mesa</q-item-label></q-item-section>
                  </q-item>
                  <q-item clickable v-close-popup @click="printMesaAbierta">
                    <q-item-section avatar><q-icon name="schedule" /></q-item-section>
                    <q-item-section><q-item-label>Imprimir mesa abierta</q-item-label></q-item-section>
                  </q-item>
                  <q-item clickable v-close-popup @click="openSupervisorPrintDialog">
                    <q-item-section avatar><q-icon name="supervisor_account" /></q-item-section>
                    <q-item-section><q-item-label>Recintos por supervisor</q-item-label></q-item-section>
                  </q-item>
                  <q-item clickable v-close-popup @click="openRecintoPrintDialog">
                    <q-item-section avatar><q-icon name="store" /></q-item-section>
                    <q-item-section><q-item-label>Jerarquía por recinto</q-item-label></q-item-section>
                  </q-item>
                </q-list>
              </q-btn-dropdown>
            </div>

          </div>
        </div>
      </q-card-section>

      <q-separator />

      <!-- CHIPS -->
      <q-card-section class="q-pt-sm q-pb-none">
        <div class="row items-center q-col-gutter-sm">
          <div class="col-auto"><q-chip outline color="primary">Total: {{ summaryTotal }}</q-chip></div>
          <div class="col-auto"><q-chip outline color="positive">Asignadas: {{ countAsignadas }}</q-chip></div>
          <div class="col-auto"><q-chip outline color="negative">Sin delegado: {{ countSinDelegado }}</q-chip></div>
          <div class="col-auto"><q-chip outline color="indigo">En mesa: {{ countEnMesa }}</q-chip></div>
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
            <th class="text-center" style="width: 76px;">Acciones</th>
            <th class="text-left" style="width: 180px;">Mesa</th>
            <th class="text-left">Delegado</th>
            <th class="text-left">Estado</th>
            <th class="text-left">Resultado</th>
            <th class="text-left">Control de Mesa</th>
          </tr>
          </thead>

          <tbody v-if="pagedRows.length">
          <tr v-for="(r,i) in pagedRows" :key="r.id">
            <td class="text-center">
              <q-btn-dropdown dense color="primary" icon="more_horiz" label="" no-caps size="sm">
                <q-list>
                  <q-item clickable v-close-popup @click="openAsignar(r)">
                    <q-item-section avatar><q-icon name="person_add" /></q-item-section>
                    <q-item-section><q-item-label>Asignar delegado</q-item-label></q-item-section>
                  </q-item>
                  <q-item clickable v-close-popup @click="openResultado(r)">
                    <q-item-section avatar><q-icon name="how_to_vote" /></q-item-section>
                    <q-item-section><q-item-label>Registrar resultado</q-item-label></q-item-section>
                  </q-item>
                  <q-item clickable v-close-popup :disable="!hasPresenceData(r)" @click="openPresencia(r)">
                    <q-item-section avatar><q-icon name="place" /></q-item-section>
                    <q-item-section><q-item-label>Ver presencia delegado</q-item-label></q-item-section>
                  </q-item>
                </q-list>
              </q-btn-dropdown>
            </td>
            <td class="text-left">
              <div class="text-weight-bold">Mesa {{ r.numero_mesa }}</div>
              <div class="text-caption text-grey-7">{{ r.recinto_nombre }}</div>
              <div class="text-caption text-grey-6">
                {{ r.municipio_nombre || 'Sin municipio' }} · {{ r.provincia_nombre || 'Sin provincia' }}
              </div>
            </td>

            <td class="text-left">
              <div v-if="r.delegado">
                <div class="text-weight-medium" style="max-width: 140px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ r.delegado.name }}</div>
                <div class="text-caption text-grey-7">
                  <strong>Username:</strong>
                  {{ r.delegado.username }}
                </div>
                <div class="text-caption text-grey-7">
                  <strong>CI:</strong>
                  {{ r.delegado.ci }}
                </div>
                <div class="text-caption text-grey-7">
                  <strong>Nac:</strong>
                  {{ r.delegado.fecha_nacimiento }}
                </div>
                <div class="text-caption text-grey-7">
                  <strong>Celular:</strong>
                  {{ r.delegado.celular || 'Sin celular' }}
                  <q-btn
                    v-if="whatsappUrl(r.delegado.celular)"
                    flat
                    round
                    dense
                    size="sm"
                    color="positive"
                    icon="fa-brands fa-whatsapp"
                    class="q-ml-xs"
                    @click="openWhatsapp(r.delegado.celular)"
                  >
                    <q-tooltip>Enviar WhatsApp</q-tooltip>
                  </q-btn>
                </div>
              </div>
              <q-badge v-else outline color="negative">
                SIN ASIGNAR
              </q-badge>
            </td>

            <td class="text-left">
              <q-chip dense text-color="white" :color="colorEstado(r.estado)">
                {{ r.estado }}
              </q-chip>
              <div v-if="ganadoresMesa(r).length" class="q-mt-xs ganadores-compactos">
                <div
                  v-for="item in ganadoresMesa(r)"
                  :key="`${r.id}-${item.key}`"
                  class="ganador-item"
                >
                  <q-img
                    v-if="item.icono"
                    :src="getImageUrl('images/partidos/' + item.icono)"
                    class="ganador-icono"
                  />
                  <q-icon v-else name="flag" size="12px" color="grey-6" class="ganador-icono-fallback" />
                  <span class="ganador-texto">
                    <span class="text-weight-medium">{{ item.label }}:</span>
                    {{ item.value }}
                  </span>
                </div>
              </div>
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
                <q-chip dense size="11px" :color="b(r.aviso_mediodia)" text-color="white">Acta alcaldia</q-chip>
                <q-chip dense size="11px" :color="b(r.aviso_tarde)" text-color="white">Acta gobernacion</q-chip>
                <q-chip v-if="r.hora_apertura_mesa" dense size="11px" color="indigo" text-color="white">
                  {{ r.hora_apertura_mesa }}
                </q-chip>
              </div>
              <div v-if="hasPresenceData(r)" class="q-mt-xs">
                <q-btn
                  flat
                  dense
                  no-caps
                  size="sm"
                  color="primary"
                  icon="location_on"
                  label="Ver presencia"
                  @click="openPresencia(r)"
                />
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
            :options="delegadosAsignarOptFiltered"
            option-label="label"
            option-value="id"
            emit-value map-options
            use-input input-debounce="200"
            dense outlined
            label="Delegado de Mesa"
            clearable
            @filter="filterDelegadosAsignar"
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
          <q-btn color="primary" label="Guardar" no-caps :disable="!curMesa?.id" :loading="saving" @click="saveAsignar" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- DIALOG: RESULTADO -->
    <q-dialog v-model="dlgResultado" persistent>
      <q-card style="width: 1180px; max-width: 99vw;">
        <q-card-section class="row items-center">
          <div class="text-weight-bold">Resultado de Mesa</div>
          <q-space />
          <q-btn
            color="primary"
            label="Guardar"
            no-caps
            class="q-mr-sm"
            :disable="!resMesa?.id || !resMesa?.delegado_id"
            :loading="saving"
            @click="saveResultado"
          />
          <q-btn icon="close" flat round dense @click="dlgResultado=false" />
        </q-card-section>

        <q-card-section class="q-pt-none">
          <div class="text-caption text-grey-7">
            {{ resMesa?.recinto_nombre }} · Mesa {{ resMesa?.numero_mesa }} ·
            {{ resMesa?.provincia_nombre || 'Sin provincia' }} ·
            {{ resMesa?.municipio_nombre || 'Sin municipio' }} ·
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
                  <q-toggle v-model="resForm.aviso_mediodia" label="Tengo el acta de la alcaldia en mi poder" />
                  <q-toggle v-model="resForm.aviso_tarde" label="Tengo el acta de la gobernacion en mi poder" />
                </q-card-section>
              </q-card>

              <q-card flat bordered class="q-mt-sm">
                <q-card-section class="text-weight-bold">Totales</q-card-section>
                <q-separator />
                <q-card-section class="row q-col-gutter-sm">
                 <div class="col-12 text-caption text-grey-7">Gobernador</div>
                 <div class="col-6">
                   <q-input v-model.number="resForm.blancos_gobernador" type="number" dense outlined label="Blancos" min="0" />
                 </div>
                 <div class="col-6">
                   <q-input v-model.number="resForm.nulos_gobernador" type="number" dense outlined label="Nulos" min="0" />
                 </div>
                 <div class="col-12">
                   <q-input v-model.number="resForm.papeletas_no_utilizadas_gobernador" type="number" dense outlined label="Papeletas no utilizadas" min="0" />
                 </div>

                 <div class="col-12 text-caption text-grey-7">Asambleísta Distrito  </div>
                 <div class="col-6">
                   <q-input v-model.number="resForm.blancos_asambleista_distrito" type="number" dense outlined label="Blancos" min="0" />
                 </div>
                 <div class="col-6">
                   <q-input v-model.number="resForm.nulos_asambleista_distrito" type="number" dense outlined label="Nulos" min="0" />
                 </div>
                 <div class="col-12">
                   <q-input v-model.number="resForm.papeletas_no_utilizadas_asambleista_distrito" type="number" dense outlined label="Papeletas no utilizadas" min="0" />
                 </div>

                 <div class="col-12 text-caption text-grey-7">Asambleísta Población</div>
                 <div class="col-6">
                   <q-input v-model.number="resForm.blancos_asambleista_poblacion" type="number" dense outlined label="Blancos" min="0" />
                 </div>
                 <div class="col-6">
                   <q-input v-model.number="resForm.nulos_asambleista_poblacion" type="number" dense outlined label="Nulos" min="0" />
                 </div>
                 <div class="col-12">
                   <q-input v-model.number="resForm.papeletas_no_utilizadas_asambleista_poblacion" type="number" dense outlined label="Papeletas no utilizadas" min="0" />
                 </div>



                  <div class="col-12 text-caption text-grey-7">Alcalde</div>
                  <div class="col-6">
                    <q-input v-model.number="resForm.blancos_alcalde" type="number" dense outlined label="Blancos" min="0" />
                  </div>
                  <div class="col-6">
                    <q-input v-model.number="resForm.nulos_alcalde" type="number" dense outlined label="Nulos" min="0" />
                  </div>
                  <div class="col-12">
                    <q-input
                      v-model.number="resForm.papeletas_no_utilizadas_alcalde"
                      type="number"
                      dense
                      outlined
                      label="Papeletas no utilizadas"
                      min="0"
                    />
                  </div>
                  <div class="col-12 text-caption text-grey-7">Concejal</div>
                  <div class="col-6">
                    <q-input v-model.number="resForm.blancos_concejal" type="number" dense outlined label="Blancos" min="0" />
                  </div>
                  <div class="col-6">
                    <q-input v-model.number="resForm.nulos_concejal" type="number" dense outlined label="Nulos" min="0" />
                  </div>
                  <div class="col-12">
                    <q-input
                      v-model.number="resForm.papeletas_no_utilizadas_concejal"
                      type="number"
                      dense
                      outlined
                      label="Papeletas no utilizadas"
                      min="0"
                    />
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
                      <q-card flat bordered class="q-pa-xs relative-position foto-card">
                        <input
                          :id="`foto-input-${n}`"
                          :ref="`fotoInput${n}`"
                          class="hidden"
                          type="file"
                          accept="image/*"
                          @change="onFotoSelected($event, n)"
                        >
                        <q-btn
                          icon="upload"
                          dense
                          round
                          color="blue-grey-7"
                          class="absolute-top-left q-ma-xs"
                          style="z-index: 1000"
                          @click="triggerFotoInput(n)"
                        />
                        <q-btn
                          v-if="fotoPreview(n)"
                          icon="open_in_new"
                          dense
                          round
                          color="primary"
                          class="absolute-top-right q-ma-xs"
                          @click="openPhotoExternal(n)"
                          style="z-index: 1000"
                        />
                        <q-btn
                          v-if="fotoPreview(n)"
                          icon="delete"
                          dense
                          round
                          color="negative"
                          class="absolute-top-right q-ma-xs"
                          @click="clearFoto(n)"
                          style="z-index: 1000; right: 40px;"
                        />
<!--                        <pre>{{fotoPreview(n)}}</pre>-->
                        <q-img
                          v-if="fotoPreview(n)"
                          :src="fotoPreview(n)"
                          class="foto-preview"
                          spinner-color="primary"
                        />
                        <div v-else class="flex flex-center text-grey-6 foto-preview-empty">
                          <q-icon name="image" size="28px" />
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
                   <div class="col-12 col-md-6">
                     <q-card flat bordered class="q-pa-sm">
                       <div class="text-weight-bold q-mb-xs">Gobernador</div>
                       <div v-for="p in partidosGobernador" :key="'gob_'+p.id" class="row items-center q-col-gutter-sm q-mb-xs">
                         <div class="col-12 col-md-7 row items-center">
                           <div v-if="p.icono" class="q-mr-sm">
                             <q-img :src="getImageUrl('images/partidos/' + p.icono)" style="width:26px; height:26px;" />
                           </div>
                           <q-badge outline :style="{ borderColor: p.color || '#999', color: p.color || '#111' }">
                             {{ p.sigla }}
                           </q-badge>
                           <span class="q-ml-sm">{{ p.nombre }}</span>
                         </div>
                         <div class="col-12 col-md-5">
                           <q-input v-model.number="votosMap[p.id].votos_gobernador" type="number" dense outlined label="Votos" min="0" />
                         </div>
                       </div>
                       <q-separator />
                        <div class="text-caption text-grey-7 q-mt-xs">
                         Total: {{ sumGobernador }} · Blancos: {{ resForm.blancos_gobernador }} · Nulos: {{ resForm.nulos_gobernador }} · PNU: {{ resForm.papeletas_no_utilizadas_gobernador }}
                       </div>
                     </q-card>
                   </div>

                   <div class="col-12 col-md-6">
                     <q-card flat bordered class="q-pa-sm">
                       <div class="text-weight-bold q-mb-xs">Asambleísta Distrito</div>
                       <div v-for="p in partidosAsambleistaDistrito" :key="'asd_'+p.id" class="row items-center q-col-gutter-sm q-mb-xs">
                         <div class="col-12 col-md-7 row items-center">
                           <div v-if="p.icono" class="q-mr-sm">
                             <q-img :src="getImageUrl('images/partidos/' + p.icono)" style="width:26px; height:26px;" />
                           </div>
                           <q-badge outline :style="{ borderColor: p.color || '#999', color: p.color || '#111' }">
                             {{ p.sigla }}
                           </q-badge>
                           <span class="q-ml-sm">{{ p.nombre }}</span>
                         </div>
                         <div class="col-12 col-md-5">
                           <q-input v-model.number="votosMap[p.id].votos_asambleista_distrito" type="number" dense outlined label="Votos" min="0" />
                         </div>
                       </div>
                       <q-separator />
                        <div class="text-caption text-grey-7 q-mt-xs">
                         Total: {{ sumAsd }} · Blancos: {{ resForm.blancos_asambleista_distrito }} · Nulos: {{ resForm.nulos_asambleista_distrito }} · PNU: {{ resForm.papeletas_no_utilizadas_asambleista_distrito }}
                       </div>
                     </q-card>
                   </div>

                   <div class="col-12 col-md-6">
                     <q-card flat bordered class="q-pa-sm">
                       <div class="text-weight-bold q-mb-xs">Asambleísta Población</div>
                       <div v-for="p in partidosAsambleistaPoblacion" :key="'asp_'+p.id" class="row items-center q-col-gutter-sm q-mb-xs">
                         <div class="col-12 col-md-7 row items-center">
                           <div v-if="p.icono" class="q-mr-sm">
                             <q-img :src="getImageUrl('images/partidos/' + p.icono)" style="width:26px; height:26px;" />
                           </div>
                           <q-badge outline :style="{ borderColor: p.color || '#999', color: p.color || '#111' }">
                             {{ p.sigla }}
                           </q-badge>
                           <span class="q-ml-sm">{{ p.nombre }}</span>
                         </div>
                         <div class="col-12 col-md-5">
                           <q-input v-model.number="votosMap[p.id].votos_asambleista_poblacion" type="number" dense outlined label="Votos" min="0" />
                         </div>
                       </div>
                       <q-separator />
                        <div class="text-caption text-grey-7 q-mt-xs">
                         Total: {{ sumAsp }} · Blancos: {{ resForm.blancos_asambleista_poblacion }} · Nulos: {{ resForm.nulos_asambleista_poblacion }} · PNU: {{ resForm.papeletas_no_utilizadas_asambleista_poblacion }}
                       </div>
                     </q-card>
                   </div>
                    <div class="col-12 col-md-6">
                      <q-card flat bordered class="q-pa-sm">
                        <div class="text-weight-bold q-mb-xs">Alcalde</div>
                        <div v-for="p in partidosAlcalde" :key="'alc_'+p.id" class="row items-center q-col-gutter-sm q-mb-xs">
                          <div class="col-12 col-md-7 row items-center">
                            <div v-if="p.icono" class="q-mr-sm">
                              <q-img :src="getImageUrl('images/partidos/' + p.icono)" style="width:26px; height:26px;" />
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
                          Total: {{ sumAlc }} · Blancos: {{ resForm.blancos_alcalde }} · Nulos: {{ resForm.nulos_alcalde }} · PNU: {{ resForm.papeletas_no_utilizadas_alcalde }}
                        </div>
                      </q-card>
                    </div>
                    <div class="col-12 col-md-6">
                      <q-card flat bordered class="q-pa-sm">
                        <div class="text-weight-bold q-mb-xs">Concejal</div>
                        <div v-for="p in partidosConcejal" :key="'con_'+p.id" class="row items-center q-col-gutter-sm q-mb-xs">
                          <div class="col-12 col-md-7 row items-center">
                            <div v-if="p.icono" class="q-mr-sm">
                              <q-img :src="getImageUrl('images/partidos/' + p.icono)" style="width:26px; height:26px;" />
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
                          Total: {{ sumCon }} · Blancos: {{ resForm.blancos_concejal }} · Nulos: {{ resForm.nulos_concejal }} · PNU: {{ resForm.papeletas_no_utilizadas_concejal }}
                        </div>
                      </q-card>
                    </div>
                  </div>
                </q-card-section>
              </q-card>

              <q-card flat bordered class="q-mt-sm">
                <q-card-section>
                  <q-input v-model="resForm.observacion_alcalde" type="textarea" outlined label="Observacion Alcalde" class="q-mb-sm" />
                  <q-input v-model="resForm.observacion_concejal" type="textarea" outlined label="Observacion Concejal" class="q-mb-sm" />
                  <q-input v-model="resForm.observacion_gobernador" type="textarea" outlined label="Observacion Gobernador" class="q-mb-sm" />
                  <q-input v-model="resForm.observacion_asambleista_distrito" type="textarea" outlined label="Observacion Asambleista Distrito" class="q-mb-sm" />
                  <q-input v-model="resForm.observacion_asambleista_poblacion" type="textarea" outlined label="Observacion Asambleista Poblacion" />
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

    <q-dialog v-model="dlgPresencia">
      <q-card style="width: 460px; max-width: 95vw;">
        <q-card-section class="row items-center">
          <div class="text-weight-bold">Presencia del delegado</div>
          <q-space />
          <q-btn icon="close" flat round dense @click="dlgPresencia=false" />
        </q-card-section>

        <q-card-section class="q-pt-none">
          <div class="text-caption text-grey-7 q-mb-md">
            {{ presenceRow?.recinto_nombre }} · Mesa {{ presenceRow?.numero_mesa }}
          </div>

          <q-list dense bordered separator>
            <q-item>
              <q-item-section avatar><q-icon name="person" color="primary" /></q-item-section>
              <q-item-section>
                <q-item-label>Delegado</q-item-label>
                <q-item-label caption>{{ presenceRow?.delegado?.name || 'Sin delegado' }}</q-item-label>
              </q-item-section>
            </q-item>
            <q-item>
              <q-item-section avatar><q-icon name="schedule" color="primary" /></q-item-section>
              <q-item-section>
                <q-item-label>Hora registrada</q-item-label>
                <q-item-label caption>{{ fmtPresenceAt(presenceRow?.delegado_presente_at) }}</q-item-label>
              </q-item-section>
            </q-item>
            <q-item>
              <q-item-section avatar><q-icon name="my_location" color="primary" /></q-item-section>
              <q-item-section>
                <q-item-label>Latitud</q-item-label>
                <q-item-label caption>{{ presenceRow?.delegado_latitud || 'Sin dato' }}</q-item-label>
              </q-item-section>
            </q-item>
            <q-item>
              <q-item-section avatar><q-icon name="explore" color="primary" /></q-item-section>
              <q-item-section>
                <q-item-label>Longitud</q-item-label>
                <q-item-label caption>{{ presenceRow?.delegado_longitud || 'Sin dato' }}</q-item-label>
              </q-item-section>
            </q-item>
            <q-item>
              <q-item-section avatar><q-icon name="store" color="indigo" /></q-item-section>
              <q-item-section>
                <q-item-label>Recinto</q-item-label>
                <q-item-label caption>
                  {{ presenceRow?.recinto_nombre || 'Sin recinto' }}
                  <span v-if="presenceRow?.recinto_latitud && presenceRow?.recinto_longitud">
                    · {{ presenceRow.recinto_latitud }}, {{ presenceRow.recinto_longitud }}
                  </span>
                </q-item-label>
              </q-item-section>
            </q-item>
            <q-item>
              <q-item-section avatar><q-icon name="route" color="deep-orange" /></q-item-section>
              <q-item-section>
                <q-item-label>Distancia al recinto</q-item-label>
                <q-item-label caption>{{ fmtDistanceToRecinto(presenceRow) }}</q-item-label>
              </q-item-section>
            </q-item>
          </q-list>

          <q-card v-if="presenceMapUrl(presenceRow)" flat bordered class="q-mt-md">
            <q-card-section class="q-pb-none">
              <div class="text-weight-medium">Ubicación registrada</div>
              <div class="text-caption text-grey-7">
                Puede cambiar la capa del mapa desde el selector en la esquina superior derecha.
              </div>
            </q-card-section>
            <q-card-section>
              <PresenceLeafletMap
                :latitud="presenceRow?.delegado_latitud"
                :longitud="presenceRow?.delegado_longitud"
                :recinto-latitud="presenceRow?.recinto_latitud"
                :recinto-longitud="presenceRow?.recinto_longitud"
              />
            </q-card-section>
          </q-card>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn color="grey-7" flat label="Cerrar" no-caps @click="dlgPresencia=false" />
          <q-btn
            v-if="presenceMapUrl(presenceRow)"
            color="primary"
            icon="map"
            label="Abrir mapa"
            no-caps
            @click="openPresenceMap"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <q-dialog v-model="dlgFotoPreview" maximized>
      <q-card class="bg-black">
        <q-card-section class="row items-center q-pb-none">
          <q-space />
          <q-btn
            icon="close"
            flat
            round
            dense
            color="white"
            @click="dlgFotoPreview=false"
          />
        </q-card-section>
        <q-card-section class="flex flex-center" style="height: calc(100vh - 56px);">
          <q-img
            v-if="fotoPreviewSrc"
            :src="fotoPreviewSrc"
            fit="contain"
            style="max-width: 100%; max-height: 100%;"
          />
        </q-card-section>
      </q-card>
    </q-dialog>

    <q-dialog v-model="dlgSupervisorPrint" persistent>
      <q-card style="width: 460px; max-width: 95vw;">
        <q-card-section class="row items-center">
          <div class="text-weight-bold">Recintos por supervisor</div>
          <q-space />
          <q-btn icon="close" flat round dense @click="dlgSupervisorPrint = false" />
        </q-card-section>

        <q-card-section class="q-pt-none">
          <q-select
            v-model="supervisorPrintId"
            :options="supervisoresOptFiltered"
            option-label="label"
            option-value="id"
            emit-value
            map-options
            use-input
            input-debounce="200"
            dense
            outlined
            clearable
            label="Supervisor"
            @filter="filterSupervisores"
          />

          <q-list v-if="supervisorPrintPreview" dense bordered separator class="q-mt-md">
            <q-item>
              <q-item-section>
                <q-item-label class="text-weight-medium">{{ supervisorPrintPreview.supervisor?.name }}</q-item-label>
                <q-item-label caption>
                  {{ supervisorPrintPreview.supervisor?.username || '-' }} · {{ supervisorPrintPreview.supervisor?.celular || 'Sin celular' }}
                </q-item-label>
              </q-item-section>
            </q-item>
            <q-item v-for="(row, index) in (supervisorPrintPreview.rows || [])" :key="`sup-prev-${index}`">
              <q-item-section>
                <q-item-label>{{ row.jefe_nombre }}</q-item-label>
                <q-item-label caption>{{ row.jefe_username || '-' }} · {{ row.jefe_celular || 'Sin celular' }}</q-item-label>
                <q-item-label caption>{{ (row.recintos || []).join(' · ') || 'Sin recintos' }}</q-item-label>
              </q-item-section>
            </q-item>
          </q-list>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat color="grey-7" label="Cancelar" no-caps @click="dlgSupervisorPrint = false" />
          <q-btn flat color="primary" label="Buscar" no-caps :disable="!supervisorPrintId" @click="previewRecintosPorSupervisor" />
          <q-btn color="primary" label="Generar PDF" no-caps :disable="!supervisorPrintId" @click="printRecintosPorSupervisor" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <q-dialog v-model="dlgRecintoPrint" persistent>
      <q-card style="width: 560px; max-width: 95vw;">
        <q-card-section class="row items-center">
          <div class="text-weight-bold">Jerarquía por recinto</div>
          <q-space />
          <q-btn icon="close" flat round dense @click="dlgRecintoPrint = false" />
        </q-card-section>

        <q-card-section class="q-pt-none">
          <q-select
            v-model="recintoPrintId"
            :options="recintosOpt"
            option-label="label"
            option-value="value"
            emit-value
            map-options
            use-input
            input-debounce="200"
            dense
            outlined
            clearable
            label="Recinto"
            @filter="filterRecintos"
          />

          <q-list v-if="recintoPrintPreview" dense bordered separator class="q-mt-md">
            <q-item>
              <q-item-section>
                <q-item-label class="text-weight-medium">{{ recintoPrintPreview.recinto?.nombre }}</q-item-label>
              </q-item-section>
            </q-item>
            <q-item>
              <q-item-section>
                <q-item-label>Supervisores</q-item-label>
                <q-item-label caption>{{ (recintoPrintPreview.supervisores || []).map(x => x.name).join(' · ') || 'Sin supervisores' }}</q-item-label>
              </q-item-section>
            </q-item>
            <q-item>
              <q-item-section>
                <q-item-label>Jefes de recinto</q-item-label>
                <q-item-label caption>{{ (recintoPrintPreview.jefes || []).map(x => x.name).join(' · ') || 'Sin jefes' }}</q-item-label>
              </q-item-section>
            </q-item>
            <q-item v-for="(row, index) in (recintoPrintPreview.delegados || [])" :key="`rec-prev-${index}`">
              <q-item-section>
                <q-item-label>Mesa {{ row.mesa_numero }} · {{ row.name }}</q-item-label>
                <q-item-label caption>{{ row.username || '-' }} · {{ row.celular || 'Sin celular' }}</q-item-label>
              </q-item-section>
            </q-item>
          </q-list>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat color="grey-7" label="Cancelar" no-caps @click="dlgRecintoPrint = false" />
          <q-btn flat color="primary" label="Buscar" no-caps :disable="!recintoPrintId" @click="previewJerarquiaPorRecinto" />
          <q-btn color="primary" label="Generar PDF" no-caps :disable="!recintoPrintId" @click="printJerarquiaPorRecinto" />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script>
import PresenceLeafletMap from 'components/PresenceLeafletMap.vue'
import { io } from 'socket.io-client'

export default {
  name: 'AdminResultadosMesas',
  components: { PresenceLeafletMap },
  data () {
    return {
      loadingAll: false,
      loadingPrint: false,
      loading: false,
      saving: false,
      loadingMesas: false,
      capacitacionSavingId: null,

      // datos
      allRows: [],
      totalReal: 0,
      summary: {
        total: 0,
        asignadas: 0,
        sin_delegado: 0,
        con_resultado: 0
      },
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
        departamento_id: 5,
        provincia_id: 57,
        municipio_id: 191,
        localidad_id: 1988,
        municipio_full_id: 191,
        recinto_id: null,
        mesa_id: null,
        asignado: 'ALL',
        delegado_id: null,
        jefe_recinto_id: null,
        supervisor_id: null,
        estado: null,
        con_resultado: 'ALL',
        en_mesa: 'ALL',
        acta_alcaldia: 'ALL',
        acta_gobernacion: 'ALL'
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
      enMesaOptions: [
        { label: 'Todos', value: 'ALL' },
        { label: 'En mesa', value: 'YES' },
        { label: 'No en mesa', value: 'NO' }
      ],

      geoOptions: {
        departamentos: [],
        provincias: [],
        municipios: [],
        localidades: []
      },
      municipiosFullBase: [],
      municipiosFullOpt: [],
      recintosOpt: [],
      recintosBase: [],
      mesasOpt: [],
      jefesRecintoOpt: [],
      jefesRecintoOptFiltered: [],
      supervisoresOpt: [],
      supervisoresOptFiltered: [],
      delegadosOptFiltered: [],
      delegadosAsignarOptFiltered: [],

      // asignar
      dlgAsignar: false,
      curMesa: null,
      delegadosOpt: [],
      delegadoPick: null,
      estadoPick: 'ASIGNADA',

      // resultado
      dlgResultado: false,
      resMesa: null,
      dlgPresencia: false,
      presenceRow: null,
      dlgSupervisorPrint: false,
      supervisorPrintId: null,
      supervisorPrintPreview: null,
      dlgRecintoPrint: false,
      recintoPrintId: null,
      recintoPrintPreview: null,
      partidos: [],
      votosMap: {},
      voteTypes: [
        { key: 'votos_gobernador', label: 'Gobernador' },
        { key: 'votos_asambleista_distrito', label: 'Asambleista Distrito' },
        { key: 'votos_asambleista_poblacion', label: 'Asambleista Poblacion' },
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
        papeletas_no_utilizadas_gobernador: 0,
        blancos_asambleista_distrito: 0,
        nulos_asambleista_distrito: 0,
        papeletas_no_utilizadas_asambleista_distrito: 0,
        blancos_asambleista_poblacion: 0,
        nulos_asambleista_poblacion: 0,
        papeletas_no_utilizadas_asambleista_poblacion: 0,
        blancos_concejal: 0,
        nulos_concejal: 0,
        papeletas_no_utilizadas_concejal: 0,
        blancos_alcalde: 0,
        nulos_alcalde: 0,
        papeletas_no_utilizadas_alcalde: 0,
        observacion: '',
        observacion_gobernador: '',
        observacion_asambleista_distrito: '',
        observacion_asambleista_poblacion: '',
        observacion_concejal: '',
        observacion_alcalde: ''
      },

      // fotos
      fotos: {
        foto1: null, foto2: null, foto3: null, foto4: null, foto5: null,
        foto6: null, foto7: null, foto8: null, foto9: null, foto10: null
      },
      fotosServer: {
        foto1_url: null, foto2_url: null, foto3_url: null, foto4_url: null, foto5_url: null,
        foto6_url: null, foto7_url: null, foto8_url: null, foto9_url: null, foto10_url: null
      },
      fotosToClear: {
        foto1: false, foto2: false, foto3: false, foto4: false, foto5: false,
        foto6: false, foto7: false, foto8: false, foto9: false, foto10: false
      },
      dlgFotoPreview: false,
      fotoPreviewSrc: null,
      socket: null,
      socketRefreshTimer: null
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

    departamentosOpt () {
      return (this.geoOptions.departamentos || []).map(d => ({
        label: d.nombre,
        value: d.id
      }))
    },

    provinciasOpt () {
      return (this.geoOptions.provincias || [])
        .filter(p => !this.filters.departamento_id || p.departamento_id === this.filters.departamento_id)
        .map(p => ({
          label: p.nombre,
          value: p.id
        }))
    },

    municipiosOpt () {
      return (this.geoOptions.municipios || [])
        .filter(m => !this.filters.provincia_id || m.provincia_id === this.filters.provincia_id)
        .map(m => ({
          label: m.nombre,
          value: m.id
        }))
    },

    localidadesOpt () {
      return (this.geoOptions.localidades || [])
        .filter(l => !this.filters.municipio_id || l.municipio_id === this.filters.municipio_id)
        .map(l => ({
          label: l.nombre,
          value: l.id
        }))
    },

    summaryTotal () {
      return Number(this.summary?.total || this.totalReal || 0)
    },

    countSinDelegado () {
      return Number(this.summary?.sin_delegado || 0)
    },
    countEnMesa () { return Number(this.summary?.en_mesa || 0) },
    countAsignadas () { return Number(this.summary?.asignadas || 0) },
    countConResultado () { return Number(this.summary?.con_resultado || 0) },

    partidosGobernador () {
      return this.sortPartidosBy((this.partidos || []).filter(p => !!p.habilitado_gobernador), 'orden_departamental')
    },

    partidosAsambleistaDistrito () {
      return this.sortPartidosBy((this.partidos || []).filter(p => !!p.habilitado_asambleista_distrito), 'orden_departamental')
    },

    partidosAsambleistaPoblacion () {
      return this.sortPartidosBy((this.partidos || []).filter(p => !!p.habilitado_asambleista_poblacion), 'orden_departamental')
    },

    partidosAlcalde () {
      return this.sortPartidosBy((this.partidos || []).filter(p => !!p.habilitado_alcalde), 'orden_municipal')
    },

    partidosConcejal () {
      return this.sortPartidosBy((this.partidos || []).filter(p => !!p.habilitado_concejal), 'orden_municipal')
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
      const p =
        Number(this.resForm.papeletas_no_utilizadas_gobernador || 0) +
        Number(this.resForm.papeletas_no_utilizadas_asambleista_distrito || 0) +
        Number(this.resForm.papeletas_no_utilizadas_asambleista_poblacion || 0) +
        Number(this.resForm.papeletas_no_utilizadas_concejal || 0) +
        Number(this.resForm.papeletas_no_utilizadas_alcalde || 0)
      return this.sumVotos + b + n + p
    },

    mismatchLabels () {
      return []
    },

    totalMismatchAny () {
      return this.mismatchLabels.length > 0
    }
  },

  async mounted () {
    await this.bootstrapPageData()
    this.connectSocket()
  },

  beforeUnmount () {
    const socketEvent = import.meta.env.VITE_SOCKET_EVENT || 'votacion'
    if (this.socketRefreshTimer) {
      clearTimeout(this.socketRefreshTimer)
      this.socketRefreshTimer = null
    }
    if (this.socket) {
      this.socket.off(socketEvent)
      this.socket.disconnect()
      this.socket = null
    }
  },

  methods: {
    applyMesasResponse (res) {
      this.allRows = res?.data || []
      this.totalReal = res?.total || this.allRows.length
      this.summary = res?.summary || this.summary
      this.backendTotal = res?.total || 0
      this.backendLast = res?.last_page || 1
      this.truncated = false
      this.maxCap = this.rowsPerPage === 0 ? 250 : this.rowsPerPage
    },

    connectSocket () {
      const socketUrl = import.meta.env.VITE_API_SOCKET
      const socketEvent = import.meta.env.VITE_SOCKET_EVENT || 'votacion'
      if (!socketUrl) return

      this.socket = io(socketUrl, {
        transports: ['websocket', 'polling'],
        reconnection: true
      })

      this.socket.on(socketEvent, (evt) => {
        this.onSocketVotacion(evt)
      })
    },

    onSocketVotacion (evt) {
      const data = typeof evt === 'string' ? { message: evt } : (evt || {})
      const title = data.title || 'Nuevo dato registrado'
      const caption = data.message || 'Se actualizó información de mesas'

      this.$alert.info(title, caption)

      if (this.socketRefreshTimer) clearTimeout(this.socketRefreshTimer)
      this.socketRefreshTimer = setTimeout(() => {
        this.refresh()
      }, 400)
    },

    sumByKey (key) {
      let s = 0
      for (const k of Object.keys(this.votosMap || {})) {
        const row = this.votosMap[k] || {}
        const v = Number(row[key] || 0)
        if (!Number.isNaN(v)) s += v
      }
      return s
    },

    sortPartidosBy (partidos, orderKey) {
      return (partidos || []).slice().sort((a, b) => {
        const oa = Number(a?.[orderKey] || 0)
        const ob = Number(b?.[orderKey] || 0)
        if (oa !== ob) return oa - ob
        return String(a?.sigla || '').localeCompare(String(b?.sigla || ''))
      })
    },

    colorEstado (e) {
      if (e === 'PENDIENTE') return 'grey-7'
      if (e === 'ASIGNADA') return 'primary'
      if (e === 'EN_PROCESO') return 'orange'
      if (e === 'FINALIZADA') return 'positive'
      if (e === 'OBSERVADA') return 'negative'
      return 'grey-7'
    },
    async toggleAsistenciaCapacitacion (row, value) {
      const previous = !value
      this.capacitacionSavingId = row.id
      try {
        await this.$axios.put(`admin/mesas/${row.id}/asistencia-capacitacion`, {
          asistencia_capacitacion: value
        })
        row.asistencia_capacitacion = value
        this.$alert.success(value ? 'Asistencia marcada' : 'Asistencia desmarcada')
      } catch (e) {
        row.asistencia_capacitacion = previous
        this.$alert?.error(e.response?.data?.message || 'No se pudo actualizar asistencia')
      } finally {
        this.capacitacionSavingId = null
      }
    },
    b (val) { return val ? 'positive' : 'grey-6' },

    onChangeRowsPerPage () {
      this.page = 1
      if (!this.showAll) this.refresh()
    },

    async bootstrapPageData () {
      this.loading = true
      try {
        this.showAll = false
        const perPage = this.rowsPerPage === 0 ? 250 : this.rowsPerPage
        const data = await this.$axios.get('admin/mesas/bootstrap', {
          params: {
            departamento_id: this.filters.departamento_id || undefined,
            provincia_id: this.filters.provincia_id || undefined,
            municipio_id: this.filters.municipio_id || undefined,
            localidad_id: this.filters.localidad_id || undefined,
            recinto_id: this.filters.recinto_id || undefined,
            mesa_id: this.filters.mesa_id || undefined,
            asignado: this.filters.asignado,
            delegado_id: this.filters.delegado_id || undefined,
            jefe_recinto_id: this.filters.jefe_recinto_id || undefined,
            supervisor_id: this.filters.supervisor_id || undefined,
            estado: this.filters.estado || undefined,
            con_resultado: this.filters.con_resultado,
            en_mesa: this.filters.en_mesa,
            acta_alcaldia: this.filters.acta_alcaldia,
            acta_gobernacion: this.filters.acta_gobernacion,
            all: 1,
            per_page: perPage,
            page: this.page
          }
        }).then(r => r.data)

        this.geoOptions = {
          departamentos: Array.isArray(data?.geo?.departamentos) ? data.geo.departamentos : [],
          provincias: Array.isArray(data?.geo?.provincias) ? data.geo.provincias : [],
          municipios: Array.isArray(data?.geo?.municipios) ? data.geo.municipios : [],
          localidades: Array.isArray(data?.geo?.localidades) ? data.geo.localidades : []
        }
        this.delegadosOpt = Array.isArray(data?.delegados) ? data.delegados : []
        this.jefesRecintoOpt = (Array.isArray(data?.jefes_recinto) ? data.jefes_recinto : []).map(j => ({
          ...j,
          label: `${j.name || '-'} (${j.username || '-'})`
        }))
        this.jefesRecintoOptFiltered = this.jefesRecintoOpt
        this.supervisoresOpt = (Array.isArray(data?.supervisores) ? data.supervisores : []).map(s => ({
          ...s,
          label: `${s.name || '-'} (${s.username || '-'})`
        }))
        this.supervisoresOptFiltered = this.supervisoresOpt
        this.recintosBase = (Array.isArray(data?.recintos) ? data.recintos : []).map(r => ({
          ...r,
          value: r.id,
          label: this.buildRecintoLabel(r)
        }))
        this.recintosOpt = this.recintosBase
        this.buildMunicipiosFullOptions()

        const base = this.buildDelegadosOptions()
        this.delegadosOptFiltered = base
        this.delegadosAsignarOptFiltered = base

        this.applyMesasResponse(data?.mesas || {})
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'No se pudo cargar la pantalla')
      } finally {
        this.loading = false
      }
    },

    async loadRecintosOptions () {
      const data = await this.$axios.get('admin/mesas/options/recintos', {
        params: {
          departamento_id: this.filters.departamento_id || undefined,
          provincia_id: this.filters.provincia_id || undefined,
          municipio_id: this.filters.municipio_id || undefined,
          localidad_id: this.filters.localidad_id || undefined
        }
      }).then(r => r.data)

      this.recintosBase = (Array.isArray(data) ? data : []).map(r => ({
        ...r,
        value: r.id,
        label: this.buildRecintoLabel(r)
      }))
      this.recintosOpt = this.recintosBase
      return this.recintosBase
    },

    buildMunicipiosFullOptions () {
      this.municipiosFullBase = (this.geoOptions.municipios || [])
        .filter(m => !this.filters.departamento_id || this.provinciaById(m.provincia_id)?.departamento_id === this.filters.departamento_id)
        .map(m => {
          const provincia = this.provinciaById(m.provincia_id)
          return {
            value: m.id,
            label: `${provincia?.nombre || 'Sin provincia'} · ${m.nombre}`,
            municipio_id: m.id,
            provincia_id: m.provincia_id,
            departamento_id: provincia?.departamento_id || null
          }
        })
      this.municipiosFullOpt = this.municipiosFullBase
    },

    provinciaById (id) {
      return (this.geoOptions.provincias || []).find(p => p.id === id) || null
    },

    recintoById (id) {
      return (this.recintosBase || []).find(r => r.id === id) || null
    },

    buildRecintoLabel (recinto) {
      return [
        recinto?.nombre,
        recinto?.localidad_nombre,
        recinto?.municipio_nombre,
        recinto?.provincia_nombre
      ].filter(Boolean).join(' · ')
    },

    buildDelegadosOptions () {
      return (this.delegadosOpt || []).map(d => ({
        ...d,
        label: `${d.name || '-'} (${d.username || '-'})`
      }))
    },

    filterDelegados (val, update) {
      update(() => {
        const needle = (val || '').toLowerCase().trim()
        const base = this.buildDelegadosOptions()
        if (!needle) {
          this.delegadosOptFiltered = base
          return
        }
        this.delegadosOptFiltered = base.filter(d =>
          String(d.name || '').toLowerCase().includes(needle) ||
          String(d.username || '').toLowerCase().includes(needle)
        )
      })
    },

    filterDelegadosAsignar (val, update) {
      update(() => {
        const needle = (val || '').toLowerCase().trim()
        const base = this.buildDelegadosOptions()
        if (!needle) {
          this.delegadosAsignarOptFiltered = base
          return
        }
        this.delegadosAsignarOptFiltered = base.filter(d =>
          String(d.name || '').toLowerCase().includes(needle) ||
          String(d.username || '').toLowerCase().includes(needle)
        )
      })
    },

    filterJefesRecinto (val, update) {
      update(() => {
        const needle = (val || '').toLowerCase().trim()
        if (!needle) {
          this.jefesRecintoOptFiltered = this.jefesRecintoOpt
          return
        }
        this.jefesRecintoOptFiltered = (this.jefesRecintoOpt || []).filter(j =>
          String(j.name || '').toLowerCase().includes(needle) ||
          String(j.username || '').toLowerCase().includes(needle) ||
          String(j.celular || '').toLowerCase().includes(needle)
        )
      })
    },

    filterSupervisores (val, update) {
      update(() => {
        const needle = (val || '').toLowerCase().trim()
        if (!needle) {
          this.supervisoresOptFiltered = this.supervisoresOpt
          return
        }
        this.supervisoresOptFiltered = (this.supervisoresOpt || []).filter(s =>
          String(s.name || '').toLowerCase().includes(needle) ||
          String(s.username || '').toLowerCase().includes(needle) ||
          String(s.celular || '').toLowerCase().includes(needle)
        )
      })
    },

    onPickDelegadoFiltro (delegadoId) {
      if (delegadoId) {
        this.filters.asignado = 'YES'
      }
    },

    onPickAsignado (val) {
      if (val === 'NO') {
        this.filters.delegado_id = null
      }
    },

    async onDepartamentoChange () {
      this.filters.provincia_id = null
      this.filters.municipio_id = null
      this.filters.localidad_id = null
      this.filters.municipio_full_id = null
      this.filters.recinto_id = null
      this.filters.mesa_id = null
      this.mesasOpt = []
      this.buildMunicipiosFullOptions()
      await this.loadRecintosOptions()
    },

    async onProvinciaChange () {
      this.filters.municipio_id = null
      this.filters.localidad_id = null
      this.filters.municipio_full_id = null
      this.filters.recinto_id = null
      this.filters.mesa_id = null
      this.mesasOpt = []
      this.buildMunicipiosFullOptions()
      await this.loadRecintosOptions()
    },

    async onMunicipioChange () {
      this.filters.localidad_id = null
      this.filters.municipio_full_id = this.filters.municipio_id || null
      this.filters.recinto_id = null
      this.filters.mesa_id = null
      this.mesasOpt = []
      await this.loadRecintosOptions()
    },

    async onLocalidadChange () {
      this.filters.recinto_id = null
      this.filters.mesa_id = null
      this.mesasOpt = []
      await this.loadRecintosOptions()
    },

    filterMunicipiosFull (val, update) {
      update(() => {
        const needle = (val || '').toLowerCase().trim()
        if (!needle) {
          this.buildMunicipiosFullOptions()
          return
        }
        this.municipiosFullOpt = (this.municipiosFullBase || []).filter(m =>
          String(m.label || '').toLowerCase().includes(needle)
        )
      })
    },

    async onPickMunicipioFull (municipioId) {
      if (!municipioId) {
        this.filters.provincia_id = null
        this.filters.municipio_id = null
        this.filters.localidad_id = null
        this.filters.recinto_id = null
        this.filters.mesa_id = null
        this.mesasOpt = []
        await this.loadRecintosOptions()
        return
      }
      const picked = (this.municipiosFullBase || []).find(m => m.value === municipioId) || null
      this.filters.departamento_id = picked?.departamento_id || this.filters.departamento_id
      this.filters.provincia_id = picked?.provincia_id || null
      this.filters.municipio_id = picked?.municipio_id || null
      this.filters.localidad_id = null
      this.filters.recinto_id = null
      this.filters.mesa_id = null
      this.mesasOpt = []
      await this.loadRecintosOptions()
    },

    filterRecintos (val, update) {
      update(() => {
        const needle = (val || '').toLowerCase().trim()
        if (!needle) { this.recintosOpt = this.recintosBase; return }
        this.recintosOpt = (this.recintosBase || []).filter(r =>
          String(r.label || '').toLowerCase().includes(needle) ||
          String(r.nombre || '').toLowerCase().includes(needle) ||
          String(r.localidad_nombre || '').toLowerCase().includes(needle) ||
          String(r.municipio_nombre || '').toLowerCase().includes(needle) ||
          String(r.provincia_nombre || '').toLowerCase().includes(needle) ||
          String(r.departamento_nombre || '').toLowerCase().includes(needle)
        )
      })
    },

    async onPickRecinto (recintoId) {
      const recinto = this.recintoById(recintoId)
      if (recinto) {
        this.filters.departamento_id = recinto.departamento_id || this.filters.departamento_id
        this.filters.provincia_id = recinto.provincia_id || null
        this.filters.municipio_id = recinto.municipio_id || null
        this.filters.localidad_id = recinto.localidad_id || null
        this.filters.municipio_full_id = recinto.municipio_id || null
      }
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
    buildMesaQueryParams () {
      return {
        departamento_id: this.filters.departamento_id || undefined,
        provincia_id: this.filters.provincia_id || undefined,
        municipio_id: this.filters.municipio_id || undefined,
        localidad_id: this.filters.localidad_id || undefined,
        recinto_id: this.filters.recinto_id || undefined,
        mesa_id: this.filters.mesa_id || undefined,
        asignado: this.filters.asignado,
        delegado_id: this.filters.delegado_id || undefined,
        jefe_recinto_id: this.filters.jefe_recinto_id || undefined,
        supervisor_id: this.filters.supervisor_id || undefined,
        estado: this.filters.estado || undefined,
        con_resultado: this.filters.con_resultado,
        en_mesa: this.filters.en_mesa,
        acta_alcaldia: this.filters.acta_alcaldia,
        acta_gobernacion: this.filters.acta_gobernacion
      }
    },

    buildPrintUrl (path, extraParams = {}) {
      const params = new URLSearchParams()
      const query = { departamento_id: 5, ...extraParams }

      Object.entries(query).forEach(([key, value]) => {
        if (value === undefined || value === null || value === '') return
        params.append(key, value)
      })

      return `${this.$url}/${path}?${params.toString()}`
    },

    async openBlobFile (path, extraParams = {}, filename = 'reporte.pdf', mimeType = 'application/pdf') {
      this.loadingPrint = true
      try {
        const url = this.buildPrintUrl(path, extraParams)
        const response = await this.$axios.get(url, {
          responseType: 'blob'
        })

        const blob = new Blob([response.data], { type: mimeType })
        const blobUrl = window.URL.createObjectURL(blob)
        const link = document.createElement('a')
        link.href = blobUrl
        if (mimeType === 'application/pdf') {
          link.target = '_blank'
          link.rel = 'noopener'
        }
        link.rel = 'noopener'
        link.download = filename
        document.body.appendChild(link)
        link.click()
        link.remove()

        setTimeout(() => {
          window.URL.revokeObjectURL(blobUrl)
        }, 1000)
      } catch (e) {
        throw e
      } finally {
        this.loadingPrint = false
      }
    },

    async openPdfBlob (path, extraParams = {}, filename = 'reporte.pdf') {
      try {
        await this.openBlobFile(path, extraParams, filename, 'application/pdf')
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'No se pudo generar el PDF')
      }
    },

    async openCsvBlob (path, extraParams = {}, filename = 'reporte.csv') {
      try {
        await this.openBlobFile(path, extraParams, filename, 'text/csv;charset=utf-8')
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'No se pudo generar el archivo')
      }
    },

    async printAsistenciaCapacitacion (asistio) {
      await this.openPdfBlob(
        'admin/mesas-print/asistencia-capacitacion',
        { asistio: asistio ? 1 : 0 },
        asistio ? 'asistencia_capacitacion_si.pdf' : 'asistencia_capacitacion_no.pdf'
      )
    },

    async printActas () {
      await this.openPdfBlob(
        'admin/mesas-print/actas',
        {},
        'actas_mesas.pdf'
      )
    },

    async printEnMesa () {
      await this.openPdfBlob(
        'admin/mesas-print/en-mesa',
        {
          departamento_id: this.filters.departamento_id || undefined,
          provincia_id: this.filters.provincia_id || undefined,
          municipio_id: this.filters.municipio_id || undefined,
          localidad_id: this.filters.localidad_id || undefined,
          recinto_id: this.filters.recinto_id || undefined,
          mesa_id: this.filters.mesa_id || undefined,
          asignado: this.filters.asignado,
          delegado_id: this.filters.delegado_id || undefined,
          jefe_recinto_id: this.filters.jefe_recinto_id || undefined,
          supervisor_id: this.filters.supervisor_id || undefined,
          estado: this.filters.estado || undefined,
          con_resultado: this.filters.con_resultado,
          en_mesa: 'YES',
          acta_alcaldia: this.filters.acta_alcaldia,
          acta_gobernacion: this.filters.acta_gobernacion
        },
        'delegados_en_mesa.pdf'
      )
    },

    async printNoEnMesa () {
      await this.openPdfBlob(
        'admin/mesas-print/en-mesa',
        {
          departamento_id: this.filters.departamento_id || undefined,
          provincia_id: this.filters.provincia_id || undefined,
          municipio_id: this.filters.municipio_id || undefined,
          localidad_id: this.filters.localidad_id || undefined,
          recinto_id: this.filters.recinto_id || undefined,
          mesa_id: this.filters.mesa_id || undefined,
          asignado: this.filters.asignado,
          delegado_id: this.filters.delegado_id || undefined,
          jefe_recinto_id: this.filters.jefe_recinto_id || undefined,
          supervisor_id: this.filters.supervisor_id || undefined,
          estado: this.filters.estado || undefined,
          con_resultado: this.filters.con_resultado,
          en_mesa: 'NO',
          acta_alcaldia: this.filters.acta_alcaldia,
          acta_gobernacion: this.filters.acta_gobernacion
        },
        'delegados_no_en_mesa.pdf'
      )
    },

    async printMesaAbierta () {
      await this.openPdfBlob(
        'admin/mesas-print/apertura',
        {
          departamento_id: this.filters.departamento_id || undefined,
          provincia_id: this.filters.provincia_id || undefined,
          municipio_id: this.filters.municipio_id || undefined,
          localidad_id: this.filters.localidad_id || undefined,
          recinto_id: this.filters.recinto_id || undefined,
          mesa_id: this.filters.mesa_id || undefined,
          asignado: this.filters.asignado,
          delegado_id: this.filters.delegado_id || undefined,
          jefe_recinto_id: this.filters.jefe_recinto_id || undefined,
          supervisor_id: this.filters.supervisor_id || undefined,
          estado: this.filters.estado || undefined,
          con_resultado: this.filters.con_resultado,
          acta_alcaldia: this.filters.acta_alcaldia,
          acta_gobernacion: this.filters.acta_gobernacion
        },
        'mesas_abiertas.pdf'
      )
    },

    openSupervisorPrintDialog () {
      this.supervisorPrintId = this.filters.supervisor_id || null
      this.supervisorPrintPreview = null
      this.dlgSupervisorPrint = true
    },

    async previewRecintosPorSupervisor () {
      if (!this.supervisorPrintId) return
      try {
        this.supervisorPrintPreview = await this.$axios.get(
          'admin/mesas-preview/recintos-por-supervisor',
          {
            params: { supervisor_id: this.supervisorPrintId }
          }
        ).then(r => r.data)
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'No se pudo buscar el supervisor')
      }
    },

    async printRecintosPorSupervisor () {
      if (!this.supervisorPrintId) return
      await this.openPdfBlob(
        'admin/mesas-print/recintos-por-supervisor',
        { supervisor_id: this.supervisorPrintId },
        'recintos_por_supervisor.pdf'
      )
      this.dlgSupervisorPrint = false
    },

    openRecintoPrintDialog () {
      this.recintoPrintId = this.filters.recinto_id || null
      this.recintoPrintPreview = null
      this.dlgRecintoPrint = true
    },

    async previewJerarquiaPorRecinto () {
      if (!this.recintoPrintId) return
      try {
        this.recintoPrintPreview = await this.$axios.get(
          'admin/mesas-preview/jerarquia-por-recinto',
          {
            params: { recinto_id: this.recintoPrintId }
          }
        ).then(r => r.data)
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'No se pudo buscar el recinto')
      }
    },

    async printJerarquiaPorRecinto () {
      if (!this.recintoPrintId) return
      await this.openPdfBlob(
        'admin/mesas-print/jerarquia-por-recinto',
        { recinto_id: this.recintoPrintId },
        'jerarquia_por_recinto.pdf'
      )
      this.dlgRecintoPrint = false
    },

    async fetchAll () {
      this.loadingAll = true
      try {
        const baseParams = {
          departamento_id: this.filters.departamento_id || undefined,
          provincia_id: this.filters.provincia_id || undefined,
          municipio_id: this.filters.municipio_id || undefined,
          localidad_id: this.filters.localidad_id || undefined,
          recinto_id: this.filters.recinto_id || undefined,
          mesa_id: this.filters.mesa_id || undefined,
          asignado: this.filters.asignado,
          delegado_id: this.filters.delegado_id || undefined,
          jefe_recinto_id: this.filters.jefe_recinto_id || undefined,
          supervisor_id: this.filters.supervisor_id || undefined,
          estado: this.filters.estado || undefined,
          con_resultado: this.filters.con_resultado,
          en_mesa: this.filters.en_mesa,
          acta_alcaldia: this.filters.acta_alcaldia,
          acta_gobernacion: this.filters.acta_gobernacion,
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
          if (res.summary) this.summary = res.summary
          this.allRows = all
        } while (page <= last)

        this.allRows = all
        this.totalReal = total
        if (last > 0) {
          // summary ya llega por página; se conserva el último valor recibido
        }
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
          departamento_id: this.filters.departamento_id || undefined,
          provincia_id: this.filters.provincia_id || undefined,
          municipio_id: this.filters.municipio_id || undefined,
          localidad_id: this.filters.localidad_id || undefined,
          recinto_id: this.filters.recinto_id || undefined,
          mesa_id: this.filters.mesa_id || undefined,
          asignado: this.filters.asignado,
          delegado_id: this.filters.delegado_id || undefined,
          jefe_recinto_id: this.filters.jefe_recinto_id || undefined,
          supervisor_id: this.filters.supervisor_id || undefined,
          estado: this.filters.estado || undefined,
          con_resultado: this.filters.con_resultado,
          en_mesa: this.filters.en_mesa,
          acta_alcaldia: this.filters.acta_alcaldia,
          acta_gobernacion: this.filters.acta_gobernacion,
          all: 1,
          per_page: perPage,
          page: this.page
        }

        const res = await this.$axios.get('admin/mesas', { params }).then(r => r.data)
        this.applyMesasResponse(res)
      } finally {
        this.loading = false
      }
    },

    hasPresenceData (row) {
      return !!(row?.aviso_antes || row?.delegado_latitud || row?.delegado_longitud || row?.delegado_presente_at)
    },

    openPresencia (row) {
      this.presenceRow = row
      this.dlgPresencia = true
    },

    fmtPresenceAt (value) {
      if (!value) return 'Sin horario registrado'
      const date = new Date(value)
      if (Number.isNaN(date.getTime())) return value
      return new Intl.DateTimeFormat('es-BO', {
        dateStyle: 'short',
        timeStyle: 'medium'
      }).format(date)
    },

    distanceKm (lat1, lng1, lat2, lng2) {
      const toRad = deg => (Number(deg) * Math.PI) / 180
      const r = 6371
      const dLat = toRad(lat2 - lat1)
      const dLng = toRad(lng2 - lng1)
      const a =
        Math.sin(dLat / 2) * Math.sin(dLat / 2) +
        Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
        Math.sin(dLng / 2) * Math.sin(dLng / 2)
      return r * (2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a)))
    },

    normalizeBoliviaCoords (latValue, lngValue) {
      let lat = Number(latValue)
      let lng = Number(lngValue)

      if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
        return { lat, lng }
      }

      const looksSwapped =
        lat <= -40 && lat >= -90 &&
        lng <= 0 && lng >= -35

      if (looksSwapped) {
        return { lat: lng, lng: lat }
      }

      return { lat, lng }
    },

    fmtDistanceToRecinto (row) {
      const delegado = this.normalizeBoliviaCoords(row?.delegado_latitud, row?.delegado_longitud)
      const recinto = this.normalizeBoliviaCoords(row?.recinto_latitud, row?.recinto_longitud)
      const lat1 = delegado.lat
      const lng1 = delegado.lng
      const lat2 = recinto.lat
      const lng2 = recinto.lng
      if (![lat1, lng1, lat2, lng2].every(Number.isFinite)) {
        return 'Sin coordenadas suficientes'
      }
      const km = this.distanceKm(lat1, lng1, lat2, lng2)
      if (km < 1) return `${Math.round(km * 1000)} m aprox.`
      return `${km.toFixed(2)} km aprox.`
    },

    presenceMapUrl (row) {
      const lat = row?.delegado_latitud
      const lng = row?.delegado_longitud
      if (lat == null || lng == null || lat === '' || lng === '') return null
      return `https://www.google.com/maps?q=${encodeURIComponent(`${lat},${lng}`)}`
    },

    normalizePhone (value) {
      const digits = String(value || '').replace(/\D/g, '')
      if (!digits) return ''
      if (digits.startsWith('591')) return digits
      if (digits.length === 8) return `591${digits}`
      return digits
    },

    whatsappUrl (value) {
      const phone = this.normalizePhone(value)
      if (!phone) return null
      return `https://wa.me/${phone}`
    },

    openWhatsapp (value) {
      const url = this.whatsappUrl(value)
      if (!url) return
      window.open(url, '_blank', 'noopener,noreferrer')
    },

    openPresenceMap () {
      const url = this.presenceMapUrl(this.presenceRow)
      if (!url) return
      window.open(url, '_blank', 'noopener,noreferrer')
    },

    openAsignar (row) {
      this.curMesa = row
      this.delegadoPick = row.delegado_id || null
      this.estadoPick = row.estado || 'ASIGNADA'
      this.delegadosAsignarOptFiltered = this.buildDelegadosOptions()
      this.dlgAsignar = true
    },

    async saveAsignar () {
      if (!this.curMesa?.id) return
      this.saving = true
      try {
        await this.$axios.put(`admin/mesas/${this.curMesa.id}/delegado`, {
          delegado_id: this.delegadoPick,
          estado: this.estadoPick
        })
        this.$alert.success(this.delegadoPick ? 'Delegado asignado' : 'Mesa liberada')
        this.dlgAsignar = false
        this.refresh()
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'No se pudo asignar')
      } finally {
        this.saving = false
      }
    },

    getImageUrl (path) {
      if (!path) return null
      if (path.startsWith('http')) return path
      const baseUrl = this.$url.split('/api')[0] || this.$url
      return baseUrl + (path.startsWith('/') ? '' : '/') + path
    },

    // preview: primero archivo local, si no hay -> foto server
    fotoPreview (n) {
      const key = `foto${n}`
      const f =  this.fotos[key]
      if (f) return URL.createObjectURL(f)
      if (this.fotosToClear[key]) return null

      const serverUrl = this.fotosServer[`${key}_url`]
      return this.getImageUrl(serverUrl)
    },

    openPhotoExternal (n) {
      const src = this.fotoPreview(n)
      if (!src) return
      window.open(src, '_blank', 'noopener,noreferrer')
    },

    clearFoto (n) {
      const key = `foto${n}`
      const serverKey = `${key}_url`
      const hadServerPhoto = !!this.fotosServer[serverKey]

      this.fotos[key] = null
      if (hadServerPhoto) {
        this.fotosToClear[key] = true
        this.fotosServer[serverKey] = null
      }
    },

    onFotoSelected (event, n) {
      const key = `foto${n}`
      const [file] = event?.target?.files || []
      this.fotos[key] = file || null
      this.fotosToClear[key] = false
      if (event?.target) {
        event.target.value = ''
      }
    },

    triggerFotoInput (n) {
      const input = this.$refs[`fotoInput${n}`]
      if (Array.isArray(input)) {
        input[0]?.click?.()
        return
      }
      input?.click?.()
    },

    ganadoresMesa (row) {
      const ganadores = row?.ganadores || {}
      const labels = [
        { key: 'gobernador', label: 'Gobernador' },
        { key: 'asambleista_distrito', label: 'Asambleista distrito' },
        { key: 'asambleista_poblacion', label: 'Asambleista poblacion' },
        { key: 'alcalde', label: 'Alcalde' },
        { key: 'concejal', label: 'Concejal' }
      ]

      return labels
        .map(item => {
          const ganador = ganadores[item.key]
          if (!ganador) return null

          return {
            key: item.key,
            label: item.label,
            icono: ganador.icono || ganador.partidos?.[0]?.icono || null,
            value: ganador.es_empate
              ? 'Empate'
              : (ganador.sigla || ganador.nombre || 'Sin partido')
          }
        })
        .filter(Boolean)
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
          papeletas_no_utilizadas_gobernador: 0,
          blancos_asambleista_distrito: 0,
          nulos_asambleista_distrito: 0,
          papeletas_no_utilizadas_asambleista_distrito: 0,
          blancos_asambleista_poblacion: 0,
          nulos_asambleista_poblacion: 0,
          papeletas_no_utilizadas_asambleista_poblacion: 0,
          blancos_concejal: 0,
          nulos_concejal: 0,
          papeletas_no_utilizadas_concejal: 0,
          blancos_alcalde: 0,
          nulos_alcalde: 0,
          papeletas_no_utilizadas_alcalde: 0,
          observacion: '',
          observacion_gobernador: '',
          observacion_asambleista_distrito: '',
          observacion_asambleista_poblacion: '',
          observacion_concejal: '',
          observacion_alcalde: ''
        }

        this.fotos = {
          foto1: null, foto2: null, foto3: null, foto4: null, foto5: null,
          foto6: null, foto7: null, foto8: null, foto9: null, foto10: null
        }
        this.fotosServer = {
          foto1_url: null, foto2_url: null, foto3_url: null, foto4_url: null, foto5_url: null,
          foto6_url: null, foto7_url: null, foto8_url: null, foto9_url: null, foto10_url: null
        }
        this.fotosToClear = {
          foto1: false, foto2: false, foto3: false, foto4: false, foto5: false,
          foto6: false, foto7: false, foto8: false, foto9: false, foto10: false
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
          this.resForm.papeletas_no_utilizadas_gobernador = Number(r.papeletas_no_utilizadas_gobernador || 0)
          this.resForm.blancos_asambleista_distrito = Number(r.blancos_asambleista_distrito || 0)
          this.resForm.nulos_asambleista_distrito = Number(r.nulos_asambleista_distrito || 0)
          this.resForm.papeletas_no_utilizadas_asambleista_distrito = Number(r.papeletas_no_utilizadas_asambleista_distrito || 0)
          this.resForm.blancos_asambleista_poblacion = Number(r.blancos_asambleista_poblacion || 0)
          this.resForm.nulos_asambleista_poblacion = Number(r.nulos_asambleista_poblacion || 0)
          this.resForm.papeletas_no_utilizadas_asambleista_poblacion = Number(r.papeletas_no_utilizadas_asambleista_poblacion || 0)
          this.resForm.blancos_concejal = Number(r.blancos_concejal || 0)
          this.resForm.nulos_concejal = Number(r.nulos_concejal || 0)
          this.resForm.papeletas_no_utilizadas_concejal = Number(r.papeletas_no_utilizadas_concejal || 0)
          this.resForm.blancos_alcalde = Number(r.blancos_alcalde || 0)
          this.resForm.nulos_alcalde = Number(r.nulos_alcalde || 0)
          this.resForm.papeletas_no_utilizadas_alcalde = Number(r.papeletas_no_utilizadas_alcalde || 0)
          this.resForm.observacion = r.observacion || ''
          this.resForm.observacion_gobernador = r.observacion_gobernador || ''
          this.resForm.observacion_asambleista_distrito = r.observacion_asambleista_distrito || ''
          this.resForm.observacion_asambleista_poblacion = r.observacion_asambleista_poblacion || ''
          this.resForm.observacion_concejal = r.observacion_concejal || ''
          this.resForm.observacion_alcalde = r.observacion_alcalde || ''

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
        fd.append('aviso_antes', this.resForm.aviso_antes ? '1' : '0')
        fd.append('aviso_manana', this.resForm.aviso_manana ? '1' : '0')
        fd.append('aviso_mediodia', this.resForm.aviso_mediodia ? '1' : '0')
        fd.append('aviso_tarde', this.resForm.aviso_tarde ? '1' : '0')
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
          Number(this.resForm.papeletas_no_utilizadas_gobernador || 0) +
          Number(this.resForm.papeletas_no_utilizadas_asambleista_distrito || 0) +
          Number(this.resForm.papeletas_no_utilizadas_asambleista_poblacion || 0) +
          Number(this.resForm.nulos_concejal || 0) +
          Number(this.resForm.nulos_alcalde || 0) +
          Number(this.resForm.papeletas_no_utilizadas_concejal || 0) +
          Number(this.resForm.papeletas_no_utilizadas_alcalde || 0)

        fd.append('total_blancos', String(totalBlancos))
        fd.append('total_nulos', String(totalNulos))
        fd.append('total_validos', String(this.sumVotos || 0))
        fd.append('observacion', this.resForm.observacion || '')
        fd.append('observacion_gobernador', this.resForm.observacion_gobernador || '')
        fd.append('observacion_asambleista_distrito', this.resForm.observacion_asambleista_distrito || '')
        fd.append('observacion_asambleista_poblacion', this.resForm.observacion_asambleista_poblacion || '')
        fd.append('observacion_concejal', this.resForm.observacion_concejal || '')
        fd.append('observacion_alcalde', this.resForm.observacion_alcalde || '')

        fd.append('blancos_gobernador', String(this.resForm.blancos_gobernador || 0))
        fd.append('nulos_gobernador', String(this.resForm.nulos_gobernador || 0))
        fd.append('papeletas_no_utilizadas_gobernador', String(this.resForm.papeletas_no_utilizadas_gobernador || 0))
        fd.append('blancos_asambleista_distrito', String(this.resForm.blancos_asambleista_distrito || 0))
        fd.append('nulos_asambleista_distrito', String(this.resForm.nulos_asambleista_distrito || 0))
        fd.append('papeletas_no_utilizadas_asambleista_distrito', String(this.resForm.papeletas_no_utilizadas_asambleista_distrito || 0))
        fd.append('blancos_asambleista_poblacion', String(this.resForm.blancos_asambleista_poblacion || 0))
        fd.append('nulos_asambleista_poblacion', String(this.resForm.nulos_asambleista_poblacion || 0))
        fd.append('papeletas_no_utilizadas_asambleista_poblacion', String(this.resForm.papeletas_no_utilizadas_asambleista_poblacion || 0))
        fd.append('blancos_concejal', String(this.resForm.blancos_concejal || 0))
        fd.append('nulos_concejal', String(this.resForm.nulos_concejal || 0))
        fd.append('papeletas_no_utilizadas_concejal', String(this.resForm.papeletas_no_utilizadas_concejal || 0))
        fd.append('blancos_alcalde', String(this.resForm.blancos_alcalde || 0))
        fd.append('nulos_alcalde', String(this.resForm.nulos_alcalde || 0))
        fd.append('papeletas_no_utilizadas_alcalde', String(this.resForm.papeletas_no_utilizadas_alcalde || 0))

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
        if (this.fotosToClear.foto1) fd.append('clear_foto1', '1')
        if (this.fotosToClear.foto2) fd.append('clear_foto2', '1')
        if (this.fotosToClear.foto3) fd.append('clear_foto3', '1')
        if (this.fotosToClear.foto4) fd.append('clear_foto4', '1')
        if (this.fotosToClear.foto5) fd.append('clear_foto5', '1')
        if (this.fotosToClear.foto6) fd.append('clear_foto6', '1')
        if (this.fotosToClear.foto7) fd.append('clear_foto7', '1')
        if (this.fotosToClear.foto8) fd.append('clear_foto8', '1')
        if (this.fotosToClear.foto9) fd.append('clear_foto9', '1')
        if (this.fotosToClear.foto10) fd.append('clear_foto10', '1')

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

<style scoped>
.foto-card {
  min-height: 100px;
}

.foto-preview,
.foto-preview-empty {
  height: 88px;
}

.ganadores-compactos {
  font-size: 11px;
  line-height: 1.05;
}

.ganador-item {
  display: flex;
  align-items: center;
  gap: 4px;
  margin-bottom: 2px;
}

.ganador-item:last-child {
  margin-bottom: 0;
}

.ganador-icono {
  width: 12px;
  height: 12px;
  flex: 0 0 12px;
}

.ganador-icono-fallback {
  width: 12px;
  text-align: center;
}

.ganador-texto {
  display: inline-block;
}
</style>

