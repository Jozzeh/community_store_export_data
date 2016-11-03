<?php

namespace Concrete\Package\CommunityStoreExportData\Controller\SinglePage\Dashboard\Store;

use \Concrete\Core\Page\Controller\DashboardPageController;

class Export extends DashboardPageController
{

    public function view()
    {
        $this->redirect('/dashboard/store/export/sales');
    }

}
?>
