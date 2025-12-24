<template>
  <q-page class="q-pa-md">
    <q-table :rows="users" :columns="columns" dense wrap-cells flat bordered :rows-per-page-options="[0]"
             title="Usuarios" :filter="filter">
      <template v-slot:top-right>
        <q-btn color="positive" label="Nuevo" @click="userNew" no-caps icon="add_circle_outline" :loading="loading"
               class="q-mr-sm"/>
        <q-btn color="primary" label="Actualizar" @click="usersGet" no-caps icon="refresh" :loading="loading"/>
        <q-input v-model="filter" label="Buscar" dense outlined>
          <template v-slot:append>
            <q-icon name="search"/>
          </template>
        </q-input>
      </template>
      <template v-slot:body-cell-actions="props">
        <q-td :props="props">
          <q-btn-dropdown label="Opciones" no-caps size="10px" dense color="primary">
            <q-list>
              <q-item clickable @click="userEdit(props.row)" v-close-popup>
                <q-item-section avatar>
                  <q-icon name="edit"/>
                </q-item-section>
                <q-item-section>
                  <q-item-label>Editar</q-item-label>
                </q-item-section>
              </q-item>
              <q-item clickable @click="userDelete(props.row.id)" v-close-popup>
                <q-item-section avatar>
                  <q-icon name="delete"/>
                </q-item-section>
                <q-item-section>
                  <q-item-label>Eliminar</q-item-label>
                </q-item-section>
              </q-item>
              <q-item clickable @click="userEditPassword(props.row)" v-close-popup>
                <q-item-section avatar>
                  <q-icon name="lock_reset"/>
                </q-item-section>
                <q-item-section>
                  <q-item-label>Cambiar contraseña</q-item-label>
                </q-item-section>
              </q-item>
              <q-item clickable @click="cambiarAvatar(props.row)" v-close-popup>
                <q-item-section avatar>
                  <q-icon name="image"/>
                </q-item-section>
                <q-item-section>
                  <q-item-label>Cambiar avatar</q-item-label>
                </q-item-section>
              </q-item>
              <q-item clickable @click="permisosShow(props.row)" v-close-popup>
                <q-item-section avatar>
                  <q-icon name="lock" />
                </q-item-section>
                <q-item-section>
                  <q-item-label>Permisos</q-item-label>
                </q-item-section>
              </q-item>
            </q-list>
          </q-btn-dropdown>
        </q-td>
      </template>
      <template v-slot:body-cell-role="props">
        <q-td :props="props">
          <q-chip :label="props.row.role"
                  :color="$filters.color(props.row.role)"
                  text-color="white" dense size="14px"/>
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
      <template v-slot:body-cell-permissions="props">
        <q-td :props="props">
          <div class="row items-center q-col-gutter-xs">

            <!-- hasta 3 chips visibles -->
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

            <!-- si hay más, badge + tooltip con el listado completo -->
            <template v-if="(props.row.permissions || []).length > 3">
              <q-badge outline color="primary" class="q-ml-xs">
                +{{ (props.row.permissions || []).length - 3 }}
                <q-tooltip anchor="top middle" self="bottom middle" :offset="[0,8]">
                  <div class="text-left">
                    <div
                      v-for="perm in props.row.permissions"
                      :key="perm.id"
                    >• {{ perm.name }}</div>
                  </div>
                </q-tooltip>
              </q-badge>
            </template>

            <!-- sin permisos -->
            <q-badge v-if="!(props.row.permissions || []).length" color="grey-5" text-color="white" outline>
              Sin permisos
            </q-badge>
          </div>
        </q-td>
      </template>
      <!--      <template v-slot:body-cell-permisos="props">-->
      <!--        <q-td :props="props">-->
      <!--          <ul class="pm-0">-->
      <!--            <li class="pm-0" v-for="permiso in props.row.userPermisos" :key="permiso.id">-->
      <!--              {{ permiso?.permiso?.nombre }}-->
      <!--            </li>-->
      <!--          </ul>-->
      <!--        </q-td>-->
      <!--      </template>-->
    </q-table>
<!--    <pre>{{ users }}</pre>-->
    <q-dialog v-model="userDialog" persistent>
      <q-card style="width: 400px">
        <q-card-section class="q-pb-none row items-center">
          <div>
            {{ actionUser }} user
          </div>
          <q-space/>
          <q-btn icon="close" flat round dense @click="userDialog = false"/>
        </q-card-section>
        <q-card-section class="q-pt-none">
          <q-form @submit="user.id ? userPut() : userPost()">
            <q-input v-model="user.name" label="Nombre" dense outlined :rules="[val => !!val || 'Campo requerido']"/>
            <q-input v-model="user.username" label="Usuario" dense outlined
                     :rules="[val => !!val || 'Campo requerido']"/>
            <q-input v-model="user.email" label="Email" dense outlined hint="" />
            <q-input v-model="user.password" label="Contraseña" dense outlined
                     :rules="[val => !!val || 'Campo requerido']" v-if="!user.id"/>
            <q-select v-model="user.role" label="Rol" dense outlined :options="roles"
                      :rules="[val => !!val || 'Campo requerido']"/>
