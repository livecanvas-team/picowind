<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { storeToRefs } from 'pinia';
import { useOnboardingStore } from '../stores/onboarding';

const onboardingStore = useOnboardingStore();
const { status, bundledThemes, recommendedPlugins, error } = storeToRefs(onboardingStore);

const pageLoading = ref(true);
const actionState = ref<{ id: string; type: 'install' | 'activate' } | null>(null);
const pluginActionState = ref<{ slug: string; type: 'install' | 'activate' } | null>(null);
const successMessage = ref<string | null>(null);

const isBusy = computed(() => actionState.value !== null || pluginActionState.value !== null);
const hasActiveChildTheme = computed(() => Boolean(status.value?.childTheme));
const activeChildThemeName = computed(() => status.value?.childTheme?.name ?? '');

onMounted(async () => {
  try {
    await refresh();
  } finally {
    pageLoading.value = false;
  }
});

async function refresh() {
  try {
    await Promise.all([
      onboardingStore.fetchStatus(),
      onboardingStore.fetchThemes(),
      onboardingStore.fetchPlugins(),
    ]);
  } catch (err) {
    // Error is handled by the store
  }
}

function isAction(themeId: string, type: 'install' | 'activate') {
  return actionState.value?.id === themeId && actionState.value?.type === type;
}

function isPluginAction(slug: string, type: 'install' | 'activate') {
  return pluginActionState.value?.slug === slug && pluginActionState.value?.type === type;
}

async function installTheme(themeId: string) {
  successMessage.value = null;
  actionState.value = { id: themeId, type: 'install' };

  try {
    const response = await onboardingStore.installTheme(themeId);
    successMessage.value = response?.message || 'Theme installed successfully.';
  } catch (err) {
    // Error is handled by the store
  } finally {
    await refresh();
    actionState.value = null;
  }
}

async function activateTheme(themeSlug: string, themeId: string) {
  if (!themeSlug) {
    return;
  }

  successMessage.value = null;
  actionState.value = { id: themeId, type: 'activate' };

  try {
    const response = await onboardingStore.activateTheme(themeSlug);
    successMessage.value = response?.message || 'Theme activated successfully.';
  } catch (err) {
    // Error is handled by the store
  } finally {
    await refresh();
    actionState.value = null;
  }
}

async function installPlugin(slug: string) {
  successMessage.value = null;
  pluginActionState.value = { slug, type: 'install' };

  try {
    const response = await onboardingStore.installPlugin(slug);
    successMessage.value = response?.message || 'Plugin installed successfully.';
  } catch (err) {
    // Error is handled by the store
  } finally {
    await refresh();
    pluginActionState.value = null;
  }
}

async function activatePlugin(slug: string) {
  successMessage.value = null;
  pluginActionState.value = { slug, type: 'activate' };

  try {
    const response = await onboardingStore.activatePlugin(slug);
    successMessage.value = response?.message || 'Plugin activated successfully.';
  } catch (err) {
    // Error is handled by the store
  } finally {
    await refresh();
    pluginActionState.value = null;
  }
}
</script>

