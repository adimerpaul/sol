<template>
  <q-page class="q-pa-md bg-grey-2">
    <q-card flat bordered class="bg-white">
      <q-card-section class="row items-center">
        <div class="col">
          <div class="text-h6 text-weight-bold">Dashboard Graficos</div>
          <div class="text-caption text-grey-7">Resumen electoral y visor operativo del mapa en Oruro</div>
        </div>
        <div class="col-auto row items-center q-gutter-sm">
          <q-chip outline color="primary">Validos: {{ votosValidosTotal }}</q-chip>
          <q-chip outline color="secondary">Mesas: {{ mesas.total }}</q-chip>
          <q-chip outline color="positive">Con resultado: {{ mesas.con_resultado }}</q-chip>
          <q-chip outline color="orange">Faltantes: {{ mesas.faltantes }}</q-chip>
          <q-btn color="primary" icon="refresh" round dense :loading="loading" @click="loadDashboard" />
        </div>
      </q-card-section>

      <q-separator />

      <q-card-section class="q-pb-none">
        <div class="row q-col-gutter-sm items-center">
          <div class="col-12 col-md-3">
            <q-select v-model="filters.provincia_id" :options="provinciaOptions" emit-value map-options option-label="label" option-value="value" dense outlined clearable label="Provincia" @update:model-value="onProvinciaChange" />
          </div>
          <div class="col-12 col-md-3">
            <q-select v-model="filters.municipio_id" :options="municipioOptions" emit-value map-options option-label="label" option-value="value" dense outlined clearable label="Municipio" :disable="!filters.provincia_id" @update:model-value="onMunicipioChange" />
          </div>
          <div class="col-12 col-md-2">
            <q-select v-model="filters.localidad_id" :options="localidadOptions" emit-value map-options option-label="label" option-value="value" dense outlined clearable label="Localidad" :disable="!filters.municipio_id" @update:model-value="loadDashboard" />
          </div>
          <div class="col-12 col-md-3">
            <q-select v-model="filters.delegado_id" :options="delegadoOptions" emit-value map-options option-label="label" option-value="value" dense outlined clearable label="Delegado de mesa" @update:model-value="loadDashboard" />
          </div>
          <div class="col-12 col-md-auto">
            <q-btn flat color="grey-7" no-caps label="Limpiar" @click="clearFilters" />
          </div>
          <div v-if="enableDialogViewers" class="col-12 col-md-auto">
            <q-btn color="secondary" icon="map" no-caps label="Ver Mapa" @click="openMapViewer" />
          </div>
          <div v-if="enableDialogViewers" class="col-12 col-md-auto">
            <q-btn color="dark" icon="view_module" no-caps label="Ver Mesas" @click="openMesasViewer" />
          </div>
        </div>
      </q-card-section>

      <q-card-section v-if="isChartRoute" class="q-pa-md">
        <template v-if="chartsViewMode === 'both'">
          <div v-for="card in chartCards" :key="card.key" class="category-block q-mb-md">
            <div class="row items-center justify-between q-mb-sm">
              <div class="text-subtitle1 text-weight-bold">{{ card.label }}</div>
              <q-chip dense outline color="primary">Total: {{ card.total }}</q-chip>
            </div>
            <div class="row q-col-gutter-md">
              <div class="col-12 col-md-6">
                <q-card flat bordered class="q-pa-sm chart-modern">
                  <apexchart type="pie" height="280" :options="pieOptions(card)" :series="card.series" />
                </q-card>
              </div>
              <div class="col-12 col-md-6">
                <q-card flat bordered class="q-pa-sm chart-modern">
                  <apexchart type="bar" height="280" :options="barOptions(card)" :series="[{ name: card.label, data: card.series }]" />
                </q-card>
              </div>
            </div>
          </div>
        </template>

        <div v-else class="row q-col-gutter-md">
          <div v-for="card in chartCards" :key="`single-${chartsViewMode}-${card.key}`" class="col-12 col-md-4">
            <q-card flat bordered class="q-pa-sm chart-modern chart-grid-card q-mb-md">
              <div class="row items-center justify-between q-mb-sm">
                <div class="text-subtitle2 text-weight-bold">{{ card.label }}</div>
                <q-chip dense outline color="primary">Total: {{ card.total }}</q-chip>
              </div>
              <apexchart
                v-if="chartsViewMode === 'pie'"
                type="pie"
                height="250"
                :options="pieOptions(card)"
                :series="card.series"
              />
              <apexchart
                v-else
                type="bar"
                height="250"
                :options="barOptions(card)"
                :series="[{ name: card.label, data: card.series }]"
              />
            </q-card>
          </div>
        </div>
      </q-card-section>

      <q-card-section v-else-if="isMapRoute" class="q-pa-md">
        <div class="row q-col-gutter-sm q-mb-sm">
          <div class="col-12">
            <q-tabs v-model="viewerCategory" dense class="text-grey-8" active-color="secondary" indicator-color="secondary" align="justify" narrow-indicator>
              <q-tab v-for="cat in chartCards" :key="'route-map-' + cat.key" :name="cat.key" :label="cat.label" />
            </q-tabs>
          </div>
          <div class="col-12">
            <div class="row q-gutter-sm">
              <q-chip outline color="grey-7">Recintos: {{ mapData.length }}</q-chip>
              <q-chip outline color="grey-7">Pendientes: {{ mapStateCounts.pendiente }}</q-chip>
              <q-chip outline color="warning">En proceso: {{ mapStateCounts.proceso }}</q-chip>
              <q-chip outline color="positive">Ganados Jacha: {{ mapStateCounts.ganado }}</q-chip>
              <q-chip outline color="primary">Realizados sin ganar: {{ mapStateCounts.perdido }}</q-chip>
            </div>
          </div>
          <div class="col-12 col-md-8">
            <q-card flat bordered class="overflow-hidden" style="height: calc(100vh - 230px)">
              <l-map ref="mapRef" :zoom="zoom" :center="center" :use-global-leaflet="false" :options="{ attributionControl: false }">
                <l-control-layers position="topright" />
                <l-tile-layer layer-type="base" name="Mapa Claro" url="https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png" attribution="OpenStreetMap / CARTO" :max-zoom="20" :visible="true" />
                <template v-for="recinto in mapData" :key="`route-${recinto.id}`">
                  <l-marker :lat-lng="[recinto.lat, recinto.lng]" @click="selectMapRecinto(recinto)">
                    <l-icon :icon-anchor="[10, 10]" :popup-anchor="[0, -10]" class-name="modern-marker-container">
                      <div class="modern-marker" :class="{ 'modern-marker--active': selectedMapRecinto?.id === recinto.id }" :style="{ backgroundColor: markerState(recinto).color, boxShadow: `0 0 12px ${markerState(recinto).color}` }">
                        <div class="inner-dot"></div>
                      </div>
                    </l-icon>
                    <l-popup>
                      <div class="text-weight-bold text-primary">{{ recinto.nombre }}</div>
                      <div class="text-caption">Estado: {{ markerStateLabel(markerState(recinto).estado) }}</div>
                      <div class="text-caption">Mesas: {{ recinto.mesas_total }} · Con resultado: {{ recinto.mesas_con_resultado }}</div>
                      <div class="text-caption">Votos ganador: {{ markerState(recinto).votos }}</div>
                    </l-popup>
                  </l-marker>
                </template>
              </l-map>
            </q-card>
          </div>
          <div class="col-12 col-md-4">
            <q-card flat bordered class="map-panel route-panel">
              <q-card-section v-if="!selectedMapRecinto">
                <q-banner dense class="bg-grey-2">Selecciona un recinto para ver mesas, votos, supervisor, delegado y fotografías.</q-banner>
              </q-card-section>
              <template v-else>
                <q-card-section class="q-pb-sm">
                  <div class="text-subtitle1 text-weight-bold">{{ selectedMapRecinto.nombre }}</div>
                  <div class="row q-gutter-xs q-mt-sm">
                    <q-chip dense outline color="grey-8">Mesas: {{ selectedMapRecinto.mesas_total }}</q-chip>
                    <q-chip dense outline color="positive">Con resultado: {{ selectedMapRecinto.mesas_con_resultado }}</q-chip>
                    <q-chip dense outline :color="selectedMapRecinto.mesas_faltantes ? 'negative' : 'positive'">{{ selectedMapRecinto.mesas_faltantes ? `Faltan ${selectedMapRecinto.mesas_faltantes}` : 'Completo' }}</q-chip>
                    <q-chip dense :color="markerState(selectedMapRecinto).estado === 'ganado' ? 'positive' : (markerState(selectedMapRecinto).estado === 'proceso' ? 'warning' : (markerState(selectedMapRecinto).estado === 'perdido' ? 'primary' : 'grey-6'))" text-color="white">
                      {{ markerStateLabel(markerState(selectedMapRecinto).estado) }}
                    </q-chip>
                  </div>
                </q-card-section>
                <q-separator />
                <q-card-section class="scroll map-panel-scroll route-panel-scroll">
                  <q-expansion-item
                    v-for="mesa in selectedMapRecinto.mesas"
                    :key="`route-mesa-${mesa.id}`"
                    dense
                    expand-separator
                    icon="how_to_vote"
                    :label="`Mesa ${mesa.numero_mesa}`"
                    :caption="mesa.tiene_resultado ? `${mesa.resultado?.total_votos ?? 0} votos registrados` : 'Pendiente'"
                    :caption-class="mesa.tiene_resultado ? 'text-grey-7' : 'text-negative text-weight-bold'"
                  >
                    <div class="q-pa-sm">
                      <div class="row q-gutter-xs q-mb-sm">
                        <q-badge outline color="grey-8">Estado: {{ mesa.estado }}</q-badge>
                        <q-badge outline color="primary">Total votos: {{ mesa.resultado?.total_votos ?? 0 }}</q-badge>
                      </div>
                    </div>
                  </q-expansion-item>
                </q-card-section>
              </template>
            </q-card>
          </div>
        </div>
      </q-card-section>

      <q-card-section v-else-if="isMesasRoute" class="q-pa-md">
        <div class="text-subtitle1 text-weight-bold">Historial en vivo</div>
        <div class="text-caption text-grey-7 row items-center q-gutter-xs q-mb-md">
          <span class="live-dot" />
          <span>Actualización en tiempo real de las últimas mesas registradas</span>
        </div>

        <div class="row q-col-gutter-sm q-mb-md">
          <div class="col-12 col-md-4">
            <q-select v-model="monitorFilters.recinto_id" :options="monitorRecintoOptions" option-label="label" option-value="value" emit-value map-options use-input input-debounce="0" clearable dense outlined label="Filtrar por recinto" @filter="filterMonitorRecintos" />
          </div>
          <div class="col-12 col-md-4">
            <q-select v-model="monitorFilters.confirmador" :options="monitorConfirmadorOptions" option-label="label" option-value="value" emit-value map-options use-input input-debounce="0" clearable dense outlined label="Filtrar por confirmador" @filter="filterMonitorConfirmadores" />
          </div>
          <div class="col-12 col-md-3">
            <q-select v-model="monitorFilters.ganador" :options="monitorWinnerOptions" option-label="label" option-value="value" emit-value map-options use-input input-debounce="0" clearable dense outlined label="Filtrar por ganador" @filter="filterMonitorWinners" />
          </div>
          <div class="col-12 col-md-3">
            <q-select v-model="monitorFilters.categoria" :options="monitorCategoryOptions" option-label="label" option-value="value" emit-value map-options use-input input-debounce="0" clearable dense outlined label="Filtrar por categoría" @filter="filterMonitorCategories" />
          </div>
          <div class="col-12 col-md-1">
            <q-btn flat color="grey-7" no-caps label="Limpiar" @click="clearMesasViewerFilters" />
          </div>
        </div>

        <div class="row q-col-gutter-md monitor-shell">
          <div class="col-12 col-lg-9">
            <div v-if="!filteredMonitorCards.length" class="q-pa-xl text-center text-grey-7">No hay mesas para mostrar.</div>
            <div v-else class="monitor-grid">
              <q-card v-for="card in filteredMonitorCards" :key="`route-card-${card.id}`" flat bordered class="monitor-card">
                <q-card-section class="q-pa-sm">
                  <div class="row items-start justify-between q-col-gutter-sm">
                    <div class="col">
                      <div class="text-h6 text-weight-bold">Mesa {{ card.numero }}</div>
                      <div class="text-subtitle2 text-grey-8">{{ card.recinto }}</div>
                    </div>
                    <div class="col-auto">
                      <q-chip dense square :color="card.tieneResultado ? 'positive' : 'negative'" text-color="white">
                        {{ card.tieneResultado ? `${card.totalVotos} votos` : 'Pendiente' }}
                      </q-chip>
                    </div>
                  </div>
                  <div class="winner-box q-mt-sm" :style="{ borderColor: card.ganadorColor || '#d1d5db' }">
                    <div class="row items-center no-wrap q-gutter-sm">
                      <q-avatar v-if="card.ganadorIcono" size="28px" rounded><img :src="assetUrl(card.ganadorIcono)" alt="logo ganador"></q-avatar>
                      <q-avatar v-else size="28px" rounded :style="{ backgroundColor: card.ganadorColor || '#cbd5e1' }" />
                      <div class="col">
                        <div class="text-caption text-grey-7">{{ card.tieneResultado ? 'Ganador' : 'Estado' }}</div>
                        <div class="text-weight-bold" :style="{ color: card.ganadorColor || '#111827' }">{{ card.ganador }}</div>
                        <div v-if="card.tieneResultado && card.ganadorCategoria" class="text-caption text-grey-7">
                          {{ card.ganadorCategoria }}
                        </div>
                      </div>
                      <q-chip dense square :style="{ backgroundColor: card.ganadorColor || '#111827', color: '#fff' }">{{ card.tieneResultado ? card.ganadorVotos : '-' }}</q-chip>
                    </div>
                  </div>
                  <div class="q-mt-sm text-caption text-grey-8">
                    <div>Confirmó: {{ card.confirmador }}</div>
                    <div>Hora: {{ card.hora }}</div>
                  </div>
                </q-card-section>
              </q-card>
            </div>
          </div>
          <div class="col-12 col-lg-3">
            <q-card flat bordered class="history-card">
              <q-card-section class="q-pb-sm">
                <div class="text-subtitle1 text-weight-bold">Historial</div>
                <div class="text-caption text-grey-7">Últimas mesas que llegaron</div>
              </q-card-section>
              <q-separator />
              <q-list separator class="history-list">
                <q-item v-for="item in mesasHistory" :key="`route-history-${item.id}`">
                  <q-item-section avatar>
                    <q-avatar size="30px" rounded v-if="item.ganadorIcono"><img :src="assetUrl(item.ganadorIcono)" alt="logo ganador"></q-avatar>
                    <q-avatar v-else size="30px" rounded :style="{ backgroundColor: item.ganadorColor || '#94a3b8' }" />
                  </q-item-section>
                  <q-item-section>
                    <q-item-label class="text-weight-medium">Mesa {{ item.numero }} · {{ item.ganador }}</q-item-label>
                    <q-item-label caption v-if="item.ganadorCategoria">{{ item.ganadorCategoria }}</q-item-label>
                    <q-item-label caption>{{ item.recinto }}</q-item-label>
                    <q-item-label caption>{{ item.hora }} · {{ item.confirmador }}</q-item-label>
                  </q-item-section>
                </q-item>
              </q-list>
            </q-card>
          </div>
        </div>
      </q-card-section>

      <q-inner-loading :showing="loading">
        <q-spinner />
      </q-inner-loading>
    </q-card>

    <q-dialog v-if="enableDialogViewers" v-model="mapViewerOpen" maximized transition-show="slide-up" transition-hide="slide-down">
      <q-card class="bg-grey-1 text-dark">
        <q-toolbar class="bg-secondary text-white">
          <q-toolbar-title class="text-weight-bold">Visor de Mapa Ganadores</q-toolbar-title>
          <q-btn flat dense no-caps icon="refresh" label="Actualizar" :loading="loadingMap" @click="loadDashboard" />
          <q-btn flat round dense icon="close" v-close-popup />
        </q-toolbar>

        <q-card-section>
          <div class="row q-col-gutter-sm q-mb-sm">
            <div class="col-12">
              <q-tabs v-model="viewerCategory" dense class="text-grey-8" active-color="secondary" indicator-color="secondary" align="justify" narrow-indicator>
                <q-tab v-for="cat in chartCards" :key="'map-' + cat.key" :name="cat.key" :label="cat.label" />
              </q-tabs>
            </div>
            <div class="col-12">
              <div class="row q-gutter-sm">
                <q-chip outline color="grey-7">Recintos: {{ mapData.length }}</q-chip>
                <q-chip outline color="grey-7">Pendientes: {{ mapStateCounts.pendiente }}</q-chip>
                <q-chip outline color="warning">En proceso: {{ mapStateCounts.proceso }}</q-chip>
                <q-chip outline color="positive">Ganados Jacha: {{ mapStateCounts.ganado }}</q-chip>
                <q-chip outline color="primary">Realizados sin ganar: {{ mapStateCounts.perdido }}</q-chip>
              </div>
            </div>
            <div class="col-12 col-md-8">
              <q-card flat bordered class="overflow-hidden" style="height: calc(100vh - 180px)">
                <l-map ref="mapRef" :zoom="zoom" :center="center" :use-global-leaflet="false" :options="{ attributionControl: false }">
                  <l-control-layers position="topright" />
                  <l-tile-layer layer-type="base" name="Mapa Claro" url="https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png" attribution="OpenStreetMap / CARTO" :max-zoom="20" :visible="true" />
                  <template v-for="recinto in mapData" :key="recinto.id">
                    <l-marker :lat-lng="[recinto.lat, recinto.lng]" @click="selectMapRecinto(recinto)">
                      <l-icon :icon-anchor="[10, 10]" :popup-anchor="[0, -10]" class-name="modern-marker-container">
                        <div class="modern-marker" :class="{ 'modern-marker--active': selectedMapRecinto?.id === recinto.id }" :style="{ backgroundColor: markerState(recinto).color, boxShadow: `0 0 12px ${markerState(recinto).color}` }">
                          <div class="inner-dot"></div>
                        </div>
                      </l-icon>
                      <l-popup>
                        <div class="text-weight-bold text-primary">{{ recinto.nombre }}</div>
                        <div class="text-caption">Estado: {{ markerStateLabel(markerState(recinto).estado) }}</div>
                        <div class="text-caption">Mesas: {{ recinto.mesas_total }} · Con resultado: {{ recinto.mesas_con_resultado }}</div>
                        <div class="text-caption">Votos ganador: {{ markerState(recinto).votos }}</div>
                      </l-popup>
                    </l-marker>
                  </template>
                </l-map>
              </q-card>
            </div>
            <div class="col-12 col-md-4">
              <q-card flat bordered class="map-panel">
                <q-card-section v-if="!selectedMapRecinto">
                  <q-banner dense class="bg-grey-2">Selecciona un recinto para ver mesas, votos, supervisor, delegado y fotografias.</q-banner>
                </q-card-section>
                <template v-else>
                  <q-card-section class="q-pb-sm">
                    <div class="text-subtitle1 text-weight-bold">{{ selectedMapRecinto.nombre }}</div>
                    <div class="row q-gutter-xs q-mt-sm">
                      <q-chip dense outline color="grey-8">Mesas: {{ selectedMapRecinto.mesas_total }}</q-chip>
                      <q-chip dense outline color="positive">Con resultado: {{ selectedMapRecinto.mesas_con_resultado }}</q-chip>
                      <q-chip dense outline :color="selectedMapRecinto.mesas_faltantes ? 'negative' : 'positive'">{{ selectedMapRecinto.mesas_faltantes ? `Faltan ${selectedMapRecinto.mesas_faltantes}` : 'Completo' }}</q-chip>
                      <q-chip dense :color="markerState(selectedMapRecinto).estado === 'ganado' ? 'positive' : (markerState(selectedMapRecinto).estado === 'proceso' ? 'warning' : (markerState(selectedMapRecinto).estado === 'perdido' ? 'primary' : 'grey-6'))" text-color="white">
                        {{ markerStateLabel(markerState(selectedMapRecinto).estado) }}
                      </q-chip>
                    </div>
                  </q-card-section>
                  <q-separator />
                  <q-card-section class="scroll map-panel-scroll">
                    <q-expansion-item
                      v-for="mesa in selectedMapRecinto.mesas"
                      :key="mesa.id"
                      dense
                      expand-separator
                      icon="how_to_vote"
                      :label="`Mesa ${mesa.numero_mesa}`"
                      :caption="mesa.tiene_resultado ? `${mesa.resultado?.total_votos ?? 0} votos registrados` : 'Pendiente'"
                      :caption-class="mesa.tiene_resultado ? 'text-grey-7' : 'text-negative text-weight-bold'"
                    >
                      <template v-slot:header>
