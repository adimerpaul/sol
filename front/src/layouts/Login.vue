<template>
  <q-layout class="login-layout">
    <q-page-container>
      <q-page class="full-height">
        <div class="login-bg-overlay"></div>

        <q-form @submit="login" class="login-wrap">
          <q-card flat bordered class="login-card">
            <q-card-section class="q-pt-lg text-center">
              <q-img src="logo.png" width="110px" class="q-mb-sm" ratio="1" fit="contain" />
              <br>
              <div class="text-subtitle2 text-grey-7 brand-chip">
                <b>Resultados</b>  Electorales
              </div>
            </q-card-section>

            <q-separator spaced />

            <q-card-section class="q-pt-none">
              <div class="text-h6 text-bold q-mb-xs">Iniciar sesion</div>
              <div class="text-body2 text-grey-7 q-mb-md">
                Accede con tu username y fecha de nacimiento.
              </div>

              <div class="q-mb-sm text-caption text-grey-7">Carnet de identidad</div>
              <q-input
                v-model="username"
                outlined
                dense
                placeholder="Ej: 1234567 "
                :rules="[v => !!v || 'Ingrese su username']"
                class="q-mb-md"
              >
                <template #prepend><q-icon name="badge" size="18px" /></template>
              </q-input>

              <div class="q-mb-sm text-caption text-grey-7">Fecha de nacimiento</div>
              <div class="row q-col-gutter-sm q-mb-md">
                <div class="col-4">
                  <q-select
                    v-model="birthDay"
                    :options="dayOptions"
                    outlined
                    dense
                    emit-value
                    map-options
                    label="Dia"
                    :rules="[v => !!v || 'Dia requerido']"
                  />
                </div>
                <div class="col-4">
                  <q-select
                    v-model="birthMonth"
                    :options="monthOptions"
                    outlined
                    dense
                    emit-value
                    map-options
                    label="Mes"
                    :rules="[v => !!v || 'Mes requerido']"
                  />
                </div>
                <div class="col-4">
                  <q-select
                    v-model="birthYear"
                    :options="yearOptions"
                    outlined
                    dense
                    emit-value
                    map-options
                    label="Año"
                    :rules="[v => !!v || 'Ano requerido']"
                  />
                </div>
              </div>

              <q-btn
                color="primary"
                label="Ingresar"
                class="full-width btnLogin"
                no-caps
                unelevated
                size="16px"
                :loading="loading"
                type="submit"
              />
            </q-card-section>

            <q-card-section class="q-pt-none text-center">
              <div class="text-caption text-grey-6">
                 {{ year }} Resultados Electorales. Todos los derechos reservados.
              </div>
            </q-card-section>
          </q-card>
        </q-form>
      </q-page>
    </q-page-container>
  </q-layout>
</template>

<script setup>
import { computed, getCurrentInstance, ref, watch } from 'vue'
import { useCounterStore } from 'stores/example-store.js'

const { proxy } = getCurrentInstance()

const username = ref('')
const loading = ref(false)

const now = new Date()
const currentYear = now.getFullYear()

const birthDay = ref(null)
const birthMonth = ref(null)
const birthYear = ref(null)

const year = computed(() => currentYear)

const yearOptions = computed(() => {
  const out = []
  for (let y = currentYear; y >= currentYear - 100; y--) {
    out.push({ label: String(y), value: y })
  }
  return out
})

const monthOptions = [
  { label: '01', value: 1 },
  { label: '02', value: 2 },
  { label: '03', value: 3 },
  { label: '04', value: 4 },
  { label: '05', value: 5 },
  { label: '06', value: 6 },
  { label: '07', value: 7 },
  { label: '08', value: 8 },
  { label: '09', value: 9 },
  { label: '10', value: 10 },
  { label: '11', value: 11 },
  { label: '12', value: 12 }
]

const maxDays = computed(() => {
  if (!birthYear.value || !birthMonth.value) return 31
  return new Date(birthYear.value, birthMonth.value, 0).getDate()
})

const dayOptions = computed(() => {
  const out = []
  for (let d = 1; d <= maxDays.value; d++) {
    out.push({ label: String(d).padStart(2, '0'), value: d })
  }
  return out
})

watch(maxDays, (newMax) => {
  if (birthDay.value && birthDay.value > newMax) {
    birthDay.value = null
  }
})

function buildBirthDate () {
  const y = birthYear.value
  const m = String(birthMonth.value).padStart(2, '0')
  const d = String(birthDay.value).padStart(2, '0')
  return `${y}-${m}-${d}`
}

function login () {
  if (!username.value || !birthYear.value || !birthMonth.value || !birthDay.value) {
    proxy.$alert.error('Complete username y fecha de nacimiento')
    return
  }

  loading.value = true

  proxy.$axios.post('/login', {
    username: username.value,
    fecha_nacimiento: buildBirthDate()
  })
    .then(res => {
      const { user, token } = res.data
      proxy.$axios.defaults.headers.common.Authorization = `Bearer ${token}`
      proxy.$store.isLogged = true
      proxy.$store.user = user
      proxy.$store.permissions = (user.permissions || []).map(p => p.name)
      localStorage.setItem('tokenResultados', token)
      localStorage.setItem('user', JSON.stringify(user))
      proxy.$alert.success('Bienvenido', user.name)
      proxy.$router.push('/')
    })
    .catch(err => {
      proxy.$alert.error(err?.response?.data?.message || 'Error de autenticacion', 'Error')
    })
    .finally(() => {
      loading.value = false
    })
}
</script>

<style scoped>
.login-layout {
  background-image: url('./../bg.jpg');
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
  min-height: 100vh;
}

.full-height {
  min-height: 100vh;
  position: relative;
}

.login-bg-overlay {
  position: absolute;
  inset: 0;
  backdrop-filter: blur(3px);
  background: radial-gradient(1200px 800px at 70% 40%, rgba(0, 0, 0, 0.12), rgba(0, 0, 0, 0.25));
}

.login-wrap {
  position: relative;
  z-index: 1;
  max-width: 520px;
  margin: 0 auto;
  padding: 24px 12px;
  display: flex;
  align-items: center;
  min-height: 100vh;
}

.login-card {
  width: 100%;
  border-radius: 18px;
  background: rgba(255, 255, 255, 0.78);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.6);
  box-shadow:
    0 10px 25px rgba(0, 0, 0, 0.08),
    0 2px 8px rgba(0, 0, 0, 0.05);
}

.brand-chip {
  display: inline-block;
  padding: 6px 10px;
  border-radius: 999px;
  background: rgba(15, 23, 42, 0.06);
}

.btnLogin {
  height: 42px;
  border-radius: 10px;
  transition: all .25s ease;
}

.btnLogin:hover {
  background-color: #fff !important;
  color: var(--q-primary) !important;
  outline: 1px solid var(--q-primary) !important;
}

@media (max-width: 768px) {
  .login-wrap {
    max-width: 92vw;
    padding: 16px 8px;
  }

  .login-card {
    border-radius: 14px;
  }
}
</style>
