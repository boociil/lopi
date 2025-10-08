import axios, { AxiosInstance } from 'axios';
import { toast } from 'sonner';

const csrf: string = document.querySelector('meta[name="csrf"]')?.getAttribute('content') || '';

/**
 * Configure an axios instance that is wired up to the first-party Sanctum backend authentication.
 * This is preferable to using the default Axios instance as we can load it up with the headers
 * necessary to authenticate with Sanctum. For more: https://laravel.com/docs/12.x/sanctum
 */
const axiosClient = axios.create({
    withCredentials: true,
    withXSRFToken: true,
    headers: {
        'X-XSRF-TOKEN': csrf,
    },
});

/**
 * Some simple default handling for the common auth errors
 * If we are not logged in (401), simply redirect to the
 * login page. If unauthorized (403), toast a warning.
 */
axiosClient.interceptors.response.use(
    (response) => {
        return response;
    },
    (error) => {
        if (error.response.status === 401) {
            window.location.href = '/login';
        }
        if (error.response.status === 403) {
            toast.warning('You do not have permission for the requested action');
        }
        return Promise.reject(error);
    },
);

function useApi(): AxiosInstance {
    return axiosClient;
}

export { useApi };