<!--            <q-select v-model="user.docente_id" label="Docente" dense outlined :options="docentes"-->
<!--                      option-label="nombre" option-value="id" emit-value map-options-->
<!--                      :rules="[val => !!val || 'Campo requerido']"/>-->
            <!--            <q-input v-model="user.phone" label="Telefono" dense outlined hint="" />-->
            <!--            <q-input v-model="user.codigo" label="Codigo" dense outlined hint="" />-->
            <!--            <q-input v-model="user.gestion" label="Gestion" dense outlined hint="" />-->
            <!--            <q-input v-model="user.bloque" label="Bloque" dense outlined hint="" />-->
<!--            <pre>{{user}}</pre>-->
            <div class="text-right">
              <q-btn color="negative" label="Cancelar" @click="userDialog = false" no-caps :loading="loading"/>
              <q-btn color="primary" label="Guardar" type="submit" no-caps :loading="loading" class="q-ml-sm"/>
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>
    <!--    dialogPermisos-->
    <!--    <q-dialog v-model="dialogPermisos" persistent>-->
    <!--      <q-card>-->
    <!--        <q-card-section class="q-pb-none row items-center text-bold">-->
    <!--          Permisos-->
    <!--          <q-space />-->
    <!--          <q-btn icon="close" flat round dense @click="dialogPermisos = false" />-->
    <!--        </q-card-section>-->
    <!--        <q-card-section class="q-pt-none">-->
    <!--          <q-list dense>-->
    <!--            <q-item v-for="permiso in permissions" :key="permiso.id">-->
    <!--              <q-item-section>-->
    <!--                <q-item-label>{{ permiso.nombre }}</q-item-label>-->
    <!--              </q-item-section>-->
    <!--              <q-item-section side>-->
    <!--                <q-toggle v-model="permiso.checked" />-->
    <!--              </q-item-section>-->
    <!--            </q-item>-->
    <!--          </q-list>-->
    <!--          &lt;!&ndash;          <pre>{{ user }}</pre>&ndash;&gt;-->
    <!--        </q-card-section>-->
    <!--        <q-card-actions align="right">-->
    <!--          <q-btn color="negative" label="Cancelar" @click="dialogPermisos = false" no-caps :loading="loading" />-->
    <!--          <q-btn color="primary" label="Guardar" @click="permisosPost" no-caps :loading="loading" />-->
    <!--        </q-card-actions>-->
    <!--      </q-card>-->
    <!--    </q-dialog>-->
    <q-dialog v-model="cambioAvatarDialogo" persistent>
      <q-card>
        <q-card-section class="q-pb-none row items-center text-bold">
          Cambiar avatar
          <q-space/>
          <q-btn icon="close" flat round dense @click="cambioAvatarDialogo = false"/>
        </q-card-section>
        <q-card-section class="q-pt-none">
          <q-form @submit="userPut()">
            <!--            <q-avatar>-->
            <div>
              <div style="position: relative;top: 0;left: 0;">
                <q-btn icon="edit" size="10px" class="absolute q-mt-sm q-ml-sm" @click="$refs.fileInput.click()" dense
                       outline label="Cambiar foto" no-caps/>
              </div>
              <img :src="`${$url}/../images/${user.avatar}`" width="300px" height="300px" v-if="user.avatar"/>
              <q-icon name="person" size="100px" v-else/>
              <input ref="fileInput" type="file" style="display: none;" @change="onFileChange" accept="image/*"/>
            </div>
            <!--            </q-avatar>-->
            <div class="text-right">
              <q-btn color="negative" label="Cancelar" @click="cambioAvatarDialogo = false" no-caps :loading="loading"/>
              <q-btn color="primary" label="Guardar" type="submit" no-caps :loading="loading" class="q-ml-sm"/>
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>
    <q-dialog v-model="dialogPermisos" persistent>
      <q-card style="min-width: 420px">
        <q-card-section class="q-pb-none row items-center text-bold">
          Permisos de {{ user.username }}
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
import moment from 'moment'