<!--                        <q-item-section avatar>-->
<!--                          <q-avatar icon="bluetooth" color="primary" text-color="white" />-->
<!--                        </q-item-section>-->

                        <q-item-section>
                          <div class="row items-center no-wrap q-gutter-sm">
                            <span class="text-weight-bold">Mesa {{ mesa.numero_mesa }}</span>
                            <q-chip dense :color="mesa.tiene_resultado ? 'positive' : 'grey-6'" text-color="white">{{ mesa.tiene_resultado ? 'Con resultado' : 'Pendiente' }}</q-chip>
                          </div>
                        </q-item-section>

<!--                        <q-item-section side>-->
<!--&lt;!&ndash;                          <div class="row items-center">&ndash;&gt;-->
<!--&lt;!&ndash;                            <q-icon name="star" color="red" size="24px" />&ndash;&gt;-->
<!--&lt;!&ndash;                            <q-icon name="star" color="red" size="24px" />&ndash;&gt;-->
<!--&lt;!&ndash;                            <q-icon name="star" color="red" size="24px" />&ndash;&gt;-->
<!--&lt;!&ndash;                          </div>&ndash;&gt;-->
<!--                        </q-item-section>-->
                      </template>
                      <div class="q-pa-sm">
                        <div class="row q-gutter-xs q-mb-sm">
                          <q-badge outline color="grey-8">Estado: {{ mesa.estado }}</q-badge>
                          <q-badge outline color="primary">Total votos: {{ mesa.resultado?.total_votos ?? 0 }}</q-badge>
                          <q-badge outline color="secondary">Validos: {{ mesa.resultado?.total_validos ?? 0 }}</q-badge>
                          <q-chip dense :color="mesa.tiene_resultado ? 'positive' : 'grey-6'" text-color="white">{{ mesa.tiene_resultado ? 'Con resultado' : 'Pendiente' }}</q-chip>
                        </div>
                        <div class="text-caption text-grey-7">Delegado</div>
                        <div class="text-body2 text-weight-medium">{{ mesa.delegado?.name || 'Sin delegado asignado' }} <span v-if="mesa.delegado?.username" class="text-grey-7">({{ mesa.delegado.username }})</span></div>
                        <div v-if="mesa.delegado?.celular" class="text-caption text-grey-7">{{ mesa.delegado.celular }}</div>
                        <div class="text-caption text-grey-7 q-mt-sm">Supervisor(es)</div>
                        <div v-if="mesa.delegado?.supervisores?.length" class="q-mb-sm">
                          <q-chip v-for="sup in mesa.delegado.supervisores" :key="`sup-${mesa.id}-${sup.id}`" dense outline color="primary">{{ sup.name || sup.username }}</q-chip>
                        </div>
                        <div v-else class="text-caption text-grey-6 q-mb-sm">Sin supervisor relacionado</div>
                        <div v-if="mesa.resultado?.detalles?.length">
                          <div class="text-caption text-grey-7 q-mb-xs">Votos por partido</div>
                          <q-markup-table dense flat bordered separator="cell">
                            <thead>
                              <tr>
                                <th class="text-left">Partido</th>
                                <th class="text-right">{{ activeCategoryColumnLabel }}</th>
                              </tr>
                            </thead>
                            <tbody>
                              <tr v-for="detalle in mesa.resultado.detalles" :key="`det-${mesa.id}-${detalle.partido_id}`">
                                <td class="text-left">
                                  <div class="row items-center no-wrap q-gutter-sm">
                                    <q-avatar v-if="detalle.icono_url" size="24px" rounded>
                                      <img :src="assetUrl(detalle.icono_url)" alt="logo partido">
                                    </q-avatar>
                                    <q-avatar v-else size="24px" rounded :style="{ backgroundColor: detalle.color || '#cbd5e1' }" />
                                    <span :style="{ color: detalle.color || '#111827' }">{{ detalle.sigla || detalle.nombre || '-' }}</span>
                                  </div>
                                </td>
                                <td class="text-right">{{ detalle[activeCategoryField] ?? 0 }}</td>
                              </tr>
                            </tbody>
                          </q-markup-table>
                        </div>
                        <div v-if="mesa.resultado?.fotos?.length" class="q-mt-sm">
                          <div class="row items-center justify-between q-mb-xs">
                            <div class="text-caption text-grey-7">Fotografias</div>
                            <q-btn flat dense no-caps color="secondary" icon="open_in_new" label="Ver fotos" @click="openPhoto(mesa.resultado.fotos[0]?.url)" />
                          </div>
                          <div class="row q-col-gutter-sm">
                            <div v-for="foto in mesa.resultado.fotos" :key="`foto-${mesa.id}-${foto.slot}`" class="col-6">
                              <q-img :src="assetUrl(foto.url)" ratio="1" class="rounded-borders" />
                            </div>
                          </div>
                        </div>
                        <div v-if="mesa.delegado && (mesa.delegado.foto_personal_url || mesa.delegado.ci_anverso_url || mesa.delegado.ci_reverso_url)" class="q-mt-sm">
                          <div class="text-caption text-grey-7 q-mb-xs">Documentacion del delegado</div>
                          <div class="row q-col-gutter-sm">
                            <div v-if="mesa.delegado.foto_personal_url" class="col-4"><q-img :src="assetUrl(mesa.delegado.foto_personal_url)" ratio="1" class="rounded-borders" /></div>
                            <div v-if="mesa.delegado.ci_anverso_url" class="col-4"><q-img :src="assetUrl(mesa.delegado.ci_anverso_url)" ratio="1" class="rounded-borders" /></div>
                            <div v-if="mesa.delegado.ci_reverso_url" class="col-4"><q-img :src="assetUrl(mesa.delegado.ci_reverso_url)" ratio="1" class="rounded-borders" /></div>
                          </div>
                        </div>
                      </div>
                    </q-expansion-item>
                  </q-card-section>
                </template>
              </q-card>
            </div>
          </div>
        </q-card-section>
        <q-inner-loading :showing="loadingMap"><q-spinner color="secondary" size="4em" /></q-inner-loading>
      </q-card>
    </q-dialog>

    <q-dialog v-if="enableDialogViewers" v-model="mesasViewerOpen" maximized transition-show="slide-up" transition-hide="slide-down">
      <q-card class="bg-grey-1 text-dark">
        <q-toolbar class="bg-dark text-white">
          <q-toolbar-title class="text-weight-bold">Mesas del control</q-toolbar-title>
          <div class="text-caption q-mr-md">{{ filteredMonitorCards.length }} mesas</div>
          <q-btn flat dense no-caps icon="refresh" label="Actualizar" :loading="loading" @click="loadDashboard" />
          <q-btn flat round dense icon="close" v-close-popup />
        </q-toolbar>

        <q-card-section class="q-pb-none">
          <div class="text-subtitle1 text-weight-bold">Historial en vivo</div>
          <div class="text-caption text-grey-7 row items-center q-gutter-xs">
            <span class="live-dot" />
            <span>Actualización en tiempo real de mesas con datos</span>
          </div>
        </q-card-section>

        <q-card-section>
          <div class="row q-col-gutter-sm q-mb-md">
            <div class="col-12 col-md-4">
              <q-select
                v-model="monitorFilters.recinto_id"
                :options="monitorRecintoOptions"
                option-label="label"
                option-value="value"
                emit-value
                map-options
                use-input
                input-debounce="0"
                clearable
                dense
                outlined
                label="Filtrar por recinto"
                @filter="filterMonitorRecintos"
              />
            </div>
            <div class="col-12 col-md-4">
              <q-select
                v-model="monitorFilters.confirmador"
                :options="monitorConfirmadorOptions"
                option-label="label"
                option-value="value"
                emit-value
                map-options
                use-input
                input-debounce="0"
                clearable
                dense
                outlined
                label="Filtrar por confirmador"
                @filter="filterMonitorConfirmadores"
              />
            </div>
            <div class="col-12 col-md-3">
              <q-select
                v-model="monitorFilters.ganador"
                :options="monitorWinnerOptions"
                option-label="label"
                option-value="value"
                emit-value
                map-options
                use-input
                input-debounce="0"
                clearable
                dense
                outlined
                label="Filtrar por ganador"
                @filter="filterMonitorWinners"
              />
            </div>
            <div class="col-12 col-md-3">
              <q-select
                v-model="monitorFilters.categoria"
                :options="monitorCategoryOptions"
                option-label="label"
                option-value="value"
                emit-value
                map-options
                use-input
                input-debounce="0"
                clearable
                dense
                outlined
                label="Filtrar por categoría"
                @filter="filterMonitorCategories"
              />
            </div>
            <div class="col-12 col-md-1">
              <q-btn flat color="grey-7" no-caps label="Limpiar" @click="clearMesasViewerFilters" />
            </div>
          </div>

          <div class="row q-col-gutter-md monitor-shell">
            <div class="col-12 col-lg-9">
              <div v-if="!filteredMonitorCards.length" class="q-pa-xl text-center text-grey-7">
                No hay mesas para mostrar.
              </div>
              <div v-else class="monitor-grid">
                <q-card v-for="card in filteredMonitorCards" :key="card.id" flat bordered class="monitor-card">
                  <q-card-section class="q-pa-sm">
                    <div class="row items-start justify-between q-col-gutter-sm">
                      <div class="col">
                        <div class="text-h6 text-weight-bold">Mesa {{ card.numero }}</div>
                        <div class="text-subtitle2 text-grey-8">{{ card.recinto }}</div>
                      </div>
                      <div class="col-auto">
                        <q-chip dense square :color="card.tieneResultado ? 'positive' : 'negative'" text-color="white">
                          {{ card.tieneResultado ? `${card.totalVotos} votos` : 'Pendiente' }}
                        </q-chip>
                      </div>
                    </div>

                    <div class="winner-box q-mt-sm" :style="{ borderColor: card.ganadorColor || '#d1d5db' }">
                      <div class="row items-center no-wrap q-gutter-sm">
                        <q-avatar v-if="card.ganadorIcono" size="28px" rounded>
                          <img :src="assetUrl(card.ganadorIcono)" alt="logo ganador">
                        </q-avatar>
                        <q-avatar v-else size="28px" rounded :style="{ backgroundColor: card.ganadorColor || '#cbd5e1' }" />
                        <div class="col">
                          <div class="text-caption text-grey-7">{{ card.tieneResultado ? 'Ganador' : 'Estado' }}</div>
                          <div class="text-weight-bold" :style="{ color: card.ganadorColor || '#111827' }">
                            {{ card.ganador }}
                          </div>
                          <div v-if="card.tieneResultado && card.ganadorCategoria" class="text-caption text-grey-7">
                            {{ card.ganadorCategoria }}
                          </div>
                        </div>
                        <q-chip dense square :style="{ backgroundColor: card.ganadorColor || '#111827', color: '#fff' }">
                          {{ card.tieneResultado ? card.ganadorVotos : '-' }}
                        </q-chip>
                      </div>
                    </div>

                    <div class="q-mt-sm text-caption text-grey-8">
                      <div>Confirmó: {{ card.confirmador }}</div>
                      <div>Hora: {{ card.hora }}</div>
                    </div>
                  </q-card-section>
                </q-card>
              </div>
            </div>

            <div class="col-12 col-lg-3">
              <q-card flat bordered class="history-card">
                <q-card-section class="q-pb-sm">
                  <div class="text-subtitle1 text-weight-bold">Historial</div>
                  <div class="text-caption text-grey-7">Últimas mesas que llegaron</div>
                </q-card-section>
                <q-separator />
                <q-list separator class="history-list">
                  <q-item v-for="item in mesasHistory" :key="`history-${item.id}`">
                    <q-item-section avatar>
                      <q-avatar size="30px" rounded v-if="item.ganadorIcono">
                        <img :src="assetUrl(item.ganadorIcono)" alt="logo ganador">
                      </q-avatar>
                      <q-avatar v-else size="30px" rounded :style="{ backgroundColor: item.ganadorColor || '#94a3b8' }" />
                    </q-item-section>
                    <q-item-section>
                      <q-item-label class="text-weight-medium">Mesa {{ item.numero }} · {{ item.ganador }}</q-item-label>
                      <q-item-label caption v-if="item.ganadorCategoria">{{ item.ganadorCategoria }}</q-item-label>
                      <q-item-label caption>{{ item.recinto }}</q-item-label>
                      <q-item-label caption>{{ item.hora }} · {{ item.confirmador }}</q-item-label>
                    </q-item-section>
                  </q-item>
                </q-list>
              </q-card>
            </div>
          </div>
        </q-card-section>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script>
