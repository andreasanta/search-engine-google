<?php
/**
 * @license see LICENSE
 */

namespace Serps\SearchEngine\Google;

abstract class NaturalResultType
{

    const CLASSICAL = 'classical';
    const CLASSICAL_LARGE = 'classical_large';
    const CLASSICAL_VIDEO = 'classical_video';

    const IMAGE_GROUP = 'imageGroup';
    const IMAGE_GROUP_IMAGE = 'imageGroupImage';

    const IN_THE_NEWS = 'inTheNews';
    const TWEETS_CAROUSEL= 'tweetsCarousel';

    const MAP = 'map';
    const MAP_PLACE = 'map_place';
}