export default {
  name: 'UsuariosPage',
  data() {
    return {
      users: [],
      user: {},
      userDialog: false,
      loading: false,
      actionUser: '',
      gestiones: [],
      filter: '',
      // USUARIO GERENTE PRODUCTOR PRODUCCION ADMINITRATIVO
      roles: ['Administrador', 'Vendedor'],
      columns: [
        {name: 'actions', label: 'Acciones', align: 'center'},
        {name: 'name', label: 'Nombre', align: 'left', field: 'name'},
        {name: 'username', label: 'Usuario', align: 'left', field: 'username'},
        {name: 'avatar', label: 'Avatar', align: 'left', field: (row) => row.avatar},
        {name: 'role', label: 'Rol', align: 'left', field: 'role'},
        { name: 'permissions', label: 'Permisos', align: 'left',
          field: row => (row.permissions || []).map(p => p.name).join(', ')
        },
      ],
      permissions: [],
      dialogPermisos: false,
      permFilter: '',
      cambioAvatarDialogo: false,
      docentes: [],
    }
  },
  async mounted() {
    // this.docentes = await this.$axios.get('docentes').then(res => res.data)
    this.usersGet()
    // this.permissionsGet()
  },
  methods: {
    onFileChange(event) {
      const file = event.target.files[0]
      const formData = new FormData()
      formData.append('avatar', file)
      this.loading = true
      this.$axios.post(this.user.id + '/avatar', formData, {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      }).then(res => {
        this.cambioAvatarDialogo = false
        this.$alert.success('Avatar actualizado')
        this.usersGet()
      }).catch(error => {
        this.$alert.error(error.response.data.message)
      }).finally(() => {
        this.loading = false
      })
    },
    cambiarAvatar(user) {
      this.cambioAvatarDialogo = true
      this.user = {...user}
    },
    // permisosPost() {
    //   this.loading = true
    //   const permissions = this.permissions.filter(p => p.checked).map(p => p.id)
    //   this.$axios.post('permisos', {
    //     user_id: this.user.id,
    //     permissions
    //   }).then(res => {
    //     this.dialogPermisos = false
    //     this.$alert.success('Permisos actualizados')
    //     this.usersGet()
    //   }).catch(error => {
    //     this.$alert.error(error.response.data.message)
    //   }).finally(() => {
    //     this.loading = false
    //   })
    // },
    // permissionsGet() {
    //   this.$axios.get('permisos').then(res => {
    //     this.permissions = res.data
    //   }).catch(error => {
    //     this.$alert.error(error.response.data.message)
    //   })
    // },
    userNew() {
      this.user = {
        name: '',
        email: '',
        password: '',
        area_id: 1,
        username: '',
        cargo: '',
        role: 'Vendedor',
      }
      this.actionUser = 'Nuevo'
      this.userDialog = true
    },
    usersGet() {
      this.loading = true
      this.users = []
      this.$axios.get('users').then(res => {
        this.users = res.data
      }).catch(error => {
        this.$alert.error(error.response.data.message)
      }).finally(() => {
        this.loading = false
      })
    },
    gestionGet() {
      this.loading = true
      this.$axios.get('gestiones').then(res => {
        this.gestiones = res.data
        this.loading = false
      }).catch(error => {
        this.$alert.error(error.response.data.message)
        this.loading = false
      })
    },
    userPost() {
      this.loading = true
      this.$axios.post('users', this.user).then(res => {
        this.userDialog = false
        this.$alert.success('User creado')
        this.usersGet()
        // this.users.push(res.data)
      }).catch(error => {
        this.$alert.error(error.response.data.message)
      }).finally(() => {
        this.loading = false
      })
    },
    userPut() {
      this.loading = true
      this.$axios.put('users/' + this.user.id, this.user).then(res => {
        this.usersGet()
        this.userDialog = false
        this.$alert.success('User actualizado')
      }).catch(error => {
        this.$alert.error(error.response.data.message)
      }).finally(() => {
        this.loading = false
      })
    },
    async permisosShow(user) {
      this.user = { ...user }
      this.dialogPermisos = true
      this.loading = true
      try {
        // 1) Traer todos los permisos
        const all = await this.$axios.get('permissions').then(r => r.data)
        // 2) Traer permisos del usuario (array de IDs)
        const userPermIds = await this.$axios.get(`users/${user.id}/permissions`).then(r => r.data)

        // 3) Mezclar con checked
        this.permissions = all.map(p => ({
          ...p,
          checked: userPermIds.includes(p.id)
        }))
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
    userEditPassword(user) {
      this.user = {...user}
      this.$alert.dialogPrompt('Nueva contraseña', 'Ingrese la nueva contraseña', 'password')
        .onOk(password => {
          this.$axios.put('updatePassword/' + user.id, {
            password: password
          }).then(res => {
            this.usersGet()
            this.$alert.success('Contraseña actualizada de ' + user.username)
          }).catch(error => {
            this.$alert.error(error.response.data.message)
          })
        })
    },
    userEdit(user) {
      this.user = {...user}
      this.actionUser = 'Editar'
      this.userDialog = true
    },
    userDelete(id) {
      this.$alert.dialog('¿Desea eliminar el user?')
        .onOk(() => {
          this.loading = true
          this.$axios.delete('users/' + id).then(res => {
            this.usersGet()
            this.$alert.success('User eliminado')
          }).catch(error => {
            this.$alert.error(error.response.data.message)
          }).finally(() => {
            this.loading = false
          })
        })
    },
  },
  computed: {
    filteredPermissions() {
      if (!this.permFilter) return this.permissions
      const t = this.permFilter.toLowerCase()
      return this.permissions.filter(p => p.name.toLowerCase().includes(t))
    }
  },
}
</script>
