<?php
include('dbcon.php'); // This includes the database connection settings

// Capture URL parameters
$txnid = isset($_GET['txn_id']) ? $_GET['txn_id'] : null;
$refno = isset($_GET['ref_no']) ? $_GET['ref_no'] : null;
$status = isset($_GET['status']) ? $_GET['status'] : null;
// $message = isset($_GET['message']) ? $_GET['message'] : null;
$amount = isset($_GET['amount']) ? $_GET['amount'] : null;
$ccy = isset($_GET['ccy']) ? $_GET['ccy'] : null;
$procid = isset($_GET['procid']) ? $_GET['procid'] : null;

// // Logging for debugging
// $log_data = "Received parameters:\n";
// $log_data .= "txn_id: $txnid\nref_no: $refno\nstatus: $status\nmessage: $message\namount: $amount\nccy: $ccy\nprocid: $procid\n";
// file_put_contents('postback.log', $log_data, FILE_APPEND);

// Check if mandatory parameters are available
if ($txnid && $refno && $status) {
    // Check if the transaction exists
    $check_stmt = $conn->prepare("SELECT COUNT(*) FROM transactions WHERE txnid = ? AND refno = ?");
    if (!$check_stmt) {
        file_put_contents('postback.log', "Prepare failed: (" . $conn->errno . ") " . $conn->error . "\n", FILE_APPEND);
        die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
    }
    $check_stmt->bind_param("ss", $txnid, $refno);
    $check_stmt->execute();
    $check_stmt->bind_result($count);
    $check_stmt->fetch();
    $check_stmt->close();

    if ($count == 0) {
        file_put_contents('postback.log', "Transaction not found.\n", FILE_APPEND);
        die("Transaction not found.");
    }

    // http://localhost:8000/postback.php?txn_id=[id]&ref_no=[refno]&status=[S||P]&procid=[BOGX]
    // Update the transaction status in the database
    $stmt = $conn->prepare("UPDATE transactions SET status = ?, procid = ? WHERE txnid = ? AND refno = ?");
    
    if (!$stmt) {
        file_put_contents('postback.log', "Prepare failed: (" . $conn->errno . ") " . $conn->error . "\n", FILE_APPEND);
        die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
    }

    $stmt->bind_param("ssss", $status, $procid, $txnid, $refno);

    if ($stmt->execute()) {
        $affected_rows = $stmt->affected_rows;
        $result = "result=OK, affected rows: $affected_rows";
    } else {
        $result = "result=FAIL: " . $stmt->error;
    }
    $stmt->close();
} else {
    $result = "result=FAIL: Missing parameters";
}

// // Logging for debugging
// file_put_contents('postback.log', $result . "\n", FILE_APPEND);

echo $result;
?>
