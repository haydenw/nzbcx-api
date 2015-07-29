<?php

/**
 * Examples for NZBCX class
 */

// Composer autoloader
require __DIR__ . '/vendor/autoload.php';

// Use dotenv to store and retrieve sensitive API information using environment variables
// See https://github.com/vlucas/phpdotenv
$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();

// Import required namespaces
use NZBCX_API\NZBCX;
use NZBCX_API\Exceptions\ExceptionAPICallFailure;
use NZBCX_API\Exceptions\ExceptionAuthenticationFailure;

// Prepare API details
$apiKey = getenv('NZBCX_API_KEY');
$apiSecret = getenv('NZBCX_API_SECRET');
$userId = getenv('NZBCX_API_USER_ID');

//$nzbcx = new NZBCX($apiKey, $apiSecret, $userId); // Live mode
$nzbcx = new NZBCX($apiKey, $apiSecret, $userId, true); // Test mode

try {

	/**
	 * Secure/authenticated examples
	 * See https://nzbcx.com/docs/apiauth
	 */

	// Request account balance
	echo "Requesting account balance\n";
	$accountBalance = $nzbcx->accountBalance();
	if ($accountBalance) {
		echo "BTC Balance: ".$accountBalance->BTC_balance."\n";
		echo "NZD Balance: ".$accountBalance->NZD_balance."\n\n";
	}

	// Get active orders
	echo "Requesting active orders\n";
	$accountOrders = $nzbcx->accountOrders();
	if ($accountOrders && count($accountOrders)) {
		foreach ($accountOrders as $order) {
			echo "Order ".$order->order_id." ".$order->side." ".$order->type." for ".$order->quantity.($order->price > 0 ? " at ".$order->price : '') . "\n";
		}
		echo "\n";
	} else {
		echo "No active orders found\n\n";
	}

	// Create new order
	echo "Creating new order\n";
	$newOrder = $nzbcx->accountOrderNew('BTCNZD', 'BUY', 'MKT', null, '0.05'); // Create order for 0.05 BTC at market prices
	if ($newOrder) {
		echo "Created new order ".$newOrder->order_id." ".$newOrder->side." ".$newOrder->type." for ".$newOrder->quantity.($newOrder->price > 0 ? " at ".$newOrder->price : '') . "\n";
	}
	echo "\n";

	// Check new order status
	if ($newOrder) {
		echo "Requesting new order status\n";
		$newOrderStatus = $nzbcx->accountOrders('A', $newOrder->order_id);
		if ($newOrderStatus && count($newOrderStatus)) {
			echo "New order status: ".$newOrderStatus[0]->status."\n";
		}
		echo "\n";
	}

	// Cancel the order
	if ($newOrder) {
		echo "Cancelling new order\n";
		$nzbcx->accountOrderCancel($newOrder->order_id);
		echo "\n";
	}

	// Verifiying cancelled order status
	if ($newOrder) {
		echo "Verifiying cancelled order status\n";
		$newOrderStatus = $nzbcx->accountOrders(null, $newOrder->order_id);
		if ($newOrderStatus && count($newOrderStatus)) {
			echo "Cancelled order status: ".$newOrderStatus[0]->status."\n";
		}
		echo "\n";
	}

	echo "Done";

	/**
	 * Public Examples
	 * See https://nzbcx.com/docs/api
	 */

	/*
	// Get BTCNZD market FID codes and values
	$fidCodes = $nzbcx->market('BTCNZD');

	// Get BTCNZD market values filtered by LTP (last trade price) FID code
	$ltpValues = $nzbcx->market('BTCNZD', "LTP");

	// Get ticker data for BTCNZD
	$tickerData = $nzbcx->ticker('BTCNZD');

	// Get order book data for BTCNZD
	$orderBookData = $nzbcx->orderBook('BTCNZD');

	// Get trades of BTCNZD for last 30mins
	$trades = $nzbcx->trades('BTCNZD', 0, 30, 0);

	// Get last traded BTCNZD
	$lastTraded = $nzbcx->last('BTCNZD');

	// Get last update of BTCNZD
	$lastUpdate = $nzbcx->lastUpdate('BTCNZD');
	*/

} catch (ExceptionAuthenticationFailure $e) {
	die($e->getMessage());
} catch (ExceptionAPICallFailure $e) {
	die($e->getMessage());
}