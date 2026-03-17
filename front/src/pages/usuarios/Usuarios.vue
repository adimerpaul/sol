<template>
  <q-page class="q-pa-md">
    <q-table
      :rows="users"
      :columns="visibleColumns"
      row-key="id"
      dense
      wrap-cells
      flat
      bordered
      class="users-table"
      v-model:pagination="pagination"
      :rows-per-page-options="[15, 30, 50, 100]"
      title="Usuarios"
      :filter="filter"
      :filter-method="filterUsers"
    >
      <template v-slot:top-right>
        <q-btn-dropdown
          color="indigo"
          label="Imprimir"
          no-caps
          icon="print"
          class="q-mr-sm"
          :disable="loading"
        >
          <q-list dense>
            <q-item clickable v-close-popup @click="printUsers('todos')">
              <q-item-section avatar><q-icon name="groups" /></q-item-section>
              <q-item-section><q-item-label>Todos</q-item-label></q-item-section>
            </q-item>
            <q-item clickable v-close-popup @click="printUsers('jefes')">
              <q-item-section avatar><q-icon name="badge" /></q-item-section>
              <q-item-section><q-item-label>Jefes de Recinto</q-item-label></q-item-section>
            </q-item>
            <q-item clickable v-close-popup @click="printUsers('supervisores')">
              <q-item-section avatar><q-icon name="supervisor_account" /></q-item-section>
              <q-item-section><q-item-label>Supervisores</q-item-label></q-item-section>
            </q-item>
            <q-item clickable v-close-popup @click="printUsers('administradores')">
              <q-item-section avatar><q-icon name="admin_panel_settings" /></q-item-section>
              <q-item-section><q-item-label>Administradores</q-item-label></q-item-section>
            </q-item>
            <q-item clickable v-close-popup @click="printUsers('delegados')">
              <q-item-section avatar><q-icon name="how_to_reg" /></q-item-section>
              <q-item-section><q-item-label>Delegados de Mesa</q-item-label></q-item-section>
            </q-item>
          </q-list>
        </q-btn-dropdown>

        <q-btn-dropdown
          color="teal"
          label="Excel"
          no-caps
          icon="table_view"
          class="q-mr-sm"
          :disable="loading"
        >
          <q-list dense>
            <q-item clickable v-close-popup @click="exportUsersExcel('todos')">
              <q-item-section avatar><q-icon name="groups" /></q-item-section>
              <q-item-section><q-item-label>Excel Todos</q-item-label></q-item-section>
            </q-item>
            <q-item clickable v-close-popup @click="exportUsersExcel('jefes')">
              <q-item-section avatar><q-icon name="badge" /></q-item-section>
              <q-item-section><q-item-label>Excel Jefes de Recinto</q-item-label></q-item-section>
            </q-item>
            <q-item clickable v-close-popup @click="exportUsersExcel('supervisores')">
              <q-item-section avatar><q-icon name="supervisor_account" /></q-item-section>
              <q-item-section><q-item-label>Excel Supervisores</q-item-label></q-item-section>
            </q-item>
            <q-item clickable v-close-popup @click="exportUsersExcel('administradores')">
              <q-item-section avatar><q-icon name="admin_panel_settings" /></q-item-section>
              <q-item-section><q-item-label>Excel Administradores</q-item-label></q-item-section>
            </q-item>
            <q-item clickable v-close-popup @click="exportUsersExcel('delegados')">
              <q-item-section avatar><q-icon name="how_to_reg" /></q-item-section>
              <q-item-section><q-item-label>Excel Delegados de Mesa</q-item-label></q-item-section>
            </q-item>
          </q-list>
        </q-btn-dropdown>

        <q-btn
          color="positive"
          label="Nuevo"
          @click="userNew"
          no-caps
          icon="add_circle_outline"
          :loading="loading"
          class="q-mr-sm"
        />

        <q-input v-model="filter" label="Buscar" dense outlined style="width: 260px">
          <template v-slot:append><q-icon name="search"/></template>
        </q-input>
        <q-btn
          color="primary"
          label="Actualizar"
          @click="usersGet"
          no-caps
          icon="refresh"
          :loading="loading"
          class="q-mr-sm"
        />
      </template>

      <template v-slot:body-cell-actions="props">
        <q-td :props="props">
          <q-btn-dropdown label="Opciones" no-caps size="10px" dense color="primary">
            <q-list>
              <q-item clickable @click="openUsernameDialog(props.row)" v-close-popup v-if="isAdminOrSupervisor">
                <q-item-section avatar><q-icon name="person"/></q-item-section>
                <q-item-section><q-item-label>Cambiar Codigo Ingreso</q-item-label></q-item-section>
              </q-item>

              <q-item clickable @click="userEdit(props.row)" v-close-popup>
                <q-item-section avatar><q-icon name="edit"/></q-item-section>
                <q-item-section><q-item-label>Editar</q-item-label></q-item-section>
              </q-item>

              <q-item clickable @click="userDelete(props.row.id)" v-close-popup>
                <q-item-section avatar><q-icon name="delete"/></q-item-section>
                <q-item-section><q-item-label>Eliminar</q-item-label></q-item-section>
              </q-item>
              <q-item clickable @click="cambiarAvatar(props.row)" v-close-popup>
                <q-item-section avatar><q-icon name="image"/></q-item-section>
                <q-item-section><q-item-label>Cambiar avatar</q-item-label></q-item-section>
              </q-item>

              <q-item clickable @click="permisosShow(props.row)" v-close-popup v-if="$store?.user?.role === 'Administrador'">
                <q-item-section avatar><q-icon name="lock"/></q-item-section>
                <q-item-section><q-item-label>Permisos</q-item-label></q-item-section>
              </q-item>
            </q-list>
          </q-btn-dropdown>
        </q-td>
      </template>

      <template v-slot:body-cell-avatar="props">
        <q-td :props="props">
          <q-avatar rounded>
            <q-img :src="`${$url}/../images/${props.row.avatar}`" width="40px" height="40px" v-if="props.row.avatar"/>
            <q-icon name="person" size="40px" v-else/>
          </q-avatar>
        </q-td>
      </template>

      <template v-slot:body-cell-role="props">
        <q-td :props="props">
          <q-chip :label="props.row.role" :color="$filters.color(props.row.role)" text-color="white" dense size="14px"/>
        </q-td>
      </template>

      <template v-slot:body-cell-created_by="props">
        <q-td :props="props">
          {{ [props.row.nombres, props.row.apellido_paterno, props.row.apellido_materno].filter(Boolean).join(' ') || '-' }}
          <q-chip
            v-if="props.row.creator_name"
            dense
            outline
            color="primary"
            size="12px"
            class="creator-chip"
          >
            {{ props.row.creator_name }}
          </q-chip>
          <q-badge v-else outline color="grey-6">Sin registro</q-badge>
        </q-td>
      </template>

