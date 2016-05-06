<?php
/*
** 102 PRESTASHOP STOCK UPDATER
** the update file needs 4 fields
** [reference];[wholesale price];[price];[stock]
*/
?>

<?php if (!isset($_POST['update'])) : ?>

	<form method="POST">
		<h1>102 PRESTASHOP Stock Updater</h1>
		
		<?php if (file_exists('update.csv')) : ?>
		
			<input name="update" type="submit" value="UPDATE">
			
		<?php else : ?>
		
			<p>no update.csv file</p>
			
		<?php endif; ?>
	</form> 

<?php else : ?>

	<?php
	
	// CONFIG
	// ---------------------------------------------------------
	$updater['db_ip']		= 'DB server IP';
	$updater['db_name']		= 'DB name';
	$updater['db_user']		= 'DB user';
	$updater['db_pass']		= 'DB pass';
	
	$updater['name']		= 'Contact name';
	$updater['email_from']	= 'Administrator email';
	$updater['email_to']	= 'Notification email';
	// ---------------------------------------------------------
	
	
	$dir = opendir('.');
	
	
	
	// 1. DB CONNECT
	echo '<h3>1. DB Connect</h3>';
	flush();
	
	mysql_connect($updater['db_ip'], $updater['db_user'], $updater['db_pass']) or die(mysql_error());
	mysql_select_db($updater['db_name']) or die(mysql_error());
	
	
	
	// OPTION : ZERO STOCK
	// all undefined products stock 0
	// mysql_query ('UPDATE '.$update_table.' SET ps_stock_available.quantity = 0');
	
	
	
	// 2. OPEN FILE
	echo '<h1>2. Open file</h1>';
	flush();
	$handle = fopen('update.csv', 'r');
	$log = fopen('log.txt', 'w');
	
	
	
	// 3. READ UPDATE DATA
	echo '<h1>3. Read CSV update data</h1>';
	flush();
	$number = 1;
	
	while (($data = fgetcsv($handle, 0, ';')) !== FALSE) :
	
		$num = count($data);
		echo '<div style="background-color:#EEE;padding:10px;margin:10px;">';
		echo '<h2>['.$number.']</h2> ';
		flush();
		$number ++;
		
		unset($ref, $product_ref, $product_id, $combi_ref, $combi_id);
		$combi_ref = '';
		
		for ($csv = 0; $csv <= $num; $csv++) :
	
			// 3.1. REFERENCE : Product or Combination
			if ($csv == 1) :
			
				// search product ...
				$ref = trim($data[($csv - 1)]);
				$query_ref = mysql_query('SELECT * FROM ps_product WHERE reference = "'.$ref.'"') or die (mysql_error());
				$exist_ref = mysql_num_rows($query_ref);
				if ($exist_ref == 0) :
				
					// ... or combination
					$query_ref = mysql_query('SELECT * FROM ps_product_attribute WHERE reference = "'.$ref.'"') or die (mysql_error());
					$exist_ref = mysql_num_rows($query_ref);
					
					if ($exist_ref == 0) :
					
						// reference not found
						fwrite ($log, date('Y/m/d h:i:s').' : reference '.$ref.' not found' . PHP_EOL);
						
					else : // if ($exist_ref == 0)
	
						$query_ids = mysql_query('SELECT id_product_attribute, id_product FROM ps_product_attribute WHERE reference = "'.$ref.'"') or die (mysql_error());
						$array_ids = mysql_fetch_array($query_ids);
						
						$combi_ref	= $ref;
						$combi_id	= $array_ids['id_product_attribute'];
						$product_id	= $array_ids['id_product'];
						
						echo '<h3>COMBINATION</h3>';
						echo '<p>';
						echo '<strong>combi_ref:</strong> '.$combi_ref.'<br />';
						echo '<strong>combi_id:</strong> '.$combi_id.'<br />';
						echo '<strong>product_id:</strong> '.$product_id;
						echo '</p>';
						flush();
						
					endif; // if ($exist_ref == 0)
					
				else : // if ($exist_ref == 0)
					
					$query_ids = mysql_query('SELECT id_product FROM ps_product WHERE reference = "'.$ref.'"') or die (mysql_error());
					$array_ids = mysql_fetch_array($query_ids);
					
					$product_ref	= $ref;
					$product_id		= $array_ids['id_product'];
					
					echo '<h3>PRODUCT</h3>';
					echo '<p>';
					echo '<strong>product_ref:</strong> '.$product_ref.'<br />';
					echo '<strong>product_id:</strong> '.$product_id;
					echo '</p>';
					flush();
					
				endif; // if ($exist_ref == 0)
	
			endif; // if ($csv == 1)
	
	
	
			// 3.2. WHOLESALE PRICE
			if ($csv == 2) :
			
				$wholesale_price = trim($data[($csv - 1)]);
				$wholesale_price = str_replace(',', '.', $wholesale_price);
				if ($wholesale_price == '') :
				
					echo '<h4>no wholesale price variation</h4>';
					flush();	
							
				elseif (is_numeric($wholesale_price)) :
				
					$wholesale_price = number_format($wholesale_price, 6); 
				
					if ($combi_ref) :
	
						// wholesale price combination
						mysql_query('UPDATE ps_product_attribute SET wholesale_price = "'.$wholesale_price.'" WHERE reference = "'.$combi_ref.'"') or die(mysql_error());
						mysql_query('UPDATE ps_product_attribute_shop SET wholesale_price = "'.$wholesale_price.'" WHERE id_product = "'.$product_id.'" AND id_product_attribute = "'.$combi_id.'"') or die(mysql_error());
					
					else :
					
						// wholesale price product
						mysql_query('UPDATE ps_product SET wholesale_price = "'.$wholesale_price.'" WHERE reference = "'.$product_ref.'"') or die(mysql_error());
						mysql_query('UPDATE ps_product_shop SET wholesale_price = "'.$wholesale_price.'" WHERE id_product = "'.$product_id.'"') or die(mysql_error());
					
					endif;
					
					echo '<p><strong>wholesale price:</strong> '.$wholesale_price.'</p>';
					flush();
					
				endif; // elseif (is_numeric($wholesale_price))
				
			endif; // if ($csv == 2)
	
	
	
			// 3.3. PRICE
			if ($csv == 3) :
			
				$price = trim($data[($csv - 1)]);
				$price = str_replace(',', '.', $price);
				if ($price == '') :
				
					echo '<h4>no price variation</h4>';
					flush();			
					
				elseif (is_numeric($price)) :
				
					$price = number_format($price, 6);
					
					if ($combi_ref) :
					
						// price combination
						mysql_query('UPDATE ps_product_attribute SET price = "'.$price.'" WHERE reference = "'.$combi_ref.'"') or die(mysql_error());
						mysql_query('UPDATE ps_product_attribute_shop SET price = "'.$price.'" WHERE id_product = "'.$product_id.'" AND id_product_attribute = "'.$combi_id.'"') or die(mysql_error());

					else :
				
						// price product
						mysql_query('UPDATE ps_product SET price = "'.$price.'" WHERE reference = "'.$product_ref.'"') or die(mysql_error());
						mysql_query('UPDATE ps_product_shop SET price = "'.$price.'" WHERE id_product = "'.$product_id.'"') or die(mysql_error());
					
					endif;
					
					echo '<p><strong>price:</strong> '.$price.'</p>';
					flush();
					
				endif; // elseif (is_numeric($price))
				
			endif; // if ($csv == 3)
	
	
	
			// 3.4. STOCK
			if ($csv == 4) :
			
				$stock = trim($data[($csv - 1)]);
				
				echo '<p>stock: '.$stock.'</p>';
				
				if ($stock == '') :
				
					echo '<h4>no stock variation</h4>';
					flush();
					
				elseif (is_numeric($stock)) :
				
					$stock = number_format($stock, 0);
					
					if ($combi_ref) :
				
						mysql_query('UPDATE ps_stock_available SET quantity = "'.$stock.'" WHERE id_product = "'.$product_id.'" AND id_product_attribute = "'.$combi_id.'"') or die(mysql_error());
						
					else :
					
						mysql_query('UPDATE ps_stock_available SET quantity = "'.$stock.'" WHERE id_product = "'.$product_id.'" AND id_product_attribute = "0"') or die(mysql_error());
					
					endif;
					
					echo '<p><strong>stock:</strong> '.$stock.'</p>';
					flush();
					
					
					// anyway, check product stock based on combinations
					$check_combis = mysql_query('SELECT quantity FROM ps_stock_available WHERE id_product = "'.$product_id.'" AND id_product_attribute != "0"') or die(mysql_error());
					$exist_combis = mysql_num_rows($check_combis);
					if ($exist_combis > 0) :
	
						$stock_query = mysql_query('SELECT SUM(quantity) AS stockproducto FROM ps_stock_available WHERE id_product = "'.$product_id.'" AND id_product_attribute != "0"') or die(mysql_error());
						$stock_total = mysql_result($stock_query, 0);
						mysql_query('UPDATE ps_stock_available SET quantity = "'.$stock_total.'" WHERE id_product = "'.$product_id.'" AND id_product_attribute = "0"') or die(mysql_error());
						echo '<p><strong>product stock:</strong> '.$stock_total.'</p>';
					
					endif;
					
				endif; // elseif (is_numeric($stock))
				
			endif; // if ($csv == 4)
	
		endfor;
		
		echo '</div>';
	
	endwhile;
	
	fclose($handle);
	fclose($log);
	
	
	
	// 4. SEND LOG EMAIL
	$archivo		= file_get_contents('log.txt');
	$name_from		= $updater['name'];
	$email_from		= $updater['email_from'];
	$email_to		= $updater['email_to'];
	$asunto			= '102 PRESTASHOP STOCK UPDATER | log';
	$header			= 'From: ' . $updater['email_from'] . ' <' . $updater['email_from'] . '>\r\n';
	$ok				= mail($email_to, $asunto, $archivo, $header);
	
	echo ($ok) ? '<h2>email sent...</h2>' : '<h2>email error</h2>';

	echo '<h1>UPDATE COMPLETE</h1>';
	flush();
	
	
	
	// 5. DELETE UPDATE.CSV
	unlink('update.csv');
	echo '<h2>update file deleted</h2>';
	flush();
	
	
	
	// 6. END
	echo '<h1>THE END</h1>';
	flush();
	?>


<?php endif; ?>


