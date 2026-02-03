<script setup>
import { useRouter, useRoute } from 'vue-router';

const router = useRouter();
const route = useRoute();

const tabs = [
  { id: 'onboarding', label: 'Onboarding', path: '/onboarding' },
];

const isActiveTab = (path) => {
  return route.path === path;
};

const navigateTo = (path) => {
  router.push(path);
};
</script>

<template>
  <div class="min-h-screen">
    <!-- Header Section -->
    <div class="relative bg-white overflow-hidden">
      <!-- Header Content -->
      <div class="relative px-6 py-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-4">
            <i-lucide-bolt class="size-10 fill-current" />
            <h1 class="text-2xl font-semibold text-gray-800">Picowind</h1>
          </div>

          <!-- Side -->
          <div class="flex items-center gap-3">
            <span class="text-sm text-gray-700 font-medium">Version {{ window.picowind?._version }}</span>

          </div>
        </div>
      </div>

      <!-- Navigation Tabs -->
      <div class="relative bg-gray-100 border-b border-gray-500/30">
        <nav class="px-8 flex flex-row">
          <ul class="flex items-baseline gap-x-7 m-0 pt-5 uppercase font-semibold text-xs">
            <li
              v-for="tab in tabs"
              :key="tab.id"
              class="m-0 px-1 pt-2.5 pb-1.5 grow has-[>.router-link-active]:border-b-[3px] has-[>.router-link-active]:border-solid has-[>.router-link-active]:border-black"
            >
              <RouterLink
                :to="tab.path"
                class="no-underline text-gray-600 hover:text-gray-900 focus:shadow-none [.router-link-active&]:text-black"
              >
                {{ tab.label }}
              </RouterLink>
            </li>
          </ul>
        </nav>
      </div>
    </div>

    <!-- Main Content Area -->
    <RouterView />
  </div>
</template>