import { io } from 'socket.io-client'
import { LMap, LTileLayer, LMarker, LIcon, LPopup, LControlLayers } from '@vue-leaflet/vue-leaflet'
import 'leaflet/dist/leaflet.css'
import L from 'leaflet'
import markerIcon2xUrl from 'leaflet/dist/images/marker-icon-2x.png'
import markerIconUrl from 'leaflet/dist/images/marker-icon.png'
import markerShadowUrl from 'leaflet/dist/images/marker-shadow.png'

L.Icon.Default.mergeOptions({ iconRetinaUrl: markerIcon2xUrl, iconUrl: markerIconUrl, shadowUrl: markerShadowUrl })

const FALLBACK_COLORS = ['#1e88e5', '#43a047', '#fb8c00', '#8e24aa', '#e53935', '#00897b']
const CATEGORY_DEFS = [
  { key: 'alcalde', label: 'Alcalde', valueField: 'votos_alcalde' },
  { key: 'concejal', label: 'Concejal', valueField: 'votos_concejal' },
  { key: 'gobernador', label: 'Gobernador', valueField: 'votos_gobernador' },
  { key: 'asambleista_distrito', label: 'Asambleista por Territorio', valueField: 'votos_asambleista_distrito' },
  { key: 'asambleista_poblacion', label: 'Asambleista por Poblacion', valueField: 'votos_asambleista_poblacion' }
]

