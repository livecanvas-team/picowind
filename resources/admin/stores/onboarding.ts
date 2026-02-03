import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { useApi } from '../library/api';

export interface BundledTheme {
  id: string;
  name: string;
  description: string;
  version: string;
  author: string;
  themeUri?: string;
  template?: string;
  textDomain?: string;
  tags: string[];
  installed: boolean;
  active: boolean;
  installedSlug: string | null;
}

export interface RecommendedPlugin {
  id: string;
  name: string;
  slug: string;
  source: 'wporg' | 'external';
  url: string;
  description: string;
  installed: boolean;
  active: boolean;
}

export interface OnboardingStatus {
  completed: boolean;
  hasChildTheme: boolean;
  childTheme: {
    name: string;
    slug: string;
    version: string;
    parent: string;
  } | null;
}

export const useOnboardingStore = defineStore('onboarding', () => {
  const api = useApi();

  const loading = ref(false);
  const installing = ref(false);
  const activating = ref(false);
  const status = ref<OnboardingStatus | null>(null);
  const bundledThemes = ref<BundledTheme[]>([]);
  const recommendedPlugins = ref<RecommendedPlugin[]>([]);
  const error = ref<string | null>(null);

  const isCompleted = computed(() => status.value?.completed || false);
  const hasChildTheme = computed(() => status.value?.hasChildTheme || false);
  const activeTheme = computed(() => bundledThemes.value.find(theme => theme.active) || null);

  async function fetchStatus() {
    try {
      loading.value = true;
      error.value = null;

      const response = await api.get('/onboarding/status');
      status.value = response.data.data;

      return status.value;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to fetch onboarding status';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchThemes() {
    try {
      loading.value = true;
      error.value = null;

      const response = await api.get('/onboarding/themes');
      bundledThemes.value = Array.isArray(response.data.data) ? response.data.data : [];

      return bundledThemes.value;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to fetch bundled themes';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchPlugins() {
    try {
      loading.value = true;
      error.value = null;

      const response = await api.get('/onboarding/plugins');
      recommendedPlugins.value = Array.isArray(response.data.data) ? response.data.data : [];

      return recommendedPlugins.value;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to fetch recommended plugins';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function installTheme(themeId: string) {
    try {
      installing.value = true;
      error.value = null;

      const response = await api.post('/onboarding/install-theme', {
        themeId,
      });

      return response.data;
    } catch (err: any) {
      error.value = err.response?.data?.message || err.message || 'Failed to install theme';
      throw err;
    } finally {
      installing.value = false;
    }
  }

  async function activateTheme(themeSlug: string) {
    try {
      activating.value = true;
      error.value = null;

      const response = await api.post('/onboarding/activate-theme', {
        themeSlug,
      });

      return response.data;
    } catch (err: any) {
      error.value = err.response?.data?.message || err.message || 'Failed to activate theme';
      throw err;
    } finally {
      activating.value = false;
    }
  }

  async function installPlugin(slug: string) {
    try {
      installing.value = true;
      error.value = null;

      const response = await api.post('/onboarding/install-plugin', {
        slug,
      });

      return response.data;
    } catch (err: any) {
      error.value = err.response?.data?.message || err.message || 'Failed to install plugin';
      throw err;
    } finally {
      installing.value = false;
    }
  }

  async function activatePlugin(slug: string) {
    try {
      activating.value = true;
      error.value = null;

      const response = await api.post('/onboarding/activate-plugin', {
        slug,
      });

      return response.data;
    } catch (err: any) {
      error.value = err.response?.data?.message || err.message || 'Failed to activate plugin';
      throw err;
    } finally {
      activating.value = false;
    }
  }

  async function complete() {
    try {
      error.value = null;

      const response = await api.post('/onboarding/complete');

      if (response.data.success) {
        if (status.value) {
          status.value.completed = true;
        }
      }

      return response.data;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to complete onboarding';
      throw err;
    }
  }

  async function reset() {
    try {
      error.value = null;

      const response = await api.post('/onboarding/reset');

      if (response.data.success) {
        status.value = null;
        await Promise.all([fetchStatus(), fetchThemes(), fetchPlugins()]);
      }

      return response.data;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to reset onboarding';
      throw err;
    }
  }

  function clearError() {
    error.value = null;
  }

  return {
    loading,
    installing,
    activating,
    status,
    bundledThemes,
    recommendedPlugins,
    error,
    isCompleted,
    hasChildTheme,
    activeTheme,
    fetchStatus,
    fetchThemes,
    fetchPlugins,
    installTheme,
    activateTheme,
    installPlugin,
    activatePlugin,
    complete,
    reset,
    clearError,
  };
});
