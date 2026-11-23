export const BUKU_NOTIF_REFRESH_EVENT = "buku-notif-refresh";

/**
 * Minta sidebar memuat ulang jumlah badge Buku tanpa reload halaman.
 *
 * @param delta Perubahan angka yang langsung ditampilkan (optimistic),
 *              mis. +1 saat mengajukan dan -1 saat membatalkan.
 */
export function refreshBukuNotif(delta = 0) {
    if (typeof window !== "undefined") {
        window.dispatchEvent(
            new CustomEvent(BUKU_NOTIF_REFRESH_EVENT, { detail: { delta } }),
        );
    }
}
