<?php
namespace Concrete\Package\CommunityStoreExportData\Controller\SinglePage\Dashboard\Store\Export;

use Core;
use \Concrete\Core\Page\Controller\DashboardPageController;
use Database;
use \Concrete\Package\CommunityStore\Src\CommunityStore\Product\Product as StoreProduct;
use \Concrete\Package\CommunityStore\Src\CommunityStore\Product\ProductFile as StoreProductFile;
use \Concrete\Package\CommunityStore\Src\CommunityStore\Product\ProductGroup as StoreProductGroup;
use \Concrete\Package\CommunityStore\Src\CommunityStore\Product\ProductImage as StoreProductImage;
use \Concrete\Package\CommunityStore\Src\CommunityStore\Product\ProductList as StoreProductList;
use \Concrete\Package\CommunityStore\Src\CommunityStore\Product\ProductLocation as StoreProductLocation;
use \Concrete\Package\CommunityStore\Src\CommunityStore\Product\ProductUserGroup as StoreProductUserGroup;
use \Concrete\Package\CommunityStore\Src\CommunityStore\Product\ProductVariation\ProductVariation as StoreProductVariation;
use \Concrete\Package\CommunityStore\Src\CommunityStore\Product\ProductRelated as StoreProductRelated;
use \Concrete\Package\CommunityStore\Src\CommunityStore\Product\ProductOption\ProductOption as StoreProductOption;
use \Concrete\Package\CommunityStore\Src\CommunityStore\Group\Group as StoreGroup;
use \Concrete\Package\CommunityStore\Src\CommunityStore\Group\GroupList as StoreGroupList;
use \Concrete\Package\CommunityStore\Src\Attribute\Key\StoreProductKey;
use \Concrete\Package\CommunityStore\Src\CommunityStore\Tax\TaxClass as StoreTaxClass;
use \Concrete\Package\CommunityStore\Src\CommunityStore\Utilities\Price as Price;

class Products extends DashboardPageController{
  public function view(){
    $this->set('pageTitle', t('Product export'));

    $db = Database::connection();
    $previousQueries = $db->fetchAll('select * from CommunityStoreExportData where type="products" order by exportdate desc limit 0,50');
    $this->set('previousQueries', $previousQueries);
  }

  public function exportProducts(){
    $postValues = $this->request();

    $products = new StoreProductList();
    $products->setActiveOnly(false);
    $products->setShowOutOfStock(true);
    $paginator = $products->getPagination();
    $pagination = $paginator->renderDefaultView();
    $products = $paginator->getCurrentPageResults();

    $productsArray = $this->generateProducts($products);

    if(isset($postValues['excel'])){
      $this->generateExcel($postValues, $productsArray);
    }else if(isset($postValues['csv'])){
      $this->generateCsv($postValues, $productsArray);
    }
    exit;
  }

  public function generateProducts($products){
    $taxClasses = array();
    foreach(StoreTaxClass::getTaxClasses() as $taxClass){
        $taxClasses[$taxClass->getID()] = $taxClass->getTaxClassName();
    }

    $productArr = array();
    if(!empty($products)){
      foreach($products as $product){
        $prodArr = array();
        $prodArr['name'] = $product->getName();
        $prodArr['SKU'] = $product->getSKU();
        $prodArr['active'] = $product->isActive();
        $prodArr['featured'] = $product->isFeatured();
        $prodArr['price'] = str_replace(array('€', '$'), '', Price::format($product->getPrice()));
        $prodArr['saleprice'] = str_replace(array('€', '$'), '', Price::format($product->getSalePrice()));
        $prodArr['taxable'] = $product->isTaxable();
        $prodArr['taxclass'] = $taxClasses[$product->getTaxClassID()];
        $prodArr['stocklvl'] = $product->getQty();
        $prodArr['stocklimit'] = $product->isUnlimited();
        $prodArr['backorders'] = $product->allowBackOrders();
        $prodArr['shippable'] = $product->isShippable();
        //options
        $options  = $product->getOptions();
        $productOptions = "";
        if($options){
          $optionNames = "";
          foreach($options as $option){
            $optionItems = $option->getOptionItems();
            if(!empty($optionItems)){
              foreach($optionItems as $optionItem){
                $optionNames = $optionNames.' '.$optionItem->getName();
              }
              $productOptions = $productOptions.' '.$option->getName().' ['.$optionNames.'] '.' -- ';
            }
          }
        }
        $prodArr['options'] = $productOptions;

        //variations
        $variations = $product->getVariations();
        $prodArr['hasvariations'] = $product->hasVariations();
        if($product->hasVariations()){
          $productVariations = "";
          if(!empty($variations)){
            foreach($variations as $variation){
              $variationQty = $variation->getVariationQty().' in stock';
              if($variation->isUnlimited()){
                $variationQty = "Unlimited";
              }
              $productVariations = $productVariations.' '.$variation->getVariationSKU().' : '.$variationQty." -- ";
            }
          }
          $prodArr['variations'] = $productVariations;
        }else{
          $prodArr['variations'] = "";
        }

        //add to general product array
        $productArr[] = $prodArr;
      }
    }

    return $productArr;
  }

