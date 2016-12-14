<?php
namespace Concrete\Package\CommunityStoreExportData\Controller\SinglePage\Dashboard\Store\Export;

use Core;
use \Concrete\Core\Page\Controller\DashboardPageController;
use Database;
use Concrete\Package\CommunityStore\Src\CommunityStore\Order\OrderList as StoreOrderList;
use Concrete\Package\CommunityStore\Src\CommunityStore\Order\OrderStatus\OrderStatus as StoreOrderStatus;
use \Concrete\Package\CommunityStore\Src\Attribute\Key\StoreOrderKey as StoreOrderKey;
use \Concrete\Package\CommunityStore\Src\CommunityStore\Utilities\Price as Price;

class Sales extends DashboardPageController{
  public function view(){
    $this->set('pageTitle', t('Sales export'));

    $orderStatuses = StoreOrderStatus::getAll();
    $orderStates = array('all' => 'All statuses');
    foreach($orderStatuses as $orderStatus){
      $orderStates[$orderStatus->getHandle()] = $orderStatus->getName();
    }
    $this->set('orderStatuses', $orderStates);

    $db = Database::connection();
    $previousQueries = $db->fetchAll('select * from CommunityStoreExportData where type="sales" order by exportdate desc limit 0,50');
    $this->set('previousQueries', $previousQueries);
  }

  public function exportSales(){
    $postValues = $this->request();

    $orderList = new StoreOrderList();
    $orderList->setFromDate($postValues['dateFrom']);
    $orderList->setToDate($postValues['dateTo']);
    if($postValues['orderStatus'] != 'all'){
      $orderList->setStatus($postValues['orderStatus']);
    }
    if(isset($postValues['paymentStatus'])){
      $orderList->setPaid(1);
    }

    $paginator = $orderList->getPagination();
    $pagination = $paginator->renderDefaultView();
    $orders = $paginator->getCurrentPageResults();

    $orders = $this->generateOrderArray($orders);

    if(isset($postValues['excel'])){
      $this->generateExcel($postValues, $orders);
    }else if(isset($postValues['csv'])){
      $this->generateCsv($postValues, $orders);
    }
    exit;
  }

  public function generateOrderArray($orders){
    $orderArray = array();
    $dh = Core::make('helper/date');
    if(!empty($orders)){
      foreach($orders as $order){
        $oArr = array();
        $oArr['orderdate'] = $dh->formatDateTime($order->getOrderDate());

        $refunded = $order->getRefunded();
        $paid = $order->getPaid();
        $cancelled = $order->getCancelled();
        if ($cancelled) {
          $oArr['paymentStatus'] = 'cancelled';
        } else {
          if ($refunded) {
            $refundreason = $order->getRefundReason();
            $oArr['paymentStatus'] = 'refunded : '.$refundreason;
          } elseif ($paid) {
            $oArr['paymentStatus'] = 'paid';
          } elseif ($order->getTotal() > 0) {
            $oArr['paymentStatus'] = 'not paid';
          } else {
            $oArr['paymentStatus'] = 'free order';
          }
        }

        $oArr['orderemail'] = $order->getAttribute("email");
        //billing
        $oArr['billingfirstname'] = $order->getAttribute("billing_first_name");
        $oArr['billinglastname'] = $order->getAttribute("billing_last_name");
        $oArr['billingphone'] = $order->getAttribute("billing_phone");
        $billingaddress = $order->getAttributeValueObject(StoreOrderKey::getByHandle('billing_address'));
        $billingaddress = str_replace(array('< />', ','), array(' - ', ''), preg_replace('<br>', '', $billingaddress->getValue('displaySanitized', 'display')));
        $billingaddress = preg_replace("/[\n\r]/","",$billingaddress);
        $oArr['billingaddress'] = $billingaddress;

        //shipping
        if ($order->isShippable()) {
          $oArr['shippingfirstname'] = $order->getAttribute("shipping_first_name");
          $oArr['shippinglastname'] = $order->getAttribute("shipping_last_name");
          $shippingaddress = $order->getAttributeValueObject(StoreOrderKey::getByHandle('shipping_address'));
          $shippingaddress = str_replace(array('< />', ','), array(' - ', ''), preg_replace('<br>', '', $shippingaddress->getValue('displaySanitized', 'display')));
          $shippingaddress = preg_replace("/[\n\r]/","",$shippingaddress);
          $oArr['shippingaddress'] = $shippingaddress;
          //shippingtotal
          $oArr['shippingtotal'] = str_replace(array('€', '$'), '', Price::format($order->getShippingTotal()));
          //shippingmethod
          $oArr['shippingmethod'] = $order->getShippingMethodName();
        }else{
          $oArr['shippingfirstname'] = '';
          $oArr['shippinglastname'] = '';
          $oArr['shippingaddress'] = '';
          $oArr['shippingtotal'] = 0;
          $oArr['shippingmethod'] = '';
        }

        //item Quantity
        $items = $order->getOrderItems();
        $numberOfItems = 0;
        foreach($items as $item){
          $numberOfItems = $numberOfItems + $item->getQty();
        }
        $oArr['orderquantity'] = $numberOfItems;
        //subtotal
        $oArr['ordersubtotal'] = str_replace(array('€', '$'), '', Price::format($order->getSubTotal()));
        //Discounts
        $applieddiscounts = $order->getAppliedDiscounts();
        if(!empty($applieddiscounts)){
          if($discount['odValue'] > 0){
            $oArr['discount'] = str_replace(array('€', '$'), '', Price::format($discount['odValue']));
          }else{
            $oArr['discount'] = $discount['odPercentage'].'%';
          }
        }else{
          $oArr['discount'] = 0;
        }

        //taxes
        $taxes = $order->getTaxes();
        $taxAmount = 0;
        if(!empty($taxes)){
          foreach ($taxes as $tax) {
            if($tax['amount']){
              $taxAmount = $taxAmount + $tax['amount'];
            }else{
              $taxAmount = $taxAmount + $tax['amountIncluded'];
            }
          }
        }
        $oArr['taxes'] = str_replace(array('€', '$'), '', Price::format($taxAmount));

        //grand total
        $oArr['grandtotal'] = str_replace(array('€', '$'), '', Price::format($order->getTotal()));

        //payment info
        $oArr['paymentmethod'] = t($order->getPaymentMethodName());
        $oArr['paymenttransactionref'] = $order->getTransactionReference();

        $orderArray[] = $oArr;
      }
    }
    return $orderArray;
  }