<!--      <template v-slot:body-cell-full_name="props">-->
<!--        <q-td :props="props" class="full-name-cell">-->
<!--          <div class="text-weight-medium">-->
<!--            {{ [props.row.nombres, props.row.apellido_paterno, props.row.apellido_materno].filter(Boolean).join(' ') || '-' }}-->
<!--          </div>-->
<!--        </q-td>-->
<!--      </template>-->

      <template v-slot:body-cell-permissions="props">
        <q-td :props="props">
          <div class="row items-center q-col-gutter-xs">
            <q-chip
              v-for="(perm, idx) in (props.row.permissions || []).slice(0, 3)"
              :key="perm.id"
              dense
              color="grey-3"
              text-color="black"
              size="12px"
              class="q-mr-xs q-mb-xs"
            >
              {{ perm.name }}
            </q-chip>

            <template v-if="(props.row.permissions || []).length > 3">
              <q-badge outline color="primary" class="q-ml-xs">
                +{{ (props.row.permissions || []).length - 3 }}
                <q-tooltip anchor="top middle" self="bottom middle" :offset="[0,8]">
                  <div class="text-left">
                    <div v-for="perm in props.row.permissions" :key="perm.id">• {{ perm.name }}</div>
                  </div>
                </q-tooltip>
              </q-badge>
            </template>

            <q-badge v-if="!(props.row.permissions || []).length" color="grey-5" text-color="white" outline>
              Sin permisos
            </q-badge>
          </div>
        </q-td>
      </template>

      <template v-slot:body-cell-ci_files="props">
        <q-td :props="props">
          <div class="row items-center q-gutter-xs">
            <q-badge outline :color="props.row.ci_anverso_url ? 'positive' : 'grey-6'">CI A</q-badge>
            <q-badge outline :color="props.row.ci_reverso_url ? 'positive' : 'grey-6'">CI R</q-badge>
            <q-badge outline :color="props.row.foto_personal_url ? 'positive' : 'grey-6'">FOTO</q-badge>
          </div>
        </q-td>
      </template>
    </q-table>

    <!-- DIALOG CREAR/EDITAR -->
    <q-dialog v-model="userDialog" persistent>
      <q-card style="width: 860px; max-width: 98vw">
        <q-card-section class="q-pb-none row items-center">
          <div class="text-weight-bold">{{ actionUser }} usuario</div>
          <q-space/>
          <q-btn icon="close" flat round dense @click="closeUserDialog"/>
        </q-card-section>

        <q-separator />

        <q-card-section>
          <q-form @submit="user.id ? userPut() : userPost()">
            <div class="row q-col-gutter-sm">
              <div class="col-12 col-md-4">
                <q-input
                  v-model="user.nombres"
                  label="Nombre(s) *"
                  dense outlined
                  :rules="[v => !!v || 'Campo requerido']"
                />
              </div>
              <div class="col-12 col-md-4">
                <q-input
                  v-model="user.apellido_paterno"
                  label="Apellido paterno (opcional)"
                  dense outlined
                />
              </div>
              <div class="col-12 col-md-4">
                <q-input
                  v-model="user.apellido_materno"
                  label="Apellido materno *"
                  dense outlined
                  :rules="[v => !!v || 'Campo requerido']"
                />
              </div>

              <div class="col-12 col-md-4">
                <q-input
                  v-model="user.ci"
                  label="Carnet de identidad *"
                  dense outlined
                  :rules="[v => !!v || 'Campo requerido']"
                />
              </div>

              <div class="col-12 col-md-4">
                <q-input
                  v-model="user.fecha_nacimiento"
                  type="date"
                  label="Fecha de nacimiento *"
                  dense outlined
                  :rules="[v => !!v || 'Campo requerido']"
                />
              </div>

              <div class="col-12 col-md-4">
                <q-input
                  v-model="user.bloque"
                  label="Bloque / agrupación / organización *"
                  dense outlined
                  :rules="[v => !!v || 'Campo requerido']"
                />
              </div>
              <div class="col-12 col-md-4">
                <q-input v-model="user.numero_mesa" label="Numero de mesa" dense outlined />
              </div>
              <div class="col-12 col-md-4">
                <q-input v-model="user.celular" label="Celular" dense outlined />
              </div>

              <div class="col-12 col-md-4">
                <q-select
                  v-model="user.role"
                  label="Rol *"
                  dense outlined
                  :options="availableRoles"
                  :rules="[v => !!v || 'Campo requerido']"
                />
              </div>

              <div class="col-12 col-md-4">
                <q-select
                  v-model="user.recinto_id"
                  label="Recinto (ID)"
                  dense
                  outlined
                  use-input
                  clearable
                  map-options
                  emit-value
                  option-label="label"
                  option-value="value"
                  :options="recintoOptions"
                  :loading="loadingRecintos"
                  input-debounce="300"
                  @filter="filterRecintos"
                />
              </div>
            </div>

            <q-separator class="q-my-md" />

            <!-- ARCHIVOS -->
