import React from "react";
import { Input } from "@/components/ui/input";
import { Checkbox } from "@/components/ui/checkbox";
import { Textarea } from "@/components/ui/textarea";
import { useEffect, useState } from "react";
import { SpaceIcon } from "lucide-react";
import { renderHTML } from "@/utils/renderHtml";

const SchoolFormDrawer = ({
    groups,
    data,
    setData,
    webData,
}: {
    groups: any[];
    data: any;
    setData: any;
    webData: any;
}) => {
    const [formattedData, setFormattedData] = useState("");

    function trimFirstAndLastChar(inputString: string): string {
        // Check if the string has at least two characters to trim
        if (inputString.length < 2) {
            return ""; // Return an empty string if it's too short to trim
        }
        // Use slice to remove the first character (index 0) and the last character
        // The first argument (1) starts the slice from the second character.
        // The second argument (-1) indicates to stop before the last character.
        return inputString.slice(1, -1);
    }

    useEffect(() => {
        // Convert the object to a nicely formatted JSON string
        const formatted = JSON.stringify(webData, null, 2);
        setFormattedData(formatted);
    }, []);

    const handleChanges = (
        setting_group: string,
        setting: string,
        checked: boolean | string
    ) => {
        const selected = data[setting_group] ?? [];
        console.log("selected ", selected);
        setData(
            setting_group,
            checked
                ? [...selected, setting]
                : selected.filter((f: any) => f !== setting)
        );

        console.log("changes => ", data);
    };

    const formatTextWithNewlines = (text: string): React.ReactNode => {
        return text.split("\n\n").map((paragraph, index, array) => (
            <React.Fragment key={index}>
                {paragraph}
                {index < array.length - 1 && (
                    <>
                        <br />
                        <br />
                    </>
                )}
            </React.Fragment>
        ));
    };

    const TextWithNewlines: React.FC<{ text: string }> = ({ text }) => {
        return <div>{formatTextWithNewlines(text)}</div>;
    };
    const handleTextChange = (
        settingGroup: string,
        setting: string,
        v: string,
        settingLength: number,
        index: number
    ) => {
        const selected = data[settingGroup] ?? [];

        setData((prev: any) => {
            if (prev.length === 0) {
                prev = Array(settingLength);
            }
            const updated = { ...prev };
            if (!updated[settingGroup]) {
                updated[settingGroup] = Array(settingLength).fill("");
            }
            updated[settingGroup][index] = v;

            return updated;
        });
    };

    return (
        <div className="px-4">
            {groups.map((g: any, idx: number) => (
                <div key={idx} className="py-2">
                    <div className="py-2">
                        <span className="font-bold italic">
                            {g.setting_group}
                        </span>
                    </div>
                    <div
                        className={`grid gap-2
                                    ${
                                        g.setting_type === "number" ||
                                        g.setting_type === "text"
                                            ? "grid-cols-2"
                                            : g.setting_type === "checkbox"
                                            ? "grid-cols-3"
                                            : "grid-cols-1"
                                    }`}
                    >
                        {g.settings.map((s: any, i: number) => (
                            <div className="" key={s.setting_id}>
                                <div className="pb-4">
                                    {g.setting_type === "text" ||
                                    g.setting_type === "number" ? (
                                        <div className="flex flex-row gap-2">
                                            <label className="pb-2 w-[200px]">
                                                {s.setting}
                                            </label>
                                            <Input
                                                className="w-[200px]"
                                                type={g.setting_type}
                                                value={
                                                    data[g.setting_group_short]
                                                        ? data[
                                                              g
                                                                  .setting_group_short
                                                          ][i]
                                                        : ""
                                                }
                                                onChange={(e) =>
                                                    handleTextChange(
                                                        g.setting_group_short,
                                                        s.setting,
                                                        e.target.value,
                                                        g.settings.length,
                                                        i
                                                    )
                                                }
                                            />
                                        </div>
                                    ) : g.setting_type === "checkbox" ? (
                                        <div className="flex flex-row items-center gap-2">
                                            <Checkbox
                                                checked={
                                                    data[
                                                        g.setting_group_short
                                                    ]?.includes(s.setting) ||
                                                    false
                                                }
                                                onCheckedChange={(checked) =>
                                                    handleChanges(
                                                        g.setting_group_short,
                                                        s.setting,
                                                        checked
                                                    )
                                                }
                                            />
                                            <label className="pl-2">
                                                {s.setting}
                                            </label>
                                            {
                                                <div>
                                                    {s.setting === "Other" && (
                                                        <Input
                                                            type="text"
                                                            disabled={
                                                                data[
                                                                    g
                                                                        .setting_group_short
                                                                ]
                                                                    ? data[
                                                                          g
                                                                              .setting_group_short
                                                                      ].includes(
                                                                          "Other"
                                                                      )
                                                                        ? false
                                                                        : true
                                                                    : true
                                                            }
                                                            value={
                                                                data[
                                                                    g
                                                                        .setting_group_short
                                                                ]
                                                                    ? data[
                                                                          g
                                                                              .setting_group_short
                                                                      ][i]
                                                                    : ""
                                                            }
                                                            onChange={(e) => {
                                                                handleTextChange(
                                                                    g.setting_group_short,
                                                                    s.setting,
                                                                    e.target
                                                                        .value,
                                                                    g.settings
                                                                        .length,
                                                                    i
                                                                );
                                                                // handleChanges(
                                                                //     g.setting_group_short,
                                                                //     s.setting,
                                                                //     e.target
                                                                //         .value
                                                                //         .length >
                                                                //         0
                                                                //         ? true
                                                                //         : false
                                                                // );
                                                            }}
                                                        />
                                                    )}
                                                </div>
                                            }
                                        </div>
                                    ) : (
                                        <div className="col-span-3">
                                            <div className="flex flex-row gap-2">
                                                <Textarea
                                                    className="w-full"
                                                    value={
                                                        data[
                                                            g
                                                                .setting_group_short
                                                        ]
                                                    }
                                                    onChange={(e) =>
                                                        handleTextChange(
                                                            g.setting_group_short,
                                                            s.setting,
                                                            e.target.value,
                                                            g.settings.length,
                                                            i
                                                        )
                                                    }
                                                />
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </div>
                        ))}
                        {/* {g.has_other === "y" ? (
                            <div className="flex flex-row gap-2 items-center">
                                <label>Other:</label>
                                <Input
                                    type="text"
                                    value={data[g.setting_group_short].includes('Other')}
                                    onChange={}
                                />
                            </div>
                        ) : (
                            ""
                        )} */}
                    </div>

                    <div className="pt-4">
                        <hr />
                    </div>
                </div>
            ))}
            <div className="py-2">
                {webData && (
                    <div className="flex flex-col gap-2">
                        <span className="font-bold italic">
                            Data from Web Sites
                        </span>
                        <div>
                            <TextWithNewlines text={webData} />;
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
};

export default SchoolFormDrawer;
