<template>
  <q-page class="q-pa-md bg-grey-2">
    <div class="row justify-center">
      <div class="col-12 col-md-6 col-lg-5">
        <q-card flat bordered class="bg-white">
          <q-card-section>
            <div class="text-h6 text-weight-bold">Cambiar contraseña</div>
            <div class="text-caption text-grey-7">
              Solo disponible para Administrador y Supervisor.
            </div>
          </q-card-section>

          <q-separator />

          <q-card-section class="q-gutter-md">
            <q-input
              v-model="form.current_password"
              :type="showCurrent ? 'text' : 'password'"
              dense
              outlined
              label="Contraseña actual"
            >
              <template #append>
                <q-icon
                  :name="showCurrent ? 'visibility_off' : 'visibility'"
                  class="cursor-pointer"
                  @click="showCurrent = !showCurrent"
                />
              </template>
            </q-input>

            <q-input
              v-model="form.password"
              :type="showNew ? 'text' : 'password'"
              dense
              outlined
              label="Contraseña nueva"
            >
              <template #append>
                <q-icon
                  :name="showNew ? 'visibility_off' : 'visibility'"
                  class="cursor-pointer"
                  @click="showNew = !showNew"
                />
              </template>
            </q-input>

            <q-input
              v-model="form.password_confirmation"
              :type="showConfirm ? 'text' : 'password'"
              dense
              outlined
              label="Repetir contraseña nueva"
            >
              <template #append>
                <q-icon
                  :name="showConfirm ? 'visibility_off' : 'visibility'"
                  class="cursor-pointer"
                  @click="showConfirm = !showConfirm"
                />
              </template>
            </q-input>
          </q-card-section>

          <q-card-actions align="right" class="q-pa-md">
            <q-btn flat no-caps label="Cancelar" @click="$router.push('/')" />
            <q-btn
              color="primary"
              no-caps
              label="Actualizar contraseña"
              :loading="saving"
              @click="savePassword"
            />
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
const showCurrent = ref(false)
const showNew = ref(false)
const showConfirm = ref(false)

const form = ref({
  current_password: '',
  password: '',
  password_confirmation: ''
})

function canChangePassword () {
  const role = proxy.$store?.user?.role || ''
  return role === 'Administrador' || role === 'Supervisor'
}

function forceLogoutWithMessage () {
  proxy.$store.isLogged = false
  proxy.$store.user = {}
  proxy.$store.permissions = []
  localStorage.removeItem('tokenResultados')
  localStorage.removeItem('user')
  localStorage.setItem('auth_success_message', 'Puede ingresar con su nueva clave, por favor.')
  proxy.$router.push('/login')
}

function savePassword () {
  if (!form.value.current_password || !form.value.password || !form.value.password_confirmation) {
    proxy.$alert.error('Complete todos los campos')
    return
  }

  if (form.value.password !== form.value.password_confirmation) {
    proxy.$alert.error('La nueva contraseña y su repetición no coinciden')
    return
  }

  saving.value = true
  proxy.$axios.post('/me/change-password', {
    current_password: form.value.current_password,
    password: form.value.password,
    password_confirmation: form.value.password_confirmation
  })
    .then(() => {
      forceLogoutWithMessage()
    })
    .catch((err) => {
      proxy.$alert.error(err?.response?.data?.message || 'No se pudo cambiar la contraseña')
    })
    .finally(() => {
      saving.value = false
    })
}

onMounted(() => {
  if (!canChangePassword()) {
    proxy.$alert.error('No autorizado')
    proxy.$router.push('/')
  }
})
</script>
