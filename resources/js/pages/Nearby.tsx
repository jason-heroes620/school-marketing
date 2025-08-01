import React, { useEffect, useRef, useState } from "react";
import { Head, Link } from "@inertiajs/react";
import {
    GoogleMap,
    MarkerF,
    useLoadScript,
    InfoWindowF,
} from "@react-google-maps/api";
import AuthenticatedLayout from "@/layouts/authenticatedLayout";
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupContent,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
    useSidebar,
} from "@/components/ui/sidebar";

// --- (Re-use existing TypeScript Interfaces from previous response) ---

// Type for a single education center returned from the API
interface EducationCenter {
    name: string;
    address: string;
    latitude: number;
    longitude: number;
    place_id: string;
    rating?: number | null; // Optional, can be null
    user_ratings_total?: number | null; // Optional, can be null
}

// Type for the preset location
interface PresetLocation {
    lat: number;
    lng: number;
}

// Type for a single link in the Laravel pagination
interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

// Type for the paginated data structure coming from Laravel/Inertia
interface PaginatedData<T> {
    current_page: number;
    data: T[]; // Array of the actual items (EducationCenter in this case)
    first_page_url: string | null;
    from: number | null;
    last_page: number;
    last_page_url: string | null;
    links: PaginationLink[];
    next_page_url: string | null;
    path: string;
    per_page: number;
    prev_page_url: string | null;
    to: number | null;
    total: number;
}

// Type for the props received by the EducationCenters component
interface EducationCentersProps {
    educationCenters: PaginatedData<EducationCenter>; // The paginated collection of centers
    presetLocation: PresetLocation;
    error?: string; // Optional error message
    currentPageToken?: string | null;
    nextPageToken?: string | null;
}

// -------------------------------------------------------------------------
// React Component
// -------------------------------------------------------------------------

const containerStyle: React.CSSProperties = {
    width: "82%",
    height: "600px",
};

const libraries: "places"[] = ["places"];

