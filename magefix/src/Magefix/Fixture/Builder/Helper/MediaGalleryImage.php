<?php

namespace Magefix\Fixture\Builder\Helper;

use Magefix\Exceptions\ProductMediaGalleryImageNotFound;
use Magefix\Fixture\Builder\Helper;

/**
 * Class MediaGalleryImage
 * @package Magefix\Fixture\Builder\Helper
 * @author Carlo Tasca <ctasca.d3@gmail.com>
 */
class MediaGalleryImage implements Helper
{
    /**
     * @param \Mage_Core_Model_Abstract $product
     * @param array $gallery
     * @param bool $save
     * @throws ProductMediaGalleryImageNotFound
     * @throws \Exception
     */
    public static function save(\Mage_Core_Model_Abstract $product, array $gallery, $save = true)
    {
        if (!@file_exists($gallery['image'])) {
            throw new ProductMediaGalleryImageNotFound(
                'Specified product fixture gallery image does not exists -> ' . $gallery['image']
            );
        }

        $product->addImageToMediaGallery(
            $gallery['image'], ['image', 'thumbnail', 'small_image'], false, false
        );

        if ($save) {
            $product->save();
        }
    }
}