<!--            <div class="row q-col-gutter-sm">-->
<!--              <div class="col-12 col-md-4">-->
<!--                <div class="text-caption text-grey-7 q-mb-xs">Foto CI (anverso) </div>-->
<!--                <q-file-->
<!--                  v-model="files.ci_anverso"-->
<!--                  dense outlined-->
<!--                  accept="image/*,application/pdf"-->
<!--                  label="Subir archivo (máx 10MB)"-->
<!--                  clearable-->
<!--                />-->
<!--                <div v-if="user.ci_anverso_url" class="text-caption text-primary q-mt-xs">-->
<!--                  Ya cargado:-->
<!--&lt;!&ndash;                  {{ user.ci_anverso_url }}&ndash;&gt;-->
<!--                  <q-btn size="12px" :href="$url + '/..' + user.ci_anverso_url" target="_blank" no-caps>-->
<!--                    Ver archivo-->
<!--                  </q-btn>-->
<!--                </div>-->
<!--              </div>-->

<!--              <div class="col-12 col-md-4">-->
<!--                <div class="text-caption text-grey-7 q-mb-xs">Foto CI (reverso) </div>-->
<!--                <q-file-->
<!--                  v-model="files.ci_reverso"-->
<!--                  dense outlined-->
<!--                  accept="image/*,application/pdf"-->
<!--                  label="Subir archivo (máx 10MB)"-->
<!--                  clearable-->
<!--                />-->
<!--                <div v-if="user.ci_reverso_url" class="text-caption text-primary q-mt-xs">-->
<!--                  Ya cargado:-->
<!--&lt;!&ndash;                  {{ user.ci_reverso_url }}&ndash;&gt;-->
<!--                  <q-btn size="12px" :href="$url + '/..' + user.ci_reverso_url" target="_blank" no-caps>-->
<!--                    Ver archivo-->
<!--                  </q-btn>-->
<!--                </div>-->
<!--              </div>-->

