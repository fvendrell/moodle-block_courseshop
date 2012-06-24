<?php

    // Security
    if (!defined('MOODLE_INTERNAL')) die("You are not authorized to run this file directly");

    $sortorder = optional_param('order', 'id', PARAM_TEXT);
    $dir = optional_param('dir', 'ASC', PARAM_TEXT);
    $cmd = optional_param('cmd', '', PARAM_TEXT);
    $status = optional_param('status', 'ALL', PARAM_TEXT);

    $statusclause = ($status != 'ALL') ? " AND b.status = '$status' " : '' ;
    
    $url = $CFG->wwwroot."/blocks/courseshop/bills/view.php?view=viewAllBills&id=$id";

    if ($cmd != ''){
        include $CFG->dirroot.'/blocks/courseshop/bills/viewAllBills.controller.php';
    }

    $sql = "
        SELECT 
           b.*,
           c.firstname,
           c.lastname,
           c.email,
           DATE_FORMAT(FROM_UNIXTIME(emissiondate), \"%Y%m%d\") as date
        FROM 
           {$CFG->prefix}courseshop_bill as b,
           {$CFG->prefix}courseshop_customer as c
        WHERE
           b.userid = c.id
           $statusclause
        ORDER BY 
           `{$sortorder}` {$dir}
    ";
    
    if ($bills = get_records_sql($sql)){
	
        foreach ($bills as $bill) {
          $billsbystate[$bill->status][$bill->id] = $bill;		  	  
        }
    } else {
        $billsbystate = array();
    }
    
    print_heading_with_help(get_string('billing', 'block_courseshop'), 'billstates', 'block_courseshop');

/// print tabs

    $total->WORKING = count_records('courseshop_bill', 'status', 'WORKING');
    $total->PENDING = count_records('courseshop_bill', 'status', 'PENDING');
    $total->DELAYED = count_records('courseshop_bill', 'status', 'DELAYED');
    $total->SOLDOUT = count_records('courseshop_bill', 'status', 'SOLDOUT');
    $total->COMPLETE = count_records('courseshop_bill', 'status', 'COMPLETE');
    $total->CANCELLED = count_records('courseshop_bill', 'status', 'CANCELLED');
    $total->FAILED = count_records('courseshop_bill', 'status', 'FAILED');
    $total->PAYBACK = count_records('courseshop_bill', 'status', 'PAYBACK');
    $total->ALL = count_records('courseshop_bill');

    $rows[0][] = new tabobject('WORKING', "$url&status=WORKING", get_string('bill_WORKINGs', 'block_courseshop').' ('.$total->WORKING.')');
    $rows[0][] = new tabobject('PENDING', "$url&status=PENDING", get_string('bill_PENDINGs', 'block_courseshop').' ('.$total->PENDING.')');
    $rows[0][] = new tabobject('DELAYED', "$url&status=DELAYED", get_string('bill_DELAYEDs', 'block_courseshop').' ('.$total->DELAYED.')');
    $rows[0][] = new tabobject('SOLDOUT', "$url&status=SOLDOUT", get_string('bill_SOLDOUTs', 'block_courseshop').' ('.$total->SOLDOUT.')');
    $rows[0][] = new tabobject('COMPLETE', "$url&status=COMPLETE", get_string('bill_COMPLETEs', 'block_courseshop').' ('.$total->COMPLETE.')');
    $rows[0][] = new tabobject('CANCELLED', "$url&status=CANCELLED", get_string('bill_CANCELLEDs', 'block_courseshop').' ('.$total->CANCELLED.')');
    $rows[0][] = new tabobject('FAILED', "$url&status=FAILED", get_string('bill_FAILEDs', 'block_courseshop').' ('.$total->FAILED.')');
    $rows[0][] = new tabobject('PAYBACK', "$url&status=PAYBACK", get_string('bill_PAYBACKs', 'block_courseshop').' ('.$total->PAYBACK.')');
    $rows[0][] = new tabobject('ALL', "$url&status=ALL", get_string('bill_ALLs', 'block_courseshop').' ('.$total->ALL.')');
    
    print_tabs($rows, $status);

/// print bills
    
    $subtotal = 0;

    if (empty($billsbystate)) {
        print_box_start();
        print_string('nobills', 'block_courseshop');
        print_box_end();
    } else {
?>
<table width="100%" class="generaltable">
    <tr>
       <th class="header c0"> 
       </th>
       <th class="header c1">
            <?php print_string('num', 'block_courseshop') ?>
       </th>
       <th class="header c2">
            <?php print_string('label', 'block_courseshop') ?>
       </th>
       <th class="header c3">
            <?php print_string('transaction', 'block_courseshop') ?>
       </th>
       <th class="header lastcol">
            <?php print_string('amount', 'block_courseshop') ?>
       </th>
    </tr>
<?php
       $i = 0;
       foreach (array_keys($billsbystate) as $status) {
?>
    <tr>
        <td colspan="5" class="grouphead">
           <b><?php print_string('bill_' . $status . 's', 'block_courseshop') ?></b>
        </td>
    </tr>
<?php
        $CFG->subtotal = 0;
        foreach ($billsbystate[$status] as $portlet){
			
			 $subtotal += floor($portlet->amount * 100) / 100;
             include ($CFG->dirroot.'/blocks/courseshop/lib/billMerchantLine.portlet.php');
        }
?>
    <tr>
        <td colspan="2" class="groupSubtotal">
        </td>
        <td colspan="3" align="right" class="groupsubtotal">
            <?php echo round($subtotal, 2) ?> <?php echo $CFG->block_courseshop_defaultcurrency ?>
        </td>
    </tr>
<?php
        $i++;
        }
        echo '</table>';
    }
?>
<table width="100%">
   <tr>
      <td align="left">
      </td>
      <td align="right">
         <a href="<?php echo $CFG->wwwroot."/blocks/courseshop/bills/edit_bill.php?id={$id}" ?>"><?php print_string('newbill', 'block_courseshop') ?></a>
      </td>
   </tr>
</table>
<br />
