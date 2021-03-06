<?php
/**
 * Encapsulates the type of coordinates used by the region provider.
 */
class CoordinatesType {
    /**
     * The coordinates should be used "as is" on the screenshot image.
     * Regardless of the current context.
     */
    const SCREENSHOT_AS_IS = "SCREENSHOT_AS_IS";

    /**
     * The coordinates should be used "as is" within the current context. For
     * example, if we're inside a frame, the coordinates are "as is",
     * but within the current frame's viewport.
     */
    const CONTEXT_AS_IS = "CONTEXT_AS_IS";

    /**
     * Coordinates are relative to the context. For example, if we are in
     * a context of a frame in a web page, then the coordinates are relative to
     * the  frame. In this case, if we want to crop an image region based on
     * an element's region, we will need to calculate their respective "as
     * is" coordinates.
     */
    const CONTEXT_RELATIVE = "CONTEXT_RELATIVE";
}