<!--              <div class="col-12 col-md-4">-->
<!--                <div class="text-caption text-grey-7 q-mb-xs">Foto personal (selfie) </div>-->
<!--                <q-file-->
<!--                  v-model="files.foto_personal"-->
<!--                  dense outlined-->
<!--                  accept="image/*"-->
<!--                  label="Subir archivo (máx 10MB)"-->
<!--                  clearable-->
<!--                />-->
<!--                <div v-if="user.foto_personal_url" class="text-caption text-primary q-mt-xs">-->
<!--                  Ya cargado:-->
<!--&lt;!&ndash;                  {{ user.foto_personal_url }}&ndash;&gt;-->
<!--                </div>-->
<!--              </div>-->
<!--            </div>-->

            <div class="text-right q-mt-md">
              <q-btn color="negative" label="Cancelar" @click="closeUserDialog" no-caps :loading="loading"/>
              <q-btn color="primary" label="Guardar" type="submit" no-caps :loading="loading" class="q-ml-sm"/>
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- DIALOG AVATAR (tu mismo) -->
    <q-dialog v-model="cambioAvatarDialogo" persistent>
      <q-card>
        <q-card-section class="q-pb-none row items-center text-bold">
          Cambiar avatar
          <q-space/>
          <q-btn icon="close" flat round dense @click="cambioAvatarDialogo = false"/>
        </q-card-section>
        <q-card-section class="q-pt-none">
          <q-form @submit="userPut()">
            <div>
              <div style="position: relative;top: 0;left: 0;">
                <q-btn icon="edit" size="10px" class="absolute q-mt-sm q-ml-sm" @click="$refs.fileInput.click()" dense
                       outline label="Cambiar foto" no-caps/>
              </div>
              <img :src="`${$url}/../images/${user.avatar}`" width="300px" height="300px" v-if="user.avatar"/>
              <q-icon name="person" size="100px" v-else/>
              <input ref="fileInput" type="file" style="display: none;" @change="onFileChange" accept="image/*"/>
            </div>
            <div class="text-right">
              <q-btn color="negative" label="Cancelar" @click="cambioAvatarDialogo = false" no-caps :loading="loading"/>
              <q-btn color="primary" label="Guardar" type="submit" no-caps :loading="loading" class="q-ml-sm"/>
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- DIALOG PERMISOS (tu mismo) -->
    <q-dialog v-model="dialogPermisos" persistent>
      <q-card style="min-width: 420px">
        <q-card-section class="q-pb-none row items-center text-bold">
          Permisos de {{ user.nombres || user.name || user.ci }}
          <q-space />
          <q-btn icon="close" flat round dense @click="dialogPermisos = false" />
        </q-card-section>

        <q-card-section class="q-pt-none">
          <q-input v-model="permFilter" dense outlined placeholder="Filtrar permisos..." class="q-mb-sm">
            <template v-slot:append><q-icon name="search" /></template>
          </q-input>

          <q-list dense bordered>
            <q-item v-for="perm in filteredPermissions" :key="perm.id">
              <q-item-section>{{ perm.name }}</q-item-section>
              <q-item-section side>
                <q-toggle v-model="perm.checked" />
              </q-item-section>
            </q-item>
          </q-list>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn color="negative" label="Cancelar" @click="dialogPermisos = false" no-caps :loading="loading" />
          <q-btn color="primary" label="Guardar" @click="permisosPost" no-caps :loading="loading" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <q-dialog v-model="dialogUsername" persistent>
      <q-card style="min-width: 420px">
        <q-card-section class="q-pb-none row items-center text-bold">
          Cambiar Codigo Ingreso
          <q-space />
          <q-btn icon="close" flat round dense @click="dialogUsername = false" />
        </q-card-section>

        <q-card-section class="q-pt-none">
          <div class="text-caption text-grey-7 q-mb-sm">
            Usuario: {{ user.nombres || user.name || user.ci }}
          </div>
          <q-input
            v-model="usernameForm.username"
            dense outlined
            label="Codigo de ingreso"
            hint="Este username se usa para el login."
            persistent-hint
          />
        </q-card-section>

        <q-card-actions align="right">
          <q-btn color="negative" label="Cancelar" @click="dialogUsername = false" no-caps :loading="loading" />
          <q-btn color="primary" label="Guardar" @click="saveUsername" no-caps :loading="loading" />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script>
