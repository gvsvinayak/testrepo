<?php
header("Access-Control-Allow-Origin: *");
require_once $_SERVER['DOCUMENT_ROOT'] . '/of/includes/xmldataservices.inc.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/of/includes/jsondataservices.inc.php';
require_once $_SERVER['DOCUMENT_ROOT'] . "/of/includes/security.include.php";

require_once $_SERVER['DOCUMENT_ROOT'] . '/acgl/acct/div/class.div.php';

global $aryData;
$aryData = json_decode(file_get_contents("php://input"), true);

$dbConn = $securityInfo->getConnection();
$data = array(
    'success' => true,
    'message' => ''
);

$div = new Div($dbConn);
if (empty($aryData)) {
    $aryData = $_REQUEST;
}

$action = getValue('Action'); //
$account = getValue('Account');
$finalOutputData = array();
switch ($action) {
    case 'getInvoices':
	    $vendorID = $_REQUEST['vendorID'];
		$invoiceNumbers = $_REQUEST['invoiceNumbers'];
        $selection = "1|".$vendorID."|".$invoiceNumbers;
        $jsonArr = $div->callEIP($selection);
        $resp = @json_decode($jsonArr, true);
        if (isset($resp['success'])) {
            if ($resp['success'] == 0) {
                echo $jsonArr;
                exit();
            }
        }
		
        while ($invData = $jsonArr->get_next()) {
			//echo "<pre>";print_r($invData);exit;
            $invoiceData = array(
                'vendor_number' => $invData['VendorNumber'],
                'vendor_name' => $invData['VendorName'],
                'invoice_number' => $invData['InvoiceNumber'],
                'invoice_date' => $invData['InvoiceDate'],
                'amount' => $invData['Amount'],
                'discount' => $invData['Discount'],
                'due_date' => $invData['DueDate'],
                'net_amount' => $invData['NetAmount'],
                'po_number' => $invData['PONumber'],
                'check_number' => $invData['CheckNumber'],
                'checkdate' => $invData['CheckDate'],
                'comments' => $invData['Comments'],
                'username' => $invData['UserName']
            );
            $jlEntries = array();
            if ($invData['JL']) {
                foreach ($invData['JL'] as $j) {
                    $journalInfo = explode("$", $j);
                    $jlEntries[] = array(
                        'company' => $journalInfo[0],
                        'journal' => $journalInfo[1],
                        'description' => $journalInfo[2]
                    );
                }
            }
            $invoiceData['journal_info'] = $jlEntries;
            
            $postings = array();
            if ($invData['Postings']) {
                foreach ($invData['Postings'] as $p) {
                    $postingsInfo = explode("$", $p);
                    $postings[] = array(
                        'postdate' => $postingsInfo[0],
                        'company' => $postingsInfo[1],
                        'account' => $postingsInfo[2],
                        'amount' => $postingsInfo[3],
                        'control' => $postingsInfo[4],
                        'control2' => $postingsInfo[5]
                    );
                }
            }
            $invoiceData['postings'] = $postings;
            $finalOutputData[] = $invoiceData;
        }
		
        break;
    case 'getSelectedVendors':
		$vendorNumbers = $_REQUEST['vendorNumbers'];
        $selection = "2|".$vendorNumbers;
        $jsonArr = $div->callEIP($selection);
        $resp = @json_decode($jsonArr, true);
        if (isset($resp['success'])) {
            if ($resp['success'] == 0) {
                echo $jsonArr;
                exit();
            }
        }
        while ($vendorData = $jsonArr->get_next()) {
            $vendorInfo = array(
                'vendor_number' => $vendorData['VendorNumber'],
                'vendor_name' => $vendorData['VendorName'],
                'vendor_name_2' => $vendorData['VendorName2'],
                'vendor_address' => $vendorData['Address'],
                'vendor_address_2' => $vendorData['Address2'],
                'vendor_city_state_zip' => $vendorData['CityStateZip'],
                'vendor_phone' => $vendorData['Phone'],
                'vendor_po_required' => $vendorData['PORequired'],
                'vendor_terms' => $vendorData['Terms'],
                'vendor_discount_percentage' => $vendorData['DiscountPercentage'],
                'vendor_last_check_number' => $vendorData['LastCheckNumber'],
                'vendor_last_check_date' => $vendorData['LastCheckDate'],
                'vendor_last_check_amount' => $vendorData['LastCheckAmount'],
                'vendor_total_amount' => $vendorData['TotalAmount'],
                'vendor_total_discount' => $vendorData['TotalDiscount'],
                'vendor_total_net' => $vendorData['TotalNet'],
                'vendor_distributed_company_accounts' => '',
                'vendor_comments' => ''
            
            );
            if ($vendorData['VendorComments']) {
                $vendorComments = array();
                foreach ($vendorData['VendorComments'] as $vc) {
                    //var_dump($vc);
                    $tempArray = explode('$', $vc);
                    $vendorComments[] = array(
                        'sequence_number' => $tempArray[0],
                        'date' => $tempArray[1],
                        'comment' => $vc[2]
                    );
                }
                $vendorInfo['vendor_comments'] = $vendorComments;
            }
            if ($vendorData['DisctributionCompanyAccounts']) {
                $distributedCompanyAccounts = array();
                $tempArray = explode('$', $vendorData['DisctributionCompanyAccounts']);
                foreach ($tempArray as $t) {
                    $tempArray2 = explode('*', $t);
                    if ($tempArray2[0] != '')
                        $distributedCompanyAccounts[] = array(
                            'company' => $tempArray2[0],
                            'account' => $tempArray2[1]
                        );
                }
                $vendorInfo['vendor_distributed_company_accounts'] = $distributedCompanyAccounts;
            }
            
            $finalOutputData[] = $vendorInfo;
        }
        break;
    case 'getInvoicesOnSelectedDates':
		$tempDate= strtotime($_REQUEST['selectedDate']);
		$selectedDate = strtoupper(date('d',$tempDate).date('M',$tempDate).date('y',$tempDate));
		$selection = "3|".$selectedDate;
        $jsonArr = $div->callEIP($selection);
        $resp = @json_decode($jsonArr, true);
        if (isset($resp['success'])) {
            if ($resp['success'] == 0) {
                echo $jsonArr;
                exit();
            }
        }
        while ($invData = $jsonArr->get_next()) {
            $invoiceData = array(
                'vendor_number' => $invData['VendorNumber'],
                'vendor_name' => $invData['VendorName'],
                'invoice_number' => $invData['InvoiceNumber'],
                'invoice_date' => $invData['InvoiceDate'],
                'amount' => $invData['Amount'],
                'discount' => $invData['Discount'],
                'due_date' => $invData['DueDate'],
                'net_amount' => $invData['NetAmount'],
                'po_number' => $invData['PONumber'],
                'check_number' => $invData['CheckNumber'],
                'checkdate' => $invData['CheckDate'],
                'comments' => $invData['Comments'],
                'username' => $invData['UserName']
            );
            $jlEntries = array();
            if ($invData['JL']) {
                foreach ($invData['JL'] as $j) {
                    $journalInfo = explode("$", $j);
                    $jlEntries[] = array(
                        'company' => $journalInfo[0],
                        'journal' => $journalInfo[1],
                        'posting_date' => $journalInfo[3]
                    );
                }
            }
            $invoiceData['journal_info'] = $jlEntries;
            
            $postings = array();
            if ($invData['Postings']) {
                foreach ($invData['Postings'] as $p) {
                    $postingsInfo = explode("$", $p);
                    $postings[] = array(
                        'company' => $postingsInfo[0],
                        'account' => $postingsInfo[1],
                        'amount' => $postingsInfo[2],
                        'control' => $postingsInfo[3],
                        'control2' => $postingsInfo[4]
                    );
                }
            }
            $invoiceData['postings'] = $postings;
            $finalOutputData[] = $invoiceData;
        }
        break;
    case 'getReconciliationStatement':
        $selection = "4|23JAN17|2033|27548 27623 27633 12458 4521 785445 52165";
        $jsonArr = $div->callEIP($selection);
        $resp = @json_decode($jsonArr, true);
        if (isset($resp['success'])) {
            if ($resp['success'] == 0) {
                echo $jsonArr;
                exit();
            }
        }
        $dueInvoicesButNotOnStatement = array();
        $holdInvoices = array();
        $invoicesWithLaterDueDate = array();
        $paidInvoices = array();
        $reconciliationInvoices = array();
        while ($invData = $jsonArr->get_next()) {
            if ($invData['ReconciliationString']) {
                foreach ($invData['ReconciliationString'] as $inv) {
                    $tempArray = explode("$", $inv);
                    $reconciliationInvoices[] = array(
                        'invoice_number' => $tempArray[0],
                        'invoice_due_date' => $tempArray[1],
                        'invoice_date' => $tempArray[2],
                        'invoice_amount' => $tempArray[3],
                        'invoice_discount' => $tempArray[4],
                        'invoice_net_due' => $tempArray[5]
                    );
                }
            }
            
            if ($invData['DUECNTString']) {
                foreach ($invData['DUECNTString'] as $inv) {
                    $tempArray = explode("$", $inv);
                    $dueInvoicesButNotOnStatement[] = array(
                        'invoice_number' => $tempArray[0],
                        'invoice_due_date' => $tempArray[1],
                        'invoice_date' => $tempArray[2],
                        'invoice_amount' => $tempArray[3],
                        'invoice_discount' => $tempArray[4],
                        'invoice_net_due' => $tempArray[5]
                    );
                }
            }
            if ($invData['HCNTString']) {
                foreach ($invData['HCNTString'] as $inv) {
                    $tempArray = explode("$", $inv);
                    $holdInvoices[] = array(
                        'invoice_number' => $tempArray[0],
                        'invoice_due_date' => $tempArray[1],
                        'invoice_date' => $tempArray[2],
                        'invoice_amount' => $tempArray[3],
                        'invoice_discount' => $tempArray[4],
                        'invoice_net_due' => $tempArray[5]
                    );
                }
            }
            if ($invData['LCNTString']) {
                foreach ($invData['LCNTString'] as $inv) {
                    $tempArray = explode("$", $inv);
                    $invoicesWithLaterDueDate[] = array(
                        'invoice_number' => $tempArray[0],
                        'invoice_due_date' => $tempArray[1],
                        'invoice_date' => $tempArray[2],
                        'invoice_amount' => $tempArray[3],
                        'invoice_discount' => $tempArray[4],
                        'invoice_net_due' => $tempArray[5]
                    );
                }
            }
            if ($invData['PCNTString']) {
                foreach ($invData['PCNTString'] as $inv) {
                    $tempArray = explode("$", $inv);
                    $paidInvoices[] = array(
                        'invoice_number' => $tempArray[0],
                        'invoice_due_date' => $tempArray[1],
                        'invoice_date' => $tempArray[2],
                        'invoice_amount' => $tempArray[3],
                        'check_number' => $tempArray[4],
                        'check_date' => $tempArray[5]
                    );
                }
            }
            
            $finalOutputData['statement_reconciliation'] = $reconciliationInvoices;
            $finalOutputData['due_invoices_but_not_on_statement'] = $dueInvoicesButNotOnStatement;
            $finalOutputData['hold_invoices_on_statement'] = $holdInvoices;
            $finalOutputData['invoices_with_later_due_date_on_statement'] = $invoicesWithLaterDueDate;
            $finalOutputData['invoices_paid_on_statement'] = $paidInvoices;
            $finalOutputData['invoices_not_on_file'] = $invData['NCNTString'];
        }
        break;
}

function getValue($name, $isBoolean = false)
{
    global $aryData;
    $opt = isset($aryData[$name]) ? $aryData[$name] : "";
    if ($isBoolean) {
        $opt = (int) $opt;
    }
    return $opt;
}

echo json_encode(array(
    'success' => 1,
    'data' => $finalOutputData
));
