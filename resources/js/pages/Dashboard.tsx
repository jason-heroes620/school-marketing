import AuthenticatedLayout from "@/layouts/authenticatedLayout";
import { Head } from "@inertiajs/react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

export default function Dashboard({ count }: { count: [] }) {
    console.log(count);
    return (
        <AuthenticatedLayout>
            <Head title="Dashboard" />

            <div className="py-12">
                <div className="mx-auto px-4">
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div className="py-4 px-4">
                            <div>
                                <span className="font-semibold">
                                    No. of Schools Nearby Your Location
                                </span>
                            </div>
                            <div className="grid grid-cols-1 gap-4 overflow-x-auto p-4 text-gray-900 md:grid-cols-4 lg:grid-cols-5 dark:text-gray-100">
                                {count ? (
                                    count.map((c) => (
                                        <Card
                                            key={c.school_result_status}
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
                                                <div className="flex items-center justify-end">
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