export default {
  name: 'UsuariosPage',
  data() {
    return {
      users: [],
      user: {},
      userDialog: false,
      loading: false,
      actionUser: '',
      filter: '',
      roles: ['Administrador', 'Supervisor', 'Jefe de Recinto', 'Delegado de Mesa'],
      recintoOptions: [],
      loadingRecintos: false,
      pagination: {
        sortBy: 'id',
        descending: true,
        page: 1,
        rowsPerPage: 15
      },

      // archivos nuevos (temporal)
      files: {
        ci_anverso: null,
        ci_reverso: null,
        foto_personal: null
      },

      columns: [
        { name: 'actions', label: 'Acciones', align: 'center' },
        { name: 'username', label: 'Codigo de ingresos', align: 'left', field: 'username' },
        {
          name: 'created_by',
          label: 'Nombre',
          align: 'left',
          field: row => [row.nombres, row.apellido_paterno, row.apellido_materno].filter(Boolean).join(' ') || row.name || '-',
          style: 'width: 180px; white-space: normal;'
        },
        // { name: 'full_name', label: 'Nombre completo', align: 'left', field: row => [row.nombres, row.apellido_paterno, row.apellido_materno].filter(Boolean).join(' '), style: 'width: 240px; white-space: normal;' },
        { name: 'numero_mesa', label: 'Numero mesa', align: 'left', field: 'numero_mesa' },
        { name: 'celular', label: 'Celular', align: 'left', field: 'celular' },
        { name: 'fecha_nacimiento', label: 'Nacimiento', align: 'left', field: 'fecha_nacimiento' },
        { name: 'bloque', label: 'Bloque', align: 'left', field: 'bloque' },
        { name: 'recinto_nombre', label: 'Recinto', align: 'left', field: row => row.recinto_nombre || row.recinto?.nombre || '-' },
        // { name: 'avatar', label: 'Avatar', align: 'left', field: row => row.avatar },
        { name: 'role', label: 'Rol', align: 'left', field: 'role' },
        // { name: 'ci_files', label: 'Docs', align: 'left', field: row => row.id },
        {
          name: 'permissions',
          label: 'Permisos',
          align: 'left',
          field: row => (row.permissions || []).map(p => p.name).join(', ')
        },
      ],

      // permisos
      permissions: [],
      dialogPermisos: false,
      permFilter: '',
      dialogUsername: false,
      usernameForm: {
        username: ''
      },

      // avatar dialog
      cambioAvatarDialogo: false
    }
  },

  async mounted() {
    this.usersGet()
  },

  computed: {
    isAdminOrSupervisor() {
      const role = this.$store?.user?.role
      return role === 'Administrador' || role === 'Supervisor'
    },
    visibleColumns() {
      if (this.isAdminOrSupervisor) return this.columns
      return this.columns.filter(c => c.name !== 'username')
    },
    availableRoles() {
      const role = this.$store?.user?.role
      if (role === 'Administrador') {
        return this.roles
      }
      return this.roles.filter(r => r !== 'Administrador')
    },
    filteredPermissions() {
      if (!this.permFilter) return this.permissions
      const t = this.permFilter.toLowerCase()
      return this.permissions.filter(p => (p.name || '').toLowerCase().includes(t))
    }
  },

  methods: {
    filterUsers (rows, terms) {
      const needle = String(terms || '').toLowerCase().trim()
      if (!needle) return rows

      return (rows || []).filter(row => {
        const fullName = [row.nombres, row.apellido_paterno, row.apellido_materno]
          .filter(Boolean)
          .join(' ')
        const values = [
          row.username,
          row.nombres,
          row.apellido_paterno,
          row.apellido_materno,
          fullName,
          row.name,
          row.ci,
          row.numero_mesa,
          row.celular,
          row.fecha_nacimiento,
          row.bloque,
          row.recinto_nombre,
          row.recinto?.nombre,
          row.role,
          row.creator_name
        ]

        return values.some(value => String(value || '').toLowerCase().includes(needle))
      })
    },

    closeUserDialog () {
      this.userDialog = false
      this.files = { ci_anverso: null, ci_reverso: null, foto_personal: null }
    },

    userNew() {
      this.user = {
        nombres: '',
        apellido_paterno: '',
        apellido_materno: '',
        ci: '',
        fecha_nacimiento: '',
        bloque: '',
        numero_mesa: '',
        celular: '',
        role: 'Supervisor',
        recinto_id: null,
      }
      this.actionUser = 'Nuevo'
      this.files = { ci_anverso: null, ci_reverso: null, foto_personal: null }
      this.recintosGet()
      this.userDialog = true
    },

    userEdit(row) {
      this.user = { ...row, recinto_id: row.recinto_id ?? null }
      this.actionUser = 'Editar'
      this.files = { ci_anverso: null, ci_reverso: null, foto_personal: null }
      this.ensureSelectedRecintoOption()
      this.recintosGet()
      this.userDialog = true
    },

    usersGet() {
      this.loading = true
      this.users = []
      this.$axios.get('users')
        .then(res => { this.users = res.data })
        .catch(err => { this.$alert.error(err.response?.data?.message || 'Error') })
        .finally(() => { this.loading = false })
    },

    ensureSelectedRecintoOption () {
      const recintoId = this.user?.recinto_id
      if (!recintoId) return

      const exists = this.recintoOptions.some(opt => opt.value === recintoId)
      if (exists) return

      const labelName = this.user?.recinto_nombre || this.user?.recinto?.nombre || `Recinto ${recintoId}`
      this.recintoOptions = [
        ...this.recintoOptions,
        { label: labelName, value: recintoId }
      ]
    },

    async recintosGet (search = '') {
      this.loadingRecintos = true
      try {
        const res = await this.$axios.get('admin/recintos-oruro-city', {
          params: {
            search: search || undefined
          }
        })

        const rows = Array.isArray(res?.data)
          ? res.data
          : (Array.isArray(res?.data?.data) ? res.data.data : [])
        this.recintoOptions = rows.map(r => ({
          label: r.nombre,
          value: r.id
        }))

        this.ensureSelectedRecintoOption()
      } catch (err) {
        this.$alert.error(err.response?.data?.message || 'Error cargando recintos')
      } finally {
        this.loadingRecintos = false
      }
    },

    filterRecintos (val, update) {
      update(async () => {
        await this.recintosGet(val)
      })
    },

    async uploadFilesIfNeeded (userId) {
      const hasAny = !!(this.files.ci_anverso || this.files.ci_reverso || this.files.foto_personal)
      if (!hasAny) return

      const fd = new FormData()
      if (this.files.ci_anverso) fd.append('ci_anverso', this.files.ci_anverso)
      if (this.files.ci_reverso) fd.append('ci_reverso', this.files.ci_reverso)
      if (this.files.foto_personal) fd.append('foto_personal', this.files.foto_personal)

      await this.$axios.post(`users/${userId}/files`, fd, {
        headers: { 'Content-Type': 'multipart/form-data' }
      })
    },

    async userPost() {
      this.loading = true
      try {
        const res = await this.$axios.post('users', this.user)
        const created = res.data

        // subir archivos obligatorios en create
        await this.uploadFilesIfNeeded(created.id)

        this.userDialog = false
        this.$alert.success('Usuario creado')
        this.usersGet()
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'No se pudo crear')
      } finally {
        this.loading = false
      }
    },

    async userPut() {
      this.loading = true
      try {
        await this.$axios.put('users/' + this.user.id, this.user)

        // si seleccionó archivos nuevos, subirlos
        await this.uploadFilesIfNeeded(this.user.id)

        this.userDialog = false
        this.$alert.success('Usuario actualizado')
        this.usersGet()
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'No se pudo actualizar')
      } finally {
        this.loading = false
      }
    },

    userDelete(id) {
      this.$alert.dialog('¿Desea eliminar el usuario?')
        .onOk(() => {
          this.loading = true
          this.$axios.delete('users/' + id)
            .then(() => {
              this.usersGet()
              this.$alert.success('Usuario eliminado')
            })
            .catch(err => this.$alert.error(err.response?.data?.message || 'Error'))
            .finally(() => { this.loading = false })
        })
    },

    // AVATAR (tu lógica)
    onFileChange(event) {
      const file = event.target.files[0]
      const formData = new FormData()
      formData.append('avatar', file)
      this.loading = true
      this.$axios.post(this.user.id + '/avatar', formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      }).then(() => {
        this.cambioAvatarDialogo = false
        this.$alert.success('Avatar actualizado')
        this.usersGet()
      }).catch(error => {
        this.$alert.error(error.response?.data?.message || 'Error')
      }).finally(() => {
        this.loading = false
      })
    },

    cambiarAvatar(user) {
      this.cambioAvatarDialogo = true
      this.user = { ...user }
    },

    // PERMISOS (tu lógica)
    async permisosShow(user) {
      this.user = { ...user }
      this.dialogPermisos = true
      this.loading = true
      try {
        const all = await this.$axios.get('permissions').then(r => r.data)
        const userPermIds = await this.$axios.get(`users/${user.id}/permissions`).then(r => r.data)
        this.permissions = all.map(p => ({ ...p, checked: (userPermIds || []).includes(p.id) }))
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'Error cargando permisos')
      } finally {
        this.loading = false
      }
    },

    async permisosPost() {
      this.loading = true
      try {
        const ids = this.permissions.filter(p => p.checked).map(p => p.id)
        await this.$axios.put(`users/${this.user.id}/permissions`, { permissions: ids })
        this.dialogPermisos = false
        this.$alert.success('Permisos actualizados')
        this.usersGet()
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'No se pudo guardar')
      } finally {
        this.loading = false
      }
    },

    openUsernameDialog(row) {
      if (!this.isAdminOrSupervisor) return
      this.user = { ...row }
      this.usernameForm = {
        username: row?.username || ''
      }
      this.dialogUsername = true
    },

    async saveUsername() {
      if (!this.usernameForm.username) {
        this.$alert.error('Ingrese username')
        return
      }

      this.loading = true
      try {
        await this.$axios.patch(`users/${this.user.id}/username`, {
          username: this.usernameForm.username
        })
        this.dialogUsername = false
        this.$alert.success('Username actualizado')
        this.usersGet()
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'No se pudo actualizar username')
      } finally {
        this.loading = false
      }
    },

    async printUsers(type) {
      this.loading = true
      try {
        const res = await this.$axios.get(`users/print/${type}`, {
          responseType: 'blob'
        })
        const blob = new Blob([res.data], { type: 'application/pdf' })
        const url = URL.createObjectURL(blob)
        window.open(url, '_blank')
        setTimeout(() => URL.revokeObjectURL(url), 60000)
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'No se pudo generar el PDF')
      } finally {
        this.loading = false
      }
    },

    getUsersByType (type) {
      const roleMap = {
        administradores: 'Administrador',
        supervisores: 'Supervisor',
        jefes: 'Jefe de Recinto',
        delegados: 'Delegado de Mesa'
      }

      if (!type || type === 'todos') return this.users || []

      const role = roleMap[String(type).toLowerCase()]
      if (!role) return this.users || []

      return (this.users || []).filter(u => String(u.role || '').trim() === role)
    },

    excelTitleByType (type) {
      const labels = {
        todos: 'Todos',
        jefes: 'Jefes de Recinto',
        supervisores: 'Supervisores',
        administradores: 'Administradores',
        delegados: 'Delegados de Mesa'
      }
      return labels[String(type || '').toLowerCase()] || 'Todos'
    },

    async exportUsersExcel (type) {
      const rows = this.getUsersByType(type)
      if (!rows.length) {
        this.$alert.error('No hay usuarios para exportar')
        return
      }

      this.loading = true
      try {
        const { Excel } = await import('src/addons/Excel')
        const title = this.excelTitleByType(type)
        const content = rows.map(u => ({
          ID: u.id ?? '',
          Username: u.username ?? '',
          Nombres: u.nombres ?? '',
          'Apellido paterno': u.apellido_paterno ?? '',
          'Apellido materno': u.apellido_materno ?? '',
          CI: u.ci ?? '',
          'Fecha nacimiento': u.fecha_nacimiento ?? '',
          'Numero mesa': u.numero_mesa ?? '',
          Celular: u.celular ?? '',
          Bloque: u.bloque ?? '',
          Rol: u.role ?? '',
          'Registrado por': u.creator_name || u.creator_username || '',
          Recinto: u.recinto_nombre || u.recinto?.nombre || ''
        }))

        const data = [{
          sheet: 'Usuarios',
          columns: [
            { label: 'ID', value: 'ID' },
            { label: 'Username', value: 'Username' },
            { label: 'Nombres', value: 'Nombres' },
            { label: 'Apellido paterno', value: 'Apellido paterno' },
            { label: 'Apellido materno', value: 'Apellido materno' },
            { label: 'CI', value: 'CI' },
            { label: 'Fecha nacimiento', value: 'Fecha nacimiento' },
            { label: 'Numero mesa', value: 'Numero mesa' },
            { label: 'Celular', value: 'Celular' },
            { label: 'Bloque', value: 'Bloque' },
            { label: 'Rol', value: 'Rol' },
            { label: 'Registrado por', value: 'Registrado por' },
            { label: 'Recinto', value: 'Recinto' }
          ],
          content
        }]

        Excel.export(data, `usuarios_${String(type || 'todos').toLowerCase()}_${new Date().toISOString().slice(0, 10)}`)
        this.$alert.success(`Excel generado: ${title}`)
      } catch (e) {
        this.$alert.error('No se pudo generar el Excel')
      } finally {
        this.loading = false
      }
    },
  }
}
</script>

<style scoped>
.users-table :deep(.q-table thead th),
.users-table :deep(.q-table tbody td) {
  padding: 6px 8px;
}

.full-name-cell {
  min-width: 220px;
  white-space: normal;
  line-height: 1.2;
}

.creator-chip {
  max-width: 170px;
  white-space: normal;
  line-height: 1.15;
}
</style>
