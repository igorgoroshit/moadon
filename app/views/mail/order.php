<ul style="list-style:none;padding:20px 50px;direction:rtl;">
	<li style="margin-bottom:10px;">שלום <?= $client['firstName']?> <?=$client['lastName']?>,</li>
	<li style="margin-bottom:10px;">תודה שרכשת במועדון של ווב טק!</li>
	<li style="margin-bottom:10px;">קוד הזמנה: <?=$code?></li>
	<li>להלן פרטי ההזמנה:</li>
	<?php foreach ($items as $item) { ?>
		<li>
			<?=$item['supplierName']?> - <?= $item['name']?> ,כמות מוזמנת: <?=$item['qty']?> 
			<?php if($item['notes']!="") { ?>
				<span style="color:red;">(*<?=$item['notes']?>)</span>
			<?php } ?>
		</li>
	<?php } ?>
	<li style="margin-top:10px;"><strong>למימוש הזמנה יש להציג קוד הזמנה בקופה.</strong></li>
	<li style="margin-bottom:10px;"><strong>יש לתאם מראש לפני הגעה:</strong></li>
	<?php  foreach ($suppliers as $supplier) { ?>
		<li><?=$supplier['supplierName']?>: <?=$supplier['phone2']?> ,<?=$supplier['city']?></li>
	<?php }?>
	<li style="margin-top:10px;">מועדון העובדים של ווב טק</li>
</ul>



 