export default {
  name: 'IndexPage',
  components: { LMap, LTileLayer, LMarker, LIcon, LPopup, LControlLayers },
  data () {
    return {
      loading: false,
      loadingMap: false,
      votosValidosTotal: 0,
      categorias: {},
      categoryRankings: { alcalde: [], concejal: [], gobernador: [], asambleista_distrito: [], asambleista_poblacion: [] },
      mesas: { total: 0, con_resultado: 0, faltantes: 0 },
      filters: { provincia_id: 57, municipio_id: 191, localidad_id: 1988, delegado_id: null },
      geoOptions: { provincias: [], municipios: [], localidades: [], delegados: [] },
      mapViewerOpen: false,
      mesasViewerOpen: false,
      viewerCategory: 'alcalde',
      mapData: [],
      selectedMapRecinto: null,
      zoom: 12,
      center: [-17.9647, -67.1060],
      monitorFilters: {
        recinto_id: null,
        confirmador: null,
        ganador: null,
        categoria: null
      },
      monitorOptionPool: {
        recintos: [],
        confirmadores: [],
        ganadores: [],
        categorias: []
      },
      monitorOptions: {
        recintos: [],
        confirmadores: [],
        ganadores: [],
        categorias: []
      },
      chartsViewMode: 'both',
      audioContext: null,
      isAlive: false,
      dashboardRequestId: 0,
      socket: null,
      socketRefreshTimer: null
    }
  },
  computed: {
    dashboardMode () {
      return this.$route?.meta?.dashboardMode || 'both'
    },
    isChartRoute () {
      return ['both', 'pie', 'bar'].includes(this.dashboardMode)
    },
    isMapRoute () {
      return this.dashboardMode === 'mapa'
    },
    isMesasRoute () {
      return this.dashboardMode === 'mesas'
    },
    enableDialogViewers () {
      return false
    },
    provinciaOptions () { return (this.geoOptions.provincias || []).map(p => ({ label: p.nombre, value: p.id })) },
    municipioOptions () { return (this.geoOptions.municipios || []).map(m => ({ label: m.nombre, value: m.id })) },
    localidadOptions () { return (this.geoOptions.localidades || []).map(l => ({ label: l.nombre, value: l.id })) },
    delegadoOptions () { return (this.geoOptions.delegados || []).map(d => ({ label: `${d.name || '-'} (${d.username || '-'})`, value: d.id })) },
    chartCards () {
      return CATEGORY_DEFS.map(def => {
        const ranking = Array.isArray(this.categoryRankings[def.key]) ? this.categoryRankings[def.key] : []
        return { ...def, labels: ranking.map(r => this.toNameCase(r.sigla || '-')), series: ranking.map(r => Number(r[def.valueField] || 0)), colors: ranking.map((r, i) => r.color || FALLBACK_COLORS[i % FALLBACK_COLORS.length]), total: Number(this?.categorias?.[def.key]?.total || 0) }
      })
    },
    mapStateCounts () {
      return (this.mapData || []).reduce((acc, recinto) => {
        const state = this.markerState(recinto).estado
        acc[state] = Number(acc[state] || 0) + 1
        return acc
      }, { pendiente: 0, proceso: 0, ganado: 0, perdido: 0 })
    },
    activeCategoryDef () {
      return CATEGORY_DEFS.find(def => def.key === this.viewerCategory) || CATEGORY_DEFS[0]
    },
    activeCategoryField () {
      return this.activeCategoryDef?.valueField || 'votos_alcalde'
    },
    activeCategoryColumnLabel () {
      return this.activeCategoryDef?.label || 'Alcalde'
    },
    monitorCards () {
      return (this.mapData || [])
        .flatMap(recinto => (recinto.mesas || []).map(mesa => this.buildMonitorCard(recinto, mesa)))
        .filter(Boolean)
        .sort((a, b) => {
          if (a.tieneResultado !== b.tieneResultado) {
            return a.tieneResultado ? -1 : 1
          }
          return new Date(b.isoHora || 0) - new Date(a.isoHora || 0)
        })
    },
    filteredMonitorCards () {
      return this.monitorCards.filter(card => {
        if (this.monitorFilters.recinto_id && card.recintoId !== this.monitorFilters.recinto_id) return false
        if (this.monitorFilters.confirmador && card.confirmador !== this.monitorFilters.confirmador) return false
        if (this.monitorFilters.ganador && card.ganador !== this.monitorFilters.ganador) return false
        if (this.monitorFilters.categoria && card.ganadorCategoria !== this.monitorFilters.categoria) return false
        return true
      })
    },
    mesasHistory () {
      return this.monitorCards.filter(card => card.tieneResultado).slice(0, 18)
    },
    monitorRecintoOptions () {
      return this.monitorOptions.recintos
    },
    monitorConfirmadorOptions () {
      return this.monitorOptions.confirmadores
    },
    monitorWinnerOptions () {
      return this.monitorOptions.ganadores
    },
    monitorCategoryOptions () {
      return this.monitorOptions.categorias
    }
  },
  async mounted () {
    this.isAlive = true
    this.syncDashboardMode()
    await this.loadDashboard()
    this.connectSocket()
  },
  beforeUnmount () {
    this.isAlive = false
    const socketEvent = import.meta.env.VITE_SOCKET_EVENT || 'votacion'
    if (this.socketRefreshTimer) clearTimeout(this.socketRefreshTimer)
    if (this.audioContext?.state !== 'closed') {
      this.audioContext?.close?.()
    }
    if (this.socket) {
      this.socket.off(socketEvent)
      this.socket.disconnect()
    }
  },
  methods: {
    syncDashboardMode () {
      this.chartsViewMode = ['both', 'pie', 'bar'].includes(this.dashboardMode) ? this.dashboardMode : 'both'
      this.mapViewerOpen = false
      this.mesasViewerOpen = false
    },
    dashboardParams () { return { provincia_id: this.filters.provincia_id || undefined, municipio_id: this.filters.municipio_id || undefined, localidad_id: this.filters.localidad_id || undefined, delegado_id: this.filters.delegado_id || undefined } },
    onProvinciaChange () { this.filters.municipio_id = null; this.filters.localidad_id = null; this.loadDashboard() },
    onMunicipioChange () { this.filters.localidad_id = null; this.loadDashboard() },
    clearFilters () { this.filters = { provincia_id: 57, municipio_id: 191, localidad_id: 1988, delegado_id: null }; this.loadDashboard() },
    openMapViewer () { this.mapViewerOpen = true; if (!this.selectedMapRecinto && this.mapData.length) this.selectedMapRecinto = this.mapData[0] },
    openMesasViewer () { this.mesasViewerOpen = true },
    selectMapRecinto (recinto) { this.selectedMapRecinto = recinto },
    toNameCase (text) { const v = String(text || '').trim().toLowerCase(); return v ? v.charAt(0).toUpperCase() + v.slice(1) : '-' },
    assetUrl (path) {
      if (!path) return ''
      const value = String(path).trim()
      if (!value) return ''
      if (value.startsWith('http')) return value
      if (value.startsWith('/storage') || value.startsWith('/images') || value.startsWith('/')) {
        return `${this.$url}/..${value}`
      }
      return `${this.$url}/../images/partidos/${value}`
    },
    openPhoto (path) {
      const url = this.assetUrl(path)
      if (url) window.open(url, '_blank', 'noopener')
    },
    markerState (recinto) { return recinto?.winners?.[this.viewerCategory] || { estado: 'pendiente', color: '#9e9e9e', votos: 0 } },
    markerStateLabel (estado) {
      if (estado === 'ganado') return 'Jacha gana'
      if (estado === 'proceso') return 'Jacha va ganando'
      if (estado === 'perdido') return 'Realizado sin ganar'
      return 'Sin resultado'
    },
    formatDateTime (value) {
      if (!value) return 'Sin hora'
      const date = new Date(value)
      if (Number.isNaN(date.getTime())) return 'Sin hora'
      return date.toLocaleString('es-BO', {
        day: '2-digit',
        month: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
      })
    },
    buildMonitorCard (recinto, mesa) {
      const resultado = mesa?.resultado || {}
      const ganador = Array.isArray(resultado.ganadores_resumen) && resultado.ganadores_resumen.length
        ? resultado.ganadores_resumen[0]
        : null

      return {
        id: mesa.id,
        recintoId: recinto.id,
        recinto: recinto.nombre,
        numero: mesa.numero_mesa,
        tieneResultado: !!mesa.tiene_resultado,
        totalVotos: Number(resultado.total_votos || 0),
        confirmador: resultado.registrado_por_nombre || 'Sin confirmador',
        hora: resultado.updated_at ? this.formatDateTime(resultado.updated_at) : 'Sin registro',
        isoHora: resultado.updated_at || null,
        ganador: ganador?.sigla || ganador?.nombre || (mesa.tiene_resultado ? 'Sin ganador' : 'Pendiente'),
        ganadorCategoria: ganador?.label || null,
        ganadorColor: ganador?.color || (mesa.tiene_resultado ? '#2563eb' : '#dc2626'),
        ganadorIcono: ganador?.icono || null,
        ganadorVotos: Number(ganador?.votos || 0)
      }
    },
    resetMonitorOptionFilters () {
      const recintos = this.monitorCards.map(card => ({ label: card.recinto, value: card.recintoId }))
      const confirmadores = this.monitorCards.map(card => ({ label: card.confirmador, value: card.confirmador }))
      const ganadores = this.monitorCards.map(card => ({ label: card.ganador, value: card.ganador }))
      const categorias = this.monitorCards
        .filter(card => card.ganadorCategoria)
        .map(card => ({ label: card.ganadorCategoria, value: card.ganadorCategoria }))

      const uniq = (rows) => rows.filter((row, index, arr) => arr.findIndex(item => item.value === row.value) === index)

      this.monitorOptionPool = {
        recintos: uniq(recintos).sort((a, b) => a.label.localeCompare(b.label)),
        confirmadores: uniq(confirmadores).sort((a, b) => a.label.localeCompare(b.label)),
        ganadores: uniq(ganadores).sort((a, b) => a.label.localeCompare(b.label)),
        categorias: uniq(categorias).sort((a, b) => a.label.localeCompare(b.label))
      }
      this.monitorOptions = {
        recintos: [...this.monitorOptionPool.recintos],
        confirmadores: [...this.monitorOptionPool.confirmadores],
        ganadores: [...this.monitorOptionPool.ganadores],
        categorias: [...this.monitorOptionPool.categorias]
      }
    },
    filterMonitorRecintos (val, update) {
      update(() => {
        const needle = String(val || '').toLowerCase().trim()
        this.monitorOptions.recintos = !needle
          ? [...this.monitorOptionPool.recintos]
          : this.monitorOptionPool.recintos.filter(option => String(option.label || '').toLowerCase().includes(needle))
      })
    },
    filterMonitorConfirmadores (val, update) {
      update(() => {
        const needle = String(val || '').toLowerCase().trim()
        this.monitorOptions.confirmadores = !needle
          ? [...this.monitorOptionPool.confirmadores]
          : this.monitorOptionPool.confirmadores.filter(option => String(option.label || '').toLowerCase().includes(needle))
      })
    },
    filterMonitorWinners (val, update) {
      update(() => {
        const needle = String(val || '').toLowerCase().trim()
        this.monitorOptions.ganadores = !needle
          ? [...this.monitorOptionPool.ganadores]
          : this.monitorOptionPool.ganadores.filter(option => String(option.label || '').toLowerCase().includes(needle))
      })
    },
    filterMonitorCategories (val, update) {
      update(() => {
        const needle = String(val || '').toLowerCase().trim()
        this.monitorOptions.categorias = !needle
          ? [...this.monitorOptionPool.categorias]
          : this.monitorOptionPool.categorias.filter(option => String(option.label || '').toLowerCase().includes(needle))
      })
    },
    clearMesasViewerFilters () {
      this.monitorFilters = { recinto_id: null, confirmador: null, ganador: null, categoria: null }
      this.monitorOptions = {
        recintos: [...this.monitorOptionPool.recintos],
        confirmadores: [...this.monitorOptionPool.confirmadores],
        ganadores: [...this.monitorOptionPool.ganadores],
        categorias: [...this.monitorOptionPool.categorias]
      }
    },
    pieOptions (card) { return { chart: { toolbar: { show: true } }, labels: card.labels, colors: card.colors, legend: { position: 'right', fontSize: '11px', width: 130 }, dataLabels: { enabled: true, formatter: (val, opts) => `${opts?.w?.globals?.labels?.[opts.seriesIndex] || ''} ${Number(val || 0).toFixed(1)}%`, style: { fontSize: '10px', fontWeight: 700 } } } },
    barOptions (card) {
      return {
        chart: { toolbar: { show: true } },
        colors: card.colors,
        plotOptions: {
          bar: {
            horizontal: true,
            borderRadius: 6,
            barHeight: '72%',
            distributed: true
          }
        },
        xaxis: {
          categories: card.labels,
          labels: {
            style: { fontSize: '11px', fontWeight: 700 }
          }
        },
        grid: {
          borderColor: '#e5e7eb',
          strokeDashArray: 3
        },
        dataLabels: {
          enabled: true,
          offsetX: 6,
          style: {
            fontSize: '13px',
            fontWeight: 800,
            colors: ['#ffffff']
          },
          dropShadow: {
            enabled: true,
            top: 1,
            left: 1,
            blur: 2,
            color: '#000000',
            opacity: 0.95
          }
        },
        legend: { show: false }
      }
    },
    playVoteSound () {
      if (typeof window === 'undefined') return
      const AudioCtx = window.AudioContext || window.webkitAudioContext
      if (!AudioCtx) return

      if (!this.audioContext) {
        this.audioContext = new AudioCtx()
      }

      if (this.audioContext.state === 'suspended') {
        this.audioContext.resume().catch(() => {})
      }

      const now = this.audioContext.currentTime
      const playBeep = (startAt, fromFreq, toFreq, peakGain, duration, type = 'square') => {
        const oscillator = this.audioContext.createOscillator()
        const gainNode = this.audioContext.createGain()

        oscillator.type = type
        oscillator.frequency.setValueAtTime(fromFreq, startAt)
        oscillator.frequency.exponentialRampToValueAtTime(toFreq, startAt + duration * 0.7)

        gainNode.gain.setValueAtTime(0.0001, startAt)
        gainNode.gain.exponentialRampToValueAtTime(peakGain, startAt + 0.02)
        gainNode.gain.exponentialRampToValueAtTime(0.0001, startAt + duration)

        oscillator.connect(gainNode)
        gainNode.connect(this.audioContext.destination)
        oscillator.start(startAt)
        oscillator.stop(startAt + duration)
      }

      playBeep(now, 740, 1180, 0.22, 0.28, 'square')
      playBeep(now + 0.18, 740, 1180, 0.22, 0.28, 'square')
      playBeep(now + 0.42, 820, 1320, 0.18, 0.22, 'triangle')
    },
    connectSocket () {
      const socketUrl = import.meta.env.VITE_API_SOCKET
      const socketEvent = import.meta.env.VITE_SOCKET_EVENT || 'votacion'
      if (!socketUrl) return
      this.socket = io(socketUrl, { transports: ['websocket', 'polling'], reconnection: true })
      this.socket.on(socketEvent, () => {
        if (!this.isAlive) return
        this.playVoteSound()
        if (this.socketRefreshTimer) clearTimeout(this.socketRefreshTimer)
        this.socketRefreshTimer = setTimeout(() => this.loadDashboard(), 400)
      })
    },
    async loadDashboard () {
      const requestId = ++this.dashboardRequestId
      this.loading = true
      this.loadingMap = true
      try {
        const { data } = await this.$axios.get('dashboard/bootstrap', { params: this.dashboardParams() })
        if (!this.isAlive || requestId !== this.dashboardRequestId) return
        this.votosValidosTotal = Number(data?.votos_validos_total || 0)
        this.categorias = data?.categorias || {}
        this.categoryRankings = {
          alcalde: Array.isArray(data?.categorias?.alcalde?.ranking) ? data.categorias.alcalde.ranking : [],
          concejal: Array.isArray(data?.categorias?.concejal?.ranking) ? data.categorias.concejal.ranking : [],
          gobernador: Array.isArray(data?.categorias?.gobernador?.ranking) ? data.categorias.gobernador.ranking : [],
          asambleista_distrito: Array.isArray(data?.categorias?.asambleista_distrito?.ranking) ? data.categorias.asambleista_distrito.ranking : [],
          asambleista_poblacion: Array.isArray(data?.categorias?.asambleista_poblacion?.ranking) ? data.categorias.asambleista_poblacion.ranking : []
        }
        this.mesas = { total: Number(data?.mesas?.total || 0), con_resultado: Number(data?.mesas?.con_resultado || 0), faltantes: Number(data?.mesas?.faltantes || 0) }
        this.geoOptions = {
          provincias: Array.isArray(data?.options?.provincias) ? data.options.provincias : [],
          municipios: Array.isArray(data?.options?.municipios) ? data.options.municipios : [],
          localidades: Array.isArray(data?.options?.localidades) ? data.options.localidades : [],
          delegados: Array.isArray(data?.options?.delegados) ? data.options.delegados : []
        }
        this.mapData = Array.isArray(data?.mapa) ? data.mapa : []
        this.selectedMapRecinto = this.selectedMapRecinto?.id ? (this.mapData.find(x => x.id === this.selectedMapRecinto.id) || this.mapData[0] || null) : (this.mapData[0] || null)
        this.resetMonitorOptionFilters()
      } catch (e) {
        if (!this.isAlive || requestId !== this.dashboardRequestId) return
        this.$q.notify({ type: 'negative', message: e?.response?.data?.message || 'No se pudo cargar dashboard' })
      } finally {
        if (!this.isAlive || requestId !== this.dashboardRequestId) return
        this.loading = false
        this.loadingMap = false
      }
    }
  }
  ,
  watch: {
    '$route.fullPath' () {
      this.syncDashboardMode()
    }
  }
}
</script>

