import { toast } from "sonner";

const csrf: string =
    document.querySelector('meta[name="csrf"]')?.getAttribute("content") || "";

export interface AxiosLikeResponse<T = any> {
  data: T;
  status: number;
  statusText: string;
  headers: Headers;
  config: RequestInit;
  request: Response;
}

type FetchOptions = RequestInit & {
  data?: any; // biar mirip axios (axios pakai "data")
};

async function apiFetch<T = any>(
    url: string,
    options: FetchOptions = {},
): Promise<AxiosLikeResponse<T>> {
    const { data, headers, ...rest } = options;

    const config: RequestInit = {
        credentials: "include",
        headers: {
            "X-CSRF-TOKEN": csrf,
            Accept: "application/json",
            "Content-Type": "application/json",
            ...headers,
        },
        body: data ? JSON.stringify(data) : rest.body,
        ...rest,
    };

    const response = await fetch(url, config);

    // ---- interceptor replacement ----
    if (response.status === 401) {
        toast.warning("You do not have permission for the requested action");
        throw new Error("Unauthenticated");
    }

    if (response.status === 403) {
        toast.warning("You do not have permission for the requested action");
        throw new Error("Forbidden");
    }

    let responseData: any = null;
    const contentType = response.headers.get("content-type");

    if (contentType?.includes("application/json")) {
        responseData = await response.json();
    } else {
        responseData = await response.text();
    }

    // axios: reject kalau status >= 400
    if (!response.ok) {
        return Promise.reject({
            response: {
                status: response.status,
                statusText: response.statusText,
                headers: response.headers,
                config,
                request: response,
            },
        });
    }

    // SUCCESS → return axios shape
    return {
        data: responseData,
        status: response.status,
        statusText: response.statusText,
        headers: response.headers,
        config,
        request: response,
    };
}

function useApi() {
    return {
        get: <T = any>(url: string, options?: FetchOptions) =>
            apiFetch<T>(url, { method: "GET", ...options }),

        post: <T = any>(url: string, data?: any, options?: FetchOptions) =>
            apiFetch<T>(url, { method: "POST", data: data, ...options }),

        put: <T = any>(url: string, data?: any, options?: FetchOptions) =>
            apiFetch<T>(url, { method: "PUT", data: data, ...options }),

        patch: <T = any>(url: string, data?: any, options?: FetchOptions) =>
            apiFetch<T>(url, { method: "PATCH", data: data, ...options }),

        delete: <T = any>(url: string, options?: FetchOptions) =>
            apiFetch<T>(url, { method: "DELETE", ...options }),
    };
}

export { useApi };