export default function Nearby({
    educationCenters,
    presetLocation,
    error,
    currentPageToken,
    nextPageToken,
}: EducationCentersProps) {
    console.log("education centers =>", educationCenters);
    const { isLoaded, loadError } = useLoadScript({
        googleMapsApiKey: import.meta.env.VITE_GOOGLE_MAPS_KEY,
        libraries: libraries,
    });

    const mapRef = useRef<google.maps.Map | null>(null);
    const [selectedPlace, setSelectedPlace] = useState<EducationCenter | null>(
        null
    );

    // --- NEW STATE for managing local markers ---
    // We'll initialize this with the markers passed from Laravel
    // and then allow adding more.
    const [currentMarkers, setCurrentMarkers] = useState<EducationCenter[]>(
        educationCenters.data
    );

    // Update currentMarkers whenever educationCenters.data changes from props (e.g., pagination)
    useEffect(() => {
        setCurrentMarkers(educationCenters.data);
    }, [educationCenters.data]);

    const onMapLoad = React.useCallback((map: google.maps.Map) => {
        mapRef.current = map;
    }, []);

    const onUnmount = React.useCallback(() => {
        mapRef.current = null;
    }, []);

    if (loadError) return <div>Error loading maps</div>;
    if (!isLoaded) return <div>Loading Maps...</div>;

    // --- NEW: Function to add a dummy marker ---
    const handleAddDummyMarker = () => {
        const newMarker: EducationCenter = {
            name: "New Dummy School",
            address: "123 Test St, Kuala Lumpur",
            latitude: presetLocation.lat + (Math.random() * 0.02 - 0.01), // Slightly offset from preset
            longitude: presetLocation.lng + (Math.random() * 0.02 - 0.01), // Slightly offset
            place_id: `dummy_school_${Date.now()}`, // Unique ID
            rating: 4.5,
        };
        setCurrentMarkers((prevMarkers) => [...prevMarkers, newMarker]);
        // Optionally, pan to the new marker
        if (mapRef.current) {
            mapRef.current.panTo({
                lat: newMarker.latitude,
                lng: newMarker.longitude,
            });
        }
    };

    return (
        <AuthenticatedLayout>
            <Head title="Education Centers" />
            <div className="container mx-auto p-4">
                <h1 className="text-2xl font-bold mb-4">
                    Education Centers within 5km of Preset Location
                </h1>

                {error && (
                    <div
                        className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4"
                        role="alert"
                    >
                        {error}
                    </div>
                )}

                {/* --- NEW: Button to add dummy marker --- */}
                <button
                    onClick={handleAddDummyMarker}
                    className="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded mb-4"
                >
                    Add New Dummy Marker
                </button>

                <GoogleMap
                    mapContainerStyle={containerStyle}
                    center={presetLocation}
                    zoom={13}
                    onLoad={onMapLoad}
                    onUnmount={onUnmount}
                >
                    <MarkerF
                        position={presetLocation}
                        title="Preset Location (Kuala Lumpur)"
                        icon={{
                            url: "http://maps.google.com/mapfiles/ms/icons/blue-dot.png", // A different icon for clarity
                            scaledSize: new window.google.maps.Size(30, 30),
                        }}
                    />

                    {/* Iterate over currentMarkers state */}
                    {currentMarkers.map((center) => (
                        <MarkerF
                            key={center.place_id} // Crucial for React list rendering performance and stability
                            position={{
                                lat: center.latitude,
                                lng: center.longitude,
                            }}
                            onClick={() => setSelectedPlace(center)}
                        />
                    ))}

                    {selectedPlace && (
                        <InfoWindowF
                            position={{
                                lat: selectedPlace.latitude,
                                lng: selectedPlace.longitude,
                            }}
                            onCloseClick={() => setSelectedPlace(null)}
                        >
                            <div>
                                <h2 className="font-semibold">
                                    {selectedPlace.name}
                                </h2>
                                <p>{selectedPlace.address}</p>
                                {selectedPlace.rating && (
                                    <p>
                                        Rating: {selectedPlace.rating} (
                                        {selectedPlace.user_ratings_total}{" "}
                                        reviews)
                                    </p>
                                )}
                                <a
                                    href={`http://maps.google.com/maps?q=${selectedPlace.latitude},${selectedPlace.longitude}&query_place_id=${selectedPlace.place_id}`}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="text-blue-500 hover:underline"
                                >
                                    View on Google Maps
                                </a>
                            </div>
                        </InfoWindowF>
                    )}
                </GoogleMap>

                <div className="mt-4">
                    <Sidebar side={"right"}>
                        <SidebarContent>
                            <div className="px-4 py-2">
                                <h2 className="text-xl font-semibold mb-2">
                                    Education Centers List:
                                </h2>
                                {currentMarkers.length > 0 ? ( // Displaying from currentMarkers
                                    <ul>
                                        {currentMarkers.map((center) => (
                                            <li
                                                key={center.place_id}
                                                className="border-b py-2"
                                            >
                                                <p className="font-medium text-md">
                                                    {center.name}
                                                </p>
                                                {/* <p className="text-gray-600">
                                                    {center.address}
                                                </p> */}
                                                {center.rating && (
                                                    <p className="text-sm text-gray-500">
                                                        Rating: {center.rating}{" "}
                                                        (
                                                        {
                                                            center.user_ratings_total
                                                        }
                                                        )
                                                    </p>
                                                )}
                                            </li>
                                        ))}
                                    </ul>
                                ) : (
                                    <p>
                                        No education centers found within 5km or
                                        an error occurred.
                                    </p>
                                )}
                            </div>
                        </SidebarContent>
                    </Sidebar>
                </div>

                {/* Pagination Links (from educationCenters.links) */}
                <div className="mt-6 flex justify-center">
                    <nav
                        className="relative z-0 inline-flex rounded-md shadow-sm -space-x-px"
                        aria-label="Pagination"
                    >
                        {educationCenters.links.map(
                            (
                                link,
                                index // Pagination links still use original prop
                            ) =>
                                link.url && (
                                    <Link
                                        key={index}
                                        href={link.url}
                                        className={`relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50
                    ${
                        link.active
                            ? "z-10 bg-indigo-50 border-indigo-500 text-indigo-600"
                            : ""
                    }
                    ${link.url === null ? "pointer-events-none opacity-50" : ""}
                    ${index === 0 ? "rounded-l-md" : ""}
                    ${
                        index === educationCenters.links.length - 1
                            ? "rounded-r-md"
                            : ""
                    }
                  `}
                                        dangerouslySetInnerHTML={{
                                            __html: link.label,
                                        }}
                                        preserveScroll
                                    />
                                )
                        )}
                    </nav>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
