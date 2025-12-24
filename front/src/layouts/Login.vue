<template>
  <q-layout class="login-layout">
    <q-page-container>
      <q-page class="full-height">
        <div class="login-bg-overlay"></div>

        <q-form @submit="login" class="login-wrap">
          <q-card flat bordered class="login-card">
            <q-card-section class="q-pt-lg text-center">
              <!-- LOGO -->
              <q-img
                src="logo.png"
                width="120px"
                class="q-mb-sm"
                ratio="1"
                fit="contain"
              />
              <br>

              <!-- CHIP MARCA -->
              <div class="text-subtitle2 text-grey-8 brand-chip">
                <b>Multicines</b> · Plaza
              </div>
            </q-card-section>

            <q-separator spaced />

            <q-card-section class="q-pt-none">
              <div class="text-h6 text-weight-bold q-mb-xs">Iniciar sesión</div>
              <div class="text-body2 text-grey-7 q-mb-md">
                Accede al sistema de <b>Multicines Plaza</b> con tus credenciales.
              </div>

              <div class="q-mb-sm text-caption text-grey-7">Usuario</div>
              <q-input
                v-model="username"
                outlined dense
                placeholder="Usuario"
                :rules="[v => !!v || 'Ingrese su usuario']"
                class="q-mb-md"
              >
                <template #prepend><q-icon name="account_circle" size="18px" /></template>
              </q-input>

              <div class="q-mb-sm text-caption text-grey-7">Contraseña</div>
              <q-input
                v-model="password"
                outlined dense
                :type="showPassword ? 'text' : 'password'"
                placeholder="Contraseña"
                :rules="[v => !!v || 'Ingrese su contraseña']"
              >
                <template #prepend><q-icon name="lock" size="18px" /></template>
                <template #append>
                  <q-icon
                    :name="showPassword ? 'visibility' : 'visibility_off'"
                    size="18px"
                    class="cursor-pointer"
                    @click="showPassword = !showPassword"
                  />
                </template>
              </q-input>

              <div class="row items-center q-mt-sm q-mb-md">
                <q-checkbox v-model="rememberMe" label="Recuérdame" color="primary" dense />
                <q-space />
                <q-btn
                  flat dense no-caps
                  class="text-weight-medium link-muted"
                  label="¿Olvidaste tu contraseña?"
                />
              </div>

              <q-btn
                color="primary"
                label="Iniciar sesión"
                class="full-width btnLogin"
                no-caps
                unelevated
                size="16px"
                :loading="loading"
                type="submit"
              />
            </q-card-section>

            <q-card-section class="q-pt-none text-center">
              <div class="text-body2">
                ¿No tienes cuenta?
                <q-btn flat dense no-caps class="text-weight-medium link-primary" label="Solicitar acceso" />
              </div>

              <q-separator spaced />

              <div class="text-caption text-grey-6">
                © {{ year }} Multicines Plaza. Todos los derechos reservados.
              </div>
            </q-card-section>
          </q-card>
        </q-form>
      </q-page>
    </q-page-container>
  </q-layout>
</template>

<script setup>
import { getCurrentInstance, ref, computed } from 'vue'
const { proxy } = getCurrentInstance()

const username = ref('')
const password = ref('')
const showPassword = ref(false)
const rememberMe = ref(false)
const loading = ref(false)
const year = computed(() => new Date().getFullYear())

function login () {
  loading.value = true
  proxy.$axios.post('/login', { username: username.value, password: password.value })
    .then(res => {
      const { user, token } = res.data
      proxy.$axios.defaults.headers.common.Authorization = `Bearer ${token}`
      proxy.$store.isLogged = true
      proxy.$store.user = user
      proxy.$store.permissions = (user.permissions || []).map(p => p.name)

      // nombre más coherente para Plaza
      localStorage.setItem('tokenPlazaMovie', token)
      localStorage.setItem('user', JSON.stringify(user))

      proxy.$alert.success('Bienvenido', user.name)
      proxy.$router.push('/')
    })
    .catch(err => {
      proxy.$alert.error(err?.response?.data?.message || 'Error de autenticación', 'Error')
    })
    .finally(() => { loading.value = false })
}
</script>

<style scoped>
/* =========================================================
   TEMA MULTICINES PLAZA (AZUL)
   Ajusta aquí si quieres un azul más claro/oscuro
========================================================= */
.login-layout{
  --plaza-primary: #1f64ff;   /* azul principal */
  --plaza-primary-2: #0b2a6b; /* azul oscuro */
  --plaza-accent:  #37d3ff;   /* celeste */
  --plaza-ink:     #0b1220;

  /* Quasar usa var(--q-primary) en bg-primary/text-primary */
  --q-primary: var(--plaza-primary);

  background-image: url('./../bg.jpg'); /* si quieres, cámbialo por un fondo de Multicines */
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
  min-height: 100vh;
}

.full-height { min-height: 100vh; position: relative; }

.login-bg-overlay{
  position: absolute; inset: 0;
  /* overlay azul cine */
  background:
    radial-gradient(1100px 800px at 75% 35%, rgba(55, 211, 255, .18), rgba(0,0,0,.30)),
    linear-gradient(135deg, rgba(11, 42, 107, .65), rgba(0,0,0,.55));
  backdrop-filter: blur(3px);
}

/* ===== Wrapper / Card ===== */
.login-wrap{
  position: relative;
  z-index: 1;
  max-width: 520px;
  margin: 0 auto;
  padding: 24px 12px;
  display: flex;
  align-items: center;
  min-height: 100vh;
}

.login-card{
  width: 100%;
  border-radius: 18px;
  background: rgba(255,255,255,0.86);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border: 1px solid rgba(255,255,255,0.65);
  box-shadow:
    0 14px 34px rgba(0,0,0,0.18),
    0 2px 10px rgba(0,0,0,0.08);
}

/* ===== Marca ===== */
.brand-chip{
  display: inline-block;
  padding: 6px 12px;
  border-radius: 999px;
  background: rgba(31, 100, 255, 0.10);
  border: 1px solid rgba(31, 100, 255, 0.18);
}

/* links */
.link-muted { color: #6b7280 !important; }
.link-muted:hover { color: var(--plaza-primary) !important; }

.link-primary{ color: var(--plaza-primary) !important; }
.link-primary:hover{ color: var(--plaza-primary-2) !important; }

/* ===== Botón ===== */
.btnLogin{
  height: 44px;
  border-radius: 12px;
  transition: transform .18s ease, box-shadow .18s ease, filter .18s ease;
  background: linear-gradient(135deg, var(--plaza-primary), var(--plaza-accent)) !important;
  color: #fff !important;
  box-shadow: 0 10px 18px rgba(31,100,255,.22);
}

.btnLogin:hover{
  transform: translateY(-1px);
  filter: brightness(1.03);
  box-shadow: 0 14px 22px rgba(31,100,255,.28);
}

.btnLogin:active{
  transform: translateY(0px);
}

/* ===== Responsivo ===== */
@media (max-width: 768px){
  .login-wrap { max-width: 92vw; padding: 16px 8px; }
  .login-card { border-radius: 14px; }
}
</style>
