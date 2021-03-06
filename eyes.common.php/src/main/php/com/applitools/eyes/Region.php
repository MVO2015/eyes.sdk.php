<?php

/**
 * Represents a region.
 */
class Region
{
    private $left;
    private $top;
    private $width;
    private $height;
    public static $empty;

    public static function getEmpty()
    {
        self::$empty = new Region(0, 0, 0, 0);
        return self::$empty;
    }

    protected function makeEmpty()
    {
        $this->left = self::$empty->getLeft();
        $this->top = self::$empty->getTop();
        $this->width = self::$empty->getWidth();
        $this->height = self::$empty->getHeight();
    }

    public function __construct($left = null, $top = null, $width = null, $height = null,
                                Location $location = null, RectangleSize $size = null,
                                Region $other = null) //FIXME 3 construct's merged
    {
        if($left !== null && $top !== null && $width !== null && $height !== null){
            ArgumentGuard::greaterThanOrEqualToZero($width, "width");
            ArgumentGuard::greaterThanOrEqualToZero($height, "height");
            //$this->empty = new Region(0, 0, 0, 0); //FIXME

            $this->left = $left;
            $this->top = $top;
            $this->width = $width;
            $this->height = $height;
        }
        else if($location !== null && $size !== null){
            ArgumentGuard::notNull($location, "location");
            ArgumentGuard::notNull($size, "size");

            $this->left = $location->getX();
            $this->top = $location->getY();
            $this->width = $size->getWidth();
            $this->height = $size->getHeight();
        }
        else if ($other ==! null){
            ArgumentGuard::notNull($other, "other");

            $this->left = $other->getLeft();
            $this->top = $other->getTop();
            $this->width = $other->getWidth();
            $this->height = $other->getHeight();
        }
    }

    /**
     *
     * @return true if the region is empty, false otherwise.
     */
    public function isEmpty()
    {
        return $this->left == self::getEmpty()->left
        && $this->top == self::getEmpty()->top
        && $this->width == self::getEmpty()->width
        && $this->height == self::getEmpty()->height;
    }

    public function equals($obj)
    {
        if ($obj == null) {
            return false;
        }

        if (!($obj instanceof Region)) {
            return false;
        }
        $other = clone $obj; // clone????

        return ($this->getLeft() == $other->getLeft())
        && ($this->getTop() == $other->getTop())
        && ($this->getWidth() == $other->getWidth())
        && ($this->getHeight() == $other->getHeight());
    }

    public function hashCode()
    {
        return ($this->left . $this->top . $this->width + $this->height);
    }

    /**
     *
     * @return The (top,left) position of the current region.
     */
    public function getLocation()
    {
        return new Location($this->left, $this->top);
    }

    /**
     * Offsets the region's location (in place).
     *
     * @param dx The X axis offset.
     * @param dy The Y axis offset.
     */
    public function offset($dx, $dy)
    {
        $this->left += $dx;
        $this->top += $dy;
    }

    /**
     *
     * @return The (top,left) position of the current region.
     */
    public function getSize()
    {
        return new RectangleSize($this->width, $this->height);
    }

    /**
     * Set the (top,left) position of the current region
     * @param location The (top,left) position to set.
     */
    public function setLocation(Location $location)
    {
        ArgumentGuard::notNull(location, "location");
        $this->left = $location->getX();
        $this->top = $location->getY();
    }

