<?php
defined('C5_EXECUTE') or die("Access Denied.");
$form = Core::make('helper/form');
?>

	<div class="ccm-search-fields-row"> </div>
	<div class="ccm-search-fields-row">
		<div class="container-fluid">
			<div class="row">
				<div class="col-sm-6">
					<h4>Previous exports</h4>
				</div>
				<div class="col-sm-6">
					<form action="<?=$this->action('exportProducts')?>" method="post" class="form form-inline ccm-search-fields">
						<div class="ccm-search-fields-submit">
							<?php
								echo $form->submit('csv', t('Export CSV'), array('class' => 'btn btn-primary pull-right', 'style' => 'margin-right: 15px;'));
								echo ' &nbsp; ';
								echo $form->submit('excel', t('Export Excel'), array('class' => 'btn btn-success pull-right', 'style' => 'margin-right: 15px;'));
							?>
				    </div>
					</form>
				</div>
			</div>
		</div>
	</div>
<div class="ccm-dashboard-content-full">
	<table class="ccm-search-results-table">
		<thead>
			<tr>
				<th style="padding: 10px 20px;">Date</th>
				<th style="padding: 10px 20px;">Export type</th>
			</tr>
		</thead>
		<tbody>
		<?php
		if(!empty($previousQueries)){
			foreach($previousQueries as $qry){
				echo '<tr>';
					echo '<td>'.$qry['exportdate'].'</td>';
					echo '<td>'.$qry['exporttype'].'</td>';
				echo '</tr>';
			}
		}
		?>
		</tbody>
	</table>
</div>