<template>
  <div class="min-h-[calc(100vh-200px)] bg-neutral-50 p-8">
    <div class="mx-auto flex max-w-6xl flex-col gap-10">
      <section class="rounded-2xl border border-neutral-200 bg-white p-8 shadow-sm">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
          <div>
            <p class="text-xs uppercase tracking-[0.25em] text-neutral-400">Onboarding</p>
            <h1 class="mt-3 text-3xl font-semibold text-neutral-900">Get started with Picowind</h1>
            <p class="mt-2 text-sm text-neutral-600">
              Get your site looking great quickly by installing a child theme and some recommended plugins.
            </p>
          </div>
        </div>

        <div v-if="successMessage" class="mt-6 rounded-lg border border-green-300 bg-green-50 px-4 py-3 text-sm text-green-800">
          {{ successMessage }}
        </div>

        <div v-if="error" class="mt-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
          {{ error }}
        </div>

      </section>

      <section class="rounded-2xl border border-neutral-200 bg-white p-8 shadow-sm">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
          <div>
            <h2 class="text-xl font-semibold text-neutral-900">Bundled child themes</h2>
            <p class="text-sm text-neutral-600">
              Install a bundled child theme or switch to one you already have installed.
            </p>
          </div>
          <div class="flex flex-wrap items-center gap-3">
            <span class="text-xs uppercase tracking-[0.3em] text-neutral-400">{{ bundledThemes.length }} bundles</span>
            <div v-if="hasActiveChildTheme" class="rounded-md border border-sky-200 px-4 py-2 text-xs font-semibold text-sky-700">
              Active child theme: {{ activeChildThemeName }}
            </div>
          </div>
        </div>

        <div v-if="pageLoading" class="mt-8 flex items-center justify-center py-12">
          <div class="text-center">
            <div class="inline-block h-12 w-12 animate-spin rounded-md border-b-2 border-neutral-500"></div>
            <p class="mt-3 text-sm text-neutral-500">Loading bundled themes...</p>
          </div>
        </div>

        <div v-else-if="bundledThemes.length === 0 && !error" class="mt-8 rounded-md border border-dashed border-neutral-200 bg-neutral-50 p-8 text-center text-sm text-neutral-600">
          No bundled child themes were found in <span class="font-mono text-neutral-700">@child-theme/</span>.
        </div>

        <div v-else-if="bundledThemes.length > 0" class="mt-8 grid gap-6 md:grid-cols-2">
          <article v-for="theme in bundledThemes" :key="theme.id" class="flex h-full flex-col justify-between rounded-md border border-neutral-200 p-6 shadow-sm transition hover:border-neutral-300" :class="theme.active ? 'bg-neutral-100' : ''">
            <div>
              <div class="flex items-start justify-between gap-4">
                <div>
                  <div class="flex">
                    <h3 class="flex flex-1 text-lg font-semibold text-neutral-900">{{ theme.name }}</h3>

                    <div class="flex flex-wrap items-center justify-end gap-2">
                      <span v-if="theme.active" class="rounded-md border border-green-700 px-2.5 py-1 text-xs font-semibold text-green-700">
                        Active
                      </span>
                      <span v-else-if="theme.installed" class="rounded-md border border-neutral-700 px-2.5 py-1 text-xs font-semibold text-neutral-700">
                        Installed
                      </span>
                      <span v-else class="rounded-md border border-amber-700 px-2.5 py-1 text-xs font-semibold text-amber-700">
                        Not installed
                      </span>
                    </div>
                  </div>
                  <p class="mt-2 text-sm text-neutral-600">
                    {{ theme.description || 'A bundled child theme for Picowind.' }}
                  </p>
                </div>
              </div>

              <div class="mt-4 flex flex-wrap gap-4 text-xs text-neutral-500">
                <span v-if="theme.version">Version {{ theme.version }}</span>
                <span v-if="theme.author">By {{ theme.author }}</span>
              </div>
            </div>

            <div class="mt-6 flex flex-wrap items-center justify-end gap-3">
              <button v-if="!theme.installed" @click="installTheme(theme.id)" :disabled="isBusy" class="rounded-md bg-neutral-100 px-4 py-2 text-xs font-semibold capitalize tracking-wider transition hover:bg-neutral-200 disabled:cursor-not-allowed disabled:bg-neutral-400">
                {{ isAction(theme.id, 'install') ? 'Installing...' : 'Install' }}
              </button>
              <button v-else-if="!theme.active" @click="activateTheme(theme.installedSlug || '', theme.id)" :disabled="!theme.installedSlug || isBusy" class="rounded-md px-4 py-2 text-xs font-semibold capitalize tracking-wider transition text-blue-800 hover:text-blue-100  bg-blue-50 hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-blue-300">
                {{ isAction(theme.id, 'activate') ? 'Switching...' : 'Activate' }}
              </button>
            </div>
          </article>
        </div>
      </section>

      <section class="rounded-2xl border border-neutral-200 bg-white p-8 shadow-sm">
        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
          <div>
            <h2 class="text-xl font-semibold text-neutral-900">Recommended plugins</h2>
            <p class="text-sm text-neutral-600">These plugins pair perfectly with Picowind.</p>
          </div>
        </div>

        <div v-if="pageLoading" class="mt-8 flex items-center justify-center py-12">
          <div class="text-center">
            <div class="inline-block h-12 w-12 animate-spin rounded-md border-b-2 border-neutral-500"></div>
            <p class="mt-3 text-sm text-neutral-500">Loading recommended plugins...</p>
          </div>
        </div>

        <div v-else class="mt-6 grid gap-6 md:grid-cols-3">
          <article v-for="plugin in recommendedPlugins" :key="plugin.id" class="flex h-full flex-col justify-between rounded-md border border-neutral-200 p-6 shadow-sm transition hover:border-neutral-300" :class="plugin.active ? 'bg-neutral-100' : ''">
            <div>
              <div class="flex items-start justify-between gap-4">
                <div>
                  <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-2">
                      <h3 class="text-lg font-semibold text-neutral-900">{{ plugin.name }}</h3>
                      <a v-if="plugin.url" :href="plugin.url" target="_blank" rel="noopener noreferrer" :aria-label="`Open ${plugin.name} website`" :title="`Open ${plugin.name} website`" class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-neutral-200 text-neutral-500 transition hover:border-neutral-300 hover:text-neutral-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-neutral-400">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-4 w-4" aria-hidden="true">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5h6m0 0v6m0-6L10.5 13.5" />
                          <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6H6.75A2.25 2.25 0 0 0 4.5 8.25v9A2.25 2.25 0 0 0 6.75 19.5h9A2.25 2.25 0 0 0 18 17.25V13.5" />
                        </svg>
                      </a>
                    </div>
                    <div class="flex flex-wrap items-center justify-end gap-2">
                      <span v-if="plugin.active" class="rounded-md border border-green-700 px-2.5 py-1 text-xs font-semibold text-green-700">
                        Active
                      </span>
                      <span v-else-if="plugin.installed" class="rounded-md border border-neutral-700 px-2.5 py-1 text-xs font-semibold text-neutral-700">
                        Installed
                      </span>
                      <span v-else-if="plugin.source === 'wporg'" class="rounded-md border border-amber-700 px-2.5 py-1 text-xs font-semibold text-amber-700">
                        Not installed
                      </span>
                      <span v-else class="rounded-md border border-violet-700 px-2.5 py-1 text-xs font-semibold text-violet-700">
                        Premium
                      </span>
                    </div>
                  </div>
                  <p class="mt-2 text-sm text-neutral-600">{{ plugin.description }}</p>
                </div>
              </div>
            </div>

            <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
              <p class="text-xs text-neutral-500">
                <!-- {{ plugin.source === 'wporg' ? 'WordPress.org plugin' : 'Premium plugin' }} -->
              </p>

              <div class="flex flex-wrap gap-2">
                <a v-if="plugin.source === 'external' && plugin.slug !== 'livecanvas'" :href="plugin.url" target="_blank" rel="noopener noreferrer" class="rounded-md bg-neutral-100 px-4 py-2 text-xs font-semibold capitalize tracking-wider text-neutral-900 transition hover:bg-neutral-200">
                  Visit {{ plugin.name }}
                </a>
                <button v-else-if="!plugin.installed" @click="installPlugin(plugin.slug)" :disabled="isBusy" class="rounded-md bg-neutral-100 px-4 py-2 text-xs font-semibold capitalize tracking-wider transition hover:bg-neutral-200 disabled:cursor-not-allowed disabled:bg-neutral-400">
                  {{ isPluginAction(plugin.slug, 'install') ? 'Installing...' : 'Install' }}
                </button>
                <button v-else-if="!plugin.active" @click="activatePlugin(plugin.slug)" :disabled="isBusy" class="rounded-md px-4 py-2 text-xs font-semibold capitalize tracking-wider transition text-blue-800 hover:text-blue-100 bg-blue-50 hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-blue-300">
                  {{ isPluginAction(plugin.slug, 'activate') ? 'Activating...' : 'Activate' }}
                </button>
                <!-- <button v-else disabled class="rounded-md border border-green-700 px-4 py-2 text-xs font-semibold capitalize tracking-wider text-green-700">
                  Active
                </button> -->
              </div>
            </div>
          </article>
        </div>
      </section>
    </div>
  </div>
</template>