<style scoped>
.category-block { background: #f8fafc; border: 1px solid #e6ebf2; border-radius: 12px; padding: 12px; }
.chart-modern { border-radius: 10px; background: #fff; }
.chart-grid-card { height: 100%; }
.map-panel { height: calc(100vh - 180px); }
.map-panel-scroll { height: calc(100vh - 280px); }
.modern-marker-container { background: transparent !important; border: none !important; }
.modern-marker { width: 20px; height: 20px; border-radius: 50%; border: 2px solid white; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease; animation: pulse 2s infinite ease-in-out; cursor: pointer; }
.modern-marker--active { transform: scale(1.35); border-color: #111827; }
.inner-dot { width: 6px; height: 6px; background-color: white; border-radius: 50%; opacity: 0.8; }
.monitor-shell { align-items: stretch; }
.monitor-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 12px; }
.monitor-card { border-radius: 18px; background: linear-gradient(180deg, #fff6f6 0%, #fffdfd 100%); border-color: #f2c3c3; }
.winner-box { border: 1px solid #d1d5db; border-radius: 14px; background: #fff; padding: 10px; }
.history-card { height: 100%; min-height: 420px; }
.history-list { max-height: calc(100vh - 260px); overflow: auto; }
.live-dot { width: 10px; height: 10px; border-radius: 999px; background: #ef4444; display: inline-block; box-shadow: 0 0 0 rgba(239, 68, 68, 0.55); animation: livePulse 1.4s infinite; }
@keyframes pulse { 0% { transform: scale(0.95); opacity: 0.9; } 70% { transform: scale(1.1); opacity: 1; } 100% { transform: scale(0.95); opacity: 0.9; } }
@keyframes livePulse { 0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.55); } 70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); } 100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); } }
@media (max-width: 768px) { .map-panel, .map-panel-scroll { height: auto; } }
</style>
