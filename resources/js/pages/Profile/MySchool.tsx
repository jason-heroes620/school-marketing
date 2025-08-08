import SchoolFormDrawer from "@/components/SchoolFormDrawer";
import { Button } from "@/components/ui/button";
import AuthenticatedLayout from "@/layouts/authenticatedLayout";
import { Head, useForm } from "@inertiajs/react";
import axios from "axios";
import { useEffect } from "react";
import { toast } from "sonner";

const MySchool = ({
    groups,
    existingData,
    dynamicFields,
    web,
    id,
}: {
    groups: any[];
    existingData: any;
    dynamicFields: any;
    web: any[];
    id: string;
}) => {
    const { data, setData, processing, errors, post } = useForm();
    console.log(existingData);
    useEffect(() => {
        setData(existingData);
    }, []);

    const handleSave = () => {
        axios
            .post(route("profile.update_my_school"), {
                id: id,
                data: data,
            })
            .then((resp) => {
                if (resp.status === 200) {
                    toast.success("Information updated.");
                } else {
                    toast.error("Error saving information.");
                }
            });
    };
    return (
        <AuthenticatedLayout>
            <Head title={"My School"} />
            <div className="px-4 py-4 md:px-12">
                <div className="py-4">
                    <span className="text-lg font-bold">My School</span>
                </div>
                <div className="border rounded-md">
                    <SchoolFormDrawer
                        groups={groups}
                        data={data}
                        setData={setData}
                        webData={null}
                    />
                </div>
                <div className="py-4 flex justify-end">
                    <form action="">
                        <div className="flex flex-row gap-4">
                            <div>
                                {/* <Button
                                    variant={"primary"}
                                    type={"submit"}
                                    size={"sm"}
                                >
                                    Set as Complete
                                </Button> */}
                            </div>
                            <div>
                                <Button
                                    variant={"default"}
                                    type="button"
                                    size={"sm"}
                                    onClick={() => handleSave()}
                                >
                                    {processing ? "Saving..." : "Save"}
                                </Button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
};

export default MySchool;
