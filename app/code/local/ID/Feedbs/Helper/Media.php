<?php

class ID_Feedbs_Helper_Media extends Mage_Core_Helper_Abstract
{

  public function addMediaGalleryAttributeToProductCollection( &$productCollection )
  {
    $storeId = Mage::app()->getStore()->getId();
    $ids = array();
    foreach ( $productCollection as $product ) {
      $ids[] = $product->getEntityId();
    }

    $resource = Mage::getSingleton( 'core/resource' );
    $conn = Mage::getSingleton( 'core/resource' )->getConnection( 'catalog_read' );
    $select = $conn->select()
      ->from(
        array( 'mg' => $resource->getTableName( 'catalog/product_attribute_media_gallery' ) ),
        array(
          'mg.entity_id', 'mg.attribute_id', 'mg.value_id', 'file' => 'mg.value',
          'mgv.label', 'mgv.position', 'mgv.disabled',
          'label_default' => 'mgdv.label',
          'position_default' => 'mgdv.position',
          'disabled_default' => 'mgdv.disabled'
        )
      )
      ->joinLeft(
        array( 'mgv' => $resource->getTableName( 'catalog/product_attribute_media_gallery_value' ) ),
        '(mg.value_id=mgv.value_id AND mgv.store_id=' . $storeId . ')',
        array()
      )
      ->joinLeft(
        array( 'mgdv' => $resource->getTableName( 'catalog/product_attribute_media_gallery_value' ) ),
        '(mg.value_id=mgdv.value_id AND mgdv.store_id=0)',
        array()
      )
      ->where( 'entity_id IN(?)', $ids );

    $mediaGalleryByProductId = array();

    $stmt = $conn->query( $select );
    while ( $gallery = $stmt->fetch() ) {
      $k = $gallery[ 'entity_id' ];
      unset( $gallery[ 'entity_id' ] );
      if ( !isset($mediaGalleryByProductId[$k]) ) {
        $mediaGalleryByProductId[$k] = array();
      }
      $mediaGalleryByProductId[$k][] = $gallery;
    }
    unset( $stmt ); // finalize statement

    // Updating collection ...
    foreach ( $productCollection as &$product ) {
      $productId = $product->getEntityId();
      if ( isset( $mediaGalleryByProductId[ $productId ] ) ) {
        $product->setData( 'media_gallery', array( 'images' => $mediaGalleryByProductId[ $productId ] ) );
      }
    }
    unset( $mediaGalleryByProductId );
  }

}