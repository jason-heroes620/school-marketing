import AuthenticatedLayout from "@/layouts/authenticatedLayout";
import React from "react";
import { Head, usePage } from "@inertiajs/react";
import {
    Pie,
    PieChart,
    ResponsiveContainer,
    Sector,
    SectorProps,
    Legend,
    Cell,
    LineChart,
    Line,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
} from "recharts";

type PageProps = {
    auth: any;
    types: []; // Adjust the type as needed
};

type TooltipPayload = ReadonlyArray<any>;

type Coordinate = {
    x: number;
    y: number;
};

type PieSectorData = {
    percent?: number;
    name?: string | number;
    midAngle?: number;
    middleRadius?: number;
    tooltipPosition?: Coordinate;
    value?: number;
    paddingAngle?: number;
    dataKey?: string;
    payload?: any;
    tooltipPayload?: ReadonlyArray<TooltipPayload>;
};

type GeometrySector = {
    cx: number;
    cy: number;
    innerRadius: number;
    outerRadius: number;
    startAngle: number;
    endAngle: number;
};

type PieLabelProps = PieSectorData &
    GeometrySector & {
        tooltipPayload?: any;
    };

const Reports = () => {
    // Replace 'PageProps' with your actual props type if different
    const { auth, types, fees } = usePage<PageProps>().props;

    const COLORS = ["#6050DC", "#D52DB7", "#FF2E7E", "#FF6B45", "FFAB05"];

    const RADIAN = Math.PI / 180;

    const renderCustomizedLabel = (props: PieLabelProps) => {
        const {
            cx = 0,
            cy = 0,
            midAngle = 0,
            innerRadius = 0,
            outerRadius = 0,
            percent = 0,
            index = 0,
        } = props;

        const radius = innerRadius + (outerRadius - innerRadius) * 0.5;
        const x = cx + radius * Math.cos(-midAngle * RADIAN);
        const y = cy + radius * Math.sin(-midAngle * RADIAN);

        return (
            <text
                x={x}
                y={y}
                fill="white"
                textAnchor={x > cx ? "start" : "end"}
                dominantBaseline="central"
            >
                {`${(percent * 100).toFixed(0)}%`}
            </text>
        );
    };

    const renderLegend = (props) => {
        const { payload } = props;

        return (
            <ul>
                {payload.map((entry, index) => (
                    <li
                        key={`item-${index}`}
                        className={`flex flex-row bg-[${COLORS[index]}]`}
                    >
                        <div className={`bg-[${COLORS[index]}] px-2 py gap-2`}>
                            {entry.payload.name[0]}
                        </div>
                        <span className="pl-2">{entry.payload.name}</span>
                    </li>
                ))}
            </ul>
        );
    };

    const [hoveringDataKey, setHoveringDataKey] = React.useState(null);

    let pvOpacity = 1;
    let uvOpacity = 1;

    if (hoveringDataKey === "uv") {
        pvOpacity = 0.5;
    }

    if (hoveringDataKey === "pv") {
        uvOpacity = 0.5;
    }

    const handleMouseEnter = (payload: any) => {
        setHoveringDataKey(payload.dataKey);
    };

    const handleMouseLeave = () => {
        setHoveringDataKey(null);
    };

    const radiusMap = {
        1000: "r1",
        2000: "r2",
        5000: "r5",
    };

    const result = fees.reduce((acc, item) => {
        const key = item.setting;
        const valueKey = radiusMap[item.radius];

        if (!acc[key]) {
            // Initialize new entry with all possible radius properties set to 0
            acc[key] = {
                setting: key,
                r1: 0,
                r2: 0,
                r5: 0,
            };
        }

        // Set the appropriate value
        acc[key][valueKey] = item.value;

        return acc;
    }, {});

    const finalResult = Object.values(result);

    return (
        <AuthenticatedLayout>
            <Head title="Reports" />
            <div className="mx-auto max-w-7xl px-4 md:px-8 py-4">
                <div className="py-4">
                    <span className="text-lg font-bold">Reports</span>
                </div>
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div className="border px-2 py-4">
                        <div>
                            <span>Type Of School</span>
                        </div>
                        <div>
                            <ResponsiveContainer width={"100%"} height={400}>
                                <PieChart>
                                    <Pie
                                        data={types}
                                        dataKey="value"
                                        nameKey="name"
                                        cx="50%"
                                        cy="50%"
                                        outerRadius={100}
                                        fill="#4f46e5"
                                        labelLine={false}
                                        label={renderCustomizedLabel}
                                    >
                                        {types.map((entry, index) => (
                                            <Cell
                                                key={`cell-${index}`}
                                                fill={
                                                    COLORS[
                                                        index % COLORS.length
                                                    ]
                                                }
                                            />
                                        ))}
                                    </Pie>

                                    <Legend className="flex flex-col" />
                                </PieChart>
                            </ResponsiveContainer>
                        </div>
                    </div>
                    <div className="border px-2 py-4 col-span-2">
                        <div className="py-2 px-2">
                            <span>Average Fees</span>
                        </div>
                        <ResponsiveContainer width={"100%"} height={400}>
                            <LineChart
                                width={600}
                                height={300}
                                data={finalResult}
                                margin={{
                                    top: 5,
                                    right: 30,
                                    left: 20,
                                    bottom: 5,
                                }}
                            >
                                <CartesianGrid strokeDasharray="3 3" />
                                <XAxis dataKey="setting" />
                                <YAxis />
                                <Tooltip />
                                <Legend
                                    onMouseEnter={handleMouseEnter}
                                    onMouseLeave={handleMouseLeave}
                                />
                                <Line
                                    type="monotone"
                                    dataKey="r1"
                                    strokeOpacity={pvOpacity}
                                    stroke="#8884d8"
                                    activeDot={{ r: 8 }}
                                />
                                <Line
                                    type="monotone"
                                    dataKey="r2"
                                    strokeOpacity={pvOpacity}
                                    stroke="#012345"
                                    activeDot={{ r: 8 }}
                                />
                                <Line
                                    type="monotone"
                                    dataKey="r5"
                                    strokeOpacity={pvOpacity}
                                    stroke="#ABCDEF"
                                    activeDot={{ r: 8 }}
                                />
                            </LineChart>
                        </ResponsiveContainer>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
};

export default Reports;