  public function generateExcel($data, $orders){
    $iData = array();
    $iData[] = 'sales';
    $iData[] = 'excel';
    $iData[] = json_encode($data);
    $db = Database::connection();
    $db->Execute('insert into CommunityStoreExportData (type, exporttype, exportdate, exportvariables) values (?,?,NOW(),?)', $iData);

    //dirty echo in controller
    header("Content-Type:   application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=export-sales.xls");
    echo '<table>';
      echo '<tr>';
        echo '<th>Order Date</th>';
        echo '<th>Order E-mail</th>';
        echo '<th>Billing First name</th>';
        echo '<th>Billing Last name</th>';
        echo '<th>Billing Phone</th>';
        echo '<th>Billing Address</th>';
        echo '<th>Shipping First name</th>';
        echo '<th>Shipping Last name</th>';
        echo '<th>Shipping address</th>';
        echo '<th>Order quantity</th>';
        echo '<th>Order Subtotal</th>';
        echo '<th>Order discount</th>';
        echo '<th>Order taxes</th>';
        echo '<th>Order Shipping Method</th>';
        echo '<th>Order Shipping Total</th>';
        echo '<th>Order Grand Total</th>';
        echo '<th>Payment Status</th>';
        echo '<th>Payment Method</th>';
        echo '<th>Payment Transaction Ref</th>';
      echo '</tr>';
      if(!empty($orders)){
        foreach($orders as $order){
          echo '<tr>';
            echo '<td>'.$order['orderdate'].'</td>';
            echo '<td>'.$order['orderemail'].'</td>';
            echo '<td>'.$order['billingfirstname'].'</td>';
            echo '<td>'.$order['billinglastname'].'</td>';
            echo '<td>'.$order['billingphone'].'</td>';
            echo '<td>'.$order['billingaddress'].'</td>';
            echo '<td>'.$order['shippingfirstname'].'</td>';
            echo '<td>'.$order['shippinglastname'].'</td>';
            echo '<td>'.$order['shippingaddress'].'</td>';
            echo '<td>'.$order['orderquantity'].'</td>';
            echo '<td>'.$order['ordersubtotal'].'</td>';
            echo '<td>'.$order['discount'].'</td>';
            echo '<td>'.$order['taxes'].'</td>';
            echo '<td>'.$order['shippingmethod'].'</td>';
            echo '<td>'.$order['shippingtotal'].'</td>';
            echo '<td>'.$order['grandtotal'].'</td>';
            echo '<td>'.$order['paymentStatus'].'</td>';
            echo '<td>'.$order['paymentmethod'].'</td>';
            echo '<td>'.$order['paymenttransactionref'].'</td>';
          echo '</tr>';
        }
      }
    echo '</table>';
  }
  public function generateCsv($data, $orders){
    $iData = array();
    $iData[] = 'sales';
    $iData[] = 'csv';
    $iData[] = json_encode($data);
    $db = Database::connection();
    $db->Execute('insert into CommunityStoreExportData (type, exporttype, exportdate, exportvariables) values (?,?,NOW(),?)', $iData);

    header( 'Content-Type: text/csv' );
    header('Content-Disposition: attachment;filename=export-sales.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, array('Order Date','Order e-mail', 'Billing First name', 'Billing Last name', 'Billing Phone', 'Billing address',
  'Shipping First name', 'Shipping Last name', 'Shipping Address', 'Order quantity', 'Order Subtotal', 'Order discount', 'Order taxes',
  'Order Shipping Method', 'Order Shipping Total', 'Order Grand Total', 'Payment Status', 'Payment Method', 'Payment Transaction Ref'));
    if(!empty($orders)){
      foreach($orders as $order){
        fputcsv($out, array($order['orderdate'], $order['orderemail'], $order['billingfirstname'], $order['billinglastname'], $order['billingphone'], $order['billingaddress'],
      $order['shippingfirstname'], $order['shippinglastname'], $order['shippingaddress'], $order['orderquantity'], $order['ordersubtotal'], $order['discount'], $order['taxes'],
      $order['shippingmethod'], $order['shippingtotal'], $order['grandtotal'], $order['paymentStatus'], $order['paymentmethod'], $order['paymenttransactionref']));
      }
    }
    fclose($out);
  }
}
?>
