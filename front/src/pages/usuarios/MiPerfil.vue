<template>
  <q-page class="q-pa-md bg-grey-2">
    <div class="row justify-center">
      <div class="col-12 col-md-8 col-lg-7">
        <q-card flat bordered class="bg-white">
          <q-card-section>
            <div class="text-h6 text-weight-bold">Mi perfil</div>
            <div class="text-caption text-grey-7">
              Actualiza tu username y tus datos básicos.
            </div>
            <div class="text-caption text-primary q-mt-xs">
              El username es el dato que usarás para ingresar al login.
            </div>
            <div v-if="creatorLabel" class="q-mt-sm">
              <q-chip dense outline color="primary">
                Registrado por: {{ creatorLabel }}
              </q-chip>
            </div>
          </q-card-section>

          <q-separator />

          <q-card-section class="row q-col-gutter-sm">
            <div class="col-12 col-md-6">
              <q-input v-model="form.username" dense outlined label="Username" />
            </div>
            <div class="col-12 col-md-6">
              <q-input v-model="form.celular" dense outlined label="Celular" />
            </div>
            <div class="col-12 col-md-6">
              <q-input v-model="form.nombres" dense outlined label="Nombres" />
            </div>
            <div class="col-12 col-md-6">
              <q-input v-model="form.apellido_paterno" dense outlined label="Apellido paterno" />
            </div>
            <div class="col-12 col-md-6">
              <q-input v-model="form.apellido_materno" dense outlined label="Apellido materno (opcional)" />
            </div>
            <div class="col-12 col-md-6">
              <q-input v-model="form.email" dense outlined label="Email" />
            </div>
          </q-card-section>

          <q-card-actions align="right" class="q-pa-md">
            <q-btn flat no-caps label="Cancelar" @click="$router.push('/')" />
            <q-btn color="primary" no-caps label="Guardar cambios" :loading="saving" @click="save" />
          </q-card-actions>
        </q-card>
      </div>
    </div>
  </q-page>
</template>

<script setup>
import { getCurrentInstance, onMounted, ref } from 'vue'

const { proxy } = getCurrentInstance()
const saving = ref(false)
const creatorLabel = ref('')

const form = ref({
  username: '',
  nombres: '',
  apellido_paterno: '',
  apellido_materno: '',
  celular: '',
  email: ''
})

function loadFromStore () {
  const u = proxy.$store?.user || {}
  form.value.username = u.username || ''
  form.value.nombres = u.nombres || ''
  form.value.apellido_paterno = u.apellido_paterno || ''
  form.value.apellido_materno = u.apellido_materno || ''
  form.value.celular = u.celular || ''
  form.value.email = u.email || ''
  creatorLabel.value = u.creator_name || u.creator?.name || u.creator_username || u.creator?.username || ''
}

function canAccessProfile () {
  const role = proxy.$store?.user?.role || ''
  return role === 'Administrador' || role === 'Supervisor'
}

function save () {
  if (!form.value.username || !form.value.nombres) {
    proxy.$alert.error('Complete username y nombres')
    return
  }

  saving.value = true
  proxy.$axios.put('/me/profile', form.value)
    .then((res) => {
      const user = res?.data?.user || {}
      proxy.$store.user = user
      proxy.$store.permissions = (user.permissions || []).map(p => p.name)
      localStorage.setItem('user', JSON.stringify(user))
      proxy.$alert.success(res?.data?.message || 'Perfil actualizado')
    })
    .catch((err) => {
      proxy.$alert.error(err?.response?.data?.message || 'No se pudo actualizar el perfil')
    })
    .finally(() => {
      saving.value = false
    })
}

onMounted(() => {
  if (!canAccessProfile()) {
    proxy.$alert.error('Solo Administrador y Supervisor pueden visualizar este módulo')
    proxy.$router.push('/')
    return
  }
  loadFromStore()
})
</script>
