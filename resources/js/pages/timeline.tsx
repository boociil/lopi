import AppLayout from "@/layouts/app-layout";
import {
    type BreadcrumbItem,
    Satuan_Kerja_Tahun,
    Tim,
    SharedData,
} from "@/types";
import { Head, usePage } from "@inertiajs/react";

import { useState } from "react";
import { Plus } from "lucide-react";
import _ from "lodash";


import { Button } from "@/components/ui/button";
import { useLoading } from "@/props/LoadingContext";
import { useApi } from "@/lib/API/useApi";

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: "Timeline",
        href: "/lopi/timeline",
    },
];

// Task interface
interface TimeLine {
    id: string; // Unique identifier
    name: string; // Task name
    startDate: Date; // Start date
    endDate: Date; // End date
    color?: string; // Task color (CSS color value like "#3B82F6")
    percent?: number; // Completion percentage (0-100)
    dependencies?: string[]; // IDs of dependent tasks
    [key: string]: any; // Additional custom properties
}

// TaskGroup interface
interface TimeLineGroup {
    id: string; // Unique identifier
    type: "all" | "satuan_kerja_tahun" | "tim" | "user";
    name: string; // Group name
    description?: string; // Group description
    icon?: string; // Optional icon (HTML string)
    tasks: TimeLine[]; // Array of tasks in this group
    [key: string]: any; // Additional custom properties
}

function hashUuid(uuid: string) {
    let hash = 0;
    for (let i = 0; i < uuid.length; i++) {
        hash = uuid.charCodeAt(i) + ((hash << 5) - hash);
    }
    return Math.abs(hash);
}

function uuidToColor(uuid: string) {
    const index = hashUuid(uuid) % ColorOption.length;
    return ColorOption[index] as TColorOption;
}

const ColorOption = ["indigo", "pink", "orange", "emerald", "blue"];

export type TColorOption = "indigo" | "pink" | "orange" | "emerald" | "blue";

// Option 1: Import in your JS/TS file (Recommended)
import "react-modern-gantt/dist/index.css";
import { complexDemoData } from "@/props/data_timeline";
import { GanttChart, Task, TaskGroup, ViewMode } from "react-modern-gantt";


export default function Index({
    data,
    satuan_kerja_tahun,
    tim,
    can_auth,
}: {
    data: TimeLine[];
    satuan_kerja_tahun: Satuan_Kerja_Tahun[];
    tim: Tim[];
    can_auth: string[];
}) {
    const axiosClient = useApi();
    const auth = usePage<SharedData>().props.auth;
    const { setIsLoading } = useLoading();
    can_auth.push(auth.user.username);


     const [tasks, setTasks] = useState<TaskGroup[]>(complexDemoData);

     // Master switch
    const [editMode, setEditMode] = useState(true);

    // Granular controls
    const [allowProgressEdit, setAllowProgressEdit] = useState(true);
    const [allowTaskResize, setAllowTaskResize] = useState(true);
    const [allowTaskMove, setAllowTaskMove] = useState(true);

    const [showProgress, setShowProgress] = useState(true);

    const handleTaskUpdate = (groupId: string, updatedTask: Task) => {
        setTasks(prevTasks =>
        prevTasks.map(group =>
            group.id === groupId
            ? {
                ...group,
                tasks: group.tasks.map(task => (task.id === updatedTask.id ? updatedTask : task)),
                }
            : group
        )
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Timeline" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
                <div className="w-full">
                    {/* <Heading
                        title="Timeline"
                        description="Timeline Sulawesi Barat."
                    /> */}
                    <div className="flex justify-end mt-2 mb-6 gap-x-1.5">
                        <Button
                            className="flex items-center gap-1 bg-primary text-primary-foreground"
                            onClick={() =>
                                console.log("asasasas")
                            }
                        >
                            <Plus />
                            Tambah Peran
                        </Button>
                    </div>
                    <div className="overflow-hidden">
                        <div className="min-h-screen flex-1 flex flex-col text-slate-800 font-sans">
                            {/* Kolom Pilarian */}
                            <div>
                                <GanttChart
                                    title={""}
                                    todayLabel = {"Hari ini"}
                                    headerLabel = {"Tim"}
                                    viewMode = {ViewMode.WEEK}
                                    viewModes = {[ViewMode.DAY, ViewMode.WEEK, ViewMode.MONTH]}
                                    tasks={tasks}
                                    onTaskUpdate={handleTaskUpdate}
                                    editMode={editMode}
                                    showProgress={showProgress}
                                    allowProgressEdit={allowProgressEdit}
                                    allowTaskResize={allowTaskResize}
                                    allowTaskMove={allowTaskMove}
                                    darkMode={false}
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
