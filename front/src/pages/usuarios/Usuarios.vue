<template>
  <q-page class="q-pa-md">
    <q-table
      :rows="users"
      :columns="columns"
      row-key="id"
      dense
      wrap-cells
      flat
      bordered
      :rows-per-page-options="[0]"
      title="Usuarios"
      :filter="filter"
    >
      <template v-slot:top-right>
        <q-btn
          color="positive"
          label="Nuevo"
          @click="userNew"
          no-caps
          icon="add_circle_outline"
          :loading="loading"
          class="q-mr-sm"
        />
        <q-btn
          color="primary"
          label="Actualizar"
          @click="usersGet"
          no-caps
          icon="refresh"
          :loading="loading"
          class="q-mr-sm"
        />
        <q-input v-model="filter" label="Buscar" dense outlined style="width: 260px">
          <template v-slot:append><q-icon name="search"/></template>
        </q-input>
      </template>

      <template v-slot:body-cell-actions="props">
        <q-td :props="props">
          <q-btn-dropdown label="Opciones" no-caps size="10px" dense color="primary">
            <q-list>
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
            </div>

            <q-separator class="q-my-md" />

            <!-- ARCHIVOS -->
            <div class="row q-col-gutter-sm">
              <div class="col-12 col-md-4">
                <div class="text-caption text-grey-7 q-mb-xs">Foto CI (anverso) </div>
                <q-file
                  v-model="files.ci_anverso"
                  dense outlined
                  accept="image/*,application/pdf"
                  label="Subir archivo (máx 10MB)"
                  clearable
                />
                <div v-if="user.ci_anverso_url" class="text-caption text-primary q-mt-xs">
                  Ya cargado:
<!--                  {{ user.ci_anverso_url }}-->
                  <q-btn size="12px" :href="$url + '/..' + user.ci_anverso_url" target="_blank" no-caps>
                    Ver archivo
                  </q-btn>
                </div>
              </div>

              <div class="col-12 col-md-4">
                <div class="text-caption text-grey-7 q-mb-xs">Foto CI (reverso) </div>
                <q-file
                  v-model="files.ci_reverso"
                  dense outlined
                  accept="image/*,application/pdf"
                  label="Subir archivo (máx 10MB)"
                  clearable
                />
                <div v-if="user.ci_reverso_url" class="text-caption text-primary q-mt-xs">
                  Ya cargado:
<!--                  {{ user.ci_reverso_url }}-->
                  <q-btn size="12px" :href="$url + '/..' + user.ci_reverso_url" target="_blank" no-caps>
                    Ver archivo
                  </q-btn>
                </div>
              </div>

              <div class="col-12 col-md-4">
                <div class="text-caption text-grey-7 q-mb-xs">Foto personal (selfie) </div>
                <q-file
                  v-model="files.foto_personal"
                  dense outlined
                  accept="image/*"
                  label="Subir archivo (máx 10MB)"
                  clearable
                />
                <div v-if="user.foto_personal_url" class="text-caption text-primary q-mt-xs">
                  Ya cargado:
<!--                  {{ user.foto_personal_url }}-->
                </div>
              </div>
            </div>

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

      // archivos nuevos (temporal)
      files: {
        ci_anverso: null,
        ci_reverso: null,
        foto_personal: null
      },

      columns: [
        { name: 'actions', label: 'Acciones', align: 'center' },
        { name: 'celular', label: 'Celular', align: 'left', field: 'celular' },
        { name: 'nombres', label: 'Nombre(s)', align: 'left', field: 'nombres' },
        { name: 'apellido_paterno', label: 'Ap. paterno', align: 'left', field: 'apellido_paterno' },
        { name: 'apellido_materno', label: 'Ap. materno', align: 'left', field: 'apellido_materno' },
        { name: 'ci', label: 'CI', align: 'left', field: 'ci' },
        { name: 'fecha_nacimiento', label: 'Nacimiento', align: 'left', field: 'fecha_nacimiento' },
        { name: 'bloque', label: 'Bloque', align: 'left', field: 'bloque' },
        { name: 'avatar', label: 'Avatar', align: 'left', field: row => row.avatar },
        { name: 'role', label: 'Rol', align: 'left', field: 'role' },
        { name: 'ci_files', label: 'Docs', align: 'left', field: row => row.id },
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

      // avatar dialog
      cambioAvatarDialogo: false
    }
  },

  async mounted() {
    this.usersGet()
  },

  computed: {
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
        celular: '',
        role: 'Supervisor',
      }
      this.actionUser = 'Nuevo'
      this.files = { ci_anverso: null, ci_reverso: null, foto_personal: null }
      this.userDialog = true
    },

    userEdit(row) {
      this.user = { ...row }
      this.actionUser = 'Editar'
      this.files = { ci_anverso: null, ci_reverso: null, foto_personal: null }
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
  }
}
</script>
