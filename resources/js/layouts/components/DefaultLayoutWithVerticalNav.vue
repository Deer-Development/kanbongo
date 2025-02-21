<script setup>
import navItems from '@/navigation/vertical'
import { themeConfig } from '@themeConfig'

import UserProfile from '@/layouts/components/UserProfile.vue'
import NavBarI18n from '@core/components/I18n.vue'

import { VerticalNavLayout } from '@layouts'
import NavBarNotifications from "@/layouts/components/NavBarNotifications.vue"
</script>

<template>
  <VerticalNavLayout :nav-items="navItems">
    <!-- 👉 navbar -->
    <template #navbar="{ toggleVerticalOverlayNavActive }">
      <div class="d-flex h-100 align-center">
        <IconBtn
          id="vertical-nav-toggle-btn"
          class="ms-n3 d-lg-none"
          @click="toggleVerticalOverlayNavActive(true)"
        >
          <VIcon
            size="26"
            icon="tabler-menu-2"
          />
        </IconBtn>
        <VSpacer />

        <NavBarI18n
          v-if="themeConfig.app.i18n.enable && themeConfig.app.i18n.langConfig?.length"
          :languages="themeConfig.app.i18n.langConfig"
        />
        <NavBarNotifications class="me-2" />
        <UserProfile />
      </div>
    </template>

    <slot />
  </VerticalNavLayout>
</template>