    /**
     *
     * @param containerRegion The region to divide into sub-regions.
     * @param subRegionSize The maximum size of each sub-region.
     * @return The sub-regions composing the current region. If subRegionSize
     * is equal or greater than the current region,  only a single region is
     * returned.
     */
    private static function getSubRegionsWithFixedSize(
        Region $containerRegion, RectangleSize $subRegionSize)
    {
        ArgumentGuard::notNull($containerRegion, "containerRegion");
        ArgumentGuard::notNull($subRegionSize, "subRegionSize");

        $subRegions = array();

        $subRegionWidth = $subRegionSize->getWidth();
        $subRegionHeight = $subRegionSize->getHeight();

        ArgumentGuard::greaterThanZero($subRegionWidth, "subRegionSize width");
        ArgumentGuard::greaterThanZero($subRegionHeight, "subRegionSize height");

        // Normalizing.
        if ($subRegionWidth > $containerRegion->width) {
            $subRegionWidth = $containerRegion->width;
        }
        if ($subRegionHeight > $containerRegion->height) {
            $subRegionHeight = $containerRegion->height;
        }

        // If the requested size is greater or equal to the entire region size,
        // we return a copy of the region.
        if ($subRegionWidth == $containerRegion->width &&
            $subRegionHeight == $containerRegion->height
        ) {
            $subRegions->add(new Region($containerRegion));
            return $subRegions;
        }

        $currentTop = $containerRegion->top;
        $bottom = $containerRegion->top + $containerRegion->height - 1;
        $right = $containerRegion->left + $containerRegion->width - 1;

        while ($currentTop <= $bottom) {

            if ($currentTop + $subRegionHeight > $bottom) {
                $currentTop = ($bottom - $subRegionHeight) + 1;
            }

            $currentLeft = $containerRegion->left;
            while ($currentLeft <= $right) {
                if ($currentLeft + $subRegionWidth > $right) {
                    $currentLeft = ($right - $subRegionWidth) + 1;
                }

                $subRegions-> add(new Region($currentLeft, $currentTop,
                    $subRegionWidth, $subRegionHeight));

                $currentLeft += $subRegionWidth;
            }
            $currentTop += $subRegionHeight;
        }
        return $subRegions;
    }

    /**
     * @param containerRegion The region to divide into sub-regions.
     * @param maxSubRegionSize The maximum size of each sub-region (some
     *                         regions might be smaller).
     * @return The sub-regions composing the current region. If
     * maxSubRegionSize is equal or greater than the current region,
     * only a single region is returned.
     */
    private static function getSubRegionsWithVaryingSize(Region $containerRegion, RectangleSize $maxSubRegionSize)
    {
        ArgumentGuard::notNull($containerRegion, "containerRegion");
        ArgumentGuard::notNull($maxSubRegionSize, "maxSubRegionSize");
        ArgumentGuard::greaterThanZero($maxSubRegionSize->getWidth(),
            "maxSubRegionSize.getWidth()");
        ArgumentGuard::greaterThanZero($maxSubRegionSize->getHeight(),
            "maxSubRegionSize.getHeight()");

        /*List<Region>*/
        $subRegions = array();
        $subRegions[] = new /*LinkedList<*/Region();


        $currentTop = $containerRegion->top;
        $bottom = $containerRegion->top + $containerRegion->height;
        $right = $containerRegion->left + $containerRegion->width;

        while ($currentTop < $bottom) {

            $currentBottom = $currentTop + $maxSubRegionSize->getHeight();
            if ($currentBottom > $bottom) {
                $currentBottom = $bottom;
            }
            $currentLeft = $containerRegion->left;
            while ($currentLeft < $right) {
                $currentRight = $currentLeft + $maxSubRegionSize->getWidth();
                if ($currentRight > $right) {
                    $currentRight = $right;
                }

                $currentHeight = $currentBottom - $currentTop;
                $currentWidth = $currentRight - $currentLeft;

                $subRegions[] = new Region($currentLeft, $currentTop,
                    $currentWidth, $currentHeight);

                $currentLeft += $maxSubRegionSize->getWidth();
            }
            $currentTop += $maxSubRegionSize->getHeight();
        }
        return $subRegions;
    }

    /**
     * Returns a list of sub-regions which compose the current region.
     * @param subRegionSize The default sub-region size to use.
     * @param isFixedSize If {@code false}, then sub-regions might have a
     *                      size which is smaller then {@code subRegionSize}
     *                      (thus there will be no overlap of regions).
     *                      Otherwise, all sub-regions will have the same
     *                      size, but sub-regions might overlap.
     * @return The sub-regions composing the current region. If {@code
     * subRegionSize} is equal or greater than the current region,
     * only a single region is returned.
     */
    public function getSubRegions(RectangleSize $subRegionSize, $isFixedSize = false)
    {
        if ($isFixedSize) {
            return getSubRegionsWithFixedSize($this, $subRegionSize);
        }

        return $this->getSubRegionsWithVaryingSize($this, $subRegionSize);
    }

