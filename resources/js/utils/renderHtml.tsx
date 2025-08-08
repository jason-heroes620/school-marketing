import React from "react";

export const renderHTML = (rawHTML: string) => {
    const html = rawHTML.replace(/\n\n/g, "<br /><br />");
    return React.createElement("div", {
        dangerouslySetInnerHTML: { __html: html },
    });
};
