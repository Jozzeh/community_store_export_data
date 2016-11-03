<?php
namespace Concrete\Package\CommunityStoreExportData;

use Package;
use Page;
use SinglePage;

class Controller extends Package
{
    protected $pkgHandle = 'community_store_export_data';
    protected $appVersionRequired = '5.7.5';
    protected $pkgVersion = '0.2';

    public function getPackageDescription()
    {
        return t("Adds Community Store product and sales export capabilities to the dashboard");
    }

    public function getPackageName()
    {
        return t("Community Store Export Data");
    }

    public function install(){
      $pkg = parent::install();
      SinglePage::add('/dashboard/store/export', $pkg);
      $salesPage = SinglePage::add('/dashboard/store/export/sales', $pkg);
      $productsPage = SinglePage::add('/dashboard/store/export/products', $pkg);

      $salesPage->update(array(
        'cName' => 'Sales' // this updates the name
      ));
      $productsPage->update(array(
        'cName' => 'Products' // this updates the name
      ));
    }
}
?>