  public function generateExcel($data, $products){
    $iData = array();
    $iData[] = 'products';
    $iData[] = 'excel';
    $iData[] = json_encode($data);
    $db = Database::connection();
    $db->Execute('insert into CommunityStoreExportData (type, exporttype, exportdate, exportvariables) values (?,?,NOW(),?)', $iData);

    //dirty echo in controller
    header("Content-Type:   application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=export-products.xls");
    echo '<table>';
      echo '<tr>';
        echo '<th>Name</th>';
        echo '<th>SKU</th>';
        echo '<th>Active</th>';
        echo '<th>Featured</th>';
        echo '<th>Price</th>';
        echo '<th>Sale Price</th>';
        echo '<th>Taxable</th>';
        echo '<th>Tax Class</th>';
        echo '<th>Stock Level</th>';
        echo '<th>Unlimited stock</th>';
        echo '<th>Allow Back orders</th>';
        echo '<th>Shippable</th>';
        echo '<th>Options</th>';
        echo '<th>Variations</th>';
      echo '</tr>';
      if(!empty($products)){
        foreach($products as $product){
          echo '<tr>';
            echo '<td>'.$product['name'].'</td>';
            echo '<td>'.$product['SKU'].'</td>';
            echo '<td>'.$product['active'].'</td>';
            echo '<td>'.$product['featured'].'</td>';
            echo '<td>'.$product['price'].'</td>';
            echo '<td>'.$product['saleprice'].'</td>';
            echo '<td>'.$product['taxable'].'</td>';
            echo '<td>'.$product['taxclass'].'</td>';
            echo '<td>'.$product['stocklvl'].'</td>';
            echo '<td>'.$product['stocklimit'].'</td>';
            echo '<td>'.$product['backorders'].'</td>';
            echo '<td>'.$product['shippable'].'</td>';
            echo '<td>'.str_replace(' -- ', '<br/>', $product['options']).'</td>';
            echo '<td>'.str_replace(' -- ', '<br/>',$product['variations']).'</td>';
          echo '</tr>';
        }
      }
    echo '</table>';
  }

  public function generateCsv($data, $products){
    $iData = array();
    $iData[] = 'products';
    $iData[] = 'csv';
    $iData[] = json_encode($data);
    $db = Database::connection();
    $db->Execute('insert into CommunityStoreExportData (type, exporttype, exportdate, exportvariables) values (?,?,NOW(),?)', $iData);

    header( 'Content-Type: text/csv' );
    header('Content-Disposition: attachment;filename=export-products.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, array('Name','SKU', 'Active', 'Featured', 'Price', 'Sale Price', 'Taxable', 'Tax Class', 'Stock Level', 'Unlimited Stock', 'Back Orders allowed', 'Shippable', 'Options', 'Variations'));
    if(!empty($products)){
      foreach($products as $product){
        fputcsv($out, array($product['name'], $product['SKU'], $product['active'], $product['featured'], $product['price'], $product['saleprice'],
      $product['taxable'], $product['taxclass'], $product['stocklvl'], $product['stocklimit'], $product['backorders'], $product['shippable'], $product['options'], $product['variations']));
      }
    }
    fclose($out);
  }
}
?>
