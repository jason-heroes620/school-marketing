import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from "@/components/ui/collapsible";
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
import { Link, usePage } from "@inertiajs/react";
import {
    ChevronRight,
    FileChartLine,
    LayoutDashboard,
    School,
} from "lucide-react";
import { useEffect, useState } from "react";

type SubItem = {
    key: string;
    label: string;
    href: string;
};

type GroupItem = {
    key: string;
    label: string;
    icon?: React.ComponentType<{ className?: string }>;
    href: string;
    subItems: SubItem[];
};

type ItemGroup = {
    group: string;
    groupItems: GroupItem[];
};

const items = [
    {
        group: "General",
        groupItems: [
            {
                key: "my_school",
                label: "My School",
                icon: School,
                href: "/my-school",
                subItems: [
                    // {
                    //     key: "nearby",
                    //     label: "Nearby Search ",
                    //     href: "/nearby",
                    // },
                    {
                        key: "search",
                        label: "Search ",
                        href: "/search-page",
                    },
                ],
            },
            {
                key: "reports",
                label: "Reports",
                icon: FileChartLine,
                href: "/reports",
                subItems: [
                    {
                        key: "reports",
                        label: "My Reports",
                        href: "/my-reports",
                    },
                ],
            },
        ],
    },
];
export function AppSidebar() {
    const { state, isMobile } = useSidebar();
    const { url, props } = usePage();
    const [expandedItems, setExpandedItems] = useState<Record<string, boolean>>(
        {}
    );

    useEffect(() => {
        const initialExpanded: Record<string, boolean> = {};

        items.forEach((group) => {
            group.groupItems.forEach((item) => {
                const isActive =
                    url === item.href ||
                    url.startsWith(item.href + "/") ||
                    item.subItems.some(
                        (subItem) =>
                            url === subItem.href ||
                            (subItem.href.includes("/:") &&
                                url.startsWith(subItem.href.split("/:")[0]))
                    );
                if (isActive) {
                    initialExpanded[item.key] = true;
                }
            });
        });

        setExpandedItems(initialExpanded);
    }, [url]);

    const toggleExpand = (key: string) => {
        setExpandedItems((prev) => ({
            ...prev,
            [key]: !prev[key],
        }));
    };

    const isActive = (href: string) => {
        return (
            url === href ||
            (href !== "/" && url.startsWith(href + "/")) ||
            (href.endsWith("/") && url.startsWith(href.slice(0, -1)))
        );
    };

    const isSubItemActive = (subItem: SubItem) => {
        // if (subItem.href.includes("/:")) {
        if (subItem.href.includes("/:")) {
            const basePath = subItem.href.split("/:")[0];
            console.log(basePath);
            return url.startsWith(basePath + "/") || url === basePath;
        }
        return isActive(subItem.href);
    };

    return (
        <Sidebar side="left" collapsible="icon" variant="sidebar">
            <SidebarContent className="pt-4 md:pt-10">
                <SidebarGroup>
                    <SidebarGroupContent>
                        <SidebarMenu>
                            <SidebarMenuItem key={"dashboard"}>
                                <SidebarMenuButton
                                    asChild
                                    tooltip={"Dashboard"}
                                    isActive={url === "/dashboard"}
                                >
                                    <Link href={"/dashboard"}>
                                        <LayoutDashboard />
                                        Dashboard
                                    </Link>
                                </SidebarMenuButton>
                            </SidebarMenuItem>
                        </SidebarMenu>
                    </SidebarGroupContent>
                </SidebarGroup>
                {items.map((item) => {
                    return (
                        <SidebarGroup key={item.group}>
                            <SidebarGroupLabel>{item.group}</SidebarGroupLabel>
                            {item.groupItems.map((groupItem) => {
                                return (
                                    <SidebarMenu key={groupItem.key}>
                                        {groupItem.subItems.length > 0 ? (
                                            <Collapsible
                                                className="group/collapsible"
                                                defaultOpen={
                                                    groupItem.subItems &&
                                                    // groupItem.subItems.some(
                                                    //     (g: any) =>
                                                    //         g.href === url,
                                                    // )
                                                    //     ? true
                                                    //     : false
                                                    groupItem.subItems.some(
                                                        (g: SubItem) =>
                                                            isActive(g.href)
                                                    )
                                                        ? true
                                                        : false
                                                }
                                            >
                                                <CollapsibleTrigger asChild>
                                                    <SidebarMenuItem>
                                                        <SidebarMenuButton
                                                            tooltip={
                                                                groupItem.label
                                                            }
                                                            // isActive={
                                                            //     groupItem.subItems.some(
                                                            //         (g) =>
                                                            //             g.href ===
                                                            //             url,
                                                            //     )
                                                            //         ? true
                                                            //         : false
                                                            // }
                                                            isActive={
                                                                groupItem.subItems.some(
                                                                    (g) =>
                                                                        isActive(
                                                                            g.href
                                                                        )
                                                                )
                                                                    ? true
                                                                    : false
                                                            }
                                                        >
                                                            <groupItem.icon />
                                                            {groupItem.label}
                                                            <ChevronRight className="ml-auto transition-transform group-data-[state=open]/collapsible:rotate-90" />
                                                        </SidebarMenuButton>
                                                    </SidebarMenuItem>
                                                </CollapsibleTrigger>

                                                <CollapsibleContent>
                                                    <SidebarMenuSub>
                                                        {groupItem.subItems.map(
                                                            (subItem) => {
                                                                return (
                                                                    subItem.label !==
                                                                        "" && (
                                                                        <SidebarMenuSubItem
                                                                            key={
                                                                                subItem.key
                                                                            }
                                                                        >
                                                                            <SidebarMenuSubButton
                                                                                asChild
                                                                                // isActive={
                                                                                //     url ===
                                                                                //     subItem.href
                                                                                // }
                                                                                isActive={isSubItemActive(
                                                                                    subItem
                                                                                )}
                                                                            >
                                                                                <Link
                                                                                    href={
                                                                                        subItem.href
                                                                                    }
                                                                                >
                                                                                    <span>
                                                                                        {
                                                                                            subItem.label
                                                                                        }
                                                                                    </span>
                                                                                </Link>
                                                                            </SidebarMenuSubButton>
                                                                        </SidebarMenuSubItem>
                                                                    )
                                                                );
                                                            }
                                                        )}
                                                    </SidebarMenuSub>
                                                </CollapsibleContent>
                                            </Collapsible>
                                        ) : (
                                            <SidebarMenuItem>
                                                <SidebarMenuButton
                                                    asChild
                                                    tooltip={groupItem.label}
                                                    isActive={
                                                        url === groupItem.href
                                                    }
                                                >
                                                    <Link href={groupItem.href}>
                                                        <groupItem.icon />
                                                        {groupItem.label}
                                                    </Link>
                                                </SidebarMenuButton>
                                            </SidebarMenuItem>
                                        )}
                                    </SidebarMenu>
                                );
                            })}
                        </SidebarGroup>
                    );
                })}
            </SidebarContent>
            <SidebarFooter>
                {state === "expanded" && (
                    <div className="flex justify-center">
                        <span className="text-[10px]">
                            &copy; HEROES Malaysia
                        </span>
                    </div>
                )}
            </SidebarFooter>
        </Sidebar>
    );
}