    /**
     * See {@link #getSubRegions(RectangleSize, boolean)}.
     * {@code isFixedSize} defaults to {@code false}.
     */
    /* public function getSubRegions(RectangleSize $subRegionSize) {
         return $this->getSubRegions($subRegionSize, false);
     }*/

    /**
     * Check if a region is contained within the current region.
     * @param other The region to check if it is contained within the current
     *              region.
     * @return True if {@code other} is contained within the current region,
     *          false otherwise.
     */

    public function contains(Region $other)
    {
        $right = $this->left + $this->width;
        $otherRight = $other->getLeft() + $other->getWidth();

        $bottom = $this->top + $this->height;
        $otherBottom = $other->getTop() + $other->getHeight();

        return $this->top <= $other->getTop() && $this->left <= $other->getLeft()
        && $bottom >= $otherBottom && $right >= $otherRight;
    }

    /**
     * Check if a specified location is contained within this region.
     * <p>
     * @param location The location to test.
     * @return True if the location is contained within this region,
     *          false otherwise.
     */
    /*public function contains(Location $location) {               //FIXME
        return $location->getX() >= $this->left
        && $location->getX() <= ($this->left + $this->width)
        && $location->getY() >= $this->top
        && $location->getY() <= ($this->top + $this->height);
    }*/

    /**
     * Check if a region is intersected with the current region.
     * @param other The region to check intersection with.
     * @return True if the regions are intersected, false otherwise.
     */
    public function isIntersected(Region $other)
    {
        $right = $this->left + $this->width;
        $bottom = $this->top + $this->height;

        $otherLeft = $other->getLeft();
        $otherTop = $other->getTop();
        $otherRight = $otherLeft + $other->getWidth();
        $otherBottom = $otherTop + $other->getHeight();

        return ((($this->left <= $otherLeft && $otherLeft <= $right)
                || ($otherLeft <= $this->left && $this->left <= $otherRight))
            && (($this->top <= $otherTop && $otherTop <= $bottom)
                || ($otherTop <= $this->top && $this->top <= $otherBottom)));
    }

    /**
     * Replaces this region with the intersection of itself and
     * {@code other}
     * @param other The region with which to intersect.
     */
    public function intersect(Region $other)
    {

        // If there's no intersection set this as the Empty region.
        if (!$this->isIntersected($other)) {
            $this->makeEmpty();
            return;
        }
        // The regions intersect. So let's first find the left & top values
        $otherLeft = $other->getLeft();
        $otherTop = $other->getTop();

        $intersectionLeft = ($this->left >= $otherLeft) ? $this->left : $otherLeft;
        $intersectionTop = ($this->top >= $otherTop) ? $this->top : $otherTop;

        // Now the width and height of the intersect
        $right = $this->left + $this->width;
        $otherRight = $otherLeft + $other->getWidth();
        $intersectionRight = ($right <= $otherRight) ? $right : $otherRight;
        $intersectionWidth = $intersectionRight - $intersectionLeft;
        $bottom = $this->top + $this->height;
        $otherBottom = $otherTop + $other->getHeight();
        $intersectionBottom = ($bottom <= $otherBottom) ? $bottom : $otherBottom;
        $intersectionHeight = $intersectionBottom - $intersectionTop;

        $this->left = $intersectionLeft;
        $this->top = $intersectionTop;
        $this->width = $intersectionWidth;
        $this->height = $intersectionHeight;
    }


    public function getLeft()
    {
        return $this->left;
    }

    public function getTop()
    {
        return $this->top;
    }

    public function getWidth()
    {
        return $this->width;
    }

    public function getHeight()
    {
        return $this->height;
    }

    public function getMiddleOffset()
    {
        $middleX = $this->width / 2;
        $middleY = $this->height / 2;

        return new Location($middleX, $middleY);
    }

    public function toString()
    {
        return "(" . $this->left . ", " . $this->top . ") " . $this->width . "x" . $this->height;
    }
}
