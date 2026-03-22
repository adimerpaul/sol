<template>
  <q-layout view="lHh Lpr lFf" class="public-layout">
    <q-header bordered class="bg-white text-dark">
      <q-toolbar class="q-px-md">
        <q-avatar rounded size="42px" class="bg-grey-2">
          <q-img src="/logo.png" fit="contain" />
        </q-avatar>
        <div class="q-ml-md">
          <div class="text-subtitle1 text-weight-bold">Recintos Públicos</div>
          <div class="text-caption text-grey-7">Ubicación, jefe de recinto y cantidad de mesas</div>
        </div>
      </q-toolbar>
    </q-header>

    <q-page-container class="bg-grey-2">
      <router-view />
    </q-page-container>
  </q-layout>
</template>

<script setup>
</script>

<style scoped>
.public-layout {
  background:
    radial-gradient(circle at top right, rgba(14, 165, 233, 0.10), transparent 28%),
    linear-gradient(180deg, #f8fafc 0%, #eef2f7 100%);
}
</style>
