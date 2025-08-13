import AuthenticatedLayout from "@/layouts/authenticatedLayout";
import { Head } from "@inertiajs/react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

interface Count {
    count: number;
    school_result_status: string;
    radius: number;
}

// Define the props for the component
interface TotalCounterProps {
    items: Count[];
}

export default function Dashboard({ count }: { count: Count[] }) {
    console.log(count);

    // Use the reduce method to calculate the total count
    const totalCount = count.reduce((accumulator, currentItem) => {
        // Add the 'count' of the current item to the accumulator
        return accumulator + currentItem.count;
    }, 0);

    return (
        <AuthenticatedLayout>
            <Head title="Dashboard" />

            <div className="py-12">
                <div className="mx-auto px-4">
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div className="py-4 px-4">
                            <div className="flex flex-col">
                                <span className="text-lg font-semibold">
                                    No. of Schools Nearby Your Location
                                </span>
                                <div className="py-4">
                                    <span className="font-semibold text-lg">
                                        Total ({totalCount})
                                    </span>
                                </div>
                            </div>
                            <div className="grid grid-cols-1 gap-4 overflow-x-auto p-4 text-gray-900 md:grid-cols-4 lg:grid-cols-5 dark:text-gray-100">
                                {count ? (
                                    count.map((c, index) => (
                                        <Card
                                            key={index}
                                            className={`bg-gradient-to-r text-white opacity-80
                                            ${
                                                c.school_result_status === "D"
                                                    ? "from-orange-500 to-orange-700"
                                                    : "from-green-500 to-green-700"
                                            }`}
                                        >
                                            <CardHeader>
                                                <CardTitle>
                                                    {c.school_result_status ===
                                                    "D"
                                                        ? "Draft"
                                                        : "Complete"}
                                                </CardTitle>
                                            </CardHeader>
                                            <CardContent>
                                                <div className="flex items-center justify-between">
                                                    <span className="text-xl font-bold">
                                                        {c.radius / 1000} KM
                                                        Radius
                                                    </span>
                                                    <span className="text-xl font-bold">
                                                        {c.count}
                                                    </span>
                                                </div>
                                            </CardContent>
                                        </Card>
                                    ))
                                ) : (
                                    <div className="py-4">
                                        <span className="italic">
                                            No school records yet.
                                        </span>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
