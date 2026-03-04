import React, {
    createContext,
    useContext,
    useState,
    useEffect,
    ReactNode,
} from "react";
import { router } from "@inertiajs/react";

interface LoadingContextType {
    isLoading: boolean;
    setIsLoading: (value: boolean) => void;
}

const LoadingContext = createContext<LoadingContextType | undefined>(undefined);

export const LoadingProvider: React.FC<{ children: ReactNode }> = ({
    children,
}) => {
    const [isLoading, setIsLoading] = useState<boolean>(false);

    useEffect(() => {
        const unbindStart = router.on("start", (event) => {
            if (!event.detail.visit.prefetch) {
                setIsLoading(true);
            }
        });

        const unbindFinish = router.on("finish", () => {
            setIsLoading(false);
        });

        const unbindError = router.on("error", () => {
            setIsLoading(false);
        });

        const unbindCancel = router.on("cancel", () => {
            setIsLoading(false);
        });

        return () => {
            unbindStart();
            unbindFinish();
            unbindError();
            unbindCancel();
        };
    }, []);

    return (
        <LoadingContext.Provider
            value={{ isLoading, setIsLoading}}
        >
            {children}
        </LoadingContext.Provider>
    );
};

// 2. Custom Hook dengan proteksi tipe data
export const useLoading = (): LoadingContextType => {
    const context = useContext(LoadingContext);
    if (!context) {
        throw new Error("useLoading harus digunakan di dalam LoadingProvider");
    }
    return context;
};
