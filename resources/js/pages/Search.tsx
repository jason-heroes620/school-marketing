import React, { useState, useCallback, useEffect, FormEvent } from "react";
import { GoogleMap, Marker, useLoadScript } from "@react-google-maps/api";
import axios from "axios";
import AuthenticatedLayout from "@/layouts/authenticatedLayout";
import { Head, useForm } from "@inertiajs/react";
import { RadioGroup, RadioGroupItem } from "@/components/ui/radio-group";
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
    SheetFooter,
} from "@/components/ui/sheet";
import SchoolFormDrawer from "@/components/SchoolFormDrawer";
import { Button } from "@/components/ui/button";
import { ScrollArea } from "@/components/ui/scroll-area";
import { toast } from "sonner";
import Loading from "@/components/loading";

const containerStyle = {
    width: "80%",
    height: "600px",
};

const libraries: "places"[] = ["places"];

export default function Search({
    groups,
    longitude,
    latitude,
}: {
    groups: [];
    longitude: string;
    latitude: string;
}) {
    const { isLoaded, loadError } = useLoadScript({
        googleMapsApiKey: import.meta.env.VITE_GOOGLE_MAPS_KEY,
        libraries: libraries,
    });
    const center = {
        lat: parseFloat(latitude), // Replace with actual
        lng: parseFloat(longitude),
    };

    const { data, setData, processing, errors } = useForm({});

    const [radius, setRadius] = useState<number>(1000);
    const [places, setPlaces] = useState<any[]>([]);
    const [nextPageToken, setNextPageToken] = useState<string | null>(null);
    const [hoveredPlaceId, setHoveredPlaceId] = useState<string | null>(null);
    const [loading, setLoading] = useState(false);

    const [openSheet, setOpenSheet] = useState(false);
    const [school, setSelectSchool] = useState({
        name: "",
        place_id: "",
    });
    const [webData, setWebData] = useState([]);

    const fetchPlaces = useCallback(
        async (reset = false, pagetoken?: string) => {
            const params = {
                latitude: center.lat,
                longitude: center.lng,
                radius,
                ...(pagetoken && { next_page_token: pagetoken }),
            };
            setLoading(true);
            const res = await axios.get(route("results.axiosGoogleRequest"), {
                params,
            });

            if (res.data.results) {
                console.log("results =>", res.data.results);
                setPlaces((prev) =>
                    reset ? res.data.results : [...prev, ...res.data.results]
                );
                setNextPageToken(res.data.next_page_token || null);
                setLoading(false);
            } else {
                console.log("waiting");
            }
        },
        [radius]
    );

    // Load first time or on radius change
    useEffect(() => {
        fetchPlaces(true); // reset=true
    }, [radius]);

    const handleHover = (placeId: string | null) => {
        setHoveredPlaceId(placeId);
    };

    const handleRadiusChange = (value: string) => {
        setRadius(parseInt(value));
    };

    const handleOpenSheet = async (placeId: string) => {
        try {
            const res = await axios.get(
                route("results.getSchoolResultById", placeId)
            );
            if (res.data) {
                const d = res.data;
                // console.log("d =>", d.web);
                setData(d.existingData);
                setWebData(d.web);
                setOpenSheet(true);
            }
        } catch (e) {
            console.error("Failed to retrieve data");
        }
    };

    const handleUpateResult = async (e: FormEvent) => {
        e.preventDefault();
        const r = {
            data: data,
            school: school,
        };
        console.log("data =>", data);
        await axios.patch("/update", r).then((resp) => {
            if (resp.status === 200) {
                toast.success("Information Updated.");
            } else {
                toast.error(
                    "There was an error saving the data! Please contact administrator for assistance."
                );
            }
        });
    };

    const handleSetComplete = async (e: FormEvent) => {
        e.preventDefault();
        console.log("complete " + school.place_id);
        await axios.patch("/setComplete/" + school.place_id).then((resp) => {
            if (resp.status === 200) {
                console.log(resp.data);
                toast.success("School Updated to Complete.");
            } else {
                toast.error(
                    "There was an error saving the data! Please contact administrator for assistance."
                );
            }
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Search" />
            <div className="px-4">
                <div className="py-8 flex flex-row">
                    <label>Radius</label>
                    <div className="pl-4">
                        <RadioGroup
                            defaultValue={radius.toString()}
                            className="flex flex-row"
                            onValueChange={(v) => handleRadiusChange(v)}
                        >
                            <div className="flex items-center gap-3">
                                <RadioGroupItem value="1000" id="r1" />
                                <label htmlFor="r1">1 KM</label>
                            </div>
                            <div className="flex items-center gap-3">
                                <RadioGroupItem value="2000" id="r2" />
                                <label htmlFor="r2">2 KM</label>
                            </div>
                            <div className="flex items-center gap-3">
                                <RadioGroupItem value="5000" id="r3" />
                                <label htmlFor="r3">5 KM</label>
                            </div>
                        </RadioGroup>
                    </div>
                </div>
                <div>
                    {isLoaded ? (
                        <div className="flex gap-4">
                            <div className="w-1/3 h-[600px] overflow-auto border-r">
                                <h2 className="text-lg font-bold px-4 pt-2">
                                    Nearby Schools
                                </h2>
                                <ul className="divide-y">
                                    {loading ? <Loading /> : ""}
                                    {places.map((place, idx) => (
                                        <li
                                            key={place.place_id}
                                            className={`p-2 cursor-pointer hover:bg-yellow-100 ${
                                                hoveredPlaceId ===
                                                place.place_id
                                                    ? "bg-yellow-200"
                                                    : ""
                                            }`}
                                            onMouseEnter={() =>
                                                handleHover(place.place_id)
                                            }
                                            onMouseLeave={() =>
                                                handleHover(null)
                                            }
                                        >
                                            <p className="font-medium text-md">
                                                {idx + 1}. {place.name}
                                            </p>
                                            <p>
                                                Rating:{" "}
                                                <span className="italic font-semibold">
                                                    {place.rating}
                                                </span>
                                            </p>
                                            <div className="flex justify-between py-2 items-center">
                                                <span
                                                    className={`text-sm text-white font-semibold italic py-1 px-2 rounded-md ${
                                                        place.status === "D"
                                                            ? "bg-orange-500"
                                                            : place.status ===
                                                              "C"
                                                            ? "bg-green-600"
                                                            : ""
                                                    }`}
                                                >
                                                    {place.status === "D"
                                                        ? "Draft"
                                                        : place.status === "C"
                                                        ? "Completed"
                                                        : ""}
                                                </span>
                                                <Button
                                                    size={"sm"}
                                                    onClick={() => {
                                                        handleOpenSheet(
                                                            place.place_id
                                                        );
                                                        setSelectSchool({
                                                            name: place.name,
                                                            place_id:
                                                                place.place_id,
                                                        });
                                                    }}
                                                >
                                                    Update
                                                </Button>
                                            </div>
                                        </li>
                                    ))}
                                </ul>
                                {nextPageToken && (
                                    <button
                                        onClick={() =>
                                            fetchPlaces(false, nextPageToken)
                                        }
                                        className="rounded block w-full mt-2 py-2 bg-blue-500 text-white hover:bg-blue-600"
                                    >
                                        {loading ? "Loading ..." : "Load More"}
                                    </button>
                                )}
                            </div>
                            <GoogleMap
                                mapContainerStyle={containerStyle}
                                center={center}
                                zoom={14}
                            >
                                <Marker
                                    position={{
                                        lat: center.lat,
                                        lng: center.lng,
                                    }}
                                    title="Preset Location (Kuala Lumpur)"
                                    icon={{
                                        url: "http://maps.google.com/mapfiles/ms/icons/blue-dot.png", // A different icon for clarity
                                        scaledSize: new window.google.maps.Size(
                                            40,
                                            40
                                        ),
                                    }}
                                />
                                {places.map((place) => (
                                    <Marker
                                        key={place.place_id}
                                        position={place.geometry.location}
                                        label={place.name[0]}
                                        icon={
                                            hoveredPlaceId === place.place_id
                                                ? {
                                                      url: "http://maps.google.com/mapfiles/ms/icons/yellow-dot.png",
                                                  }
                                                : undefined
                                        }
                                    />
                                ))}
                            </GoogleMap>
                        </div>
                    ) : (
                        <p>Loading map...</p>
                    )}
                </div>
                <div>
                    <Sheet open={openSheet} onOpenChange={setOpenSheet}>
                        <SheetContent className="w-full sm:max-w-4xl">
                            <SheetTitle></SheetTitle>
                            <SheetDescription></SheetDescription>
                            <ScrollArea className="h-[85%]">
                                <SchoolFormDrawer
                                    groups={groups}
                                    data={data}
                                    setData={setData}
                                    webData={webData}
                                />
                            </ScrollArea>

                            <SheetFooter>
                                <div className="flex justify-between">
                                    <Button
                                        type="submit"
                                        onClick={(e) => handleUpateResult(e)}
                                        size={"sm"}
                                    >
                                        Save Draft
                                    </Button>
                                    <Button
                                        variant={"primary"}
                                        size={"sm"}
                                        onClick={(e) => handleSetComplete(e)}
                                    >
                                        Set As Complete
                                    </Button>
                                </div>
                            </SheetFooter>
                        </SheetContent>
                    </Sheet>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
