import axios from 'redaxios';

import type { DashboardAction, DashboardData } from '../types';

let apiInstance: ReturnType<typeof axios.create>;

/**
 * Returns an Axios (redaxios) instance for making API requests. The instance is created only once.
 */
export function useApi(config = {}): ReturnType<typeof axios.create> {
    if (!apiInstance) {
        apiInstance = axios.create(Object.assign({
            baseURL: window.picowind.rest_api.url || '',
            headers: {
                'content-type': 'application/json',
                'accept': 'application/json',
                'X-WP-Nonce': window.picowind.rest_api.nonce || '',
            },
        }, config));
    }
    return apiInstance;
}

interface ApiPayload<T> {
    success: boolean;
    data: T;
    message?: string;
}

export async function fetchDashboard(): Promise<DashboardData> {
    const response = await useApi().get('/onboarding/dashboard');
    return (response.data as ApiPayload<DashboardData>).data;
}

export async function runDashboardAction(action: DashboardAction): Promise<string> {
    let response;

    switch (action.type) {
        case 'install-theme':
            response = await useApi().post('/onboarding/install-theme', { themeId: action.id });
            break;
        case 'activate-theme':
            response = await useApi().post('/onboarding/activate-theme', { themeSlug: action.slug });
            break;
        case 'install-plugin':
            response = await useApi().post('/onboarding/install-plugin', { slug: action.slug });
            break;
        case 'activate-plugin':
            response = await useApi().post('/onboarding/activate-plugin', { slug: action.slug });
            break;
        case 'complete':
            response = await useApi().post('/onboarding/complete');
            break;
    }

    const payload = response.data as ApiPayload<unknown>;
    return payload.message || 'Dashboard updated successfully.';
}

export function getApiErrorMessage(error: unknown): string {
    if (typeof error === 'object' && error !== null) {
        const candidate = error as {
            message?: string;
            response?: { data?: { message?: string } };
        };

        return candidate.response?.data?.message || candidate.message || 'Something went wrong.';
    }

    return 'Something went wrong.';
}
