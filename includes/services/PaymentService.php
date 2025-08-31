<?php
// --- Payment Gateway Integration Service ---

class PaymentService
{
    private $pdo;
    private $gateway_api_key; // e.g., for Zarinpal

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        // In a real application, this would be loaded securely from config
        $this->gateway_api_key = 'your-zarinpal-api-key';
    }

    /**
     * Creates a new payment record and returns a redirect URL to the payment gateway.
     *
     * @param int $userId The ID of the user making the payment.
     * @param int $amount The amount to be paid.
     * @param string $description A description for the payment.
     * @param int $appointmentId The associated appointment ID.
     * @return string|null The URL to redirect the user to, or null on failure.
     */
    public function requestPayment(int $userId, int $amount, string $description, int $appointmentId): ?string
    {
        try {
            // 1. Create a new payment record in our database with 'pending' status
            $stmt = $this->pdo->prepare(
                "INSERT INTO payments (user_id, amount, description, status, gateway) VALUES (?, ?, ?, 'pending', 'zarinpal')"
            );
            $stmt->execute([$userId, $amount, $description]);
            $paymentId = $this->pdo->lastInsertId();

            // Link the payment to the appointment
            $stmt = $this->pdo->prepare("UPDATE appointments SET payment_id = ? WHERE id = ?");
            $stmt->execute([$paymentId, $appointmentId]);

            // 2. Make an API call to the payment gateway to get a payment URL.
            // THIS IS A SIMULATION. The code below is a placeholder.

            // $callback_url = BASE_URL . '/payment_callback.php?payment_id=' . $paymentId;
            // $gateway_data = [
            //      'merchant_id' => $this->gateway_api_key,
            //      'amount' => $amount,
            //      'description' => $description,
            //      'callback_url' => $callback_url,
            // ];
            // $response = // ... cURL request to Zarinpal API with $gateway_data ...
            // $result = json_decode($response);

            // if ($result->data->code == 100) {
            //      // On success, the gateway provides a URL to redirect the user to.
            //      $payment_url = 'https://www.zarinpal.com/pg/StartPay/' . $result->data->authority;
            //      return $payment_url;
            // }

            // 3. For this simulation, we'll just return a dummy URL.
            // This simulates a successful request to the gateway.
            $simulated_payment_url = 'payment_callback.php?simulated=true&payment_id=' . $paymentId . '&status=success';

            return $simulated_payment_url;

        } catch (Exception $e) {
            error_log('Payment Request Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Verifies the payment after the user returns from the gateway.
     *
     * @param int $paymentId
     * @param array $gatewayResponse The data sent back from the gateway (e.g., $_GET)
     * @return bool True if payment is successfully verified, false otherwise.
     */
    public function verifyPayment(int $paymentId, array $gatewayResponse): bool
    {
        // 1. In a real scenario, you would get the payment amount from your DB
        //    and make another API call to the gateway to verify the transaction.

        // $stmt = $this->pdo->prepare("SELECT amount FROM payments WHERE id = ?");
        // $stmt->execute([$paymentId]);
        // $amount = $stmt->fetchColumn();

        // $response = // ... cURL request to Zarinpal's verification endpoint ...
        // $result = json_decode($response);

        // if ($result->data->code == 100) {
        //      // Payment is successful, update DB
        //      $stmt = $this->pdo->prepare("UPDATE payments SET status = 'completed', transaction_id = ? WHERE id = ?");
        //      $stmt->execute([$result->data->ref_id, $paymentId]);
        //      return true;
        // }

        // 2. For this simulation, we'll just check the status from the dummy URL.
        if (isset($gatewayResponse['simulated']) && $gatewayResponse['status'] === 'success') {
             $stmt = $this->pdo->prepare("UPDATE payments SET status = 'completed', transaction_id = 'simulated_transaction_123' WHERE id = ?");
             $stmt->execute([$paymentId]);
             return true;
        }

        return false;
    }
}
