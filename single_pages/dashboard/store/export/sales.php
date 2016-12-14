<?php
defined('C5_EXECUTE') or die("Access Denied.");
$form = Core::make('helper/form');
if(!isset($dateFrom)){
	$dateFrom = date('Y-m-d', strtotime('-1 month'));
}
?>

<div class="ccm-dashboard-content-full">
	<form action="<?=$this->action('exportSales')?>" method="post" class="form form-inline ccm-search-fields">
		<div class="ccm-search-fields-row">
			<div class="form-group form-group-full">
        		<?= $form->label('dateFrom', t('From'))?>
        		<div class="ccm-search-field-content ccm-search-field-content-select2">
					<?= Core::make('helper/form/date_time')->date('dateFrom', $dateFrom); ?>
				</div>
			</div>
		</div>
		<div class="ccm-search-fields-row">
			<div class="form-group form-group-full">
				<?= $form->label('dateFrom', t('To'))?>
				<div class="ccm-search-field-content ccm-search-field-content-select2">
					<?= Core::make('helper/form/date_time')->date('dateTo', $dateTo); ?>
				</div>
			</div>
		</div>
		<div class="ccm-search-fields-row">
			<div class="form-group form-group-full">
				<?= $form->label('orderStatus', t('Status'))?>
				<div class="ccm-search-field-content ccm-search-field-content-select2">
					<?= Core::make('helper/form')->select('orderStatus', $orderStatuses, $_POST['orderStatus']); ?>
				</div>
			</div>
		</div>
		<div class="ccm-search-fields-row">
			<div class="form-group form-group-full">
				<?= $form->label('paymentStatus', t('Only paid orders'))?>
				<div class="ccm-search-field-content ccm-search-field-content-select2">
					<?= Core::make('helper/form')->checkbox('paymentStatus', $_POST['paymentStatus']); ?>
				</div>
			</div>
		</div>
		<div class="ccm-search-fields-row">
			<div class="container-fluid">
				<div class="row">
					<div class="col-sm-6">
						<h4>Previous exports</h4>
					</div>
					<div class="col-sm-6">
						<div class="ccm-search-fields-submit">
							<?php
								echo $form->submit('csv', t('Export CSV'), array('class' => 'btn btn-primary pull-right', 'style' => 'margin-right: 15px;'));
								echo ' &nbsp; ';
								echo $form->submit('excel', t('Export Excel'), array('class' => 'btn btn-success pull-right', 'style' => 'margin-right: 15px;'));
							?>
				    </div>
					</div>
				</div>
			</div>
		</div>
	</form>
	<table class="ccm-search-results-table">
		<thead>
			<tr>
				<th style="padding: 10px 20px;">Date</th>
				<th style="padding: 10px 20px;">Export type</th>
				<th style="padding: 10px 20px;">Filters</th>
			</tr>
		</thead>
		<tbody>
			<?php
			if(!empty($previousQueries)){
				foreach($previousQueries as $qry){
					echo '<tr>';
						echo '<td>'.$qry['exportdate'].'</td>';
						echo '<td>'.$qry['exporttype'].'</td>';
						$data = json_decode($qry['exportvariables']);
						echo '<td>From: '.$data->dateFrom.'<br/>To: '.$data->dateTo.'<br/>Status: '.$data->orderStatus.'</td>';
					echo '</tr>';
				}
			}
			?>
		</tbody>
	</table>
</div>
