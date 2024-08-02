<?php
define('MERCHANT_ID', 'TESTLOUIE');
define('MERCHANT_PASSWORD', '12c3fb6d3ed06833bea851b841445228addbe46d');
include('dbcon.php');

//response
$digest = $_POST['digest'] ?? '';
$txnid = $_POST['txnid'] ?? '';
$status = $_POST['status'] ?? '';
$refno = $_POST['refno'] ?? '';
$message = $_POST['message'] ?? '';

$result = 'unknown';
$is_okay = false;

// Translate to your transaction status.
$merchant_statuses = [
	'P' => 'Unpaid+',
	'U' => 'Unpaid',
	'F' => 'Payment_Failed',
	'S' => 'Paid',
	'V' => 'Cancelled', // Merchant cancelled or the user requested for a refund directly through Dragonpay.
	'R' => 'Reversed', // Only for credit cards and other payment methods that can be reversed.
];

if (empty($digest) || empty($txnid) || empty($status) || empty($refno) || empty($message)) {
	$result = 'missing_parameters';
}
else if (!in_array($status, array_keys($merchant_statuses))) {
	$result = 'invalid_status_' . $status;
}
else {
	$digest_data = [
		'txnid' => $txnid,
		'refno' => $refno,
		'status' => $status,
		'message' => $message,
		'key' => MERCHANT_PASSWORD,
	];

	$digest_string = implode(':', $digest_data);
	$digest_compare = sha1($digest_string);

	if($digest_compare != $digest)
	{
		$result = 'digest_error';
	}
	else
	{
		// IMPORTANT: not safe. Use binded parameters or escaped parameters.
		$query = "SELECT * FROM orders where trans_id = $txnid";
		// This will depend on the class which you are using.
		// $statement = $conn->prepare('SELECT * FROM orders WHERE trans_id = ?');
		// $statement->bind_param('s', $txnid);
		// $result = $statement->execute();
		$result = $conn->query($query);

		if ($result->num_rows > 0)
		{
			// IMPORTANT: not safe. Use binded parameters or escaped parameters.
			// Translate the dragonpay payment status to your own status. e.g. S = Paid, etc.
			$merchant_status = $merchant_statuses[$status];
			$query = "UPDATE orders SET status = '$merchant_status', refno = '$refno', message = '$message' WHERE trans_id = '$txnid'";
			// Parameterized  query should look something like this
			// Do you even want to know the payment channel's message?
			// $statement = $conn->prepare('UPDATE orders SET status = ?, refno = ?, message = ? WHERE trans_id = ?');
			// $statement->bind_param('ssss', $merchant_status, $refno, $message, $txnid);
			// $result = $statement->execute(); 

			// IMPORTANT: add logic to check the current status of the transaction. Do not update a transaction if it does not make sense. 
			// Lookup IDEMPOTENCY as well. Your postback handler should handle multiple duplicate notifications and not generate duplicate 
			// orders or shipments. https://www.youtube.com/watch?v=IP-rGJKSZ3s

			// Example: Business logic is Unpaid > Paid > Shipping > Delivered. A status=S should only update an "Unpaid" status.
			// Otherwise, you may end up with an already Delivered item going back to Paid, then "Shipping" could occur again.
			if ($conn->query($query) === TRUE) {
				$result = 'ok';
				$is_okay = true;
			}
			else
			{
				// Log the error, then alert admin? Add logic appropriate for your situation.
				// echo "Error: " . $query . "<br>" . $conn->error;
				$result = 'db_error';
			}
		}
		else {
			$result = 'txn_not_found';
		}
	}
}

if (!$is_okay) {
	// Dragonpay will retry every 5 minutes up to three times (as of September 22, 2020).
	// Retry behavior may change in the future.
	http_response_code(500);
}

// Will default to text/html and will still work, but Dragonpay technically expects a text/plain response
header('Content-Type: text/plain');
// in the format of "result=<RESULT>". Please only use a-zA-Z0-9_ for the <RESULT>
echo "result=$result";

?>